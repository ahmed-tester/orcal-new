<?php

namespace App\Filament\Resources\Mail\MailAccounts;

use App\Filament\Resources\Mail\MailAccounts\Pages\CreateMailAccount;
use App\Filament\Resources\Mail\MailAccounts\Pages\EditMailAccount;
use App\Filament\Resources\Mail\MailAccounts\Pages\ListMailAccounts;
use App\Filament\Resources\Mail\MailAccounts\Schemas\MailAccountForm;
use App\Filament\Resources\Mail\MailAccounts\Tables\MailAccountsTable;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MailAccessService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailAccountResource extends Resource
{
    protected static ?string $model = MailAccount::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-at-symbol';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.account.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.account.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.accounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getEloquentQuery(): Builder
    {
        return MailAccessService::scopeAccounts(parent::getEloquentQuery(), auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return MailAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailAccounts::route('/'),
            'create' => CreateMailAccount::route('/create'),
            'edit' => EditMailAccount::route('/{record}/edit'),
        ];
    }
}
