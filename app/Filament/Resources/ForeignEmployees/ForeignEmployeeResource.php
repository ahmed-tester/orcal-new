<?php

namespace App\Filament\Resources\ForeignEmployees;

use App\Filament\Resources\ForeignEmployees\Pages\CreateForeignEmployee;
use App\Filament\Resources\ForeignEmployees\Pages\EditForeignEmployee;
use App\Filament\Resources\ForeignEmployees\Pages\ListForeignEmployees;
use App\Filament\Resources\ForeignEmployees\Schemas\ForeignEmployeeForm;
use App\Filament\Resources\ForeignEmployees\Tables\ForeignEmployeesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\ForeignEmployee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ForeignEmployeeResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = ForeignEmployee::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    public static function getModelLabel(): string
    {
        return __('employees.foreign_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('employees.foreign_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.foreign_employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ForeignEmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ForeignEmployeesTable::configure($table);
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
            'index' => ListForeignEmployees::route('/'),
            'create' => CreateForeignEmployee::route('/create'),
            'edit' => EditForeignEmployee::route('/{record}/edit'),
        ];
    }
}
