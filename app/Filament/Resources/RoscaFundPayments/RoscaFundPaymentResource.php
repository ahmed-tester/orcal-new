<?php

namespace App\Filament\Resources\RoscaFundPayments;

use App\Filament\Resources\RoscaFundPayments\Pages\CreateRoscaFundPayment;
use App\Filament\Resources\RoscaFundPayments\Pages\EditRoscaFundPayment;
use App\Filament\Resources\RoscaFundPayments\Pages\ListRoscaFundPayments;
use App\Filament\Resources\RoscaFundPayments\Schemas\RoscaFundPaymentForm;
use App\Filament\Resources\RoscaFundPayments\Tables\RoscaFundPaymentsTable;
use App\Models\RoscaFundPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RoscaFundPaymentResource extends Resource
{
    protected static ?string $model = RoscaFundPayment::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    public static function getModelLabel(): string
    {
        return __('funds.rosca_fund_payments.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.rosca_fund_payments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.rosca_fund_payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return RoscaFundPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoscaFundPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoscaFundPayments::route('/'),
            'create' => CreateRoscaFundPayment::route('/create'),
            'edit'   => EditRoscaFundPayment::route('/{record}/edit'),
        ];
    }
}
