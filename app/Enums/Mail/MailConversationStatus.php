<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailConversationStatus: string implements HasColor, HasLabel
{
    case NEW = 'new';
    case FOLLOWING_UP = 'following_up';
    case REPLIED = 'replied';
    case UNDER_REVIEW = 'under_review';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return __("mail_inbox.conversation_statuses.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'info',
            self::FOLLOWING_UP => 'warning',
            self::REPLIED => 'success',
            self::UNDER_REVIEW => 'purple',
            self::CLOSED => 'gray',
        };
    }
}
