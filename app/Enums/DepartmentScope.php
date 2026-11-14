<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DepartmentScope: string implements HasLabel, HasColor, HasIcon
{
    case SINGLE = 'single';
    case SHARED = 'shared';
    case FAWAZ_GROUP = 'fawaz_group';

    public static function isSingle(mixed $value): bool
    {
        return $value === self::SINGLE || $value === self::SINGLE->value;
    }

    public static function isShared(mixed $value): bool
    {
        return $value === self::SHARED || $value === self::SHARED->value;
    }

    public static function isFawazGroup(mixed $value): bool
    {
        return $value === self::FAWAZ_GROUP || $value === self::FAWAZ_GROUP->value;
    }

    public static function toOptionsArray(): array
    {
        return [
            self::SINGLE->value => __('organization.departments.scope.single'),
            self::SHARED->value => __('organization.departments.scope.shared'),
            self::FAWAZ_GROUP->value => __('organization.departments.scope.fawaz_group'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SINGLE => __('organization.departments.scope.single'),
            self::SHARED => __('organization.departments.scope.shared'),
            self::FAWAZ_GROUP => __('organization.departments.scope.fawaz_group'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SINGLE => 'gray',
            self::SHARED => 'warning',
            self::FAWAZ_GROUP => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SINGLE => 'heroicon-m-building-office',
            self::SHARED => 'heroicon-m-building-office-2',
            self::FAWAZ_GROUP => 'heroicon-m-globe-alt',
        };
    }
}
