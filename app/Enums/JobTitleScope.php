<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum JobTitleScope: string implements HasLabel, HasColor, HasIcon
{
    case COMPANY = 'company';
    case GLOBAL  = 'global';

    public static function isGlobalValue(mixed $value): bool
    {
        return $value === self::GLOBAL || $value === self::GLOBAL->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY => __('organization.job_titles.scope.company'),
            self::GLOBAL  => __('organization.job_titles.scope.global'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::COMPANY => 'info',
            self::GLOBAL  => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::COMPANY => 'heroicon-m-building-office',
            self::GLOBAL  => 'heroicon-m-globe-alt',
        };
    }
}

