<?php

namespace App\Services\Reports\Types;

use App\Models\PayrollRecord;

class PayrollRecordsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'payroll_records';
    }

    public function getTitle(): string
    {
        return __('reports.reports.payroll_records.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.payroll_records.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public function getExportFilename(): string
    {
        return 'payroll_records_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = PayrollRecord::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('payroll_month', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('payroll_month', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_records,
                COALESCE(SUM(net_amount), 0) as total_net_sum,
                COALESCE(SUM(paid_amount), 0) as total_paid_sum,
                COALESCE(SUM(remaining_amount), 0) as total_remaining_sum
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_net_payroll'),
                'value' => number_format((float) ($stats->total_net_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_net_payroll_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_paid_payroll'),
                'value' => number_format((float) ($stats->total_paid_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_paid_payroll_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_remaining_payroll'),
                'value' => number_format((float) ($stats->total_remaining_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_remaining_payroll_sub'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = PayrollRecord::query()
            ->with(['company', 'employee.user', 'employee.branch']);

        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'payroll_month');
        $this->applyStatusFilter($query, $status, 'payment_status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'record_number' => 'record_number',
            'payroll_month' => 'payroll_month',
            'basic_salary' => 'basic_salary',
            'additional_amount' => 'additional_amount',
            'deduction_amount' => 'deduction_amount',
            'net_amount' => 'net_amount',
            'paid_amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'payment_status' => 'payment_status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'record_number', 'label' => __('reports.columns.record_number')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'branch_name', 'label' => __('reports.columns.branch')],
            ['key' => 'payroll_month', 'label' => __('reports.columns.payroll_month')],
            ['key' => 'basic_salary', 'label' => __('reports.columns.basic_salary')],
            ['key' => 'additional_amount', 'label' => __('reports.columns.additional_amount')],
            ['key' => 'deduction_amount', 'label' => __('reports.columns.deduction_amount')],
            ['key' => 'net_amount', 'label' => __('reports.columns.net_amount')],
            ['key' => 'paid_amount', 'label' => __('reports.columns.paid_amount')],
            ['key' => 'remaining_amount', 'label' => __('reports.columns.remaining_amount')],
            ['key' => 'payment_status', 'label' => __('reports.columns.payment_status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $paymentStatusValue = $item->payment_status instanceof \BackedEnum ? $item->payment_status->value : ($item->payment_status ?? 'unpaid');

        return [
            'record_number' => $item->record_number ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'branch_name' => $item->employee?->branch?->name ?? '—',
            'payroll_month' => $item->payroll_month ? ($datePrefix . $item->payroll_month->format('Y-m')) : '—',
            'basic_salary' => number_format((float) ($item->basic_salary ?? 0), 2) . __('reports.values.currency'),
            'additional_amount' => number_format((float) ($item->additional_amount ?? 0), 2) . __('reports.values.currency'),
            'deduction_amount' => number_format((float) ($item->deduction_amount ?? 0), 2) . __('reports.values.currency'),
            'net_amount' => number_format((float) ($item->net_amount ?? 0), 2) . __('reports.values.currency'),
            'paid_amount' => number_format((float) ($item->paid_amount ?? 0), 2) . __('reports.values.currency'),
            'remaining_amount' => number_format((float) ($item->remaining_amount ?? 0), 2) . __('reports.values.currency'),
            'payment_status' => $this->translateStatus($paymentStatusValue),
        ];
    }
}
