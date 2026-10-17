<?php

namespace App\Services\Reports\Types;

use App\Models\EmployeeReward;

class EmployeeRewardsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'employee_rewards';
    }

    public function getTitle(): string
    {
        return __('reports.reports.employee_rewards.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.employee_rewards.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-gift';
    }

    public function getExportFilename(): string
    {
        return 'employee_rewards_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = EmployeeReward::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('reward_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('reward_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_rewards,
                COALESCE(SUM(net_amount), 0) as total_amount_sum,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) as paid_amount_sum,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_rewards_amount'),
                'value' => number_format((float) ($stats->total_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_rewards_amount_sub'),
                'icon' => 'heroicon-o-trophy',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_paid_rewards'),
                'value' => number_format((float) ($stats->paid_amount_sum ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_paid_rewards_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.pending_rewards_count'),
                'value' => $stats->pending_count ?? 0,
                'subtitle' => __('reports.stats.pending_rewards_count_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = EmployeeReward::query()
            ->with(['company', 'employee.user', 'employee.branch', 'creator']);

        $this->applyCompanyScope($query);
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'reward_date');
        $this->applyStatusFilter($query, $status, 'status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('reward_number', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%")
                ->orWhereHas('employee.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'reward_number' => 'reward_number',
            'reward_date' => 'reward_date',
            'net_amount' => 'net_amount',
            'status' => 'status',
            'paid_date' => 'paid_date',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'reward_number', 'label' => __('reports.columns.reward_number')],
            ['key' => 'employee_name', 'label' => __('reports.columns.employee_name')],
            ['key' => 'branch_name', 'label' => __('reports.columns.branch')],
            ['key' => 'reward_date', 'label' => __('reports.columns.date')],
            ['key' => 'hours_count', 'label' => __('reports.columns.hours_count')],
            ['key' => 'minutes_count', 'label' => __('reports.columns.minutes_count')],
            ['key' => 'net_amount', 'label' => __('reports.columns.net_amount')],
            ['key' => 'reason', 'label' => __('reports.columns.reason')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'paid_date', 'label' => __('reports.columns.paid_date')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $statusLabel = $item->status instanceof \BackedEnum ? $item->status->getLabel() : $this->translateStatus($item->status);

        return [
            'reward_number' => $item->reward_number ?? '—',
            'employee_name' => $item->employee?->user?->name ?? '—',
            'branch_name' => $item->employee?->branch?->name ?? '—',
            'reward_date' => $item->reward_date ? ($datePrefix . $item->reward_date->format('Y-m-d')) : '—',
            'hours_count' => ($item->hours_count ?? 0) . __('reports.values.hours'),
            'minutes_count' => ($item->minutes_count ?? 0) . __('reports.values.minutes'),
            'net_amount' => number_format((float) ($item->net_amount ?? 0), 2) . __('reports.values.currency'),
            'reason' => $item->reason ?? '—',
            'status' => $statusLabel,
            'paid_date' => $item->paid_date ? ($datePrefix . $item->paid_date->format('Y-m-d')) : '—',
        ];
    }
}
