<?php

namespace App\Filament\Resources\AttendanceDevices;

use App\Filament\Resources\AttendanceDevices\Pages\CreateAttendanceDevice;
use App\Filament\Resources\AttendanceDevices\Pages\EditAttendanceDevice;
use App\Filament\Resources\AttendanceDevices\Pages\ListAttendanceDevices;
use App\Filament\Resources\AttendanceDevices\Schemas\AttendanceDeviceForm;
use App\Filament\Resources\AttendanceDevices\Tables\AttendanceDevicesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\AttendanceDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AttendanceDeviceResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = AttendanceDevice::class;

    protected static ?string $slug = 'attendance-devices';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    public static function getModelLabel(): string
    {
        return __('attendance_devices.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendance_devices.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('attendance_devices.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceDevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceDevices::route('/'),
            'create' => CreateAttendanceDevice::route('/create'),
            'edit' => EditAttendanceDevice::route('/{record}/edit'),
        ];
    }
}
