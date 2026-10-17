<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\GeneralStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('tasks.sections.info'))
                    ->description(__('tasks.sections.info_desc'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([
                        TextInput::make('task_code')
                            ->label(__('tasks.fields.task_code'))
                            ->placeholder('Auto Generated')
                            ->disabled()
                            ->maxLength(50),

                        TextInput::make('name')
                            ->label(__('tasks.fields.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('default_weight')
                            ->label(__('tasks.fields.default_weight'))
                            ->numeric()
                            ->default(0.00)
                            ->minValue(0)
                            ->maxValue(100)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->suffix('%'),

                        Select::make('status')
                            ->label(__('tasks.fields.status'))
                            ->options(GeneralStatus::class)
                            ->default(GeneralStatus::ACTIVE->value)
                            ->required(),

                        Textarea::make('description')
                            ->label(__('tasks.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
