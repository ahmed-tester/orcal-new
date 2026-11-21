<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains\Pages;

use App\Filament\Resources\Mail\MailTrustedDomains\MailTrustedDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMailTrustedDomain extends EditRecord
{
    protected static string $resource = MailTrustedDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
