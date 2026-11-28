<?php

namespace Tests\Feature;

use App\Enums\FinancialCashDirection;
use App\Enums\GeneralStatus;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskAssignmentItem;
use App\Models\TreasuryTransaction;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\FinancialTreasuryService;
use App\Services\InvoiceFinancialService;
use App\Services\OperationsReconciliationService;
use App\Services\PerformanceScoreService;
use App\Services\Tasks\Renewals\DocumentRenewalAccessService;
use App\Services\Tasks\Renewals\DocumentRenewalApprovalService;
use App\Services\Tasks\Renewals\DocumentRenewalClaimService;
use App\Services\Tasks\Renewals\DocumentRenewalRequestService;
use App\Services\Tasks\Renewals\RenewalDocumentRegistry;
use App\Services\Tasks\TaskAssignmentSummaryService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentRenewalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        RenewalDocumentRegistry::syncPermissionDefinitions();
        Permission::firstOrCreate(['name' => 'update_treasury_transaction', 'guard_name' => 'web']);
    }

    private function createCompany(string $name, array $overrides = []): Company
    {
        return Company::query()->create(array_merge([
            'trade_name' => $name,
            'status' => GeneralStatus::ACTIVE,
        ], $overrides));
    }

    private function createSaudiEmployee(Company $company, string $name, string $email): Employee
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => GeneralStatus::ACTIVE,
        ]);

        return Employee::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'employment_type' => 'full_time',
            'status' => GeneralStatus::ACTIVE,
        ]);
    }

    private function createThreeLayerAdmin(Employee $manager, array $companyIds): Department
    {
        $previousUser = auth()->user();
        $admin = User::query()->firstOrCreate(
            ['email' => 'fixture-superadmin@test.local'],
            [
                'name' => 'Fixture Super Admin',
                'password' => bcrypt('password'),
                'user_type' => UserType::SUPER_ADMIN,
                'status' => GeneralStatus::ACTIVE,
            ]
        );
        $this->actingAs($admin);

        $layer1 = Department::query()->create([
            'name' => 'Admin L1 '.$manager->id.' '.uniqid(),
            'parent_id' => null,
            'manager_id' => $manager->id,
            'is_global' => true,
            'status' => GeneralStatus::ACTIVE,
        ]);

        $layer2 = Department::query()->create([
            'name' => 'Main L2 '.$manager->id.' '.uniqid(),
            'parent_id' => $layer1->id,
            'is_global' => true,
            'status' => GeneralStatus::ACTIVE,
        ]);

        $layer3 = Department::query()->create([
            'name' => 'Sub L3 '.$manager->id.' '.uniqid(),
            'parent_id' => $layer2->id,
            'is_global' => count($companyIds) > 1,
            'company_id' => count($companyIds) > 1 ? null : ($companyIds[0] ?? null),
            'status' => GeneralStatus::ACTIVE,
        ]);

        if ($companyIds !== []) {
            $layer3->companies()->sync($companyIds);
        }

        if ($previousUser) {
            $this->actingAs($previousUser);
        } else {
            auth()->logout();
        }

        return $layer1;
    }

    public function test_dry_run_makes_zero_writes_and_live_scan_covers_all_11_types_without_duplicates(): void
    {
        $company = $this->createCompany('Acme Corp', [
            'commercial_registration_expiry' => '2026-11-20',
            'municipality_license_expiry' => '2026-11-21',
            'civil_defense_license_expiry' => '2026-11-22',
            'metadata' => [
                'subscriptions' => [
                    'qiwa' => ['expiry_date' => '2026-11-23'],
                    'absher' => ['expiry_date' => '2026-11-24'],
                    'spl' => ['expiry_date' => '2026-11-25'],
                ],
                'other_key' => 'keep-me',
            ],
        ]);

        $emp = $this->createSaudiEmployee($company, 'Worker', 'worker@test.local');
        $emp->update(['contract_end_date' => '2026-11-26']);

        EmployeeDetail::query()->create([
            'employee_id' => $emp->id,
            'iqama_number' => '2000000001',
            'iqama_expiry' => '2026-11-19',
            'insurance_expiry' => '2026-11-18',
            'work_permit_expiry' => '2026-11-17',
            'passport_expiry' => '2026-11-16',
        ]);

        // Open contract (null date) should NOT create a request
        $openContractEmp = $this->createSaudiEmployee($company, 'Open', 'open@test.local');
        $openContractEmp->update(['contract_end_date' => null]);

        // 1. Dry-run must perform ZERO writes
        $drySummary = DocumentRenewalRequestService::scan(dryRun: true, asOfDate: Carbon::parse('2026-11-28'));
        $this->assertTrue($drySummary['dry_run']);
        $this->assertSame(11, $drySummary['due_count']);
        $this->assertSame(11, $drySummary['created_count']);
        $this->assertSame(0, UnifiedDeficiencyTask::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Task::count());
        Notification::assertNothingSent();

        // 2. Historical cutoff (--since) excludes older expiries
        $sinceSummary = DocumentRenewalRequestService::scan(
            dryRun: true,
            asOfDate: Carbon::parse('2026-11-28'),
            sinceDate: '2026-11-20',
        );
        $this->assertSame(4, $sinceSummary['excluded_historical_count']);
        $this->assertSame(7, $sinceSummary['due_count']);

        // 3. Live scan creates exactly 11 requests and 11 draft expense invoices
        $liveSummary = DocumentRenewalRequestService::scan(dryRun: false, asOfDate: Carbon::parse('2026-11-28'));
        $this->assertSame(11, $liveSummary['created_count']);
        $this->assertSame(11, UnifiedDeficiencyTask::documentRenewals()->count());
        $this->assertSame(11, Invoice::renewalExpenses()->count());

        // Available renewal requests MUST have due_date = NULL (not now()+2)
        $this->assertSame(0, UnifiedDeficiencyTask::documentRenewals()->whereNotNull('due_date')->count());

        // Regular service deficiency task STILL defaults to now()+2
        $regularDef = UnifiedDeficiencyTask::query()->create([
            'company_id' => $company->id,
            'deficiency_type' => 'missing_gov_receipt',
            'title' => 'Regular deficiency',
        ]);
        $this->assertNotNull($regularDef->due_date);

        // 4. Re-running scan creates ZERO duplicates
        $secondSummary = DocumentRenewalRequestService::scan(dryRun: false, asOfDate: Carbon::parse('2026-11-28'));
        $this->assertSame(0, $secondSummary['created_count']);
        $this->assertSame(11, $secondSummary['existing_count']);
        $this->assertSame(11, UnifiedDeficiencyTask::documentRenewals()->count());
        $this->assertSame(11, Invoice::renewalExpenses()->count());
    }

    public function test_system_catalog_tasks_are_protected_from_modification_deactivation_and_deletion(): void
    {
        $task = RenewalDocumentRegistry::ensureSystemTask(RenewalDocumentRegistry::TYPE_IQAMA);
        $this->assertSame('SYS-REN-IQAMA', $task->task_code);

        // Re-fetching returns the exact same record
        $same = RenewalDocumentRegistry::ensureSystemTask(RenewalDocumentRegistry::TYPE_IQAMA);
        $this->assertSame($task->id, $same->id);

        // Cannot rename task_code
        $this->expectException(ValidationException::class);
        $task->update(['task_code' => 'CUSTOM-001']);
    }

    public function test_system_catalog_task_cannot_be_deleted(): void
    {
        $task = RenewalDocumentRegistry::ensureSystemTask(RenewalDocumentRegistry::TYPE_EMPLOYEE_CONTRACT);

        $this->expectException(ValidationException::class);
        $task->delete();
    }

    public function test_shared_layer3_multi_manager_claim_reassignment_and_permission_transfer(): void
    {
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');

        $manager1 = $this->createSaudiEmployee($companyA, 'Manager 1', 'm1@test.local');
        $manager2 = $this->createSaudiEmployee($companyA, 'Manager 2', 'm2@test.local');
        $replacement = $this->createSaudiEmployee($companyA, 'Replacement', 'rep@test.local');

        $manager1->user->givePermissionTo('manage_iqama_renewals');
        $manager2->user->givePermissionTo('manage_iqama_renewals');

        // Manager 1 manages Admin 1 covering Company A & B (shared L3), AND Admin 2 covering Company B
        $admin1 = $this->createThreeLayerAdmin($manager1, [$companyA->id, $companyB->id]);
        $this->createThreeLayerAdmin($manager1, [$companyB->id]);
        // Manager 2 also covers Company B
        $this->createThreeLayerAdmin($manager2, [$companyB->id]);

        $eligible = DocumentRenewalAccessService::eligibleManagersFor($companyB->id, RenewalDocumentRegistry::TYPE_IQAMA);
        $this->assertCount(2, $eligible);

        // Manager 1 replaced on admin1 while still managing admin2 -> permissions granted to replacement AND kept for manager1
        $this->actingAs(User::where('email', 'fixture-superadmin@test.local')->firstOrFail());
        $admin1->update(['manager_id' => $replacement->id]);
        auth()->logout();
        $syncResult = DocumentRenewalAccessService::syncLayerOneManagerPermissions(
            department: $admin1,
            oldManagerId: $manager1->id,
            newManagerId: $replacement->id,
        );
        $this->assertTrue($syncResult['kept_for_old_other_admin']);
        $this->assertTrue($replacement->user->fresh()->hasDirectPermission('manage_iqama_renewals'));
        $this->assertTrue($manager1->user->fresh()->hasDirectPermission('manage_iqama_renewals'));

        // Create renewal request on Company B
        $targetEmp = $this->createSaudiEmployee($companyB, 'Target', 'target@test.local');
        $detail = EmployeeDetail::query()->create([
            'employee_id' => $targetEmp->id,
            'iqama_number' => '2111111111',
            'iqama_expiry' => '2026-11-20',
        ]);

        DocumentRenewalRequestService::scan(types: [RenewalDocumentRegistry::TYPE_IQAMA], asOfDate: Carbon::parse('2026-11-28'));
        $request = UnifiedDeficiencyTask::documentRenewals()->firstOrFail();

        // Manager 1 claims atomically
        $claimed = DocumentRenewalClaimService::claim($manager1->user, $request);
        $this->assertFalse($claimed['already_claimed_by_user']);
        $this->assertSame($manager1->id, $claimed['request']->responsible_employee_id);
        $this->assertNotNull($claimed['request']->due_date);
        $this->assertSame($companyB->id, $claimed['item']->company_id);

        // Same manager re-claiming is idempotent
        $reclaim = DocumentRenewalClaimService::claim($manager1->user, $request);
        $this->assertTrue($reclaim['already_claimed_by_user']);
        $this->assertSame($claimed['item']->id, $reclaim['item']->id);

        // Second manager cannot claim already-claimed request
        $failed = false;
        try {
            DocumentRenewalClaimService::claim($manager2->user, $request);
        } catch (ValidationException) {
            $failed = true;
        }
        $this->assertTrue($failed);

        // General TaskStatus::TRANSFERRED is blocked on renewal items
        $transferBlocked = false;
        try {
            $item = $claimed['item']->fresh();
            $item->status = TaskStatus::TRANSFERRED;
            $item->metadata = array_merge($item->metadata ?? [], ['transferred_to_employee_id' => $manager2->id]);
            $item->save();
        } catch (ValidationException) {
            $transferBlocked = true;
        }
        $this->assertTrue($transferBlocked);

        // Dedicated reassignment preserves item ID and due_date
        $superAdmin = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'sa@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN,
            'status' => GeneralStatus::ACTIVE,
        ]);

        $originalDueDate = $claimed['item']->due_date->toDateString();
        $reassignedItem = DocumentRenewalClaimService::reassign($superAdmin, $request, $manager2, 'Manager on leave');
        $this->assertSame($claimed['item']->id, $reassignedItem->id);
        $this->assertSame($manager2->id, $request->fresh()->responsible_employee_id);
        $this->assertSame($originalDueDate, $reassignedItem->due_date->toDateString());
    }

    public function test_full_financial_approval_return_late_scoring_and_isolation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00'));

        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_DEADLINE_DAYS], ['value' => 5]);
        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_TASK_WEIGHT], ['value' => 20]);
        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_LATE_COMPLETION_PERCENTAGE], ['value' => 60]);

        $company = $this->createCompany('Fin Corp', [
            'metadata' => [
                'subscriptions' => [
                    'qiwa' => ['expiry_date' => '2026-11-01'],
                    'spl' => ['expiry_date' => '2027-06-01'],
                ],
                'custom_flag' => 'preserve-me',
            ],
        ]);

        // Fund treasury with 5000
        FinancialTreasuryService::post(
            companyId: $company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: 5000,
            description: 'Opening balance',
        );

        $manager = $this->createSaudiEmployee($company, 'Renew Manager', 'rm@test.local');
        $manager->user->givePermissionTo(['manage_qiwa_subscription_renewals', 'update_treasury_transaction']);
        $this->createThreeLayerAdmin($manager, [$company->id]);

        $treasuryUser = $this->createSaudiEmployee($company, 'Treasury Officer', 'tr@test.local')->user;
        $treasuryUser->givePermissionTo('update_treasury_transaction');

        DocumentRenewalRequestService::scan(
            types: [RenewalDocumentRegistry::TYPE_SUBSCRIPTION_QIWA],
            asOfDate: Carbon::parse('2026-11-10'),
        );
        $request = UnifiedDeficiencyTask::documentRenewals()->firstOrFail();
        $invoice = Invoice::renewalExpenses()->firstOrFail();

        // Isolation checks: reconciliation cannot resolve renewal, invoice cannot be activated/deleted via general service
        $this->assertThrows(fn () => OperationsReconciliationService::resolveTask($request), ValidationException::class);
        $this->assertThrows(fn () => InvoiceFinancialService::activate($invoice), ValidationException::class);
        $this->assertThrows(fn () => InvoiceFinancialService::assertDeletable($invoice), ValidationException::class);

        // Claim on Nov 10 -> due_date = Nov 15
        $claimed = DocumentRenewalClaimService::claim($manager->user, $request);
        $this->assertSame('2026-11-15', $claimed['request']->due_date->toDateString());

        // Attempt 1 submitted on Nov 12 (on time), then returned by treasury
        Carbon::setTestNow(Carbon::parse('2026-11-12 12:00:00'));
        $submitted1 = DocumentRenewalApprovalService::submitForTreasury($manager->user, $request, [
            'fee_mode' => Invoice::RENEWAL_FEE_WITH_FEE,
            'amount' => 1200,
            'new_expiry_date' => '2027-11-01',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-001',
        ]);

        // Source expiry must NOT change before approval, and treasury must NOT be deducted
        $this->assertSame('2026-11-01', data_get($company->fresh()->metadata, 'subscriptions.qiwa.expiry_date'));
        $this->assertSame(5000.0, FinancialTreasuryService::balance($company->id));

        // Self-approval strictly blocked even though manager has update_treasury_transaction
        $this->assertThrows(
            fn () => DocumentRenewalApprovalService::approve($manager->user, $submitted1, 1),
            AuthorizationException::class
        );

        // Treasury returns Attempt 1 -> due_date MUST NOT reset
        $returned = DocumentRenewalApprovalService::returnForCorrection($treasuryUser, $submitted1, 'Wrong invoice amount', 1);
        $this->assertSame(UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED, $returned->status);
        $this->assertSame('2026-11-15', $returned->due_date->toDateString());
        $this->assertSame(5000.0, FinancialTreasuryService::balance($company->id));

        // Attempt 2 submitted on Nov 17 (LATE after Nov 15) and approved on Nov 18
        Carbon::setTestNow(Carbon::parse('2026-11-17 09:00:00'));
        $submitted2 = DocumentRenewalApprovalService::submitForTreasury($manager->user, $returned, [
            'fee_mode' => Invoice::RENEWAL_FEE_WITH_FEE,
            'amount' => 1500,
            'new_expiry_date' => '2027-11-01',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-002',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-18 14:00:00'));
        $approved = DocumentRenewalApprovalService::approve($treasuryUser, $submitted2, 2, 'Verified');

        $this->assertSame(UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED, $approved->status);
        // Treasury deducted exactly once (5000 - 1500 = 3500)
        $this->assertSame(3500.0, FinancialTreasuryService::balance($company->id));
        // Qiwa updated, SPL and custom_flag preserved!
        $freshMeta = $company->fresh()->metadata;
        $this->assertSame('2027-11-01', data_get($freshMeta, 'subscriptions.qiwa.expiry_date'));
        $this->assertSame('2027-06-01', data_get($freshMeta, 'subscriptions.spl.expiry_date'));
        $this->assertSame('preserve-me', $freshMeta['custom_flag']);

        // Late score = 20 * 60% = 12.00 (based on accepted Attempt 2 date Nov 17 > Nov 15)
        $item = TaskAssignmentItem::where('renewal_request_id', $approved->id)->firstOrFail();
        $this->assertSame(TaskStatus::COMPLETED, $item->status);
        $this->assertEquals(12.0, (float) $item->score);
        $this->assertSame('2026-11-17', $item->completed_at->toDateString());

        Carbon::setTestNow();
    }

    public function test_no_fee_renewal_updates_contract_and_scores_on_time_without_treasury_movement(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00'));

        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_DEADLINE_DAYS], ['value' => 5]);
        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_TASK_WEIGHT], ['value' => 15]);
        Setting::updateOrCreate(['key' => RenewalDocumentRegistry::SETTING_LATE_COMPLETION_PERCENTAGE], ['value' => 50]);

        $company = $this->createCompany('Contract Corp');
        $manager = $this->createSaudiEmployee($company, 'HR Manager', 'hrm@test.local');
        $manager->user->givePermissionTo('manage_employee_contract_renewals');
        $this->createThreeLayerAdmin($manager, [$company->id]);

        $treasuryUser = $this->createSaudiEmployee($company, 'Treasury', 'tr2@test.local')->user;
        $treasuryUser->givePermissionTo('update_treasury_transaction');

        $worker = $this->createSaudiEmployee($company, 'Staff', 'staff@test.local');
        $worker->update(['contract_end_date' => '2026-11-05']);

        DocumentRenewalRequestService::scan(
            types: [RenewalDocumentRegistry::TYPE_EMPLOYEE_CONTRACT],
            asOfDate: Carbon::parse('2026-11-10'),
        );
        $request = UnifiedDeficiencyTask::documentRenewals()->firstOrFail();

        DocumentRenewalClaimService::claim($manager->user, $request);

        // Zero amount with with_fee must fail
        $this->assertThrows(
            fn () => DocumentRenewalApprovalService::submitForTreasury($manager->user, $request, [
                'fee_mode' => Invoice::RENEWAL_FEE_WITH_FEE,
                'amount' => 0,
                'new_expiry_date' => '2027-11-05',
            ]),
            ValidationException::class
        );

        // Positive amount with no_fee must fail
        $this->assertThrows(
            fn () => DocumentRenewalApprovalService::submitForTreasury($manager->user, $request, [
                'fee_mode' => Invoice::RENEWAL_FEE_NO_FEE,
                'amount' => 100,
                'new_expiry_date' => '2027-11-05',
            ]),
            ValidationException::class
        );

        // Submit on time (Nov 12 <= Nov 15) with no_fee
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00'));
        $submitted = DocumentRenewalApprovalService::submitForTreasury($manager->user, $request, [
            'fee_mode' => Invoice::RENEWAL_FEE_NO_FEE,
            'amount' => 0,
            'new_expiry_date' => '2027-11-05',
        ]);

        // Approved AFTER due date (Nov 18 > Nov 15) -> STILL FULL SCORE because accepted submission was on time!
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));
        $approved = DocumentRenewalApprovalService::approve($treasuryUser, $submitted, 1);

        $this->assertSame(UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED, $approved->status);
        $this->assertSame(0, TreasuryTransaction::count());
        $this->assertSame('2027-11-05', $worker->fresh()->contract_end_date->toDateString());

        $item = TaskAssignmentItem::where('renewal_request_id', $approved->id)->firstOrFail();
        $this->assertEquals(15.0, (float) $item->score);

        $invoice = Invoice::renewalExpenses()->firstOrFail();
        $this->assertSame('approved_no_fee', $invoice->metadata['renewal_workflow_status'] ?? null);

        Carbon::setTestNow();
    }

    public function test_notification_delivery_branch_scope_manager_replacement_and_cross_company_performance(): void
    {
        $companyA = $this->createCompany('Home Co A');
        $companyB = $this->createCompany('Doc Co B');
        $branch1 = Branch::query()->create(['company_id' => $companyB->id, 'name' => 'Branch 1', 'status' => 'active']);
        $branch2 = Branch::query()->create(['company_id' => $companyB->id, 'name' => 'Branch 2', 'status' => 'active']);

        // Manager belongs to Company A, but manages an admin covering Company B
        $manager = $this->createSaudiEmployee($companyA, 'Cross Manager', 'cm@test.local');
        $manager->user->givePermissionTo('manage_iqama_renewals');
        $admin = $this->createThreeLayerAdmin($manager, [$companyB->id]);

        // Replacement manager receives transferred permissions even if selectedPermissions is []
        $replacement = $this->createSaudiEmployee($companyA, 'New Manager', 'nm@test.local');
        $this->actingAs(User::where('email', 'fixture-superadmin@test.local')->firstOrFail());
        $admin->update(['manager_id' => $replacement->id]);
        DocumentRenewalAccessService::syncLayerOneManagerPermissions(
            department: $admin,
            oldManagerId: $manager->id,
            newManagerId: $replacement->id,
            selectedPermissions: [],
        );
        auth()->logout();
        $this->assertTrue($replacement->user->fresh()->hasDirectPermission('manage_iqama_renewals'));

        // Out-of-scope manager cannot be reassigned to Company B
        $outsider = $this->createSaudiEmployee($companyA, 'Outsider', 'out@test.local');
        $outsider->user->givePermissionTo('manage_iqama_renewals');
        $this->createThreeLayerAdmin($outsider, [$companyA->id]);

        // Treasury officer restricted to Branch 1 vs Branch 2
        $treasuryB1 = $this->createSaudiEmployee($companyB, 'Treasury B1', 'tb1@test.local');
        $treasuryB1->update(['branch_id' => $branch1->id]);
        $treasuryB1->user->givePermissionTo('update_treasury_transaction');

        $treasuryB2 = $this->createSaudiEmployee($companyB, 'Treasury B2', 'tb2@test.local');
        $treasuryB2->update(['branch_id' => $branch2->id]);
        $treasuryB2->user->givePermissionTo('update_treasury_transaction');

        // Target employee in Company B, Branch 1
        $target = $this->createSaudiEmployee($companyB, 'Target B1', 'targetb1@test.local');
        $target->update(['branch_id' => $branch1->id]);
        EmployeeDetail::query()->create([
            'employee_id' => $target->id,
            'iqama_number' => '2999999999',
            'iqama_expiry' => '2026-11-20',
        ]);

        DocumentRenewalRequestService::scan(types: [RenewalDocumentRegistry::TYPE_IQAMA], asOfDate: Carbon::parse('2026-11-28'));
        $request = UnifiedDeficiencyTask::documentRenewals()->firstOrFail();

        // 1. Notification::shouldSend() allows normal eligible manager for 'available'
        $availNotif = new BaseSystemNotification(
            eventKey: 'document_renewal_available',
            customTitle: 't',
            customMessage: 'm',
            recordType: UnifiedDeficiencyTask::class,
            recordId: $request->id,
            context: ['routing_mode' => 'document_renewal_targeted', 'renewal_event' => 'available'],
        );
        $this->assertTrue($availNotif->shouldSend($replacement->user, 'database'));
        $this->assertFalse($availNotif->shouldSend($outsider->user, 'database'));

        // Claim as replacement
        $claimed = DocumentRenewalClaimService::claim($replacement->user, $request);
        $this->assertFalse($availNotif->shouldSend($replacement->user, 'database'));

        // Reassign to outsider must fail because outsider does not cover Company B
        $this->assertThrows(
            fn () => DocumentRenewalClaimService::reassign(
                User::where('email', 'fixture-superadmin@test.local')->firstOrFail(),
                $request,
                $outsider,
                'Cross company test',
            ),
            ValidationException::class
        );

        // 2. Performance aggregation attributes overdue item to Company B (document company), NOT Company A (manager home company)
        $item = $claimed['item'];
        TaskAssignmentItem::withoutReviewGuard(function () use ($item): void {
            $item->update(['due_date' => '2026-11-20']);
        });
        $overdueScores = PerformanceScoreService::overdueTasksByScope([$companyA->id, $companyB->id], null, null);
        $this->assertSame(0, (int) ($overdueScores->where('company_id', $companyA->id)->sum('count')));
        $this->assertSame(1, (int) ($overdueScores->where('company_id', $companyB->id)->sum('count')));

        // TaskAssignmentSummaryService::items() and summaryQuery() attribute renewal item to Company B (document), not Company A (manager)
        $this->actingAs(User::where('email', 'fixture-superadmin@test.local')->firstOrFail());
        $this->assertSame(1, TaskAssignmentSummaryService::items(['company_id' => [$companyB->id]])->count());
        $this->assertSame(0, TaskAssignmentSummaryService::items(['company_id' => [$companyA->id]])->count());

        $summaryB = TaskAssignmentSummaryService::summaryQuery(['company_id' => [$companyB->id], 'only_with_items' => true])->get();
        $this->assertCount(1, $summaryB);
        $this->assertSame($replacement->id, (int) $summaryB->first()->id);
        $this->assertSame(1, (int) $summaryB->first()->total_items);

        $summaryA = TaskAssignmentSummaryService::summaryQuery(['company_id' => [$companyA->id], 'only_with_items' => true])->get();
        $this->assertCount(0, $summaryA);

        // Non-super-admin Owner of Company B MUST see Company B's renewal item even though manager belongs to Company A
        $ownerBUser = User::query()->create([
            'name' => 'Owner B',
            'email' => 'ownerb@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::OWNER,
            'status' => GeneralStatus::ACTIVE,
        ]);
        Owner::query()->create(['user_id' => $ownerBUser->id, 'company_id' => $companyB->id]);

        $ownerAUser = User::query()->create([
            'name' => 'Owner A',
            'email' => 'ownera@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::OWNER,
            'status' => GeneralStatus::ACTIVE,
        ]);
        Owner::query()->create(['user_id' => $ownerAUser->id, 'company_id' => $companyA->id]);

        $this->actingAs($ownerBUser);
        $ownerBSummary = TaskAssignmentSummaryService::summaryQuery(['only_with_items' => true])->get();
        $this->assertCount(1, $ownerBSummary);
        $this->assertSame(1, (int) $ownerBSummary->first()->total_items);

        $this->actingAs($ownerAUser);
        $ownerASummary = TaskAssignmentSummaryService::summaryQuery(['only_with_items' => true])->get();
        $this->assertCount(0, $ownerASummary);
        auth()->logout();

        // Branch-restricted visibility: treasuryB1 CAN view Branch 1 request, treasuryB2 CANNOT
        $this->assertTrue(DocumentRenewalAccessService::canViewRequest($treasuryB1->user, $request));
        $this->assertFalse(DocumentRenewalAccessService::canViewRequest($treasuryB2->user, $request));

        // Submit for treasury
        $submitted = DocumentRenewalApprovalService::submitForTreasury($replacement->user, $request, [
            'fee_mode' => Invoice::RENEWAL_FEE_NO_FEE,
            'amount' => 0,
            'new_expiry_date' => '2027-11-20',
        ]);

        // 3. Branch-scoped treasury check + notification delivery
        $treasuryNotif = new BaseSystemNotification(
            eventKey: 'document_renewal_pending_treasury',
            customTitle: 't',
            customMessage: 'm',
            recordType: UnifiedDeficiencyTask::class,
            recordId: $submitted->id,
            context: ['routing_mode' => 'document_renewal_targeted', 'renewal_event' => 'pending_treasury'],
        );
        $this->assertTrue($treasuryNotif->shouldSend($treasuryB1->user, 'database'));
        $this->assertFalse($treasuryNotif->shouldSend($treasuryB2->user, 'database'));

        $this->assertThrows(
            fn () => DocumentRenewalApprovalService::approve($treasuryB2->user, $submitted, 1),
            AuthorizationException::class
        );

        // Stale version check (expectedVersion = 0 when current is 1)
        $this->assertThrows(
            fn () => DocumentRenewalApprovalService::approve($treasuryB1->user, $submitted, 0),
            ValidationException::class
        );

        // Removing Company B from Layer 3 blocks further edit/submission
        $layer3 = Department::where('parent_id', Department::where('parent_id', $admin->id)->value('id'))->firstOrFail();
        $layer3->companies()->sync([$companyA->id]);
        $layer3->update(['company_id' => $companyA->id]);
        $this->assertFalse(DocumentRenewalAccessService::canEditRenewalWork($replacement->user, $submitted));
    }

    public function test_scan_avoids_n_plus_one_queries_across_multiple_candidates(): void
    {
        $company = $this->createCompany('Batch Corp');
        $manager = $this->createSaudiEmployee($company, 'Batch Mgr', 'bm@test.local');
        $manager->user->givePermissionTo('manage_iqama_renewals');
        $this->createThreeLayerAdmin($manager, [$company->id]);

        for ($i = 1; $i <= 12; $i++) {
            $emp = $this->createSaudiEmployee($company, "Emp {$i}", "emp{$i}@test.local");
            EmployeeDetail::query()->create([
                'employee_id' => $emp->id,
                'iqama_number' => '28000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'iqama_expiry' => '2026-11-20',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $summary = DocumentRenewalRequestService::scan(
            types: [RenewalDocumentRegistry::TYPE_IQAMA],
            dryRun: true,
            asOfDate: Carbon::parse('2026-11-28'),
        );
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(12, $summary['due_count']);
        $this->assertSame(12, $summary['created_count']);
        // Preloaded hierarchy + 1 chunk query + 1 batch task_key query (well under 15 queries for 12 candidates)
        $this->assertLessThan(15, $queryCount, "Expected batched queries without N+1, got {$queryCount}");
    }

    public function test_live_scan_with_multiple_managers_does_not_requery_departments_per_request(): void
    {
        $company = $this->createCompany('Live Batch Corp');

        // Create 5 eligible managers + 1 temporary manager (must be excluded cleanly without SQL errors)
        for ($m = 1; $m <= 5; $m++) {
            $mgr = $this->createSaudiEmployee($company, "Mgr {$m}", "mgr{$m}@test.local");
            $mgr->user->givePermissionTo('manage_iqama_renewals');
            $this->createThreeLayerAdmin($mgr, [$company->id]);
        }
        $tempMgr = $this->createSaudiEmployee($company, 'Temp Mgr', 'tmgr@test.local');
        $tempMgr->update(['employment_type' => 'temporary']);
        $tempMgr->user->givePermissionTo('manage_iqama_renewals');
        $this->createThreeLayerAdmin($tempMgr, [$company->id]);

        // Create 10 expiring employees
        for ($i = 1; $i <= 10; $i++) {
            $emp = $this->createSaudiEmployee($company, "Live Emp {$i}", "lemp{$i}@test.local");
            EmployeeDetail::query()->create([
                'employee_id' => $emp->id,
                'iqama_number' => '27000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'iqama_expiry' => '2026-11-20',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $summary = DocumentRenewalRequestService::scan(
            types: [RenewalDocumentRegistry::TYPE_IQAMA],
            dryRun: false,
            asOfDate: Carbon::parse('2026-11-28'),
        );
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertSame(10, $summary['created_count']);
        $hierarchyScanQueries = $queries->filter(fn (string $q) => str_contains($q, '"departments"') && str_contains($q, '"manager_id"') && ! str_contains($q, '"manager_id" = ?'))->count();
        $this->assertSame(1, $hierarchyScanQueries, "Expected full department hierarchy to be loaded once during live scan, got {$hierarchyScanQueries} queries");
    }
}
