<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDebtCollections extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = DebtCollectionResource::class;

    public mixed $paymentMethod = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['paymentMethod'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'collection_date',
            'search_columns' => [
                'voucher_number',
                'debt.debt_number',
                'debt.external_name',
                'debt.employee.user.name',
            ],
            'where_in' => [
                'metadata->status' => $this->status,
                'payment_method' => $this->paymentMethod,
            ],
        ]);
    }
}
