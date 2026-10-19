<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
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
