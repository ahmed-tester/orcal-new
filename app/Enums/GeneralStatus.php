<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum GeneralStatus: string implements HasLabel, HasColor, HasIcon
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('admin.active'),
            self::INACTIVE => __('admin.inactive'),
            self::SUSPENDED => __('admin.suspended') ?? 'موقوف',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
            self::SUSPENDED => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-m-check-circle',
            self::INACTIVE => 'heroicon-m-x-circle',
            self::SUSPENDED => 'heroicon-m-pause-circle',
        };
    }
}
