<?php

namespace App\Services\Payroll;

use App\Enums\FinancialPaymentMethod;
use App\Enums\Payroll\FinancialApprovalItemStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Models\Company;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\FinancialApprovalRequestItem;
use App\Models\Payroll\FinancialApprovalRequestLog;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\Departments\DepartmentManagerResolverService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FinancialApprovalService
{
    private static ?int $executingRequestId = null;

    public static function isExecutingRequest(int $requestId): bool
    {
        return static::$executingRequestId === $requestId;
    }

    /**
     * Submit a batch of payroll records for treasury review and Layer 1 manager approval.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function submitPayrollBatch(
        array $items,
        User $submitter,
        ?string $payrollMonth = null,
        ?string $notes = null,
        ?string $attemptKey = null,
    ): FinancialApprovalRequest {
        if (! DepartmentManagerResolverService::isActiveStatus($submitter->status)
            || (! $submitter->isSuperAdmin() && ! $submitter->hasPermissionTo('submit_financial_request'))) {
            throw new AuthorizationException(__('financial.errors.unauthorized_submit_request'));
        }

        $recordIds = static::extractValidatedRecordIds($items);
        $normalizedAttemptKey = $attemptKey !== null && trim($attemptKey) !== '' ? trim($attemptKey) : null;
        $inputFingerprint = static::computeBatchInputFingerprint($items, $payrollMonth);

        try {
            $outcome = DB::transaction(function () use (
                $items,
                $recordIds,
                $submitter,
                $payrollMonth,
                $notes,
                $normalizedAttemptKey,
                $inputFingerprint,
            ): array {
                [$preparedItems, $companyIds, $totalAmount] = static::lockAndPreparePayrollItems(
                    $items,
                    $recordIds,
                    $payrollMonth,
                    $submitter,
                    $normalizedAttemptKey,
                );

                if ($existing = static::findIdempotentSubmission($normalizedAttemptKey, $submitter, $inputFingerprint)) {
                    return ['request' => $existing, 'reviewers' => collect(), 'idempotent' => true];
                }

                $eligibleReviewers = PayrollApprovalScopeService::findEligibleReviewers($companyIds);
                if ($eligibleReviewers->isEmpty()) {
                    $companyNames = Company::query()->whereIn('id', $companyIds)->pluck('trade_name')->implode('، ');
                    Log::warning('No eligible treasury reviewer found for payroll approval request', [
                        'submitter_id' => $submitter->id,
                        'requested_company_ids' => $companyIds,
                        'requested_companies' => $companyNames,
                        'diagnostics' => PayrollApprovalScopeService::diagnoseReviewerEligibility($companyIds),
                    ]);

                    throw ValidationException::withMessages([
                        'companies' => __('financial.errors.no_eligible_treasury_reviewer', [
                            'companies' => $companyNames ?: implode(', ', $companyIds),
                        ]),
                    ]);
                }

                $request = static::persistSubmittedRequest(
                    $preparedItems,
                    $companyIds,
                    $totalAmount,
                    $submitter,
                    $payrollMonth,
                    $notes,
                    $normalizedAttemptKey,
                    $inputFingerprint,
                );

                return ['request' => $request, 'reviewers' => $eligibleReviewers, 'idempotent' => false];
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($normalizedAttemptKey !== null) {
                $existing = static::findIdempotentSubmission($normalizedAttemptKey, $submitter, $inputFingerprint);
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }

        if (! $outcome['idempotent'] && $outcome['reviewers']->isNotEmpty()) {
            FinancialApprovalNotificationService::dispatch(
                $outcome['reviewers'],
                $outcome['request'],
                'FinancialApprovalSubmitted',
                'financial.notifications.approval_submitted_title',
                'financial.notifications.approval_submitted_body',
                'treasury',
                'warning',
            );
        }

        return $outcome['request'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, int>
     */
    protected static function extractValidatedRecordIds(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_items_required'),
            ]);
        }

        $recordIds = collect($items)
            ->pluck('payroll_record_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (count($recordIds) !== count($items)) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.duplicate_or_invalid_payroll_items'),
            ]);
        }

        return $recordIds;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected static function computeBatchInputFingerprint(array $items, ?string $payrollMonth): string
    {
        $normalized = collect($items)
            ->map(fn (array $row): array => [
                'payroll_record_id' => (int) ($row['payroll_record_id'] ?? 0),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'payment_method' => trim((string) ($row['payment_method'] ?? FinancialPaymentMethod::CASH->value)),
                'destination_reference' => $row['destination_reference'] ?? null,
                'reference_number' => $row['reference_number'] ?? null,
            ])
            ->sortBy('payroll_record_id')
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'payroll_month' => $payrollMonth,
            'items' => $normalized,
        ], JSON_THROW_ON_ERROR));
    }

    protected static function findIdempotentSubmission(
        ?string $attemptKey,
        User $submitter,
        string $inputFingerprint,
    ): ?FinancialApprovalRequest {
        if (! $attemptKey) {
            return null;
        }

        $existing = FinancialApprovalRequest::query()
            ->where('submitted_by', $submitter->id)
            ->where('attempt_key', $attemptKey)
            ->where('status', '!=', FinancialApprovalStatus::CANCELLED->value)
            ->lockForUpdate()
            ->first();

        if (! $existing) {
            return null;
        }

        if (! PayrollApprovalScopeService::canViewRequest($submitter, $existing)) {
            throw new AuthorizationException(__('financial.errors.unauthorized_submit_request'));
        }

        if ($existing->input_fingerprint !== null && $existing->input_fingerprint !== $inputFingerprint) {
            throw ValidationException::withMessages([
                'attempt_key' => __('financial.errors.request_key_reused'),
            ]);
        }

        return $existing->load(['companies', 'items']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $recordIds
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, int>, 2: float}
     */
    protected static function lockAndPreparePayrollItems(
        array $items,
        array $recordIds,
        ?string $payrollMonth,
        User $submitter,
        ?string $attemptKey = null,
    ): array {
        $records = PayrollRecord::query()
            ->with(['employee.user', 'company'])
            ->whereIn('id', $recordIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($records->count() !== count($recordIds)) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.payroll_record_not_found'),
            ]);
        }

        foreach ($records as $record) {
            PayrollApprovalScopeService::authorizeSubmitterForRecord($submitter, $record);
        }

        if ($attemptKey !== null) {
            $existing = FinancialApprovalRequest::query()
                ->where('submitted_by', $submitter->id)
                ->where('attempt_key', $attemptKey)
                ->where('status', '!=', FinancialApprovalStatus::CANCELLED->value)
                ->exists();
            if ($existing) {
                return [[], [], 0.0];
            }
        }

        $reservedByRecord = FinancialApprovalRequestItem::query()
            ->whereIn('payroll_record_id', $recordIds)
            ->where('status', FinancialApprovalItemStatus::PENDING->value)
            ->whereHas('request', fn ($q) => $q->whereIn('status', [
                FinancialApprovalStatus::PENDING_TREASURY->value,
                FinancialApprovalStatus::PENDING_MANAGER->value,
            ]))
            ->lockForUpdate()
            ->get()
            ->groupBy('payroll_record_id')
            ->map(fn (Collection $group) => round((float) $group->sum('requested_amount'), 2));

        $preparedItems = [];
        $companyIds = [];
        $totalAmount = 0.0;

        foreach ($items as $input) {
            $recordId = (int) ($input['payroll_record_id'] ?? 0);
            /** @var PayrollRecord $record */
            $record = $records->get($recordId);
            $prepared = static::prepareSinglePayrollItem(
                $input,
                $record,
                (float) ($reservedByRecord->get($recordId) ?? 0.0),
                $payrollMonth,
            );

            $preparedItems[] = $prepared;
            $companyIds[$prepared['company_id']] = $prepared['company_id'];
            $totalAmount = round($totalAmount + $prepared['requested_amount'], 2);
        }

        return [$preparedItems, array_values($companyIds), $totalAmount];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected static function prepareSinglePayrollItem(
        array $input,
        PayrollRecord $record,
        float $alreadyReserved,
        ?string $payrollMonth,
    ): array {
        if (! $record->isPayroll()) {
            throw ValidationException::withMessages([
                'operation_type' => __('financial.errors.collection_cannot_disburse_salary'),
            ]);
        }

        $rawMethod = trim((string) ($input['payment_method'] ?? FinancialPaymentMethod::CASH->value));
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

        $requestedAmount = round((float) ($input['amount'] ?? 0), 2);
        if ($requestedAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        $available = $record->isManaged()
            ? (float) PayrollRecordFinancialService::payrollSummary($record)['available_for_disbursement']
            : (float) $record->remaining_amount;
        $unreservedAvailable = round(max(0.0, $available - $alreadyReserved), 2);

        if ($requestedAmount > $unreservedAvailable + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.exceeds_unreserved_salary', [
                    'employee' => $record->employee?->user?->name ?? ('#'.$record->employee_id),
                    'amount' => number_format($unreservedAvailable, 2),
                ]),
            ]);
        }

        $companyId = (int) $record->company_id;
        $branchId = $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id;
        $monthStr = $payrollMonth ?: substr((string) $record->payroll_month, 0, 7);
        $previouslyPaid = round((float) ($record->paid_amount ?? 0), 2);

        $snapshot = PayrollApprovalSnapshotService::buildSnapshot($record, $requestedAmount, $method, $monthStr);

        return [
            'payroll_record_id' => $record->id,
            'employee_id' => (int) $record->employee_id,
            'company_id' => $companyId,
            'branch_id' => $branchId ? (int) $branchId : null,
            'payroll_month' => $monthStr,
            'employee_name_snapshot' => $record->employee?->user?->name ?? ('#'.$record->employee_id),
            'employee_number_snapshot' => $record->employee?->employee_number,
            'net_amount' => round((float) $record->net_amount, 2),
            'previously_paid_amount' => $previouslyPaid,
            'requested_amount' => $requestedAmount,
            'payment_method' => $method->value,
            'destination_reference' => $input['destination_reference'] ?? null,
            'reference_number' => $input['reference_number'] ?? null,
            'proof_path' => $input['proof_path'] ?? null,
            'reason_description' => $input['notes'] ?? $record->notes ?? __('financial.descriptions.payroll_approval_item', ['month' => $monthStr]),
            'data_fingerprint' => PayrollApprovalSnapshotService::computeFingerprint($snapshot),
            'snapshot' => $snapshot,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $preparedItems
     * @param  array<int, int>  $companyIds
     */
    protected static function persistSubmittedRequest(
        array $preparedItems,
        array $companyIds,
        float $totalAmount,
        User $submitter,
        ?string $payrollMonth,
        ?string $notes,
        ?string $attemptKey,
        string $inputFingerprint,
    ): FinancialApprovalRequest {
        $requestNumber = CodeGeneratorService::generate(
            modelClass: FinancialApprovalRequest::class,
            column: 'request_number',
            prefix: 'FAR',
            padLength: 5,
            ignoreCompany: true,
            sequenceFromMaximum: true,
        );

        $request = FinancialApprovalRequest::create([
            'request_number' => $requestNumber,
            'source_type' => FinancialApprovalRequest::SOURCE_PAYROLL,
            'payroll_month' => $payrollMonth ?: ($preparedItems[0]['payroll_month'] ?? null),
            'version' => 1,
            'status' => FinancialApprovalStatus::PENDING_TREASURY,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'employees_count' => count($preparedItems),
            'total_amount' => $totalAmount,
            'notes' => $notes,
            'attempt_key' => $attemptKey,
            'input_fingerprint' => $inputFingerprint,
            'metadata' => array_filter(['attempt_key' => $attemptKey]),
        ]);

        $request->companies()->sync($companyIds);

        foreach ($preparedItems as $itemData) {
            FinancialApprovalRequestItem::create(array_merge($itemData, [
                'request_id' => $request->id,
                'version' => 1,
                'idempotency_key' => sprintf('far:%d:v1:rec:%d', $request->id, $itemData['payroll_record_id']),
                'status' => FinancialApprovalItemStatus::PENDING,
            ]));
        }

        static::recordLog(
            request: $request,
            action: 'submitted',
            actor: $submitter,
            actorRole: 'submitter',
            fromStatus: null,
            toStatus: FinancialApprovalStatus::PENDING_TREASURY->value,
            reason: $notes,
            context: [
                'company_ids' => $companyIds,
                'employees_count' => count($preparedItems),
                'total_amount' => $totalAmount,
            ],
        );

        return $request->load(['companies', 'items']);
    }

    /**
     * First valid treasury reviewer approval atomically claims the request and routes it to their Layer 1 manager.
     */
    public static function approveByTreasury(
        FinancialApprovalRequest $request,
        User $reviewer,
        ?string $notes = null,
    ): FinancialApprovalRequest {
        $updated = DB::transaction(function () use ($request, $reviewer, $notes): FinancialApprovalRequest {
            $locked = FinancialApprovalRequest::query()
                ->with(['companies:id'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($locked->status !== FinancialApprovalStatus::PENDING_TREASURY) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.request_not_pending_treasury'),
                ]);
            }

            if (! PayrollApprovalScopeService::coversAllCompanies($reviewer, $locked->companies->modelKeys())) {
                throw new AuthorizationException(__('financial.errors.unauthorized_treasury_review'));
            }

            $resolved = DepartmentManagerResolverService::resolveForUser($reviewer);
            if (! $resolved['ok'] || ! $resolved['root_department'] || ! $resolved['manager_employee'] || ! $resolved['manager_user']) {
                throw ValidationException::withMessages([
                    'manager_id' => __('financial.errors.manager_path_unavailable'),
                ]);
            }

            $locked->update([
                'status' => FinancialApprovalStatus::PENDING_MANAGER,
                'treasury_reviewer_id' => $reviewer->id,
                'treasury_reviewer_department_id' => $resolved['department']->id,
                'treasury_reviewed_at' => now(),
                'manager_department_id' => $resolved['root_department']->id,
                'manager_employee_id' => $resolved['manager_employee']->id,
                'manager_user_id' => $resolved['manager_user']->id,
                'last_error' => null,
            ]);

            static::recordLog(
                request: $locked,
                action: 'treasury_approved',
                actor: $reviewer,
                actorRole: 'treasury_reviewer',
                fromStatus: FinancialApprovalStatus::PENDING_TREASURY->value,
                toStatus: FinancialApprovalStatus::PENDING_MANAGER->value,
                reason: $notes,
                context: [
                    'reviewer_department_id' => $resolved['department']->id,
                    'manager_department_id' => $resolved['root_department']->id,
                    'manager_employee_id' => $resolved['manager_employee']->id,
                    'manager_user_id' => $resolved['manager_user']->id,
                    'path_ids' => $resolved['path_ids'],
                ],
            );

            return $locked->refresh()->load(['companies', 'items', 'managerUser']);
        });

        if ($updated->managerUser) {
            FinancialApprovalNotificationService::dispatch(
                collect([$updated->managerUser]),
                $updated,
                'FinancialApprovalManagerPending',
                'financial.notifications.approval_manager_pending_title',
                'financial.notifications.approval_manager_pending_body',
                'manager',
                'info',
            );
        }

        return $updated;
    }

    /**
     * Final Layer 1 manager approval: executes all payroll disbursements (cash & bank) in one all-or-nothing transaction.
     */
    public static function approveByManager(
        FinancialApprovalRequest $request,
        User $manager,
        ?string $notes = null,
    ): FinancialApprovalRequest {
        $alreadyExecuted = false;

        try {
            $executed = static::executeManagerApprovalInTransaction($request, $manager, $notes, $alreadyExecuted);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            static::recordExecutionFailure($request, $manager, $e->getMessage());
            throw $e;
        }

        if (! $alreadyExecuted) {
            $recipients = collect([$executed->submitter, $executed->treasuryReviewer])
                ->filter(fn ($u) => $u instanceof User && DepartmentManagerResolverService::isActiveStatus($u->status))
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                FinancialApprovalNotificationService::dispatch(
                    $recipients,
                    $executed,
                    'FinancialApprovalExecuted',
                    'financial.notifications.approval_executed_title',
                    'financial.notifications.approval_executed_body',
                    'executed',
                    'success',
                );
            }
        }

        return $executed;
    }

    protected static function executeManagerApprovalInTransaction(
        FinancialApprovalRequest $request,
        User $manager,
        ?string $notes,
        bool &$alreadyExecuted = false,
    ): FinancialApprovalRequest {
        return DB::transaction(function () use ($request, $manager, $notes, &$alreadyExecuted): FinancialApprovalRequest {
            $locked = FinancialApprovalRequest::query()
                ->with(['companies:id'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($locked->status === FinancialApprovalStatus::EXECUTED) {
                if (! PayrollApprovalScopeService::isAssignedManager($manager, $locked)
                    && ! PayrollApprovalScopeService::canViewRequest($manager, $locked)) {
                    throw new AuthorizationException(__('financial.errors.unauthorized_manager_approval'));
                }

                $alreadyExecuted = true;

                return $locked->load(['companies', 'items', 'submitter', 'treasuryReviewer']);
            }

            if ($locked->status !== FinancialApprovalStatus::PENDING_MANAGER) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.request_not_pending_manager'),
                ]);
            }

            if (! PayrollApprovalScopeService::isAssignedManager($manager, $locked)) {
                throw new AuthorizationException(__('financial.errors.unauthorized_manager_approval'));
            }

            $companyIds = $locked->companies->modelKeys();
            sort($companyIds);
            if ($companyIds !== []) {
                DB::table('companies')->whereIn('id', $companyIds)->orderBy('id')->lockForUpdate()->get();
            }

            static::$executingRequestId = (int) $locked->id;
            try {
                $executedCount = static::executeApprovedItems($locked, $manager);
            } finally {
                static::$executingRequestId = null;
            }

            $locked->update([
                'status' => FinancialApprovalStatus::EXECUTED,
                'manager_decided_at' => now(),
                'executed_at' => now(),
                'last_error' => null,
            ]);

            static::recordLog(
                request: $locked,
                action: 'manager_approved',
                actor: $manager,
                actorRole: 'department_manager',
                fromStatus: FinancialApprovalStatus::PENDING_MANAGER->value,
                toStatus: FinancialApprovalStatus::EXECUTED->value,
                reason: $notes,
                context: [
                    'employees_count' => $executedCount,
                    'total_amount' => (float) $locked->total_amount,
                ],
            );

            return $locked->refresh()->load(['companies', 'items', 'submitter', 'treasuryReviewer']);
        });
    }

    protected static function executeApprovedItems(FinancialApprovalRequest $locked, User $manager): int
    {
        $items = FinancialApprovalRequestItem::query()
            ->where('request_id', $locked->id)
            ->where('version', $locked->version)
            ->orderBy('payroll_record_id')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_items_required'),
            ]);
        }

        foreach ($items as $item) {
            $disbursement = PayrollDisbursementService::disburseFromApprovedRequest($item, $locked, $manager);

            $item->update([
                'status' => FinancialApprovalItemStatus::EXECUTED,
                'payroll_payment_id' => $disbursement['payment']?->id,
                'error_message' => null,
            ]);
        }

        return $items->count();
    }

    protected static function recordExecutionFailure(FinancialApprovalRequest $request, User $manager, string $message): void
    {
        FinancialApprovalRequest::query()
            ->whereKey($request->id)
            ->where('status', FinancialApprovalStatus::PENDING_MANAGER->value)
            ->update(['last_error' => $message]);

        static::recordLog(
            request: $request,
            action: 'execution_failed',
            actor: $manager,
            actorRole: 'department_manager',
            fromStatus: FinancialApprovalStatus::PENDING_MANAGER->value,
            toStatus: FinancialApprovalStatus::PENDING_MANAGER->value,
            reason: $message,
        );
    }

    /**
     * Reroute an active pending request back to eligible treasury reviewers when the previous route became invalid.
     */
    public static function rerouteRequest(
        FinancialApprovalRequest $request,
        User $actor,
        string $reason,
    ): FinancialApprovalRequest {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.decision_reason_required'),
            ]);
        }

        $outcome = DB::transaction(function () use ($request, $actor, $reason): array {
            $locked = FinancialApprovalRequest::query()
                ->with(['companies:id'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            if (! PayrollApprovalScopeService::canRerouteRequest($actor, $locked)) {
                throw new AuthorizationException(__('financial.errors.unauthorized_reroute_request'));
            }

            $oldItems = FinancialApprovalRequestItem::query()
                ->where('request_id', $locked->id)
                ->where('version', $locked->version)
                ->where('status', FinancialApprovalItemStatus::PENDING->value)
                ->orderBy('payroll_record_id')
                ->lockForUpdate()
                ->get();

            if ($oldItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('financial.errors.approval_items_required'),
                ]);
            }

            $recordIds = $oldItems->pluck('payroll_record_id')->map(fn ($id) => (int) $id)->all();
            $records = PayrollRecord::query()
                ->with(['employee'])
                ->whereIn('id', $recordIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($oldItems as $item) {
                $record = $records->get((int) $item->payroll_record_id);
                if (! $record) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.payroll_record_not_found'),
                    ]);
                }

                PayrollApprovalSnapshotService::verifyItemMatchesSourceRecord($item, $record);
            }

            $companyIds = $locked->companies->modelKeys();
            $eligibleReviewers = PayrollApprovalScopeService::findEligibleReviewers($companyIds);
            if ($eligibleReviewers->isEmpty()) {
                $companyNames = Company::query()->whereIn('id', $companyIds)->pluck('trade_name')->implode('، ');
                throw ValidationException::withMessages([
                    'companies' => __('financial.errors.no_eligible_treasury_reviewer', [
                        'companies' => $companyNames ?: implode(', ', $companyIds),
                    ]),
                ]);
            }

            $fromStatus = $locked->status->value;
            $oldRoute = [
                'version' => (int) $locked->version,
                'treasury_reviewer_id' => $locked->treasury_reviewer_id,
                'treasury_reviewer_department_id' => $locked->treasury_reviewer_department_id,
                'manager_department_id' => $locked->manager_department_id,
                'manager_employee_id' => $locked->manager_employee_id,
                'manager_user_id' => $locked->manager_user_id,
            ];

            $nextVersion = ((int) $locked->version) + 1;

            FinancialApprovalRequestItem::query()
                ->where('request_id', $locked->id)
                ->where('version', $locked->version)
                ->where('status', FinancialApprovalItemStatus::PENDING->value)
                ->update(['status' => FinancialApprovalItemStatus::CANCELLED->value]);

            foreach ($oldItems as $oldItem) {
                FinancialApprovalRequestItem::create([
                    'request_id' => $locked->id,
                    'version' => $nextVersion,
                    'payroll_record_id' => $oldItem->payroll_record_id,
                    'employee_id' => $oldItem->employee_id,
                    'company_id' => $oldItem->company_id,
                    'branch_id' => $oldItem->branch_id,
                    'payroll_month' => $oldItem->payroll_month,
                    'employee_name_snapshot' => $oldItem->employee_name_snapshot,
                    'employee_number_snapshot' => $oldItem->employee_number_snapshot,
                    'net_amount' => $oldItem->net_amount,
                    'previously_paid_amount' => $oldItem->previously_paid_amount,
                    'requested_amount' => $oldItem->requested_amount,
                    'payment_method' => $oldItem->payment_method,
                    'destination_reference' => $oldItem->destination_reference,
                    'reference_number' => $oldItem->reference_number,
                    'proof_path' => $oldItem->proof_path,
                    'reason_description' => $oldItem->reason_description,
                    'data_fingerprint' => $oldItem->data_fingerprint,
                    'idempotency_key' => sprintf('far:%d:v%d:rec:%d', $locked->id, $nextVersion, $oldItem->payroll_record_id),
                    'status' => FinancialApprovalItemStatus::PENDING,
                    'snapshot' => $oldItem->snapshot,
                ]);
            }

            $locked->update([
                'version' => $nextVersion,
                'status' => FinancialApprovalStatus::PENDING_TREASURY,
                'treasury_reviewer_id' => null,
                'treasury_reviewer_department_id' => null,
                'treasury_reviewed_at' => null,
                'manager_department_id' => null,
                'manager_employee_id' => null,
                'manager_user_id' => null,
                'manager_decided_at' => null,
                'last_error' => null,
            ]);

            static::recordLog(
                request: $locked,
                action: 'rerouted',
                actor: $actor,
                actorRole: $actor->isSuperAdmin() ? 'super_admin' : 'submitter',
                fromStatus: $fromStatus,
                toStatus: FinancialApprovalStatus::PENDING_TREASURY->value,
                reason: $reason,
                context: [
                    'old_route' => $oldRoute,
                    'new_version' => $nextVersion,
                    'eligible_reviewer_ids' => $eligibleReviewers->pluck('id')->all(),
                ],
            );

            return [
                'request' => $locked->refresh()->load(['companies', 'items']),
                'reviewers' => $eligibleReviewers,
            ];
        });

        FinancialApprovalNotificationService::dispatch(
            $outcome['reviewers'],
            $outcome['request'],
            'FinancialApprovalRerouted',
            'financial.notifications.approval_submitted_title',
            'financial.notifications.approval_submitted_body',
            'treasury',
            'warning',
        );

        return $outcome['request'];
    }

    public static function rejectRequest(FinancialApprovalRequest $request, User $actor, string $reason): FinancialApprovalRequest
    {
        return static::transitionNegative($request, $actor, $reason, FinancialApprovalStatus::REJECTED, 'rejected');
    }

    public static function returnRequest(FinancialApprovalRequest $request, User $actor, string $reason): FinancialApprovalRequest
    {
        return static::transitionNegative($request, $actor, $reason, FinancialApprovalStatus::RETURNED, 'returned');
    }

    protected static function transitionNegative(
        FinancialApprovalRequest $request,
        User $actor,
        string $reason,
        FinancialApprovalStatus $targetStatus,
        string $action,
    ): FinancialApprovalRequest {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.decision_reason_required'),
            ]);
        }

        $updated = DB::transaction(function () use ($request, $actor, $reason, $targetStatus, $action): FinancialApprovalRequest {
            $locked = FinancialApprovalRequest::query()
                ->with(['companies:id'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $fromStatus = $locked->status;
            $actorRole = static::authorizeNegativeTransition($locked, $actor);

            $locked->update([
                'status' => $targetStatus,
                'last_error' => null,
            ]);

            FinancialApprovalRequestItem::query()
                ->where('request_id', $locked->id)
                ->where('status', FinancialApprovalItemStatus::PENDING->value)
                ->update(['status' => FinancialApprovalItemStatus::CANCELLED->value]);

            static::recordLog(
                request: $locked,
                action: $action,
                actor: $actor,
                actorRole: $actorRole,
                fromStatus: $fromStatus->value,
                toStatus: $targetStatus->value,
                reason: $reason,
            );

            return $locked->refresh()->load(['submitter', 'treasuryReviewer']);
        });

        $recipients = collect([$updated->submitter, $updated->treasuryReviewer])
            ->filter(fn ($u) => $u instanceof User && (int) $u->id !== (int) $actor->id && DepartmentManagerResolverService::isActiveStatus($u->status))
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            $titleKey = $targetStatus === FinancialApprovalStatus::REJECTED
                ? 'financial.notifications.approval_rejected_title'
                : 'financial.notifications.approval_returned_title';
            $bodyKey = $targetStatus === FinancialApprovalStatus::REJECTED
                ? 'financial.notifications.approval_rejected_body'
                : 'financial.notifications.approval_returned_body';

            FinancialApprovalNotificationService::dispatch(
                $recipients,
                $updated,
                'FinancialApproval'.ucfirst($action),
                $titleKey,
                $bodyKey,
                $action,
                $targetStatus === FinancialApprovalStatus::REJECTED ? 'danger' : 'warning',
                ['reason' => $reason],
            );
        }

        return $updated;
    }

    protected static function authorizeNegativeTransition(FinancialApprovalRequest $locked, User $actor): string
    {
        if ($locked->status === FinancialApprovalStatus::PENDING_TREASURY) {
            if (! PayrollApprovalScopeService::coversAllCompanies($actor, $locked->companies->modelKeys())) {
                throw new AuthorizationException(__('financial.errors.unauthorized_treasury_review'));
            }

            return 'treasury_reviewer';
        }

        if ($locked->status === FinancialApprovalStatus::PENDING_MANAGER) {
            if (! PayrollApprovalScopeService::isAssignedManager($actor, $locked)) {
                throw new AuthorizationException(__('financial.errors.unauthorized_manager_approval'));
            }

            return 'department_manager';
        }

        throw ValidationException::withMessages([
            'status' => __('financial.errors.request_cannot_be_decided'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected static function recordLog(
        FinancialApprovalRequest $request,
        string $action,
        ?User $actor,
        string $actorRole,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason = null,
        array $context = [],
    ): void {
        FinancialApprovalRequestLog::create([
            'request_id' => $request->id,
            'version' => $request->version,
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_role' => $actorRole,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'context' => $context ?: null,
            'created_at' => now(),
        ]);
    }
}
