<?php

namespace App\Services\Reports\Types;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'attendance';
    }

    public function getTitle(): string
    {
        return __('reports.reports.attendance.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.attendance.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public function getExportFilename(): string
    {
        return 'attendance_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $stats = Attendance::query()
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN late_hours > 0 THEN 1 ELSE 0 END) as late_count,
                COALESCE(SUM(CASE WHEN overtime_hours > 0 THEN overtime_hours ELSE 0 END), 0) as total_overtime
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.attendance_records'),
                'value' => $stats->total ?? 0,
                'subtitle' => __('reports.stats.attendance_records_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'indigo',
            ],
            [
                'title' => __('reports.stats.late'),
                'value' => $stats->late_count ?? 0,
                'subtitle' => __('reports.stats.late_sub'),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'amber',
            ],
            [
                'title' => __('reports.stats.overtime'),
                'value' => number_format((float) ($stats->total_overtime ?? 0), 2) . __('reports.values.hours'),
                'subtitle' => __('reports.stats.overtime_sub'),
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'blue',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Attendance::query()
            ->with(['employee.user', 'employee.branch']);

        // Authorization scoping — Attendance → employee → company/branch
        $this->applyCompanyScopeViaRelation($query, 'employee');
        $this->applyBranchScopeViaRelation($query, $branchId);

        $this->applyDateFilter($query, $dateFrom, $dateTo, 'date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->whereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('date')->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'date' => 'date',
            'check_in' => 'check_in',
            'check_out' => 'check_out',
            'late_hours' => 'late_hours',
            'overtime_hours' => 'overtime_hours',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.record_id')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'branch', 'label' => __('reports.columns.branch')],
            ['key' => 'date', 'label' => __('reports.columns.date')],
            ['key' => 'check_in', 'label' => __('reports.columns.check_in')],
            ['key' => 'check_out', 'label' => __('reports.columns.check_out')],
            ['key' => 'late_hours', 'label' => __('reports.columns.late_hours')],
            ['key' => 'overtime_hours', 'label' => __('reports.columns.overtime_hours')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'id' => $item->id,
            'employee_name' => $item->employee?->user?->name ?? '—',
            'branch' => $item->branch?->name ?? '—',
            'date' => $item->date ? ($datePrefix . $item->date->format('Y-m-d')) : '—',
            'check_in' => $item->check_in ? Carbon::parse($item->check_in)->format('H:i') : '—',
            'check_out' => $item->check_out ? Carbon::parse($item->check_out)->format('H:i') : '—',
            'late_hours' => number_format((float) ($item->late_hours ?? 0), 2),
            'overtime_hours' => number_format((float) ($item->overtime_hours ?? 0), 2),
            'status' => $this->translateStatus($item->status ?? 'present'),
        ];
    }
}
