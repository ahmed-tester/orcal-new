<?php

namespace App\Services\Payroll;

use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Pure payroll entitlement and late-compensation calculator.
 * Performs zero database queries and zero side effects.
 */
class PayrollEntitlementCalculator
{
    /**
     * Calculate full monthly payroll entitlement from a preloaded context.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function calculateEntitlement(array $context): array
    {
        $useNewPolicy = (bool) ($context['use_new_policy'] ?? false);
        $basic = (float) ($context['basic_salary'] ?? 0.0);
        $rewardsAmount = round((float) ($context['rewards_amount'] ?? 0.0), 2);
        $advanceAmount = round((float) ($context['advance_deduction'] ?? 0.0), 2);
        $overtimeMinutes = (float) ($context['overtime_minutes'] ?? 0.0);
        $effectiveAbsentDays = (float) ($context['absent_days'] ?? 0.0);
        $collectedDeduction = (float) ($context['collected_deduction'] ?? 0.0);
        $collectionNotes = (string) ($context['collection_notes'] ?? '');
        $resolvedPolicy = is_array($context['resolved_policy'] ?? null) ? $context['resolved_policy'] : [];

        if ($useNewPolicy) {
            $basic = (float) ($resolvedPolicy['basic_salary'] ?? $basic);
            $dayRate = (float) ($resolvedPolicy['day_rate'] ?? 0.0);
            $hourRate = (float) ($resolvedPolicy['hour_rate'] ?? 0.0);
            $overtimeRate = ($resolvedPolicy['overtime_hourly_rate'] ?? null) !== null
                ? (float) $resolvedPolicy['overtime_hourly_rate']
                : $hourRate;
        } else {
            $hourRate = $basic > 0 ? ($basic / 240) : 0.0;
            $dayRate = $basic > 0 ? ($basic / 30) : 0.0;
            $overtimeRate = $hourRate;
        }

        $overtimeHours = $overtimeMinutes > 0 ? ($overtimeMinutes / 60) : 0.0;
        $overtimeAmount = round($overtimeHours * $overtimeRate, 2);

        $lateAndComp = is_array($context['late_and_comp'] ?? null) ? $context['late_and_comp'] : null;
        $lateAmount = $useNewPolicy
            ? (float) ($lateAndComp['late_deduction_amount'] ?? 0.0)
            : (float) ($context['legacy_late_amount'] ?? 0.0);

        $absencePenaltyAmount = round($effectiveAbsentDays * $dayRate, 2);
        $absenceAmount = round($lateAmount + $absencePenaltyAmount, 2);

        $fixedAllowancesTotal = $useNewPolicy
            ? round(
                (float) ($resolvedPolicy['allowances_amount'] ?? 0.0)
                + (float) ($resolvedPolicy['bonuses_amount'] ?? 0.0)
                + (float) ($resolvedPolicy['other_allowances_amount'] ?? 0.0),
                2
            )
            : 0.0;
        $compensationAmount = $useNewPolicy ? (float) ($lateAndComp['compensation_amount'] ?? 0.0) : 0.0;

        $additions = round($overtimeAmount + $rewardsAmount + $fixedAllowancesTotal + $compensationAmount, 2);
        $deductions = round($absenceAmount + $collectedDeduction, 2);
        $net = round(max(0, $basic + $additions - $deductions), 2);

        $metadata = array_filter([
            'collected_deduction' => $collectedDeduction,
            'collection_notes' => $collectionNotes,
        ]);

        if ($useNewPolicy && $lateAndComp !== null) {
            $snapshotBase = is_array($context['snapshot_base'] ?? null) ? $context['snapshot_base'] : [];
            $metadata['calculation_snapshot'] = array_merge(
                $snapshotBase,
                [
                    'allowances_amount' => (float) ($resolvedPolicy['allowances_amount'] ?? 0.0),
                    'bonuses_amount' => (float) ($resolvedPolicy['bonuses_amount'] ?? 0.0),
                    'other_allowances_amount' => (float) ($resolvedPolicy['other_allowances_amount'] ?? 0.0),
                    'total_salary' => (float) ($resolvedPolicy['total_salary'] ?? 0.0),
                    'deduction_basis' => $resolvedPolicy['deduction_basis'] ?? null,
                    'daily_work_hours' => $resolvedPolicy['daily_work_hours'] ?? null,
                    'overtime_hourly_rate' => $resolvedPolicy['overtime_hourly_rate'] ?? null,
                    'compensation_hourly_rate' => $resolvedPolicy['compensation_hourly_rate'] ?? null,
                    'use_new_policy' => true,
                    'is_policy_complete' => true,
                    'policy_version' => $resolvedPolicy['policy_version'] ?? null,
                    'effective_month' => $resolvedPolicy['effective_month'] ?? null,
                    'calculation_days' => $resolvedPolicy['calculation_days'] ?? null,
                    'days_source' => $resolvedPolicy['days_source'] ?? null,
                    'deduction_base_amount' => $resolvedPolicy['deduction_base_amount'] ?? null,
                    'day_rate' => round($dayRate, 4),
                    'hour_rate' => round($hourRate, 4),
                    'fixed_allowances_total' => $fixedAllowancesTotal,
                    'late_minutes' => $lateAndComp['late_minutes'],
                    'late_deduction_amount' => $lateAndComp['late_deduction_amount'],
                    'compensation_minutes_used' => $lateAndComp['compensation_minutes_used'],
                    'compensation_amount' => $compensationAmount,
                    'remaining_late_deduction' => $lateAndComp['remaining_late_deduction'],
                ]
            );
            $metadata['compensation_allocations'] = $lateAndComp['allocations'];
        }

        return [
            'company_id' => $context['company_id'] ?? null,
            'basic_salary' => $basic,
            'rewards_amount' => $rewardsAmount,
            'overtime_amount' => $overtimeAmount,
            'additional_amount' => $additions,
            'advance_deduction' => $advanceAmount,
            'absence_deduction' => $absenceAmount,
            'collected_deduction' => $collectedDeduction,
            'collection_notes' => $collectionNotes,
            'metadata' => $metadata,
            'deduction_amount' => $deductions,
            'net_amount' => $net,
            'remaining_amount' => $net,
            'payment_status' => PaymentStatus::UNPAID->value,
        ];
    }

    /**
     * Recalculate net amount, remaining amount, and payment status.
     *
     * @return array{net_amount: float, remaining_amount: float, payment_status: PaymentStatus}
     */
    public static function computeTotals(float $basic, float $additional, float $deductions, float $paid = 0): array
    {
        $net = round(max(0, $basic + $additional - $deductions), 2);
        $remaining = round(max(0, $net - $paid), 2);

        $status = PaymentStatus::UNPAID;
        if ($paid >= $net && $net > 0) {
            $status = PaymentStatus::PAID;
        } elseif ($paid > 0) {
            $status = PaymentStatus::PARTIALLY_PAID;
        }

        return [
            'net_amount' => $net,
            'remaining_amount' => $remaining,
            'payment_status' => $status,
        ];
    }

