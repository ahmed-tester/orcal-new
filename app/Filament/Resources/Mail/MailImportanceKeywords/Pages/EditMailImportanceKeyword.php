<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Pages;

use App\Filament\Resources\Mail\MailImportanceKeywords\MailImportanceKeywordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditMailImportanceKeyword extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailImportanceKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
