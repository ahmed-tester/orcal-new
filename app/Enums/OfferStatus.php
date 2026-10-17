<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OfferStatus: string implements HasLabel, HasColor, HasIcon
{
    case DRAFT      = 'draft';
    case SENT       = 'sent';
    case ACCEPTED   = 'accepted';
    case REJECTED   = 'rejected';
    case CANCELLED  = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT     => __('offers.statuses.draft'),
            self::SENT      => __('offers.statuses.sent'),
            self::ACCEPTED  => __('offers.statuses.accepted'),
            self::REJECTED  => __('offers.statuses.rejected'),
            self::CANCELLED => __('offers.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::SENT      => 'info',
            self::ACCEPTED  => 'success',
            self::REJECTED  => 'danger',
            self::CANCELLED => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT     => 'heroicon-m-pencil-square',
            self::SENT      => 'heroicon-m-paper-airplane',
            self::ACCEPTED  => 'heroicon-m-check-circle',
            self::REJECTED  => 'heroicon-m-x-circle',
            self::CANCELLED => 'heroicon-m-archive-box-x-mark',
        };
    }
}
