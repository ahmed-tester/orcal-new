<?php

namespace App\Services\Reports\Types;

use App\Models\LoanFundLoanPayment;

class LoanFundLoanPaymentsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'loan_fund_loan_payments';
    }

    public function getTitle(): string
    {
        return __('reports.reports.loan_fund_loan_payments.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.loan_fund_loan_payments.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-receipt-percent';
    }

    public function getExportFilename(): string
    {
        return 'loan_fund_payments_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = LoanFundLoanPayment::query();
        $this->applyCompanyScopeViaRelation($query, 'loan.loanFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'installment_date');

        $totalCount = (clone $query)->count();
        $totalCollected = (clone $query)->sum('collected_amount');

        return [
            [
                'title' => __('reports.stats.total_payments_count'),
                'value' => $totalCount,
                'subtitle' => __('reports.stats.total_payments_count_sub'),
                'icon' => 'heroicon-o-document-text',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_payments_collected'),
                'value' => number_format($totalCollected, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_payments_collected_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = LoanFundLoanPayment::query()
            ->with(['loan.loanFund', 'member.employee.user', 'receiver']);

        $this->applyCompanyScopeViaRelation($query, 'loan.loanFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'installment_date');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('voucher_number', 'like', "%{$search}%")
                ->orWhereHas('loan', fn($lq) => $lq->where('loan_number', 'like', "%{$search}%"))
                ->orWhereHas('member', fn($mq) => $mq->where('full_name', 'like', "%{$search}%")->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%")));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'voucher_number' => 'voucher_number',
            'installment_order' => 'installment_order',
            'collected_amount' => 'collected_amount',
            'remaining_balance' => 'remaining_balance',
            'installment_date' => 'installment_date',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'voucher_number', 'label' => __('reports.columns.voucher_number')],
            ['key' => 'loan_number', 'label' => __('reports.columns.loan_number')],
            ['key' => 'member_name', 'label' => __('reports.columns.member_name')],
            ['key' => 'fund_name', 'label' => __('reports.columns.fund_name')],
            ['key' => 'installment_order', 'label' => __('reports.columns.installment_order')],
            ['key' => 'collected_amount', 'label' => __('reports.columns.collected_amount')],
            ['key' => 'remaining_balance', 'label' => __('reports.columns.remaining_amount')],
            ['key' => 'installment_date', 'label' => __('reports.columns.installment_date')],
            ['key' => 'receiver_name', 'label' => __('reports.columns.receiver_name')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $memberName = $item->member?->full_name ?: ($item->member?->employee?->user?->name ?? '—');

        return [
            'voucher_number' => $item->voucher_number ?? '—',
            'loan_number' => $item->loan?->loan_number ?? '—',
            'member_name' => $memberName,
            'fund_name' => $item->loan?->loanFund?->fund_name ?? '—',
            'installment_order' => '#' . ($item->installment_order ?? 1),
            'collected_amount' => number_format((float)$item->collected_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'remaining_balance' => number_format((float)$item->remaining_balance, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'installment_date' => $item->installment_date ? ($datePrefix . $item->installment_date->format('Y-m-d')) : '—',
            'receiver_name' => $item->receiver?->name ?? '—',
        ];
    }
}
