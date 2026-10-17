<?php

namespace App\Filament\Resources\SupervisionShifts;

use App\Filament\Resources\SupervisionShifts\Pages\CreateSupervisionShift;
use App\Filament\Resources\SupervisionShifts\Pages\EditSupervisionShift;
use App\Filament\Resources\SupervisionShifts\Pages\ListSupervisionShifts;
use App\Filament\Resources\SupervisionShifts\Schemas\SupervisionShiftForm;
use App\Filament\Resources\SupervisionShifts\Tables\SupervisionShiftsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\SupervisionShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SupervisionShiftResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = SupervisionShift::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('tasks.supervision_shifts.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tasks.supervision_shifts.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.supervision_shifts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.tasks_group');
    }

    public static function form(Schema $schema): Schema
    {
        return SupervisionShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupervisionShiftsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisionShifts::route('/'),
            'create' => CreateSupervisionShift::route('/create'),
            'edit' => EditSupervisionShift::route('/{record}/edit'),
        ];
    }
}
