<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListServiceTransactions extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = ServiceTransactionResource::class;

    public mixed $scopeType = null;

    public mixed $serviceStatus = null;

    public mixed $paymentStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['scopeType', 'serviceStatus', 'paymentStatus'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'transaction_date',
            'search_columns' => [
                'transaction_number',
                'service_name',
                'beneficiary_name',
                'beneficiary_phone',
                'company.trade_name',
                'employee.user.name',
            ],
            'where_in' => [
                'metadata->status' => $this->status,
                'scope_type' => $this->scopeType,
                'service_status' => $this->serviceStatus,
                'payment_status' => $this->paymentStatus,
            ],
        ]);
    }
}
