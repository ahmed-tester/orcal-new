<?php

namespace App\Services\Reports\Types;

use App\Models\DailyJournal;

class DailyJournalsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'daily_journals';
    }

    public function getTitle(): string
    {
        return __('reports.reports.daily_journals.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.daily_journals.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public function getExportFilename(): string
    {
        return 'daily_journals_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = DailyJournal::query();
        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('entry_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('entry_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_entries,
                COALESCE(SUM(sales), 0) as total_sales,
                COALESCE(SUM(purchases), 0) as total_purchases,
                COALESCE(SUM(net_amount), 0) as total_net
            ")
            ->first();

        $sales = (float)($stats->total_sales ?? 0);
        $purchases = (float)($stats->total_purchases ?? 0);
        $net = (float)($stats->total_net ?? ($sales - $purchases));

        return [
            [
                'title' => __('reports.stats.total_sales'),
                'value' => number_format($sales, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_sales_sub'),
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_purchases'),
                'value' => number_format($purchases, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_purchases_sub'),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'red',
            ],
            [
                'title' => __('reports.stats.net_journal'),
                'value' => number_format($net, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.net_journal_sub'),
                'icon' => 'heroicon-o-calculator',
                'color' => $net >= 0 ? 'emerald' : 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = DailyJournal::query()
            ->with(['company', 'branch', 'recorder.user']);

        $this->applyCompanyScope($query);
        $this->applyBranchScope($query, $branchId);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'entry_date');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('entry_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('recorder.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'entry_number' => 'entry_number',
            'entry_date' => 'entry_date',
            'sales' => 'sales',
            'purchases' => 'purchases',
            'net_amount' => 'net_amount',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'entry_number', 'label' => __('reports.columns.entry_number')],
            ['key' => 'entry_date', 'label' => __('reports.columns.date')],
            ['key' => 'branch_name', 'label' => __('reports.columns.branch')],
            ['key' => 'sales', 'label' => __('reports.columns.sales')],
            ['key' => 'purchases', 'label' => __('reports.columns.purchases')],
            ['key' => 'net_amount', 'label' => __('reports.columns.net_amount')],
            ['key' => 'recorder_name', 'label' => __('reports.columns.recorded_by')],
            ['key' => 'notes', 'label' => __('reports.columns.notes')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'entry_number' => $item->entry_number ?? '—',
            'entry_date' => $item->entry_date ? ($datePrefix . $item->entry_date->format('Y-m-d')) : '—',
            'branch_name' => $item->branch?->name ?? '—',
            'sales' => number_format((float) ($item->sales ?? 0), 2) . __('reports.values.currency'),
            'purchases' => number_format((float) ($item->purchases ?? 0), 2) . __('reports.values.currency'),
            'net_amount' => number_format((float) ($item->net_amount ?? 0), 2) . __('reports.values.currency'),
            'recorder_name' => $item->recorder?->user?->name ?? '—',
            'notes' => $item->notes ?? '—',
        ];
    }
}
