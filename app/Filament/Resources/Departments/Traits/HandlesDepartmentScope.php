<?php

namespace App\Filament\Resources\Departments\Traits;

use App\Services\DepartmentScopeService;

trait HandlesDepartmentScope
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateDepartmentScopeData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mutateDepartmentScopeData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncDepartmentScope();
    }

    protected function afterSave(): void
    {
        $this->syncDepartmentScope();
    }

    protected function mutateDepartmentScopeData(array $data): array
    {
        $scope = $this->form->getRawState()['department_scope'] ?? null;
        $record = property_exists($this, 'record') ? $this->record : null;

        return DepartmentScopeService::mutateBeforeSave($data, $scope, $record);
    }

    protected function syncDepartmentScope(): void
    {
        $scope = $this->form->getRawState()['department_scope'] ?? null;

        DepartmentScopeService::afterSave($this->record, $scope);
    }
}

