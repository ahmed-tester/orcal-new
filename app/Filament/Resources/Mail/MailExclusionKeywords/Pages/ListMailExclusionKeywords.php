<?php

namespace App\Filament\Resources\Mail\MailExclusionKeywords\Pages;

use App\Filament\Resources\Mail\MailExclusionKeywords\MailExclusionKeywordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailExclusionKeywords extends ListRecords
{
    protected static string $resource = MailExclusionKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
