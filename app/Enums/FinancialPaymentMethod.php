<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialPaymentMethod: string implements HasColor, HasLabel
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case POS = 'pos';

    public function getLabel(): string
    {
        return __('financial.payment_methods.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CASH => 'success',
            self::BANK_TRANSFER => 'info',
            self::POS => 'warning',
        };
    }

    public function isCash(): bool
    {
        return $this === self::CASH;
    }
}
