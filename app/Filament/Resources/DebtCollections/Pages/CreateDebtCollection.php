<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use App\Models\DebtCollection;
use App\Services\DebtSummaryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreateDebtCollection extends CreateRecord
{
    protected static string $resource = DebtCollectionResource::class;

    public bool $shouldPrintAfterCreate = false;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('debt_id')) {
            $id = filter_var(request()->query('debt_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            abort_unless($id !== false, 404);

            $debt = DebtSummaryService::debts()->where('remaining_amount', '>', 0)->find($id);
            abort_if($debt === null, 404);
            $this->form->fill(array_merge($this->data ?? [], ['debt_id' => $debt->id, 'amount' => $debt->remaining_amount]));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $debt = DebtSummaryService::debts()->where('remaining_amount', '>', 0)->find($data['debt_id']);
        abort_if($debt === null, 404);
        $data['company_id'] = $debt->company_id;
        $data['employee_id'] = $debt->employee_id;

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('debts.collections.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->visible(fn () => Gate::allows('viewAny', DebtCollection::class))
                ->action(function () {
                    $this->shouldPrintAfterCreate = true;
                    $this->create();
                }),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->shouldPrintAfterCreate && $this->record && Gate::allows('view', $this->record)) {
            return route('system.print', ['type' => 'debt-collection', 'id' => $this->record->id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
