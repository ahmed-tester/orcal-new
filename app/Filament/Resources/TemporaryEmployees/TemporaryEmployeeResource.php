<?php

namespace App\Filament\Resources\TemporaryEmployees;

use App\Filament\Resources\TemporaryEmployees\Pages\CreateTemporaryEmployee;
use App\Filament\Resources\TemporaryEmployees\Pages\EditTemporaryEmployee;
use App\Filament\Resources\TemporaryEmployees\Pages\ListTemporaryEmployees;
use App\Filament\Resources\TemporaryEmployees\Schemas\TemporaryEmployeeForm;
use App\Filament\Resources\TemporaryEmployees\Tables\TemporaryEmployeesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\TemporaryEmployee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TemporaryEmployeeResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = TemporaryEmployee::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function getModelLabel(): string
    {
        return __('employees.temporary_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('employees.temporary_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.temporary_employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return TemporaryEmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemporaryEmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemporaryEmployees::route('/'),
            'create' => CreateTemporaryEmployee::route('/create'),
            'edit' => EditTemporaryEmployee::route('/{record}/edit'),
        ];
    }
}

