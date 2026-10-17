<?php

namespace App\Filament\Resources\InvoiceTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('invoices.types_single'))
                    ->icon('heroicon-o-folder')
                    ->columns(2)
                    ->schema([
                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('invoices.fields.company'), false)
                            ->placeholder(__('invoices.placeholders.general_to_all')),

                        TextInput::make('name')
                            ->label(__('invoices.fields.invoice_type'))
                            ->placeholder(__('invoices.placeholders.type_name_example'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(__('invoices.fields.code'))
                            ->maxLength(50),

                        Select::make('status')
                            ->label(__('invoices.fields.status'))
                            ->options(\App\Enums\GeneralStatus::class)
                            ->default(\App\Enums\GeneralStatus::ACTIVE->value)
                            ->required(),

                        Textarea::make('description')
                            ->label(__('invoices.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
