<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ViolationSummaryService
{
    public const TOTALS = ['total_sum', 'paid_sum', 'remaining_sum', 'overdue_sum'];

    private const OVERDUE = "remaining_amount > 0 AND (stage IN ('legal', 'admin_execution') OR (grace_deadline IS NOT NULL AND grace_deadline < ?) OR (grace_deadline IS NULL AND DATE_ADD(violation_date, INTERVAL 90 DAY) < ?))";

    public static function violations(): Builder
    {
        $query = CompanyAccessScopeService::apply(Violation::query());
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && $user->branch_id && ! $user->canAccessAllBranches()) {
            $query->whereIn('violations.company_id', CompanyFormService::getAuthorizedCompanyIds($user));
        }

        return $query;
    }

    public static function transactions(): Builder
    {
        return CompanyAccessScopeService::apply(ViolationTransaction::query())
            ->whereIn('violation_id', self::violations()->select('violations.id'));
    }

    public static function summaryQuery(array $filters): Builder
    {
        return Violation::query()
            ->joinSub(self::grouped($filters), 'summary', 'violations.id', '=', 'summary.row_id')
            ->select('violations.*', 'summary.violation_count', ...array_map(fn ($key) => "summary.{$key}", self::TOTALS))
            ->with(['company'])
            ->orderByDesc('summary.remaining_sum')
            ->orderBy('violations.id');
    }

    public static function stats(array $filters): object
    {
        return DB::query()->fromSub(self::grouped($filters), 'summary')
            ->selectRaw('COUNT(*) AS companies')
            ->selectRaw('COALESCE(SUM(violation_count), 0) AS violation_count')
            ->selectRaw(implode(', ', array_map(fn ($key) => "COALESCE(SUM({$key}), 0) AS {$key}", self::TOTALS)))
            ->first();
    }

    public static function details(int $id, array $filters): array
    {
        $query = self::filtered($filters);
        $representative = (clone $query)->with(['company'])->find($id);
        abort_if($representative === null, 404);

        if ($representative->company_id !== null) {
            $query->where('company_id', $representative->company_id);
        } else {
            $query->whereKey($representative->id);
        }

        $showTransactions = Gate::allows('viewAny', ViolationTransaction::class);
        if ($showTransactions) {
            $query->with([
                'category',
                'type',
                'transactions' => fn ($relation) => $relation
                    ->whereIn('violation_transactions.id', self::transactions()->select('violation_transactions.id'))
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id'),
            ]);
        } else {
            $query->with(['category', 'type']);
        }

        return [
            'representative' => $representative,
            'company' => $representative->company,
            'violations' => $query->orderByDesc('violation_date')->orderByDesc('id')->get(),
            'showTransactions' => $showTransactions,
        ];
    }

    public static function isOverdue(Violation $violation): bool
    {
        if ((float) $violation->remaining_amount <= 0) {
            return false;
        }

        $stage = $violation->stage instanceof ViolationStage ? $violation->stage : ViolationStage::tryFrom((string) $violation->stage);
        if (in_array($stage, [ViolationStage::LEGAL, ViolationStage::ADMIN_EXECUTION], true)) {
            return true;
        }

        if ($violation->grace_deadline) {
            return $violation->grace_deadline->lt(today());
        }

        return $violation->violation_date ? Carbon::parse($violation->violation_date)->addDays(90)->lt(today()) : false;
    }

    private static function grouped(array $filters): Builder
    {
        return self::filtered($filters)
            ->selectRaw('MIN(id) AS row_id, COUNT(*) AS violation_count,
                SUM(violation_amount) AS total_sum,
                SUM(paid_amount) AS paid_sum,
                SUM(remaining_amount) AS remaining_sum')
            ->selectRaw('SUM(CASE WHEN '.self::OVERDUE.' THEN remaining_amount ELSE 0 END) AS overdue_sum', [
                today()->toDateString(),
                today()->toDateString(),
            ])
            ->groupBy('company_id');
    }

    private static function filtered(array $filters): Builder
    {
        $query = FilterBarService::applyFilters(self::violations(), $filters, [
            'company' => true,
            'date_column' => 'violation_date',
            'search_columns' => [
                'violation_number',
                'external_violation_number',
                'issuing_authority',
                'description',
                'company.trade_name',
                'company.commercial_registration_number',
            ],
            'where_in' => [
                'violation_category_id' => $filters['category_id'] ?? null,
                'violation_type_id' => $filters['type_id'] ?? null,
                'stage' => $filters['stage'] ?? null,
            ],
        ]);

        $statuses = FilterBarService::values($filters['status'] ?? null, ['all']);

        if (! empty($statuses)) {
            $query->where(function (Builder $group) use ($statuses) {
                foreach ($statuses as $status) {
                    $group->orWhere(function (Builder $sub) use ($status) {
                        match ($status) {
                            'open' => $sub->where('remaining_amount', '>', 0),
                            'paid' => $sub->where('payment_status', ViolationPaymentStatus::PAID->value),
                            'partial' => $sub->where('payment_status', ViolationPaymentStatus::PARTIAL->value),
                            'pending' => $sub->where('payment_status', ViolationPaymentStatus::PENDING->value),
                            'overdue' => $sub->whereRaw(self::OVERDUE, [today()->toDateString(), today()->toDateString()]),
                            default => $sub->where('payment_status', $status),
                        };
                    });
                }
            });
        }

        return $query;
    }
}

