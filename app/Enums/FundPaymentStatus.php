<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FundPaymentStatus: string implements HasLabel, HasColor, HasIcon
{
    case PAID    = 'paid';
    case PARTIAL = 'partial';
    case UNPAID  = 'unpaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID    => __('funds.enums.payment_status.paid'),
            self::PARTIAL => __('funds.enums.payment_status.partial'),
            self::UNPAID  => __('funds.enums.payment_status.unpaid'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PAID    => 'success',
            self::PARTIAL => 'warning',
            self::UNPAID  => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PAID    => 'heroicon-m-check-badge',
            self::PARTIAL => 'heroicon-m-clock',
            self::UNPAID  => 'heroicon-m-x-circle',
        };
    }
}
