<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialReceiptStatus: string implements HasColor, HasLabel
{
    case PENDING_CONFIRMATION = 'pending_confirmation';
    case CONFIRMED = 'confirmed';
    case REVERSED = 'reversed';

    public function getLabel(): string
    {
        return __('financial.receipt_statuses.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING_CONFIRMATION => 'warning',
            self::CONFIRMED => 'success',
            self::REVERSED => 'danger',
        };
    }
}
