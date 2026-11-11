<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialDisbursementStatus: string implements HasColor, HasLabel
{
    case PAID = 'paid';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case PARTIALLY_REIMBURSED = 'partially_reimbursed';
    case FULLY_REIMBURSED = 'fully_reimbursed';

    public function getLabel(): string
    {
        return __('financial.disbursement_statuses.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PAID, self::FULLY_REIMBURSED => 'success',
            self::PENDING_APPROVAL => 'warning',
            self::APPROVED => 'info',
            self::PARTIALLY_REIMBURSED => 'primary',
        };
    }

    public function isOpenClaim(): bool
    {
        return in_array($this, [self::PENDING_APPROVAL, self::APPROVED, self::PARTIALLY_REIMBURSED], true);
    }
}
