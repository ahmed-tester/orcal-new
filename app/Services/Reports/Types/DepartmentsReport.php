<?php

namespace App\Services\Reports\Types;

use App\Models\Department;

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
            ->with(['company', 'parent'])
            ->withCount('employees');

        $this->applyCompanyScope($query);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('department_code', 'like', "%{$search}%");
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
            ['key' => 'parent_name', 'label' => __('reports.columns.parent_department')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'employees_count', 'label' => __('reports.columns.employees_count')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'department_code' => $item->department_code ?? '—',
            'name' => $item->name,
            'parent_name' => $item->parent?->name ?? '—',
            'company_name' => $item->company?->trade_name ?? $item->company?->name ?? '—',
            'employees_count' => $item->employees_count ?? 0,
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
