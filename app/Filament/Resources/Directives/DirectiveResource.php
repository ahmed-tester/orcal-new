<?php

namespace App\Filament\Resources\Directives;

use App\Filament\Resources\Directives\Pages\CreateDirective;
use App\Filament\Resources\Directives\Pages\EditDirective;
use App\Filament\Resources\Directives\Pages\ListDirectives;
use App\Filament\Resources\Directives\Pages\ViewDirective;
use App\Filament\Resources\Directives\RelationManagers\RecipientsRelationManager;
use App\Filament\Resources\Directives\Schemas\DirectiveForm;
use App\Filament\Resources\Directives\Tables\DirectivesTable;
use App\Models\Directive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DirectiveResource extends Resource
{
    protected static ?string $model = Directive::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    public static function getModelLabel(): string
    {
        return __('directives.models.directive.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('directives.models.directive.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.directives');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['category', 'creator:id,name'])
            ->withCount(['recipients', 'acknowledgedRecipients']);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isOwner()) {
            $ownedCompanyIds = $user->getOwnedCompanyIds();

            return $query->where(function (Builder $q) use ($user, $ownedCompanyIds): void {
                $q->where('created_by', $user->id);

                if ($ownedCompanyIds !== []) {
                    $q->orWhereHas('recipients.employee', fn (Builder $emp) => $emp->whereIn('company_id', $ownedCompanyIds));
                }
            });
        }

        return $query->where('created_by', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        return DirectiveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectivesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectives::route('/'),
            'create' => CreateDirective::route('/create'),
            'view' => ViewDirective::route('/{record}'),
            'edit' => EditDirective::route('/{record}/edit'),
        ];
    }
}
