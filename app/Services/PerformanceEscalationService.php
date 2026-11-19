<?php

namespace App\Services;

use App\Enums\PerformanceEscalationClosureReason;
use App\Enums\PerformanceEscalationStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceEscalation;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Performance escalation cases.
 *
 * The table accepts four subject types (department, employee, branch,
 * supervisor) from the first migration, but only the department rule is
 * evaluated by the scheduled run for now. The service itself is subject
 * agnostic: opening and closing receive the company, subject type/id, rule key,
 * score availability, calculation rules version and threshold.
 */
class PerformanceEscalationService
{
    public const RULE_DEPARTMENT_OPERATIONAL = 'department_operational';

    /**
     * Evaluate every company department and open/close cases, then retry any
     * pending opening notifications. Two aggregated queries cover all
     * departments, so the run never issues one query per department.
     *
     * @return array{companies: int, departments: int, opened: int, closed: int, notified: int, failed_notifications: int}
     */
    public function evaluateAll(): array
    {
        $companyIds = Company::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $summary = [
            'companies' => count($companyIds),
            'departments' => 0,
            'opened' => 0,
            'closed' => 0,
            'notified' => 0,
            'failed_notifications' => 0,
        ];

        if ($companyIds === []) {
            return $summary;
        }

        $taskCounts = self::countMap(PerformanceScoreService::overdueTasksByScope($companyIds, null, null));
        $deficiencyCounts = self::countMap(PerformanceScoreService::overdueDeficienciesByScope($companyIds, null, null));

        $openCases = PerformanceEscalation::query()
            ->open()
            ->where('subject_type', PerformanceEscalation::SUBJECT_DEPARTMENT)
            ->where('rule_key', self::RULE_DEPARTMENT_OPERATIONAL)
            ->get()
            ->keyBy(fn (PerformanceEscalation $escalation) => self::caseKey(
                (int) $escalation->company_id,
                $escalation->subject_type,
                (int) $escalation->subject_id,
                $escalation->rule_key,
            ));

        foreach ($this->companyDepartmentPairs($companyIds) as [$companyId, $departmentId]) {
            $summary['departments']++;

            $score = PerformanceScoreService::scoreFromCounts(
                $taskCounts[$companyId][$departmentId] ?? 0,
                $deficiencyCounts[$companyId][$departmentId] ?? 0,
            );

            // An unavailable score never opens or closes a case.
            if (! $score->available) {
                continue;
            }

            $key = self::caseKey($companyId, PerformanceEscalation::SUBJECT_DEPARTMENT, $departmentId, self::RULE_DEPARTMENT_OPERATIONAL);
            $openCase = $openCases->get($key);

            if (self::shouldOpen($score->score, PerformanceScoreService::REVIEW_THRESHOLD)) {
                if ($openCase === null) {
                    $opened = $this->open(
                        companyId: $companyId,
                        subjectType: PerformanceEscalation::SUBJECT_DEPARTMENT,
                        subjectId: $departmentId,
                        ruleKey: self::RULE_DEPARTMENT_OPERATIONAL,
                        score: $score,
                        threshold: PerformanceScoreService::REVIEW_THRESHOLD,
                    );

                    if ($opened !== null) {
                        $openCases->put($key, $opened);
                        $summary['opened']++;
                    }
                }

                continue;
            }

            if ($openCase !== null && $this->closeForRecovery($openCase, $score)) {
                $openCases->forget($key);
                $summary['closed']++;
            }
        }

        $notifications = $this->notifyPending();
        $summary['notified'] = $notifications['sent'];
        $summary['failed_notifications'] = $notifications['failed'];

        return $summary;
    }

    /**
     * Decision rule shared by every subject type: anything below the threshold
     * opens a case, and the threshold itself counts as recovered.
     */
    public static function shouldOpen(int|float $score, ?int $threshold = null): bool
    {
        return $score < ($threshold ?? PerformanceScoreService::REVIEW_THRESHOLD);
    }

