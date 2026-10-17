<?php

namespace App\Filament\Resources\Leaves;

use App\Filament\Resources\Leaves\Pages\CreateLeave;
use App\Filament\Resources\Leaves\Pages\EditLeave;
use App\Filament\Resources\Leaves\Pages\ListLeaves;
use App\Filament\Resources\Leaves\Schemas\LeaveForm;
use App\Filament\Resources\Leaves\Tables\LeavesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Leave;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LeaveResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Leave::class;

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getModelLabel(): string
    {
        return __('leaves.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leaves.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.leaves');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $query = Leave::where('status', 'pending');

        if ($user && !$user->isSuperAdmin()) {
            if ($user->isOwner()) {
                $query->whereHas('employee', fn ($q) => $q->whereIn('company_id', $user->getOwnedCompanyIds()));
            } elseif ($user->branch_id) {
                $query->whereHas('employee', fn ($q) => $q->where('branch_id', $user->branch_id));
            }
        }

        $pending = $query->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeavesTable::configure($table);
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
            'index' => ListLeaves::route('/'),
            'create' => CreateLeave::route('/create'),
            'edit' => EditLeave::route('/{record}/edit'),
        ];
    }
}
