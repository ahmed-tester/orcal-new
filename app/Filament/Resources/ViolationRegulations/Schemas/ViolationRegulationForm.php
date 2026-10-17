<?php

namespace App\Filament\Resources\ViolationRegulations\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationRegulationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('violations.regulations.sections.regulation_info'))
                    ->description(__('violations.regulations.sections.regulation_info_desc'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('violations.regulations.fields.title'))
                            ->placeholder(__('violations.regulations.placeholders.title'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label(__('violations.regulations.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0)),

                        Toggle::make('is_active')
                            ->label(__('violations.regulations.fields.is_active'))
                            ->default(true)
                            ->inline(false),

                        RichEditor::make('content')
                            ->label(__('violations.regulations.fields.content'))
                            ->placeholder(__('violations.regulations.placeholders.content'))
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
