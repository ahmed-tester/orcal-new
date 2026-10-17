<?php

namespace App\Services\Reports\Types;

use App\Models\Violation;

class ViolationsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'violations';
    }

    public function getTitle(): string
    {
        return __('reports.reports.violations.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.violations.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-scale';
    }

    public function getExportFilename(): string
    {
        return 'violations_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $stats = Violation::query()
            ->when($dateFrom, fn($q) => $q->whereDate('violation_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('violation_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(violation_amount), 0) as total_amount_sum,
                COALESCE(SUM(paid_amount), 0) as paid_amount_sum,
                COALESCE(SUM(remaining_amount), 0) as remaining_amount_sum
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.violations_count'),
                'value' => $stats->total ?? 0,
                'subtitle' => __('reports.stats.violations_count_sub'),
                'icon' => 'heroicon-o-scale',
                'color' => 'amber',
            ],
            [
                'title' => __('reports.stats.violations_total_amount'),
                'value' => number_format((float) ($stats->total_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violations_total_amount_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.violations_paid_amount'),
                'value' => number_format((float) ($stats->paid_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violations_paid_amount_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'emerald',
            ],
            [
                'title' => __('reports.stats.violations_remaining_amount'),
                'value' => number_format((float) ($stats->remaining_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violations_remaining_amount_sub'),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'rose',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Violation::query()
            ->with(['company', 'category', 'type']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'violation_date');
        $this->applyStatusFilter($query, $status, 'payment_status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('violation_number', 'like', "%{$search}%")
                ->orWhere('external_violation_number', 'like', "%{$search}%")
                ->orWhere('issuing_authority', 'like', "%{$search}%")
                ->orWhereHas('company', fn($cq) => $cq->where('trade_name', 'like', "%{$search}%"))
                ->orWhereHas('category', fn($catq) => $catq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('type', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'violation_number' => 'violation_number',
            'violation_date' => 'violation_date',
            'total_amount' => 'violation_amount',
            'paid_amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'stage' => 'stage',
            'payment_status' => 'payment_status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'violation_number', 'label' => __('reports.columns.violation_number')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'category_name', 'label' => __('reports.columns.violation_category')],
            ['key' => 'type_name', 'label' => __('reports.columns.violation_type')],
            ['key' => 'issuing_authority', 'label' => __('reports.columns.issuing_authority')],
            ['key' => 'violation_date', 'label' => __('reports.columns.violation_date')],
            ['key' => 'stage', 'label' => __('reports.columns.violation_stage')],
            ['key' => 'total_amount', 'label' => __('reports.columns.total_amount')],
            ['key' => 'paid_amount', 'label' => __('reports.columns.paid_amount')],
            ['key' => 'remaining_amount', 'label' => __('reports.columns.remaining_amount')],
            ['key' => 'payment_status', 'label' => __('reports.columns.payment_status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $stageLabel = $item->stage instanceof \BackedEnum && method_exists($item->stage, 'getLabel') ? $item->stage->getLabel() : ($item->stage?->value ?? '—');
        $paymentStatusLabel = $item->payment_status instanceof \BackedEnum && method_exists($item->payment_status, 'getLabel') ? $item->payment_status->getLabel() : ($item->payment_status?->value ?? '—');

        return [
            'violation_number' => $item->violation_number ?? ($item->external_violation_number ? "#{$item->external_violation_number}" : '—'),
            'company_name' => $item->company?->trade_name ?? '—',
            'category_name' => $item->category?->name ?? '—',
            'type_name' => $item->type?->name ?? '—',
            'issuing_authority' => $item->issuing_authority ?? '—',
            'violation_date' => $item->violation_date ? ($datePrefix . $item->violation_date->format('Y-m-d')) : '—',
            'stage' => $stageLabel,
            'total_amount' => number_format((float) ($item->violation_amount ?? 0), 2) . __('reports.values.currency'),
            'paid_amount' => number_format((float) ($item->paid_amount ?? 0), 2) . __('reports.values.currency'),
            'remaining_amount' => number_format((float) ($item->remaining_amount ?? 0), 2) . __('reports.values.currency'),
            'payment_status' => $paymentStatusLabel,
        ];
    }
}
