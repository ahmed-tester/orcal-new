<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeductionBasis: string implements HasLabel
{
    case BASIC = 'basic';
    case BASIC_ALLOWANCES = 'basic_allowances';
    case BASIC_BONUSES = 'basic_bonuses';
    case TOTAL = 'total';

    public function getLabel(): string
    {
        return match ($this) {
            self::BASIC => __('employees.salary_profile.deduction_basis_options.basic'),
            self::BASIC_ALLOWANCES => __('employees.salary_profile.deduction_basis_options.basic_allowances'),
            self::BASIC_BONUSES => __('employees.salary_profile.deduction_basis_options.basic_bonuses'),
            self::TOTAL => __('employees.salary_profile.deduction_basis_options.total'),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
