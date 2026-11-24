<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains\Pages;

use App\Filament\Resources\Mail\MailTrustedDomains\MailTrustedDomainResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateMailTrustedDomain extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailTrustedDomainResource::class;
}
