<?php

namespace App\Services;

use App\Enums\OperationWorkflowStatus;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\UnifiedDeficiencyTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Read-only operational services and deficiency aggregation for the employee
 * performance page. Every number uses the same ServiceTransactionResource
 * scope as the services screen, plus the executor (executed_by) employee
 * constraint. Beneficiary employee_id is never treated as the executor.
 */
class ServicePerformanceSummaryService
{
    public const CATEGORY_ACTIVE = 'active';

    public const CATEGORY_COMPLETED = 'completed';

    public const CATEGORY_OVERDUE = 'overdue';

    public const CATEGORY_DEFICIENCIES = 'assigned_deficiencies';

    /**
     * Company/branch scoped service transactions, exactly as the services
     * resource sees them.
     */
    public static function baseQuery(): Builder
    {
        return ServiceTransactionResource::getEloquentQuery();
    }

    /**
     * Transactions executed by one employee in the period, with an optional
     * card category. Unassigned transactions have executed_by = null and are
     * intentionally outside every employee aggregation.
     */
    public static function transactionsQuery(
        int $employeeId,
        array $filters = [],
        ?string $category = null,
        ?Employee $target = null,
    ): Builder {
        [$startDate, $endDate] = TaskAssignmentSummaryService::resolveDateRange($filters);

        $query = self::baseQuery()
            ->where('service_transactions.executed_by', $employeeId);

        self::excludeCancelled($query);
        if ($category !== null) {
            self::applyCategory($query, $category, $startDate, $endDate, $target);
        } else {
            $query->where(function (Builder $period) use ($startDate, $endDate, $target) {
                foreach ([self::CATEGORY_ACTIVE, self::CATEGORY_COMPLETED, self::CATEGORY_OVERDUE] as $card) {
                    $period->orWhere(fn (Builder $categoryQuery) => self::applyCategory(
                        $categoryQuery, $card, $startDate, $endDate, $target
                    ));
                }
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, ServiceTransaction>
     */
    public static function transactions(
        int $employeeId,
        array $filters = [],
        ?string $category = null,
        ?Employee $target = null,
    ): Collection {
        return self::transactionsQuery($employeeId, $filters, $category, $target)
            ->with(['service', 'executor.user', 'employee.user'])
            ->orderByRaw('COALESCE(service_transactions.due_date, service_transactions.completion_date, service_transactions.transaction_date)')
            ->orderBy('service_transactions.id')
            ->get();
    }

    /**
     * KPI values. Each value uses the same query as its statement.
     */
    public static function stats(int $employeeId, array $filters = [], ?Employee $target = null): object
    {
        return (object) [
            'active' => self::transactionsQuery($employeeId, $filters, self::CATEGORY_ACTIVE, $target)->count(),
            'completed' => self::transactionsQuery($employeeId, $filters, self::CATEGORY_COMPLETED, $target)->count(),
            'overdue' => self::transactionsQuery($employeeId, $filters, self::CATEGORY_OVERDUE, $target)->count(),
            'assigned_deficiencies' => self::assignedDeficienciesQuery($employeeId, $filters, $target)->count(),
        ];
    }

    /**
     * Deficiencies linked to an authorized service transaction, directly or
     * through the taskable morph. Resolved and open rows are both returned for
     * the transaction statement.
     */
    public static function deficienciesBaseQuery(): Builder
    {
        $transactionIds = self::baseQuery()->select('service_transactions.id');

        return UnifiedDeficiencyTask::query()
            ->where(function (Builder $query) use ($transactionIds) {
                $query->whereIn('unified_deficiency_tasks.service_transaction_id', $transactionIds)
                    ->orWhere(function (Builder $morph) use ($transactionIds) {
                        $morph->where('unified_deficiency_tasks.taskable_type', ServiceTransaction::class)
                            ->whereIn('unified_deficiency_tasks.taskable_id', $transactionIds);
                    });
            });
    }

    /**
     * Open deficiencies assigned to the employee inside the authorized
     * transaction scope. This is the counter behind the assigned deficiencies
     * card and its statement.
     */
    public static function assignedDeficienciesQuery(
        int $employeeId,
        array $filters = [],
        ?Employee $target = null,
    ): Builder {
        [$startDate, $endDate] = TaskAssignmentSummaryService::resolveDateRange($filters);

        $query = self::deficienciesBaseQuery()
            ->where('unified_deficiency_tasks.responsible_employee_id', $employeeId)
            ->where('unified_deficiency_tasks.status', 'open')
            ->whereBetween('unified_deficiency_tasks.created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($target && ($user = auth()->user()) && EmployeePerformanceAccessService::isSupervisor($user)
            && ! EmployeePerformanceAccessService::isSelf($user, $target)) {
            $query->where('unified_deficiency_tasks.company_id', $target->company_id);
        }

        self::applySupervisorWindow($query, $target, $startDate, $endDate, 'unified_deficiency_tasks.created_at');

        return $query;
    }

    /**
     * @return Collection<int, UnifiedDeficiencyTask>
     */
    public static function assignedDeficiencies(
        int $employeeId,
        array $filters = [],
        ?Employee $target = null,
    ): Collection {
        return self::assignedDeficienciesQuery($employeeId, $filters, $target)
            ->with(['transaction', 'taskable', 'responsibleEmployee.user'])
            ->orderByRaw('COALESCE(unified_deficiency_tasks.due_date, unified_deficiency_tasks.created_at)')
            ->orderBy('unified_deficiency_tasks.id')
            ->get();
    }

    /**
     * All deficiencies linked to one transaction (including rows assigned to
     * another responsible employee inside the same authorized scope).
     *
     * @return Collection<int, UnifiedDeficiencyTask>
     */
    public static function linkedDeficiencies(ServiceTransaction $transaction): Collection
    {
        return UnifiedDeficiencyTask::query()
            ->where(function (Builder $query) use ($transaction) {
                $query->where('service_transaction_id', $transaction->id)
                    ->orWhere(function (Builder $morph) use ($transaction) {
                        $morph->where('taskable_type', ServiceTransaction::class)
                            ->where('taskable_id', $transaction->id);
                    });
            })
            ->with(['responsibleEmployee.user'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Resolve the transaction behind a deficiency, whether it uses the direct
     * column or the taskable morph.
     */
    public static function deficiencyTransaction(UnifiedDeficiencyTask $task): ?ServiceTransaction
    {
        if ($task->service_transaction_id) {
            return $task->transaction;
        }

        return $task->taskable instanceof ServiceTransaction ? $task->taskable : null;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => __('services_tracking.service_statuses.completed'),
            'inactive' => __('services_tracking.service_statuses.inactive'),
            default => __('services_tracking.service_statuses.active'),
        };
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'inactive' => 'gray',
            default => 'info',
        };
    }

    public static function deficiencyStatusLabel(?string $status): string
    {
        return match ($status) {
            'resolved' => __('tasks.performance.services.deficiency_statuses.resolved'),
            default => __('tasks.performance.services.deficiency_statuses.open'),
        };
    }

    public static function deficiencyStatusColor(?string $status): string
    {
        return $status === 'resolved' ? 'success' : 'danger';
    }

    public static function deficiencyTypeLabel(?string $type): string
    {
        if (! $type) {
            return '—';
        }

        $key = 'services_tracking.reconciliation.tasks.'.$type;

        return Lang::has($key) ? __($key) : $type;
    }

    /**
     * Pending operational action derived from the existing workflow metadata.
     * The deficiency responsible employee is kept separate from this value.
     *
     * @return array{key: string|null, label: string, color: string}
     */
    public static function pendingAction(ServiceTransaction $transaction): array
    {
        $meta = $transaction->metadata ?? [];
        $workflow = (string) ($transaction->workflow_status ?? '');

        $key = match (true) {
            ($meta['is_escalated'] ?? ($workflow === OperationWorkflowStatus::ESCALATED->value)) => 'escalated',
            ! empty($meta['approval_requested_at']) || ($meta['self_paid_status'] ?? '') === 'pending_approval' => 'awaiting_approval',
            $workflow === OperationWorkflowStatus::AT_TREASURY->value => 'at_treasury',
            ! empty($meta['suspense_amount_id']) => 'suspense_amount',
            ($meta['reconciliation_status'] ?? '') === 'mismatched' || ! empty($meta['mismatch_reason']) => 'missing_documents',
            ($meta['custody_status'] ?? '') === 'on_employee' && (float) ($transaction->paid_amount ?? 0) > 0 => 'custody_handover',
            $transaction->service_status === 'active'
                && $transaction->due_date
                && $transaction->due_date->lt(today()) => 'overdue_follow_up',
            default => null,
        };

        return [
            'key' => $key,
            'label' => $key ? __('tasks.performance.services.pending_actions.'.$key) : __('tasks.performance.services.pending_actions.none'),
            'color' => match ($key) {
                'escalated', 'missing_documents' => 'danger',
                'awaiting_approval', 'custody_handover', 'suspense_amount' => 'warning',
                'at_treasury', 'overdue_follow_up' => 'info',
                default => 'gray',
            },
        ];
    }

    private static function excludeCancelled(Builder $query): void
    {
        $query->where(function (Builder $status) {
            $status->whereNull('metadata->status')
                ->orWhere('metadata->status', '!=', 'cancelled');
        });
    }

    private static function applyCategory(
        Builder $query,
        string $category,
        string $startDate,
        string $endDate,
        ?Employee $target,
    ): void {
        $dateColumn = match ($category) {
            self::CATEGORY_ACTIVE => 'service_transactions.transaction_date',
            self::CATEGORY_COMPLETED => 'service_transactions.completion_date',
            self::CATEGORY_OVERDUE => 'service_transactions.due_date',
        };

        $query->where('service_transactions.service_status', $category === self::CATEGORY_COMPLETED ? 'completed' : 'active')
            ->whereBetween($dateColumn, [$startDate, $endDate]);

        if ($category === self::CATEGORY_OVERDUE) {
            $query->where($dateColumn, '<', today()->toDateString());
        }

        if ($target && ($user = auth()->user()) && EmployeePerformanceAccessService::isSupervisor($user)
            && ! EmployeePerformanceAccessService::isSelf($user, $target)) {
            $query->where('service_transactions.company_id', $target->company_id);
        }

        self::applySupervisorWindow($query, $target, $startDate, $endDate, $dateColumn);
    }

    private static function applySupervisorWindow(
        Builder $query,
        ?Employee $target,
        string $startDate,
        string $endDate,
        string $dateColumn,
    ): void {
        $user = auth()->user();

        if (
            ! $target
            || ! $user
            || ! EmployeePerformanceAccessService::isSupervisor($user)
            || EmployeePerformanceAccessService::isSelf($user, $target)
        ) {
            return;
        }

        $windows = EmployeePerformanceAccessService::supervisorWindows(
            $user,
            $target,
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        );

        if ($windows === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $windowQuery) use ($windows, $dateColumn) {
            foreach ($windows as [$windowStart, $windowEnd]) {
                $windowQuery->orWhere(fn (Builder $window) => $window
                    ->where($dateColumn, '>=', $windowStart)
                    ->where($dateColumn, '<', Carbon::parse($windowEnd)->addDay()->toDateString()));
            }
        });
    }
}
