<?php

namespace App\Filament\Resources\Installments;

use App\Filament\Resources\Installments\Pages\CreateInstallment;
use App\Filament\Resources\Installments\Pages\EditInstallment;
use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Filament\Resources\Installments\Schemas\InstallmentForm;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Installment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InstallmentResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Installment::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function getModelLabel(): string
    {
        return __('finance.installments.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.installments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.installments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return InstallmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInstallments::route('/'),
            'create' => CreateInstallment::route('/create'),
            'edit'   => EditInstallment::route('/{record}/edit'),
        ];
    }
}
