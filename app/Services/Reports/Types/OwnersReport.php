<?php

namespace App\Services\Reports\Types;

use App\Models\Owner;

class OwnersReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'owners';
    }

    public function getTitle(): string
    {
        return __('reports.reports.owners.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.owners.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-users';
    }

    public function getExportFilename(): string
    {
        return 'owners_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = Owner::query()
            ->with(['user', 'company']);

        // Authorization scoping — Owner has direct company_id
        $this->applyCompanyScope($query);

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhere('national_id', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'national_id' => 'national_id',
            'ownership_percentage' => 'ownership_percentage',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'owner_name', 'label' => __('reports.columns.owner_name')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'national_id', 'label' => __('reports.columns.national_id')],
            ['key' => 'ownership_percentage', 'label' => __('reports.columns.ownership_percentage')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'owner_name' => $item->user?->name ?? '—',
            'company_name' => $item->company?->trade_name ?? '—',
            'national_id' => $item->national_id ?? '—',
            'ownership_percentage' => $item->ownership_percentage ? $item->ownership_percentage . '%' : '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
