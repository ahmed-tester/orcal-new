<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FundGeneralStatus: string implements HasLabel, HasColor, HasIcon
{
    case ACTIVE    = 'active';
    case COMPLETED = 'completed';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE    => __('funds.enums.general_status.active'),
            self::COMPLETED => __('funds.enums.general_status.completed'),
            self::SUSPENDED => __('funds.enums.general_status.suspended'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE    => 'success',
            self::COMPLETED => 'info',
            self::SUSPENDED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE    => 'heroicon-m-check-circle',
            self::COMPLETED => 'heroicon-m-shield-check',
            self::SUSPENDED => 'heroicon-m-pause-circle',
        };
    }
}
