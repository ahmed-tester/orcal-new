<?php

namespace App\Services\Reports\Types;

use App\Models\PaymentCollectionItem;

class PaymentCollectionItemsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'payment_collection_items';
    }

    public function getTitle(): string
    {
        return __('reports.reports.payment_collection_items.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.payment_collection_items.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-queue-list';
    }

    public function getExportFilename(): string
    {
        return 'payment_collection_items_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = PaymentCollectionItem::query();
        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $total = (clone $query)->count();

        return [
            [
                'title' => __('reports.stats.total_collection_items'),
                'value' => $total,
                'subtitle' => __('reports.stats.total_collection_items_sub'),
                'icon' => 'heroicon-o-queue-list',
                'color' => 'blue',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = PaymentCollectionItem::query()
            ->with(['company'])
            ->withCount('treasuryTransactions');

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('transaction_number', 'like', "%{$search}%")
                ->orWhere('sub_item', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'transaction_number' => 'transaction_number',
            'sub_item' => 'sub_item',
            'service_price' => 'service_price',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'transaction_number', 'label' => __('reports.columns.classification_code')],
            ['key' => 'main_item', 'label' => __('reports.columns.violation_category')],
            ['key' => 'sub_item', 'label' => __('reports.columns.service_name')],
            ['key' => 'company', 'label' => __('reports.columns.company_name')],
            ['key' => 'service_price', 'label' => __('reports.columns.service_price')],
            ['key' => 'usage_count', 'label' => __('reports.columns.employees_count')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'transaction_number' => $item->transaction_number ?? '—',
            'main_item' => $this->translateStatus($item->main_item),
            'sub_item' => $item->sub_item ?? '—',
            'company' => $item->company?->trade_name ?? '—',
            'service_price' => number_format((float)$item->service_price, 2) . ($isCsv ? "" : __('reports.values.currency')),
            'usage_count' => (string)($item->treasury_transactions_count ?? 0),
        ];
    }
}
