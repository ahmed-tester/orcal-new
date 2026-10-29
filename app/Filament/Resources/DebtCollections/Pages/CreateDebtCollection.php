<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateDebtCollection extends CreateRecord
{
    protected static string $resource = DebtCollectionResource::class;

    public bool $shouldPrintAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('debts.collections.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
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
        if ($this->shouldPrintAfterCreate && $this->record) {
            return route('system.print', ['type' => 'debt-collection', 'id' => $this->record->id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
