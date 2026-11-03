<?php

namespace App\Filament\Resources\AssetFunds;

use App\Filament\Resources\AssetFunds\Pages\CreateAssetFund;
use App\Filament\Resources\AssetFunds\Pages\EditAssetFund;
use App\Filament\Resources\AssetFunds\Pages\ListAssetFunds;
use App\Filament\Resources\AssetFunds\Schemas\AssetFundForm;
use App\Filament\Resources\AssetFunds\Tables\AssetFundsTable;
use App\Models\AssetFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AssetFundResource extends Resource
{
    protected static ?string $model = AssetFund::class;

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    public static function getModelLabel(): string
    {
        return __('asset_funds.asset_funds.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('asset_funds.asset_funds.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.asset_funds');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return AssetFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetFundsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAssetFunds::route('/'),
            'create' => CreateAssetFund::route('/create'),
            'edit'   => EditAssetFund::route('/{record}/edit'),
        ];
    }
}

