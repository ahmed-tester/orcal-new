<?php

namespace App\Filament\Resources\Mail\MailTrustedDomains\Pages;

use App\Filament\Resources\Mail\MailTrustedDomains\MailTrustedDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailTrustedDomains extends ListRecords
{
    protected static string $resource = MailTrustedDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
