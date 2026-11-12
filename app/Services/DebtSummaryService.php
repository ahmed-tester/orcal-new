<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DebtSummaryService
{
    public const TOTALS = ['total_sum', 'paid_sum', 'remaining_sum', 'overdue_sum'];

    private const GROUP_COLUMNS = "company_id, debtor_type,
        CASE WHEN debtor_type = 'internal' THEN employee_id END,
        CASE WHEN debtor_type = 'external' THEN NULLIF(TRIM(external_national_id), '') END,
        CASE WHEN (debtor_type = 'internal' AND employee_id IS NOT NULL)
            OR (debtor_type = 'external' AND NULLIF(TRIM(external_national_id), '') IS NOT NULL)
            THEN NULL ELSE id END";

    private const OVERDUE = 'remaining_amount > 0 AND due_date < ?';

    public static function debts(): Builder
    {
        $query = CompanyAccessScopeService::apply(Debt::query());
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && $user->branch_id && ! $user->canAccessAllBranches()) {
            $query->whereIn('debts.company_id', CompanyFormService::getAuthorizedCompanyIds($user));
        }

        return $query;
    }

    public static function collections(): Builder
    {
        return CompanyAccessScopeService::apply(DebtCollection::query())
            ->whereIn('debt_id', self::debts()->select('debts.id'));
    }

    public static function summaryQuery(array $filters): Builder
    {
        return Debt::query()
            ->joinSub(self::grouped($filters), 'summary', 'debts.id', '=', 'summary.row_id')
            ->select('debts.*', 'summary.debt_count', ...array_map(fn ($key) => "summary.{$key}", self::TOTALS))
            ->with(['company', 'employee.user'])
            ->orderByDesc('summary.remaining_sum')
            ->orderBy('debts.id');
    }

    public static function stats(array $filters): object
    {
        return DB::query()->fromSub(self::grouped($filters), 'summary')
            ->selectRaw('COUNT(*) AS debtors')
            ->selectRaw('COALESCE(SUM(debt_count), 0) AS debt_count')
            ->selectRaw(implode(', ', array_map(fn ($key) => "COALESCE(SUM({$key}), 0) AS {$key}", self::TOTALS)))
            ->first();
    }

    public static function details(int $id, array $filters): array
    {
        $query = self::filtered($filters);
        $debtor = (clone $query)->with(['company', 'employee.user'])->find($id);
        abort_if($debtor === null, 404);
        $query->where('company_id', $debtor->company_id)->where('debtor_type', $debtor->debtor_type);

        if ($debtor->isExternal() && trim((string) $debtor->external_national_id, ' ') !== '') {
            $query->whereRaw('TRIM(external_national_id) = ?', [trim($debtor->external_national_id, ' ')]);
        } elseif ($debtor->debtor_type === 'internal' && $debtor->employee_id !== null) {
            $query->where('employee_id', $debtor->employee_id);
        } else {
            $query->whereKey($debtor->id);
        }

        $showCollections = Gate::allows('viewAny', DebtCollection::class);
        if ($showCollections) {
            $query->with(['collections' => fn ($relation) => $relation
                ->whereIn('debt_collections.id', self::collections()->select('debt_collections.id'))
                ->orderByDesc('collection_date')->orderByDesc('id')]);
        }

        return [
            'debtor' => $debtor,
            'debts' => $query->orderByDesc('debt_date')->orderByDesc('id')->get(),
            'showCollections' => $showCollections,
        ];
    }

    public static function isOverdue(Debt $debt): bool
    {
        return $debt->remaining_amount > 0 && $debt->due_date?->lt(today());
    }

    private static function grouped(array $filters): Builder
    {
        return self::filtered($filters)
            ->selectRaw('MIN(id) AS row_id, COUNT(*) AS debt_count, SUM(total_amount) AS total_sum,
                SUM(paid_amount) AS paid_sum, SUM(remaining_amount) AS remaining_sum')
            ->selectRaw('SUM(CASE WHEN '.self::OVERDUE.' THEN remaining_amount ELSE 0 END) AS overdue_sum', [today()->toDateString()])
            ->groupByRaw(self::GROUP_COLUMNS);
    }

    private static function filtered(array $filters): Builder
    {
        $query = FilterBarService::applyFilters(self::debts(), $filters, [
            'company' => true,
            'branch' => true,
            'branch_column' => 'employee.branch_id',
            'date_column' => 'debt_date',
            'search_columns' => ['debt_number', 'external_name', 'external_national_id', 'external_phone', 'employee.user.name', 'company.trade_name'],
            'where_in' => ['debtor_type' => $filters['debtor_type'] ?? null],
        ]);

        $statuses = FilterBarService::values($filters['status'] ?? null, ['all']);

        if (! empty($statuses)) {
            $query->where(function (Builder $group) use ($statuses) {
                foreach ($statuses as $status) {
                    $group->orWhere(function (Builder $sub) use ($status) {
                        match ($status) {
                            'open' => $sub->where('remaining_amount', '>', 0),
                            'paid' => $sub->where('status', 'paid'),
                            'overdue' => $sub->whereRaw(self::OVERDUE, [today()->toDateString()]),
                            default => $sub->where('status', $status),
                        };
                    });
                }
            });
        }

        return $query;
    }
}
