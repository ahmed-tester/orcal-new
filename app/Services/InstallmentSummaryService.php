<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\InstallmentPayment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InstallmentSummaryService
{
    /**
     * Shared grouping expression so SELECT / GROUP BY / WHERE stay consistent
     * under MySQL only_full_group_by: the external beneficiary key is the
     * national id when present, otherwise the name.
     */
    protected const EXTERNAL_KEY_SQL = 'COALESCE(external_national_id, external_name)';

    /**
     * One aggregated row per beneficiary (internal employee or external person).
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        $query = Installment::query()
            ->selectRaw(implode(', ', [
                'MIN(id) as row_id',
                'beneficiary_type',
                'employee_id',
                self::EXTERNAL_KEY_SQL.' as external_key',
                'MAX(external_name) as group_name',
                'MAX(external_national_id) as group_national_id',
                'MAX(company_id) as company_id',
                'COUNT(*) as installments_count',
                'SUM(total_amount) as total_sum',
                'SUM(paid_amount) as paid_sum',
                'GREATEST(0, SUM(COALESCE(total_amount, 0)) - SUM(COALESCE(paid_amount, 0)) - SUM(COALESCE(down_payment, 0))) as remaining_sum',
            ]))
            ->groupByRaw('beneficiary_type, employee_id, '.self::EXTERNAL_KEY_SQL)
            ->with(['employee.user', 'employee.details', 'company']);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    /**
     * Totals for the stats cards, computed over every group of the current filters.
     */
    public static function getStats(array $filters = []): array
    {
        $rows = self::summaryQuery($filters)->get();

        return [
            'total' => round((float) $rows->sum('total_sum'), 2),
            'beneficiaries' => $rows->count(),
            'installments' => (int) $rows->sum('installments_count'),
            'paid' => round((float) $rows->sum('paid_sum'), 2),
            'remaining' => round((float) $rows->sum('remaining_sum'), 2),
        ];
    }

    /**
     * Modal payload: beneficiary header info + all of his installments and payments.
     *
     * When $filters is provided the detail query honours the same search /
     * company / branch / transaction-type / beneficiary-type / status
     * criteria used by the summary list so numbers stay consistent.
     */
    public static function getBeneficiaryDetails(string $key, array $filters = []): ?array
    {
        $decoded = self::decodeBeneficiaryKey($key);

        if ($decoded === null) {
            return null;
        }

        [$type, $value] = $decoded;

        $query = Installment::query()
            ->with(['installmentType', 'company', 'employee.user', 'employee.details', 'executor.user'])
            ->where('beneficiary_type', $type);

        $query = $type === 'external'
            ? $query->whereRaw(self::EXTERNAL_KEY_SQL.' = ?', [$value])
            : $query->where('employee_id', (int) $value);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        $installments = $query->orderBy('installment_date')->orderBy('id')->get();

        if ($installments->isEmpty()) {
            return null;
        }

        $head = $installments->first();

        // Also fetch installment payments linked to these installments
        $installmentIds = $installments->pluck('id')->filter()->values()->all();

        $payments = ! empty($installmentIds)
            ? InstallmentPayment::query()
                ->with('executor.user')
                ->whereIn('installment_id', $installmentIds)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get()
            : collect();

        $transactions = collect();

        foreach ($installments as $inst) {
            $categoryName = $inst->installmentType?->name;
            $note = $inst->description;
            $combinedDesc = $categoryName
                ? ($note ? $categoryName.' — '.$note : $categoryName)
                : ($note ?? '—');

            $transactions->push([
                'type' => 'installment',
                'id' => $inst->id,
                'number' => $inst->installment_number,
                'date' => $inst->installment_date,
                'due_date' => $inst->final_due_date ?? $inst->start_due_date ?? $inst->installment_date,
                'item_type' => $inst->transaction_type === 'advance'
                    ? __('finance.installments.types.advance')
                    : __('finance.installments.types.installment'),
                'description' => $combinedDesc,
                'charges_amount' => (float) $inst->total_amount,
                'paid_amount' => 0.0,
                'operator_name' => $inst->executor?->user?->name,
                'status' => ((float) $inst->remaining_amount <= 0 && (float) $inst->total_amount > 0) ? 'settled' : 'pending',
                'is_approved' => true,
            ]);
        }

        foreach ($payments as $pay) {
            $transactions->push([
                'type' => 'payment',
                'id' => $pay->id,
                'number' => $pay->voucher_number ?? ('PAY-'.$pay->id),
                'date' => $pay->payment_date,
                'due_date' => null,
                'item_type' => __('finance.installments.summary.modal.type_payment'),
                'description' => $pay->description ?? '—',
                'charges_amount' => 0.0,
                'paid_amount' => (float) $pay->amount,
                'operator_name' => $pay->executor?->user?->name,
                'status' => 'payment',
                'is_approved' => $pay->isApproved(),
            ]);
        }

        $transactions = $transactions->sortBy(function ($t) {
            $d = $t['date'];
            if ($d instanceof CarbonInterface) {
                return $d->format('Y-m-d');
            }

            return ! empty($d) ? (string) $d : '9999-12-31';
        })->values();

        $totalCharges = round((float) $installments->sum('total_amount'), 2);
        $paymentsSum = round((float) $payments->sum('amount'), 2);
        $installmentsPaidSum = round((float) $installments->sum('paid_amount'), 2);
        $totalPaid = $payments->isNotEmpty() ? $paymentsSum : $installmentsPaidSum;
        $totalDown = round((float) $installments->sum('down_payment'), 2);
        $netRemaining = max(0.0, round($totalCharges - $totalPaid - $totalDown, 2));

        return [
            'name' => self::beneficiaryName($head),
            'national_id' => self::beneficiaryNationalId($head),
            'company' => self::beneficiaryCompany($head),
            'count' => $installments->count(),
            'payments_count' => $payments->count(),
            'total' => $totalCharges,
            'paid' => $totalPaid,
            'remaining' => $netRemaining,
            'totals' => [
                'charges' => $totalCharges,
                'paid' => $totalPaid,
                'remaining' => $netRemaining,
            ],
            'transactions' => $transactions,
        ];
    }

    /**
     * Stable beneficiary key of a single installment:
     * "internal|{employee_id}" or "external|{national_id_or_name}".
     */
    public static function beneficiaryKeyForInstallment(Installment $installment): string
    {
        if ($installment->isExternal()) {
            return 'external|'.($installment->external_national_id ?? $installment->external_name ?? 'unknown');
        }

        return 'internal|'.(string) $installment->employee_id;
    }

    /**
     * Unpaginated summary rows for the CSV export.
     */
    public static function exportRows(array $filters = []): Collection
    {
        return self::summaryQuery($filters)
            ->orderBy('remaining_sum', 'desc')
            ->orderBy('row_id')
            ->get();
    }

    public static function beneficiaryName(Installment $row): ?string
    {
        if ($row->isExternal()) {
            return $row->group_name ?? $row->external_name;
        }

        return $row->employee?->user?->name;
    }

    public static function beneficiaryNationalId(Installment $row): ?string
    {
        if ($row->isExternal()) {
            return $row->group_national_id ?? $row->external_national_id;
        }

        $details = $row->employee?->details;

        return $details?->national_id ?: $details?->iqama_number;
    }

    public static function beneficiaryCompany(Installment $row): ?string
    {
        return $row->company?->trade_name;
    }

    /**
     * Company/branch access scope — mirrors HasCompanyScope semantics for the
     * installments table (super admin: all, owner: owned companies, restricted
     * branch user: his branch via the employee, staff: accessible companies).
     */
    protected static function applyAccessScope(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isOwner()) {
            return $query->whereIn('company_id', $user->getOwnedCompanyIds());
        }

        if ($user->branch_id && ! $user->canAccessAllBranches()) {
            return $query->whereHas('employee', fn (Builder $q) => $q->where('branch_id', $user->branch_id));
        }

        $accessibleIds = $user->getAccessibleCompanyIds();

        if (! empty($accessibleIds)) {
            $query->whereIn('company_id', $accessibleIds);
        }

        return $query;
    }

    protected static function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $term = '%'.trim((string) $filters['search']).'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('external_name', 'like', $term)
                    ->orWhere('external_national_id', 'like', $term)
                    ->orWhere('external_phone', 'like', $term)
                    ->orWhereHas('employee.user', fn (Builder $u) => $u->where('name', 'like', $term))
                    ->orWhereHas('employee', fn (Builder $e) => $e->where('employee_number', 'like', $term))
                    ->orWhereHas('employee.details', fn (Builder $d) => $d
                        ->where('national_id', 'like', $term)
                        ->orWhere('iqama_number', 'like', $term));
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->whereHas('employee', fn (Builder $e) => $e->where('branch_id', (int) $filters['branch_id']));
        }

        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (! empty($filters['beneficiary_type'])) {
            $query->where('beneficiary_type', $filters['beneficiary_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected static function decodeBeneficiaryKey(string $key): ?array
    {
        $parts = explode('|', $key, 2);

        if (count($parts) !== 2 || $parts[1] === '') {
            return null;
        }

        if (! in_array($parts[0], ['internal', 'external'], true)) {
            return null;
        }

        return $parts;
    }
}