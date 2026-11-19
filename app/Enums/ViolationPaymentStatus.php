<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ViolationPaymentStatus: string implements HasLabel, HasColor, HasIcon
{
    case UNPAID   = 'unpaid';
    case PENDING  = 'pending';
    case PARTIAL  = 'partial';
    case PAID     = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID  => __('violations.payment_statuses.unpaid'),
            self::PENDING => __('violations.payment_statuses.pending'),
            self::PARTIAL => __('violations.payment_statuses.partial'),
            self::PAID    => __('violations.payment_statuses.paid'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UNPAID  => 'danger',
            self::PENDING => 'warning',
            self::PARTIAL => 'info',
            self::PAID    => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::UNPAID  => 'heroicon-o-x-circle',
            self::PENDING => 'heroicon-o-clock',
            self::PARTIAL => 'heroicon-o-arrows-right-left',
            self::PAID    => 'heroicon-o-check-badge',
        };
    }
}
