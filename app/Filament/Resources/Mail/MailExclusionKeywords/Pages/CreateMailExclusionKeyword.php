<?php

namespace App\Filament\Resources\Mail\MailExclusionKeywords\Pages;

use App\Filament\Resources\Mail\MailExclusionKeywords\MailExclusionKeywordResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateMailExclusionKeyword extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailExclusionKeywordResource::class;
}
