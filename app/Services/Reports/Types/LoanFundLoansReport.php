<?php

namespace App\Services\Reports\Types;

use App\Models\LoanFundLoan;

class LoanFundLoansReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'loan_fund_loans';
    }

    public function getTitle(): string
    {
        return __('reports.reports.loan_fund_loans.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.loan_fund_loans.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public function getExportFilename(): string
    {
        return 'loan_fund_loans_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = LoanFundLoan::query();
        $this->applyCompanyScopeViaRelation($query, 'loanFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'loan_date');

        $totalLoans = (clone $query)->count();
        $totalAmount = (clone $query)->sum('loan_amount');
        $totalPaid = (clone $query)->sum('paid_amount');
        $totalRemaining = (clone $query)->sum('remaining_amount');

        return [
            [
                'title' => __('reports.stats.total_loans_count'),
                'value' => $totalLoans,
                'subtitle' => __('reports.stats.total_loans_count_sub'),
                'icon' => 'heroicon-o-credit-card',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_loans_amount'),
                'value' => number_format($totalAmount, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_loans_amount_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'indigo',
            ],
            [
                'title' => __('reports.stats.total_loans_paid'),
                'value' => number_format($totalPaid, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_loans_paid_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_loans_remaining'),
                'value' => number_format($totalRemaining, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_loans_remaining_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = LoanFundLoan::query()
            ->with(['loanFund', 'member.employee.user', 'approver']);

        $this->applyCompanyScopeViaRelation($query, 'loanFund');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'loan_date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('loan_number', 'like', "%{$search}%")
                ->orWhereHas('loanFund', fn($fq) => $fq->where('fund_name', 'like', "%{$search}%")->orWhere('fund_number', 'like', "%{$search}%"))
                ->orWhereHas('member', fn($mq) => $mq->where('full_name', 'like', "%{$search}%")->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%")));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'loan_number' => 'loan_number',
            'loan_amount' => 'loan_amount',
            'paid_amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'monthly_installment' => 'monthly_installment',
            'loan_date' => 'loan_date',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'loan_number', 'label' => __('reports.columns.loan_number')],
            ['key' => 'member_name', 'label' => __('reports.columns.member_name')],
            ['key' => 'fund_name', 'label' => __('reports.columns.fund_name')],
            ['key' => 'loan_amount', 'label' => __('reports.columns.loan_amount')],
            ['key' => 'monthly_installment', 'label' => __('reports.columns.monthly_installment')],
            ['key' => 'paid_amount', 'label' => __('reports.columns.paid_amount')],
            ['key' => 'remaining_amount', 'label' => __('reports.columns.remaining_amount')],
            ['key' => 'loan_date', 'label' => __('reports.columns.loan_date')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $memberName = $item->member?->full_name ?: ($item->member?->employee?->user?->name ?? '—');

        return [
            'loan_number' => $item->loan_number ?? '—',
            'member_name' => $memberName,
            'fund_name' => $item->loanFund?->fund_name ?? '—',
            'loan_amount' => number_format((float)$item->loan_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'monthly_installment' => number_format((float)$item->monthly_installment, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'paid_amount' => number_format((float)$item->paid_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'remaining_amount' => number_format((float)$item->remaining_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'loan_date' => $item->loan_date ? ($datePrefix . $item->loan_date->format('Y-m-d')) : '—',
            'status' => $this->translateStatus($item->status),
        ];
    }
}
