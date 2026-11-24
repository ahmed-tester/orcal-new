<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateFaq extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = FaqResource::class;
}
