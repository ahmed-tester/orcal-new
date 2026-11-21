<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasLabel;

enum MailSourceFolder: string implements HasLabel
{
    case INBOX = 'inbox';
    case SPAM_JUNK = 'spam_junk';

    public function getLabel(): string
    {
        return __("mail_inbox.source_folders.{$this->value}");
    }
}
