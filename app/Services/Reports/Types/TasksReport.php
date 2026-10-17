<?php

namespace App\Services\Reports\Types;

use App\Models\Task;

class TasksReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'tasks';
    }

    public function getTitle(): string
    {
        return __('reports.reports.tasks.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.tasks.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-check-badge';
    }

    public function getExportFilename(): string
    {
        return 'tasks_catalog_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = Task::query();
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $total = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_tasks_catalog'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_tasks_catalog_sub'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.active_tasks'),
                'value' => $active,
                'subtitle' => __('reports.stats.active_tasks_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Task::query()
            ->withCount('assignmentItems');

        $this->applyDateFilter($query, $dateFrom, $dateTo);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('task_code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'task_code' => 'task_code',
            'name' => 'name',
            'default_weight' => 'default_weight',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'task_code', 'label' => __('reports.columns.classification_code')],
            ['key' => 'name', 'label' => __('reports.columns.subject')],
            ['key' => 'default_weight', 'label' => __('reports.columns.ownership_percentage')],
            ['key' => 'assignments_count', 'label' => __('reports.columns.employees_count')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'description', 'label' => __('reports.columns.description')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'task_code' => $item->task_code ?? '—',
            'name' => $item->name ?? '—',
            'default_weight' => number_format((float)$item->default_weight, 2) . '%',
            'assignments_count' => (string)($item->assignment_items_count ?? 0),
            'status' => $this->translateStatus($item->status),
            'description' => $item->description ?? '—',
        ];
    }
}
