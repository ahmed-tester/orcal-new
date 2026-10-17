<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('طباعة وحفظ الفاتورة 🖨️')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
