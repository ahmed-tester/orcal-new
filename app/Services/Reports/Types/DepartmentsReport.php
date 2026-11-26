<?php

namespace App\Services\Reports\Types;

use App\Enums\DepartmentScope;
use App\Models\Department;
use App\Services\Departments\DepartmentHierarchyService;
use App\Services\Departments\DepartmentScopeService;

class DepartmentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'departments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.departments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.departments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public function getExportFilename(): string
    {
        return 'departments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = Department::query();
        $this->applyCompanyScope($query);

        $totalDepartments = (clone $query)->count();
        $activeDepartments = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_departments'),
                'value' => $totalDepartments,
                'subtitle' => __('reports.stats.total_departments_sub'),
                'icon' => 'heroicon-o-folder',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.active_departments'),
                'value' => $activeDepartments,
                'subtitle' => __('reports.stats.active_departments_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Department::query()
            ->with(['company', 'parent', 'companies', 'manager.user'])
            ->withCount('employees');

        $this->applyCompanyScope($query);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('department_code', 'like', "%{$search}%")
                ->orWhereHas('manager.user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'department_code' => 'department_code',
            'name' => 'name',
            'employees_count' => 'employees_count',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'department_code', 'label' => __('reports.columns.department_code')],
            ['key' => 'name', 'label' => __('reports.columns.department_name')],
            ['key' => 'manager_name', 'label' => __('organization.departments.fields.manager')],
            ['key' => 'parent_name', 'label' => __('reports.columns.parent_department')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'employees_count', 'label' => __('reports.columns.employees_count')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $scope = DepartmentScopeService::resolveScope($item);
        $companyName = match ($scope) {
            DepartmentScope::FAWAZ_GROUP => DepartmentHierarchyService::levelOf($item) === 1
                ? __('organization.departments.scope.group_umbrella')
                : __('organization.departments.scope.general_main'),
            DepartmentScope::SHARED => $item->companies->isNotEmpty()
                ? $item->companies->pluck('trade_name')->join(__('organization.departments.fields.companies_separator'))
                : __('organization.departments.fields.global_badge'),
            DepartmentScope::SINGLE => $item->company?->trade_name ?? $item->company?->name ?? '—',
        };

        return [
            'department_code' => $item->department_code ?? '—',
            'name' => $item->name,
            'manager_name' => $item->manager?->user?->name ?? '—',
            'parent_name' => $item->parent?->name ?? '—',
            'company_name' => $companyName,
            'employees_count' => $item->employees_count ?? 0,
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
