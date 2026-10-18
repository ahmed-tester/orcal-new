<?php

namespace App\Filament\Resources\OfficialLetters;

use App\Filament\Resources\OfficialLetters\Pages\CreateOfficialLetter;
use App\Filament\Resources\OfficialLetters\Pages\EditOfficialLetter;
use App\Filament\Resources\OfficialLetters\Pages\ListOfficialLetters;
use App\Filament\Resources\OfficialLetters\Schemas\OfficialLetterForm;
use App\Filament\Resources\OfficialLetters\Tables\OfficialLettersTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\OfficialLetter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OfficialLetterResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = OfficialLetter::class;

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    public static function getModelLabel(): string
    {
        return __('official_letters.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('official_letters.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.official_letters');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.letters_group');
    }

    public static function form(Schema $schema): Schema
    {
        return OfficialLetterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfficialLettersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOfficialLetters::route('/'),
            'create' => CreateOfficialLetter::route('/create'),
            'edit'   => EditOfficialLetter::route('/{record}/edit'),
        ];
    }
}
