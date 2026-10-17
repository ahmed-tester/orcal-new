<?php

namespace App\Filament\Resources\Offers;

use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Filament\Resources\Offers\Schemas\OfferForm;
use App\Filament\Resources\Offers\Tables\OffersTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OfferResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Offer::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getModelLabel(): string
    {
        return __('offers.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offers.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.offers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.contracts_group');
    }

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'edit'   => EditOffer::route('/{record}/edit'),
        ];
    }
}
