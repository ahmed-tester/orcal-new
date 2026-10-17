<?php

namespace App\Filament\Resources\LoanFundLoanPayments;

use App\Filament\Resources\LoanFundLoanPayments\Pages\CreateLoanFundLoanPayment;
use App\Filament\Resources\LoanFundLoanPayments\Pages\EditLoanFundLoanPayment;
use App\Filament\Resources\LoanFundLoanPayments\Pages\ListLoanFundLoanPayments;
use App\Filament\Resources\LoanFundLoanPayments\Schemas\LoanFundLoanPaymentForm;
use App\Filament\Resources\LoanFundLoanPayments\Tables\LoanFundLoanPaymentsTable;
use App\Models\LoanFundLoanPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoanFundLoanPaymentResource extends Resource
{
    protected static ?string $model = LoanFundLoanPayment::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    public static function getModelLabel(): string
    {
        return __('funds.loan_fund_loan_payments.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.loan_fund_loan_payments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.loan_fund_loan_payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanFundLoanPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanFundLoanPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoanFundLoanPayments::route('/'),
            'create' => CreateLoanFundLoanPayment::route('/create'),
            'edit'   => EditLoanFundLoanPayment::route('/{record}/edit'),
        ];
    }
}
