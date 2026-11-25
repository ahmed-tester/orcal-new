<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserType;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin.admin');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.admins');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.admins');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($q) => $q->where('name', '!=', 'Super Admin'))
                    ->orWhere('user_type', UserType::ADMIN);
            })
            ->whereNotIn('user_type', [
                UserType::SUPER_ADMIN,
                UserType::OWNER,
            ])
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->doesntHave('owner')
            ->doesntHave('owners');
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
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
