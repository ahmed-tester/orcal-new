<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentHierarchyService;

class DepartmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->can('view_any_department');
    }

    public function view(User $user, Department $model): bool
    {
        return $user->isOwner() || $user->can('view_any_department');
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->can('create_department');
    }

    public function update(User $user, Department $model): bool
    {
        return ($user->isOwner() || $user->can('update_department'))
            && DepartmentHierarchyService::levelOf($model) >= DepartmentHierarchyService::MAX_DEPTH;
    }

    public function delete(User $user, Department $model): bool
    {
        return ($user->isOwner() || $user->can('delete_department'))
            && DepartmentHierarchyService::levelOf($model) >= DepartmentHierarchyService::MAX_DEPTH;
    }
}
