<?php

namespace App\Filament\Resources\Directives\Pages;

use App\Filament\Resources\Directives\DirectiveResource;
use App\Services\DirectiveService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditDirective extends EditRecord
{
    protected static string $resource = DirectiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label(__('directives.actions.view_recipients')),
            DeleteAction::make()
                ->visible(fn (): bool => Gate::allows('delete', $this->getRecord())),
        ];
    }

    protected function afterSave(): void
    {
        $selectedIds = $this->data['selected_employee_ids'] ?? [];
        DirectiveService::syncRecipients($this->getRecord(), is_array($selectedIds) ? $selectedIds : []);
    }
}
