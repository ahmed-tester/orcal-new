<?php

namespace App\Filament\Resources\ViolationTransactions;

use App\Filament\Resources\ViolationTransactions\Pages\CreateViolationTransaction;
use App\Filament\Resources\ViolationTransactions\Pages\EditViolationTransaction;
use App\Filament\Resources\ViolationTransactions\Pages\ListViolationTransactions;
use App\Filament\Resources\ViolationTransactions\Schemas\ViolationTransactionForm;
use App\Filament\Resources\ViolationTransactions\Tables\ViolationTransactionsTable;
use App\Filament\Resources\ViolationTransactions\Widgets\ViolationTreasuryStatsWidget;
use App\Filament\Traits\HasCompanyScope;
use App\Models\ViolationTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ViolationTransactionResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = ViolationTransaction::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getModelLabel(): string
    {
        return __('violations.transactions.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('violations.transactions.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.violation_transactions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.violations_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ViolationTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationTransactionsTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            ViolationTreasuryStatsWidget::class,
        ];
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
            'index' => ListViolationTransactions::route('/'),
            'create' => CreateViolationTransaction::route('/create'),
            'edit' => EditViolationTransaction::route('/{record}/edit'),
        ];
    }
}
