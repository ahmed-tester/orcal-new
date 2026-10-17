<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use App\Services\PhoneService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('customers.sections.basic_info'))
                    ->description(__('customers.sections.basic_info_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(3)
                    ->schema([
                        Select::make('customer_type')
                            ->label(__('customers.fields.customer_type'))
                            ->options(CustomerType::class)
                            ->default(CustomerType::INDIVIDUAL)
                            ->live()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('customers.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Select::make('status')
                            ->label(__('customers.fields.status'))
                            ->options(__('customers.statuses'))
                            ->default('active')
                            ->required(),

                        TextInput::make('national_id')
                            ->label(__('customers.fields.national_id'))
                            ->maxLength(50)
                            ->visible(fn (callable $get) => in_array($get('customer_type'), ['individual', 'other', CustomerType::INDIVIDUAL, CustomerType::OTHER, null], true)),

                        TextInput::make('commercial_registration_number')
                            ->label(__('customers.fields.cr_number'))
                            ->maxLength(50)
                            ->visible(fn (callable $get) => in_array($get('customer_type'), ['company', 'establishment', 'government', 'other', CustomerType::COMPANY, CustomerType::ESTABLISHMENT, CustomerType::GOVERNMENT, CustomerType::OTHER], true)),

                        TextInput::make('tax_number')
                            ->label(__('customers.fields.tax_number'))
                            ->maxLength(50),
                    ]),

                Section::make(__('customers.sections.contact_info'))
                    ->description(__('customers.sections.contact_info_desc'))
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        PhoneService::makeFormField('phone', __('customers.fields.phone')),

                        TextInput::make('email')
                            ->label(__('customers.fields.email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label(__('customers.fields.city'))
                            ->maxLength(100),

                        Textarea::make('address')
                            ->label(__('customers.fields.address'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label(__('customers.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
