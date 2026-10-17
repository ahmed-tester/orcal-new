<?php

namespace App\Filament\Resources\LoanFundLoans;

use App\Filament\Resources\LoanFundLoans\Pages\CreateLoanFundLoan;
use App\Filament\Resources\LoanFundLoans\Pages\EditLoanFundLoan;
use App\Filament\Resources\LoanFundLoans\Pages\ListLoanFundLoans;
use App\Filament\Resources\LoanFundLoans\Schemas\LoanFundLoanForm;
use App\Filament\Resources\LoanFundLoans\Tables\LoanFundLoansTable;
use App\Models\LoanFundLoan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoanFundLoanResource extends Resource
{
    protected static ?string $model = LoanFundLoan::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function getModelLabel(): string
    {
        return __('funds.loan_fund_loans.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.loan_fund_loans.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.loan_fund_loans');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanFundLoanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanFundLoansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoanFundLoans::route('/'),
            'create' => CreateLoanFundLoan::route('/create'),
            'edit'   => EditLoanFundLoan::route('/{record}/edit'),
        ];
    }
}
