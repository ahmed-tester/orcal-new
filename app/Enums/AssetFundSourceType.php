<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetFundSourceType: string implements HasLabel, HasColor, HasIcon
{
    case COMPANY = 'company';
    case FREE    = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY => __('asset_funds.enums.source_type.company'),
            self::FREE    => __('asset_funds.enums.source_type.free'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::COMPANY => 'primary',
            self::FREE    => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::COMPANY => 'heroicon-m-building-office',
            self::FREE    => 'heroicon-m-pencil-square',
        };
    }
}

