<?php

namespace App\Filament\Resources\PosDevices;

use App\Filament\Resources\PosDevices\Pages\CreatePosDevice;
use App\Filament\Resources\PosDevices\Pages\EditPosDevice;
use App\Filament\Resources\PosDevices\Pages\ListPosDevices;
use App\Filament\Resources\PosDevices\Schemas\PosDeviceForm;
use App\Filament\Resources\PosDevices\Tables\PosDevicesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\PosDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PosDeviceResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = PosDevice::class;

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function getModelLabel(): string
    {
        return __('finance.pos_devices.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.pos_devices.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.pos_devices');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return PosDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosDevicesTable::configure($table);
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
            'index'  => ListPosDevices::route('/'),
            'create' => CreatePosDevice::route('/create'),
            'edit'   => EditPosDevice::route('/{record}/edit'),
        ];
    }
}
