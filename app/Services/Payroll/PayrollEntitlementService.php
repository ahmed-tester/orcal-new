<?php

namespace App\Services\Payroll;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll\PayrollRecord;
use App\Services\OperationsAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single transactional write-point for monthly payroll entitlement records.
 * Handles locking, financial-impact protection, snapshot/allocation persistence, and policy transitions.
 */
class PayrollEntitlementService
{
    /**
     * Create or update a monthly payroll entitlement record inside a single transaction.
     *
     * @param  array<string, mixed>  $options
     */
    public static function upsertMonthlyPayroll(int $employeeId, string $monthStr, array $options = []): ?PayrollRecord
    {
        $explicitRecalculate = (bool) ($options['explicit_recalculate'] ?? false);
        $skipIfFinanciallyImpacted = (bool) ($options['skip_if_financially_impacted'] ?? true);
        $returnRecordWhenProtected = (bool) ($options['return_record_when_protected'] ?? false);
        $activateIfNew = (bool) ($options['activate_if_new'] ?? false);
        $auditTransition = (bool) ($options['audit_transition'] ?? false);
        $hasNotes = array_key_exists('notes', $options);
        $notes = $options['notes'] ?? null;
        $overrides = is_array($options['overrides'] ?? null) ? $options['overrides'] : [];
        $onlyIfMissing = (bool) ($options['only_if_missing'] ?? false);
        $recordDate = isset($options['record_date']) && $options['record_date'] !== ''
            ? Carbon::parse($options['record_date'])->toDateString()
            : null;

        $monthDateString = Carbon::parse($monthStr ?: now())->startOfMonth()->toDateString();
        $monthYm = Carbon::parse($monthDateString)->format('Y-m');

        return DB::transaction(function () use (
            $employeeId,
            $monthDateString,
            $monthYm,
            $explicitRecalculate,
            $skipIfFinanciallyImpacted,
            $returnRecordWhenProtected,
            $activateIfNew,
            $auditTransition,
            $hasNotes,
            $notes,
            $overrides,
            $onlyIfMissing,
            $recordDate,
        ): ?PayrollRecord {
            $lockedEmployee = Employee::query()->whereKey($employeeId)->lockForUpdate()->first();
            if (! $lockedEmployee || empty($lockedEmployee->company_id) || ! Company::whereKey($lockedEmployee->company_id)->exists()) {
                return null;
            }

            $lockedRecord = PayrollRecord::query()
                ->where('company_id', $lockedEmployee->company_id)
                ->where('employee_id', $lockedEmployee->id)
                ->whereDate('payroll_month', $monthDateString)
                ->where('operation_type', 'payroll')
                ->lockForUpdate()
                ->first();

            if ($lockedRecord && $onlyIfMissing) {
                return $lockedRecord;
            }

            if ($lockedRecord && $lockedRecord->hasFinancialImpact()) {
                if (! $skipIfFinanciallyImpacted) {
                    throw ValidationException::withMessages([
                        'payroll_record' => __('financial.errors.cannot_modify_with_financial_records'),
                    ]);
                }

                if ($hasNotes && $notes !== $lockedRecord->notes) {
                    $lockedRecord->notes = $notes;
                    $lockedRecord->save();
                }

                return $returnRecordWhenProtected ? $lockedRecord->refresh() : null;
            }

            $isNew = ! $lockedRecord;
            $beforeNet = $lockedRecord ? round((float) ($lockedRecord->net_amount ?? 0), 2) : 0.0;
            $wasNewPolicy = $lockedRecord && ! empty($lockedRecord->metadata['calculation_snapshot']['use_new_policy']);

            $financials = PayrollCalculationService::calculateEmployeeFinancials(
                $lockedEmployee->id,
                $monthDateString,
                $lockedRecord?->id,
                $lockedRecord,
                $explicitRecalculate
            );

            if (empty($financials)) {
                return null;
            }

            if (! $lockedRecord) {
                $lockedRecord = new PayrollRecord([
                    'company_id' => $lockedEmployee->company_id,
                    'employee_id' => $lockedEmployee->id,
                    'payroll_month' => $monthDateString,
                    'operation_type' => 'payroll',
                    'record_date' => $recordDate ?? now()->toDateString(),
                    'created_by' => auth()->id(),
                ]);
            } elseif ($recordDate !== null) {
                $lockedRecord->record_date = $recordDate;
            } elseif (empty($lockedRecord->record_date)) {
                $lockedRecord->record_date = now()->toDateString();
            }

            $lockedRecord->basic_salary = array_key_exists('basic_salary', $overrides)
                ? (float) $overrides['basic_salary']
                : (float) $financials['basic_salary'];
            $lockedRecord->overtime_amount = array_key_exists('overtime_amount', $overrides)
                ? (float) $overrides['overtime_amount']
                : (float) ($financials['overtime_amount'] ?? 0);
            $lockedRecord->rewards_amount = array_key_exists('rewards_amount', $overrides)
                ? (float) $overrides['rewards_amount']
                : (float) ($financials['rewards_amount'] ?? 0);
            $lockedRecord->advance_deduction = array_key_exists('advance_deduction', $overrides)
                ? (float) $overrides['advance_deduction']
                : (float) ($financials['advance_deduction'] ?? 0);
            $lockedRecord->absence_deduction = array_key_exists('absence_deduction', $overrides)
                ? (float) $overrides['absence_deduction']
                : (float) ($financials['absence_deduction'] ?? 0);

            $meta = is_array($lockedRecord->metadata) ? $lockedRecord->metadata : [];
            $newMeta = is_array($financials['metadata'] ?? null) ? $financials['metadata'] : [];
            $isNewPolicy = ! empty($newMeta['calculation_snapshot']['use_new_policy']);

            if ($isNewPolicy) {
                if (! empty($newMeta)) {
                    $meta = array_merge($meta, $newMeta);
                }
            } else {
                if ($wasNewPolicy && ! empty($meta['compensation_allocations']) && is_array($meta['compensation_allocations'])) {
                    $reversedAt = now()->toIso8601String();
                    foreach ($meta['compensation_allocations'] as &$alloc) {
                        if (in_array(($alloc['status'] ?? ''), ['reserved', 'confirmed'], true)) {
                            $alloc['status'] = 'reversed';
                            $alloc['reversed_at'] = $reversedAt;
                            $alloc['reverse_reason'] = 'explicit_recalculate_to_legacy';
                        }
                    }
                    unset($alloc);
                }

                unset($meta['calculation_snapshot']);
                if (! empty($newMeta)) {
                    $meta = array_merge($meta, $newMeta);
                }

                if (! $explicitRecalculate && EmployeeSalaryProfileService::isEligibleSaudiEmployee($lockedEmployee)) {
                    $meta['calculation_snapshot'] = EmployeeSalaryProfileService::buildCalculationSnapshot($lockedEmployee, [
                        'basic_salary' => (float) $lockedRecord->basic_salary,
                    ]);
                }
            }

            if (! array_key_exists('collected_deduction', $newMeta)) {
                unset($meta['collected_deduction'], $meta['collection_notes']);
            }

            if (array_key_exists('overtime_amount', $overrides) || array_key_exists('rewards_amount', $overrides)) {
                $snapshot = is_array($meta['calculation_snapshot'] ?? null) ? $meta['calculation_snapshot'] : [];
                $fixedAllowances = ! empty($snapshot['use_new_policy']) ? (float) ($snapshot['fixed_allowances_total'] ?? 0) : 0.0;
                $compAmount = ! empty($snapshot['use_new_policy']) ? (float) ($snapshot['compensation_amount'] ?? 0) : 0.0;
                $lockedRecord->additional_amount = round(
                    (float) $lockedRecord->overtime_amount + (float) $lockedRecord->rewards_amount + $fixedAllowances + $compAmount,
                    2
                );
            } else {
                $lockedRecord->additional_amount = (float) ($financials['additional_amount'] ?? 0);
            }

            if (array_key_exists('advance_deduction', $overrides) || array_key_exists('absence_deduction', $overrides)) {
                $collected = (float) ($meta['collected_deduction'] ?? 0);
                $lockedRecord->deduction_amount = round(
                    (float) $lockedRecord->advance_deduction + (float) $lockedRecord->absence_deduction + $collected,
                    2
                );
            } else {
                $lockedRecord->deduction_amount = (float) ($financials['deduction_amount'] ?? 0);
            }

            $paid = (float) ($lockedRecord->paid_amount ?? 0);
            $totals = PayrollEntitlementCalculator::computeTotals(
                (float) $lockedRecord->basic_salary,
                (float) $lockedRecord->additional_amount,
                (float) $lockedRecord->deduction_amount,
                $paid
            );

            $lockedRecord->net_amount = $totals['net_amount'];
            $lockedRecord->remaining_amount = $totals['remaining_amount'];
            $lockedRecord->payment_status = $totals['payment_status'];

            if ($lockedRecord->isManaged()) {
                $meta['net_amount_snapshot'] = round((float) $lockedRecord->net_amount, 2);
            }

            $lockedRecord->metadata = $meta;
            if ($hasNotes) {
                $lockedRecord->notes = $notes;
            }

            $lockedRecord->skipObserverTotalsSync = true;
            try {
                $lockedRecord->save();
            } finally {
                $lockedRecord->skipObserverTotalsSync = false;
            }

            if ($isNew && $activateIfNew) {
                PayrollRecordFinancialService::activate($lockedRecord);
            }

            if ($auditTransition && $wasNewPolicy !== $isNewPolicy && ! empty($lockedRecord->company_id)) {
                OperationsAuditService::recordFor(
                    model: $lockedRecord,
                    actionType: 'payroll_policy_recalculated',
                    oldStatus: $wasNewPolicy ? 'new_policy' : 'legacy',
                    newStatus: $isNewPolicy ? 'new_policy' : 'legacy',
                    payload: [
                        'month' => $monthYm,
                        'before_net' => $beforeNet,
                        'after_net' => round((float) $lockedRecord->net_amount, 2),
                    ],
                    userId: auth()->id(),
                    companyId: (int) $lockedRecord->company_id,
                );
            }

            return $lockedRecord->refresh();
        });
    }

    /**
     * Explicitly recalculate an existing non-financially-impacted payroll record under the current policy.
     */
    public static function recalculateExistingRecord(PayrollRecord $record): PayrollRecord
    {
        $monthStr = Carbon::parse($record->payroll_month)->format('Y-m');

        return self::upsertMonthlyPayroll((int) $record->employee_id, $monthStr, [
            'explicit_recalculate' => true,
            'skip_if_financially_impacted' => false,
            'audit_transition' => true,
        ]) ?? $record->refresh();
    }
}
