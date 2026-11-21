<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailAccountStatus: string implements HasColor, HasLabel
{
    case CONNECTED = 'connected';
    case CONNECTION_ERROR = 'connection_error';
    case REAUTH_REQUIRED = 'reauth_required';

    public function getLabel(): string
    {
        return __("mail_inbox.account_statuses.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CONNECTED => 'success',
            self::CONNECTION_ERROR => 'danger',
            self::REAUTH_REQUIRED => 'warning',
        };
    }
}
