<?php

namespace App\Services\Reports\Types;

use App\Models\ViolationTransaction;

class ViolationTransactionsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'violation_transactions';
    }

    public function getTitle(): string
    {
        return __('reports.reports.violation_transactions.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.violation_transactions.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public function getExportFilename(): string
    {
        return 'violation_transactions_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $stats = ViolationTransaction::query()
            ->when($dateFrom, fn($q) => $q->whereDate('transaction_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('transaction_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END), 0) as total_collections,
                COALESCE(SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END), 0) as total_payments
            ")
            ->first();

        $latestTransaction = ViolationTransaction::orderByDesc('transaction_date')->orderByDesc('id')->first();
        $runningBalance = $latestTransaction?->balance_after ?? 0;

        return [
            [
                'title' => __('reports.stats.violation_trans_count'),
                'value' => $stats->total ?? 0,
                'subtitle' => __('reports.stats.violation_trans_count_sub'),
                'icon' => 'heroicon-o-arrows-right-left',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.violation_total_collections'),
                'value' => number_format((float) ($stats->total_collections ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violation_total_collections_sub'),
                'icon' => 'heroicon-o-arrow-down-tray',
                'color' => 'emerald',
            ],
            [
                'title' => __('reports.stats.violation_total_payments'),
                'value' => number_format((float) ($stats->total_payments ?? 0), 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violation_total_payments_sub'),
                'icon' => 'heroicon-o-arrow-up-tray',
                'color' => 'amber',
            ],
            [
                'title' => __('reports.stats.violation_fund_balance'),
                'value' => number_format((float) $runningBalance, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.violation_fund_balance_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'indigo',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = ViolationTransaction::query()
            ->with(['company', 'violation']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'transaction_date');
        $this->applyStatusFilter($query, $status, 'transaction_type');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('voucher_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('company', fn($cq) => $cq->where('trade_name', 'like', "%{$search}%"))
                ->orWhereHas('violation', fn($vq) => $vq->where('violation_number', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('transaction_date')->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'voucher_number' => 'voucher_number',
            'transaction_date' => 'transaction_date',
            'transaction_type' => 'transaction_type',
            'amount' => 'amount',
            'balance_after' => 'balance_after',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'voucher_number', 'label' => __('reports.columns.transaction_number')],
            ['key' => 'transaction_date', 'label' => __('reports.columns.transaction_date')],
            ['key' => 'transaction_type', 'label' => __('reports.columns.transaction_type')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'violation_number', 'label' => __('reports.columns.violation_number')],
            ['key' => 'amount', 'label' => __('reports.columns.amount')],
            ['key' => 'balance_after', 'label' => __('reports.columns.balance_after')],
            ['key' => 'description', 'label' => __('reports.columns.notes')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $type = $item->transaction_type;
        $typeLabel = $type instanceof \BackedEnum && method_exists($type, 'getLabel') ? $type->getLabel() : ($type?->value ?? (string) $type ?? '—');

        return [
            'voucher_number' => $item->voucher_number ?? '—',
            'transaction_date' => $item->transaction_date ? ($datePrefix . $item->transaction_date->format('Y-m-d')) : '—',
            'transaction_type' => $typeLabel,
            'company_name' => $item->company?->trade_name ?? '—',
            'violation_number' => $item->violation?->violation_number ?? '—',
            'amount' => number_format((float) ($item->amount ?? 0), 2) . __('reports.values.currency'),
            'balance_after' => number_format((float) ($item->balance_after ?? 0), 2) . __('reports.values.currency'),
            'description' => $item->description ?? '—',
        ];
    }
}
