<?php

namespace App\Filament\Resources\ServiceTransactions;

use App\Filament\Resources\ServiceTransactions\Pages\CreateServiceTransaction;
use App\Filament\Resources\ServiceTransactions\Pages\EditServiceTransaction;
use App\Filament\Resources\ServiceTransactions\Pages\ListServiceTransactions;
use App\Filament\Resources\ServiceTransactions\Schemas\ServiceTransactionForm;
use App\Filament\Resources\ServiceTransactions\Tables\ServiceTransactionsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\ServiceTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ServiceTransactionResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = ServiceTransaction::class;

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    public static function getModelLabel(): string
    {
        return __('services_tracking.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('services_tracking.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.services_tracking');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListServiceTransactions::route('/'),
            'create' => CreateServiceTransaction::route('/create'),
            'edit'   => EditServiceTransaction::route('/{record}/edit'),
        ];
    }
}
