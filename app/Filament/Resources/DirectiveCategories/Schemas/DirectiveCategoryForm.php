<?php

namespace App\Filament\Resources\DirectiveCategories\Schemas;

use App\Models\Directives\DirectiveCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DirectiveCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('directives.sections.category_details'))
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name_ar')
                            ->label(__('directives.fields.name_ar'))
                            ->required()
                            ->maxLength(100)
                            ->columnSpanFull(),

                        TextInput::make('name_en')
                            ->label(__('directives.fields.name_en'))
                            ->maxLength(100)
                            ->hidden()
                            ->columnSpan(1),

                        Select::make('color')
                            ->label(__('directives.fields.color'))
                            ->options(DirectiveCategory::colorOptions())
                            ->default('info')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        TextInput::make('sort_order')
                            ->label(__('directives.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('directives.fields.is_active'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
