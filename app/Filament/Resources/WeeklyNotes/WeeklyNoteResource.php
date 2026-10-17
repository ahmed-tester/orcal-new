<?php

namespace App\Filament\Resources\WeeklyNotes;

use App\Filament\Resources\WeeklyNotes\Pages\CreateWeeklyNote;
use App\Filament\Resources\WeeklyNotes\Pages\EditWeeklyNote;
use App\Filament\Resources\WeeklyNotes\Pages\ListWeeklyNotes;
use App\Filament\Resources\WeeklyNotes\Schemas\WeeklyNoteForm;
use App\Filament\Resources\WeeklyNotes\Tables\WeeklyNotesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\WeeklyNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WeeklyNoteResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = WeeklyNote::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return __('weekly_notes.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('weekly_notes.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.weekly_notes');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.tasks_group');
    }

    public static function form(Schema $schema): Schema
    {
        return WeeklyNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WeeklyNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeeklyNotes::route('/'),
            'create' => CreateWeeklyNote::route('/create'),
            'edit' => EditWeeklyNote::route('/{record}/edit'),
        ];
    }
}
