<?php

namespace App\Filament\Resources\ViolationCategories\Pages;

use App\Filament\Resources\ViolationCategories\ViolationCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditViolationCategory extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ViolationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
