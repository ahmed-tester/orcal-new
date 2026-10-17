<?php

namespace App\Filament\Resources\AttendanceDevices\Schemas;

use App\Filament\Components\OrgSelect;
use App\Models\Branch;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('attendance_devices.sections.device_info'))
                    ->description(__('attendance_devices.sections.device_info_desc'))
                    ->columns(3)
                    ->schema([
                        OrgSelect::company('company_id', __('attendance_devices.fields.company')),

                        Select::make('branch_id')
                            ->label(__('attendance_devices.fields.branch'))
                            ->options(fn (callable $get) => Branch::when(
                                $get('company_id'),
                                fn ($q, $cid) => $q->where('company_id', $cid)
                            )->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        TextInput::make('name')
                            ->label(__('attendance_devices.fields.name'))
                            ->placeholder(__('attendance_devices.placeholders.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(__('attendance_devices.fields.code'))
                            ->placeholder(__('attendance_devices.placeholders.code'))
                            ->helperText(__('attendance_devices.fields.code_helper'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('model')
                            ->label(__('attendance_devices.fields.model'))
                            ->placeholder(__('attendance_devices.placeholders.model'))
                            ->maxLength(255),

                        Select::make('device_type')
                            ->label(__('attendance_devices.fields.device_type'))
                            ->options([
                                'fingerprint' => __('attendance_devices.types.fingerprint'),
                                'face' => __('attendance_devices.types.face'),
                                'fingerprint_face' => __('attendance_devices.types.fingerprint_face'),
                                'rfid_card' => __('attendance_devices.types.rfid_card'),
                            ])
                            ->default('fingerprint_face'),

                        TextInput::make('ip_address')
                            ->label(__('attendance_devices.fields.ip_address'))
                            ->placeholder(__('attendance_devices.placeholders.ip'))
                            ->maxLength(45),

                        TextInput::make('location')
                            ->label(__('attendance_devices.fields.location'))
                            ->placeholder(__('attendance_devices.placeholders.location'))
                            ->maxLength(255),

                        Select::make('status')
                            ->label(__('attendance_devices.fields.status'))
                            ->options([
                                'active' => __('attendance_devices.statuses.active'),
                                'inactive' => __('attendance_devices.statuses.inactive'),
                                'maintenance' => __('attendance_devices.statuses.maintenance'),
                            ])
                            ->default('active')
                            ->required(),

                        DateTimePicker::make('last_seen_at')
                            ->label(__('attendance_devices.fields.last_seen_at'))
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
