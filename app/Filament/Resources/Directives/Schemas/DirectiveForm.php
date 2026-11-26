<?php

namespace App\Filament\Resources\Directives\Schemas;

use App\Models\Directive;
use App\Models\DirectiveCategory;
use App\Services\DirectiveService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DirectiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('directives.sections.directive_details'))
                    ->description(__('directives.sections.directive_details_desc'))
                    ->icon('heroicon-o-megaphone')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('directives.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('category_id')
                            ->label(__('directives.fields.category'))
                            ->options(DirectiveCategory::options())
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('directives.fields.is_active'))
                            ->default(true)
                            ->columnSpan(1)
                            ->inline(false),

                        Textarea::make('description')
                            ->label(__('directives.fields.description'))
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('directives.sections.target_audience'))
                    ->icon('heroicon-o-users')
                    ->columns(1)
                    ->schema([
                        ToggleButtons::make('target_type')
                            ->label(__('directives.fields.target_type'))
                            ->options([
                                Directive::TARGET_ALL      => __('directives.targets.all'),
                                Directive::TARGET_SELECTED => __('directives.targets.selected'),
                            ])
                            ->icons([
                                Directive::TARGET_ALL      => 'heroicon-o-user-group',
                                Directive::TARGET_SELECTED => 'heroicon-o-users',
                            ])
                            ->colors([
                                Directive::TARGET_ALL      => 'info',
                                Directive::TARGET_SELECTED => 'warning',
                            ])
                            ->default(Directive::TARGET_ALL)
                            ->required()
                            ->live()
                            ->helperText(fn ($state): string => match ($state) {
                                Directive::TARGET_ALL      => __('directives.helpers.target_all'),
                                Directive::TARGET_SELECTED => __('directives.helpers.target_selected'),
                                default                    => '',
                            }),

                        Select::make('selected_employee_ids')
                            ->label(__('directives.fields.employees'))
                            ->options(fn (): array => DirectiveService::getEmployeeSelectOptions())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->visible(fn ($get): bool => $get('target_type') === Directive::TARGET_SELECTED)
                            ->required(fn ($get): bool => $get('target_type') === Directive::TARGET_SELECTED),
                    ]),
            ]);
    }
}
