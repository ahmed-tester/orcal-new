<?php

namespace App\Services\Reports\Types;

use App\Models\PosDevice;

class PosDevicesReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'pos_devices';
    }

    public function getTitle(): string
    {
        return __('reports.reports.pos_devices.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.pos_devices.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public function getExportFilename(): string
    {
        return 'pos_devices_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $query = PosDevice::query();
        $this->applyCompanyScope($query);

        $totalDevices = (clone $query)->count();
        $activeDevices = (clone $query)->where('status', 'active')->count();

        return [
            [
                'title' => __('reports.stats.total_pos_devices'),
                'value' => $totalDevices,
                'subtitle' => __('reports.stats.total_pos_devices_sub'),
                'icon' => 'heroicon-o-calculator',
                'color' => 'blue',
            ],
            [
                'title' => __('reports.stats.active_pos_devices'),
                'value' => $activeDevices,
                'subtitle' => __('reports.stats.active_pos_devices_sub'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = PosDevice::query()
            ->with(['company', 'owner', 'accountant.user']);

        $this->applyCompanyScope($query);
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('device_number', 'like', "%{$search}%")
                ->orWhere('card_number', 'like', "%{$search}%")
                ->orWhere('bank_or_wallet_name', 'like', "%{$search}%")
                ->orWhere('account_number', 'like', "%{$search}%")
                ->orWhere('accountant_name', 'like', "%{$search}%")
                ->orWhere('accountant_email', 'like', "%{$search}%")
                ->orWhere('accountant_phone', 'like', "%{$search}%")
                ->orWhere('commercial_registration_number', 'like', "%{$search}%")
                ->orWhereHas('accountant.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'device_number' => 'device_number',
            'commercial_registration_number' => 'commercial_registration_number',
            'card_number' => 'card_number',
            'bank_or_wallet_name' => 'bank_or_wallet_name',
            'account_number' => 'account_number',
            'accountant_name' => 'accountant_name',
            'status' => 'status',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'device_number', 'label' => __('reports.columns.device_number')],
            ['key' => 'company_name', 'label' => __('reports.columns.company_name')],
            ['key' => 'commercial_reg', 'label' => __('reports.columns.commercial_reg')],
            ['key' => 'card_number', 'label' => __('reports.columns.card_number')],
            ['key' => 'bank_or_wallet_name', 'label' => __('reports.columns.bank_or_wallet_name')],
            ['key' => 'account_number', 'label' => __('reports.columns.account_number')],
            ['key' => 'accountant_name', 'label' => __('reports.columns.accountant_name')],
            ['key' => 'accountant_phone', 'label' => __('reports.columns.accountant_phone')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $phonePrefix = $isCsv ? "\t" : "";
        $statusLabel = $item->status instanceof \BackedEnum ? $item->status->getLabel() : $this->translateStatus($item->status);
        $accountantPhone = $item->accountant?->user?->phone ?? $item->accountant_phone;
        $accountantName = $item->accountant?->user?->name ?? $item->accountant_name;

        return [
            'device_number' => $item->device_number ?? '—',
            'company_name' => $item->company?->trade_name ?? $item->company?->name ?? '—',
            'commercial_reg' => $item->commercial_registration_number ?? '—',
            'card_number' => $item->card_number ?? '—',
            'bank_or_wallet_name' => $item->bank_or_wallet_name ?? '—',
            'account_number' => $item->account_number ?? '—',
            'accountant_name' => $accountantName ?? '—',
            'accountant_phone' => $accountantPhone ? ($phonePrefix . $accountantPhone) : '—',
            'status' => $statusLabel,
        ];
    }
}
