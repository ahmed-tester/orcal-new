<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RoscaAllocationMethod: string implements HasLabel, HasColor, HasIcon
{
    case LOTTERY     = 'lottery';
    case CONSENSUS   = 'consensus';
    case PREARRANGED = 'prearranged';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOTTERY     => __('funds.enums.allocation_method.lottery'),
            self::CONSENSUS   => __('funds.enums.allocation_method.consensus'),
            self::PREARRANGED => __('funds.enums.allocation_method.prearranged'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LOTTERY     => 'warning',
            self::CONSENSUS   => 'success',
            self::PREARRANGED => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::LOTTERY     => 'heroicon-m-sparkles',
            self::CONSENSUS   => 'heroicon-m-user-group',
            self::PREARRANGED => 'heroicon-m-calendar-days',
        };
    }
}
