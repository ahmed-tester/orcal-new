<?php

namespace App\Filament\Resources\EmployeeRewards;

use App\Filament\Resources\EmployeeRewards\Pages\CreateEmployeeReward;
use App\Filament\Resources\EmployeeRewards\Pages\EditEmployeeReward;
use App\Filament\Resources\EmployeeRewards\Pages\ListEmployeeRewards;
use App\Filament\Resources\EmployeeRewards\Schemas\EmployeeRewardForm;
use App\Filament\Resources\EmployeeRewards\Tables\EmployeeRewardsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\EmployeeReward;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EmployeeRewardResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = EmployeeReward::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    public static function getModelLabel(): string
    {
        return __('finance.employee_rewards.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.employee_rewards.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.employee_rewards');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeRewardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeRewardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployeeRewards::route('/'),
            'create' => CreateEmployeeReward::route('/create'),
            'edit'   => EditEmployeeReward::route('/{record}/edit'),
        ];
    }
}
