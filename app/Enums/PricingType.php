<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PricingType: string implements HasLabel, HasColor, HasIcon
{
    case LUMP_SUM   = 'lump_sum';
    case PER_METER  = 'per_meter';
    case PER_UNIT   = 'per_unit';

    public function getLabel(): string
    {
        return match ($this) {
            self::LUMP_SUM  => __('offers.pricing_types.lump_sum'),
            self::PER_METER => __('offers.pricing_types.per_meter'),
            self::PER_UNIT  => __('offers.pricing_types.per_unit'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LUMP_SUM  => 'primary',
            self::PER_METER => 'info',
            self::PER_UNIT  => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::LUMP_SUM  => 'heroicon-m-banknotes',
            self::PER_METER => 'heroicon-m-variable',
            self::PER_UNIT  => 'heroicon-m-tag',
        };
    }
}
