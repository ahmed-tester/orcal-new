<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetFundStatus: string implements HasLabel, HasColor, HasIcon
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('asset_funds.enums.status.active'),
            self::CLOSED => __('asset_funds.enums.status.closed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::CLOSED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-m-check-circle',
            self::CLOSED => 'heroicon-m-lock-closed',
        };
    }
}

