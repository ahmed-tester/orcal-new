<?php

namespace App\Filament\Resources\OfficialLetters\Pages;

use App\Filament\Resources\OfficialLetters\OfficialLetterResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateOfficialLetter extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = OfficialLetterResource::class;
}
