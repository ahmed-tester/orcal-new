<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = InvoiceResource::class;

    public mixed $invoiceTypeId = null;

    public mixed $paymentStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('print_all')
                ->label(__('invoices.actions.print_all'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.invoices-statement'))
                ->openUrlInNewTab(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['invoiceTypeId', 'paymentStatus'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'issue_date',
            'search_columns' => [
                'invoice_number',
                'customer.name',
                'company.trade_name',
                'notes',
            ],
            'where_in' => [
                'invoice_type_id' => $this->invoiceTypeId,
                'payment_status' => $this->paymentStatus,
            ],
        ]);
    }
}
