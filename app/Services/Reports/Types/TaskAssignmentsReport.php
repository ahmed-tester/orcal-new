<?php

namespace App\Services\Reports\Types;

use App\Models\TaskAssignment;

class TaskAssignmentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'task_assignments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.task_assignments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.task_assignments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public function getExportFilename(): string
    {
        return 'task_assignments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = TaskAssignment::query();
        $this->applyCompanyScopeViaRelation($query, 'employee');
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('assignment_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('assignment_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(AVG(overall_percentage), 0) as avg_percentage
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_task_assignments'),
                'value' => $stats->total_count ?? 0,
                'subtitle' => __('reports.stats.total_task_assignments_sub'),
                'icon' => 'heroicon-o-clipboard-document-list',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.completed_task_assignments'),
                'value' => $stats->completed_count ?? 0,
                'subtitle' => __('reports.stats.completed_task_assignments_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.avg_task_percentage'),
                'value' => number_format((float) ($stats->avg_percentage ?? 0), 1) . ' %',
                'subtitle' => __('reports.stats.avg_task_percentage_sub'),
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'indigo',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = TaskAssignment::query()
            ->with(['employee.user', 'employee.branch', 'creator']);

        $this->applyCompanyScopeViaRelation($query, 'employee');
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'assignment_date');

        if ($status === 'active' || $status === 'completed' || $status === 'approved') {
            $query->where('is_completed', true);
        } elseif ($status === 'inactive' || $status === 'pending') {
            $query->where('is_completed', false);
        }

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('transaction_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'transaction_number' => 'transaction_number',
            'assignment_date' => 'assignment_date',
            'from_date' => 'from_date',
            'to_date' => 'to_date',
            'overall_percentage' => 'overall_percentage',
            'is_completed' => 'is_completed',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'transaction_number', 'label' => __('reports.columns.transaction_number')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'branch_name', 'label' => __('reports.columns.branch')],
            ['key' => 'assignment_date', 'label' => __('reports.columns.date')],
            ['key' => 'from_date', 'label' => __('reports.columns.from_date')],
            ['key' => 'to_date', 'label' => __('reports.columns.to_date')],
            ['key' => 'total_score', 'label' => __('reports.columns.total_score')],
            ['key' => 'overall_percentage', 'label' => __('reports.columns.overall_percentage')],
            ['key' => 'is_completed', 'label' => __('reports.columns.is_completed')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'transaction_number' => $item->transaction_number ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'branch_name' => $item->employee?->branch?->name ?? '—',
            'assignment_date' => $item->assignment_date ? ($datePrefix . $item->assignment_date->format('Y-m-d')) : '—',
            'from_date' => $item->from_date ? ($datePrefix . $item->from_date->format('Y-m-d')) : '—',
            'to_date' => $item->to_date ? ($datePrefix . $item->to_date->format('Y-m-d')) : '—',
            'total_score' => ($item->total_score ?? 0) . ' / ' . ($item->total_weight ?? 0),
            'overall_percentage' => number_format((float) ($item->overall_percentage ?? 0), 2) . ' %',
            'is_completed' => $item->is_completed ? __('reports.values.yes') : __('reports.values.no'),
        ];
    }
}
