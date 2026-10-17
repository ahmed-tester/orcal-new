<?php

namespace App\Filament\Resources\PaymentCollectionItems;

use App\Filament\Resources\PaymentCollectionItems\Pages\CreatePaymentCollectionItem;
use App\Filament\Resources\PaymentCollectionItems\Pages\EditPaymentCollectionItem;
use App\Filament\Resources\PaymentCollectionItems\Pages\ListPaymentCollectionItems;
use App\Filament\Resources\PaymentCollectionItems\Schemas\PaymentCollectionItemForm;
use App\Filament\Resources\PaymentCollectionItems\Tables\PaymentCollectionItemsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\PaymentCollectionItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PaymentCollectionItemResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = PaymentCollectionItem::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function getModelLabel(): string
    {
        return __('finance.payment_collection_items.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.payment_collection_items.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.payment_collection_items');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentCollectionItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentCollectionItemsTable::configure($table);
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
            'index'  => ListPaymentCollectionItems::route('/'),
            'create' => CreatePaymentCollectionItem::route('/create'),
            'edit'   => EditPaymentCollectionItem::route('/{record}/edit'),
        ];
    }
}
