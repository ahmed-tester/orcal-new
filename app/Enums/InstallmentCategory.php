<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InstallmentCategory: string implements HasLabel, HasColor, HasIcon
{
    case ADVANCES = 'advances';
    case DEBTS    = 'debts';
    case OTHERS   = 'others';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADVANCES => 'أقساط سلف',
            self::DEBTS    => 'أقساط ديون',
            self::OTHERS   => 'أقساط أخرى',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADVANCES => 'info',
            self::DEBTS    => 'danger',
            self::OTHERS   => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ADVANCES => 'heroicon-m-hand-raised',
            self::DEBTS    => 'heroicon-m-credit-card',
            self::OTHERS   => 'heroicon-m-tag',
        };
    }
}
