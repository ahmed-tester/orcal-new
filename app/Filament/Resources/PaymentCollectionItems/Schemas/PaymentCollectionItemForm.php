<?php

namespace App\Filament\Resources\PaymentCollectionItems\Schemas;

use App\Filament\Components\OrgSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentCollectionItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.payment_collection_items.sections.item_info'))
                    ->description(__('finance.payment_collection_items.sections.item_info_desc'))
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('transaction_number')
                            ->label(__('finance.payment_collection_items.fields.transaction_number'))
                            ->placeholder(__('finance.payment_collection_items.placeholders.auto_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        OrgSelect::company('company_id', __('finance.payment_collection_items.fields.company')),

                        Select::make('main_item')
                            ->label(__('finance.payment_collection_items.fields.main_item'))
                            ->options(\App\Enums\PaymentItemCategory::class)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('sub_item')
                            ->label(__('finance.payment_collection_items.fields.sub_item'))
                            ->placeholder(__('finance.payment_collection_items.placeholders.sub_item_example'))
                            ->required(),

                        TextInput::make('service_price')
                            ->label(__('finance.payment_collection_items.fields.service_price'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->required(),
                    ]),
            ]);
    }
}
