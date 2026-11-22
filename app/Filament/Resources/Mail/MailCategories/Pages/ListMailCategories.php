<?php

namespace App\Filament\Resources\Mail\MailCategories\Pages;

use App\Filament\Resources\Mail\MailCategories\MailCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailCategories extends ListRecords
{
    protected static string $resource = MailCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
