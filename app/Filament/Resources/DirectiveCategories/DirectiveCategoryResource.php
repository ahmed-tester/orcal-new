<?php

namespace App\Filament\Resources\DirectiveCategories;

use App\Filament\Resources\DirectiveCategories\Pages\CreateDirectiveCategory;
use App\Filament\Resources\DirectiveCategories\Pages\EditDirectiveCategory;
use App\Filament\Resources\DirectiveCategories\Pages\ListDirectiveCategories;
use App\Filament\Resources\DirectiveCategories\Schemas\DirectiveCategoryForm;
use App\Filament\Resources\DirectiveCategories\Tables\DirectiveCategoriesTable;
use App\Models\DirectiveCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DirectiveCategoryResource extends Resource
{
    protected static ?string $model = DirectiveCategory::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('directives.models.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('directives.models.category.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.directive_categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function form(Schema $schema): Schema
    {
        return DirectiveCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectiveCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectiveCategories::route('/'),
            'create' => CreateDirectiveCategory::route('/create'),
            'edit' => EditDirectiveCategory::route('/{record}/edit'),
        ];
    }
}
