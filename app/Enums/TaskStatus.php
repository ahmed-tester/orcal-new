<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case POSTPONED = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('tasks.statuses.pending'),
            self::IN_PROGRESS => __('tasks.statuses.in_progress'),
            self::COMPLETED => __('tasks.statuses.completed'),
            self::POSTPONED => __('tasks.statuses.postponed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'danger',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::POSTPONED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::COMPLETED => 'heroicon-m-check-circle',
            self::POSTPONED => 'heroicon-m-pause-circle',
        };
    }
}
