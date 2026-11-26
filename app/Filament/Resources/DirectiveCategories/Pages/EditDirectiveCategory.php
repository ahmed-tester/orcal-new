<?php

namespace App\Filament\Resources\DirectiveCategories\Pages;

use App\Filament\Resources\DirectiveCategories\DirectiveCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditDirectiveCategory extends EditRecord
{
    protected static string $resource = DirectiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => Gate::allows('delete', $this->getRecord())),
        ];
    }
}
