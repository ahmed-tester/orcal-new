<?php

namespace App\Filament\Resources\EmployeeRewards\Pages;

use App\Filament\Resources\EmployeeRewards\EmployeeRewardResource;
use App\Filament\Traits\HasFilterBarState;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeeRewards extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = EmployeeRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('finance.employee_rewards.summary.title'))
                ->icon('heroicon-o-users')
                ->color('gray')
                ->url(fn (): string => EmployeeRewardResource::getUrl('index')),

            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'status' => true,
            'date_column' => 'reward_date',
            'search_columns' => [
                'reward_number',
                'employee.user.name',
                'company.trade_name',
                'reason',
            ],
        ]);
    }
}
