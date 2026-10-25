<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ApprovalStatus: string implements HasColor, HasIcon, HasLabel
{
    case APPROVED = 'approved';
    case UNAPPROVED = 'unapproved';

    public function getLabel(): string
    {
        return match ($this) {
            self::APPROVED => __('finance.approval_statuses.approved'),
            self::UNAPPROVED => __('finance.approval_statuses.unapproved'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::UNAPPROVED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::APPROVED => 'heroicon-m-check-circle',
            self::UNAPPROVED => 'heroicon-m-clock',
        };
    }
}
