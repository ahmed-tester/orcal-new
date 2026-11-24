<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Pages;

use App\Filament\Resources\Mail\MailImportanceKeywords\MailImportanceKeywordResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateMailImportanceKeyword extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailImportanceKeywordResource::class;
}
