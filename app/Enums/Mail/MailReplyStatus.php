<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailReplyStatus: string implements HasColor, HasLabel
{
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return __("mail_inbox.reply_statuses.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SENT => 'success',
            self::FAILED => 'danger',
        };
    }
}
