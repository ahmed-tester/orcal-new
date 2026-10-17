<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Resources\Services\Tables\ServicesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use HasCompanyScope;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isOwner()) {
            $ownedCompanyIds = $user->getOwnedCompanyIds();
            return $query->where(function ($q) use ($ownedCompanyIds) {
                $q->whereIn('services.company_id', !empty($ownedCompanyIds) ? $ownedCompanyIds : [0])
                  ->orWhereNull('services.company_id');
            });
        }

        if ($user->company_id) {
            return $query->where(function ($q) use ($user) {
                $q->where('services.company_id', $user->company_id)
                  ->orWhereNull('services.company_id');
            });
        }

        return $query;
    }

    protected static ?string $model = Service::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getModelLabel(): string
    {
        return __('services.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('services.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.services');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