    /**
     * Open a case for one company/subject/rule. Returns null when the subject
     * does not belong to the company, the score is unavailable, or another run
     * already opened the same case (in-transaction check plus the database
     * unique key).
     */
    public function open(
        int $companyId,
        string $subjectType,
        int $subjectId,
        string $ruleKey,
        object $score,
        int $threshold = PerformanceScoreService::REVIEW_THRESHOLD,
    ): ?PerformanceEscalation {
        if (! $score->available || ! self::subjectBelongsToCompany($subjectType, $subjectId, $companyId)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($companyId, $subjectType, $subjectId, $ruleKey, $score, $threshold) {
                $exists = PerformanceEscalation::query()
                    ->open()
                    ->where('company_id', $companyId)
                    ->where('subject_type', $subjectType)
                    ->where('subject_id', $subjectId)
                    ->where('rule_key', $ruleKey)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    return null;
                }

                return PerformanceEscalation::create([
                    'company_id' => $companyId,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'rule_key' => $ruleKey,
                    'status' => PerformanceEscalationStatus::OPEN,
                    'score_at_open' => $score->score,
                    'threshold' => $threshold,
                    'rules_version' => $score->rules_version,
                    'opened_at' => now(),
                    'open_slot' => 1,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /**
     * Record a management action while the case stays open.
     */
    public function recordManagementAction(PerformanceEscalation $escalation, User $user, string $action): bool
    {
        if (! $escalation->isOpen()) {
            return false;
        }

        $escalation->forceFill([
            'management_action' => $action,
            'management_action_by_user_id' => $user->getKey(),
            'management_action_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Deliver alerts for open cases that still need notification.
     *
     * @return array{sent: int, failed: int}
     */
    public function notifyPending(): array
    {
        $sent = 0;
        $failed = 0;

        PerformanceEscalation::query()
            ->open()
            ->whereNull('notified_at')
            ->each(function (PerformanceEscalation $escalation) use (&$sent, &$failed): void {
                $result = $this->notifyIfPending((int) $escalation->getKey());

                if ($result === true) {
                    $sent++;
                } elseif ($result === false) {
                    $failed++;
                }
            });

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function notifyIfPending(int $escalationId): ?bool
    {
        try {
            return DB::transaction(function () use ($escalationId): ?bool {
                $escalation = PerformanceEscalation::query()
                    ->lockForUpdate()
                    ->find($escalationId);

                if (! $escalation?->isOpen() || $escalation->notified_at !== null) {
                    return null;
                }

                $escalation->load(['company', 'subject']);

                NotificationDispatcher::translated(
                    $escalation,
                    'PerformanceEscalationOpened',
                    'performance_escalations.notifications.opened_title',
                    'performance_escalations.notifications.opened_body',
                    [
                        'subject' => $escalation->subjectLabel(),
                        'company' => $escalation->company?->trade_name ?? '',
                        'score' => (int) $escalation->score_at_open,
                        'threshold' => (int) $escalation->threshold,
                    ],
                    icon: 'heroicon-o-exclamation-triangle',
                    color: 'warning',
                    synchronous: true,
                );

                $escalation->forceFill(['notified_at' => now()])->saveQuietly();

                return true;
            });
        } catch (Throwable $exception) {
            Log::error('Performance escalation notification failed.', [
                'escalation_id' => $escalationId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Escalations are only meaningful for viewers allowed to see the whole
     * department. Branch-restricted and supervisor/team viewers are excluded,
     * plus anyone without the escalation view permission.
     */
    public static function canViewEscalations(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdminOrOwner()) {
            return true;
        }

        if (EmployeePerformanceAccessService::isSupervisor($user)) {
            return false;
        }

        if ($user->branch_id && ! $user->canAccessAllBranches()) {
            return false;
        }

        return $user->can('view_any_performance_escalation');
    }

    /**
     * Explicit subject validation inside the unit: unknown types or subjects
     * outside the company scope never open a case.
     */
    public static function subjectBelongsToCompany(string $subjectType, int $subjectId, int $companyId): bool
    {
        if (! array_key_exists($subjectType, PerformanceEscalation::SUBJECT_TYPES)) {
            return false;
        }

        return match ($subjectType) {
            PerformanceEscalation::SUBJECT_DEPARTMENT => Department::query()
                ->whereKey($subjectId)
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereHas('companies', fn ($sub) => $sub->where('companies.id', $companyId));
                })
                ->exists(),
            PerformanceEscalation::SUBJECT_EMPLOYEE => Employee::query()
                ->whereKey($subjectId)
                ->where('company_id', $companyId)
                ->exists(),
            PerformanceEscalation::SUBJECT_BRANCH => Branch::query()
                ->whereKey($subjectId)
                ->where('company_id', $companyId)
                ->exists(),
            PerformanceEscalation::SUBJECT_SUPERVISOR => User::query()
                ->whereKey($subjectId)
                ->whereHas('employee', fn ($query) => $query->where('company_id', $companyId))
                ->exists(),
            default => false,
        };
    }

    private function closeForRecovery(PerformanceEscalation $escalation, object $score): bool
    {
        return DB::transaction(function () use ($escalation, $score) {
            $fresh = PerformanceEscalation::query()
                ->whereKey($escalation->getKey())
                ->lockForUpdate()
                ->first();

            if (! $fresh?->isOpen()) {
                return false;
            }

            $fresh->forceFill([
                'status' => PerformanceEscalationStatus::CLOSED,
                'closure_reason' => PerformanceEscalationClosureReason::RECOVERED,
                'close_score' => $score->score,
                'closed_at' => now(),
                'open_slot' => null,
            ])->save();

            return true;
        });
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private function companyDepartmentPairs(array $companyIds): array
    {
        $companyLookup = array_flip($companyIds);

        $departments = Department::query()
            ->where(function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds)
                    ->orWhereHas('companies', fn ($sub) => $sub->whereIn('companies.id', $companyIds));
            })
            ->with(['companies' => fn ($query) => $query->whereIn('companies.id', $companyIds)->select('companies.id')])
            ->get(['id', 'company_id']);

        $pairs = [];

        foreach ($departments as $department) {
            $ids = [];

            if ($department->company_id !== null && isset($companyLookup[(int) $department->company_id])) {
                $ids[] = (int) $department->company_id;
            }

            foreach ($department->companies as $company) {
                $ids[] = (int) $company->id;
            }

            foreach (array_unique($ids) as $companyId) {
                $pairs[] = [$companyId, (int) $department->id];
            }
        }

        return $pairs;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private static function countMap(Collection $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->company_id][(int) $row->department_id] = (int) $row->count;
        }

        return $map;
    }

    private static function caseKey(int $companyId, string $subjectType, int $subjectId, string $ruleKey): string
    {
        return implode(':', [$companyId, $subjectType, $subjectId, $ruleKey]);
    }
}
