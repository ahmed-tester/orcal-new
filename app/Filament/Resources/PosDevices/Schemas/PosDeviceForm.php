<?php

namespace App\Filament\Resources\PosDevices\Schemas;

use App\Filament\Components\OrgSelect;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Owner;
use App\Services\PhoneService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PosDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.pos_devices.sections.company_device_info'))
                    ->description(__('finance.pos_devices.sections.company_device_info_desc'))
                    ->icon('heroicon-o-credit-card')
                    ->columns(3)
                    ->schema([
                        OrgSelect::company(
                            'company_id',
                            __('finance.pos_devices.fields.company'),
                            afterUpdated: function ($state, callable $set) {
                                if ($state) {
                                    $company = Company::with(['owner.user', 'owners.user'])->find($state);
                                    if ($company) {
                                        $set('commercial_registration_number', $company->commercial_registration_number);
                                        $owner = $company->owner ?? $company->owners->first();
                                        if ($owner) {
                                            $set('owner_id', $owner->id);
                                            $set('owner_identity_number', $owner->national_id ?? $owner->identity_number);
                                            $set('owner_phone', $owner->phone ?? $owner->user?->phone);
                                        } else {
                                            $set('owner_id', null);
                                            $set('owner_identity_number', null);
                                            $set('owner_phone', null);
                                        }
                                    }
                                } else {
                                    $set('commercial_registration_number', null);
                                    $set('owner_id', null);
                                    $set('owner_identity_number', null);
                                    $set('owner_phone', null);
                                }
                            }
                        ),

                        TextInput::make('commercial_registration_number')
                            ->label(__('finance.pos_devices.fields.commercial_registration_number'))
                            ->placeholder(__('finance.pos_devices.placeholders.cr_auto'))
                            ->default(fn () => OrgSelect::getDefaultCompany()?->commercial_registration_number),

                        Select::make('owner_id')
                            ->label(__('finance.pos_devices.fields.owner'))
                            ->relationship('owner', 'id', function ($query, callable $get) {
                                $companyId = $get('company_id') ?? OrgSelect::getDefaultCompanyId();
                                if ($companyId) {
                                    $query->where('company_id', $companyId);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (string)$record->id)
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $owner = Owner::with('user')->find($state);
                                    if ($owner) {
                                        $set('owner_identity_number', $owner->national_id ?? $owner->identity_number);
                                        $set('owner_phone', $owner->phone ?? $owner->user?->phone);
                                    }
                                } else {
                                    $set('owner_identity_number', null);
                                    $set('owner_phone', null);
                                }
                            })
                            ->default(function () {
                                $company = OrgSelect::getDefaultCompany();
                                return $company?->owner?->id ?? $company?->owners?->first()?->id;
                            }),

                        TextInput::make('owner_identity_number')
                            ->label(__('finance.pos_devices.fields.owner_identity_number'))
                            ->default(function () {
                                $company = OrgSelect::getDefaultCompany();
                                $owner = $company?->owner ?? $company?->owners?->first();
                                return $owner?->national_id ?? $owner?->identity_number;
                            }),

                        PhoneService::makeFormField('owner_phone', __('finance.pos_devices.fields.owner_phone'))
                            ->default(function () {
                                $company = OrgSelect::getDefaultCompany();
                                $owner = $company?->owner ?? $company?->owners?->first();
                                return $owner?->phone ?? $owner?->user?->phone;
                            }),

                        TextInput::make('device_number')
                            ->label(__('finance.pos_devices.fields.device_number'))
                            ->placeholder(__('finance.pos_devices.placeholders.device_example'))
                            ->required(),

                        TextInput::make('card_number')
                            ->label(__('finance.pos_devices.fields.card_number'))
                            ->placeholder(__('finance.pos_devices.placeholders.card_example'))
                            ->required(),

                        TextInput::make('bank_or_wallet_name')
                            ->label(__('finance.pos_devices.fields.bank_or_wallet_name'))
                            ->placeholder(__('finance.pos_devices.placeholders.bank_or_wallet_name'))
                            ->maxLength(255),

                        TextInput::make('account_number')
                            ->label(__('finance.pos_devices.fields.account_number'))
                            ->placeholder(__('finance.pos_devices.placeholders.account_example'))
                            ->required(),

                        Select::make('status')
                            ->label(__('finance.pos_devices.fields.status'))
                            ->options(\App\Enums\PosDeviceStatus::class)
                            ->default(\App\Enums\PosDeviceStatus::ACTIVE->value)
                            ->required(),
                    ]),

                Section::make(__('finance.pos_devices.sections.accountant_info'))
                    ->description(__('finance.pos_devices.sections.accountant_info_desc'))
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([
                        OrgSelect::employee(
                            name: 'accountant_id',
                            label: __('finance.pos_devices.fields.accountant_name'),
                            valueKey: 'id',
                            autoFillPhone: 'accountant_phone',
                            autoFillIdentity: 'accountant_identity_number',
                            autoFillEmail: 'accountant_email',
                            autoFillName: 'accountant_name',
                            required: false,
                        ),

                        Hidden::make('accountant_name'),

                        TextInput::make('accountant_identity_number')
                            ->label(__('finance.pos_devices.fields.accountant_identity_number'))
                            ->placeholder(__('finance.pos_devices.placeholders.accountant_id')),

                        TextInput::make('accountant_email')
                            ->label(__('finance.pos_devices.fields.accountant_email'))
                            ->email()
                            ->placeholder(__('finance.pos_devices.placeholders.accountant_email')),

                        PhoneService::makeFormField('accountant_phone', __('finance.pos_devices.fields.accountant_phone')),

                        TextInput::make('accountant_password')
                            ->label(__('finance.pos_devices.fields.accountant_password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->placeholder(__('finance.pos_devices.placeholders.pin')),
                    ]),

                Section::make(__('finance.pos_devices.sections.notes'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->columns(1)
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('finance.pos_devices.fields.notes'))
                            ->placeholder(__('finance.pos_devices.placeholders.notes'))
                            ->rows(3),
                    ]),
            ]);
    }
}
