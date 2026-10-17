<?php

namespace App\Filament\Resources\InstallmentPayments;

use App\Filament\Resources\InstallmentPayments\Pages\CreateInstallmentPayment;
use App\Filament\Resources\InstallmentPayments\Pages\EditInstallmentPayment;
use App\Filament\Resources\InstallmentPayments\Pages\ListInstallmentPayments;
use App\Filament\Resources\InstallmentPayments\Schemas\InstallmentPaymentForm;
use App\Filament\Resources\InstallmentPayments\Tables\InstallmentPaymentsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\InstallmentPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InstallmentPaymentResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = InstallmentPayment::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    public static function getModelLabel(): string
    {
        return __('finance.installment_payments.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.installment_payments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.installment_payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return InstallmentPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInstallmentPayments::route('/'),
            'create' => CreateInstallmentPayment::route('/create'),
            'edit'   => EditInstallmentPayment::route('/{record}/edit'),
        ];
    }
}
