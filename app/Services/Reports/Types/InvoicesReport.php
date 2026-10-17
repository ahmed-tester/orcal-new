<?php

namespace App\Services\Reports\Types;

use App\Models\Invoice;

class InvoicesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'invoices';
    }

    public function getTitle(): string
    {
        return __('reports.reports.invoices.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.invoices.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function getExportFilename(): string
    {
        return 'invoices_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $stats = Invoice::query()
            ->when($dateFrom, fn($q) => $q->whereDate('issue_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('issue_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                COALESCE(SUM(total_amount), 0) as total_amount_sum
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.invoices'),
                'value' => $stats->total ?? 0,
                'subtitle' => __('reports.stats.invoices_sub'),
                'icon' => 'heroicon-o-document-duplicate',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.paid_invoices'),
                'value' => $stats->paid_count ?? 0,
                'subtitle' => __('reports.stats.paid_invoices_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_amount'),
                'value' => number_format((float) ($stats->total_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_amount_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'emerald',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Invoice::query()
            ->with(['customer', 'employee.user']);

        // Authorization scoping — Invoice has direct company_id and branch_id
        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);

        $this->applyDateFilter($query, $dateFrom, $dateTo, 'issue_date');
        $this->applyStatusFilter($query, $status, 'payment_status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'invoice_number' => 'invoice_number',
            'issue_date' => 'issue_date',
            'total_amount' => 'total_amount',
            'paid_amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'payment_status' => 'payment_status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'invoice_number', 'label' => __('reports.columns.invoice_number')],
            ['key' => 'customer_name', 'label' => __('reports.columns.customer_name')],
            ['key' => 'employee_name', 'label' => __('reports.columns.responsible_employee')],
            ['key' => 'issue_date', 'label' => __('reports.columns.issue_date')],
            ['key' => 'total_amount', 'label' => __('reports.columns.total_amount')],
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
            'invoice_number' => $item->invoice_number ?? '—',
            'customer_name' => $item->customer?->name ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'issue_date' => $item->issue_date ? ($datePrefix . $item->issue_date->format('Y-m-d')) : '—',
            'total_amount' => number_format((float) ($item->total_amount ?? 0), 2) . __('reports.values.currency'),
            'paid_amount' => number_format((float) ($item->paid_amount ?? 0), 2) . __('reports.values.currency'),
            'remaining_amount' => number_format((float) ($item->remaining_amount ?? 0), 2) . __('reports.values.currency'),
            'payment_status' => $this->translateStatus($paymentStatusValue),
        ];
    }
}
