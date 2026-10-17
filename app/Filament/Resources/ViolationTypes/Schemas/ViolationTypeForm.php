<?php

namespace App\Filament\Resources\ViolationTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('violations.types.sections.type_info'))
                    ->description(__('violations.types.sections.type_info_desc'))
                    ->columns(2)
                    ->schema([
                        Select::make('violation_category_id')
                            ->label(__('violations.types.fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label(__('violations.types.fields.name'))
                            ->placeholder(__('violations.types.placeholders.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label(__('violations.types.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0)),

                        Toggle::make('is_active')
                            ->label(__('violations.types.fields.is_active'))
                            ->default(true)
                            ->inline(false),

                        Textarea::make('description')
                            ->label(__('violations.types.fields.description'))
                            ->placeholder(__('violations.types.placeholders.description'))
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
