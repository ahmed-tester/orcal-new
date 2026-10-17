<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\Faqs\Schemas\FaqForm;
use App\Models\Faq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    public static function getModelLabel(): string
    {
        return __('faq.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('faq.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.faqs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.faq_group');
    }

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    // No table() method needed — list page uses custom view

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit'   => EditFaq::route('/{record}/edit'),
        ];
    }
}
