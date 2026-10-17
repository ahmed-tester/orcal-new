<?php

namespace App\Filament\Resources\ViolationRegulations;

use App\Filament\Resources\ViolationRegulations\Pages\CreateViolationRegulation;
use App\Filament\Resources\ViolationRegulations\Pages\EditViolationRegulation;
use App\Filament\Resources\ViolationRegulations\Pages\ListViolationRegulations;
use App\Filament\Resources\ViolationRegulations\Schemas\ViolationRegulationForm;
use App\Filament\Resources\ViolationRegulations\Tables\ViolationRegulationsTable;
use App\Models\ViolationRegulation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ViolationRegulationResource extends Resource
{
    protected static ?string $model = ViolationRegulation::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public static function getModelLabel(): string
    {
        return __('violations.regulations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('violations.regulations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.violation_regulations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.violations_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ViolationRegulationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationRegulationsTable::configure($table);
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
            'index' => ListViolationRegulations::route('/'),
            'create' => CreateViolationRegulation::route('/create'),
            'edit' => EditViolationRegulation::route('/{record}/edit'),
        ];
    }
}
