<?php

namespace App\Services\Reports\Types;

use App\Models\RoscaFundPayment;

class RoscaFundPaymentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'rosca_fund_payments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.rosca_fund_payments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.rosca_fund_payments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public function getExportFilename(): string
    {
        return 'rosca_fund_payments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = RoscaFundPayment::query();
        $this->applyCompanyScopeViaRelation($query, 'roscaFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'payment_date');

        $totalCount = (clone $query)->count();
        $totalPaid = (clone $query)->sum('paid_amount');
        $totalScheduled = (clone $query)->sum('scheduled_amount');

        return [
            [
                'title' => __('reports.stats.total_rosca_payments_count'),
                'value' => $totalCount,
                'subtitle' => __('reports.stats.total_rosca_payments_count_sub'),
                'icon' => 'heroicon-o-document-check',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_rosca_paid'),
                'value' => number_format($totalPaid, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_rosca_paid_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_rosca_scheduled'),
                'value' => number_format($totalScheduled, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_rosca_scheduled_sub'),
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'indigo',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = RoscaFundPayment::query()
            ->with(['roscaFund', 'member.employee.user', 'receiver']);

        $this->applyCompanyScopeViaRelation($query, 'roscaFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'payment_date');
        $this->applyStatusFilter($query, $status, 'payment_status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->whereHas('roscaFund', fn($fq) => $fq->where('fund_name', 'like', "%{$search}%")->orWhere('fund_number', 'like', "%{$search}%"))
                ->orWhereHas('member', fn($mq) => $mq->where('full_name', 'like', "%{$search}%")->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%")));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'installment_order' => 'installment_order',
            'scheduled_amount' => 'scheduled_amount',
            'paid_amount' => 'paid_amount',
            'payment_status' => 'payment_status',
            'payment_date' => 'payment_date',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'member_name', 'label' => __('reports.columns.member_name')],
            ['key' => 'fund_name', 'label' => __('reports.columns.fund_name')],
            ['key' => 'period_month', 'label' => __('reports.columns.date')],
            ['key' => 'installment_order', 'label' => __('reports.columns.installment_order')],
            ['key' => 'scheduled_amount', 'label' => __('reports.columns.monthly_installment')],
            ['key' => 'paid_amount', 'label' => __('reports.columns.paid_amount')],
            ['key' => 'payment_status', 'label' => __('reports.columns.status')],
            ['key' => 'payment_date', 'label' => __('reports.columns.date')],
            ['key' => 'receiver_name', 'label' => __('reports.columns.receiver_name')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $memberName = $item->member?->full_name ?: ($item->member?->employee?->user?->name ?? '—');

        return [
            'member_name' => $memberName,
            'fund_name' => $item->roscaFund?->fund_name ?? '—',
            'period_month' => $item->period_month ? ($datePrefix . $item->period_month->format('Y-m')) : '—',
            'installment_order' => '#' . ($item->installment_order ?? 1),
            'scheduled_amount' => number_format((float)$item->scheduled_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'paid_amount' => number_format((float)$item->paid_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'payment_status' => $this->translateStatus($item->payment_status),
            'payment_date' => $item->payment_date ? ($datePrefix . $item->payment_date->format('Y-m-d')) : '—',
            'receiver_name' => $item->receiver?->name ?? '—',
        ];
    }
}
