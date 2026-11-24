<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails\Pages;

use App\Filament\Resources\Mail\MailTrustedEmails\MailTrustedEmailResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateMailTrustedEmail extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailTrustedEmailResource::class;
}
