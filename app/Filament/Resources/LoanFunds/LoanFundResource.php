<?php

namespace App\Filament\Resources\LoanFunds;

use App\Filament\Resources\LoanFunds\Pages\CreateLoanFund;
use App\Filament\Resources\LoanFunds\Pages\EditLoanFund;
use App\Filament\Resources\LoanFunds\Pages\ListLoanFunds;
use App\Filament\Resources\LoanFunds\Schemas\LoanFundForm;
use App\Filament\Resources\LoanFunds\Tables\LoanFundsTable;
// use App\Filament\Traits\HasCompanyScope;
use App\Models\LoanFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoanFundResource extends Resource
{
    // use HasCompanyScope;

    protected static ?string $model = LoanFund::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getModelLabel(): string
    {
        return __('funds.loan_funds.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.loan_funds.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.loan_funds');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanFundsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoanFunds::route('/'),
            'create' => CreateLoanFund::route('/create'),
            'edit'   => EditLoanFund::route('/{record}/edit'),
        ];
    }
}
