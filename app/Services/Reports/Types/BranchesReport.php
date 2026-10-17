<?php

namespace App\Services\Reports\Types;

use App\Models\Branch;

class BranchesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'branches';
    }

    public function getTitle(): string
    {
        return __('reports.reports.branches.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.branches.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-building-storefront';
    }

    public function getExportFilename(): string
    {
        return 'branches_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Branch::query()
            ->with(['company']);

        // Authorization scoping — Branch has direct company_id
        $this->applyCompanyScope($query);

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('branch_code', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'branch_code' => 'branch_code',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'branch_code', 'label' => __('reports.columns.branch_code')],
            ['key' => 'name', 'label' => __('reports.columns.branch_name')],
            ['key' => 'phone', 'label' => __('reports.columns.phone')],
            ['key' => 'email', 'label' => __('reports.columns.email')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $phonePrefix = $isCsv ? "\t" : "";

        return [
            'id' => $item->id,
            'branch_code' => $item->branch_code ?? '—',
            'name' => $item->name,
            'phone' => $item->phone ? ($phonePrefix . $item->phone) : '—',
            'email' => $item->email ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
