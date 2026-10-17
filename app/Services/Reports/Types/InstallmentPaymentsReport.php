<?php

namespace App\Services\Reports\Types;

use App\Models\InstallmentPayment;

class InstallmentPaymentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'installment_payments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.installment_payments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.installment_payments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-receipt-percent';
    }

    public function getExportFilename(): string
    {
        return 'installment_payments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = InstallmentPayment::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('payment_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_payments,
                COALESCE(SUM(amount), 0) as total_amount_sum
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_installment_payments'),
                'value' => number_format((float) ($stats->total_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_installment_payments_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.installment_payments_count'),
                'value' => $stats->total_payments ?? 0,
                'subtitle' => __('reports.stats.installment_payments_count_sub'),
                'icon' => 'heroicon-o-document-check',
                'color' => 'blue',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = InstallmentPayment::query()
            ->with(['company', 'employee.user', 'installment', 'executor.user']);

        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'payment_date');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('voucher_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('installment', fn($iq) => $iq->where('installment_number', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'voucher_number' => 'voucher_number',
            'payment_date' => 'payment_date',
            'amount' => 'amount',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'voucher_number', 'label' => __('reports.columns.voucher_number')],
            ['key' => 'installment_number', 'label' => __('reports.columns.installment_number')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'payment_date', 'label' => __('reports.columns.date')],
            ['key' => 'amount', 'label' => __('reports.columns.amount')],
            ['key' => 'payment_source', 'label' => __('reports.columns.payment_source')],
            ['key' => 'executor_name', 'label' => __('reports.columns.executor_name')],
            ['key' => 'description', 'label' => __('reports.columns.description')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $sourceLabel = $item->payment_source instanceof \BackedEnum ? $item->payment_source->getLabel() : ($item->payment_source ?? '—');

        return [
            'voucher_number' => $item->voucher_number ?? '—',
            'installment_number' => $item->installment?->installment_number ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'payment_date' => $item->payment_date ? ($datePrefix . $item->payment_date->format('Y-m-d')) : '—',
            'amount' => number_format((float) ($item->amount ?? 0), 2) . __('reports.values.currency'),
            'payment_source' => $sourceLabel,
            'executor_name' => $item->executor?->user?->name ?? '—',
            'description' => $item->description ?? '—',
        ];
    }
}
