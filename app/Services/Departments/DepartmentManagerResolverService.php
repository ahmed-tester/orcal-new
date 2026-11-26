<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

class DepartmentManagerResolverService
{
    /**
     * Resolve the Layer 1 administration and its active Saudi manager for a department.
     *
     * @return array{
     *     ok: bool,
     *     error: ?string,
     *     root_department: ?Department,
     *     manager_employee: ?Employee,
     *     manager_user: ?User,
     *     path_ids: array<int, int>
     * }
     */
    public static function resolveForDepartment(Department $department): array
    {
        $current = $department;
        $seen = [];
        $pathIds = [];

        while ($current !== null) {
            $id = (int) $current->id;
            if (isset($seen[$id])) {
                return static::failure('department_cycle_detected', $pathIds);
            }
            $seen[$id] = true;
            $pathIds[] = $id;

            if (count($pathIds) > DepartmentHierarchyService::MAX_DEPTH) {
                return static::failure('department_depth_exceeded', $pathIds);
            }

            if ($current->parent_id === null) {
                break;
            }

            $parent = Department::query()->with('companies')->find($current->parent_id);
            if (! $parent) {
                return static::failure('department_parent_missing', $pathIds);
            }
            $current = $parent;
        }

        if ($current === null) {
            return static::failure('department_missing', $pathIds);
        }

        $root = $current->loadMissing('companies');
        if (! $root->is_global || $root->company_id !== null || $root->companies->isNotEmpty()) {
            return static::failure('root_not_group_administration', $pathIds);
        }

        if (! $root->manager_id) {
            return static::failure('root_manager_missing', $pathIds);
        }

        $managerEmployee = Employee::query()
            ->with('user')
            ->whereKey($root->manager_id)
            ->where('status', 'active')
            ->saudi()
            ->first();

        if (! $managerEmployee) {
            return static::failure('root_manager_invalid', $pathIds);
        }

        $managerUser = $managerEmployee->user;
        if (! $managerUser || ! static::isActiveStatus($managerUser->status)) {
            return static::failure('root_manager_user_inactive', $pathIds);
        }

        return [
            'ok' => true,
            'error' => null,
            'root_department' => $root,
            'manager_employee' => $managerEmployee,
            'manager_user' => $managerUser,
            'path_ids' => $pathIds,
        ];
    }

    /**
     * Resolve the Layer 1 manager for an active user's employee department.
     *
     * @return array{
     *     ok: bool,
     *     error: ?string,
     *     employee: ?Employee,
     *     department: ?Department,
     *     root_department: ?Department,
     *     manager_employee: ?Employee,
     *     manager_user: ?User,
     *     path_ids: array<int, int>
     * }
     */
    public static function isActiveStatus(mixed $status): bool
    {
        $value = $status instanceof \BackedEnum ? (string) $status->value : (string) ($status ?? 'active');

        return $value === 'active';
    }

    public static function resolveForUser(User $user): array
    {
        if (! static::isActiveStatus($user->status)) {
            return ['ok' => false, 'error' => 'user_inactive', 'employee' => null, 'department' => null, 'root_department' => null, 'manager_employee' => null, 'manager_user' => null, 'path_ids' => []];
        }

        $employee = $user->employee()->with('department')->first();
        if (! $employee || ! static::isActiveStatus($employee->status)) {
            return ['ok' => false, 'error' => 'employee_missing_or_inactive', 'employee' => $employee, 'department' => null, 'root_department' => null, 'manager_employee' => null, 'manager_user' => null, 'path_ids' => []];
        }

        $department = $employee->department;
        if (! $department) {
            return ['ok' => false, 'error' => 'employee_department_missing', 'employee' => $employee, 'department' => null, 'root_department' => null, 'manager_employee' => null, 'manager_user' => null, 'path_ids' => []];
        }

        $resolved = static::resolveForDepartment($department);

        return array_merge($resolved, [
            'employee' => $employee,
            'department' => $department,
        ]);
    }

    /**
     * @param  array<int, int>  $pathIds
     * @return array{ok: bool, error: string, root_department: null, manager_employee: null, manager_user: null, path_ids: array<int, int>}
     */
    protected static function failure(string $error, array $pathIds): array
    {
        return [
            'ok' => false,
            'error' => $error,
            'root_department' => null,
            'manager_employee' => null,
            'manager_user' => null,
            'path_ids' => $pathIds,
        ];
    }
}
