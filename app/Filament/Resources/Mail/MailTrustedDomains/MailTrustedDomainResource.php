<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains;

use App\Filament\Resources\Mail\MailTrustedDomains\Pages\CreateMailTrustedDomain;
use App\Filament\Resources\Mail\MailTrustedDomains\Pages\EditMailTrustedDomain;
use App\Filament\Resources\Mail\MailTrustedDomains\Pages\ListMailTrustedDomains;
use App\Filament\Resources\Mail\MailTrustedDomains\Schemas\MailTrustedDomainForm;
use App\Filament\Resources\Mail\MailTrustedDomains\Tables\MailTrustedDomainsTable;
use App\Models\Mail\MailTrustedDomain;
use App\Services\CompanyFormService;
use App\Services\Mail\MailAccessService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailTrustedDomainResource extends Resource
{
    protected static ?string $model = MailTrustedDomain::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.trusted_domain.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.trusted_domain.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.trusted_domains');
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
        return MailTrustedDomainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailTrustedDomainsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailTrustedDomains::route('/'),
            'create' => CreateMailTrustedDomain::route('/create'),
            'edit' => EditMailTrustedDomain::route('/{record}/edit'),
        ];
    }
}
