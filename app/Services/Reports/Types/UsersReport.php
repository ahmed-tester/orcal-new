<?php

namespace App\Services\Reports\Types;

use App\Models\User;

class UsersReport extends AbstractReport
{
    public function getKey(): string
    {
        return 'users';
    }

    public function getTitle(): string
    {
        return __('reports.reports.users.title');
    }

    public function getLabel(): string
    {
        return __('reports.reports.users.label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-users';
    }

    public function getExportFilename(): string
    {
        return 'users_report';
    }

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        $companyIds = $this->getAuthCompanyIds();
        $authBranchId = $this->getAuthBranchId();

        // Scoped employee count
        $employeeCount = \App\Models\Employee::query()
            ->when($companyIds !== null, fn($q) => $q->whereIn('company_id', $companyIds))
            ->when($authBranchId, fn($q) => $q->where('branch_id', $authBranchId))
            ->count();

        // Scoped pending leaves count
        $pendingLeaves = \App\Models\Leave::query()
            ->where('status', 'pending')
            ->when($companyIds !== null || $authBranchId, function ($q) use ($companyIds, $authBranchId) {
                $q->whereHas('employee', function ($eq) use ($companyIds, $authBranchId) {
                    $eq->when($companyIds !== null, fn($q2) => $q2->whereIn('company_id', $companyIds))
                       ->when($authBranchId, fn($q2) => $q2->where('branch_id', $authBranchId));
                });
            })
            ->count();

        // Scoped unpaid invoices amount
        $unpaidAmount = \App\Models\Invoice::query()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->when($companyIds !== null, fn($q) => $q->whereIn('company_id', $companyIds))
            ->when($authBranchId, fn($q) => $q->where('branch_id', $authBranchId))
            ->sum('remaining_amount');

        return [
            [
                'title' => __('reports.reports.employees.title'),
                'value' => $employeeCount,
                'subtitle' => __('reports.stats.total_employees'),
                'icon' => 'heroicon-o-user-group',
                'color' => 'green',
            ],
            [
                'title' => __('reports.stats.pending_leaves'),
                'value' => $pendingLeaves,
                'subtitle' => __('reports.stats.pending_leaves_sub'),
                'icon' => 'heroicon-o-clock',
                'color' => 'amber',
            ],
            [
                'title' => __('reports.stats.unpaid_invoices'),
                'value' => number_format((float) $unpaidAmount, 2) . __('reports.values.currency'),
                'subtitle' => __('reports.stats.unpaid_invoices_sub'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'red',
            ],
        ];
    }

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search)
    {
        $query = User::query()
            ->with(['employee.branch'])
            ->whereNotIn('user_type', [\App\Enums\UserType::SUPER_ADMIN])
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'));

        // Authorization scoping — User → employee → company/branch
        $this->applyCompanyScopeViaRelation($query, 'employee');
        $this->applyBranchScopeViaRelation($query, $branchId);

        $this->applyDateFilter($query, $dateFrom, $dateTo, 'created_at');
        $this->applyStatusFilter($query, $status);

        $this->applySearchFilter($query, $search, function ($sub, $search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });

        return $query->orderByDesc('id');
    }

    public function getSortableColumns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'status' => 'status',
            'created_at' => 'created_at',
        ];
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'id', 'label' => __('reports.columns.id')],
            ['key' => 'name', 'label' => __('reports.columns.manager_name')],
            ['key' => 'email', 'label' => __('reports.columns.email')],
            ['key' => 'phone', 'label' => __('reports.columns.phone')],
            ['key' => 'user_type', 'label' => __('reports.columns.user_type')],
            ['key' => 'branch', 'label' => __('reports.columns.branch')],
            ['key' => 'status', 'label' => __('reports.columns.status')],
            ['key' => 'created_at', 'label' => __('reports.columns.created_at')],
        ];
    }

    public function formatRow($item, bool $isCsv = false): array
    {
        $phonePrefix = $isCsv ? "\t" : "";
        $datePrefix = $isCsv ? "\t" : "";

        return [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'phone' => $item->phone ? ($phonePrefix . $item->phone) : '—',
            'user_type' => $item->user_type instanceof \BackedEnum ? $item->user_type->value : ($item->user_type ?? __('reports.values.user')),
            'branch' => $item->employee?->branch?->name ?? '—',
            'status' => $this->translateStatus($item->status ?? 'active'),
            'created_at' => $item->created_at ? ($datePrefix . $item->created_at->format('Y-m-d H:i')) : '—',
        ];
    }
}
