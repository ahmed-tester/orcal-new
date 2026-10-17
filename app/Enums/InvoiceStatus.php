<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel, HasColor, HasIcon
{
    case UNPAID         = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID           = 'paid';
    case CANCELLED      = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID         => __('invoices.statuses.unpaid'),
            self::PARTIALLY_PAID => __('invoices.statuses.partially_paid'),
            self::PAID           => __('invoices.statuses.paid'),
            self::CANCELLED      => __('invoices.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UNPAID         => 'danger',
            self::PARTIALLY_PAID => 'warning',
            self::PAID           => 'success',
            self::CANCELLED      => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::UNPAID         => 'heroicon-m-clock',
            self::PARTIALLY_PAID => 'heroicon-m-chart-pie',
            self::PAID           => 'heroicon-m-check-badge',
            self::CANCELLED      => 'heroicon-m-x-circle',
        };
    }
}
