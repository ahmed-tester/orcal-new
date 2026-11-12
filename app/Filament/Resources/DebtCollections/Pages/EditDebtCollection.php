<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use App\Services\DebtSummaryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDebtCollection extends EditRecord
{
    protected static string $resource = DebtCollectionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // A collection already attached to a settled debt can still be edited.
        $debt = DebtSummaryService::debts()
            ->when((int) $data['debt_id'] !== (int) $this->record->debt_id, fn ($query) => $query->where('remaining_amount', '>', 0))
            ->findOrFail($data['debt_id']);
        $data['company_id'] = $debt->company_id;
        $data['employee_id'] = $debt->employee_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_voucher')
                ->label(__('debts.collections.actions.print_voucher'))
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->url(fn () => route('system.print', ['type' => 'debt-collection', 'id' => $this->record->id]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndPrint')
                ->label(__('debts.collections.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $url = route('system.print', ['type' => 'debt-collection', 'id' => $this->record->id]);
                    $this->js("window.open('{$url}', '_blank');");
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
