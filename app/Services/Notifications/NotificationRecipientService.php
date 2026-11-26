<?php

namespace App\Services\Notifications;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialReceiptStatus;
use App\Enums\OperationWorkflowStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Enums\UserType;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\FinancialReceipt;
use App\Models\ForeignEmployee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailSyncLog;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\PayrollRecord;
use App\Models\PerformanceEscalation;
use App\Models\SaudiEmployee;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Violation;
use App\Services\CashHandoverService;
use App\Services\CompanyFormService;
use App\Services\Mail\MailAccessService;
use App\Services\Payroll\FinancialApprovalNotificationService;
use App\Services\Payroll\PayrollApprovalScopeService;
use App\Services\PerformanceEscalationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class NotificationRecipientService
{
    /**
     * Permissions that grant a targeted personal recipient (executor, collector,
     * claim owner, creator, escalator) read/operational access to service notifications
     * without requiring full update_service_transaction rights.
     */
    public const PERSONAL_SERVICE_ACCESS_PERMISSIONS = [
        'view_any_service_transaction',
        'update_service_transaction',
        'create_service_transaction',
        'view_operational_dashboard',
        'record_service_receipt',
        'confirm_service_receipt',
        'request_cash_handover',
        'confirm_cash_handover',
        'record_service_disbursement',
        'approve_self_paid_disbursement',
        'pay_employee_reimbursement',
        'escalate_service_transaction',
        'resolve_service_escalation',
        'resolve_deficiency_task',
        'reverse_financial_transaction',
        'disburse_payroll_salary',
        'view_any_invoice',
        'view_any_payroll_record',
    ];

    /**
     * Resolve eligible recipients who are active, authorized to update the record,
     * and scoped within the record's company and branch.
     *
     * @param  Model  $record  The entity related to the notification
     * @param  string|array  $requiredPermissions  Explicit update permission(s)
     * @param  int|null  $companyId  Override company ID if different from record
     * @param  int|null  $branchId  Override branch ID if different from record
     * @param  array<int>  $excludeUserIds  User IDs to exclude (e.g. self-actions)
     * @param  array<string, mixed>  $context  Optional event-specific routing context
     * @return Collection<int, User>
     */
    public static function getQualifiedRecipients(
        Model $record,
        string|array $requiredPermissions = [],
        ?int $companyId = null,
        ?int $branchId = null,
        array $excludeUserIds = [],
        array $context = [],
    ): Collection {
        // 1. Resolve required permissions
        $permissions = (array) $requiredPermissions;
        if (empty($permissions)) {
            $permissions = static::resolveDefaultUpdatePermissions($record);
        }

        // 2. Resolve target company and branch from record
        $targetCompanyId = $companyId ?? ($context['company_id'] ?? static::resolveCompanyId($record));
        $targetBranchId = $branchId ?? ($context['branch_id'] ?? static::resolveBranchId($record));
        $excludedIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($excludeUserIds, (array) ($context['exclude_user_ids'] ?? [])),
        ))));

        // 3. Fetch active users only
        $candidates = User::query()
            ->where('status', 'active')
            ->with(['employee.department', 'owners', 'roles.permissions', 'permissions'])
            ->get();

        $qualified = collect();

        foreach ($candidates as $user) {
            // Exclude specified IDs (e.g., author who performed the action)
            if (in_array((int) $user->id, $excludedIds, true)) {
                OperationsNotificationDiagnostics::write('recipient_evaluated', $record::class, $record->getKey(), [
                    'recipient_id' => $user->id,
                    'eligible' => false,
                    'reason' => 'explicitly_excluded',
                ]);

                continue;
            }

            $reason = null;
            $eligible = static::isUserEligibleToReceive($user, $record, $permissions, $targetCompanyId, $targetBranchId, $reason, $context);
            OperationsNotificationDiagnostics::write('recipient_evaluated', $record::class, $record->getKey(), [
                'recipient_id' => $user->id,
                'eligible' => $eligible,
                'reason' => $reason,
            ]);
            if ($eligible) {
                $qualified->push($user);
            }
        }

        return $qualified->unique('id')->values();
    }

    /**
     * Determine if a user satisfies all update permission and scoping criteria for a record.
     */
    public static function isUserEligibleToReceive(
        User $user,
        Model $record,
        array $permissions,
        ?int $companyId,
        ?int $branchId,
        ?string &$denialReason = null,
        array $context = [],
    ): bool {
        $denialReason = null;
        // User must be active
        if ($user->status !== 'active') {
            $denialReason = 'inactive_account';

            return false;
        }

        $excludedIds = array_map('intval', (array) ($context['exclude_user_ids'] ?? []));
        if (in_array((int) $user->id, $excludedIds, true)) {
            $denialReason = 'explicitly_excluded';

            return false;
        }

        if (($context['routing_mode'] ?? null) === 'task_assignment_targeted') {
            return static::isEligibleForTaskAssignment($user, $record, $context, $denialReason);
        }

        if (($context['routing_mode'] ?? null) === 'financial_approval' && $record instanceof FinancialApprovalRequest) {
            return static::isEligibleForFinancialApproval($user, $record, $context, $denialReason);
        }

        if (($context['routing_mode'] ?? null) === 'operations_targeted') {
            return static::isUserEligibleForOperationsNotification(
                $user,
                $record,
                $context,
                $companyId,
                $branchId,
                $denialReason,
            );
        }

        if ($record instanceof CashHandover) {
            if (! $record->isSubmitted()) {
                $denialReason = 'handover_not_submitted';

                return false;
            }

            try {
                CashHandoverService::authorizeHandover($record, 'confirm_cash_handover', $user);

                return true;
            } catch (AuthorizationException) {
                $denialReason = 'handover_confirmation_not_authorized';

                return false;
            }
        }

        if ($record instanceof PerformanceEscalation) {
            return static::isEligibleForPerformanceEscalation($user, $record, $companyId);
        }

        if ($record instanceof MailMessage) {
            return MailAccessService::canViewMessage($user, $record);
        }

        if ($record instanceof MailConversation) {
            return MailAccessService::canViewConversation($user, $record);
        }

        if ($record instanceof MailAttachment) {
            return MailAccessService::canViewAttachment($user, $record);
        }

        if ($record instanceof MailAccount || $record instanceof MailSyncLog) {
            return MailAccessService::canAdministerAllMail($user);
        }

        // 1. Super Admin: Global update rights across all companies
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Owner: Must own the specific target company
        if ($user->isOwner()) {
            if ($companyId === null) {
                return true;
            }

            $ownedCompanyIds = array_map('intval', $user->getOwnedCompanyIds());

            $denialReason = in_array((int) $companyId, $ownedCompanyIds, true) ? null : 'company_not_owned';

            return in_array((int) $companyId, $ownedCompanyIds, true);
        }

        $effectivePermissions = ! empty($permissions) ? $permissions : static::resolveDefaultUpdatePermissions($record);
        if ($effectivePermissions === [] || ! $user->hasAnyPermission($effectivePermissions)
            || ! Gate::forUser($user)->allows('update', $record)) {
            $denialReason = 'update_permission_or_policy_denied';

            return false;
        }
        // 4. Company Scope verification
        if ($companyId !== null) {
            $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
            if (! in_array((int) $companyId, $authorizedCompanyIds, true)) {
                $denialReason = 'company_out_of_scope';

                return false;
            }
        }

        // 5. Branch Scope verification
        if ($branchId !== null) {
            if (method_exists($user, 'canAccessAllBranches') && ! $user->canAccessAllBranches() && $user->branch_id) {
                if ((int) $user->branch_id !== (int) $branchId) {
                    $denialReason = 'branch_out_of_scope';

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Assignment alerts are personal to the assignee, company owners and super admins.
     * Recheck the expected assignee when the queued notification is delivered.
     */
    private static function isEligibleForTaskAssignment(
        User $user,
        Model $record,
        array $context,
        ?string &$denialReason,
    ): bool {
        if (! $record instanceof TaskAssignment
            || (int) ($context['expected_employee_id'] ?? 0) !== (int) $record->employee_id) {
            $denialReason = 'assignment_changed';

            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $employee = $record->employee;
        if ($employee && (int) $employee->user_id === (int) $user->id) {
            return true;
        }

        if ($employee?->company_id && $user->isOwner()
            && in_array((int) $employee->company_id, array_map('intval', $user->getOwnedCompanyIds()), true)) {
            return true;
        }

        $denialReason = 'not_in_assignment_audience';

        return false;
    }

    /**
     * Resolve default update permissions by model type.
     */
    public static function resolveDefaultUpdatePermissions(Model $record): array
    {
        return match (true) {
            $record instanceof CashHandover => ['confirm_cash_handover'],
            $record instanceof ForeignEmployee => ['update_foreign_employee'],
            $record instanceof SaudiEmployee => ['update_saudi_employee'],
            $record instanceof Employee => ($record->user?->user_type === UserType::FOREIGN_EMPLOYEE)
                ? ['update_foreign_employee']
                : ['update_saudi_employee'],
            $record instanceof Company => ['update_company'],
            $record instanceof Leave => ['update_leave'],
            $record instanceof Installment => ['update_installment'],
            $record instanceof InstallmentPayment => ['update_installment_payment'],
            $record instanceof Debt => ['update_debt'],
            $record instanceof Violation => ['update_violation'],
            $record instanceof Task => ['update_task'],
            $record instanceof TaskAssignment => ['update_task_assignment'],
            $record instanceof Invoice => ['update_invoice'],
            $record instanceof Ticket => ['update_ticket'],
            $record instanceof ServiceTransaction => ['update_service_transaction'],
            $record instanceof PerformanceEscalation => ['view_any_performance_escalation'],
            $record instanceof MailMessage,
            $record instanceof MailConversation,
            $record instanceof MailAttachment => [MailAccessService::PERM_VIEW_INBOX],
            $record instanceof MailAccount,
            $record instanceof MailSyncLog => [MailAccessService::PERM_MANAGE_ACCOUNTS],
            default => [],
        };
    }

    /**
     * Escalation alerts go to super admins, owners of the case company, and
     * other qualified users holding the escalation view permission.
     */
    private static function isEligibleForPerformanceEscalation(
        User $user,
        PerformanceEscalation $record,
        ?int $companyId
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $companyId ??= (int) $record->company_id;

        if ($user->isOwner()) {
            return in_array($companyId, array_map('intval', $user->getOwnedCompanyIds()), true);
        }

        if (! $user->checkPermissionTo('view_any_performance_escalation')
            || ! PerformanceEscalationService::canViewEscalations($user)) {
            return false;
        }

        return in_array(
            $companyId,
            array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user)),
            true,
        );
    }

    /**
     * Extract company_id from a record across supported relationship patterns.
     */
    public static function resolveCompanyId(Model $record): ?int
    {
        if ($record instanceof Company) {
            return (int) $record->id;
        }

        if (isset($record->company_id) && $record->company_id !== null) {
            return (int) $record->company_id;
        }

        if (method_exists($record, 'employee') && $record->employee) {
            return $record->employee->company_id ? (int) $record->employee->company_id : null;
        }

        if (method_exists($record, 'company') && $record->company) {
            return $record->company->id ? (int) $record->company->id : null;
        }

        if (method_exists($record, 'installment') && $record->installment?->employee) {
            return $record->installment->employee->company_id ? (int) $record->installment->employee->company_id : null;
        }

        return null;
    }

    /**
     * Extract branch_id from a record across supported relationship patterns.
     */
    public static function resolveBranchId(Model $record): ?int
    {
        if (isset($record->branch_id) && $record->branch_id !== null) {
            return (int) $record->branch_id;
        }

        if (method_exists($record, 'employee') && $record->employee && $record->employee->branch_id) {
            return (int) $record->employee->branch_id;
        }

        if (method_exists($record, 'executor') && $record->executor && $record->executor->branch_id) {
            return (int) $record->executor->branch_id;
        }

        return null;
    }

    /**
     * Evaluate recipient eligibility and deduplication for financial approval workflow notifications.
     *
     * @param  array<string, mixed>  $context
     */
    private static function isEligibleForFinancialApproval(
        User $user,
        FinancialApprovalRequest $request,
        array $context,
        ?string &$denialReason = null,
    ): bool {
        $dedupKey = (string) ($context['dedup_key'] ?? '');
        if ($dedupKey !== '' && FinancialApprovalNotificationService::alreadyDelivered((int) $user->id, $dedupKey)) {
            $denialReason = 'duplicate_notification_prevented';

            return false;
        }

        $expectedVersion = (int) ($context['approval_version'] ?? 0);
        if ($expectedVersion > 0 && (int) $request->version !== $expectedVersion) {
            $denialReason = 'approval_version_changed';

            return false;
        }

        $stage = (string) ($context['approval_stage'] ?? '');
        $companyIds = $request->companies()->pluck('companies.id')->all();

        if ($stage === 'treasury') {
            if ($request->status !== FinancialApprovalStatus::PENDING_TREASURY) {
                $denialReason = 'approval_stage_changed';

                return false;
            }

            if (! PayrollApprovalScopeService::coversAllCompanies($user, $companyIds)) {
                $denialReason = 'treasury_reviewer_ineligible';

                return false;
            }

            return true;
        }

        if ($stage === 'manager') {
            if ($request->status !== FinancialApprovalStatus::PENDING_MANAGER) {
                $denialReason = 'approval_stage_changed';

                return false;
            }

            if (! PayrollApprovalScopeService::isAssignedManager($user, $request)) {
                $denialReason = 'manager_ineligible_or_route_changed';

                return false;
            }

            return true;
        }

        if ($stage === 'executed') {
            if ($request->status !== FinancialApprovalStatus::EXECUTED) {
                $denialReason = 'approval_not_executed';

                return false;
            }

            $isTarget = (int) $request->submitted_by === (int) $user->id
                || (int) $request->treasury_reviewer_id === (int) $user->id;

            if (! $isTarget || ! PayrollApprovalScopeService::canViewRequest($user, $request)) {
                $denialReason = 'not_in_event_audience';

                return false;
            }

            return true;
        }

        if (in_array($stage, ['rejected', 'returned'], true)) {
            $expectedStatus = $stage === 'rejected'
                ? FinancialApprovalStatus::REJECTED
                : FinancialApprovalStatus::RETURNED;

            if ($request->status !== $expectedStatus) {
                $denialReason = 'approval_stage_changed';

                return false;
            }

            if (! PayrollApprovalScopeService::canViewRequest($user, $request)) {
                $denialReason = 'not_in_event_audience';

                return false;
            }

            return true;
        }

        $denialReason = 'unknown_approval_stage';

        return false;
    }

    /**
     * Targeted recipient eligibility for service and financial operations (N01-N13).
     * Never broadcasts automatically to managers, owners, or super admins unless
     * they satisfy the event's specific permission or personal relationship criteria.
     *
     * @param  array<string, mixed>  $context
     */
    private static function isUserEligibleForOperationsNotification(
        User $user,
        Model $record,
        array $context,
        ?int $companyId,
        ?int $branchId,
        ?string &$denialReason = null,
    ): bool {
        $subject = null;
        $subjectType = $context['subject_type'] ?? null;
        $subjectId = $context['subject_id'] ?? null;

        if (is_string($subjectType) && $subjectId !== null && is_subclass_of($subjectType, Model::class)) {
            $subject = $subjectType::query()->find($subjectId);
            if ($subject === null) {
                $denialReason = 'subject_missing';

                return false;
            }
        }

        if (! static::validateOperationsEventState($record, $subject, $context, $denialReason)) {
            return false;
        }

        $effectiveCompanyId = $companyId ?? ($context['company_id'] ?? static::resolveCompanyId($record));
        $effectiveBranchId = $branchId ?? ($context['branch_id'] ?? static::resolveBranchId($record));

        if (! $user->isSuperAdmin()) {
            $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
            if ($authorizedCompanyIds === []
                || ($effectiveCompanyId !== null && ! in_array((int) $effectiveCompanyId, $authorizedCompanyIds, true))) {
                $denialReason = 'company_out_of_scope';

                return false;
            }

            if ($effectiveBranchId !== null
                && method_exists($user, 'canAccessAllBranches')
                && ! $user->canAccessAllBranches()
                && $user->branch_id
                && (int) $user->branch_id !== (int) $effectiveBranchId) {
                $denialReason = 'branch_out_of_scope';

                return false;
            }
        }

        $targetUserIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) ($context['target_user_ids'] ?? []),
        ))));

        $isTargetUser = in_array((int) $user->id, $targetUserIds, true);

        if ($isTargetUser) {
            if (! static::validatePersonalRelationship($user, $record, $subject, $context, $denialReason)) {
                return false;
            }

            if ($user->isSuperAdmin()
                || $user->isOwner()
                || $user->hasAnyPermission(self::PERSONAL_SERVICE_ACCESS_PERMISSIONS)) {
                return true;
            }

            $denialReason = 'personal_access_denied';

            return false;
        }

        if (($context['include_higher_management'] ?? false) === true
            && ($user->isSuperAdmin() || $user->isOwner())) {
            return true;
        }

        $requiredPermissions = array_values(array_filter((array) ($context['required_permission'] ?? [])));

        if ($requiredPermissions !== []) {
            if (! $user->hasAnyPermission($requiredPermissions)) {
                $denialReason = 'required_permission_denied';

                return false;
            }

            if (in_array('approve_self_paid_disbursement', $requiredPermissions, true)) {
                if ($subject instanceof FinancialDisbursement) {
                    $isClaimRequester = (int) $subject->created_by === (int) $user->id
                        || ($subject->paid_by_employee_id && (int) $subject->paid_by_employee_id === (int) ($user->employee?->id ?? 0));
                    if ($isClaimRequester && ! $user->isSuperAdmin()) {
                        $denialReason = 'cannot_self_approve';

                        return false;
                    }
                } elseif ($record instanceof ServiceTransaction) {
                    $requesterId = (int) ($record->metadata['approval_requested_by'] ?? 0);
                    if ($requesterId > 0 && $requesterId === (int) $user->id && ! $user->isSuperAdmin()) {
                        $denialReason = 'cannot_self_approve';

                        return false;
                    }
                }
            }

            return true;
        }

        $denialReason = 'not_in_event_audience';

        return false;
    }

    /**
     * Ensure action-request notifications are cancelled if their pending state
     * was already resolved before worker delivery.
     *
     * @param  array<string, mixed>  $context
     */
    private static function validateOperationsEventState(
        Model $record,
        ?Model $subject,
        array $context,
        ?string &$denialReason = null,
    ): bool {
        $eventKey = (string) ($context['event_key'] ?? '');

        if ($eventKey === 'ServiceTransactionAssigned' && $record instanceof ServiceTransaction) {
            $expectedExecutor = (int) ($context['expected_executor_employee_id'] ?? 0);
            if ($expectedExecutor > 0 && (int) ($record->executed_by ?? 0) !== $expectedExecutor) {
                $denialReason = 'executor_changed';

                return false;
            }
        }

        if ($eventKey === 'ServiceTransactionOverdue' && $record instanceof ServiceTransaction) {
            if ($record->service_status !== 'active'
                || $record->workflow_status === OperationWorkflowStatus::COMPLETED->value
                || $record->getRawOriginal('status') === 'cancelled'
                || ($record->metadata['status'] ?? null) === 'cancelled') {
                $denialReason = 'transaction_no_longer_active';

                return false;
            }

            if (empty($record->due_date)
                || ! Carbon::parse($record->due_date)->startOfDay()->lt(Carbon::today())) {
                $denialReason = 'due_date_no_longer_overdue';

                return false;
            }

            $expectedExecutor = (int) ($context['expected_executor_employee_id'] ?? 0);
            if ($expectedExecutor > 0 && (int) ($record->executed_by ?? 0) !== $expectedExecutor) {
                $denialReason = 'executor_changed';

                return false;
            }
        }

        if (in_array($eventKey, [
            'ServiceFinancialReceiptRecorded',
            'InvoiceFinancialReceiptRecorded',
            'PayrollCollectionReceiptRecorded',
        ], true)) {
            if (! $subject instanceof FinancialReceipt || $subject->status !== FinancialReceiptStatus::PENDING_CONFIRMATION) {
                $denialReason = 'receipt_no_longer_pending';

                return false;
            }
        }

        if ($eventKey === 'ServiceFinancialReceiptConfirmed') {
            if (! $subject instanceof FinancialReceipt
                || $subject->status !== FinancialReceiptStatus::CONFIRMED) {
                $denialReason = 'receipt_no_longer_confirmed';

                return false;
            }
        }

        if ($eventKey === 'PayrollDisbursementRequested' && $record instanceof PayrollRecord) {
            $reqKey = (string) ($context['transfer_request_key'] ?? '');
            if ($reqKey === '' || empty($record->metadata['pending_bank_transfers'][$reqKey])) {
                $denialReason = 'transfer_no_longer_pending';

                return false;
            }
        }

        if ($eventKey === 'ServiceFinancialHandoverRequested') {
            $handover = $record instanceof CashHandover ? $record : ($subject instanceof CashHandover ? $subject : null);
            if (! $handover || ! $handover->isSubmitted()) {
                $denialReason = 'handover_not_submitted';

                return false;
            }
        }

        if ($eventKey === 'ServiceFinancialDisbursementRecorded') {
            if ($subject instanceof FinancialDisbursement) {
                if ($subject->disbursement_type !== FinancialDisbursementType::EMPLOYEE_PERSONAL
                    || $subject->status !== FinancialDisbursementStatus::PENDING_APPROVAL
                    || $subject->isReversed()) {
                    $denialReason = 'claim_no_longer_pending';

                    return false;
                }
            } elseif ($record instanceof ServiceTransaction) {
                if (($record->metadata['self_paid_status'] ?? '') !== 'pending_approval') {
                    $denialReason = 'claim_no_longer_pending';

                    return false;
                }
            }
        }

        if ($eventKey === 'ServiceFinancialClaimApproved') {
            if ($subject instanceof FinancialDisbursement) {
                if (! in_array($subject->status, [
                    FinancialDisbursementStatus::APPROVED,
                    FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                ], true) || $subject->remainingDue() <= 0.009 || $subject->isReversed()) {
                    $denialReason = 'reimbursement_no_longer_due';

                    return false;
                }
            } elseif ($record instanceof ServiceTransaction) {
                if (($record->metadata['self_paid_status'] ?? '') !== 'approved') {
                    $denialReason = 'reimbursement_no_longer_due';

                    return false;
                }
            }
        }

        if ($eventKey === 'ServiceTransactionEscalated' && $record instanceof ServiceTransaction) {
            $isEscalated = (bool) ($record->metadata['is_escalated'] ?? false)
                || $record->workflow_status === OperationWorkflowStatus::ESCALATED->value;
            if (! $isEscalated) {
                $denialReason = 'escalation_no_longer_open';

                return false;
            }
        }

        if ($eventKey === 'ServiceTransactionDeficiencyTasks' && $record instanceof ServiceTransaction) {
            $expectedExecutor = (int) ($context['expected_executor_employee_id'] ?? 0);
            $currentResponsible = (int) ($record->executed_by ?? $record->employee_id ?? 0);
            if ($expectedExecutor > 0 && $currentResponsible !== $expectedExecutor) {
                $denialReason = 'executor_changed';

                return false;
            }
        }

        return true;
    }

    /**
     * Re-verify personal recipient relationship to the document at delivery time.
     *
     * @param  array<string, mixed>  $context
     */
    private static function validatePersonalRelationship(
        User $user,
        Model $record,
        ?Model $subject,
        array $context,
        ?string &$denialReason = null,
    ): bool {
        $eventKey = (string) ($context['event_key'] ?? '');
        $userEmployeeId = (int) ($user->employee?->id ?? 0);

        if (in_array($eventKey, ['ServiceTransactionAssigned', 'ServiceTransactionOverdue'], true)
            && $record instanceof ServiceTransaction) {
            $expectedExecutor = (int) ($context['expected_executor_employee_id'] ?? $record->executed_by ?? 0);
            if ($expectedExecutor <= 0 || $userEmployeeId !== $expectedExecutor || (int) $record->executed_by !== $expectedExecutor) {
                $denialReason = 'executor_changed';

                return false;
            }
        }

        if ($eventKey === 'ServiceTransactionDeficiencyTasks' && $record instanceof ServiceTransaction) {
            $currentResponsible = (int) ($record->executed_by ?? $record->employee_id ?? 0);
            if ($currentResponsible <= 0 || $userEmployeeId !== $currentResponsible) {
                $denialReason = 'executor_changed';

                return false;
            }
        }

        if (in_array($eventKey, ['ServiceFinancialHandoverConfirmed', 'ServiceFinancialHandoverRejected'], true)) {
            $handover = $subject instanceof CashHandover ? $subject : ($record instanceof CashHandover ? $record : null);
            if ($handover) {
                $allowedEmployeeIds = array_filter([
                    (int) ($handover->collector_employee_id ?? 0),
                    (int) ($handover->delivered_by_employee_id ?? 0),
                ]);
                if ($userEmployeeId <= 0 || ! in_array($userEmployeeId, $allowedEmployeeIds, true)) {
                    $denialReason = 'handover_collector_mismatch';

                    return false;
                }
            }
        }

        if ($eventKey === 'ServiceFinancialReimbursementPaid' && $subject instanceof FinancialDisbursementPayment) {
            $claimOwnerEmployeeId = (int) ($subject->disbursement?->paid_by_employee_id ?? 0);
            if ($claimOwnerEmployeeId <= 0 || $userEmployeeId !== $claimOwnerEmployeeId) {
                $denialReason = 'claim_owner_mismatch';

                return false;
            }
        }

        return true;
    }
}