    /**
     * Pure calculation of late-arrival deductions and compensation allocations for a month.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function calculateLateAndCompensation(array $context): array
    {
        $monthYm = (string) ($context['month'] ?? '');
        $monthStart = Carbon::parse($monthYm.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $hourRate = (float) ($context['hour_rate'] ?? 0.0);
        $compRate = $context['compensation_hourly_rate'] ?? null;
        $compRate = $compRate !== null ? (float) $compRate : null;

        $filterStartDate = $context['filter_start_date'] ?? null;
        $filterEndDate = $context['filter_end_date'] ?? null;
        $isFiltered = $filterStartDate !== null || $filterEndDate !== null;
        $periodStart = $filterStartDate !== null
            ? max($monthStart->toDateString(), Carbon::parse($filterStartDate)->toDateString())
            : $monthStart->toDateString();
        $periodEnd = $filterEndDate !== null
            ? min($monthEnd->toDateString(), Carbon::parse($filterEndDate)->toDateString())
            : $monthEnd->toDateString();

        $rawIncidents = is_array($context['late_incidents'] ?? null) ? $context['late_incidents'] : [];
        $grants = is_array($context['grants'] ?? null) ? $context['grants'] : [];
        $eligibilityByDate = is_array($context['eligibility_by_date'] ?? null) ? $context['eligibility_by_date'] : [];
        $otherAllocationsByCycle = is_array($context['other_allocations_by_cycle'] ?? null) ? $context['other_allocations_by_cycle'] : [];
        $sharedCycleUsage = is_array($context['shared_cycle_usage'] ?? null) ? $context['shared_cycle_usage'] : [];

        $lateIncidents = [];
        $totalLateMinutes = 0;
        $totalLateDeduction = 0.0;
        $filteredLateMinutes = 0;
        $filteredLateDeduction = 0.0;

        foreach ($rawIncidents as $raw) {
            $mins = (int) ($raw['late_minutes'] ?? 0);
            if ($mins <= 0) {
                continue;
            }
            $dateStr = (string) ($raw['date'] ?? '');
            $deduction = round(($mins / 60) * $hourRate, 4);
            $totalLateMinutes += $mins;
            $totalLateDeduction += $deduction;

            if ($dateStr >= $periodStart && $dateStr <= $periodEnd) {
                $filteredLateMinutes += $mins;
                $filteredLateDeduction += $deduction;
            }

            $lateIncidents[] = [
                'attendance_id' => $raw['attendance_id'] ?? null,
                'date' => $dateStr,
                'late_minutes' => $mins,
                'deduction_amount' => $deduction,
                'remaining_deduction' => $deduction,
            ];
        }

        $totalLateDeductionRounded = round($totalLateDeduction, 2);
        $outputLateMinutes = $isFiltered ? $filteredLateMinutes : $totalLateMinutes;
        $outputLateDeductionRounded = $isFiltered ? round($filteredLateDeduction, 2) : $totalLateDeductionRounded;

        if ($totalLateDeductionRounded <= 0 || empty($grants)) {
            return [
                'late_minutes' => $outputLateMinutes,
                'late_deduction_amount' => $outputLateDeductionRounded,
                'compensation_minutes_used' => 0,
                'compensation_amount' => 0.0,
                'remaining_late_deduction' => $outputLateDeductionRounded,
                'allocations' => [],
                'blocked_missing_comp_rate' => false,
                'shared_once_usage' => $sharedCycleUsage,
            ];
        }

        if ($compRate === null) {
            return [
                'late_minutes' => $outputLateMinutes,
                'late_deduction_amount' => $outputLateDeductionRounded,
                'compensation_minutes_used' => 0,
                'compensation_amount' => 0.0,
                'remaining_late_deduction' => $outputLateDeductionRounded,
                'allocations' => [],
                'blocked_missing_comp_rate' => true,
                'shared_once_usage' => $sharedCycleUsage,
            ];
        }

        if ($compRate <= 0) {
            return [
                'late_minutes' => $outputLateMinutes,
                'late_deduction_amount' => $outputLateDeductionRounded,
                'compensation_minutes_used' => 0,
                'compensation_amount' => 0.0,
                'remaining_late_deduction' => $outputLateDeductionRounded,
                'allocations' => [],
                'blocked_missing_comp_rate' => false,
                'shared_once_usage' => $sharedCycleUsage,
            ];
        }

        $usedInCurrentRunByCycle = [];
        $onceCycleKeys = [];
        $allocations = [];
        $totalCompAmount = 0.0;
        $totalCompMinutes = 0.0;
        $reqKey = ($context['request_key'] ?? null) ?: (string) Str::uuid();

        foreach ($lateIncidents as &$incident) {
            $dateStr = $incident['date'];
            if (empty($eligibilityByDate[$dateStr])) {
                continue;
            }
            $remainingDeduction = (float) $incident['remaining_deduction'];
            if ($remainingDeduction <= 0) {
                continue;
            }

            $eligibleCycles = self::buildSortedCyclesForDate($grants, $dateStr, $monthYm);

            foreach ($eligibleCycles as $cycle) {
                if ($remainingDeduction <= 0.0001) {
                    break;
                }

                $cycleKey = $cycle['cycle_key'];
                $alreadyUsed = ($otherAllocationsByCycle[$cycleKey] ?? 0.0)
                    + ($sharedCycleUsage[$cycleKey] ?? 0.0)
                    + ($usedInCurrentRunByCycle[$cycleKey] ?? 0.0);
                $availableMinutes = max(0.0, (float) $cycle['max_minutes'] - $alreadyUsed);

                if ($availableMinutes <= 0.0001) {
                    continue;
                }

                $theoreticalAmount = ($availableMinutes / 60.0) * $compRate;
                $appliedAmount = min($theoreticalAmount, $remainingDeduction);

                if ($appliedAmount <= 0.0001) {
                    continue;
                }

                $consumedMinutes = ($appliedAmount / $compRate) * 60.0;
                $usedInCurrentRunByCycle[$cycleKey] = ($usedInCurrentRunByCycle[$cycleKey] ?? 0.0) + $consumedMinutes;
                if ($cycle['recurrence'] === SaudiCompensationGrantService::RECURRENCE_ONCE) {
                    $onceCycleKeys[$cycleKey] = true;
                }
                $remainingDeduction = max(0.0, $remainingDeduction - $appliedAmount);
                $totalCompAmount += $appliedAmount;
                $totalCompMinutes += $consumedMinutes;

                $allocations[] = [
                    'grant_id' => $cycle['grant_id'],
                    'grant_revision' => $cycle['revision'],
                    'cycle_key' => $cycleKey,
                    'recurrence' => $cycle['recurrence'],
                    'attendance_id' => $incident['attendance_id'],
                    'date' => $dateStr,
                    'consumed_minutes' => round($consumedMinutes, 4),
                    'compensation_hourly_rate' => $compRate,
                    'amount' => round($appliedAmount, 4),
                    'status' => 'reserved',
                    'request_key' => $reqKey,
                ];
            }
        }
        unset($incident);

        $nextSharedCycleUsage = $sharedCycleUsage;
        foreach (array_keys($onceCycleKeys) as $onceKey) {
            $nextSharedCycleUsage[$onceKey] = ($nextSharedCycleUsage[$onceKey] ?? 0.0) + ($usedInCurrentRunByCycle[$onceKey] ?? 0.0);
        }

        if ($isFiltered) {
            $filteredAllocations = array_values(array_filter(
                $allocations,
                fn (array $alloc): bool => $alloc['date'] >= $periodStart && $alloc['date'] <= $periodEnd
            ));
            $filteredCompMinutes = array_sum(array_column($filteredAllocations, 'consumed_minutes'));
            $filteredCompAmount = min(
                $outputLateDeductionRounded,
                round((float) array_sum(array_column($filteredAllocations, 'amount')), 2)
            );

            return [
                'late_minutes' => $outputLateMinutes,
                'late_deduction_amount' => $outputLateDeductionRounded,
                'compensation_minutes_used' => round($filteredCompMinutes, 2),
                'compensation_amount' => $filteredCompAmount,
                'remaining_late_deduction' => round(max(0.0, $outputLateDeductionRounded - $filteredCompAmount), 2),
                'allocations' => $filteredAllocations,
                'blocked_missing_comp_rate' => false,
                'shared_once_usage' => $nextSharedCycleUsage,
            ];
        }

        $compRounded = min($totalLateDeductionRounded, round($totalCompAmount, 2));

        return [
            'late_minutes' => $totalLateMinutes,
            'late_deduction_amount' => $totalLateDeductionRounded,
            'compensation_minutes_used' => round($totalCompMinutes, 2),
            'compensation_amount' => $compRounded,
            'remaining_late_deduction' => round(max(0.0, $totalLateDeductionRounded - $compRounded), 2),
            'allocations' => $allocations,
            'blocked_missing_comp_rate' => false,
            'shared_once_usage' => $nextSharedCycleUsage,
        ];
    }

    /**
     * Sort applicable grants for a date by soonest-expiring cycle first (deterministic).
     *
     * @param  array<int, array<string, mixed>>  $grants
     * @return list<array<string, mixed>>
     */
    public static function buildSortedCyclesForDate(array $grants, string $dateStr, string $monthYm): array
    {
        $cycles = [];
        $monthEnd = Carbon::parse($monthYm.'-01')->endOfMonth()->toDateString();

        foreach ($grants as $grant) {
            $startsOn = (string) ($grant['starts_on'] ?? '');
            $endsOn = isset($grant['ends_on']) && $grant['ends_on'] !== '' ? (string) $grant['ends_on'] : null;
            $revokedFrom = isset($grant['revoked_from']) && $grant['revoked_from'] !== '' ? (string) $grant['revoked_from'] : null;

            if ($startsOn === '' || $dateStr < $startsOn) {
                continue;
            }
            if ($endsOn !== null && $dateStr > $endsOn) {
                continue;
            }
            if (($grant['status'] ?? '') === 'revoked' && $revokedFrom !== null && $dateStr >= $revokedFrom) {
                continue;
            }

            $grantId = (string) ($grant['grant_id'] ?? '');
            $recurrence = (string) ($grant['recurrence'] ?? '');
            $minutes = (int) ($grant['minutes'] ?? 0);
            if ($grantId === '' || $minutes <= 0) {
                continue;
            }

            $cycleKey = match ($recurrence) {
                SaudiCompensationGrantService::RECURRENCE_DAILY => "{$grantId}:{$dateStr}",
                SaudiCompensationGrantService::RECURRENCE_MONTHLY => "{$grantId}:{$monthYm}",
                SaudiCompensationGrantService::RECURRENCE_ONCE => "{$grantId}:once",
                default => null,
            };

            if ($cycleKey === null) {
                continue;
            }

            $cycleExpiry = match ($recurrence) {
                SaudiCompensationGrantService::RECURRENCE_DAILY => $dateStr,
                SaudiCompensationGrantService::RECURRENCE_MONTHLY => $endsOn !== null ? min($monthEnd, $endsOn) : $monthEnd,
                SaudiCompensationGrantService::RECURRENCE_ONCE => $endsOn ?? $dateStr,
                default => $dateStr,
            };

            $cycles[] = [
                'grant_id' => $grantId,
                'revision' => (int) ($grant['revision'] ?? 1),
                'recurrence' => $recurrence,
                'cycle_key' => $cycleKey,
                'cycle_expiry' => $cycleExpiry,
                'max_minutes' => $minutes,
            ];
        }

        usort($cycles, function (array $a, array $b): int {
            $expCmp = strcmp($a['cycle_expiry'], $b['cycle_expiry']);
            if ($expCmp !== 0) {
                return $expCmp;
            }

            return strcmp($a['grant_id'], $b['grant_id']);
        });

        return $cycles;
    }
}
