<?php

namespace App\Filament\Resources\RoscaFundMembers;

use App\Filament\Resources\RoscaFundMembers\Pages\CreateRoscaFundMember;
use App\Filament\Resources\RoscaFundMembers\Pages\EditRoscaFundMember;
use App\Filament\Resources\RoscaFundMembers\Pages\ListRoscaFundMembers;
use App\Filament\Resources\RoscaFundMembers\Schemas\RoscaFundMemberForm;
use App\Filament\Resources\RoscaFundMembers\Tables\RoscaFundMembersTable;
use App\Models\RoscaFundMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RoscaFundMemberResource extends Resource
{
    protected static ?string $model = RoscaFundMember::class;

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    public static function getModelLabel(): string
    {
        return __('funds.rosca_fund_members.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('funds.rosca_fund_members.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.rosca_fund_members');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function form(Schema $schema): Schema
    {
        return RoscaFundMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoscaFundMembersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoscaFundMembers::route('/'),
            'create' => CreateRoscaFundMember::route('/create'),
            'edit'   => EditRoscaFundMember::route('/{record}/edit'),
        ];
    }
}
