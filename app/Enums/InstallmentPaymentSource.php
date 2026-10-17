<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InstallmentPaymentSource: string implements HasLabel, HasColor, HasIcon
{
    case CASH              = 'cash';
    case PAYROLL_DEDUCTION = 'payroll_deduction';
    case BANK              = 'bank';

    public function getLabel(): string
    {
        return match ($this) {
            self::CASH              => __('finance.installment_payments.sources.cash'),
            self::PAYROLL_DEDUCTION => __('finance.installment_payments.sources.payroll_deduction'),
            self::BANK              => __('finance.installment_payments.sources.bank'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CASH              => 'success',
            self::PAYROLL_DEDUCTION => 'warning',
            self::BANK              => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CASH              => 'heroicon-m-banknotes',
            self::PAYROLL_DEDUCTION => 'heroicon-m-calculator',
            self::BANK              => 'heroicon-m-building-library',
        };
    }
}
