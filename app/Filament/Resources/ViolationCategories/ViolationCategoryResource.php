<?php

namespace App\Filament\Resources\ViolationCategories;

use App\Filament\Resources\ViolationCategories\Pages\CreateViolationCategory;
use App\Filament\Resources\ViolationCategories\Pages\EditViolationCategory;
use App\Filament\Resources\ViolationCategories\Pages\ListViolationCategories;
use App\Filament\Resources\ViolationCategories\Schemas\ViolationCategoryForm;
use App\Filament\Resources\ViolationCategories\Tables\ViolationCategoriesTable;
use App\Models\ViolationCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ViolationCategoryResource extends Resource
{
    protected static ?string $model = ViolationCategory::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    public static function getModelLabel(): string
    {
        return __('violations.categories.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('violations.categories.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.violation_categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.violations_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ViolationCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationCategoriesTable::configure($table);
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
            'index' => ListViolationCategories::route('/'),
            'create' => CreateViolationCategory::route('/create'),
            'edit' => EditViolationCategory::route('/{record}/edit'),
        ];
    }
}
