<?php

namespace App\Filament\Resources\LoanFundMemberPayments;

use App\Filament\Resources\LoanFundMemberPayments\Pages\CreateLoanFundMemberPayment;
use App\Filament\Resources\LoanFundMemberPayments\Pages\EditLoanFundMemberPayment;
use App\Filament\Resources\LoanFundMemberPayments\Pages\ListLoanFundMemberPayments;
use App\Filament\Resources\LoanFundMemberPayments\Schemas\LoanFundMemberPaymentForm;
use App\Filament\Resources\LoanFundMemberPayments\Tables\LoanFundMemberPaymentsTable;
use App\Models\LoanFundMemberPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoanFundMemberPaymentResource extends Resource
{
    protected static ?string $model = LoanFundMemberPayment::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    public static function getModelLabel(): string
    {
        return __('funds.loan_fund_member_payments.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.loan_fund_member_payments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('funds.loan_fund_member_payments.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanFundMemberPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanFundMemberPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoanFundMemberPayments::route('/'),
            'create' => CreateLoanFundMemberPayment::route('/create'),
            'edit'   => EditLoanFundMemberPayment::route('/{record}/edit'),
        ];
    }
}
