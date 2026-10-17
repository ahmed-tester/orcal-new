<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasLabel, HasColor
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';
    case ESTABLISHMENT = 'establishment';
    case GOVERNMENT = 'government';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => __('customers.types.individual'),
            self::COMPANY => __('customers.types.company'),
            self::ESTABLISHMENT => __('customers.types.establishment'),
            self::GOVERNMENT => __('customers.types.government'),
            self::OTHER => __('customers.types.other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INDIVIDUAL => 'info',
            self::COMPANY => 'primary',
            self::ESTABLISHMENT => 'warning',
            self::GOVERNMENT => 'success',
            self::OTHER => 'gray',
        };
    }

    public function isIndividual(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    public function isCompany(): bool
    {
        return $this === self::COMPANY;
    }

    public function isEstablishment(): bool
    {
        return $this === self::ESTABLISHMENT;
    }

    public function isGovernment(): bool
    {
        return $this === self::GOVERNMENT;
    }
}
