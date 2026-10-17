<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MaritalStatus: string implements HasLabel, HasColor
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public function getLabel(): string
    {
        return match ($this) {
            self::SINGLE => __('employees.marital_statuses.single') ?? 'أعزب / عزباء',
            self::MARRIED => __('employees.marital_statuses.married') ?? 'متزوج / متزوجة',
            self::DIVORCED => __('employees.marital_statuses.divorced') ?? 'مطلق / مطلقة',
            self::WIDOWED => __('employees.marital_statuses.widowed') ?? 'أرمل / أرملة',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SINGLE => 'info',
            self::MARRIED => 'success',
            self::DIVORCED => 'warning',
            self::WIDOWED => 'gray',
        };
    }
}
