<?php

namespace App\Services\Reports\Types;

use App\Models\Service;

class ServicesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'services';
    }

    public function getTitle(): string
    {
        return __('reports.reports.services.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.services.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document';
    }

    public function getExportFilename(): string
    {
        return 'services_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Service::query()
            ->with(['company']);

        // Authorization scoping — Service has direct company_id
        $this->applyCompanyScope($query);

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'price' => 'price',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'name', 'label' => __('reports.columns.service_name')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'price', 'label' => __('reports.columns.service_price')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'company_name' => $item->company?->trade_name ?? '—',
            'price' => number_format((float) ($item->price ?? 0), 2),
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
