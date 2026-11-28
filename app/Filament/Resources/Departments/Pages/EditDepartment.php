<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\Actions\DepartmentDeleteAction;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Traits\HandlesDepartmentScope;
use App\Services\Tasks\Renewals\DocumentRenewalAccessService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDepartment extends EditRecord
{
    use HandlesDepartmentScope;

    protected static string $resource = DepartmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($record->parent_id === null && (auth()->user()?->isSuperAdmin() ?? false)) {
            $managerUser = $record->manager?->user;
            $data['renewal_permissions'] = $managerUser
                ? DocumentRenewalAccessService::directRenewalPermissions($managerUser)
                : [];
        }

        return $data;
    }

    public function getTitle(): string|Htmlable
    {
        $parentId = data_get($this->data, 'parent_id', $this->getRecord()->parent_id);

        return DepartmentForm::label('edit', filled($parentId) ? (int) $parentId : null);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return DepartmentForm::label('saved', $this->getRecord()->parent_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            DepartmentDeleteAction::makeSingle(),
        ];
    }
}
