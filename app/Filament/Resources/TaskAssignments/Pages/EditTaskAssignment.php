<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\Locked;
use App\Filament\Concerns\RedirectsToIndex;

class EditTaskAssignment extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = TaskAssignmentResource::class;

    #[Locked]
    public ?int $lockedReviewId = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    public function refreshAssignmentItem(string $itemKey, int $itemId): void
    {
        if ($itemKey !== "record-{$itemId}" || ! is_array($this->data['items'][$itemKey] ?? null)) {
            return;
        }

        $item = $this->getRecord()->items()->find($itemId);

        if (! $item) {
            return;
        }

        $this->data['items'][$itemKey] = array_merge($this->data['items'][$itemKey], [
            'status' => $item->status?->value,
            'score' => (float) $item->score,
            'score_percentage' => (float) $item->score_percentage,
            'due_date' => $item->due_date?->toDateString(),
            'completed_at' => $item->completed_at?->toDateString(),
            'returned_count' => (int) $item->returned_count,
            'latest_return_reason' => $item->latest_return_reason,
            'latest_submission_note' => $item->latest_submission_note,
        ]);

        $this->record->refresh();
        $this->data['is_completed'] = (bool) $this->record->is_completed;

        $repeater = $this->form->getComponent('items');

        if ($repeater instanceof Repeater) {
            $repeater->clearCachedExistingRecords();
        }
    }
}
