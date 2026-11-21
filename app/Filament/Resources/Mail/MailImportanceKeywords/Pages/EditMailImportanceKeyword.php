<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Pages;

use App\Filament\Resources\Mail\MailImportanceKeywords\MailImportanceKeywordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMailImportanceKeyword extends EditRecord
{
    protected static string $resource = MailImportanceKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
