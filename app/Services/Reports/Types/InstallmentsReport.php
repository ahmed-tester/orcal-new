<?php

namespace App\Services\Reports\Types;

use App\Models\Installment;

class InstallmentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'installments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.installments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.installments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public function getExportFilename(): string
    {
        return 'installments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = Installment::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('installment_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('installment_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(total_amount), 0) as total_amount_sum,
                COALESCE(SUM(paid_amount), 0) as total_paid_sum,
                COALESCE(SUM(remaining_amount), 0) as total_remaining_sum
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_installment_amount'),
                'value' => number_format((float) ($stats->total_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_installment_amount_sub'),
                'icon' => 'heroicon-o-document-currency-dollar',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_installment_paid'),
                'value' => number_format((float) ($stats->total_paid_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_installment_paid_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_installment_remaining'),
                'value' => number_format((float) ($stats->total_remaining_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_installment_remaining_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Installment::query()
            ->with(['company', 'employee.user', 'employee.branch', 'installmentType']);

        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'installment_date');
        $this->applyStatusFilter($query, $status, 'status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('installment_number', 'like', "%{$search}%")
                ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('installmentType', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'installment_number' => 'installment_number',
            'installment_date' => 'installment_date',
            'total_amount' => 'total_amount',
            'down_payment' => 'down_payment',
            'monthly_amount' => 'monthly_amount',
            'paid_amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'installment_number', 'label' => __('reports.columns.installment_number')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'installment_type', 'label' => __('reports.columns.installment_type')],
            ['key' => 'installment_date', 'label' => __('reports.columns.date')],
            ['key' => 'total_amount', 'label' => __('reports.columns.total_amount')],
            ['key' => 'down_payment', 'label' => __('reports.columns.down_payment')],
            ['key' => 'monthly_amount', 'label' => __('reports.columns.monthly_amount')],
            ['key' => 'paid_amount', 'label' => __('reports.columns.paid_amount')],
            ['key' => 'remaining_amount', 'label' => __('reports.columns.remaining_amount')],
            ['key' => 'months_count', 'label' => __('reports.columns.months_count')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $statusLabel = $item->status instanceof \BackedEnum ? $item->status->getLabel() : $this->translateStatus($item->status);

        return [
            'installment_number' => $item->installment_number ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'installment_type' => $item->installmentType?->name ?? '—',
            'installment_date' => $item->installment_date ? ($datePrefix . $item->installment_date->format('Y-m-d')) : '—',
            'total_amount' => number_format((float) ($item->total_amount ?? 0), 2) . __('reports.values.currency'),
            'down_payment' => number_format((float) ($item->down_payment ?? 0), 2) . __('reports.values.currency'),
            'monthly_amount' => number_format((float) ($item->monthly_amount ?? 0), 2) . __('reports.values.currency'),
            'paid_amount' => number_format((float) ($item->paid_amount ?? 0), 2) . __('reports.values.currency'),
            'remaining_amount' => number_format((float) ($item->remaining_amount ?? 0), 2) . __('reports.values.currency'),
            'months_count' => $item->months_count ?? '—',
            'status' => $statusLabel,
        ];
    }
}
