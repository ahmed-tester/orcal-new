<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasLabel, HasColor, HasIcon
{
    case PRESENT = 'present';
    case LATE = 'late';
    case ABSENT = 'absent';
    case EXCUSED = 'excused';
    case LEAVE = 'leave';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => __('attendance.statuses.present'),
            self::LATE => __('attendance.statuses.late'),
            self::ABSENT => __('attendance.statuses.absent'),
            self::EXCUSED => __('attendance.statuses.excused'),
            self::LEAVE => __('attendance.statuses.leave'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::LATE => 'warning',
            self::ABSENT => 'danger',
            self::EXCUSED => 'info',
            self::LEAVE => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PRESENT => 'heroicon-m-check-circle',
            self::LATE => 'heroicon-m-clock',
            self::ABSENT => 'heroicon-m-x-circle',
            self::EXCUSED => 'heroicon-m-information-circle',
            self::LEAVE => 'heroicon-m-calendar-days',
        };
    }
}
