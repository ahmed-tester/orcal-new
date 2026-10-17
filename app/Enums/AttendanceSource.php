<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendanceSource: string implements HasLabel, HasColor, HasIcon
{
    case FINGERPRINT = 'fingerprint';
    case MANUAL      = 'manual';
    case SYSTEM      = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::FINGERPRINT => __('attendance.sources.fingerprint'),
            self::MANUAL      => __('attendance.sources.manual'),
            self::SYSTEM      => __('attendance.sources.system'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FINGERPRINT => 'success',
            self::MANUAL      => 'warning',
            self::SYSTEM      => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FINGERPRINT => 'heroicon-m-finger-print',
            self::MANUAL      => 'heroicon-m-pencil-square',
            self::SYSTEM      => 'heroicon-m-cpu-chip',
        };
    }
}
