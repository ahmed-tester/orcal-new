<?php

namespace App\Services\Reports\Types;

use App\Models\LeaveType;

class LeaveTypesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'leave_types';
    }

    public function getTitle(): string
    {
        return __('reports.reports.leave_types.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.leave_types.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-calendar';
    }

    public function getExportFilename(): string
    {
        return 'leave_types_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = LeaveType::query();
            // ->with(['company']);

        // Authorization scoping — LeaveType was per-company
        // $this->applyCompanyScope($query);

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
            'annual_balance' => 'annual_balance',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'name', 'label' => __('reports.columns.leave_type')],
            ['key' => 'annual_balance', 'label' => __('reports.columns.annual_balance')],
            ['key' => 'is_paid', 'label' => __('reports.columns.is_paid')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'annual_balance' => $item->annual_balance ?? 0,
            'is_paid' => $item->is_paid ? __('reports.values.yes') : __('reports.values.no'),
            'status' => $this->translateStatus($item->status ?? 'active'),
        ];
    }
}
