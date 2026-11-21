<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords;

use App\Filament\Resources\Mail\MailImportanceKeywords\Pages\CreateMailImportanceKeyword;
use App\Filament\Resources\Mail\MailImportanceKeywords\Pages\EditMailImportanceKeyword;
use App\Filament\Resources\Mail\MailImportanceKeywords\Pages\ListMailImportanceKeywords;
use App\Filament\Resources\Mail\MailImportanceKeywords\Schemas\MailImportanceKeywordForm;
use App\Filament\Resources\Mail\MailImportanceKeywords\Tables\MailImportanceKeywordsTable;
use App\Models\Mail\MailImportanceKeyword;
use App\Services\CompanyFormService;
use App\Services\Mail\MailAccessService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailImportanceKeywordResource extends Resource
{
    protected static ?string $model = MailImportanceKeyword::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bookmark';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.importance_keyword.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.importance_keyword.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.importance_keywords');
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
        return MailImportanceKeywordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailImportanceKeywordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailImportanceKeywords::route('/'),
            'create' => CreateMailImportanceKeyword::route('/create'),
            'edit' => EditMailImportanceKeyword::route('/{record}/edit'),
        ];
    }
}
