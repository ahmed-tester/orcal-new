<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('print_all')
                ->label('طباعة وحفظ الكشف 🖨️')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.invoices-statement'))
                ->openUrlInNewTab(),
        ];
    }
}
