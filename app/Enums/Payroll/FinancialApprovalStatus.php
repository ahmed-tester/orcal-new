<?php

namespace App\Enums\Payroll;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialApprovalStatus: string implements HasColor, HasLabel
{
    case PENDING_TREASURY = 'pending_treasury';
    case PENDING_MANAGER = 'pending_manager';
    case EXECUTED = 'executed';
    case REJECTED = 'rejected';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return __('financial.approval_statuses.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING_TREASURY => 'warning',
            self::PENDING_MANAGER => 'info',
            self::EXECUTED => 'success',
            self::REJECTED => 'danger',
            self::RETURNED => 'gray',
            self::CANCELLED => 'gray',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::PENDING_TREASURY, self::PENDING_MANAGER], true);
    }
}
