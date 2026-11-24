<?php

namespace App\Filament\Resources\ViolationCategories\Pages;

use App\Filament\Resources\ViolationCategories\ViolationCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateViolationCategory extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ViolationCategoryResource::class;
}
