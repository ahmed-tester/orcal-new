<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInstallments extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = InstallmentResource::class;

    public mixed $transactionType = null;

    public mixed $beneficiaryType = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('finance.installments.summary.summary_view'))
                ->icon('heroicon-o-chart-pie')
                ->color('gray')
                ->url(fn (): string => InstallmentResource::getUrl('index')),
            CreateAction::make(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['transactionType', 'beneficiaryType'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'status' => true,
            'date_column' => 'installment_date',
            'search_columns' => [
                'installment_number',
                'external_name',
                'external_national_id',
                'external_phone',
                'employee.user.name',
                'company.trade_name',
            ],
            'where_in' => [
                'transaction_type' => $this->transactionType,
                'beneficiary_type' => $this->beneficiaryType,
            ],
        ]);
    }
}
