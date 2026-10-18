<?php

namespace App\Filament\Resources\Owners\Schemas;

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Services\CompanyFormService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('owners.sections.info'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('user_name')
                            ->label(__('owners.fields.name'))
                            ->required(),

                        TextInput::make('user_email')
                            ->label(__('owners.fields.email'))
                            ->email()
                            ->nullable()
                            ->unique(\App\Models\User::class, 'email', ignoreRecord: false, modifyRuleUsing: function ($rule, $record) {
                                return $record?->user_id ? $rule->ignore($record->user_id) : $rule;
                            }),

                        PhoneService::makeFormField('user_phone', __('owners.fields.phone'))
                            ->nullable()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('user_phone', PhoneService::normalize($state) ?? $state))
                            ->unique(\App\Models\User::class, 'phone', ignoreRecord: false, modifyRuleUsing: function ($rule, $record) {
                                return $record?->user_id ? $rule->ignore($record->user_id) : $rule;
                            }),

                        TextInput::make('user_password')
                            ->label(__('owners.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn ($livewire) => $livewire instanceof CreateOwner)
                            ->helperText(fn ($livewire) => $livewire instanceof EditOwner ? __('owners.fields.password_help') : null),

                        CompanyFormService::makeCompanySelect('company_id', __('owners.fields.company'))
                            ->required(),

                        TextInput::make('national_id')
                            ->label(__('owners.fields.national_id')),

                        TextInput::make('nationality')
                            ->label(__('owners.fields.nationality')),

                        DatePicker::make('birth_date')
                            ->label(__('owners.fields.birth_date'))
                            ->native(false),

                        TextInput::make('ownership_percentage')
                            ->label(__('owners.fields.ownership_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->default(100),

                        DatePicker::make('employment_date')
                            ->label(__('owners.fields.employment_date'))
                            ->native(false)
                            ->required(),

                        DatePicker::make('termination_date')
                            ->label(__('owners.fields.termination_date'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!empty($state)) {
                                    $set('status', 'inactive');
                                }
                            }),

                        Select::make('status')
                            ->label(__('owners.fields.status'))
                            ->options(__('owners.statuses'))
                            ->default('active'),

                        FileUpload::make('image')
                            ->label(__('owners.fields.image'))
                            ->directory('owners/images')
                            ->image()
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
