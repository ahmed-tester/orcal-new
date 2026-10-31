<?php

namespace App\Filament\Resources\Debts\Pages;

use App\Filament\Resources\Debts\DebtResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_voucher')
                ->label(__('debts.actions.print_voucher'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('exports.debt-voucher', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndPrint')
                ->label(__('debts.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $url = route('exports.debt-voucher', $this->record);
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
