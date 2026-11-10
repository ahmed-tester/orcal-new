<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use App\Filament\Resources\ViolationTransactions\Widgets\ViolationTreasuryStatsWidget;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Company;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListViolationTransactions extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = ViolationTransactionResource::class;

    public mixed $transactionType = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_statement')
                ->label(__('violations.transactions.export_statement_modal.btn_label'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->modalHeading(__('violations.transactions.export_statement_modal.heading'))
                ->modalDescription(__('violations.transactions.export_statement_modal.description'))
                ->modalSubmitActionLabel(__('violations.transactions.export_statement_modal.submit_label'))
                ->form([
                    Select::make('company_id')
                        ->label(__('violations.transactions.export_statement_modal.company_label'))
                        ->options(['all' => __('violations.transactions.export_statement_modal.all_companies')] + Company::orderBy('trade_name')->pluck('trade_name', 'id')->toArray())
                        ->default('all')
                        ->searchable()
                        ->required(),
                    Select::make('transaction_type')
                        ->label(__('violations.transactions.export_statement_modal.type_label'))
                        ->options([
                            'all' => __('violations.transactions.export_statement_modal.type_all'),
                            'incoming' => __('violations.transactions.export_statement_modal.type_incoming'),
                            'outgoing' => __('violations.transactions.export_statement_modal.type_outgoing'),
                        ])
                        ->default('all')
                        ->required(),
                    DatePicker::make('from_date')
                        ->label(__('violations.transactions.export_statement_modal.from_date')),
                    DatePicker::make('to_date')
                        ->label(__('violations.transactions.export_statement_modal.to_date')),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                    $url = route('exports.violation-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ViolationTreasuryStatsWidget::class,
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['transactionType'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'transaction_date',
            'search_columns' => [
                'voucher_number',
                'violation.violation_number',
                'company.trade_name',
            ],
            'where_in' => [
                'transaction_type' => $this->transactionType,
            ],
        ]);
    }
}
