<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WeeklyNoteStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case POSTPONED = 'postponed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('weekly_notes.statuses.pending'),
            self::IN_PROGRESS => __('weekly_notes.statuses.in_progress'),
            self::COMPLETED => __('weekly_notes.statuses.completed'),
            self::POSTPONED => __('weekly_notes.statuses.postponed'),
            self::CANCELLED => __('weekly_notes.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'danger',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::POSTPONED => 'gray',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::COMPLETED => 'heroicon-m-check-badge',
            self::POSTPONED => 'heroicon-m-pause-circle',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
