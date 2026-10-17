<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InstallmentStatus: string implements HasLabel, HasColor, HasIcon
{
    case OPEN      = 'open';
    case COMPLETED = 'completed';
    case OVERDUE   = 'overdue';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN      => 'نشط / ساري',
            self::COMPLETED => 'مكتمل المسدد',
            self::OVERDUE   => 'متأخر الاستحقاق',
            self::CANCELLED => 'ملغي',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN      => 'info',
            self::COMPLETED => 'success',
            self::OVERDUE   => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN      => 'heroicon-m-clock',
            self::COMPLETED => 'heroicon-m-check-badge',
            self::OVERDUE   => 'heroicon-m-exclamation-triangle',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
