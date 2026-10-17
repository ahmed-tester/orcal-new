<?php

namespace App\Services\Reports\Types;

use App\Enums\InstallmentStatus;
use App\Models\EmployeeDetail;

class GuarantorsReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'guarantors';
    }

    public function getTitle(): string
    {
        return __('reports.reports.guarantors.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.guarantors.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-shield-check';
    }

    public function getExportFilename(): string
    {
        return 'guarantors_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null, ?int $guarantorId = null): array
    {
        $query = EmployeeDetail::query()
            ->whereNotNull('guarantor_id')
            ->with(['employee.installments', 'employee.invoices', 'guarantor']);

        if (!empty($guarantorId)) {
            $query->where('guarantor_id', $guarantorId);
        }

        $this->applyCompanyScopeViaRelation($query, 'employee', 'company_id');
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'created_at');

        $records = $query->get();

        $totalGuarantors = $records->pluck('guarantor_id')->unique()->count();
        $totalGuaranteedStaff = $records->count();

        $totalPaidAmount = 0;
        $totalRemainingDebt = 0;
        $totalOverdueDebt = 0;

        foreach ($records as $record) {
            $emp = $record->employee;
            if ($emp) {
                if ($emp->installments) {
                    $totalPaidAmount += (float) $emp->installments->sum('paid_amount');
                    $totalRemainingDebt += (float) $emp->installments->sum('remaining_amount');
                    foreach ($emp->installments as $inst) {
                        if ($inst->status === InstallmentStatus::OVERDUE || ($inst->final_due_date && $inst->final_due_date->isPast() && (float) $inst->remaining_amount > 0)) {
                            $totalOverdueDebt += (float) $inst->remaining_amount;
                        }
                    }
                }
                if ($emp->invoices) {
                    $totalPaidAmount += (float) $emp->invoices->sum('paid_amount');
                    $totalRemainingDebt += (float) $emp->invoices->sum('remaining_amount');
                }
            }
        }

        $currency = __('reports.values.currency');

        return [
            [
                'title' => __('reports.stats.total_guaranteed_staff'),
                'value' => number_format($totalGuaranteedStaff),
                'subtitle' => __('reports.stats.total_guaranteed_staff_sub'),
                'icon' => 'heroicon-o-user-group',
                'color' => 'indigo',
            ],
            [
                'title' => __('reports.stats.guarantor_total_paid'),
                'value' => number_format($totalPaidAmount, 2) . $currency,
                'subtitle' => __('reports.stats.guarantor_total_paid_sub'),
                'icon' => 'heroicon-o-check-badge',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.guarantor_total_overdue'),
                'value' => number_format($totalOverdueDebt, 2) . $currency,
                'subtitle' => __('reports.stats.guarantor_total_overdue_sub'),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'red',
            ],
            [
                'title' => __('reports.stats.guarantor_remaining_debt'),
                'value' => number_format($totalRemainingDebt, 2) . $currency,
                'subtitle' => __('reports.stats.guarantor_remaining_debt_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search, ?int $guarantorId = null)
    {
        $query = EmployeeDetail::query()
            ->whereNotNull('guarantor_id')
            ->with([
                'guarantor.company',
                'employee.user',
                'employee.company',
                'employee.branch',
                'employee.jobTitle',
                'employee.installments.installmentType',
                'employee.invoices.invoiceType',
                'employee.invoices.items',
            ]);

        if (!empty($guarantorId)) {
            $query->where('guarantor_id', $guarantorId);
        }

        $this->applyCompanyScopeViaRelation($query, 'employee', 'company_id');
        $this->applyBranchScopeViaRelation($query, $branchId, 'employee');
        $this->applyDateFilter($query, $dateFrom, $dateTo, 'created_at');

        if ($status && $status !== 'all') {
            $query->whereHas('guarantor', fn ($gq) => $gq->where('status', $status));
        }

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->whereHas('guarantor', function ($gq) use ($search) {
                $gq->where('name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            })
            ->orWhereHas('employee.user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orWhereHas('employee', function ($eq) use ($search) {
                $eq->where('employee_number', 'like', "%{$search}%");
            })
            ->orWhereHas('employee.installments.installmentType', function ($tq) use ($search) {
                $tq->where('name', 'like', "%{$search}%");
            })
            ->orWhere('iqama_number', 'like', "%{$search}%")
            ->orWhere('national_id', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'guarantor_name' => 'guarantor_id',
            'employee_name' => 'employee_id',
            'iqama_number' => 'iqama_number',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'guarantor_name', 'label' => __('reports.columns.guarantor_name')],
            ['key' => 'guarantor_type', 'label' => __('reports.columns.guarantor_type')],
            ['key' => 'guarantor_phone', 'label' => __('reports.columns.guarantor_phone')],
            ['key' => 'guaranteed_employee', 'label' => __('reports.columns.guaranteed_employee')],
            ['key' => 'employee_number', 'label' => __('reports.columns.employee_number')],
            ['key' => 'iqama_number', 'label' => __('reports.columns.national_id')],
            ['key' => 'branch', 'label' => __('reports.columns.branch')],
            ['key' => 'basic_salary', 'label' => __('reports.columns.basic_salary')],
            ['key' => 'services_provided', 'label' => __('reports.columns.services_provided')],
            ['key' => 'total_installments', 'label' => __('reports.columns.total_installments')],
            ['key' => 'total_paid', 'label' => __('reports.columns.total_paid')],
            ['key' => 'remaining_installments', 'label' => __('reports.columns.remaining_installments')],
            ['key' => 'overdue_installments', 'label' => __('reports.columns.overdue_installments')],
            ['key' => 'guarantee_notes', 'label' => __('reports.columns.guarantee_notes')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $currency = __('reports.values.currency');

        $installments = $item->employee?->installments ?? collect();
        $invoices = $item->employee?->invoices ?? collect();

        // تجميع الخدمات والسلف المقدمة للمكفول
        $servicesList = collect();
        foreach ($installments as $inst) {
            if ($inst->installmentType?->name) {
                $servicesList->push($inst->installmentType->name);
            }
        }
        foreach ($invoices as $inv) {
            if ($inv->invoiceType?->name) {
                $servicesList->push($inv->invoiceType->name);
            }
            if ($inv->items) {
                foreach ($inv->items as $itemRow) {
                    if (!empty($itemRow->item_name)) {
                        $servicesList->push($itemRow->item_name);
                    }
                }
            }
        }
        $uniqueServices = $servicesList->filter()->unique()->values();
        $servicesText = $uniqueServices->isNotEmpty()
            ? $uniqueServices->join(' ، ')
            : '—';

        // الحسابات المالية (إجمالي، سدادات، ومتبقي)
        $totalAmount = (float) $installments->sum('total_amount') + (float) $invoices->sum('total_amount');
        $totalPaid = (float) $installments->sum('paid_amount') + (float) $invoices->sum('paid_amount');
        $remainingAmount = (float) $installments->sum('remaining_amount') + (float) $invoices->sum('remaining_amount');

        // التعثرات (المتأخرات القائمة)
        $overdueAmount = (float) $installments
            ->filter(function ($inst) {
                return $inst->status === InstallmentStatus::OVERDUE
                    || ($inst->final_due_date && $inst->final_due_date->isPast() && (float) $inst->remaining_amount > 0);
            })
            ->sum('remaining_amount');

        $guarantorType = $item->guarantor?->type === 'internal'
            ? __('guarantors.type_badges.internal')
            : __('guarantors.type_badges.external');

        $notes = (!empty($item->metadata['guarantee_reason']))
            ? $item->metadata['guarantee_reason']
            : ($item->guarantor?->notes ?? $item->sponsor_name ?? '—');

        $basicSalary = (float) ($item->employee?->basic_salary ?? 0);

        return [
            'guarantor_name' => $item->guarantor?->name ?? '—',
            'guarantor_type' => $guarantorType,
            'guarantor_phone' => $item->guarantor?->phone ?? '—',
            'guaranteed_employee' => $item->employee?->user?->name ?? '—',
            'employee_number' => $item->employee?->employee_number ?? '—',
            'iqama_number' => $item->iqama_number ?? $item->national_id ?? '—',
            'branch' => $item->employee?->branch?->name ?? '—',
            'basic_salary' => number_format($basicSalary, 2) . $currency,
            'services_provided' => $servicesText,
            'total_installments' => number_format($totalAmount, 2) . $currency,
            'total_paid' => number_format($totalPaid, 2) . $currency,
            'remaining_installments' => number_format($remainingAmount, 2) . $currency,
            'overdue_installments' => number_format($overdueAmount, 2) . $currency,
            'guarantee_notes' => $notes,
            'status' => $this->translateStatus($item->guarantor?->status ?? 'active'),
        ];
    }
}
