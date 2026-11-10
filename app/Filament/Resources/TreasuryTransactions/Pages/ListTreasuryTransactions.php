<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Company;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTreasuryTransactions extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = TreasuryTransactionResource::class;

    public mixed $transactionType = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('finance.treasury_transactions.summary.summary_view'))
                ->icon('heroicon-o-chart-pie')
                ->color('gray')
                ->url(fn (): string => TreasuryTransactionResource::getUrl('index')),
            CreateAction::make(),
            Action::make('export_statement')
                ->label(__('finance.treasury_transactions.export_statement_modal.btn_label'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->modalHeading(__('finance.treasury_transactions.export_statement_modal.heading'))
                ->modalDescription(__('finance.treasury_transactions.export_statement_modal.description'))
                ->modalSubmitActionLabel(__('finance.treasury_transactions.export_statement_modal.submit_label'))
                ->form([
                    Select::make('company_id')
                        ->label(__('finance.treasury_transactions.export_statement_modal.company_label'))
                        ->options(['all' => __('finance.treasury_transactions.export_statement_modal.all_companies')] + Company::orderBy('trade_name')->pluck('trade_name', 'id')->toArray())
                        ->default('all')
                        ->searchable()
                        ->required(),
                    Select::make('transaction_type')
                        ->label(__('finance.treasury_transactions.export_statement_modal.type_label'))
                        ->options([
                            'all' => __('finance.treasury_transactions.export_statement_modal.type_all'),
                            'incoming' => __('finance.treasury_transactions.export_statement_modal.type_incoming'),
                            'outgoing' => __('finance.treasury_transactions.export_statement_modal.type_outgoing'),
                        ])
                        ->default('all')
                        ->required(),
                    DatePicker::make('from_date')
                        ->label(__('finance.treasury_transactions.export_statement_modal.from_date')),
                    DatePicker::make('to_date')
                        ->label(__('finance.treasury_transactions.export_statement_modal.to_date')),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                    $url = route('exports.treasury-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),
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
                'transaction_number',
                'voucher_number',
                'recipient_or_payer',
                'company.trade_name',
            ],
            'where_in' => [
                'transaction_type' => $this->transactionType,
            ],
        ]);
    }
}
