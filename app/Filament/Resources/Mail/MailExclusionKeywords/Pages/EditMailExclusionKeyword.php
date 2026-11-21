<?php

namespace App\Filament\Resources\Mail\MailExclusionKeywords\Pages;

use App\Filament\Resources\Mail\MailExclusionKeywords\MailExclusionKeywordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMailExclusionKeyword extends EditRecord
{
    protected static string $resource = MailExclusionKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
