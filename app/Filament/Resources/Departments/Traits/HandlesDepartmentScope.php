<?php

namespace App\Filament\Resources\Departments\Traits;

use App\Enums\DepartmentScope;
use App\Services\DepartmentHierarchyService;
use Illuminate\Database\Eloquent\Model;

trait HandlesDepartmentScope
{
    protected function handleRecordCreation(array $data): Model
    {
        return $this->saveDepartment($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->record = $this->saveDepartment($data, $record);
    }

    protected function saveDepartment(array $data, ?Model $record = null): Model
    {
        $scope = DepartmentScope::from($data['department_scope']);
        $companies = (array) ($data['companies'] ?? []);
        unset($data['department_scope'], $data['companies']);

        return DepartmentHierarchyService::save($data, $record, $scope, $companies, 'data.');
    }
}
