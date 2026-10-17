<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $permissions = array_filter(array_merge(
            $this->data['permissions_admin'] ?? [],
            $this->data['permissions_roles'] ?? [],
            $this->data['permissions_org'] ?? [],
            $this->data['permissions_employees'] ?? [],
            $this->data['permissions_attendance'] ?? [],
            $this->data['permissions_leaves'] ?? [],
            $this->data['permissions_crm'] ?? [],
            $this->data['permissions_billing'] ?? [],
            $this->data['permissions_tasks'] ?? [],
            $this->data['permissions_settings'] ?? [],
            $this->data['permissions_finance'] ?? [],
            $this->data['permissions_installments'] ?? [],
            $this->data['permissions_faq'] ?? [],
            $this->data['permissions_tickets'] ?? [],
            $this->data['permissions_violations'] ?? [],
            $this->data['permissions_funds'] ?? [],
            $this->data['permissions_reports_and_tools'] ?? [],
        ));

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        $this->record->syncPermissions($permissions);
    }
}
