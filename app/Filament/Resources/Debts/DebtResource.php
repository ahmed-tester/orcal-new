<?php

namespace App\Filament\Resources\Debts;

use App\Filament\Resources\Debts\Pages\CreateDebt;
use App\Filament\Resources\Debts\Pages\EditDebt;
use App\Filament\Resources\Debts\Pages\ListDebts;
use App\Filament\Resources\Debts\Schemas\DebtForm;
use App\Filament\Resources\Debts\Tables\DebtsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Debt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Debt::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    public static function getModelLabel(): string
    {
        return __('debts.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('debts.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.debts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return DebtForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDebts::route('/'),
            'create' => CreateDebt::route('/create'),
            'edit'   => EditDebt::route('/{record}/edit'),
        ];
    }
}
