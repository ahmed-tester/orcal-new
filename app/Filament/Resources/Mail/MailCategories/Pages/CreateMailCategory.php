<?php

namespace App\Filament\Resources\Mail\MailCategories\Pages;

use App\Filament\Resources\Mail\MailCategories\MailCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateMailCategory extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailCategoryResource::class;
}
