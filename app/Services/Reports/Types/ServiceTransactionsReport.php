<?php

namespace App\Services\Reports\Types;

use App\Models\ServiceTransaction;

class ServiceTransactionsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'service_transactions';
    }

    public function getTitle(): string
    {
        return __('services_tracking.plural');
    }

    public function getLabel(): string
    {
        return __('services_tracking.plural');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-briefcase';
    }

    public function getExportFilename(): string
    {
        return 'service_transactions_report';
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = ServiceTransaction::query()
            ->with(['company', 'employee.user', 'service', 'executor.user']);

        $this->applyCompanyScope($query);

        if ($dateFrom) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        if ($status && $status !== 'all') {
            $query->where('service_status', $status);
        }

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('transaction_number', 'like', "%{$search}%")
                ->orWhere('service_name', 'like', "%{$search}%")
                ->orWhere('beneficiary_name', 'like', "%{$search}%")
                ->orWhereHas('service', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id'                 => 'id',
            'transaction_number' => 'transaction_number',
            'transaction_date'   => 'transaction_date',
            'total_amount'       => 'total_amount',
            'paid_amount'        => 'paid_amount',
            'remaining_amount'   => 'remaining_amount',
            'service_status'     => 'service_status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'transaction_number', 'label' => __('services_tracking.fields.transaction_number')],
            ['key' => 'scope_type',         'label' => __('services_tracking.fields.scope_type')],
            ['key' => 'service_name',       'label' => __('services_tracking.fields.service')],
            ['key' => 'beneficiary_name',   'label' => __('services_tracking.fields.beneficiary_name')],
            ['key' => 'company_name',       'label' => __('services_tracking.fields.company')],
            ['key' => 'total_amount',       'label' => __('services_tracking.fields.total_amount')],
            ['key' => 'paid_amount',        'label' => __('services_tracking.fields.paid_amount')],
            ['key' => 'remaining_amount',   'label' => __('services_tracking.fields.remaining_amount')],
            ['key' => 'service_status',     'label' => __('services_tracking.fields.service_status')],
            ['key' => 'transaction_date',   'label' => __('services_tracking.fields.transaction_date')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        return [
            'transaction_number' => $item->transaction_number,
            'scope_type'         => $item->scope_type === 'external' ? __('services_tracking.badges.external') : __('services_tracking.badges.internal'),
            'service_name'       => $item->display_service_name,
            'beneficiary_name'   => $item->display_beneficiary,
            'company_name'       => $item->company?->trade_name ?? '—',
            'total_amount'       => number_format((float) ($item->total_amount ?? 0), 2),
            'paid_amount'        => number_format((float) ($item->paid_amount ?? 0), 2),
            'remaining_amount'   => number_format((float) ($item->remaining_amount ?? 0), 2),
            'service_status'     => match ($item->service_status) {
                'completed' => __('services_tracking.badges.completed'),
                'inactive'  => __('services_tracking.badges.inactive'),
                default     => __('services_tracking.badges.active'),
            },
            'transaction_date'   => $item->transaction_date?->format('Y-m-d') ?? '—',
        ];
    }
}
