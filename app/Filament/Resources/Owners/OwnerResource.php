<?php

namespace App\Filament\Resources\Owners;

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Filament\Resources\Owners\Schemas\OwnerForm;
use App\Filament\Resources\Owners\Tables\OwnersTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Owner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OwnerResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Owner::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return __('owners.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('owners.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.owners');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.organization_group');
    }

    public static function form(Schema $schema): Schema
    {
        return OwnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OwnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOwners::route('/'),
            'create' => CreateOwner::route('/create'),
            'edit' => EditOwner::route('/{record}/edit'),
        ];
    }
}
