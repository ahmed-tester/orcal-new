<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails\Pages;

use App\Filament\Resources\Mail\MailTrustedEmails\MailTrustedEmailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMailTrustedEmail extends EditRecord
{
    protected static string $resource = MailTrustedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
