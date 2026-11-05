<?php

namespace App\Filament\Resources\Debts\Pages;

use App\Filament\Resources\Debts\DebtResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDebts extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = DebtResource::class;

    public mixed $debtorType = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('print_all')
                ->label(__('debts.actions.print_statement'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.debts-statement'))
                ->openUrlInNewTab(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['debtorType'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'status' => true,
            'date_column' => 'debt_date',
            'search_columns' => [
                'debt_number',
                'external_name',
                'external_national_id',
                'external_phone',
                'employee.user.name',
                'company.trade_name',
                'debt_reason',
            ],
            'where_in' => [
                'debtor_type' => $this->debtorType,
            ],
        ]);
    }
}
