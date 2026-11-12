<?php

namespace App\Filament\Resources\Owners\Schemas;

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Models\Setting;
use App\Models\User;
use App\Services\CompanyFormService;
use App\Services\OwnerFormService;
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
                        ...OwnerFormService::makeRegistrationFields(),

                        TextInput::make('user_name')
                            ->label(__('owners.fields.name'))
                            ->required(),

                        TextInput::make('name_en')
                            ->label(__('owners.fields.name_en'))
                            ->placeholder(__('owners.fields.name_en_placeholder'))
                            ->maxLength(255),

                        TextInput::make('user_email')
                            ->label(__('owners.fields.email'))
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: false, modifyRuleUsing: OwnerFormService::ignoreSelectedEmployeeUserId()),

                        PhoneService::makeFormField('user_phone', __('owners.fields.phone'), required: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('user_phone', PhoneService::normalize($state) ?? $state))
                            ->unique(User::class, 'phone', ignoreRecord: false, modifyRuleUsing: OwnerFormService::ignoreSelectedEmployeeUserId()),

                        TextInput::make('user_password')
                            ->label(__('owners.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn ($get, $livewire) => $livewire instanceof CreateOwner && $get('registration_source') !== 'from_saudi_employee')
                            ->helperText(OwnerFormService::getPasswordHelperText()),

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
                                if (! empty($state)) {
                                    $set('status', 'inactive');
                                }
                            }),

                        Select::make('status')
                            ->label(__('owners.fields.status'))
                            ->options(__('owners.statuses'))
                            ->default('active'),

                        TextInput::make('address')
                            ->label(__('owners.fields.address')),

                        FileUpload::make('image')
                            ->label(__('owners.fields.image'))
                            ->disk('public')
                            ->directory('owners/images')
                            ->image()
                            ->maxSize(Setting::getMaxUploadSizeKb())
                            ->columnSpanFull(),
                    ]),

                Section::make(__('owners.sections.financial_info'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema(OwnerFormService::makeFinancialFields()),
            ]);
    }
}
