<?php

namespace App\Services\Payroll;

use App\Services\OperationsAuditService;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollDeductionSettlementService
{
    /**
     * Reserve a salary deduction allocation between a collection record and a payroll record.
     *
     * @return array<string, mixed>
     */
    public static function reserve(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        float $amount,
        string $requestKey,
        ?User $actor = null,
    ): array {
        $actor = PayrollDisbursementService::authorize($payroll, 'disburse_payroll_salary', $actor);

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        if (! $collection->isCollection() || ! $payroll->isPayroll()) {
            throw ValidationException::withMessages([
                'operation_type' => __('financial.errors.invalid_settlement_types'),
            ]);
        }

        if ((int) $collection->company_id !== (int) $payroll->company_id
            || (int) $collection->employee_id !== (int) $payroll->employee_id) {
            throw ValidationException::withMessages([
                'employee_id' => __('financial.errors.settlement_scope_mismatch'),
            ]);
        }

        $fingerprint = hash('sha256', json_encode([
            'collection_id' => $collection->id,
            'payroll_id' => $payroll->id,
            'amount' => $amount,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($collection, $payroll, $amount, $requestKey, $fingerprint, $actor): array {
            DB::table('companies')->where('id', $collection->company_id)->lockForUpdate()->first();

            $ids = [$collection->id, $payroll->id];
            sort($ids);
            $lockedMap = PayrollRecord::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            /** @var PayrollRecord $lockedCollection */
            $lockedCollection = $lockedMap->get($collection->id);
            /** @var PayrollRecord $lockedPayroll */
            $lockedPayroll = $lockedMap->get($payroll->id);

            $lockedCollection = PayrollRecordFinancialService::ensureManaged($lockedCollection, $actor);
            $lockedPayroll = PayrollRecordFinancialService::ensureManaged($lockedPayroll, $actor);

            $colMeta = $lockedCollection->metadata ?? [];
            $allocations = $colMeta['salary_deduction_allocations'] ?? [];

            if (isset($allocations[$requestKey])) {
                if (($allocations[$requestKey]['fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $allocations[$requestKey];
            }

            $colSummary = PayrollRecordFinancialService::collectionSummary($lockedCollection);
            if ($amount > $colSummary['available_to_settle'] + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.exceeds_available_collection'),
                ]);
            }

            $paySummary = PayrollRecordFinancialService::payrollSummary($lockedPayroll);
            if ($amount > $paySummary['available_for_disbursement'] + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.exceeds_available_payroll'),
                ]);
            }

            $allocation = [
                'id' => 'ded:'.Str::uuid()->toString(),
                'request_key' => $requestKey,
                'fingerprint' => $fingerprint,
                'collection_record_id' => $lockedCollection->id,
                'payroll_record_id' => $lockedPayroll->id,
                'amount' => $amount,
                'status' => 'reserved',
                'created_by' => $actor?->id ?? auth()->id(),
                'created_at' => now()->toDateTimeString(),
            ];

            $allocations[$requestKey] = $allocation;
            $colMeta['salary_deduction_allocations'] = $allocations;
            $lockedCollection->metadata = $colMeta;
            $lockedCollection->saveQuietly();

            $payMeta = $lockedPayroll->metadata ?? [];
            $linked = $payMeta['linked_collection_deductions'] ?? [];
            $linked[$requestKey] = [
                'id' => $allocation['id'],
                'request_key' => $requestKey,
                'collection_record_id' => $lockedCollection->id,
                'amount' => $amount,
                'status' => 'reserved',
            ];
            $payMeta['linked_collection_deductions'] = $linked;
            $lockedPayroll->metadata = $payMeta;
            $lockedPayroll->saveQuietly();

            OperationsAuditService::recordFor(
                model: $lockedCollection,
                actionType: 'collection_deduction_reserved',
                payload: $allocation,
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $lockedCollection->company_id,
            );

            return $allocation;
        });
    }

    /**
     * Confirm one or more reserved salary deduction allocations atomically.
     * When $allowZeroCashOnly is true, the combined allocations must settle
     * the remaining payroll balance completely (or accompany existing payments).
     *
     * @param  array<int, string>  $requestKeys
     * @return array<int, array<string, mixed>>
     */
    public static function confirmBatch(
        PayrollRecord $payroll,
        array $requestKeys,
        ?User $actor = null,
        bool $allowZeroCashOnly = false,
    ): array {
        $requestKeys = array_values(array_unique(array_filter(array_map('strval', $requestKeys))));
        if ($requestKeys === []) {
            return [];
        }

        $actor = PayrollDisbursementService::authorize($payroll, 'disburse_payroll_salary', $actor);

        return DB::transaction(function () use ($payroll, $requestKeys, $actor, $allowZeroCashOnly): array {
            DB::table('companies')->where('id', $payroll->company_id)->lockForUpdate()->first();

            $preliminaryPayroll = PayrollRecord::whereKey($payroll->id)->lockForUpdate()->firstOrFail();
            $linkedPreliminary = $preliminaryPayroll->metadata['linked_collection_deductions'] ?? [];

            $collectionIds = [];
            foreach ($requestKeys as $key) {
                if (! isset($linkedPreliminary[$key]['collection_record_id'])) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.deduction_allocation_not_found'),
                    ]);
                }
                $collectionIds[] = (int) $linkedPreliminary[$key]['collection_record_id'];
            }

            $allIds = array_values(array_unique(array_merge([$payroll->id], $collectionIds)));
            sort($allIds);
            $lockedMap = PayrollRecord::whereIn('id', $allIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            /** @var PayrollRecord $lockedPayroll */
            $lockedPayroll = $lockedMap->get($payroll->id);
            $payMeta = $lockedPayroll->metadata ?? [];
            $linked = $payMeta['linked_collection_deductions'] ?? [];

            $pendingToConfirm = [];
            $alreadyConfirmed = [];
            $batchTotal = 0.0;

            foreach ($requestKeys as $key) {
                $colId = (int) ($linked[$key]['collection_record_id'] ?? 0);
                /** @var PayrollRecord|null $lockedCollection */
                $lockedCollection = $lockedMap->get($colId);

                if (! $lockedCollection) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.deduction_allocation_not_found'),
                    ]);
                }

                $allocations = $lockedCollection->metadata['salary_deduction_allocations'] ?? [];
                if (! isset($allocations[$key])) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.deduction_allocation_not_found'),
                    ]);
                }

                if ($allocations[$key]['status'] === 'confirmed') {
                    $alreadyConfirmed[] = $allocations[$key];

                    continue;
                }

                if ($allocations[$key]['status'] !== 'reserved') {
                    throw ValidationException::withMessages([
                        'status' => __('financial.errors.deduction_allocation_not_reserved'),
                    ]);
                }

                $batchTotal += (float) $allocations[$key]['amount'];
                $pendingToConfirm[] = [$key, $lockedCollection];
            }

            if ($pendingToConfirm === []) {
                return $alreadyConfirmed;
            }

            $paySummary = PayrollRecordFinancialService::payrollSummary($lockedPayroll);
            $remainingAfterBatch = round(max(0.0, $paySummary['remaining'] - $batchTotal), 2);
            $hasExistingPayment = $lockedPayroll->payments()->get()
                ->contains(fn (PayrollPayment $p) => empty($p->metadata['reversed_at']));

            if ($allowZeroCashOnly && $remainingAfterBatch > 0 && ! $hasExistingPayment) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.deduction_requires_payroll_settlement'),
                ]);
            }

            $confirmedResults = $alreadyConfirmed;
            $now = now()->toDateTimeString();
            $actorId = $actor?->id ?? auth()->id();

            foreach ($pendingToConfirm as [$key, $lockedCollection]) {
                $colMeta = $lockedCollection->metadata ?? [];
                $allocations = $colMeta['salary_deduction_allocations'] ?? [];
                $allocations[$key]['status'] = 'confirmed';
                $allocations[$key]['confirmed_by'] = $actorId;
                $allocations[$key]['confirmed_at'] = $now;
                $colMeta['salary_deduction_allocations'] = $allocations;
                $lockedCollection->metadata = $colMeta;
                $lockedCollection->saveQuietly();

                if (isset($linked[$key])) {
                    $linked[$key]['status'] = 'confirmed';
                    $linked[$key]['confirmed_at'] = $now;
                }

                PayrollRecordFinancialService::syncCollection($lockedCollection);

                OperationsAuditService::recordFor(
                    model: $lockedCollection,
                    actionType: 'collection_deduction_confirmed',
                    payload: $allocations[$key],
                    userId: $actorId,
                    companyId: (int) $lockedCollection->company_id,
                );

                OperationsAuditService::recordFor(
                    model: $lockedPayroll,
                    actionType: 'payroll_collection_deduction_applied',
                    payload: $allocations[$key],
                    userId: $actorId,
                    companyId: (int) $lockedPayroll->company_id,
                );

                $confirmedResults[] = $allocations[$key];
            }

            $payMeta['linked_collection_deductions'] = $linked;
            $lockedPayroll->metadata = $payMeta;
            $lockedPayroll->saveQuietly();

            PayrollRecordFinancialService::syncPayroll($lockedPayroll);

            return $confirmedResults;
        });
    }

    /**
     * Confirm a single reserved salary deduction allocation.
     *
     * @return array<string, mixed>
     */
    public static function confirm(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        string $requestKey,
        ?User $actor = null,
        bool $allowZeroCashOnly = false,
    ): array {
        $results = static::confirmBatch($payroll, [$requestKey], $actor, $allowZeroCashOnly);

        return $results[0] ?? [];
    }

    /**
     * Reverse a confirmed salary deduction allocation in a coordinated transaction.
     *
     * @return array<string, mixed>
     */
    public static function reverse(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        string $requestKey,
        string $reason,
        ?User $actor = null,
    ): array {
        $actor = PayrollDisbursementService::authorize($payroll, 'reverse_financial_transaction', $actor);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.reversal_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($collection, $payroll, $requestKey, $reason, $actor): array {
            DB::table('companies')->where('id', $collection->company_id)->lockForUpdate()->first();

            $ids = [$collection->id, $payroll->id];
            sort($ids);
            $lockedMap = PayrollRecord::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            /** @var PayrollRecord $lockedCollection */
            $lockedCollection = $lockedMap->get($collection->id);
            /** @var PayrollRecord $lockedPayroll */
            $lockedPayroll = $lockedMap->get($payroll->id);

            $colMeta = $lockedCollection->metadata ?? [];
            $allocations = $colMeta['salary_deduction_allocations'] ?? [];

            if (! isset($allocations[$requestKey]) || $allocations[$requestKey]['status'] !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.deduction_allocation_not_confirmed'),
                ]);
            }

            $allocations[$requestKey]['status'] = 'reversed';
            $allocations[$requestKey]['reversed_by'] = $actor?->id ?? auth()->id();
            $allocations[$requestKey]['reversed_at'] = now()->toDateTimeString();
            $allocations[$requestKey]['reversal_reason'] = $reason;
            $colMeta['salary_deduction_allocations'] = $allocations;
            $lockedCollection->metadata = $colMeta;
            $lockedCollection->saveQuietly();

            $payMeta = $lockedPayroll->metadata ?? [];
            $linked = $payMeta['linked_collection_deductions'] ?? [];
            if (isset($linked[$requestKey])) {
                $linked[$requestKey]['status'] = 'reversed';
                $linked[$requestKey]['reversed_at'] = $allocations[$requestKey]['reversed_at'];
            }
            $payMeta['linked_collection_deductions'] = $linked;
            $lockedPayroll->metadata = $payMeta;
            $lockedPayroll->saveQuietly();

            PayrollRecordFinancialService::syncCollection($lockedCollection);
            PayrollRecordFinancialService::syncPayroll($lockedPayroll);

            OperationsAuditService::recordFor(
                model: $lockedCollection,
                actionType: 'collection_deduction_reversed',
                payload: $allocations[$requestKey],
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $lockedCollection->company_id,
            );

            OperationsAuditService::recordFor(
                model: $lockedPayroll,
                actionType: 'payroll_collection_deduction_reversed',
                payload: $allocations[$requestKey],
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $lockedPayroll->company_id,
            );

            return $allocations[$requestKey];
        });
    }
}
