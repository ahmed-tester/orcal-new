<?php

namespace App\Services\Reports\Types;

use App\Models\LoanFund;

class LoanFundsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'loan_funds';
    }

    public function getTitle(): string
    {
        return __('reports.reports.loan_funds.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.loan_funds.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public function getExportFilename(): string
    {
        return 'loan_funds_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = LoanFund::query();
        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $totalFunds = (clone $query)->count();
        $totalTarget = (clone $query)->sum('target_amount');
        $activeFunds = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_loan_funds'),
                'value' => $totalFunds,
                'subtitle' => __('reports.stats.total_loan_funds_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_loan_target'),
                'value' => number_format($totalTarget, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_loan_target_sub'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.active_loan_funds'),
                'value' => $activeFunds,
                'subtitle' => __('reports.stats.active_loan_funds_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'emerald',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = LoanFund::query()
            ->with(['company', 'trusteeEmployee.user', 'creator'])
            ->withCount(['members', 'loans']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('fund_number', 'like', "%{$search}%")
                ->orWhere('fund_name', 'like', "%{$search}%")
                ->orWhere('trustee_name', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'fund_number' => 'fund_number',
            'fund_name' => 'fund_name',
            'target_amount' => 'target_amount',
            'status' => 'status',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'fund_number', 'label' => __('reports.columns.fund_number')],
            ['key' => 'fund_name', 'label' => __('reports.columns.fund_name')],
            ['key' => 'company', 'label' => __('reports.columns.company_name')],
            ['key' => 'target_amount', 'label' => __('reports.columns.target_amount')],
            ['key' => 'max_loan_limit', 'label' => __('reports.columns.max_loan_limit')],
            ['key' => 'members_count', 'label' => __('reports.columns.members_count')],
            ['key' => 'loans_count', 'label' => __('reports.columns.loans_count')],
            ['key' => 'trustee_name', 'label' => __('reports.columns.trustee_name')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'fund_number' => $item->fund_number ?? '—',
            'fund_name' => $item->fund_name ?? '—',
            'company' => $item->company?->trade_name ?? '—',
            'target_amount' => number_format((float)$item->target_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'max_loan_limit' => number_format((float)$item->max_loan_limit, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'members_count' => (string)($item->members_count ?? 0),
            'loans_count' => (string)($item->loans_count ?? 0),
            'trustee_name' => $item->trustee_name ?: ($item->trusteeEmployee?->user?->name ?? '—'),
            'status' => $this->translateStatus($item->status),
        ];
    }
}
