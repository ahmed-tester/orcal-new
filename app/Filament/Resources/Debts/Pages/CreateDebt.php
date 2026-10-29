<?php

namespace App\Filament\Resources\Debts\Pages;

use App\Filament\Resources\Debts\DebtResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    public bool $shouldPrintAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('debts.actions.save_and_print'))
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
            return route('exports.debt-voucher', $this->record);
        }

        return $this->getResource()::getUrl('index');
    }
}
