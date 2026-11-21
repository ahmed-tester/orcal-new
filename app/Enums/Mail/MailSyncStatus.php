<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailSyncStatus: string implements HasColor, HasLabel
{
    case SUCCESS = 'success';
    case PARTIAL = 'partial';
    case FAILED = 'failed';
    case RUNNING = 'running';

    public function getLabel(): string
    {
        return __("mail_inbox.sync_statuses.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::PARTIAL, self::RUNNING => 'warning',
            self::FAILED => 'danger',
        };
    }
}
