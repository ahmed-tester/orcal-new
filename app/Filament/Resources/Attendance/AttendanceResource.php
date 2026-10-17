<?php

namespace App\Filament\Resources\Attendance;

use App\Filament\Resources\Attendance\Pages\CreateAttendance;
use App\Filament\Resources\Attendance\Pages\EditAttendance;
use App\Filament\Resources\Attendance\Pages\ListAttendance;
use App\Filament\Resources\Attendance\Schemas\AttendanceForm;
use App\Filament\Resources\Attendance\Tables\AttendanceTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Attendance::class;

    protected static ?string $slug = 'attendances';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function getModelLabel(): string
    {
        return __('attendance.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendance.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.attendance');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceTable::configure($table);
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
            'index' => ListAttendance::route('/'),
            'create' => CreateAttendance::route('/create'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
