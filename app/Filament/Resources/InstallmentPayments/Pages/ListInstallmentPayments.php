<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInstallmentPayments extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = InstallmentPaymentResource::class;

    public mixed $paymentType = null;

    public mixed $paymentSource = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['paymentType', 'paymentSource'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'payment_date',
            'search_columns' => [
                'voucher_number',
                'company.trade_name',
                'installment.installment_number',
                'installment.external_name',
                'installment.employee.user.name',
            ],
            'where_in' => [
                'metadata->status' => $this->status,
                'payment_type' => $this->paymentType,
                'payment_source' => $this->paymentSource,
            ],
        ]);
    }
}
