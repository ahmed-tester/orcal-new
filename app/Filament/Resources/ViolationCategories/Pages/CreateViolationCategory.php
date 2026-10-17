<?php

namespace App\Filament\Resources\ViolationCategories\Pages;

use App\Filament\Resources\ViolationCategories\ViolationCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolationCategory extends CreateRecord
{
    protected static string $resource = ViolationCategoryResource::class;
}
