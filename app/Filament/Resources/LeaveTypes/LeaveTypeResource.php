<?php

namespace App\Filament\Resources\LeaveTypes;

use App\Filament\Resources\LeaveTypes\Pages\CreateLeaveType;
use App\Filament\Resources\LeaveTypes\Pages\EditLeaveType;
use App\Filament\Resources\LeaveTypes\Pages\ListLeaveTypes;
use App\Filament\Resources\LeaveTypes\Schemas\LeaveTypeForm;
use App\Filament\Resources\LeaveTypes\Tables\LeaveTypesTable;
// use App\Filament\Traits\HasCompanyScope;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LeaveTypeResource extends Resource
{
    // use HasCompanyScope;

    protected static ?string $model = LeaveType::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('organization.leave_types.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('organization.leave_types.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.leave_types');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }
}
