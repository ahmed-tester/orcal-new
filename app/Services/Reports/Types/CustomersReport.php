<?php

namespace App\Services\Reports\Types;

use App\Models\Customer;

class CustomersReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'customers';
    }

    public function getTitle(): string
    {
        return __('reports.reports.customers.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.customers.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public function getExportFilename(): string
    {
        return 'customers_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Customer::query();

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('national_id', 'like', "%{$search}%")
                ->orWhere('commercial_registration_number', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'national_id' => 'national_id',
            'commercial_registration_number' => 'commercial_registration_number',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.customer_id')],
            ['key' => 'name', 'label' => __('reports.columns.customer_company_name')],
            ['key' => 'customer_type', 'label' => __('reports.columns.customer_type')],
            ['key' => 'national_id', 'label' => __('reports.columns.national_id')],
            ['key' => 'commercial_registration_number', 'label' => __('reports.columns.commercial_reg')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name ?? '—',
            'customer_type' => $item->customer_type instanceof \BackedEnum ? $item->customer_type->value : ($item->customer_type ?? '—'),
            'national_id' => $item->national_id ?? '—',
            'commercial_registration_number' => $item->commercial_registration_number ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
