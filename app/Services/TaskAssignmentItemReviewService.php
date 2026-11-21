<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Filament\Pages\MyTasks;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\TaskAssignmentItemReview;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class TaskAssignmentItemReviewService
{
    public static function isAssignedToUser(User $user, TaskAssignmentItem $item): bool
    {
        $item->loadMissing('taskAssignment.employee');

        return (int) ($item->taskAssignment?->employee?->user_id ?? 0) === (int) $user->id;
    }

    public static function isWithinUserScope(User $user, TaskAssignmentItem $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $item->loadMissing('taskAssignment.employee');
        $assignment = $item->taskAssignment;
        $employee = $assignment?->employee;

        if (! $assignment || ! $employee) {
            return false;
        }

        $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);

        if (in_array((int) $employee->company_id, $authorizedCompanyIds, true)) {
            $userBranchId = $user->employee?->branch_id;

            if ($userBranchId === null || (int) $employee->branch_id === (int) $userBranchId) {
                return true;
            }
        }

        if (EmployeePerformanceAccessService::isSupervisor($user)) {
            $date = $assignment->assignment_date ?? now();
            $windows = EmployeePerformanceAccessService::teamWindows($user, $date->copy()->startOfMonth(), $date->copy()->endOfMonth());

            foreach ($windows as $window) {
                if ((int) $employee->company_id === (int) $window['company']
                    && in_array((int) $assignment->employee_id, $window['employees'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function canSubmit(User $user, TaskAssignmentItem $item): bool
    {
        if (! $item->requires_review) {
            return false;
        }

        if (! $item->status?->isEmployeeOpen()) {
            return false;
        }

        return self::isAssignedToUser($user, $item);
    }

    public static function canEscalate(User $user, TaskAssignmentItem $item): bool
    {
        if (! $item->status?->isEmployeeOpen()) {
            return false;
        }

        return self::isAssignedToUser($user, $item);
    }

    public static function canReview(User $user, TaskAssignmentItem $item): bool
    {
        if (! $item->requires_review || $item->status !== TaskStatus::PENDING_REVIEW) {
            return false;
        }

        if (self::isAssignedToUser($user, $item)) {
            return false;
        }

        if (! ($user->hasRole('super_admin') || $user->can('review_task_assignment'))) {
            return false;
        }

        return self::isWithinUserScope($user, $item);
    }

    public static function canExtendDueDate(User $user, TaskAssignmentItem $item): bool
    {
        if ($item->status === TaskStatus::COMPLETED) {
            return false;
        }

        if (self::isAssignedToUser($user, $item)) {
            return false;
        }

        if (! ($user->hasRole('super_admin') || $user->can('extend_task_assignment_due_date'))) {
            return false;
        }

        return self::isWithinUserScope($user, $item);
    }

    public static function submitForReview(User $user, TaskAssignmentItem $item, ?string $note = null): TaskAssignmentItemReview
    {
        $cleanNote = filled($note) ? trim((string) $note) : null;

        return DB::transaction(function () use ($user, $item, $cleanNote): TaskAssignmentItemReview {
            $locked = TaskAssignmentItem::query()
                ->with(['task', 'taskAssignment.employee.user'])
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! self::canSubmit($user, $locked)) {
                throw new AuthorizationException(__('tasks.review.errors.cannot_submit'));
            }

            $hasPending = TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $locked->getKey())
                ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.already_pending_review'),
                ]);
            }

            $now = now();
            $attempt = ((int) TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $locked->getKey())
                ->max('attempt_number')) + 1;

            $review = TaskAssignmentItemReview::query()->create([
                'task_assignment_item_id' => $locked->getKey(),
                'attempt_number' => $attempt,
                'submitted_by' => $user->getKey(),
                'submitted_at' => $now,
                'submission_note' => $cleanNote,
                'decision' => TaskAssignmentItemReview::DECISION_PENDING,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $now, $cleanNote): void {
                $locked->update([
                    'status' => TaskStatus::PENDING_REVIEW,
                    'submitted_at' => $now,
                    'latest_submission_note' => $cleanNote,
                ]);
            });

            DB::afterCommit(function () use ($locked, $user): void {
                self::notifyReviewers($locked, $user);
            });

            return $review;
        });
    }

    public static function latestPendingReviewId(TaskAssignmentItem $item): ?int
    {
        $id = TaskAssignmentItemReview::query()
            ->where('task_assignment_item_id', $item->getKey())
            ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
            ->orderByDesc('attempt_number')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public static function approve(User $user, TaskAssignmentItem $item, float $score, ?string $note, int $expectedReviewId): TaskAssignmentItemReview
    {
        $cleanNote = filled($note) ? trim((string) $note) : null;

        if ($score < 0) {
            throw ValidationException::withMessages([
                'score' => __('tasks.review.errors.invalid_score'),
            ]);
        }

        return DB::transaction(function () use ($user, $item, $score, $cleanNote, $expectedReviewId): TaskAssignmentItemReview {
            $locked = TaskAssignmentItem::query()
                ->with(['task', 'taskAssignment.employee.user'])
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! self::canReview($user, $locked)) {
                throw new AuthorizationException(__('tasks.review.errors.cannot_review'));
            }

            if ((float) $locked->weight > 0 && $score > (float) $locked->weight) {
                throw ValidationException::withMessages([
                    'score' => __('tasks.review.errors.score_exceeds_weight', ['weight' => number_format((float) $locked->weight, 2)]),
                ]);
            }

            $review = TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $locked->getKey())
                ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.no_pending_submission'),
                ]);
            }

            if ((int) $review->getKey() !== $expectedReviewId) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.stale_review_attempt'),
                ]);
            }

            $now = now();
            $acceptedSubmittedAt = $review->submitted_at->copy();
            $completedDate = $acceptedSubmittedAt->copy()->timezone(config('app.timezone'))->toDateString();

            $review->update([
                'decision' => TaskAssignmentItemReview::DECISION_APPROVED,
                'decided_by' => $user->getKey(),
                'decided_at' => $now,
                'decision_reason' => $cleanNote,
                'awarded_score' => $score,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $score, $completedDate, $acceptedSubmittedAt, $now, $user): void {
                $locked->update([
                    'status' => TaskStatus::COMPLETED,
                    'score' => $score,
                    'completed_at' => $completedDate,
                    'accepted_submission_at' => $acceptedSubmittedAt,
                    'approved_at' => $now,
                    'approved_by' => $user->getKey(),
                ]);
            });

            DB::afterCommit(function () use ($locked, $score): void {
                self::notifyAssignee(
                    $locked,
                    'task_item_approved',
                    'tasks.review.notifications.approved_title',
                    'tasks.review.notifications.approved_body',
                    [
                        'task' => $locked->task?->name ?? '',
                        'score' => number_format($score, 2),
                    ],
                    'heroicon-o-check-circle',
                    'success',
                );
            });

            return $review;
        });
    }

    public static function returnForRevision(
        User $user,
        TaskAssignmentItem $item,
        string $reason,
        string $category,
        int $expectedReviewId,
    ): TaskAssignmentItemReview {
        $cleanReason = trim($reason);

        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('tasks.review.errors.reason_required'),
            ]);
        }

        if (! in_array($category, [
            TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT,
            TaskAssignmentItemReview::RETURN_SCOPE_CHANGE,
        ], true)) {
            throw ValidationException::withMessages([
                'return_category' => __('tasks.review.errors.invalid_return_category'),
            ]);
        }

        return DB::transaction(function () use ($user, $item, $cleanReason, $category, $expectedReviewId): TaskAssignmentItemReview {
            $locked = TaskAssignmentItem::query()
                ->with(['task', 'taskAssignment.employee.user'])
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! self::canReview($user, $locked)) {
                throw new AuthorizationException(__('tasks.review.errors.cannot_review'));
            }

            $review = TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $locked->getKey())
                ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.no_pending_submission'),
                ]);
            }

            if ((int) $review->getKey() !== $expectedReviewId) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.stale_review_attempt'),
                ]);
            }

            $now = now();

            $review->update([
                'decision' => TaskAssignmentItemReview::DECISION_RETURNED,
                'decided_by' => $user->getKey(),
                'decided_at' => $now,
                'return_category' => $category,
                'decision_reason' => $cleanReason,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $cleanReason, $category): void {
                $locked->update([
                    'status' => TaskStatus::RETURNED_FOR_REVISION,
                    'latest_return_reason' => $cleanReason,
                    'returned_count' => (int) $locked->returned_count + 1,
                    'defect_return_count' => (int) $locked->defect_return_count
                        + ($category === TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT ? 1 : 0),
                ]);
            });

            DB::afterCommit(function () use ($locked, $cleanReason): void {
                self::notifyAssignee(
                    $locked,
                    'task_item_returned',
                    'tasks.review.notifications.returned_title',
                    'tasks.review.notifications.returned_body',
                    [
                        'task' => $locked->task?->name ?? '',
                        'reason' => $cleanReason,
                    ],
                    'heroicon-o-arrow-uturn-left',
                    'warning',
                );
            });

            return $review;
        });
    }

    public static function extendDueDate(User $user, TaskAssignmentItem $item, string $newDueDate, string $reason): TaskAssignmentItem
    {
        $cleanReason = trim($reason);

        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('tasks.review.errors.extension_reason_required'),
            ]);
        }

        $parsedDate = Carbon::parse($newDueDate)->toDateString();

        return DB::transaction(function () use ($user, $item, $parsedDate, $cleanReason): TaskAssignmentItem {
            $locked = TaskAssignmentItem::query()
                ->with(['task', 'taskAssignment.employee.user'])
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! self::canExtendDueDate($user, $locked)) {
                throw new AuthorizationException(__('tasks.review.errors.cannot_extend_due_date'));
            }

            $now = now();
            $originalDueDate = $locked->original_due_date ?? $locked->due_date;
            $metadata = is_array($locked->metadata) ? $locked->metadata : [];
            $extensions = is_array($metadata['due_date_extensions'] ?? null) ? $metadata['due_date_extensions'] : [];
            $extensions[] = [
                'from' => $locked->due_date?->toDateString(),
                'to' => $parsedDate,
                'reason' => $cleanReason,
                'by' => $user->getKey(),
                'at' => $now->toIso8601String(),
            ];
            $metadata['due_date_extensions'] = $extensions;

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $parsedDate, $originalDueDate, $now, $user, $cleanReason, $metadata): void {
                $locked->update([
                    'due_date' => $parsedDate,
                    'original_due_date' => $originalDueDate,
                    'due_date_extended_at' => $now,
                    'due_date_extended_by' => $user->getKey(),
                    'due_date_extension_reason' => $cleanReason,
                    'metadata' => $metadata,
                ]);
            });

            DB::afterCommit(function () use ($locked, $parsedDate, $cleanReason): void {
                self::notifyAssignee(
                    $locked,
                    'task_item_due_date_extended',
                    'tasks.review.notifications.extended_title',
                    'tasks.review.notifications.extended_body',
                    [
                        'task' => $locked->task?->name ?? '',
                        'due_date' => $parsedDate,
                        'reason' => $cleanReason,
                    ],
                    'heroicon-o-calendar-days',
                    'info',
                );
            });

            return $locked->fresh();
        });
    }

    public static function escalate(User $user, TaskAssignmentItem $item, string $reason): void
    {
        $cleanReason = trim($reason);

        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'escalationReason' => __('tasks.review.errors.escalation_reason_required'),
            ]);
        }

        DB::transaction(function () use ($user, $item, $cleanReason): void {
            $locked = TaskAssignmentItem::query()
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! self::canEscalate($user, $locked)) {
                throw new AuthorizationException(__('tasks.review.errors.cannot_escalate'));
            }

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $meta['escalation_reason'] = $cleanReason;
            $meta['escalated_at'] = now()->toDateTimeString();
            $meta['escalated_by'] = $user->id;

            $escalatedNote = __('tasks.review.labels.escalated_note', ['reason' => $cleanReason]);
            $noteText = ' ['.$escalatedNote.']';
            $newNotes = filled($locked->notes) ? $locked->notes.$noteText : $escalatedNote;

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $newNotes, $meta): void {
                $locked->update([
                    'status' => TaskStatus::ESCALATED,
                    'notes' => $newNotes,
                    'metadata' => $meta,
                ]);
            });
        });
    }

    private static function notifyReviewers(TaskAssignmentItem $item, User $submitter): void
    {
        $assignment = $item->taskAssignment;
        if (! $assignment) {
            return;
        }

        $recipients = User::query()
            ->where('id', '!=', $submitter->getKey())
            ->where(function ($query): void {
                $query->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'review_task_assignment'))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', 'review_task_assignment'));
            })
            ->get()
            ->filter(fn (User $candidate) => self::isWithinUserScope($candidate, $item))
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new BaseSystemNotification(
            eventKey: 'task_item_submitted_for_review',
            customTitle: __('tasks.review.notifications.submitted_title'),
            customMessage: __('tasks.review.notifications.submitted_body', [
                'employee' => $assignment->employee?->user?->name ?? '',
                'task' => $item->task?->name ?? '',
            ]),
            icon: 'heroicon-o-eye',
            color: 'info',
            actionUrl: NotificationDispatcher::actionUrl($assignment),
            recordType: TaskAssignment::class,
            recordId: $assignment->getKey(),
        );

        Notification::send($recipients, $notification);
    }

    private static function notifyAssignee(
        TaskAssignmentItem $item,
        string $eventKey,
        string $titleKey,
        string $bodyKey,
        array $parameters,
        string $icon,
        string $color,
    ): void {
        $recipient = $item->taskAssignment?->employee?->user;

        if (! $recipient instanceof User) {
            return;
        }

        $actionUrl = null;
        try {
            $actionUrl = MyTasks::getUrl();
        } catch (\Throwable) {
            $actionUrl = null;
        }

        $notification = new BaseSystemNotification(
            eventKey: $eventKey,
            customTitle: __($titleKey, $parameters),
            customMessage: __($bodyKey, $parameters),
            icon: $icon,
            color: $color,
            actionUrl: $actionUrl,
            recordType: TaskAssignment::class,
            recordId: $item->task_assignment_id,
        );

        Notification::send(Collection::make([$recipient]), $notification);
    }
}
