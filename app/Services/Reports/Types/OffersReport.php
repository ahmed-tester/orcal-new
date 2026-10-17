<?php

namespace App\Services\Reports\Types;

use App\Models\Offer;

class OffersReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'offers';
    }

    public function getTitle(): string
    {
        return __('reports.reports.offers.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.offers.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-duplicate';
    }

    public function getExportFilename(): string
    {
        return 'offers_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        $query = Offer::query();
        $this->applyCompanyScope($query);

        $stats = $query
            ->when($dateFrom, fn($q) => $q->whereDate('offer_date', '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate('offer_date', '<=', $dateToCarbon))
            ->selectRaw("
                COUNT(*) as total_offers,
                COALESCE(SUM(CASE WHEN status = 'approved' OR status = 'accepted' THEN 1 ELSE 0 END), 0) as approved_count,
                COALESCE(SUM(CASE WHEN status = 'draft' OR status = 'sent' OR status = 'under_review' THEN 1 ELSE 0 END), 0) as pending_count
            ")
            ->first();

        return [
            [
                'title' => __('reports.stats.total_offers'),
                'value' => $stats->total_offers ?? 0,
                'subtitle' => __('reports.stats.total_offers_sub'),
                'icon' => 'heroicon-o-document-text',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.approved_offers'),
                'value' => $stats->approved_count ?? 0,
                'subtitle' => __('reports.stats.approved_offers_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.pending_offers'),
                'value' => $stats->pending_count ?? 0,
                'subtitle' => __('reports.stats.pending_offers_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Offer::query()
            ->with(['company', 'customer', 'manager.user', 'supervisor.user']);

        $this->applyCompanyScope($query);
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'offer_date');
        $this->applyStatusFilter($query, $status, 'status');

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('offer_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'offer_number' => 'offer_number',
            'offer_date' => 'offer_date',
            'valid_until' => 'valid_until',
            'offer_type' => 'offer_type',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'offer_number', 'label' => __('reports.columns.offer_number')],
            ['key' => 'customer_name', 'label' => __('reports.columns.customer_name')],
            ['key' => 'offer_type', 'label' => __('reports.columns.offer_type')],
            ['key' => 'subject', 'label' => __('reports.columns.subject')],
            ['key' => 'pricing_type', 'label' => __('reports.columns.pricing_type')],
            ['key' => 'offer_date', 'label' => __('reports.columns.date')],
            ['key' => 'valid_until', 'label' => __('reports.columns.valid_until')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $datePrefix = $isCsv ? "\t" : "";
        $statusLabel = $item->status instanceof \BackedEnum ? $item->status->getLabel() : $this->translateStatus($item->status);
        $typeLabel = $item->offer_type instanceof \BackedEnum ? $item->offer_type->getLabel() : $this->translateStatus($item->offer_type);
        $pricingLabel = $item->pricing_type instanceof \BackedEnum ? $item->pricing_type->getLabel() : $this->translateStatus($item->pricing_type);

        return [
            'offer_number' => $item->offer_number ?? '—',
            'customer_name' => $item->customer?->name ?? '—',
            'offer_type' => $typeLabel,
            'subject' => $item->subject ?? '—',
            'pricing_type' => $pricingLabel,
            'offer_date' => $item->offer_date ? ($datePrefix . $item->offer_date->format('Y-m-d')) : '—',
            'valid_until' => $item->valid_until ? ($datePrefix . $item->valid_until->format('Y-m-d')) : '—',
            'status' => $statusLabel,
        ];
    }
}
