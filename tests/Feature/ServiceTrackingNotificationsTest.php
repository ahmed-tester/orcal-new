<?php

namespace Tests\Feature;

use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialPaymentMethod;
use App\Enums\OperationWorkflowStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\CashHandoverService;
use App\Services\FinancialDisbursementService;
use App\Services\FinancialReceiptService;
use App\Services\OperationsNotificationService;
use App\Services\OperationsReconciliationService;
use App\Services\OperationsSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceTrackingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Company $otherCompany;

    private Branch $branch1;

    private Branch $branch2;

    private Service $service;

    private User $superAdmin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::create(['trade_name' => 'Alpha Co']);
        $this->otherCompany = Company::create(['trade_name' => 'Beta Co']);
        $this->branch1 = Branch::create(['company_id' => $this->company->id, 'name' => 'B1', 'branch_code' => 'B1']);
        $this->branch2 = Branch::create(['company_id' => $this->company->id, 'name' => 'B2', 'branch_code' => 'B2']);

        $this->service = Service::create([
            'company_id' => $this->company->id,
            'name' => 'Visa Renewal',
            'price' => 500,
            'default_gov_fee' => 300,
        ]);

        foreach ([
            'view_any_service_transaction',
            'update_service_transaction',
            'record_service_receipt',
            'confirm_service_receipt',
            'approve_service_transaction',
            'request_cash_handover',
            'confirm_cash_handover',
            'record_service_disbursement',
            'approve_self_paid_disbursement',
            'pay_employee_reimbursement',
            'reverse_financial_transaction',
            'escalate_service_transaction',
            'resolve_service_escalation',
            'view_any_branch',
            'view_any_treasury_transaction',
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'sa@test.local',
            'password' => bcrypt('password'),
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Company Owner',
            'email' => 'owner@test.local',
            'password' => bcrypt('password'),
            'user_type' => 'owner',
            'status' => 'active',
        ]);
        Owner::create(['user_id' => $this->owner->id, 'company_id' => $this->company->id]);
    }

    private function makeStaff(
        string $name,
        array $permissions = [],
        ?int $companyId = null,
        ?int $branchId = null,
        string $status = 'active',
        ?int $departmentId = null,
    ): array {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@test.local',
            'password' => bcrypt('password'),
            'user_type' => 'saudi_employee',
            'status' => $status,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $companyId ?? $this->company->id,
            'branch_id' => $branchId,
            'department_id' => $departmentId,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [$user->fresh(), $employee->fresh()];
    }

    private function makeTx(User $actor, Employee $executor, array $overrides = []): ServiceTransaction
    {
        $this->actingAs($actor);

        return ServiceTransaction::create(array_merge([
            'scope_type' => 'internal',
            'service_target' => 'company',
            'company_id' => $this->company->id,
            'service_id' => $this->service->id,
            'total_amount' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'payment_status' => 'unpaid',
            'service_status' => 'active',
            'executed_by' => $executor->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'transaction_date' => now()->toDateString(),
        ], $overrides));
    }

    /** @return array<int, int> */
    private function queuedUserIdsFor(string $eventKey): array
    {
        return Queue::pushed(SendQueuedNotifications::class)
            ->filter(fn ($job) => $job->notification instanceof BaseSystemNotification && $job->notification->eventKey === $eventKey)
            ->flatMap(fn ($job) => $job->notifiables->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function processQueuedJobs(?string $onlyEventKey = null): void
    {
        foreach (Queue::pushed(SendQueuedNotifications::class) as $job) {
            if ($onlyEventKey !== null && $job->notification->eventKey !== $onlyEventKey) {
                continue;
            }
            unserialize(serialize($job))->handle(app(ChannelManager::class));
        }
    }

    public function test_n01_and_n09_assignment_and_completion_only_notify_intended_recipients(): void
    {
        [$creator, $creatorEmp] = $this->makeStaff('Creator', ['view_any_service_transaction', 'update_service_transaction'], $this->company->id, $this->branch1->id);
        [$exec1, $exec1Emp] = $this->makeStaff('Executor One', ['view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$exec2, $exec2Emp] = $this->makeStaff('Executor Two', ['view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$bystander] = $this->makeStaff('Bystander Manager', ['update_service_transaction', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);

        Queue::fake();

        $tx = $this->makeTx($creator, $exec1Emp);

        // N01: only exec1 receives assignment, not creator, bystander, owner, or superAdmin
        $this->assertSame([$exec1->id], $this->queuedUserIdsFor('ServiceTransactionAssigned'));
        $this->processQueuedJobs('ServiceTransactionAssigned');
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $exec1->id)->count());

        // Safe actionUrl: exec1 has view_any_service_transaction without update_service_transaction -> view url
        $payload = json_decode(DB::table('notifications')->where('notifiable_id', $exec1->id)->sole()->data, true);
        $this->assertSame('persistent', $payload['duration']);
        $this->assertStringNotContainsString('/edit', (string) $payload['action_url']);

        // Re-saving without changing executor does not re-send N01
        Queue::fake();
        $tx->update(['notes' => 'No executor change']);
        $this->assertSame([], $this->queuedUserIdsFor('ServiceTransactionAssigned'));

        // Reassign to exec2: only exec2 is notified, and stale queued notification for exec1 is denied if executor changed before worker
        $tx->update(['executed_by' => $exec2Emp->id]);
        $this->assertSame([$exec2->id], $this->queuedUserIdsFor('ServiceTransactionAssigned'));

        // N09: complete transaction -> notifies creator only (since creator != exec2)
        Queue::fake();
        $this->actingAs($exec2);
        $tx->total_amount = 0;
        $tx->paid_amount = 0;
        $tx->remaining_amount = 0;
        $tx->metadata = array_merge($tx->metadata ?? [], [
            'client_due_amount' => 0,
            'expected_fee_amount' => 0,
        ]);
        $tx->saveQuietly();
        OperationsReconciliationService::reconcile($tx);

        $this->assertSame([$creator->id], $this->queuedUserIdsFor('ServiceTransactionCompleted'));
        $this->assertNotContains($this->owner->id, $this->queuedUserIdsFor('ServiceTransactionCompleted'));
        $this->assertNotContains($this->superAdmin->id, $this->queuedUserIdsFor('ServiceTransactionCompleted'));
        $this->assertNotContains($bystander->id, $this->queuedUserIdsFor('ServiceTransactionCompleted'));
    }

    public function test_n02_overdue_command_notifies_executor_once_and_skips_ineligible(): void
    {
        [$creator] = $this->makeStaff('Creator', ['update_service_transaction'], $this->company->id);
        [$exec, $execEmp] = $this->makeStaff('Exec', ['view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$inactiveExec, $inactiveEmp] = $this->makeStaff('Inactive', ['view_any_service_transaction'], $this->company->id, $this->branch1->id, 'inactive');

        $overdueTx = $this->makeTx($creator, $execEmp, ['due_date' => now()->subDays(2)->toDateString()]);
        $dueTodayTx = $this->makeTx($creator, $execEmp, ['due_date' => now()->toDateString()]);
        $completedTx = $this->makeTx($creator, $execEmp, [
            'due_date' => now()->subDays(4)->toDateString(),
            'service_status' => 'completed',
            'workflow_status' => OperationWorkflowStatus::COMPLETED->value,
        ]);
        $inactiveExecTx = $this->makeTx($creator, $inactiveEmp, ['due_date' => now()->subDays(2)->toDateString()]);

        Artisan::call('service-transactions:check-overdue', ['--since' => now()->subDays(10)->toDateString()]);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $exec->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $inactiveExec->id)->count());
        $this->assertNotEmpty($overdueTx->fresh()->metadata['overdue_notified_at'] ?? null);
        $this->assertNull($dueTodayTx->fresh()->metadata['overdue_notified_at'] ?? null);
        $this->assertNull($completedTx->fresh()->metadata['overdue_notified_at'] ?? null);
        $this->assertNull($inactiveExecTx->fresh()->metadata['overdue_notified_at'] ?? null);
        $this->assertSame('no_eligible_recipient', $inactiveExecTx->fresh()->metadata['overdue_last_skip_reason'] ?? null);

        // Re-running does not send duplicate for the same due_date
        Artisan::call('service-transactions:check-overdue', ['--since' => now()->subDays(10)->toDateString()]);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $exec->id)->count());
    }

    public function test_n03_to_n08_and_n10_financial_lifecycle_and_reversals_target_exact_recipients(): void
    {
        [$collector, $collectorEmp] = $this->makeStaff(
            'Collector',
            ['record_service_receipt', 'request_cash_handover', 'record_service_disbursement', 'view_any_service_transaction'],
            $this->company->id,
            $this->branch1->id,
        );
        [$receiptConfirmer] = $this->makeStaff('Receipt Confirmer', ['confirm_service_receipt', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$treasuryOfficer] = $this->makeStaff('Treasury Officer', ['confirm_cash_handover', 'view_any_treasury_transaction'], $this->company->id, $this->branch1->id);
        [$claimApprover] = $this->makeStaff('Claim Approver', ['approve_self_paid_disbursement', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$reimbPayer] = $this->makeStaff('Reimb Payer', ['pay_employee_reimbursement', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$auditor] = $this->makeStaff('Auditor', ['reverse_financial_transaction', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$branch2Receiver] = $this->makeStaff('Branch 2 Receiver', ['confirm_cash_handover'], $this->company->id, $this->branch2->id);
        [$otherCoApprover] = $this->makeStaff('Other Co Approver', ['approve_self_paid_disbursement'], $this->otherCompany->id);

        $tx = $this->makeTx($collector, $collectorEmp);

        // N03: Pending non-cash receipt -> only receiptConfirmer (branch1, company1)
        Queue::fake();
        $transfer = FinancialReceiptService::record($tx, [
            'amount' => 200,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-n03',
        ], $collector);
        $this->assertSame([$receiptConfirmer->id], $this->queuedUserIdsFor('ServiceFinancialReceiptRecorded'));

        // Confirming before queue runs cancels stale N03 delivery
        $staleN03 = Queue::pushed(SendQueuedNotifications::class)->firstWhere(
            fn ($job) => $job->notification->eventKey === 'ServiceFinancialReceiptRecorded'
        );
        FinancialReceiptService::confirm($transfer, [
            'destination_type' => 'company_account',
            'destination_reference' => 'ACC-1',
            'reference_number' => 'REF-1',
            'proof_path' => 'proofs/trf.pdf',
        ], $receiptConfirmer);
        unserialize(serialize($staleN03))->handle(app(ChannelManager::class));
        $this->assertSame(0, DB::table('notifications')->count());

        // N04 & N05: Cash receipt -> Custody alert -> Handover Request -> Confirm
        [$opsManager] = $this->makeStaff('Ops Manager', ['approve_service_transaction', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        Queue::fake();
        $cash = FinancialReceiptService::record($tx, [
            'amount' => 300,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-n04',
        ], $collector);
        $custodyUsers = $this->queuedUserIdsFor('ServiceFinancialReceiptConfirmed');
        $this->assertContains($treasuryOfficer->id, $custodyUsers);
        $this->assertContains($opsManager->id, $custodyUsers);
        $this->assertContains($this->owner->id, $custodyUsers);
        $this->assertContains($this->superAdmin->id, $custodyUsers);
        $this->assertNotContains($branch2Receiver->id, $custodyUsers);
        $this->assertNotContains($collector->id, $custodyUsers);

        $handover = CashHandoverService::request([$cash->id => 300], ['request_key' => 'hnd-n04'], $collector);
        $n04Users = $this->queuedUserIdsFor('ServiceFinancialHandoverRequested');
        $this->assertContains($treasuryOfficer->id, $n04Users);
        $this->assertNotContains($branch2Receiver->id, $n04Users);
        $this->assertNotContains($this->owner->id, $n04Users);

        Queue::fake();
        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $collector),
            'confirmation_key' => 'hnd-conf-n05',
            'confirmed_amounts' => [$handover->items()->first()->id => 300],
        ], $treasuryOfficer);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialHandoverConfirmed'));

        // N06, N07, N08: Personal claim -> Approve -> Partial reimbursement
        Queue::fake();
        $claim = FinancialDisbursementService::record($tx, [
            'disbursement_type' => FinancialDisbursementType::EMPLOYEE_PERSONAL,
            'amount' => 300,
            'reference_number' => 'GOV-101',
            'paid_by_employee_id' => $collectorEmp->id,
            'receipt_proof' => 'proofs/gov.pdf',
            'request_key' => 'dsb-n06',
        ], $collector);
        $n06Users = $this->queuedUserIdsFor('ServiceFinancialDisbursementRecorded');
        $this->assertSame([$claimApprover->id], $n06Users);
        $this->assertNotContains($otherCoApprover->id, $n06Users);

        Queue::fake();
        FinancialDisbursementService::approve($claim, $claimApprover);
        $this->assertSame([$reimbPayer->id], $this->queuedUserIdsFor('ServiceFinancialClaimApproved'));

        Queue::fake();
        $payment = FinancialDisbursementService::pay($claim, ['amount' => 150, 'request_key' => 'rmb-n08'], $reimbPayer);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialReimbursementPaid'));

        // N10: Reversal of reimbursement payment notifies auditor + collector (excluding actor)
        Queue::fake();
        FinancialDisbursementService::reversePayment($payment, 'Duplicate payment', $auditor);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialTransactionReversed'));

        Queue::fake();
        FinancialDisbursementService::reversePayment(
            FinancialDisbursementService::pay($claim->fresh(), ['amount' => 100, 'request_key' => 'rmb-n10-2'], $reimbPayer),
            'Wrong amount',
            $this->superAdmin,
        );
        $n10Users = $this->queuedUserIdsFor('ServiceFinancialTransactionReversed');
        $this->assertContains($auditor->id, $n10Users);
        $this->assertContains($collector->id, $n10Users);
        $this->assertNotContains($this->superAdmin->id, $n10Users);
        $this->assertNotContains($this->owner->id, $n10Users);
    }

    public function test_n11_n12_n13_escalation_and_deficiency_routing_and_shared_department_scope(): void
    {
        $sharedDept = Department::create([
            'name' => 'Shared Ops',
            'is_global' => false,
            'status' => 'active',
        ]);
        $sharedDept->companies()->sync([$this->company->id, $this->otherCompany->id]);

        [$exec, $execEmp] = $this->makeStaff('Executor', ['escalate_service_transaction', 'view_any_service_transaction'], $this->company->id, $this->branch1->id);
        [$sharedSupervisor] = $this->makeStaff('Shared Supervisor', ['resolve_service_escalation', 'view_any_branch'], $this->otherCompany->id, null, 'active', $sharedDept->id);
        [$otherSupervisor] = $this->makeStaff('Other Co Supervisor', ['resolve_service_escalation'], $this->otherCompany->id);
        [$plainEditor] = $this->makeStaff('Plain Editor', ['update_service_transaction'], $this->company->id, $this->branch1->id);

        $tx = $this->makeTx($plainEditor, $execEmp);

        // N13: Deficiency tasks created -> only executor
        Queue::fake();
        OperationsReconciliationService::reconcile($tx);
        $this->assertSame([$exec->id], $this->queuedUserIdsFor('ServiceTransactionDeficiencyTasks'));

        // N11: Escalate -> only resolve_service_escalation holders in scope (sharedSupervisor included, plainEditor/otherSupervisor/owner excluded)
        Queue::fake();
        $this->actingAs($exec);
        OperationsSettlementService::escalateToManagement($tx, ['reason' => 'client_unresponsive', 'notes' => 'No answer'], $exec->id);
        $n11Users = $this->queuedUserIdsFor('ServiceTransactionEscalated');
        $this->assertSame([$sharedSupervisor->id], $n11Users);
        $this->assertNotContains($otherSupervisor->id, $n11Users);
        $this->assertNotContains($plainEditor->id, $n11Users);

        // N12: Resolve escalation -> only executor (and escalator if different), excluding resolver
        Queue::fake();
        OperationsSettlementService::resolveEscalation($tx->fresh(), 'Resolved with client', $sharedSupervisor->id);
        $this->assertSame([$exec->id], $this->queuedUserIdsFor('ServiceTransactionEscalationResolved'));
    }

    public function test_legacy_unserialized_base_system_notification_initializes_missing_context(): void
    {
        $className = BaseSystemNotification::class;
        $legacyPayload = 'O:'.strlen($className).':"'.$className.'":1:{s:8:"eventKey";s:26:"ServiceTransactionAssigned";}';

        /** @var BaseSystemNotification $restored */
        $restored = unserialize($legacyPayload);

        $this->assertSame('ServiceTransactionAssigned', $restored->eventKey);
        $this->assertSame([], $restored->context);
        $this->assertSame([], $restored->placeholders);
    }

    public function test_legacy_self_paid_approval_dispatches_claim_approved_without_undefined_variables(): void
    {
        [$creator, $creatorEmp] = $this->makeStaff('Creator', ['record_service_disbursement', 'update_service_transaction'], $this->company->id, $this->branch1->id);
        [$approver] = $this->makeStaff('Approver', ['approve_self_paid_disbursement'], $this->company->id, $this->branch1->id);
        [$payer] = $this->makeStaff('Payer', ['pay_employee_reimbursement'], $this->company->id, $this->branch1->id);

        $tx = $this->makeTx($creator, $creatorEmp);

        Queue::fake();
        $this->actingAs($creator);
        OperationsSettlementService::recordGovernmentDisbursement(
            transaction: $tx,
            amount: 250,
            channel: 'sadad',
            isSelfPaid: true,
        );
        $this->assertSame([$approver->id], $this->queuedUserIdsFor('ServiceFinancialDisbursementRecorded'));

        Queue::fake();
        $this->actingAs($approver);
        $approved = OperationsSettlementService::approveSelfPaidDisbursement($tx->fresh(), 'Approved');

        $this->assertSame('approved', $approved->metadata['self_paid_status'] ?? null);
        $this->assertSame([], $this->queuedUserIdsFor('ServiceFinancialDisbursementRecorded'));
        $this->assertSame([$payer->id], $this->queuedUserIdsFor('ServiceFinancialClaimApproved'));
    }

    public function test_idempotent_replays_do_not_duplicate_notifications_and_extended_due_date_cancels_overdue_delivery(): void
    {
        [$collector, $collectorEmp] = $this->makeStaff(
            'Collector',
            ['record_service_receipt', 'request_cash_handover', 'record_service_disbursement', 'view_any_service_transaction'],
            $this->company->id,
            $this->branch1->id,
        );
        [$treasuryOfficer] = $this->makeStaff('Treasury Officer', ['confirm_cash_handover', 'view_any_treasury_transaction'], $this->company->id, $this->branch1->id);
        [$claimApprover] = $this->makeStaff('Claim Approver', ['approve_self_paid_disbursement'], $this->company->id, $this->branch1->id);
        [$reimbPayer] = $this->makeStaff('Reimb Payer', ['pay_employee_reimbursement'], $this->company->id, $this->branch1->id);

        $tx = $this->makeTx($collector, $collectorEmp, ['due_date' => now()->subDays(2)->toDateString()]);

        // Overdue notification queued while due_date is in the past, then due_date is extended before worker runs
        Queue::fake();
        OperationsNotificationService::notifyOverdue($tx, 2, synchronous: false);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceTransactionOverdue'));

        $tx->update(['due_date' => now()->addDays(5)->toDateString()]);
        $this->processQueuedJobs('ServiceTransactionOverdue');
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $collector->id)->count());

        // Idempotent handover confirm & reject replays (fund treasury before reimbursement pay)
        $cash = FinancialReceiptService::record($tx, [
            'amount' => 150,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-idem-1',
        ], $collector);
        $handover = CashHandoverService::request([$cash->id => 150], ['request_key' => 'hnd-idem-1'], $collector);
        $confirmPayload = [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $collector),
            'confirmation_key' => 'hnd-conf-idem-1',
            'confirmed_amounts' => [$handover->items()->first()->id => 150],
        ];

        Queue::fake();
        CashHandoverService::confirm($handover, $confirmPayload, $treasuryOfficer);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialHandoverConfirmed'));

        Queue::fake();
        CashHandoverService::confirm($handover->fresh(), $confirmPayload, $treasuryOfficer);
        $this->assertSame([], $this->queuedUserIdsFor('ServiceFinancialHandoverConfirmed'));

        // Idempotent reimbursement payment replay
        $claim = FinancialDisbursementService::record($tx, [
            'disbursement_type' => FinancialDisbursementType::EMPLOYEE_PERSONAL,
            'amount' => 200,
            'reference_number' => 'GOV-202',
            'paid_by_employee_id' => $collectorEmp->id,
            'receipt_proof' => 'proofs/gov2.pdf',
            'request_key' => 'dsb-idem-1',
        ], $collector);
        FinancialDisbursementService::approve($claim, $claimApprover);

        Queue::fake();
        FinancialDisbursementService::pay($claim, ['amount' => 100, 'request_key' => 'rmb-idem-1'], $reimbPayer);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialReimbursementPaid'));

        Queue::fake();
        FinancialDisbursementService::pay($claim, ['amount' => 100, 'request_key' => 'rmb-idem-1'], $reimbPayer);
        $this->assertSame([], $this->queuedUserIdsFor('ServiceFinancialReimbursementPaid'));

        $cash2 = FinancialReceiptService::record($tx, [
            'amount' => 100,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-idem-2',
        ], $collector);
        $handover2 = CashHandoverService::request([$cash2->id => 100], ['request_key' => 'hnd-idem-2'], $collector);

        Queue::fake();
        CashHandoverService::reject($handover2, 'Short cash', $treasuryOfficer);
        $this->assertSame([$collector->id], $this->queuedUserIdsFor('ServiceFinancialHandoverRejected'));

        Queue::fake();
        CashHandoverService::reject($handover2->fresh(), 'Short cash', $treasuryOfficer);
        $this->assertSame([], $this->queuedUserIdsFor('ServiceFinancialHandoverRejected'));
    }
}
