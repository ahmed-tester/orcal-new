<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketCategory: string implements HasLabel, HasColor, HasIcon
{
    case TECHNICAL = 'technical';
    case HR = 'hr';
    case FINANCE = 'finance';
    case GENERAL = 'general';
    case COMPLAINT = 'complaint';
    case SUGGESTION = 'suggestion';

    public function getLabel(): string
    {
        return match ($this) {
            self::TECHNICAL => __('tickets.categories.technical'),
            self::HR => __('tickets.categories.hr'),
            self::FINANCE => __('tickets.categories.finance'),
            self::GENERAL => __('tickets.categories.general'),
            self::COMPLAINT => __('tickets.categories.complaint'),
            self::SUGGESTION => __('tickets.categories.suggestion'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TECHNICAL => 'primary',
            self::HR => 'info',
            self::FINANCE => 'success',
            self::GENERAL => 'gray',
            self::COMPLAINT => 'danger',
            self::SUGGESTION => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::TECHNICAL => 'heroicon-m-computer-desktop',
            self::HR => 'heroicon-m-user-group',
            self::FINANCE => 'heroicon-m-banknotes',
            self::GENERAL => 'heroicon-m-question-mark-circle',
            self::COMPLAINT => 'heroicon-m-exclamation-triangle',
            self::SUGGESTION => 'heroicon-m-light-bulb',
        };
    }
}
