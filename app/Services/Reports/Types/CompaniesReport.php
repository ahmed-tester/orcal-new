<?php

namespace App\Services\Reports\Types;

use App\Models\Company;

class CompaniesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'companies';
    }

    public function getTitle(): string
    {
        return __('reports.reports.companies.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.companies.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-building-office';
    }

    public function getExportFilename(): string
    {
        return 'companies_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Company::query();

        // Authorization scoping — Company itself is the entity, scope by 'id'
        $this->applyCompanyScope($query, 'id');

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('trade_name', 'like', "%{$search}%")
                ->orWhere('company_number', 'like', "%{$search}%")
                ->orWhere('commercial_registration_number', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'trade_name' => 'trade_name',
            'commercial_registration_number' => 'commercial_registration_number',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'trade_name', 'label' => __('reports.columns.trade_name')],
            ['key' => 'company_number', 'label' => __('reports.columns.company_number')],
            ['key' => 'commercial_registration_number', 'label' => __('reports.columns.commercial_reg')],
            ['key' => 'phone', 'label' => __('reports.columns.phone')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $phonePrefix = $isCsv ? "\t" : "";

        return [
            'id' => $item->id,
            'trade_name' => $item->trade_name ?? '—',
            'company_number' => $item->company_number ?? '—',
            'commercial_registration_number' => $item->commercial_registration_number ?? '—',
            'phone' => $item->phone ? ($phonePrefix . $item->phone) : '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
