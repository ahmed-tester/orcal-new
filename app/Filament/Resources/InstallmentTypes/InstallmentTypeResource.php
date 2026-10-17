<?php

namespace App\Filament\Resources\InstallmentTypes;

use App\Filament\Resources\InstallmentTypes\Pages\CreateInstallmentType;
use App\Filament\Resources\InstallmentTypes\Pages\EditInstallmentType;
use App\Filament\Resources\InstallmentTypes\Pages\ListInstallmentTypes;
use App\Filament\Resources\InstallmentTypes\Schemas\InstallmentTypeForm;
use App\Filament\Resources\InstallmentTypes\Tables\InstallmentTypesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\InstallmentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InstallmentTypeResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = InstallmentType::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('finance.installment_types.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.installment_types.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.installment_types');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return InstallmentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInstallmentTypes::route('/'),
            'create' => CreateInstallmentType::route('/create'),
            'edit'   => EditInstallmentType::route('/{record}/edit'),
        ];
    }
}
