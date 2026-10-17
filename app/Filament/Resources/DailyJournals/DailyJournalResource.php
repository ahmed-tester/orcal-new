<?php

namespace App\Filament\Resources\DailyJournals;

use App\Filament\Resources\DailyJournals\Pages\CreateDailyJournal;
use App\Filament\Resources\DailyJournals\Pages\EditDailyJournal;
use App\Filament\Resources\DailyJournals\Pages\ListDailyJournals;
use App\Filament\Resources\DailyJournals\Schemas\DailyJournalForm;
use App\Filament\Resources\DailyJournals\Tables\DailyJournalsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\DailyJournal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DailyJournalResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = DailyJournal::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public static function getModelLabel(): string
    {
        return __('finance.daily_journals.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.daily_journals.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.daily_journals');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return DailyJournalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyJournalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDailyJournals::route('/'),
            'create' => CreateDailyJournal::route('/create'),
            'edit'   => EditDailyJournal::route('/{record}/edit'),
        ];
    }
}
