<?php

namespace App\Filament\Resources\PerformanceEscalations;

use App\Filament\Resources\PerformanceEscalations\Pages\ListPerformanceEscalations;
use App\Filament\Resources\PerformanceEscalations\Pages\ViewPerformanceEscalation;
use App\Filament\Resources\PerformanceEscalations\Schemas\PerformanceEscalationInfolist;
use App\Filament\Resources\PerformanceEscalations\Tables\PerformanceEscalationsTable;
use App\Models\PerformanceEscalation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PerformanceEscalationResource extends Resource
{
    protected static ?string $model = PerformanceEscalation::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getModelLabel(): string
    {
        return __('performance_escalations.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('performance_escalations.model_plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('performance_escalations.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $count = PerformanceEscalation::query()->open()->visibleTo($user)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user, fn (Builder $query) => $query->visibleTo($user), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->with(['company', 'subject']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerformanceEscalationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceEscalationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerformanceEscalations::route('/'),
            'view' => ViewPerformanceEscalation::route('/{record}'),
        ];
    }
}
