<?php

namespace App\Filament\Resources\Mail\MailCategories;

use App\Filament\Resources\Mail\MailCategories\Pages\CreateMailCategory;
use App\Filament\Resources\Mail\MailCategories\Pages\EditMailCategory;
use App\Filament\Resources\Mail\MailCategories\Pages\ListMailCategories;
use App\Filament\Resources\Mail\MailCategories\Schemas\MailCategoryForm;
use App\Filament\Resources\Mail\MailCategories\Tables\MailCategoriesTable;
use App\Models\Mail\MailCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MailCategoryResource extends Resource
{
    protected static ?string $model = MailCategory::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.category.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.mail_rules_group');
    }

    public static function form(Schema $schema): Schema
    {
        return MailCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailCategories::route('/'),
            'create' => CreateMailCategory::route('/create'),
            'edit' => EditMailCategory::route('/{record}/edit'),
        ];
    }
}
