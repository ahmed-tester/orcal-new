<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\TaskAssignmentItem;
use Illuminate\Validation\ValidationException;

class TaskAssignmentItemObserver
{
    /**
     * Handle the TaskAssignmentItem "saving" event.
     */
    public function saving(TaskAssignmentItem $item): void
    {
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
        if (! $item->requires_review || TaskAssignmentItem::isReviewGuardBypassed()) {
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

        // An already completed item cannot have its status directly changed
        if ($item->getOriginal('status') === TaskStatus::COMPLETED->value && $item->isDirty('status')) {
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

        if ($item->isDirty('due_date') && $item->getOriginal('due_date') !== null) {
            throw ValidationException::withMessages([
                'due_date' => __('tasks.review.errors.due_date_requires_extension'),
            ]);
        }
    }
}
