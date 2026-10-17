<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasLabel, HasColor, HasIcon
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_USER_REPLY = 'waiting_user_reply';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => __('tickets.statuses.open'),
            self::IN_PROGRESS => __('tickets.statuses.in_progress'),
            self::WAITING_USER_REPLY => __('tickets.statuses.waiting_user_reply'),
            self::RESOLVED => __('tickets.statuses.resolved'),
            self::CLOSED => __('tickets.statuses.closed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'info',
            self::IN_PROGRESS => 'warning',
            self::WAITING_USER_REPLY => 'purple',
            self::RESOLVED => 'success',
            self::CLOSED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN => 'heroicon-m-inbox',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::WAITING_USER_REPLY => 'heroicon-m-clock',
            self::RESOLVED => 'heroicon-m-check-badge',
            self::CLOSED => 'heroicon-m-lock-closed',
        };
    }
}
