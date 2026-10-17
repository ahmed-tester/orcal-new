<?php

namespace App\Services\Reports\Types;

use App\Models\TreasuryTransaction;

class TreasuryTransactionsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'treasury_transactions';
    }

    public function getTitle(): string
    {
        return __('reports.reports.treasury_transactions.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.treasury_transactions.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public function getExportFilename(): string
    {
        return 'treasury_transactions_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = TreasuryTransaction::query();
        $this->applyCompanyScope($query);

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('transaction_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('transaction_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END), 0) as total_incoming,
                COALESCE(SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END), 0) as total_outgoing
            ")
            ->first();

        $incoming = (float)($stats->total_incoming ?? 0);
        $outgoing = (float)($stats->total_outgoing ?? 0);
        $net = $incoming - $outgoing;

        return [
            [
                'title' => __('reports.stats.total_incoming'),
                'value' => number_format($incoming, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_incoming_sub'),
                'icon' => 'heroicon-o-arrow-down-left',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.total_outgoing'),
                'value' => number_format($outgoing, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.total_outgoing_sub'),
                'icon' => 'heroicon-o-arrow-up-right',
                'color' => 'red',
            ],
            [
                'title' => __('reports.stats.net_treasury'),
                'value' => number_format($net, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.net_treasury_sub'),
                'icon' => 'heroicon-o-scale',
                'color' => $net >= 0 ? 'blue' : 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = TreasuryTransaction::query()
            ->with(['company', 'executor.user', 'paymentCollectionItem']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'transaction_date');
        $this->applyStatusFilter($query, $status, 'transaction_type');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('transaction_number', 'like', "%{$search}%")
                ->orWhere('voucher_number', 'like', "%{$search}%")
                ->orWhere('recipient_or_payer', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'transaction_number' => 'transaction_number',
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
            ['key' => 'transaction_number', 'label' => __('reports.columns.transaction_number')],
            ['key' => 'voucher_number', 'label' => __('reports.columns.voucher_number')],
            ['key' => 'transaction_date', 'label' => __('reports.columns.date')],
            ['key' => 'transaction_type', 'label' => __('reports.columns.transaction_type')],
            ['key' => 'recipient_or_payer', 'label' => __('reports.columns.recipient_or_payer')],
            ['key' => 'amount', 'label' => __('reports.columns.amount')],
            ['key' => 'balance_after', 'label' => __('reports.columns.balance_after')],
            ['key' => 'executor_name', 'label' => __('reports.columns.executor_name')],
            ['key' => 'description', 'label' => __('reports.columns.description')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $typeLabel = $item->transaction_type instanceof \BackedEnum ? $item->transaction_type->getLabel() : $this->translateStatus($item->transaction_type);

        return [
            'transaction_number' => $item->transaction_number ?? '—',
            'voucher_number' => $item->voucher_number ?? '—',
            'transaction_date' => $item->transaction_date ? ($datePrefix . $item->transaction_date->format('Y-m-d')) : '—',
            'transaction_type' => $typeLabel,
            'recipient_or_payer' => $item->recipient_or_payer ?? '—',
            'amount' => number_format((float) ($item->amount ?? 0), 2) . __('reports.values.currency'),
            'balance_after' => number_format((float) ($item->balance_after ?? 0), 2) . __('reports.values.currency'),
            'executor_name' => $item->executor?->user?->name ?? '—',
            'description' => $item->description ?? '—',
        ];
    }
}
