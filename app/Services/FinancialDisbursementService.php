<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialDisbursementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(ServiceTransaction $transaction, array $data, ?User $user = null): FinancialDisbursement
    {
        $user = $user ?? auth()->user();
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'record_service_disbursement', $user);
        ServiceFinancialWorkflowService::assertManaged($transaction);

        $type = $data['disbursement_type'] ?? null;
        if (is_string($type)) {
            $type = FinancialDisbursementType::tryFrom($type);
        }

        if (! $type instanceof FinancialDisbursementType) {
            throw ValidationException::withMessages([
                'disbursement_type' => __('financial.errors.invalid_disbursement_type'),
            ]);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        $requestKey = trim((string) ($data['request_key'] ?? ''));
        if ($requestKey === '') {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.request_key_required'),
            ]);
        }

        if (empty($data['reference_number'])) {
            throw ValidationException::withMessages([
                'reference_number' => __('financial.errors.reference_required'),
            ]);
        }

        if ($type === FinancialDisbursementType::EMPLOYEE_PERSONAL) {
            if (empty($data['paid_by_employee_id'])) {
                throw ValidationException::withMessages([
                    'paid_by_employee_id' => __('financial.errors.paid_by_required'),
                ]);
            }

            if (empty($data['receipt_proof'])) {
                throw ValidationException::withMessages([
                    'receipt_proof' => __('financial.errors.proof_required'),
                ]);
            }
        }

        $fingerprint = static::fingerprint([
            'source' => FinancialDisbursement::SOURCE_SERVICE_TRANSACTION.':'.$transaction->id,
            'type' => $type->value,
            'amount' => $amount,
            'reference' => $data['reference_number'],
            'paid_by' => $data['paid_by_employee_id'] ?? null,
        ]);

        $disbursement = DB::transaction(function () use ($transaction, $data, $type, $amount, $requestKey, $fingerprint, $user) {
            $existing = FinancialDisbursement::where('request_key', $requestKey)->lockForUpdate()->first();

            if ($existing) {
                if (($existing->metadata['fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $existing;
            }

            $locked = ServiceTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $summary = FinancialSummaryService::for($locked);

            if (! $summary['fee_defined']) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.fee_rule_missing'),
                ]);
            }

            if ((float) $summary['fee_expected'] <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.service_has_no_fees'),
                ]);
            }

            if ($amount > (float) $summary['fee_remaining'] + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.exceeds_remaining_fee', [
                        'amount' => number_format((float) $summary['fee_remaining'], 2),
                    ]),
                ]);
            }

            if ($type === FinancialDisbursementType::TREASURY_DIRECT
                && $amount > (float) $summary['fee_funded_remaining'] + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.unfunded_disbursement', [
                        'amount' => number_format((float) $summary['fee_funded_remaining'], 2),
                    ]),
                ]);
            }

            $disbursement = FinancialDisbursement::create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->employee?->branch_id ?? $locked->executor?->branch_id,
                'source_type' => FinancialDisbursement::SOURCE_SERVICE_TRANSACTION,
                'source_id' => $locked->id,
                'request_key' => $requestKey,
                'disbursement_type' => $type->value,
                'amount' => $amount,
                'beneficiary_name' => $data['beneficiary_name'] ?? $summary['fee_beneficiary_reference'] ?? null,
                'reference_number' => $data['reference_number'],
                'receipt_proof' => $data['receipt_proof'] ?? null,
                'status' => $type === FinancialDisbursementType::TREASURY_DIRECT
                    ? FinancialDisbursementStatus::PAID->value
                    : FinancialDisbursementStatus::PENDING_APPROVAL->value,
                'paid_by_employee_id' => $data['paid_by_employee_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'fingerprint' => $fingerprint,
                    'fee_expected_at_record' => (float) $summary['fee_expected'],
                    'recorded_fee_remaining' => (float) $summary['fee_remaining'],
                    'proof_pending_review' => empty($data['receipt_proof']),
                ]),
            ]);

            if ($type === FinancialDisbursementType::TREASURY_DIRECT) {
                $treasury = FinancialTreasuryService::post(
                    companyId: (int) $locked->company_id,
                    direction: FinancialCashDirection::OUTGOING,
                    amount: $amount,
                    description: __('financial.descriptions.disbursement_direct', [
                        'number' => $disbursement->disbursement_number,
                        'transaction' => $locked->transaction_number,
                    ]),
                    metadata: [
                        'source' => 'financial_disbursement',
                        'financial_disbursement_id' => $disbursement->id,
                        'disbursement_number' => $disbursement->disbursement_number,
                    ],
                    executedByEmployeeId: $locked->executed_by,
                    counterparty: $disbursement->beneficiary_name,
                );

                $disbursement->update(['treasury_transaction_id' => $treasury->id]);
            }

            return $disbursement;
        });

        if ($disbursement->wasRecentlyCreated) {
            ServiceFinancialWorkflowService::sync($transaction);
            OperationsReconciliationService::reconcile($transaction);

            OperationsAuditService::record(
                companyId: (int) $transaction->company_id,
                transactionId: $transaction->id,
                actionType: 'financial_disbursement_recorded',
                payload: [
                    'disbursement_number' => $disbursement->disbursement_number,
                    'type' => $type->value,
                    'amount' => $amount,
                ],
                userId: $user?->id,
            );

            DB::afterCommit(function () use ($transaction, $disbursement) {
                OperationsNotificationService::notifyFinancialDisbursementRecorded($transaction, $disbursement);
            });
        }

        return $disbursement->refresh();
    }

    public static function approve(FinancialDisbursement $disbursement, ?User $user = null, ?string $note = null): FinancialDisbursement
    {
        $transaction = static::sourceTransaction($disbursement);
        $user = $user ?? auth()->user();
        ServiceFinancialWorkflowService::authorize($transaction, 'approve_self_paid_disbursement', $user);

        $disbursement = DB::transaction(function () use ($disbursement, $user, $note) {
            $locked = FinancialDisbursement::whereKey($disbursement->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== FinancialDisbursementStatus::PENDING_APPROVAL) {
                if (in_array($locked->status, [
                    FinancialDisbursementStatus::APPROVED,
                    FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                    FinancialDisbursementStatus::FULLY_REIMBURSED,
                ], true)) {
                    return $locked;
                }

                throw ValidationException::withMessages([
                    'status' => __('financial.errors.claim_not_pending_approval'),
                ]);
            }

            $actorIsRequester = (int) $locked->created_by === (int) $user?->id
                || (int) $locked->paid_by_employee_id === (int) $user?->employee?->id;

            if ($actorIsRequester && ! $user?->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'approval' => __('financial.errors.cannot_self_approve'),
                ]);
            }

            $meta = $locked->metadata ?? [];
            $meta['approved_note'] = $note;
            $meta['approved_at'] = now()->toDateTimeString();

            $locked->update([
                'status' => FinancialDisbursementStatus::APPROVED->value,
                'approved_by' => $user?->id ?? auth()->id(),
                'approved_at' => now(),
                'metadata' => $meta,
            ]);

            return $locked;
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_claim_approved',
            payload: [
                'disbursement_number' => $disbursement->disbursement_number,
                'amount' => (float) $disbursement->amount,
            ],
            userId: $user?->id,
        );

        DB::afterCommit(function () use ($transaction, $disbursement) {
            OperationsNotificationService::notifyFinancialClaimApproved($transaction, $disbursement);
        });

        return $disbursement->refresh();
    }

    /**
     * Pay a reimbursement installment (partial or full) for an approved claim.
     *
     * @param  array<string, mixed>  $data
     */
    public static function pay(FinancialDisbursement $disbursement, array $data, ?User $user = null): FinancialDisbursementPayment
    {
        $transaction = static::sourceTransaction($disbursement);
        $user = $user ?? auth()->user();
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'pay_employee_reimbursement', $user);

        if ($disbursement->disbursement_type !== FinancialDisbursementType::EMPLOYEE_PERSONAL) {
            throw ValidationException::withMessages([
                'disbursement' => __('financial.errors.only_personal_claims_can_be_reimbursed'),
            ]);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        $requestKey = trim((string) ($data['request_key'] ?? ''));
        if ($requestKey === '') {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.request_key_required'),
            ]);
        }

        $payment = DB::transaction(function () use ($disbursement, $data, $amount, $requestKey, $user, $transaction) {
            $existing = FinancialDisbursementPayment::where('request_key', $requestKey)->lockForUpdate()->first();

            if ($existing) {
                if ((int) $existing->financial_disbursement_id !== (int) $disbursement->id
                    || abs((float) $existing->amount - $amount) > 0.009) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $existing;
            }

            $locked = FinancialDisbursement::whereKey($disbursement->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [
                FinancialDisbursementStatus::APPROVED,
                FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.claim_not_approved'),
                ]);
            }

            $remaining = $locked->remainingDue();

            if ($amount > $remaining + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.exceeds_employee_due', [
                        'amount' => number_format($remaining, 2),
                    ]),
                ]);
            }

            $treasury = FinancialTreasuryService::post(
                companyId: (int) $locked->company_id,
                direction: FinancialCashDirection::OUTGOING,
                amount: $amount,
                description: __('financial.descriptions.reimbursement_paid', [
                    'number' => $locked->disbursement_number,
                    'transaction' => $transaction->transaction_number,
                ]),
                metadata: [
                    'source' => 'financial_disbursement_payment',
                    'financial_disbursement_id' => $locked->id,
                ],
                executedByEmployeeId: $locked->paid_by_employee_id,
                counterparty: $locked->paidByEmployee?->user?->name,
            );

            $payment = FinancialDisbursementPayment::create([
                'financial_disbursement_id' => $locked->id,
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'request_key' => $requestKey,
                'amount' => $amount,
                'treasury_transaction_id' => $treasury->id,
                'paid_by' => $user?->id ?? auth()->id(),
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $paidTotal = $locked->fresh()->reimbursedTotal();
            $status = $paidTotal + 0.009 >= (float) $locked->amount
                ? FinancialDisbursementStatus::FULLY_REIMBURSED
                : FinancialDisbursementStatus::PARTIALLY_REIMBURSED;

            $locked->update(['status' => $status->value]);

            return $payment;
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_reimbursement_paid',
            payload: [
                'disbursement_number' => $disbursement->disbursement_number,
                'payment_number' => $payment->payment_number,
                'amount' => $amount,
            ],
            userId: $user?->id,
        );

        DB::afterCommit(function () use ($transaction, $disbursement, $payment) {
            OperationsNotificationService::notifyFinancialReimbursementPaid($transaction, $disbursement, $payment);
        });

        return $payment->refresh();
    }

    public static function reversePayment(FinancialDisbursementPayment $payment, string $reason, ?User $user = null): FinancialDisbursementPayment
    {
        $disbursement = $payment->disbursement()->firstOrFail();
        $transaction = static::sourceTransaction($disbursement);
        $user = $user ?? auth()->user();
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'reverse_financial_transaction', $user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.reversal_reason_required'),
            ]);
        }

        DB::transaction(function () use ($payment, $reason, $user) {
            $locked = FinancialDisbursementPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isReversed()) {
                return;
            }

            $reversal = FinancialTreasuryService::post(
                companyId: (int) $locked->company_id,
                direction: FinancialCashDirection::INCOMING,
                amount: (float) $locked->amount,
                description: __('financial.descriptions.reimbursement_reversed', ['number' => $locked->payment_number]),
                metadata: [
                    'source' => 'financial_disbursement_payment_reversal',
                    'financial_disbursement_payment_id' => $locked->id,
                    'reversal_of_treasury_transaction_id' => $locked->treasury_transaction_id,
                ],
            );

            $meta = $locked->metadata ?? [];
            $meta['reversed_at'] = now()->toDateTimeString();
            $meta['reversed_by'] = $user?->id ?? auth()->id();
            $meta['reversal_reason'] = $reason;
            $meta['reversal_treasury_transaction_id'] = $reversal->id;

            $locked->update(['metadata' => $meta]);

            $claim = FinancialDisbursement::whereKey($locked->financial_disbursement_id)->lockForUpdate()->firstOrFail();
            $remaining = $claim->remainingDue();
            $status = $remaining <= 0.009
                ? FinancialDisbursementStatus::FULLY_REIMBURSED
                : ($claim->reimbursedTotal() > 0
                    ? FinancialDisbursementStatus::PARTIALLY_REIMBURSED
                    : FinancialDisbursementStatus::APPROVED);

            $claim->update(['status' => $status->value]);
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_reimbursement_reversed',
            payload: [
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'reason' => $reason,
            ],
            userId: $user?->id,
        );

        return $payment->refresh();
    }

    public static function reverseDirect(FinancialDisbursement $disbursement, string $reason, ?User $user = null): FinancialDisbursement
    {
        $transaction = static::sourceTransaction($disbursement);
        $user = $user ?? auth()->user();
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'reverse_financial_transaction', $user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.reversal_reason_required'),
            ]);
        }

        DB::transaction(function () use ($disbursement, $reason, $user) {
            $locked = FinancialDisbursement::whereKey($disbursement->id)->lockForUpdate()->firstOrFail();

            if ($locked->isReversed()) {
                return;
            }

            if ($locked->disbursement_type !== FinancialDisbursementType::TREASURY_DIRECT) {
                throw ValidationException::withMessages([
                    'disbursement' => __('financial.errors.only_direct_can_be_reversed_here'),
                ]);
            }

            FinancialTreasuryService::post(
                companyId: (int) $locked->company_id,
                direction: FinancialCashDirection::INCOMING,
                amount: (float) $locked->amount,
                description: __('financial.descriptions.disbursement_reversed', ['number' => $locked->disbursement_number]),
                metadata: [
                    'source' => 'financial_disbursement_reversal',
                    'financial_disbursement_id' => $locked->id,
                    'reversal_of_treasury_transaction_id' => $locked->treasury_transaction_id,
                ],
            );

            $meta = $locked->metadata ?? [];
            $meta['reversed_at'] = now()->toDateTimeString();
            $meta['reversed_by'] = $user?->id ?? auth()->id();
            $meta['reversal_reason'] = $reason;

            $locked->update(['metadata' => $meta]);
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        return $disbursement->refresh();
    }

    /**
     * Link an existing treasury movement to a paid direct disbursement or a
     * reimbursement payment.
     */
    public static function linkExisting(Model $document, TreasuryTransaction $treasury, ?User $user = null): Model
    {
        $disbursement = $document instanceof FinancialDisbursementPayment
            ? $document->disbursement()->firstOrFail()
            : $document;

        $transaction = static::sourceTransaction($disbursement);
        $user = $user ?? auth()->user();
        ServiceFinancialWorkflowService::authorize($transaction, 'confirm_cash_handover', $user);

        if (! empty($document->treasury_transaction_id)) {
            throw ValidationException::withMessages([
                'treasury_transaction_id' => __('financial.errors.posting_already_linked'),
            ]);
        }

        FinancialTreasuryService::assertLinkable(
            $treasury,
            (int) $document->company_id,
            FinancialCashDirection::OUTGOING,
            (float) $document->amount,
        );

        $document->update(['treasury_transaction_id' => $treasury->id]);

        return $document->refresh();
    }

    public static function newRequestKey(string $scope = 'dsb'): string
    {
        return $scope.':'.Str::uuid()->toString();
    }

    public static function sourceTransaction(FinancialDisbursement $disbursement): ServiceTransaction
    {
        if ($disbursement->source_type !== FinancialDisbursement::SOURCE_SERVICE_TRANSACTION) {
            throw ValidationException::withMessages([
                'source_type' => __('financial.errors.unsupported_source'),
            ]);
        }

        return ServiceTransaction::findOrFail($disbursement->source_id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
