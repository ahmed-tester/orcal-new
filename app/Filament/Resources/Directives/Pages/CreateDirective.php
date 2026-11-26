<?php

namespace App\Filament\Resources\Directives\Pages;

use App\Filament\Resources\Directives\DirectiveResource;
use App\Services\DirectiveService;
use Filament\Resources\Pages\CreateRecord;

class CreateDirective extends CreateRecord
{
    protected static string $resource = DirectiveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $selectedIds = $this->data['selected_employee_ids'] ?? [];
        DirectiveService::syncRecipients($this->getRecord(), is_array($selectedIds) ? $selectedIds : []);
    }
}
