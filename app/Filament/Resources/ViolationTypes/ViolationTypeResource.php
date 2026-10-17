<?php

namespace App\Filament\Resources\ViolationTypes;

use App\Filament\Resources\ViolationTypes\Pages\CreateViolationType;
use App\Filament\Resources\ViolationTypes\Pages\EditViolationType;
use App\Filament\Resources\ViolationTypes\Pages\ListViolationTypes;
use App\Filament\Resources\ViolationTypes\Schemas\ViolationTypeForm;
use App\Filament\Resources\ViolationTypes\Tables\ViolationTypesTable;
use App\Models\ViolationType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ViolationTypeResource extends Resource
{
    protected static ?string $model = ViolationType::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('violations.types.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('violations.types.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.violation_types');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.violations_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ViolationTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationTypesTable::configure($table);
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
            'index' => ListViolationTypes::route('/'),
            'create' => CreateViolationType::route('/create'),
            'edit' => EditViolationType::route('/{record}/edit'),
        ];
    }
}
