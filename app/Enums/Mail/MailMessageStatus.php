<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MailMessageStatus: string implements HasColor, HasIcon, HasLabel
{
    case NEW = 'new';
    case FOLLOWING_UP = 'following_up';
    case REPLIED = 'replied';
    case UNDER_REVIEW = 'under_review';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return __("mail_inbox.message_statuses.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'info',
            self::FOLLOWING_UP => 'warning',
            self::REPLIED => 'success',
            self::UNDER_REVIEW => 'purple',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW => 'heroicon-m-envelope',
            self::FOLLOWING_UP => 'heroicon-m-clock',
            self::REPLIED => 'heroicon-m-check-circle',
            self::UNDER_REVIEW => 'heroicon-m-eye',
            self::REJECTED => 'heroicon-m-no-symbol',
        };
    }
}
