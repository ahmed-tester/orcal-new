<?php

namespace App\Filament\Resources\TreasuryTransactions;

use App\Filament\Resources\TreasuryTransactions\Pages\CreateTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\EditTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\ListTreasuryTransactions;
use App\Filament\Resources\TreasuryTransactions\Schemas\TreasuryTransactionForm;
use App\Filament\Resources\TreasuryTransactions\Tables\TreasuryTransactionsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\TreasuryTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TreasuryTransactionResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = TreasuryTransaction::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getModelLabel(): string
    {
        return __('finance.treasury_transactions.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.treasury_transactions.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.treasury_transactions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return TreasuryTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreasuryTransactionsTable::configure($table);
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
            'index'  => ListTreasuryTransactions::route('/'),
            'create' => CreateTreasuryTransaction::route('/create'),
            'edit'   => EditTreasuryTransaction::route('/{record}/edit'),
        ];
    }
}
