<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Filament\Resources\Branches\Tables\BranchesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Branch::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    public static function getModelLabel(): string
    {
        return __('organization.branches.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('organization.branches.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.branches');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.organization_group');
    }

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
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
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
