<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WorkScope: string implements HasLabel, HasColor, HasIcon
{
    case MATERIALS_AND_LABOR        = 'materials_and_labor';
    case LABOR_WITH_SOME_MATERIALS  = 'labor_with_some_materials';
    case LABOR_ONLY                 = 'labor_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::MATERIALS_AND_LABOR       => __('offers.scopes.materials_and_labor'),
            self::LABOR_WITH_SOME_MATERIALS => __('offers.scopes.labor_with_some_materials'),
            self::LABOR_ONLY                => __('offers.scopes.labor_only'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MATERIALS_AND_LABOR       => 'success',
            self::LABOR_WITH_SOME_MATERIALS => 'info',
            self::LABOR_ONLY                => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::MATERIALS_AND_LABOR       => 'heroicon-m-cube',
            self::LABOR_WITH_SOME_MATERIALS => 'heroicon-m-wrench-screwdriver',
            self::LABOR_ONLY                => 'heroicon-m-user-group',
        };
    }
}
