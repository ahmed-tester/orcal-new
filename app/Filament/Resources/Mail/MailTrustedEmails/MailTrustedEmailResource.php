<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails;

use App\Filament\Resources\Mail\MailTrustedEmails\Pages\CreateMailTrustedEmail;
use App\Filament\Resources\Mail\MailTrustedEmails\Pages\EditMailTrustedEmail;
use App\Filament\Resources\Mail\MailTrustedEmails\Pages\ListMailTrustedEmails;
use App\Filament\Resources\Mail\MailTrustedEmails\Schemas\MailTrustedEmailForm;
use App\Filament\Resources\Mail\MailTrustedEmails\Tables\MailTrustedEmailsTable;
use App\Models\Mail\MailTrustedEmail;
use App\Services\CompanyFormService;
use App\Services\Mail\MailAccessService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailTrustedEmailResource extends Resource
{
    protected static ?string $model = MailTrustedEmail::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.trusted_email.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.trusted_email.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.trusted_emails');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.mail_rules_group');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if (MailAccessService::canAdministerAllMail($user)) {
            return $query;
        }

        $companyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));

        return $query->where(function (Builder $q) use ($companyIds) {
            $q->whereNull('company_id')->orWhereIn('company_id', $companyIds ?: [0]);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return MailTrustedEmailForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailTrustedEmailsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailTrustedEmails::route('/'),
            'create' => CreateMailTrustedEmail::route('/create'),
            'edit' => EditMailTrustedEmail::route('/{record}/edit'),
        ];
    }
}
