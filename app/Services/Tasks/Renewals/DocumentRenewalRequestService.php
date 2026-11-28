<?php

namespace App\Services\Tasks\Renewals;

use App\Enums\GeneralStatus;
use App\Filament\Pages\DocumentRenewals;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Invoice;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DocumentRenewalRequestService
{
    /**
     * Scan whitelisted document types for due/expiring cycles.
     * When $dryRun is true, performs zero writes (no tasks, requests, invoices, or notifications).
     *
     * @param  array<int, string>|null  $types
     * @return array{
     *     dry_run: bool,
     *     as_of: string,
     *     lead_days: int,
     *     since_date: ?string,
     *     due_count: int,
     *     existing_count: int,
     *     created_count: int,
     *     unassignable_count: int,
     *     excluded_historical_count: int,
     *     missing_or_invalid_date_count: int,
     *     by_type: array<string, array{due: int, existing: int, created: int, unassignable: int, excluded_historical: int, invalid_date: int}>
     * }
     */
    public static function scan(
        ?array $types = null,
        bool $dryRun = false,
        ?Carbon $asOfDate = null,
        ?string $sinceDate = null,
    ): array {
        $today = ($asOfDate ? $asOfDate->copy() : Carbon::now(config('app.timezone')))->startOfDay();
        $leadDays = RenewalDocumentRegistry::leadDays();
        $cutoffDate = $today->copy()->addDays($leadDays)->toDateString();
        $effectiveSince = filled($sinceDate)
            ? Carbon::parse($sinceDate)->toDateString()
            : RenewalDocumentRegistry::trackingStartDate();

        $selectedTypes = $types !== null && $types !== []
            ? array_values(array_filter($types, fn (string $t) => RenewalDocumentRegistry::isValidType($t)))
            : RenewalDocumentRegistry::allTypes();

        $summary = [
            'dry_run' => $dryRun,
            'as_of' => $today->toDateString(),
            'lead_days' => $leadDays,
            'since_date' => $effectiveSince,
            'due_count' => 0,
            'existing_count' => 0,
            'created_count' => 0,
            'unassignable_count' => 0,
            'excluded_historical_count' => 0,
            'missing_or_invalid_date_count' => 0,
            'by_type' => [],
        ];

        $eligibleResolver = DocumentRenewalAccessService::buildEligibleManagersResolver();

        foreach ($selectedTypes as $type) {
            $summary['by_type'][$type] = [
                'due' => 0,
                'existing' => 0,
                'created' => 0,
                'unassignable' => 0,
                'excluded_historical' => 0,
                'invalid_date' => 0,
            ];

            $def = RenewalDocumentRegistry::definition($type);

            match ($def['source_class']) {
                EmployeeDetail::class => self::scanEmployeeDetailType($type, $cutoffDate, $effectiveSince, $dryRun, $summary, $eligibleResolver),
                Employee::class => self::scanEmployeeType($type, $cutoffDate, $effectiveSince, $dryRun, $summary, $eligibleResolver),
                Company::class => self::scanCompanyType($type, $cutoffDate, $effectiveSince, $dryRun, $summary, $eligibleResolver),
                default => null,
            };
        }

        return $summary;
    }

    /**
     * Create or retrieve the single shared renewal request and its draft expense invoice
     * for a specific source document and expiry cycle.
     *
     * @param  Collection<int, Employee>|null  $precomputedEligibleManagers
     * @return array{request: UnifiedDeficiencyTask, created: bool, eligible_count: int}
     */
    public static function ensureRequest(
        string $documentType,
        Model $source,
        int $companyId,
        ?int $branchId,
        string $expiryDate,
        string $sourceLabel,
        ?Collection $precomputedEligibleManagers = null,
    ): array {
        $normalizedExpiry = Carbon::parse($expiryDate)->toDateString();
        $sourceClass = $source::class;
        $sourceId = (int) $source->getKey();
        $taskKey = RenewalDocumentRegistry::buildTaskKey(
            companyId: $companyId,
            sourceClass: $sourceClass,
            sourceId: $sourceId,
            documentType: $documentType,
            expiryDate: $normalizedExpiry,
        );

        $eligibleManagers = $precomputedEligibleManagers
            ?? DocumentRenewalAccessService::eligibleManagersFor($companyId, $documentType);

        $existing = UnifiedDeficiencyTask::query()->where('task_key', $taskKey)->first();
        if ($existing) {
            return ['request' => $existing, 'created' => false, 'eligible_count' => $eligibleManagers->count()];
        }

        try {
            $request = DB::transaction(function () use (
                $documentType,
                $source,
                $sourceClass,
                $sourceId,
                $companyId,
                $branchId,
                $normalizedExpiry,
                $sourceLabel,
                $taskKey,
                $eligibleManagers
            ): UnifiedDeficiencyTask {
                $freshSource = $source->fresh();
                if (! $freshSource) {
                    throw new \RuntimeException('Source record no longer exists.');
                }

                $currentExpiry = RenewalDocumentRegistry::extractExpiryDate($documentType, $freshSource);
                if ($currentExpiry !== $normalizedExpiry) {
                    throw new \RuntimeException('Source expiry date changed during scan.');
                }

                $already = UnifiedDeficiencyTask::query()->where('task_key', $taskKey)->lockForUpdate()->first();
                if ($already) {
                    return $already;
                }

                $typeLabel = RenewalDocumentRegistry::label($documentType);
                $docNumber = RenewalDocumentRegistry::extractDocumentNumber($documentType, $freshSource);
                $snapshot = RenewalDocumentRegistry::settingsSnapshot();

                $task = UnifiedDeficiencyTask::query()->create([
                    'company_id' => $companyId,
                    'taskable_type' => $sourceClass,
                    'taskable_id' => $sourceId,
                    'service_transaction_id' => null,
                    'task_key' => $taskKey,
                    'deficiency_type' => UnifiedDeficiencyTask::TYPE_DOCUMENT_RENEWAL,
                    'title' => __('document_renewals.request.title_format', [
                        'type' => $typeLabel,
                        'target' => $sourceLabel,
                    ]),
                    'description' => __('document_renewals.request.description_format', [
                        'type' => $typeLabel,
                        'target' => $sourceLabel,
                        'expiry' => $normalizedExpiry,
                    ]),
                    'responsible_employee_id' => null,
                    'assigned_by_user_id' => null,
                    'status' => UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE,
                    'due_date' => null,
                    'metadata' => [
                        'document_type' => $documentType,
                        'cycle_expiry_date' => $normalizedExpiry,
                        'document_number' => $docNumber,
                        'source_label' => $sourceLabel,
                        'branch_id' => $branchId,
                        'settings_snapshot' => $snapshot,
                        'unassignable' => $eligibleManagers->isEmpty(),
                        'submission_version' => 0,
                        'submission_history' => [],
                    ],
                ]);

                $invoice = Invoice::query()->create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'invoice_type_id' => null,
                    'customer_id' => null,
                    'employee_id' => $freshSource instanceof Employee
                        ? $freshSource->id
                        : ($freshSource instanceof EmployeeDetail ? $freshSource->employee_id : null),
                    'issue_date' => Carbon::now(config('app.timezone'))->toDateString(),
                    'total_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'remaining_amount' => 0.00,
                    'is_paid_by_applicant' => false,
                    'created_by' => null,
                    'notes' => __('document_renewals.invoice.draft_notes', [
                        'type' => $typeLabel,
                        'target' => $sourceLabel,
                    ]),
                    'metadata' => [
                        'document_purpose' => Invoice::PURPOSE_RENEWAL_EXPENSE,
                        'renewal_request_id' => $task->id,
                        'document_type' => $documentType,
                        'cycle_expiry_date' => $normalizedExpiry,
                        'renewal_workflow_status' => 'draft',
                        'renewal_fee_mode' => null,
                    ],
                ]);

                $meta = $task->metadata ?? [];
                $meta['invoice_id'] = $invoice->id;
                $task->update(['metadata' => $meta]);

                DB::afterCommit(function () use ($task, $documentType, $eligibleManagers): void {
                    self::notifyEligibleManagers($task, $documentType, $eligibleManagers);
                });

                return $task;
            });

            return [
                'request' => $request,
                'created' => true,
                'eligible_count' => $eligibleManagers->count(),
            ];
        } catch (QueryException $e) {
            $duplicate = UnifiedDeficiencyTask::query()->where('task_key', $taskKey)->first();
            if ($duplicate) {
                return [
                    'request' => $duplicate,
                    'created' => false,
                    'eligible_count' => $eligibleManagers->count(),
                ];
            }

            throw $e;
        }
    }

    /**
     * Notify currently eligible managers about an available renewal request.
     * Re-checks live eligibility and availability before sending.
     *
     * @param  Collection<int, Employee>|null  $precomputedEligibleManagers
     */
    public static function notifyEligibleManagers(
        UnifiedDeficiencyTask $request,
        string $documentType,
        ?Collection $precomputedEligibleManagers = null,
    ): void {
        $fresh = $request->fresh();
        if (! $fresh || $fresh->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE || $fresh->responsible_employee_id !== null) {
            return;
        }

        $eligible = $precomputedEligibleManagers
            ?? DocumentRenewalAccessService::eligibleManagersFor((int) $fresh->company_id, $documentType);

        $recipients = $eligible
            ->map(fn (Employee $emp) => $emp->user)
            ->filter(fn ($user) => $user instanceof User && DocumentRenewalAccessService::isUserActive($user))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $actionUrl = null;
        try {
            $actionUrl = DocumentRenewals::getUrl();
        } catch (\Throwable) {
            $actionUrl = null;
        }

        $notification = new BaseSystemNotification(
            eventKey: 'document_renewal_available',
            customTitle: __('document_renewals.notifications.available_title'),
            customMessage: __('document_renewals.notifications.available_body', [
                'type' => RenewalDocumentRegistry::label($documentType),
                'target' => (string) ($fresh->metadata['source_label'] ?? ''),
                'expiry' => (string) ($fresh->metadata['cycle_expiry_date'] ?? ''),
            ]),
            icon: 'heroicon-o-document-check',
            color: 'warning',
            actionUrl: $actionUrl,
            recordType: UnifiedDeficiencyTask::class,
            recordId: $fresh->getKey(),
            context: [
                'routing_mode' => 'document_renewal_targeted',
                'renewal_event' => 'available',
            ],
        );

        Notification::send($recipients, $notification);
    }

    private static function scanEmployeeDetailType(
        string $type,
        string $cutoffDate,
        ?string $effectiveSince,
        bool $dryRun,
        array &$summary,
        \Closure $eligibleResolver,
    ): void {
        $def = RenewalDocumentRegistry::definition($type);
        $field = $def['expiry_field'];

        EmployeeDetail::query()
            ->whereNotNull($field)
            ->where($field, '<=', $cutoffDate)
            ->whereHas('employee', fn ($q) => $q->where('status', GeneralStatus::ACTIVE->value)->whereNotNull('company_id'))
            ->with(['employee.user', 'employee.company'])
            ->chunkById(200, function ($details) use ($type, $effectiveSince, $dryRun, &$summary, $eligibleResolver): void {
                $candidates = [];
                foreach ($details as $detail) {
                    $employee = $detail->employee;
                    if (! $employee || ! $employee->company_id) {
                        continue;
                    }

                    $expiry = RenewalDocumentRegistry::extractExpiryDate($type, $detail);
                    if ($expiry === null) {
                        $summary['missing_or_invalid_date_count']++;
                        $summary['by_type'][$type]['invalid_date']++;

                        continue;
                    }

                    $candidates[] = [
                        'source' => $detail,
                        'companyId' => (int) $employee->company_id,
                        'branchId' => $employee->branch_id ? (int) $employee->branch_id : null,
                        'expiryDate' => $expiry,
                        'sourceLabel' => $employee->user?->name ?? ('#'.$employee->id),
                    ];
                }

                self::processChunkCandidates($type, $candidates, $effectiveSince, $dryRun, $summary, $eligibleResolver);
            });
    }

    private static function scanEmployeeType(
        string $type,
        string $cutoffDate,
        ?string $effectiveSince,
        bool $dryRun,
        array &$summary,
        \Closure $eligibleResolver,
    ): void {
        $def = RenewalDocumentRegistry::definition($type);
        $field = $def['expiry_field'];

        Employee::query()
            ->where('status', GeneralStatus::ACTIVE->value)
            ->whereNotNull('company_id')
            ->whereNotNull($field)
            ->where($field, '<=', $cutoffDate)
            ->with(['user', 'company'])
            ->chunkById(200, function ($employees) use ($type, $effectiveSince, $dryRun, &$summary, $eligibleResolver): void {
                $candidates = [];
                foreach ($employees as $employee) {
                    $expiry = RenewalDocumentRegistry::extractExpiryDate($type, $employee);
                    if ($expiry === null) {
                        $summary['missing_or_invalid_date_count']++;
                        $summary['by_type'][$type]['invalid_date']++;

                        continue;
                    }

                    $candidates[] = [
                        'source' => $employee,
                        'companyId' => (int) $employee->company_id,
                        'branchId' => $employee->branch_id ? (int) $employee->branch_id : null,
                        'expiryDate' => $expiry,
                        'sourceLabel' => $employee->user?->name ?? ('#'.$employee->id),
                    ];
                }

                self::processChunkCandidates($type, $candidates, $effectiveSince, $dryRun, $summary, $eligibleResolver);
            });
    }

    private static function scanCompanyType(
        string $type,
        string $cutoffDate,
        ?string $effectiveSince,
        bool $dryRun,
        array &$summary,
        \Closure $eligibleResolver,
    ): void {
        $def = RenewalDocumentRegistry::definition($type);

        $query = Company::query()->where('status', GeneralStatus::ACTIVE->value);

        if ($def['json_path'] === null) {
            $query->whereNotNull($def['expiry_field'])
                ->where($def['expiry_field'], '<=', $cutoffDate);
        } else {
            $query->whereNotNull('metadata');
        }

        $query->chunkById(200, function ($companies) use ($type, $def, $cutoffDate, $effectiveSince, $dryRun, &$summary, $eligibleResolver): void {
            $candidates = [];
            foreach ($companies as $company) {
                if ($def['json_path'] !== null) {
                    $raw = data_get($company->metadata, $def['json_path']);
                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    $expiry = RenewalDocumentRegistry::extractExpiryDate($type, $company);
                    if ($expiry === null) {
                        $summary['missing_or_invalid_date_count']++;
                        $summary['by_type'][$type]['invalid_date']++;

                        continue;
                    }

                    if ($expiry > $cutoffDate) {
                        continue;
                    }
                } else {
                    $expiry = RenewalDocumentRegistry::extractExpiryDate($type, $company);
                    if ($expiry === null) {
                        $summary['missing_or_invalid_date_count']++;
                        $summary['by_type'][$type]['invalid_date']++;

                        continue;
                    }
                }

                $candidates[] = [
                    'source' => $company,
                    'companyId' => (int) $company->id,
                    'branchId' => null,
                    'expiryDate' => $expiry,
                    'sourceLabel' => (string) ($company->trade_name ?: ('#'.$company->id)),
                ];
            }

            self::processChunkCandidates($type, $candidates, $effectiveSince, $dryRun, $summary, $eligibleResolver);
        });
    }

    /**
     * @param  array<int, array{source: Model, companyId: int, branchId: ?int, expiryDate: string, sourceLabel: string}>  $candidates
     */
    private static function processChunkCandidates(
        string $type,
        array $candidates,
        ?string $effectiveSince,
        bool $dryRun,
        array &$summary,
        \Closure $eligibleResolver,
    ): void {
        if ($candidates === []) {
            return;
        }

        $dueCandidates = [];
        $chunkKeys = [];

        foreach ($candidates as $candidate) {
            if ($effectiveSince !== null && $candidate['expiryDate'] < $effectiveSince) {
                $summary['excluded_historical_count']++;
                $summary['by_type'][$type]['excluded_historical']++;

                continue;
            }

            $summary['due_count']++;
            $summary['by_type'][$type]['due']++;

            $taskKey = RenewalDocumentRegistry::buildTaskKey(
                companyId: $candidate['companyId'],
                sourceClass: $candidate['source']::class,
                sourceId: (int) $candidate['source']->getKey(),
                documentType: $type,
                expiryDate: $candidate['expiryDate'],
            );

            $candidate['taskKey'] = $taskKey;
            $chunkKeys[] = $taskKey;
            $dueCandidates[] = $candidate;
        }

        if ($dueCandidates === []) {
            return;
        }

        $existingKeys = UnifiedDeficiencyTask::query()
            ->whereIn('task_key', $chunkKeys)
            ->pluck('task_key')
            ->flip()
            ->all();

        foreach ($dueCandidates as $candidate) {
            if (isset($existingKeys[$candidate['taskKey']])) {
                $summary['existing_count']++;
                $summary['by_type'][$type]['existing']++;

                continue;
            }

            /** @var Collection<int, Employee> $eligibleManagers */
            $eligibleManagers = $eligibleResolver($candidate['companyId'], $type);

            if ($dryRun) {
                $summary['created_count']++;
                $summary['by_type'][$type]['created']++;

                if ($eligibleManagers->isEmpty()) {
                    $summary['unassignable_count']++;
                    $summary['by_type'][$type]['unassignable']++;
                }

                continue;
            }

            $result = self::ensureRequest(
                documentType: $type,
                source: $candidate['source'],
                companyId: $candidate['companyId'],
                branchId: $candidate['branchId'],
                expiryDate: $candidate['expiryDate'],
                sourceLabel: $candidate['sourceLabel'],
                precomputedEligibleManagers: $eligibleManagers,
            );

            if ($result['created']) {
                $summary['created_count']++;
                $summary['by_type'][$type]['created']++;
            } else {
                $summary['existing_count']++;
                $summary['by_type'][$type]['existing']++;
            }

            if ($result['eligible_count'] === 0) {
                $summary['unassignable_count']++;
                $summary['by_type'][$type]['unassignable']++;
            }
        }
    }
}
