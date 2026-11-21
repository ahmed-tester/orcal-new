<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialDisbursementType: string implements HasColor, HasLabel
{
    case TREASURY_DIRECT = 'treasury_direct';
    case EMPLOYEE_PERSONAL = 'employee_personal';

    public function getLabel(): string
    {
        return __('financial.disbursement_types.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TREASURY_DIRECT => 'primary',
            self::EMPLOYEE_PERSONAL => 'warning',
        };
    }
}
