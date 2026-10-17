<?php

namespace App\Services\Reports\Types;

use App\Models\Employee;

class EmployeesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'employees';
    }

    public function getTitle(): string
    {
        return __('reports.reports.employees.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.employees.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-identification';
    }

    public function getExportFilename(): string
    {
        return 'employees_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Employee::query()
            ->with(['user', 'branch', 'department', 'jobTitle']);

        // Authorization scoping
        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);

        $this->applyDateFilter($query, $dateFrom, $dateTo, 'hire_date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('employee_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'employee_number' => 'employee_number',
            'status' => 'status',
            'hire_date' => 'hire_date',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'employee_number', 'label' => __('reports.columns.employee_number')],
            ['key' => 'name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'branch', 'label' => __('reports.columns.branch')],
            ['key' => 'department', 'label' => __('reports.columns.department')],
            ['key' => 'job_title', 'label' => __('reports.columns.job_title')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'hire_date', 'label' => __('reports.columns.hire_date')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'employee_number' => $item->employee_number ?? '—',
            'name' => $item->user?->name ?? '—',
            'branch' => $item->branch?->name ?? '—',
            'department' => $item->department?->name ?? '—',
            'job_title' => $item->jobTitle?->name ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
            'hire_date' => $item->hire_date ? ($datePrefix . $item->hire_date->format('Y-m-d')) : '—',
        ];
    }
}
