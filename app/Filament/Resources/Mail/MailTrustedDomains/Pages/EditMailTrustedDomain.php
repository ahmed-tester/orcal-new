<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains\Pages;

use App\Filament\Resources\Mail\MailTrustedDomains\MailTrustedDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditMailTrustedDomain extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailTrustedDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
