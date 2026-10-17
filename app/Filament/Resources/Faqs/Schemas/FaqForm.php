<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('faq.sections.question_info'))
                    ->description(__('faq.sections.question_info_desc'))
                    ->icon('heroicon-o-question-mark-circle')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(__('faq.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0))
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('faq.fields.is_active'))
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),

                        TextInput::make('question')
                            ->label(__('faq.fields.question'))
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('faq.sections.answer'))
                    ->description(__('faq.sections.answer_desc'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        RichEditor::make('answer')
                            ->label(__('faq.fields.answer'))
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'orderedList',
                                'bulletList',
                                'blockquote',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
