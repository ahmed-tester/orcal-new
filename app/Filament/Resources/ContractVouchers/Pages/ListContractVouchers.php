<?php

namespace App\Filament\Resources\ContractVouchers\Pages;

use App\Filament\Resources\ContractVouchers\ContractVoucherResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContractVouchers extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = ContractVoucherResource::class;

    public mixed $voucherType = null;

    public mixed $paymentMethod = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('contract_vouchers.actions.create')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['voucherType', 'paymentMethod'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'status' => true,
            'date_column' => 'voucher_date',
            'search_columns' => [
                'voucher_number',
                'second_party_name',
                'first_party_name',
                'contract_number',
                'company.trade_name',
            ],
            'where_in' => [
                'voucher_type' => $this->voucherType,
                'payment_method' => $this->paymentMethod,
            ],
        ]);
    }
}
