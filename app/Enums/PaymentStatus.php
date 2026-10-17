<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel, HasColor
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => __('invoices.statuses.unpaid'),
            self::PARTIALLY_PAID => __('invoices.statuses.partially_paid'),
            self::PAID => __('invoices.statuses.paid'),
            self::CANCELLED => __('invoices.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UNPAID => 'danger',
            self::PARTIALLY_PAID => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'gray',
        };
    }
}
