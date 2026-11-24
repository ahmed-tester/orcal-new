<?php

namespace App\Filament\Resources\Mail\MailCategories\Pages;

use App\Filament\Resources\Mail\MailCategories\MailCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;
use App\Filament\Concerns\RedirectsToIndex;

class EditMailCategory extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = MailCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => Gate::allows('delete', $this->record)),
        ];
    }
}
