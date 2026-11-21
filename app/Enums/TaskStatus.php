<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case PENDING_REVIEW = 'pending_review';
    case RETURNED_FOR_REVISION = 'returned_for_revision';
    case COMPLETED = 'completed';
    case TRANSFERRED = 'transferred';
    case ESCALATED = 'escalated';
    case POSTPONED = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('tasks.statuses.pending'),
            self::IN_PROGRESS => __('tasks.statuses.in_progress'),
            self::PENDING_REVIEW => __('tasks.statuses.pending_review'),
            self::RETURNED_FOR_REVISION => __('tasks.statuses.returned_for_revision'),
            self::COMPLETED => __('tasks.statuses.completed'),
            self::TRANSFERRED => __('tasks.statuses.transferred'),
            self::ESCALATED => __('tasks.statuses.escalated'),
            self::POSTPONED => __('tasks.statuses.postponed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'danger',
            self::IN_PROGRESS => 'warning',
            self::PENDING_REVIEW => 'info',
            self::RETURNED_FOR_REVISION => 'warning',
            self::COMPLETED => 'success',
            self::TRANSFERRED => 'info',
            self::ESCALATED => 'danger',
            self::POSTPONED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::PENDING_REVIEW => 'heroicon-m-eye',
            self::RETURNED_FOR_REVISION => 'heroicon-m-arrow-uturn-left',
            self::COMPLETED => 'heroicon-m-check-circle',
            self::TRANSFERRED => 'heroicon-m-arrow-right-circle',
            self::ESCALATED => 'heroicon-m-arrow-trending-up',
            self::POSTPONED => 'heroicon-m-pause-circle',
        };
    }

    /**
     * Statuses where the item is open with the employee and can become overdue.
     *
     * @return array<int, string>
     */
    public static function employeeOpenValues(): array
    {
        return [
            self::PENDING->value,
            self::IN_PROGRESS->value,
            self::RETURNED_FOR_REVISION->value,
        ];
    }

    public function isEmployeeOpen(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::IN_PROGRESS,
            self::RETURNED_FOR_REVISION,
        ], true);
    }
}
