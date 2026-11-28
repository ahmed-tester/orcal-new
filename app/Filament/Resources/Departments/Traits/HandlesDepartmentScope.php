<?php

namespace App\Filament\Resources\Departments\Traits;

use App\Enums\DepartmentScope;
use App\Services\Departments\DepartmentHierarchyService;
use App\Services\Tasks\Renewals\DocumentRenewalAccessService;
use Filament\Notifications\Notification;
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
        $oldManagerId = $record?->manager_id ? (int) $record->manager_id : null;
        $selectedRenewalPermissions = array_key_exists('renewal_permissions', $data)
            ? (array) $data['renewal_permissions']
            : null;

        $scope = DepartmentScope::from($data['department_scope']);
        $companies = (array) ($data['companies'] ?? []);
        unset($data['department_scope'], $data['companies'], $data['renewal_permissions']);

        $department = DepartmentHierarchyService::save($data, $record, $scope, $companies, 'data.');

        if ($department->parent_id === null && (auth()->user()?->isSuperAdmin() ?? false)) {
            $newManagerId = $department->manager_id ? (int) $department->manager_id : null;
            $syncResult = DocumentRenewalAccessService::syncLayerOneManagerPermissions(
                department: $department,
                oldManagerId: $oldManagerId,
                newManagerId: $newManagerId,
                selectedPermissions: $selectedRenewalPermissions,
                actorUserId: auth()->id(),
            );

            if ($syncResult['kept_for_old_other_admin']) {
                Notification::make()
                    ->title(__('document_renewals.layer_one.kept_other_admin_title'))
                    ->body(__('document_renewals.layer_one.kept_other_admin_body'))
                    ->warning()
                    ->send();
            }

            if ($syncResult['old_role_inherited'] !== [] || $syncResult['new_role_inherited'] !== []) {
                Notification::make()
                    ->title(__('document_renewals.layer_one.role_inherited_warning_title'))
                    ->body(__('document_renewals.layer_one.role_inherited_warning_body'))
                    ->warning()
                    ->send();
            }
        }

        return $department;
    }
}
