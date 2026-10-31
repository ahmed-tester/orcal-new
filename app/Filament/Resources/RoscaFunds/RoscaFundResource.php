<?php

namespace App\Filament\Resources\RoscaFunds;

use App\Filament\Resources\RoscaFunds\Pages\CreateRoscaFund;
use App\Filament\Resources\RoscaFunds\Pages\EditRoscaFund;
use App\Filament\Resources\RoscaFunds\Pages\ListRoscaFunds;
use App\Filament\Resources\RoscaFunds\Schemas\RoscaFundForm;
use App\Filament\Resources\RoscaFunds\Tables\RoscaFundsTable;
// use App\Filament\Traits\HasCompanyScope;
use App\Models\RoscaFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RoscaFundResource extends Resource
{
    // use HasCompanyScope;

    protected static ?string $model = RoscaFund::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return __('funds.rosca_funds.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.rosca_funds.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.rosca_funds');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return RoscaFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoscaFundsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoscaFunds::route('/'),
            'create' => CreateRoscaFund::route('/create'),
            'edit'   => EditRoscaFund::route('/{record}/edit'),
        ];
    }
}
