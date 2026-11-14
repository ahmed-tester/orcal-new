<?php

namespace App\Filament\Resources\DailyJournals\Pages;

use App\Filament\Resources\DailyJournals\DailyJournalResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDailyJournals extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = DailyJournalResource::class;

    public mixed $isDetailed = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summary')
                ->label(__('finance.daily_journals.summary.title'))
                ->icon('heroicon-o-chart-bar')
                ->url(fn () => DailyJournalResource::getUrl('index')),
            CreateAction::make(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['isDetailed'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'branch' => true,
            'date_column' => 'entry_date',
            'search_columns' => [
                'entry_number',
                'company.trade_name',
                'branch.name',
            ],
            'where_in' => [
                'is_detailed' => $this->isDetailed,
            ],
        ]);
    }
}
