<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails\Pages;

use App\Filament\Resources\Mail\MailTrustedEmails\MailTrustedEmailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditMailTrustedEmail extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailTrustedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
