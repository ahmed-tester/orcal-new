<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialPaymentMethod;
use App\Enums\PaymentItemCategory;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollDisbursementService
{
    public static function newRequestKey(): string
    {
        return 'pay:'.Str::uuid()->toString();
    }

    /**
     * Authorize company, branch, and permission for a payroll disbursement action.
     */
    public static function authorize(PayrollRecord $record, string $permission, ?User $user = null): User
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            throw new AuthorizationException(__('financial.errors.unauthenticated'));
        }

        ServiceFinancialWorkflowService::authorizeCompany($user, (int) $record->company_id, $permission);

        $branchId = $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id;
        if ($branchId && $user->branch_id && ! $user->canAccessAllBranches()
            && (int) $user->branch_id !== (int) $branchId) {
            throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
        }

        return $user;
    }

    /**
     * Disburse salary (cash immediately, or non-cash via pending request + confirmed transfer).
     *
     * @param  array<string, mixed>  $data
     * @return array{status: string, payment: ?PayrollPayment, request: ?array<string, mixed>}
     */
    public static function disburse(PayrollRecord $record, array $data, ?User $actor = null): array
    {
        if (! $record->isPayroll()) {
            throw ValidationException::withMessages([
                'operation_type' => __('financial.errors.collection_cannot_disburse_salary'),
            ]);
        }

        $actor = static::authorize($record, 'disburse_payroll_salary', $actor);

        $rawMethod = trim((string) ($data['payment_method'] ?? FinancialPaymentMethod::CASH->value));
        if ($rawMethod === 'cheque') {
            throw ValidationException::withMessages([
                'payment_method' => __('financial.errors.cheque_not_supported'),
            ]);
        }

        $method = FinancialPaymentMethod::tryFrom($rawMethod);
        if (! $method instanceof FinancialPaymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => __('financial.errors.invalid_payment_method'),
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

        $paymentDate = ! empty($data['payment_date'])
            ? Carbon::parse($data['payment_date'])->toDateString()
            : now()->toDateString();

        $destinationRef = $data['destination_reference'] ?? null;
        $referenceNumber = $data['reference_number'] ?? null;
        $proofPath = $data['proof_path'] ?? null;

        $fingerprint = hash('sha256', json_encode([
            'record_id' => $record->id,
            'company_id' => (int) $record->company_id,
            'operation_type' => 'payroll',
            'amount' => $amount,
            'method' => $method->value,
            'date' => $paymentDate,
            'destination' => $destinationRef,
        ], JSON_THROW_ON_ERROR));

        $result = DB::transaction(function () use (
            $record,
            $data,
            $actor,
            $method,
            $amount,
            $requestKey,
            $paymentDate,
            $destinationRef,
            $referenceNumber,
            $proofPath,
            $fingerprint
        ): array {
            DB::table('companies')->where('id', $record->company_id)->lockForUpdate()->first();
            $locked = PayrollRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $locked = PayrollRecordFinancialService::ensureManaged($locked, $actor);

            $idempotent = static::findIdempotentRequest($locked, $requestKey, $fingerprint);
            if ($idempotent !== null) {
                return $idempotent;
            }

            $deductionKeys = static::resolveDeductionKeys($locked, $data);

            $isConfirmedNow = $method->isCash()
                || (! empty($data['confirm']) && ! empty($referenceNumber) && ! empty($proofPath));

            if (! $isConfirmedNow) {
                return static::storePendingTransfer(
                    locked: $locked,
                    actor: $actor,
                    method: $method,
                    amount: $amount,
                    requestKey: $requestKey,
                    fingerprint: $fingerprint,
                    paymentDate: $paymentDate,
                    destinationRef: $destinationRef,
                    referenceNumber: $referenceNumber,
                    proofPath: $proofPath,
                    deductionKeys: $deductionKeys,
                );
            }

            // Confirm linked deductions atomically ONLY when actual disbursement is executing now.
            if ($deductionKeys !== []) {
                PayrollDeductionSettlementService::confirmBatch($locked, $deductionKeys, $actor, false);
                $locked = $locked->fresh();
            }

            static::assertWithinAvailable($locked, $amount);

            return static::executeTreasuryAndPayment(
                locked: $locked,
                actor: $actor,
                method: $method,
                amount: $amount,
                requestKey: $requestKey,
                fingerprint: $fingerprint,
                paymentDate: $paymentDate,
                destinationRef: $destinationRef,
                referenceNumber: $referenceNumber,
                proofPath: $proofPath,
                notes: $data['notes'] ?? null,
            );
        });

        static::dispatchDisbursementNotification($record, $result, $actor);

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findIdempotentRequest(PayrollRecord $locked, string $requestKey, string $fingerprint): ?array
    {
        $existingRequests = $locked->metadata['disbursement_requests'] ?? [];
        if (! isset($existingRequests[$requestKey])) {
            return null;
        }

        $existing = $existingRequests[$requestKey];
        if (($existing['fingerprint'] ?? null) !== $fingerprint) {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.request_key_reused'),
            ]);
        }

        $payment = ! empty($existing['payment_id'])
            ? PayrollPayment::find($existing['payment_id'])
            : null;

        return [
            'status' => $existing['status'] ?? ($payment ? 'executed' : 'pending'),
            'payment' => $payment,
            'request' => $existing,
            'already_executed' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private static function resolveDeductionKeys(PayrollRecord $locked, array $data): array
    {
        $deductionKeys = (array) ($data['deduction_request_keys'] ?? []);
        if (! empty($data['confirm_reserved_deductions'])) {
            $reservedKeys = collect($locked->metadata['linked_collection_deductions'] ?? [])
                ->where('status', 'reserved')
                ->keys()
                ->all();
            $deductionKeys = array_values(array_unique(array_merge($deductionKeys, $reservedKeys)));
        }

        return array_values(array_unique(array_filter(array_map('strval', $deductionKeys))));
    }

    private static function assertWithinAvailable(PayrollRecord $locked, float $amount): void
    {
        $summary = PayrollRecordFinancialService::payrollSummary($locked);
        if ($amount > $summary['available_for_disbursement'] + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.exceeds_remaining_salary', [
                    'amount' => number_format($summary['available_for_disbursement'], 2),
                ]),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $deductionKeys
     * @return array<string, mixed>
     */
    private static function storePendingTransfer(
        PayrollRecord $locked,
        User $actor,
        FinancialPaymentMethod $method,
        float $amount,
        string $requestKey,
        string $fingerprint,
        string $paymentDate,
        ?string $destinationRef,
        ?string $referenceNumber,
        ?string $proofPath,
        array $deductionKeys,
    ): array {
        static::assertWithinAvailable($locked, $amount);

        $pendingEntry = [
            'request_key' => $requestKey,
            'fingerprint' => $fingerprint,
            'amount' => $amount,
            'payment_method' => $method->value,
            'payment_date' => $paymentDate,
            'destination_reference' => $destinationRef,
            'reference_number' => $referenceNumber,
            'proof_path' => $proofPath,
            'deduction_request_keys' => $deductionKeys,
            'status' => 'pending_confirmation',
            'requested_by' => $actor->id,
            'requested_at' => now()->toDateTimeString(),
        ];

        $meta = $locked->metadata ?? [];
        $meta['disbursement_requests'][$requestKey] = $pendingEntry;
        $meta['pending_bank_transfers'][$requestKey] = $pendingEntry;
        $locked->metadata = $meta;
        $locked->saveQuietly();

        OperationsAuditService::recordFor(
            model: $locked,
            actionType: 'payroll_disbursement_requested',
            payload: $pendingEntry,
            userId: $actor->id,
            companyId: (int) $locked->company_id,
        );

        return [
            'status' => 'pending_confirmation',
            'payment' => null,
            'request' => $pendingEntry,
            'already_executed' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function executeTreasuryAndPayment(
        PayrollRecord $locked,
        User $actor,
        FinancialPaymentMethod $method,
        float $amount,
        string $requestKey,
        string $fingerprint,
        string $paymentDate,
        ?string $destinationRef,
        ?string $referenceNumber,
        ?string $proofPath,
        ?string $notes,
    ): array {
        $employeeName = $locked->employee?->user?->name ?? ('#'.$locked->employee_id);
        $monthFormatted = Carbon::parse($locked->payroll_month)->format('Y-m');

        $treasuryTx = FinancialTreasuryService::post(
            companyId: (int) $locked->company_id,
            direction: FinancialCashDirection::OUTGOING,
            amount: $amount,
            description: __('financial.descriptions.payroll_disbursement', [
                'employee' => $employeeName,
                'month' => $monthFormatted,
                'number' => $locked->record_number,
            ]),
            metadata: [
                'source' => 'payroll_payment',
                'payroll_record_id' => $locked->id,
                'request_key' => $requestKey,
                'payment_method' => $method->value,
                'reference_number' => $referenceNumber,
                'destination_reference' => $destinationRef,
            ],
            executedByEmployeeId: $actor->employee?->id,
            counterparty: $employeeName,
            category: PaymentItemCategory::SALARY_PAYMENT,
            transactionDate: $paymentDate,
        );

        $payment = PayrollPayment::create([
            'payroll_record_id' => $locked->id,
            'company_id' => $locked->company_id,
            'employee_id' => $locked->employee_id,
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_method' => $method->value,
            'treasury_transaction_id' => $treasuryTx->id,
            'executed_by' => $actor->employee?->id,
            'notes' => $notes,
            'metadata' => [
                'financial_flow' => PayrollRecord::FLOW_MANAGED,
                'request_key' => $requestKey,
                'fingerprint' => $fingerprint,
                'reference_number' => $referenceNumber,
                'proof_path' => $proofPath,
                'destination_reference' => $destinationRef,
            ],
        ]);

        $executedEntry = [
            'request_key' => $requestKey,
            'fingerprint' => $fingerprint,
            'payment_id' => $payment->id,
            'treasury_transaction_id' => $treasuryTx->id,
            'amount' => $amount,
            'payment_method' => $method->value,
            'payment_date' => $paymentDate,
            'status' => 'executed',
            'executed_by' => $actor->id,
            'executed_at' => now()->toDateTimeString(),
        ];

        $meta = $locked->fresh()->metadata ?? [];
        $meta['disbursement_requests'][$requestKey] = $executedEntry;
        unset($meta['pending_bank_transfers'][$requestKey]);
        $locked->metadata = $meta;
        $locked->saveQuietly();

        PayrollRecordFinancialService::syncPayroll($locked);

        OperationsAuditService::recordFor(
            model: $locked,
            actionType: 'payroll_disbursement_executed',
            payload: $executedEntry,
            userId: $actor->id,
            companyId: (int) $locked->company_id,
        );

        return [
            'status' => 'executed',
            'payment' => $payment->refresh(),
            'request' => $executedEntry,
            'already_executed' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function dispatchDisbursementNotification(PayrollRecord $record, array $result, User $actor): void
    {
        if (! empty($result['already_executed'])) {
            return;
        }

        if ($result['status'] === 'pending_confirmation') {
            DB::afterCommit(function () use ($record, $result, $actor): void {
                NotificationDispatcher::translated(
                    record: $record,
                    eventKey: 'PayrollDisbursementRequested',
                    titleKey: 'financial.notifications.payroll_transfer_requested_title',
                    bodyKey: 'financial.notifications.payroll_transfer_requested_body',
                    parameters: [
                        'record' => (string) $record->record_number,
                        'amount' => number_format((float) ($result['request']['amount'] ?? 0), 2),
                    ],
                    icon: 'heroicon-o-banknotes',
                    color: 'warning',
                    excludeUserIds: [$actor->id],
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'event_key' => 'PayrollDisbursementRequested',
                        'transfer_request_key' => (string) ($result['request']['request_key'] ?? ''),
                        'required_permission' => 'disburse_payroll_salary',
                        'company_id' => (int) $record->company_id,
                        'branch_id' => $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id,
                        'is_action_request' => true,
                    ],
                );
            });

            return;
        }

        if ($result['status'] === 'executed' && $result['payment']) {
            $beneficiaryUserId = $record->employee?->user_id;
            if ($beneficiaryUserId && (int) $beneficiaryUserId !== (int) $actor->id) {
                DB::afterCommit(function () use ($record, $result, $beneficiaryUserId, $actor): void {
                    NotificationDispatcher::translated(
                        record: $record,
                        eventKey: 'PayrollDisbursementExecuted',
                        titleKey: 'financial.notifications.payroll_disbursed_title',
                        bodyKey: 'financial.notifications.payroll_disbursed_body',
                        parameters: [
                            'record' => (string) $record->record_number,
                            'amount' => number_format((float) $result['payment']->amount, 2),
                        ],
                        icon: 'heroicon-o-check-circle',
                        color: 'success',
                        excludeUserIds: [$actor->id],
                        context: [
                            'routing_mode' => 'operations_targeted',
                            'target_user_ids' => [(int) $beneficiaryUserId],
                            'subject_type' => PayrollPayment::class,
                            'subject_id' => $result['payment']->id,
                            'company_id' => (int) $record->company_id,
                            'branch_id' => $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id,
                        ],
                    );
                });
            }
        }
    }

    /**
     * Confirm a previously requested non-cash payroll transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public static function confirmPendingTransfer(PayrollRecord $record, string $requestKey, array $data, ?User $actor = null): PayrollPayment
    {
        $pending = $record->metadata['pending_bank_transfers'][$requestKey] ?? null;
        if (! is_array($pending)) {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.pending_transfer_not_found'),
            ]);
        }

        if (empty($data['reference_number']) || empty($data['proof_path'])) {
            throw ValidationException::withMessages([
                'reference_number' => __('financial.errors.non_cash_destination_required'),
            ]);
        }

        $actor = static::authorize($record, 'disburse_payroll_salary', $actor);

        return DB::transaction(function () use ($record, $requestKey, $pending, $data, $actor): PayrollPayment {
            DB::table('companies')->where('id', $record->company_id)->lockForUpdate()->first();
            $locked = PayrollRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();

            $meta = $locked->metadata ?? [];
            if (empty($meta['pending_bank_transfers'][$requestKey])) {
                $existingId = $meta['disbursement_requests'][$requestKey]['payment_id'] ?? null;
                if ($existingId) {
                    return PayrollPayment::findOrFail($existingId);
                }

                throw ValidationException::withMessages([
                    'request_key' => __('financial.errors.pending_transfer_not_found'),
                ]);
            }

            unset($meta['pending_bank_transfers'][$requestKey], $meta['disbursement_requests'][$requestKey]);
            $locked->metadata = $meta;
            $locked->saveQuietly();

            $res = static::disburse($locked, [
                'request_key' => $requestKey,
                'amount' => $pending['amount'],
                'payment_method' => $pending['payment_method'],
                'payment_date' => $pending['payment_date'],
                'destination_reference' => $data['destination_reference'] ?? $pending['destination_reference'] ?? null,
                'reference_number' => $data['reference_number'],
                'proof_path' => $data['proof_path'],
                'notes' => $data['notes'] ?? null,
                'deduction_request_keys' => $pending['deduction_request_keys'] ?? [],
                'confirm' => true,
            ], $actor);

            return $res['payment'];
        });
    }

    /**
     * Reverse a managed PayrollPayment once, preserving the original row for audit.
     */
    public static function reverse(PayrollPayment $payment, string $reason, ?User $actor = null): PayrollPayment
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.reversal_reason_required'),
            ]);
        }

        $record = $payment->payrollRecord;
        if (! $record) {
            throw ValidationException::withMessages([
                'payroll_record' => __('financial.errors.unsupported_source'),
            ]);
        }

        $actor = static::authorize($record, 'reverse_financial_transaction', $actor);

        $wasReversed = false;

        $reversed = DB::transaction(function () use ($payment, $record, $reason, $actor, &$wasReversed): PayrollPayment {
            DB::table('companies')->where('id', $record->company_id)->lockForUpdate()->first();
            $lockedPayment = PayrollPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $lockedRecord = PayrollRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();

            $meta = $lockedPayment->metadata ?? [];
            if (! empty($meta['reversed_at'])) {
                return $lockedPayment;
            }

            $employeeName = $lockedRecord->employee?->user?->name ?? ('#'.$lockedRecord->employee_id);
            $reversalTx = FinancialTreasuryService::post(
                companyId: (int) $lockedRecord->company_id,
                direction: FinancialCashDirection::INCOMING,
                amount: (float) $lockedPayment->amount,
                description: __('financial.descriptions.payroll_disbursement_reversed', [
                    'number' => $lockedPayment->payment_number,
                    'reason' => $reason,
                ]),
                metadata: [
                    'source' => 'payroll_payment_reversal',
                    'payroll_payment_id' => $lockedPayment->id,
                    'payroll_record_id' => $lockedRecord->id,
                    'reversal_of_treasury_transaction_id' => $lockedPayment->treasury_transaction_id,
                ],
                executedByEmployeeId: $actor->employee?->id,
                counterparty: $employeeName,
                category: PaymentItemCategory::SALARY_PAYMENT,
            );

            $meta['reversed_at'] = now()->toDateTimeString();
            $meta['reversed_by'] = $actor->id;
            $meta['reversal_reason'] = $reason;
            $meta['reversal_treasury_transaction_id'] = $reversalTx->id;

            $lockedPayment->metadata = $meta;
            $lockedPayment->saveQuietly();

            PayrollRecordFinancialService::syncPayroll($lockedRecord);

            OperationsAuditService::recordFor(
                model: $lockedRecord,
                actionType: 'payroll_disbursement_reversed',
                payload: [
                    'payment_number' => $lockedPayment->payment_number,
                    'amount' => (float) $lockedPayment->amount,
                    'reason' => $reason,
                ],
                userId: $actor->id,
                companyId: (int) $lockedRecord->company_id,
            );

            $wasReversed = true;

            return $lockedPayment->refresh();
        });

        $targets = array_values(array_unique(array_filter([
            $record->employee?->user_id,
            $record->created_by,
        ])));

        if ($wasReversed && $targets !== []) {
            DB::afterCommit(function () use ($record, $reversed, $reason, $targets, $actor): void {
                NotificationDispatcher::translated(
                    record: $record,
                    eventKey: 'PayrollDisbursementReversed',
                    titleKey: 'financial.notifications.payroll_disbursed_reversed_title',
                    bodyKey: 'financial.notifications.payroll_disbursed_reversed_body',
                    parameters: [
                        'record' => (string) $record->record_number,
                        'payment' => (string) $reversed->payment_number,
                        'amount' => number_format((float) $reversed->amount, 2),
                        'reason' => $reason,
                    ],
                    icon: 'heroicon-o-arrow-uturn-left',
                    color: 'danger',
                    excludeUserIds: [$actor->id],
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'target_user_ids' => $targets,
                        'subject_type' => PayrollPayment::class,
                        'subject_id' => $reversed->id,
                        'company_id' => (int) $record->company_id,
                        'branch_id' => $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id,
                    ],
                );
            });
        }

        return $reversed;
    }
}
