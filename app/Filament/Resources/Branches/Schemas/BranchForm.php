<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Services\CompanyFormService;
use App\Services\PhoneService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('organization.branches.sections.info'))
                    ->columns(3)
                    ->schema([
                        CompanyFormService::makeCompanySelect('company_id', __('organization.branches.fields.company')),

                        TextInput::make('name')
                            ->label(__('organization.branches.fields.name'))
                            ->required(),

                        TextInput::make('branch_code')
                            ->label(__('organization.branches.fields.branch_code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        PhoneService::makeFormField('phone', __('organization.branches.fields.phone')),

                        TextInput::make('email')
                            ->label(__('organization.branches.fields.email'))
                            ->email(),

                        Select::make('status')
                            ->label(__('organization.branches.fields.status'))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),

                        Textarea::make('address')
                            ->label(__('organization.branches.fields.address'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('organization.branches.sections.attendance_settings'))
                    ->description(__('organization.branches.sections.attendance_settings_desc'))
                    ->columns(3)
                    ->schema([
                        TimePicker::make('attendance_settings.check_in_time')
                            ->label(__('organization.branches.fields.check_in_time'))
                            ->default('08:00')
                            ->seconds(false)
                            ->format('H:i'),

                        TimePicker::make('attendance_settings.check_out_time')
                            ->label(__('organization.branches.fields.check_out_time'))
                            ->default('16:00')
                            ->seconds(false)
                            ->format('H:i'),

                        TextInput::make('attendance_settings.late_grace_minutes')
                            ->label(__('organization.branches.fields.late_grace_minutes'))
                            ->helperText(__('organization.branches.fields.late_grace_minutes_helper'))
                            ->numeric()
                            ->default(15)
                            ->minValue(0)
                            ->maxValue(120),

                        Toggle::make('attendance_settings.late_deduction_enabled')
                            ->label(__('organization.branches.fields.late_deduction_enabled'))
                            ->helperText(__('organization.branches.fields.late_deduction_enabled_desc'))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->visible(fn (callable $get) => (bool) $get('attendance_settings.late_deduction_enabled'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('attendance_settings.late_quarter_day_minutes')
                                    ->label(__('organization.branches.fields.late_quarter_day_minutes'))
                                    ->helperText(__('organization.branches.fields.late_quarter_day_minutes_helper'))
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(240),

                                TextInput::make('attendance_settings.late_half_day_minutes')
                                    ->label(__('organization.branches.fields.late_half_day_minutes'))
                                    ->helperText(__('organization.branches.fields.late_half_day_minutes_helper'))
                                    ->numeric()
                                    ->default(60)
                                    ->minValue(1)
                                    ->maxValue(480),
                            ]),

                        Toggle::make('attendance_settings.absence_penalty_enabled')
                            ->label(__('organization.branches.fields.absence_penalty_enabled'))
                            ->helperText(__('organization.branches.fields.absence_penalty_enabled_desc'))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Grid::make(1)
                            ->visible(fn (callable $get) => (bool) $get('attendance_settings.absence_penalty_enabled'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('attendance_settings.absence_deduction_multiplier')
                                    ->label(__('organization.branches.fields.absence_deduction_multiplier'))
                                    ->helperText(__('organization.branches.fields.absence_deduction_multiplier_helper'))
                                    ->numeric()
                                    ->step(0.5)
                                    ->default(1.0)
                                    ->minValue(1.0)
                                    ->maxValue(10.0),
                            ]),
                    ]),
            ]);
    }
}
