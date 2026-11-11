<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialHandoverStatus: string implements HasColor, HasLabel
{
    case SUBMITTED = 'submitted';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';

    public function getLabel(): string
    {
        return __('financial.handover_statuses.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SUBMITTED => 'warning',
            self::CONFIRMED => 'success',
            self::REJECTED, self::REVERSED => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
