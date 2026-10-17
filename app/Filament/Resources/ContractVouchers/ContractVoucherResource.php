<?php

namespace App\Filament\Resources\ContractVouchers;

use App\Filament\Resources\ContractVouchers\Pages\CreateContractVoucher;
use App\Filament\Resources\ContractVouchers\Pages\EditContractVoucher;
use App\Filament\Resources\ContractVouchers\Pages\ListContractVouchers;
use App\Filament\Resources\ContractVouchers\Schemas\ContractVoucherForm;
use App\Filament\Resources\ContractVouchers\Tables\ContractVouchersTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\ContractVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ContractVoucherResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = ContractVoucher::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    public static function getModelLabel(): string
    {
        return __('contract_vouchers.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contract_vouchers.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.contract_vouchers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.contracts_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ContractVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractVouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContractVouchers::route('/'),
            'create' => CreateContractVoucher::route('/create'),
            'edit'   => EditContractVoucher::route('/{record}/edit'),
        ];
    }
}
