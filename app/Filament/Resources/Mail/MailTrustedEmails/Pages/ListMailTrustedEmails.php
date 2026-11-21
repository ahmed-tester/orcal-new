<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails\Pages;

use App\Filament\Resources\Mail\MailTrustedEmails\MailTrustedEmailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailTrustedEmails extends ListRecords
{
    protected static string $resource = MailTrustedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
