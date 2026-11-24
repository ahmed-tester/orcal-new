<?php

namespace App\Filament\Resources\Mail\MailCategories\Schemas;

use App\Models\Mail\MailCategory;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MailCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('mail_inbox.sections.category_details'))
                ->description(__('mail_inbox.sections.category_details_description'))
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('slug')
                        ->label(__('mail_inbox.fields.category_slug'))
                        ->placeholder('billing_requests')
                        ->helperText(__('mail_inbox.helpers.category_slug'))
                        ->required()
                        ->maxLength(64)
                        ->regex('/^[a-z0-9_-]+$/')
                        ->unique(table: MailCategory::class, column: 'slug', ignoreRecord: true)
                        ->disabledOn('edit')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                    TextInput::make('sort_order')
                        ->label(__('mail_inbox.fields.sort_order'))
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->required(),

                    TextInput::make('name_ar')
                        ->label(__('mail_inbox.fields.name_ar'))
                        ->placeholder(__('mail_inbox.placeholders.category_name_ar'))
                        ->required()
                        ->maxLength(120),

                    TextInput::make('name_en')
                        ->label(__('mail_inbox.fields.name_en'))
                        ->placeholder('Billing')
                        ->required()
                        ->maxLength(120),
                ]),

            Section::make(__('mail_inbox.sections.category_appearance_and_rules'))
                ->description(__('mail_inbox.sections.category_appearance_description'))
                ->icon('heroicon-o-swatch')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('color')
                        ->label(__('mail_inbox.fields.badge_color'))
                        ->options(collect(MailCategory::ALLOWED_COLORS)->mapWithKeys(
                            fn (string $color): array => [$color => __("mail_inbox.colors.{$color}")]
                        )->all())
                        ->default('gray')
                        ->native(false)
                        ->required(),

                    ColorPicker::make('chart_color')
                        ->label(__('mail_inbox.fields.chart_color'))
                        ->default('#64748b')
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),

                    Toggle::make('is_excluded')
                        ->label(__('mail_inbox.fields.is_excluded_category'))
                        ->inline(false)
                        ->live()
                        ->helperText(fn (?MailCategory $record): string => $record && $record->messages()->exists()
                            ? __('mail_inbox.helpers.category_excluded_impact', ['count' => $record->messages()->count()])
                            : __('mail_inbox.helpers.category_excluded'))
                        ->disabled(fn (?MailCategory $record): bool => $record?->slug === MailCategory::SYSTEM_OTHER_SLUG),

                    Toggle::make('is_active')
                        ->label(__('mail_inbox.fields.is_active'))
                        ->inline(false)
                        ->default(true)
                        ->helperText(__('mail_inbox.helpers.category_active'))
                        ->disabled(fn (?MailCategory $record): bool => $record?->slug === MailCategory::SYSTEM_OTHER_SLUG),

                    Toggle::make('confirm_excluded_change')
                        ->label(fn (?MailCategory $record): string => __('mail_inbox.fields.confirm_excluded_change', [
                            'count' => $record?->messages()->count() ?? 0,
                        ]))
                        ->columnSpanFull()
                        ->dehydrated(false)
                        ->accepted()
                        ->required()
                        ->visible(fn (string $operation, ?MailCategory $record, Get $get): bool => $operation === 'edit'
                            && $record !== null
                            && $record->messages()->exists()
                            && (bool) $get('is_excluded') !== (bool) $record->is_excluded),
                ]),
        ]);
    }
}
