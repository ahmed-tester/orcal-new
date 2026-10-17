<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('settings.sections.general'))
                    ->description(__('settings.sections.general_desc'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(2)
                    ->schema([
                        Select::make('group')
                            ->label(__('settings.fields.group'))
                            ->options(__('settings.groups'))
                            ->default('general')
                            ->searchable()
                            ->required(),

                        TextInput::make('key')
                            ->label(__('settings.fields.key'))
                            ->helperText(__('settings.helpers.key'))
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('label')
                            ->label(__('settings.fields.label'))
                            ->required()
                            ->maxLength(255),

                        Select::make('input_type')
                            ->label(__('settings.fields.input_type'))
                            ->options(__('settings.input_types'))
                            ->default('text')
                            ->live()
                            ->required(),

                        Textarea::make('description')
                            ->label(__('settings.fields.description'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('settings.sections.value_and_type'))
                    ->description(__('settings.sections.value_and_type_desc'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns(1)
                    ->schema([
                        Textarea::make('value')
                            ->label(__('settings.fields.value'))
                            ->rows(4)
                            ->columnSpanFull(),

                        KeyValue::make('options')
                            ->label(__('settings.fields.options'))
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->helperText(__('settings.helpers.options'))
                            ->visible(fn ($get) => $get('input_type') === 'select')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label(__('settings.fields.is_active'))
                            ->helperText(__('settings.helpers.is_active'))
                            ->default(true),
                    ]),
            ]);
    }
}
