<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use Illuminate\Support\Facades\DB;

class TaskAssignmentTransferService
{
    /**
     * Transfer a task assignment item to the target employee stored in metadata.
     */
    public static function transferItem(TaskAssignmentItem $item): ?TaskAssignmentItem
    {
        $targetEmployeeId = (int) ($item->metadata['transferred_to_employee_id'] ?? 0);

        if ($targetEmployeeId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($item, $targetEmployeeId): ?TaskAssignmentItem {
            $locked = TaskAssignmentItem::query()
                ->lockForUpdate()
                ->with('taskAssignment')
                ->find($item->getKey());

            if (! $locked || $locked->status !== TaskStatus::TRANSFERRED || $locked->renewal_request_id !== null) {
                return null;
            }

            $meta = is_array($locked->metadata) ? $locked->metadata : [];

            if (! empty($meta['transferred_item_id'])) {
                return null;
            }

            $sourceAssignment = $locked->taskAssignment;

            if ($sourceAssignment && (int) $sourceAssignment->employee_id === $targetEmployeeId) {
                return null;
            }

            $newAssignment = TaskAssignment::create([
                'assignment_date' => now()->toDateString(),
                'employee_id' => $targetEmployeeId,
                'from_date' => $sourceAssignment?->from_date,
                'to_date' => $sourceAssignment?->to_date,
                'created_by' => auth()->id() ?? $sourceAssignment?->created_by,
                'notes' => $sourceAssignment?->notes,
            ]);

            $newItem = TaskAssignmentItem::withoutReviewGuard(fn (): TaskAssignmentItem => TaskAssignmentItem::create([
                'task_assignment_id' => $newAssignment->id,
                'task_id' => $locked->task_id,
                'status' => TaskStatus::PENDING,
                'requires_review' => $locked->requires_review,
                'weight' => $locked->weight,
                'due_date' => $locked->due_date,
                'original_due_date' => $locked->original_due_date,
                'notes' => $locked->notes,
                'metadata' => [
                    'transferred_from_item_id' => $locked->id,
                    'transferred_from_employee_id' => $sourceAssignment?->employee_id,
                    'transferred_at' => now()->toDateTimeString(),
                    'transferred_by' => auth()->id(),
                ],
            ]));

            $meta['transferred_item_id'] = $newItem->id;
            $meta['transferred_at'] = now()->toDateTimeString();
            $meta['transferred_by'] = auth()->id();

            TaskAssignmentItem::withoutReviewGuard(function () use ($locked, $meta): void {
                $locked->updateQuietly([
                    'metadata' => $meta,
                ]);
            });

            return $newItem;
        });
    }
}
