<?php

namespace App\Services\Reports\Types;

use App\Models\InvoiceType;

class InvoiceTypesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'invoice_types';
    }

    public function getTitle(): string
    {
        return __('reports.reports.invoice_types.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.invoice_types.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-folder';
    }

    public function getExportFilename(): string
    {
        return 'invoice_types_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = InvoiceType::query()
            ->with(['company']);

        // Authorization scoping — InvoiceType has direct company_id
        $this->applyCompanyScope($query);

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'name', 'label' => __('reports.columns.invoice_type')],
            ['key' => 'code', 'label' => __('reports.columns.classification_code')],
            ['key' => 'company_name', 'label' => __('reports.columns.issuing_company')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code ?? '—',
            'company_name' => $item->company?->trade_name ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
