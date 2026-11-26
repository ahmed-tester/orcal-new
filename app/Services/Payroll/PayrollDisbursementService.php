<?php

namespace App\Services\Payroll;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialPaymentMethod;
use App\Enums\PaymentItemCategory;
use App\Enums\Payroll\FinancialApprovalItemStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\FinancialApprovalRequestItem;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\FinancialTreasuryService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\OperationsAuditService;
use App\Services\ServiceFinancialWorkflowService;
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

        throw ValidationException::withMessages([
            'approval_request' => __('financial.errors.payroll_requires_approval_workflow'),
        ]);
    }

    /**
     * Execute payroll disbursement for an approved request item (both cash and bank immediately) inside the caller's transaction.
     *
     * @return array{status: string, payment: ?PayrollPayment, request: ?array<string, mixed>, already_executed?: bool}
     */
    public static function disburseFromApprovedRequest(
        FinancialApprovalRequestItem $item,
        FinancialApprovalRequest $request,
        User $manager,
    ): array {
        if (DB::transactionLevel() < 1 || ! FinancialApprovalService::isExecutingRequest((int) $request->id)) {
            throw new AuthorizationException(__('financial.errors.unauthorized_manager_approval'));
        }

        $lockedRequest = FinancialApprovalRequest::query()
            ->with(['companies:id'])
            ->lockForUpdate()
            ->findOrFail($request->id);

        if ($lockedRequest->status !== FinancialApprovalStatus::PENDING_MANAGER) {
            throw ValidationException::withMessages([
                'status' => __('financial.errors.request_not_pending_manager'),
            ]);
        }

        if (! PayrollApprovalScopeService::isAssignedManager($manager, $lockedRequest)) {
            throw new AuthorizationException(__('financial.errors.unauthorized_manager_approval'));
        }

        $lockedItem = FinancialApprovalRequestItem::query()
            ->lockForUpdate()
            ->findOrFail($item->id);

        if ((int) $lockedItem->request_id !== (int) $lockedRequest->id
            || (int) $lockedItem->version !== (int) $lockedRequest->version
            || $lockedItem->status !== FinancialApprovalItemStatus::PENDING) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_item_fingerprint_mismatch'),
            ]);
        }

        $locked = PayrollRecord::whereKey($lockedItem->payroll_record_id)->lockForUpdate()->firstOrFail();

        PayrollApprovalSnapshotService::verifyItemMatchesSourceRecord($lockedItem, $locked);

        $method = FinancialPaymentMethod::tryFrom((string) $lockedItem->payment_method);
        if (! $method instanceof FinancialPaymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => __('financial.errors.invalid_payment_method'),
            ]);
        }

        $amount = round((float) $lockedItem->requested_amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        $paymentDate = now()->toDateString();
        $requestKey = (string) $lockedItem->idempotency_key;
        $fingerprint = hash('sha256', json_encode([
            'record_id' => $locked->id,
            'company_id' => (int) $locked->company_id,
            'operation_type' => 'payroll',
            'amount' => $amount,
            'method' => $method->value,
            'request_id' => $lockedRequest->id,
            'version' => (int) $lockedRequest->version,
            'item_id' => $lockedItem->id,
        ], JSON_THROW_ON_ERROR));

        $locked = PayrollRecordFinancialService::ensureManaged($locked, $manager);

        $idempotent = static::findIdempotentRequest($locked, $requestKey, $fingerprint);
        if ($idempotent !== null) {
            return $idempotent;
        }

        $deductionKeys = static::resolveDeductionKeys($locked, ['confirm_reserved_deductions' => true]);
        if ($deductionKeys !== []) {
            PayrollDeductionSettlementService::confirmBatch($locked, $deductionKeys, $manager, false);
            $locked = $locked->fresh();
        }

        static::assertWithinAvailable($locked, $amount);

        $result = static::executeTreasuryAndPayment(
            locked: $locked,
            actor: $manager,
            method: $method,
            amount: $amount,
            requestKey: $requestKey,
            fingerprint: $fingerprint,
            paymentDate: $paymentDate,
            destinationRef: $lockedItem->destination_reference,
            referenceNumber: $lockedItem->reference_number,
            proofPath: $lockedItem->proof_path,
            notes: $lockedItem->reason_description,
        );

        if (! empty($result['payment']) && $result['payment'] instanceof PayrollPayment) {
            $meta = $result['payment']->metadata ?? [];
            $meta['approval_request_id'] = $lockedRequest->id;
            $meta['approval_request_number'] = $lockedRequest->request_number;
            $meta['approval_item_id'] = $lockedItem->id;
            $meta['approval_version'] = (int) $lockedRequest->version;
            $meta['treasury_reviewer_id'] = $lockedRequest->treasury_reviewer_id;
            $meta['approved_by_manager_id'] = $manager->id;
            $result['payment']->updateQuietly(['metadata' => $meta]);
        }

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
        throw ValidationException::withMessages([
            'approval_request' => __('financial.errors.payroll_requires_approval_workflow'),
        ]);
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
