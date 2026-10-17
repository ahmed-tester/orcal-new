<?php

namespace App\Services\Reports\Types;

use App\Models\RoscaFund;

class RoscaFundsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'rosca_funds';
    }

    public function getTitle(): string
    {
        return __('reports.reports.rosca_funds.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.rosca_funds.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public function getExportFilename(): string
    {
        return 'rosca_funds_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = RoscaFund::query();
        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'start_date');

        $totalFunds = (clone $query)->count();
        $totalPool = (clone $query)->sum('total_pool_amount');
        $activeFunds = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_rosca_funds'),
                'value' => $totalFunds,
                'subtitle' => __('reports.stats.total_rosca_funds_sub'),
                'icon' => 'heroicon-o-user-group',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.total_rosca_pool'),
                'value' => number_format($totalPool, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_rosca_pool_sub'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.active_rosca_funds'),
                'value' => $activeFunds,
                'subtitle' => __('reports.stats.active_rosca_funds_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'emerald',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = RoscaFund::query()
            ->with(['company', 'assignedManager'])
            ->withCount(['members', 'payments']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'start_date');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('fund_number', 'like', "%{$search}%")
                ->orWhere('fund_name', 'like', "%{$search}%")
                ->orWhere('manager_name', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'fund_number' => 'fund_number',
            'fund_name' => 'fund_name',
            'monthly_installment' => 'monthly_installment',
            'total_pool_amount' => 'total_pool_amount',
            'start_date' => 'start_date',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'fund_number', 'label' => __('reports.columns.fund_number')],
            ['key' => 'fund_name', 'label' => __('reports.columns.fund_name')],
            ['key' => 'company', 'label' => __('reports.columns.company_name')],
            ['key' => 'monthly_installment', 'label' => __('reports.columns.monthly_installment')],
            ['key' => 'members_count', 'label' => __('reports.columns.members_count')],
            ['key' => 'total_pool_amount', 'label' => __('reports.columns.total_pool_amount')],
            ['key' => 'start_date', 'label' => __('reports.columns.from_date')],
            ['key' => 'end_date', 'label' => __('reports.columns.to_date')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'fund_number' => $item->fund_number ?? '—',
            'fund_name' => $item->fund_name ?? '—',
            'company' => $item->company?->trade_name ?? '—',
            'monthly_installment' => number_format((float)$item->monthly_installment, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'members_count' => (string)($item->members_count ?? 0),
            'total_pool_amount' => number_format((float)$item->total_pool_amount, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'start_date' => $item->start_date ? ($datePrefix . $item->start_date->format('Y-m-d')) : '—',
            'end_date' => $item->end_date ? ($datePrefix . $item->end_date->format('Y-m-d')) : '—',
            'status' => $this->translateStatus($item->status),
        ];
    }
}
