<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PosDeviceStatus: string implements HasLabel, HasColor, HasIcon
{
    case ACTIVE               = 'active';
    case TEMPORARILY_DISABLED = 'temporarily_disabled';
    case INACTIVE             = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE               => __('finance.pos_devices.statuses.active'),
            self::TEMPORARILY_DISABLED => __('finance.pos_devices.statuses.temporarily_disabled'),
            self::INACTIVE             => __('finance.pos_devices.statuses.inactive'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE               => 'success',
            self::TEMPORARILY_DISABLED => 'warning',
            self::INACTIVE             => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE               => 'heroicon-m-check-circle',
            self::TEMPORARILY_DISABLED => 'heroicon-m-pause-circle',
            self::INACTIVE             => 'heroicon-m-x-circle',
        };
    }
}
