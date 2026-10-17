<?php

namespace App\Services\Reports\Types;

use App\Models\Leave;

class LeavesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'leaves';
    }

    public function getTitle(): string
    {
        return __('reports.reports.leaves.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.leaves.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    public function getExportFilename(): string
    {
        return 'leaves_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $stats = Leave::query()
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.leave_requests'),
                'value' => $stats->total ?? 0,
                'subtitle' => __('reports.stats.leave_requests_sub'),
                'icon' => 'heroicon-o-document-plus',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.approved_leaves'),
                'value' => $stats->approved ?? 0,
                'subtitle' => __('reports.stats.approved_leaves_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.pending_leaves'),
                'value' => $stats->pending ?? 0,
                'subtitle' => __('reports.stats.pending_leaves_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Leave::query()
            ->with(['employee.user', 'leaveType']);

        // Authorization scoping — Leave → employee → company/branch
        $this->applyCompanyScopeViaRelation($query, 'employee');
        $this->applyBranchScopeViaRelation($query, $branchId);

        $this->applyDateFilter($query, $dateFrom, $dateTo, 'start_date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->whereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'days' => 'days',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.request_id')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'leave_type', 'label' => __('reports.columns.leave_type')],
            ['key' => 'start_date', 'label' => __('reports.columns.from_date')],
            ['key' => 'end_date', 'label' => __('reports.columns.to_date')],
            ['key' => 'days', 'label' => __('reports.columns.days_count')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'reason', 'label' => __('reports.columns.reason')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'id' => $item->id,
            'employee_name' => $item->employee?->user?->name ?? '—',
            'leave_type' => $item->leaveType?->name ?? '—',
            'start_date' => $item->start_date ? ($datePrefix . $item->start_date->format('Y-m-d')) : '—',
            'end_date' => $item->end_date ? ($datePrefix . $item->end_date->format('Y-m-d')) : '—',
            'days' => $item->days ?? 0,
            'status' => $this->translateStatus($item->status ?? 'pending'),
            'reason' => $item->reason ?? '—',
        ];
    }
}
