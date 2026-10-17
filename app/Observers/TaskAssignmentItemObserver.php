<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\TaskAssignmentItem;

class TaskAssignmentItemObserver
{
    /**
     * Handle the TaskAssignmentItem "saving" event.
     */
    public function saving(TaskAssignmentItem $item): void
    {
        if ($item->weight > 0) {
            $item->score_percentage = round(($item->score / $item->weight) * 100, 2);
        } else {
            $item->score_percentage = 0.00;
        }

        if ($item->status === TaskStatus::COMPLETED && blank($item->completed_at)) {
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
}
