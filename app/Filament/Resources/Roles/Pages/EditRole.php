<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('admin.delete')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissionNames = $this->record->permissions->pluck('name')->toArray();

        foreach (RoleForm::getPermissionGroups() as $field => $group) {
            $validPerms = array_keys($group['permissions']);
            $data[$field] = array_values(array_intersect($permissionNames, $validPerms));
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $permissions = [];
        foreach (array_keys(RoleForm::getPermissionGroups()) as $groupField) {
            if (!empty($this->data[$groupField])) {
                $permissions = array_merge($permissions, (array) $this->data[$groupField]);
            }
        }
        $permissions = array_filter(array_unique($permissions));

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        $this->record->syncPermissions($permissions);
    }
}
