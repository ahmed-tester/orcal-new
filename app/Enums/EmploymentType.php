<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel, HasColor
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case TEMPORARY = 'temporary';
    case INTERNSHIP = 'internship';

    public function getLabel(): string
    {
        return match ($this) {
            self::FULL_TIME => __('employees.employment_types.full_time'),
            self::PART_TIME => __('employees.employment_types.part_time'),
            self::CONTRACT => __('employees.employment_types.contract'),
            self::TEMPORARY => __('employees.employment_types.temporary'),
            self::INTERNSHIP => __('employees.employment_types.internship'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FULL_TIME => 'success',
            self::PART_TIME => 'info',
            self::CONTRACT => 'warning',
            self::TEMPORARY => 'gray',
            self::INTERNSHIP => 'primary',
        };
    }
}
