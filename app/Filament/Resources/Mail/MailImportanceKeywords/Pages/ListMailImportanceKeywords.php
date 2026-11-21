<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Pages;

use App\Filament\Resources\Mail\MailImportanceKeywords\MailImportanceKeywordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailImportanceKeywords extends ListRecords
{
    protected static string $resource = MailImportanceKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
