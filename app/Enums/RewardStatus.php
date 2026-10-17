<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RewardStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case PAID     = 'paid';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return __('finance.employee_rewards.statuses.' . $this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING   => 'warning',
            self::APPROVED  => 'info',
            self::PAID      => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING   => 'heroicon-m-clock',
            self::APPROVED  => 'heroicon-m-check-circle',
            self::PAID      => 'heroicon-m-banknotes',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
