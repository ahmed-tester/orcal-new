<?php

namespace App\Filament\Resources\DebtCollections;

use App\Filament\Resources\DebtCollections\Pages\CreateDebtCollection;
use App\Filament\Resources\DebtCollections\Pages\EditDebtCollection;
use App\Filament\Resources\DebtCollections\Pages\ListDebtCollections;
use App\Filament\Resources\DebtCollections\Schemas\DebtCollectionForm;
use App\Filament\Resources\DebtCollections\Tables\DebtCollectionsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\DebtCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DebtCollectionResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = DebtCollection::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    public static function getModelLabel(): string
    {
        return __('debts.collections.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('debts.collections.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.debt_collections');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return DebtCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtCollectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDebtCollections::route('/'),
            'create' => CreateDebtCollection::route('/create'),
            'edit'   => EditDebtCollection::route('/{record}/edit'),
        ];
    }
}
