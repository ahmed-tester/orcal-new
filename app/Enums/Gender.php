<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel, HasColor, HasIcon
{
    case MALE = 'male';
    case FEMALE = 'female';

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => __('employees.genders.male') ?? 'ذكر',
            self::FEMALE => __('employees.genders.female') ?? 'أنثى',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MALE => 'info',
            self::FEMALE => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::MALE => 'heroicon-m-user',
            self::FEMALE => 'heroicon-m-user',
        };
    }
}
