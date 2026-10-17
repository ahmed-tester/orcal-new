<?php

namespace App\Filament\Resources\WeeklyNotes\Schemas;

use App\Enums\WeeklyNoteStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WeeklyNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('weekly_notes.sections.info'))
                    ->description(__('weekly_notes.sections.info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextInput::make('voucher_number')
                            ->label(__('weekly_notes.fields.voucher_number'))
                            ->default(fn () => 'WN-' . date('Ymd') . '-' . rand(100, 999))
                            ->maxLength(50),

                        DatePicker::make('note_date')
                            ->label(__('weekly_notes.fields.note_date'))
                            ->default(now())
                            ->required(),

                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('weekly_notes.fields.company')),

                        TextInput::make('title')
                            ->label(__('weekly_notes.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Select::make('status')
                            ->label(__('weekly_notes.fields.status'))
                            ->options(WeeklyNoteStatus::class)
                            ->default(WeeklyNoteStatus::PENDING->value)
                            ->required(),

                        Select::make('assigned_to')
                            ->label(__('weekly_notes.fields.assigned_to'))
                            ->relationship('assignedEmployee', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name)
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label(__('weekly_notes.fields.content'))
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
