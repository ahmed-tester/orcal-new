<?php

namespace App\Filament\Resources\OfficialLetters\Schemas;

use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfficialLetterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                // --- 1. Basic Info ---
                Section::make(__('official_letters.sections.basic_info'))
                    ->description(__('official_letters.sections.basic_info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([

                        CompanyFormService::makeCompanySelect(
                            'company_id',
                            __('official_letters.fields.company'),
                            required: false
                        ),

                        TextInput::make('letter_number')
                            ->label(__('official_letters.fields.letter_number'))
                            ->placeholder(__('official_letters.placeholders.auto'))
                            ->disabled()
                            ->dehydrated()
                            ->hiddenOn('create'),

                        DatePicker::make('letter_date')
                            ->label(__('official_letters.fields.letter_date'))
                            ->default(now())
                            ->native(false),

                        TextInput::make('subject')
                            ->label(__('official_letters.fields.subject'))
                            ->placeholder(__('official_letters.placeholders.subject'))
                            ->columnSpan(2),

                        Select::make('status')
                            ->label(__('official_letters.fields.status'))
                            ->options([
                                'draft'    => __('official_letters.statuses.draft'),
                                'approved' => __('official_letters.statuses.approved'),
                                'sent'     => __('official_letters.statuses.sent'),
                            ])
                            ->default('draft'),
                    ]),

                // --- 2. Letter Content ---
                Section::make(__('official_letters.sections.body'))
                    ->description(__('official_letters.sections.body_desc'))
                    ->icon('heroicon-o-pencil-square')
                    ->schema([

                        RichEditor::make('body')
                            ->label(__('official_letters.fields.body'))
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'h2',
                                'h3',
                                'link',
                                'undo',
                                'redo',
                            ])
                            ->placeholder(__('official_letters.placeholders.body'))
                            ->columnSpanFull(),
                    ]),

                // --- 3. Print & Display Options ---
                Section::make(__('official_letters.sections.print_options'))
                    ->description(__('official_letters.sections.print_options_desc'))
                    ->icon('heroicon-o-printer')
                    ->collapsible()
                    ->columns(3)
                    ->schema([

                        \Filament\Forms\Components\Toggle::make('metadata.print_options.show_logo')
                            ->label(__('official_letters.fields.show_logo'))
                            ->helperText(__('official_letters.helpers.show_logo'))
                            ->default(true),

                        \Filament\Forms\Components\Toggle::make('metadata.print_options.show_stamp')
                            ->label(__('official_letters.fields.show_stamp'))
                            ->helperText(__('official_letters.helpers.show_stamp'))
                            ->default(true),

                        \Filament\Forms\Components\Toggle::make('metadata.print_options.show_signature')
                            ->label(__('official_letters.fields.show_signature'))
                            ->helperText(__('official_letters.helpers.show_signature'))
                            ->default(true),
                    ]),
            ]);
    }
}
