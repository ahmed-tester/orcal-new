<?php

namespace App\Filament\Resources\ViolationCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('violations.categories.sections.category_info'))
                    ->description(__('violations.categories.sections.category_info_desc'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('violations.categories.fields.name'))
                            ->placeholder(__('violations.categories.placeholders.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label(__('violations.categories.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0)),

                        Textarea::make('description')
                            ->label(__('violations.categories.fields.description'))
                            ->placeholder(__('violations.categories.placeholders.description'))
                            ->columnSpanFull()
                            ->rows(3),

                        Toggle::make('is_active')
                            ->label(__('violations.categories.fields.is_active'))
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
