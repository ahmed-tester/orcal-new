<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\TaskAssignmentItem;
use App\Services\EmployeePerformanceAccessService;
use App\Services\TaskAssignmentTransferService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TaskAssignmentItemObserver
{
    /**
     * Handle the TaskAssignmentItem "saving" event.
     */
    public function saving(TaskAssignmentItem $item): void
    {
        $this->applyPostponement($item);
        $this->validateTransfer($item);
        $this->enforceReviewWorkflowGuard($item);

        if ($item->weight > 0) {
            $item->score_percentage = round(($item->score / $item->weight) * 100, 2);
        } else {
            $item->score_percentage = 0.00;
        }

        if (! $item->requires_review && $item->status === TaskStatus::COMPLETED && blank($item->completed_at)) {
            $item->completed_at = now()->toDateString();
        }
    }

    /**
     * Handle the TaskAssignmentItem "saved" event.
     */
    public function saved(TaskAssignmentItem $item): void
    {
        $item->taskAssignment?->recalculateTotals();

        if (
            $item->status === TaskStatus::TRANSFERRED
            && filled($item->metadata['transferred_to_employee_id'] ?? null)
            && empty($item->metadata['transferred_item_id'] ?? null)
        ) {
            TaskAssignmentTransferService::transferItem($item);
        }
    }

    /**
     * Handle the TaskAssignmentItem "deleted" event.
     */
    public function deleted(TaskAssignmentItem $item): void
    {
        $item->taskAssignment?->recalculateTotals();
    }

    private function enforceReviewWorkflowGuard(TaskAssignmentItem $item): void
    {
        if (TaskAssignmentItem::isReviewGuardBypassed()) {
            return;
        }

        $originalStatus = $item->getOriginal('status');
        $wasTransferred = $originalStatus === TaskStatus::TRANSFERRED
            || $originalStatus === TaskStatus::TRANSFERRED->value;

        if ($item->exists && $wasTransferred && $item->isDirty('status')) {
            throw ValidationException::withMessages([
                'status' => __('tasks.review.errors.direct_mutation_blocked'),
            ]);
        }

        if (! $item->requires_review) {
            return;
        }

        if (! $item->exists) {
            $disallowedInitial = [
                TaskStatus::PENDING_REVIEW,
                TaskStatus::RETURNED_FOR_REVISION,
                TaskStatus::COMPLETED,
            ];

            if (in_array($item->status, $disallowedInitial, true) || (float) $item->score > 0 || filled($item->completed_at)) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.direct_mutation_blocked'),
                ]);
            }

            return;
        }

        $originalStatus = $item->getOriginal('status');
        $wasCompleted = $originalStatus === TaskStatus::COMPLETED
            || $originalStatus === TaskStatus::COMPLETED->value;
        $wasPendingReview = $originalStatus === TaskStatus::PENDING_REVIEW
            || $originalStatus === TaskStatus::PENDING_REVIEW->value;

        // Completed or pending-review items cannot have their status directly mutated
        if (($wasCompleted || $wasPendingReview) && $item->isDirty('status')) {
            throw ValidationException::withMessages([
                'status' => __('tasks.review.errors.direct_mutation_blocked'),
            ]);
        }

        // Items cannot be directly transitioned into review-controlled states
        if ($item->isDirty('status')) {
            $disallowedTransitions = [
                TaskStatus::PENDING_REVIEW,
                TaskStatus::RETURNED_FOR_REVISION,
                TaskStatus::COMPLETED,
            ];

            if (in_array($item->status, $disallowedTransitions, true)) {
                throw ValidationException::withMessages([
                    'status' => __('tasks.review.errors.direct_mutation_blocked'),
                ]);
            }
        }

        $guardedAttributes = ['score', 'completed_at'];

        foreach ($guardedAttributes as $attribute) {
            if ($item->isDirty($attribute)) {
                throw ValidationException::withMessages([
                    $attribute => __('tasks.review.errors.direct_mutation_blocked'),
                ]);
            }
        }

        $isPostponeWithDate = $item->status === TaskStatus::POSTPONED
            && filled($item->metadata['postponed_to_date'] ?? null);

        if ($item->isDirty('due_date') && $item->getOriginal('due_date') !== null && ! $isPostponeWithDate) {
            throw ValidationException::withMessages([
                'due_date' => __('tasks.review.errors.due_date_requires_extension'),
            ]);
        }
    }

    private function applyPostponement(TaskAssignmentItem $item): void
    {
        if ($item->status !== TaskStatus::POSTPONED) {
            return;
        }

        $postponedToDate = $item->metadata['postponed_to_date'] ?? null;

        if (! filled($postponedToDate)) {
            return;
        }

        $originalStatus = $item->getOriginal('status');
        $wasPostponed = $originalStatus === TaskStatus::POSTPONED
            || $originalStatus === TaskStatus::POSTPONED->value;
        $originalMeta = $item->getOriginal('metadata');
        $originalPostponedDate = is_array($originalMeta) ? ($originalMeta['postponed_to_date'] ?? null) : null;
        $targetDateString = Carbon::parse((string) $postponedToDate)->toDateString();
        $currentDueDateString = $item->due_date?->toDateString();

        if ($wasPostponed && (string) $originalPostponedDate === (string) $postponedToDate && $currentDueDateString === $targetDateString) {
            return;
        }

        if ($item->original_due_date === null && $item->getOriginal('due_date') !== null) {
            $item->original_due_date = $item->getOriginal('due_date');
        }

        $item->due_date = $targetDateString;
        $item->due_date_extended_at = now();
        $item->due_date_extended_by = auth()->id();
    }

    private function validateTransfer(TaskAssignmentItem $item): void
    {
        if ($item->status !== TaskStatus::TRANSFERRED || ! $item->isDirty('status')) {
            return;
        }

        $targetEmployeeId = (int) ($item->metadata['transferred_to_employee_id'] ?? 0);

        if ($targetEmployeeId <= 0) {
            throw ValidationException::withMessages([
                'metadata.transferred_to_employee_id' => __('tasks.review.errors.transfer_target_required'),
            ]);
        }

        $sourceEmployeeId = (int) ($item->taskAssignment?->employee_id ?? 0);

        if ($sourceEmployeeId > 0 && $targetEmployeeId === $sourceEmployeeId) {
            throw ValidationException::withMessages([
                'metadata.transferred_to_employee_id' => __('tasks.review.errors.transfer_same_employee'),
            ]);
        }

        $targetEmployee = Employee::with('user')->find($targetEmployeeId);
        if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($targetEmployee)) {
            throw ValidationException::withMessages([
                'metadata.transferred_to_employee_id' => __('tasks.validation.saudi_only'),
            ]);
        }
    }
}
