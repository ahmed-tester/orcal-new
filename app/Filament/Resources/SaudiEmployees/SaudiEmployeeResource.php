<?php

namespace App\Filament\Resources\SaudiEmployees;

use App\Filament\Resources\SaudiEmployees\Pages\CreateSaudiEmployee;
use App\Filament\Resources\SaudiEmployees\Pages\EditSaudiEmployee;
use App\Filament\Resources\SaudiEmployees\Pages\ListSaudiEmployees;
use App\Filament\Resources\SaudiEmployees\Schemas\SaudiEmployeeForm;
use App\Filament\Resources\SaudiEmployees\Tables\SaudiEmployeesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SaudiEmployeeResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Employee::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('employees.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('employees.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return SaudiEmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaudiEmployeesTable::configure($table);
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
            'index' => ListSaudiEmployees::route('/'),
            'create' => CreateSaudiEmployee::route('/create'),
            'edit' => EditSaudiEmployee::route('/{record}/edit'),
        ];
    }
}
