<?php

namespace App\Services\Reports\Types;

use App\Models\WeeklyNote;

class WeeklyNotesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'weekly_notes';
    }

    public function getTitle(): string
    {
        return __('reports.reports.weekly_notes.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.weekly_notes.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public function getExportFilename(): string
    {
        return 'weekly_notes_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = WeeklyNote::query();
        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'note_date');

        $total = (clone $query)->count();

        return [
            [
                'title' => __('reports.stats.total_weekly_notes'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_weekly_notes_sub'),
                'icon' => 'heroicon-o-clipboard-document-list',
                'color' => 'blue',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = WeeklyNote::query()
            ->with(['company', 'assignedEmployee.user', 'creator']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'note_date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('voucher_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhereHas('assignedEmployee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'voucher_number' => 'voucher_number',
            'title' => 'title',
            'note_date' => 'note_date',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'voucher_number', 'label' => __('reports.columns.voucher_number')],
            ['key' => 'title', 'label' => __('reports.columns.subject')],
            ['key' => 'assigned_employee', 'label' => __('reports.columns.employee_name')],
            ['key' => 'company', 'label' => __('reports.columns.company_name')],
            ['key' => 'note_date', 'label' => __('reports.columns.date')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'creator_name', 'label' => __('reports.columns.recorded_by')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'voucher_number' => $item->voucher_number ?? '—',
            'title' => $item->title ?? '—',
            'assigned_employee' => $item->assignedEmployee?->user?->name ?? '—',
            'company' => $item->company?->trade_name ?? '—',
            'note_date' => $item->note_date ? ($datePrefix . $item->note_date->format('Y-m-d')) : '—',
            'status' => $this->translateStatus($item->status),
            'creator_name' => $item->creator?->name ?? '—',
        ];
    }
}
