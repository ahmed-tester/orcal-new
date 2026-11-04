<?php

namespace App\Filament\Resources\AssetFundVouchers;

use App\Filament\Resources\AssetFundVouchers\Pages\CreateAssetFundVoucher;
use App\Filament\Resources\AssetFundVouchers\Pages\EditAssetFundVoucher;
use App\Filament\Resources\AssetFundVouchers\Pages\ListAssetFundVouchers;
use App\Filament\Resources\AssetFundVouchers\Schemas\AssetFundVoucherForm;
use App\Filament\Resources\AssetFundVouchers\Tables\AssetFundVouchersTable;
use App\Models\AssetFundVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AssetFundVoucherResource extends Resource
{
    protected static ?string $model = AssetFundVoucher::class;

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return __('asset_funds.asset_fund_vouchers.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('asset_funds.asset_fund_vouchers.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.asset_fund_vouchers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return AssetFundVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetFundVouchersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAssetFundVouchers::route('/'),
            'create' => CreateAssetFundVoucher::route('/create'),
            'edit'   => EditAssetFundVoucher::route('/{record}/edit'),
        ];
    }
}

