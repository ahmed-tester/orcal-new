<?php

namespace App\Services\Reports\Types;

use App\Models\JobTitle;

class JobTitlesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'job_titles';
    }

    public function getTitle(): string
    {
        return __('reports.reports.job_titles.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.job_titles.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-academic-cap';
    }

    public function getExportFilename(): string
    {
        return 'job_titles_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = JobTitle::query()
            ->with(['company', 'department']);

        // Authorization scoping — JobTitle has direct company_id
        $this->applyCompanyScope($query);

        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('job_code', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'job_code' => 'job_code',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'job_code', 'label' => __('reports.columns.job_code')],
            ['key' => 'name', 'label' => __('reports.columns.job_title')],
            ['key' => 'department_name', 'label' => __('reports.columns.department_name')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'job_code' => $item->job_code ?? '—',
            'name' => $item->name,
            'department_name' => $item->department?->name ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
