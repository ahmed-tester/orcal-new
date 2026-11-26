<?php

namespace Tests\Feature;

use App\Enums\FinancialCashDirection;
use App\Enums\PaymentItemCategory;
use App\Enums\PaymentStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Enums\UserType;
use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Services\FinancialTreasuryService;
use App\Services\Payroll\FinancialApprovalNotificationService;
use App\Services\Payroll\FinancialApprovalService;
use App\Services\Payroll\PayrollApprovalScopeService;
use App\Services\Payroll\PayrollDisbursementService;
use App\Services\Payroll\PayrollSheetService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollApprovalSecurityAndRefactorTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private User $superAdmin;

    private User $submitterA;

    private User $submitterNoCompany;

    private User $groupReviewer;

    private User $layer1Manager;

    private Department $adminDept;

    private Department $mainDept;

    private Department $subDeptA;

    private Department $subDeptB;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/payroll_approval_security.sqlite';

        if (! is_dir(dirname($database))) {
            mkdir(dirname($database), 0777, true);
        }

        if (! file_exists($database)) {
            touch($database);
        }

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $database;
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $database;
        RefreshDatabaseState::$migrated = false;

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'submit_financial_request',
            'approve_financial_request',
            'view_financial_request',
            'update_treasury_transaction',
            'disburse_payroll_salary',
            'update_payroll_record',
            'view_any_payroll_record',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->companyA = Company::create(['trade_name' => 'Company A', 'legal_name' => 'Company A LLC', 'status' => 'active']);
        $this->companyB = Company::create(['trade_name' => 'Company B', 'legal_name' => 'Company B LLC', 'status' => 'active']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);
        $this->actingAs($this->superAdmin);

        $this->layer1Manager = User::factory()->create([
            'name' => 'Layer 1 Manager',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->layer1Manager->givePermissionTo(['approve_financial_request', 'view_financial_request']);
        $managerEmployee = Employee::create([
            'user_id' => $this->layer1Manager->id,
            'basic_salary' => 10000,
            'status' => 'active',
        ]);

        $this->adminDept = Department::create([
            'name' => 'Group Finance Administration',
            'is_global' => true,
            'manager_id' => $managerEmployee->id,
            'status' => 'active',
        ]);
        $this->mainDept = Department::create([
            'name' => 'General Payroll Dept',
            'parent_id' => $this->adminDept->id,
            'is_global' => true,
            'status' => 'active',
        ]);
        $this->subDeptA = Department::create([
            'name' => 'Sub Dept A',
            'parent_id' => $this->mainDept->id,
            'company_id' => $this->companyA->id,
            'is_global' => false,
            'status' => 'active',
        ]);
        $this->subDeptB = Department::create([
            'name' => 'Sub Dept B',
            'parent_id' => $this->mainDept->id,
            'company_id' => $this->companyB->id,
            'is_global' => false,
            'status' => 'active',
        ]);

        $this->submitterA = User::factory()->create([
            'name' => 'Submitter A',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->submitterA->givePermissionTo(['submit_financial_request', 'view_financial_request']);
        Employee::create([
            'user_id' => $this->submitterA->id,
            'company_id' => $this->companyA->id,
            'department_id' => $this->subDeptA->id,
            'basic_salary' => 5000,
            'status' => 'active',
        ]);

        $this->submitterNoCompany = User::factory()->create([
            'name' => 'Submitter No Company',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->submitterNoCompany->givePermissionTo(['submit_financial_request', 'view_financial_request']);

        $this->groupReviewer = User::factory()->create([
            'name' => 'Group Treasury Reviewer',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->groupReviewer->givePermissionTo(['update_treasury_transaction', 'view_financial_request']);
        Employee::create([
            'user_id' => $this->groupReviewer->id,
            'department_id' => $this->adminDept->id,
            'basic_salary' => 7000,
            'status' => 'active',
        ]);
    }

    public function test_gap1_real_database_notifications_are_delivered_without_update_payroll_permission(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->groupReviewer->id,
        ]);

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->layer1Manager->id,
        ]);
        $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $reviewed->status);
    }

    public function test_gap2_submitter_cannot_submit_out_of_scope_payroll_or_hijack_attempt_key(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        try {
            FinancialApprovalService::submitPayrollBatch([
                ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
            ], $this->submitterNoCompany, '2026-11', attemptKey: 'attempt:out-of-scope');
            $this->fail('Expected AuthorizationException for submitter without company scope.');
        } catch (AuthorizationException) {
            $this->assertSame(0, FinancialApprovalRequest::count());
        }

        $legit = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11', attemptKey: 'attempt:shared-key');

        try {
            FinancialApprovalService::submitPayrollBatch([
                ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
            ], $this->submitterNoCompany, '2026-11', attemptKey: 'attempt:shared-key');
            $this->fail('Expected AuthorizationException when unauthorized user reuses attempt key.');
        } catch (AuthorizationException) {
            $this->assertSame($legit->id, FinancialApprovalRequest::first()->id);
        }
    }

    public function test_gap3_direct_disbursement_and_boolean_bypass_flags_are_blocked(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $item = $request->items()->firstOrFail();

        // Direct call to disburseFromApprovedRequest while PENDING_TREASURY must fail.
        try {
            PayrollDisbursementService::disburseFromApprovedRequest($item, $request, $this->layer1Manager);
            $this->fail('Expected failure when calling disburseFromApprovedRequest on PENDING_TREASURY request.');
        } catch (\Throwable) {
            $this->assertSame(0, PayrollPayment::count());
            $this->assertEquals(20000.0, FinancialTreasuryService::balance($this->companyA->id));
        }

        // Boolean approved_request_context=true must NOT bypass approval workflow in disburse().
        try {
            PayrollDisbursementService::disburse($recA, [
                'request_key' => PayrollDisbursementService::newRequestKey(),
                'amount' => 3000,
                'payment_method' => 'cash',
                'approved_request_context' => true,
            ], $this->superAdmin);
            $this->fail('Expected ValidationException blocking approved_request_context boolean bypass.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval_request', $e->errors());
            $this->assertSame(0, PayrollPayment::count());
        }
    }

    public function test_gap4_revoked_reviewer_eligibility_blocks_old_manager_approval(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);

        // Revoke treasury permission from the reviewer after treasury approval.
        $this->groupReviewer->revokePermissionTo('update_treasury_transaction');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);
            $this->fail('Expected failure when reviewer lost treasury eligibility before manager approval.');
        } catch (AuthorizationException|ValidationException) {
            $this->assertSame(0, PayrollPayment::count());
            $this->assertEquals(20000.0, FinancialTreasuryService::balance($this->companyA->id));
            $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $reviewed->fresh()->status);
        }
    }

    public function test_gap5_modifying_payroll_record_after_submission_invalidates_old_snapshot(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);

        // Modify source payroll record financial figures after treasury review.
        $recA->update([
            'basic_salary' => 4000,
            'net_amount' => 4000,
            'remaining_amount' => 4000,
        ]);

        try {
            FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);
            $this->fail('Expected ValidationException when source payroll record changed after submission.');
        } catch (ValidationException) {
            $this->assertSame(0, PayrollPayment::count());
            $this->assertEquals(20000.0, FinancialTreasuryService::balance($this->companyA->id));
            $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $reviewed->fresh()->status);
        }
    }

    public function test_rerouting_resets_version_logs_old_and_new_path_and_completes_approval(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);

        // Revoke old reviewer's permission so old route becomes invalid.
        $this->groupReviewer->revokePermissionTo('update_treasury_transaction');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Add a second eligible reviewer covering Company A.
        $backupReviewer = User::factory()->create([
            'name' => 'Backup Reviewer',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $backupReviewer->givePermissionTo(['update_treasury_transaction', 'view_financial_request']);
        Employee::create([
            'user_id' => $backupReviewer->id,
            'department_id' => $this->adminDept->id,
            'basic_salary' => 6500,
            'status' => 'active',
        ]);

        // Unauthorized user cannot reroute.
        try {
            FinancialApprovalService::rerouteRequest($reviewed, $this->submitterNoCompany, 'No access');
            $this->fail('Expected AuthorizationException for unauthorized rerouter.');
        } catch (AuthorizationException) {
            $this->assertSame(1, $reviewed->fresh()->version);
        }

        // SubmitterA reroutes cleanly to v2.
        $rerouted = FinancialApprovalService::rerouteRequest($reviewed, $this->submitterA, 'Original reviewer lost permission');
        $this->assertSame(FinancialApprovalStatus::PENDING_TREASURY, $rerouted->status);
        $this->assertSame(2, $rerouted->version);
        $this->assertNull($rerouted->treasury_reviewer_id);
        $this->assertNull($rerouted->manager_user_id);
        $this->assertTrue($rerouted->logs()->where('action', 'rerouted')->exists());

        // Backup reviewer approves v2, then Layer 1 manager approves and disburses.
        $reviewedV2 = FinancialApprovalService::approveByTreasury($rerouted, $backupReviewer);
        $executedV2 = FinancialApprovalService::approveByManager($reviewedV2, $this->layer1Manager);

        $this->assertSame(FinancialApprovalStatus::EXECUTED, $executedV2->status);
        $this->assertSame(1, PayrollPayment::count());
        $this->assertEquals(17000.0, FinancialTreasuryService::balance($this->companyA->id));
    }

    public function test_notification_deduplication_and_idempotent_manager_approval_security(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->groupReviewer->id)->count());

        // Calling dispatch again for the same request/version/stage/recipient must NOT duplicate.
        FinancialApprovalNotificationService::dispatch(
            collect([$this->groupReviewer]),
            $request,
            'FinancialApprovalSubmitted',
            'financial.notifications.approval_submitted_title',
            'financial.notifications.approval_submitted_body',
            'treasury',
            'warning',
        );
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->groupReviewer->id)->count());

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);
        $executed = FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);

        $submitterNotifCount = DB::table('notifications')->where('notifiable_id', $this->submitterA->id)->count();
        $this->assertSame(1, $submitterNotifCount);

        // Idempotent retry by authorized manager does not re-disburse or duplicate executed notification.
        FinancialApprovalService::approveByManager($executed, $this->layer1Manager);
        $this->assertSame(1, PayrollPayment::count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->submitterA->id)->count());

        // Unauthorized user cannot use idempotency on EXECUTED request to bypass read authorization.
        try {
            FinancialApprovalService::approveByManager($executed, $this->submitterNoCompany);
            $this->fail('Expected AuthorizationException on idempotent EXECUTED request for unauthorized actor.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function test_no_partial_payroll_approval_request_when_selected_employee_fails_to_save(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $this->actingAs($this->submitterA);
        $result = PayrollSheetService::saveSheetDetailed(
            employeeRows: [
                [
                    'employee_id' => $recA->employee_id,
                    'company_id' => $this->companyA->id,
                    'basic_salary' => 3000,
                    'additional_amount' => 0,
                    'deduction_amount' => 0,
                    'net_amount' => 3000,
                ],
                [
                    'employee_id' => 999999,
                    'company_id' => $this->companyA->id,
                    'basic_salary' => 2000,
                    'additional_amount' => 0,
                    'deduction_amount' => 0,
                    'net_amount' => 2000,
                ],
            ],
            checkedEmployees: [
                $recA->employee_id => true,
                999999 => true,
            ],
            notes: [],
            operationType: 'payroll',
            payrollMonth: '2026-11',
            attemptKey: 'sheet:partial-save-fail',
        );

        $this->assertArrayNotHasKey('approval_request', $result);
        $this->assertNotEmpty($result['failed']);
        $this->assertSame(0, FinancialApprovalRequest::count());
    }

    public function test_legacy_namespace_compatibility_resolves_moved_models_and_notifications(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $legacyRequestClass = 'App\\Models\\FinancialApprovalRequest';
        $legacyRecordClass = 'App\\Models\\PayrollRecord';

        $this->assertTrue(is_a($request, $legacyRequestClass));
        $this->assertTrue(is_a($recA, $legacyRecordClass));

        $found = $legacyRequestClass::find($request->id);
        $this->assertNotNull($found);
        $this->assertSame($request->id, $found->id);

        $notification = new BaseSystemNotification(
            eventKey: 'FinancialApprovalSubmitted',
            customTitle: 'Legacy',
            customMessage: 'Legacy',
            recordType: $legacyRequestClass,
            recordId: $request->id,
            context: [
                'routing_mode' => 'financial_approval',
                'approval_request_id' => $request->id,
                'approval_version' => 1,
                'approval_stage' => 'treasury',
                'dedup_key' => 'legacy:test:'.$request->id,
            ],
        );

        $this->assertTrue($notification->shouldSend($this->groupReviewer, 'database'));
    }

    public function test_resource_query_excludes_pending_manager_request_when_reviewer_loses_company_coverage(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $reviewerA = User::factory()->create([
            'name' => 'Reviewer A Only',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $reviewerA->givePermissionTo(['update_treasury_transaction', 'view_financial_request']);
        $reviewerEmp = Employee::create([
            'user_id' => $reviewerA->id,
            'department_id' => $this->subDeptA->id,
            'basic_salary' => 6000,
            'status' => 'active',
        ]);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $reviewed = FinancialApprovalService::approveByTreasury($request, $reviewerA);

        // Move reviewerA to subDeptB (covers Company B only, no longer Company A).
        $reviewerEmp->update(['department_id' => $this->subDeptB->id]);

        $this->assertFalse(PayrollApprovalScopeService::canViewRequest($reviewerA, $reviewed->fresh()));

        $this->actingAs($reviewerA);
        $this->assertSame(0, FinancialApprovalRequestResource::getEloquentQuery()->count());

        // SubmitterA still sees the request in the resource query.
        $this->actingAs($this->submitterA);
        $this->assertSame(1, FinancialApprovalRequestResource::getEloquentQuery()->count());
    }

    public function test_details_modal_renders_only_current_version_items_after_rerouting(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);
        $employeeName = $recA->employee->user->name;

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $reroutedV2 = FinancialApprovalService::rerouteRequest($request, $this->submitterA, 'Reroute 1');
        $reroutedV3 = FinancialApprovalService::rerouteRequest($reroutedV2, $this->submitterA, 'Reroute 2');

        // All 3 versions remain in storage for audit.
        $this->assertSame(3, $reroutedV3->items()->count());

        $currentItems = $reroutedV3->items()->with('company')->where('version', $reroutedV3->version)->get();
        $htmlPending = view('filament.financial-approvals.request-details-modal', [
            'request' => $reroutedV3->load(['companies', 'submitter', 'treasuryReviewer', 'treasuryReviewerDepartment', 'managerDepartment', 'managerUser', 'logs.actor']),
            'currentItems' => $currentItems,
        ])->render();
        $this->assertSame(1, substr_count($htmlPending, $employeeName));

        // After execution, current version items still render once.
        $reviewed = FinancialApprovalService::approveByTreasury($reroutedV3, $this->groupReviewer);
        $executed = FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);
        $executedItems = $executed->items()->with('company')->where('version', $executed->version)->get();
        $htmlExecuted = view('filament.financial-approvals.request-details-modal', [
            'request' => $executed->load(['companies', 'submitter', 'treasuryReviewer', 'treasuryReviewerDepartment', 'managerDepartment', 'managerUser', 'logs.actor']),
            'currentItems' => $executedItems,
        ])->render();
        $this->assertSame(1, substr_count($htmlExecuted, $employeeName));
    }

    public function test_atomic_dedup_key_hash_prevents_duplicate_database_notification_on_concurrent_channel_send(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitterA, '2026-11');

        $dedupKey = FinancialApprovalNotificationService::dedupKey(
            $request,
            'treasury',
            'FinancialApprovalSubmitted',
            (int) $this->groupReviewer->id,
        );

        // Already delivered once during submitPayrollBatch.
        $this->assertSame(1, DB::table('notifications')->where('dedup_key_hash', hash('sha256', $dedupKey))->count());

        // Simulate a concurrent worker calling DeduplicatedDatabaseChannel::send() directly with the same dedup_key.
        $channel = app(DeduplicatedDatabaseChannel::class);
        $duplicate = new BaseSystemNotification(
            eventKey: 'FinancialApprovalSubmitted',
            customTitle: 'Concurrent Duplicate',
            customMessage: 'Concurrent Duplicate',
            recordType: FinancialApprovalRequest::class,
            recordId: $request->id,
            context: [
                'routing_mode' => 'financial_approval',
                'approval_request_id' => $request->id,
                'approval_version' => 1,
                'approval_stage' => 'treasury',
                'dedup_key' => $dedupKey,
            ],
        );
        $duplicate->id = Str::uuid()->toString();

        $saved = $channel->send($this->groupReviewer, $duplicate);
        $this->assertNull($saved);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->groupReviewer->id)->count());
    }

    private function fundTreasury(Company $company, float $amount): void
    {
        FinancialTreasuryService::post(
            companyId: $company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: $amount,
            description: 'Initial test funding',
            metadata: ['source' => 'test_seed'],
            category: PaymentItemCategory::OTHER,
        );
    }

    private function createPayrollRecord(Company $company, float $amount): PayrollRecord
    {
        $user = User::factory()->create([
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'basic_salary' => $amount,
            'daily_working_hours' => 8,
            'status' => 'active',
        ]);

        return PayrollRecord::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'payroll_month' => '2026-11-01',
            'record_date' => now()->toDateString(),
            'basic_salary' => $amount,
            'net_amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'payment_status' => PaymentStatus::UNPAID,
            'operation_type' => 'payroll',
            'metadata' => ['financial_flow' => PayrollRecord::FLOW_MANAGED],
        ]);
    }
}
