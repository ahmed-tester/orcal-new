<?php

namespace App\Services\Reports\Types;

use App\Models\AttendanceDevice;

class AttendanceDevicesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'attendance_devices';
    }

    public function getTitle(): string
    {
        return __('reports.reports.attendance_devices.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.attendance_devices.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-finger-print';
    }

    public function getExportFilename(): string
    {
        return 'attendance_devices_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = AttendanceDevice::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);

        $total = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_attendance_devices'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_attendance_devices_sub'),
                'icon' => 'heroicon-o-finger-print',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.active_attendance_devices'),
                'value' => $active,
                'subtitle' => __('reports.stats.active_attendance_devices_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'emerald',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = AttendanceDevice::query()
            ->with(['company', 'branch']);

        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);
        $this->applyDateFilter($query, $dateFrom, $dateTo);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhereHas('branch', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'name' => 'name',
            'code' => 'code',
            'serial_number' => 'serial_number',
            'ip_address' => 'ip_address',
            'status' => 'status',
            'last_seen_at' => 'last_seen_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'name', 'label' => __('reports.columns.device_number')],
            ['key' => 'code', 'label' => __('reports.columns.classification_code')],
            ['key' => 'branch', 'label' => __('reports.columns.branch')],
            ['key' => 'ip_address', 'label' => __('reports.columns.reference_number')],
            ['key' => 'serial_number', 'label' => __('reports.columns.card_number')],
            ['key' => 'device_type', 'label' => __('reports.columns.user_type')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'last_seen_at', 'label' => __('reports.columns.date')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'name' => $item->name ?? '—',
            'code' => $item->code ?? '—',
            'branch' => $item->branch?->name ?? '—',
            'ip_address' => $item->ip_address ?? '—',
            'serial_number' => $item->serial_number ?? '—',
            'device_type' => ($item->device_type ?? '—') . ' (' . ($item->model ?? '') . ')',
            'status' => $this->translateStatus($item->status),
            'last_seen_at' => $item->last_seen_at ? ($datePrefix . $item->last_seen_at->format('Y-m-d H:i')) : '—',
        ];
    }
}
