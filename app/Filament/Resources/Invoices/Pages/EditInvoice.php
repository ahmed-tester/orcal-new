<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Components\InvoiceFinancialActions;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceFinancialService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditInvoice extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = InvoiceResource::class;

    protected function beforeSave(): void
    {
        InvoiceFinancialService::assertEditable($this->record, $this->data ?? []);
    }

    protected function afterSave(): void
    {
        InvoiceFinancialService::recalculateTotals($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            InvoiceFinancialActions::recordReceipt(),
            InvoiceFinancialActions::confirmReceipt(),
            InvoiceFinancialActions::requestHandover(),
            InvoiceFinancialActions::handoverQr(),
            InvoiceFinancialActions::reverseReceipt(),
            InvoiceFinancialActions::financialSummary(),
            Action::make('print')
                ->label(__('invoices.actions.print'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->before(fn () => InvoiceFinancialService::assertDeletable($this->record)),
        ];
    }
}
