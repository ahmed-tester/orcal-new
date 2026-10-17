<?php

namespace App\Filament\Resources\LoanFundMembers;

use App\Filament\Resources\LoanFundMembers\Pages\CreateLoanFundMember;
use App\Filament\Resources\LoanFundMembers\Pages\EditLoanFundMember;
use App\Filament\Resources\LoanFundMembers\Pages\ListLoanFundMembers;
use App\Filament\Resources\LoanFundMembers\Schemas\LoanFundMemberForm;
use App\Filament\Resources\LoanFundMembers\Tables\LoanFundMembersTable;
use App\Models\LoanFundMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoanFundMemberResource extends Resource
{
    protected static ?string $model = LoanFundMember::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('funds.loan_fund_members.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.loan_fund_members.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.loan_fund_members');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanFundMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanFundMembersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoanFundMembers::route('/'),
            'create' => CreateLoanFundMember::route('/create'),
            'edit'   => EditLoanFundMember::route('/{record}/edit'),
        ];
    }
}
