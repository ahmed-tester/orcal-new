<?php

namespace App\Filament\Resources\InvoiceTypes;

use App\Filament\Resources\InvoiceTypes\Pages\CreateInvoiceType;
use App\Filament\Resources\InvoiceTypes\Pages\EditInvoiceType;
use App\Filament\Resources\InvoiceTypes\Pages\ListInvoiceTypes;
use App\Filament\Resources\InvoiceTypes\Schemas\InvoiceTypeForm;
use App\Filament\Resources\InvoiceTypes\Tables\InvoiceTypesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\InvoiceType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InvoiceTypeResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = InvoiceType::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    public static function getModelLabel(): string
    {
        return __('invoices.types_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoices.types_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.invoice_types');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceTypesTable::configure($table);
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
            'index' => ListInvoiceTypes::route('/'),
            'create' => CreateInvoiceType::route('/create'),
            'edit' => EditInvoiceType::route('/{record}/edit'),
        ];
    }
}
