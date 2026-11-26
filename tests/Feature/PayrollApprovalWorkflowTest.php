<?php

namespace Tests\Feature;

use App\Enums\FinancialCashDirection;
use App\Enums\PaymentItemCategory;
use App\Enums\PaymentStatus;
use App\Enums\Payroll\FinancialApprovalItemStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\FinancialTreasuryService;
use App\Services\Payroll\FinancialApprovalService;
use App\Services\Payroll\PayrollApprovalScopeService;
use App\Services\Payroll\PayrollDisbursementService;
use App\Services\Payroll\PayrollSheetService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private Company $companyC;

    private User $superAdmin;

    private User $submitter;

    private User $groupReviewer;

    private User $bcReviewer;

    private User $layer1Manager;

    private Department $adminDept;

    private Department $mainDept;

    private Department $subDeptA;

    private Department $subDeptBC;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/payroll_approval_workflow.sqlite';

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

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        foreach ([
            'submit_financial_request',
            'approve_financial_request',
            'view_financial_request',
            'update_treasury_transaction',
            'disburse_payroll_salary',
            'record_service_receipt',
            'confirm_service_receipt',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->companyA = Company::create(['trade_name' => 'Company A', 'legal_name' => 'Company A LLC', 'status' => 'active']);
        $this->companyB = Company::create(['trade_name' => 'Company B', 'legal_name' => 'Company B LLC', 'status' => 'active']);
        $this->companyC = Company::create(['trade_name' => 'Company C', 'legal_name' => 'Company C LLC', 'status' => 'active']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);
        $this->actingAs($this->superAdmin);

        // Layer 1 Manager (active Saudi employee)
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

        // Department Hierarchy: Layer 1 (adminDept) -> Layer 2 (mainDept) -> Layer 3 (subDeptA, subDeptBC)
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
        $this->subDeptBC = Department::create([
            'name' => 'Sub Dept BC',
            'parent_id' => $this->mainDept->id,
            'is_global' => true,
            'status' => 'active',
        ]);
        $this->subDeptBC->companies()->sync([$this->companyB->id, $this->companyC->id]);

        // Submitter (authorized for Company A via company_id and B, C via subDeptBC)
        $this->submitter = User::factory()->create([
            'name' => 'Payroll Submitter',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->submitter->givePermissionTo(['submit_financial_request', 'view_financial_request']);
        Employee::create([
            'user_id' => $this->submitter->id,
            'company_id' => $this->companyA->id,
            'department_id' => $this->subDeptBC->id,
            'basic_salary' => 5000,
            'status' => 'active',
        ]);

        // Treasury Reviewer in Layer 1 (covers A, B, C)
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

        // Treasury Reviewer in subDeptBC (covers B, C only; primary company A must NOT leak!)
        $this->bcReviewer = User::factory()->create([
            'name' => 'BC Treasury Reviewer',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);
        $this->bcReviewer->givePermissionTo(['update_treasury_transaction', 'view_financial_request']);
        Employee::create([
            'user_id' => $this->bcReviewer->id,
            'department_id' => $this->subDeptBC->id,
            'company_id' => $this->companyA->id,
            'basic_salary' => 6000,
            'status' => 'active',
        ]);
    }

    public function test_department_subtree_scope_and_partial_coverage_blocking(): void
    {
        $groupCovered = PayrollApprovalScopeService::getReviewerCoveredCompanyIds($this->groupReviewer);
        sort($groupCovered);
        $expectedAll = [$this->companyA->id, $this->companyB->id, $this->companyC->id];
        sort($expectedAll);
        $this->assertSame($expectedAll, $groupCovered);

        // BC reviewer covers ONLY B and C (never A from employee.company_id or sibling subDeptA).
        $bcCovered = PayrollApprovalScopeService::getReviewerCoveredCompanyIds($this->bcReviewer);
        sort($bcCovered);
        $expectedBC = [$this->companyB->id, $this->companyC->id];
        sort($expectedBC);
        $this->assertSame($expectedBC, $bcCovered);
    }

    public function test_full_payroll_approval_workflow_disburses_cash_and_bank_immediately_only_after_manager(): void
    {
        $this->fundTreasury($this->companyA, 20000);
        $this->fundTreasury($this->companyB, 20000);

        $recA = $this->createPayrollRecord($this->companyA, 3000);
        $recB = $this->createPayrollRecord($this->companyB, 4000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
            ['payroll_record_id' => $recB->id, 'amount' => 4000, 'payment_method' => 'bank_transfer'],
        ], $this->submitter, '2026-11');

        $this->assertSame(FinancialApprovalStatus::PENDING_TREASURY, $request->status);
        $this->assertSame(2, $request->employees_count);
        $this->assertEquals(7000.00, (float) $request->total_amount);

        // Nothing deducted yet.
        $this->assertSame(0, PayrollPayment::count());
        $this->assertSame(PaymentStatus::UNPAID, $recA->fresh()->payment_status);
        $this->assertSame(PaymentStatus::UNPAID, $recB->fresh()->payment_status);

        // BC reviewer cannot view or approve a request that includes Company A.
        $this->assertFalse(PayrollApprovalScopeService::canViewRequest($this->bcReviewer, $request));
        try {
            FinancialApprovalService::approveByTreasury($request, $this->bcReviewer);
            $this->fail('Expected AuthorizationException for partial coverage reviewer.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Group reviewer approves and claims the request -> routed to Layer 1 manager, still no disbursement.
        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);
        $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $reviewed->status);
        $this->assertSame($this->groupReviewer->id, $reviewed->treasury_reviewer_id);
        $this->assertSame($this->layer1Manager->id, $reviewed->manager_user_id);
        $this->assertSame(0, PayrollPayment::count());

        // Submitter or reviewer cannot approve as manager.
        try {
            FinancialApprovalService::approveByManager($reviewed, $this->groupReviewer);
            $this->fail('Expected AuthorizationException for non-manager.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Layer 1 manager approves -> BOTH cash and bank_transfer disburse immediately in one transaction.
        $executed = FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);
        $this->assertSame(FinancialApprovalStatus::EXECUTED, $executed->status);
        $this->assertSame(2, PayrollPayment::count());
        $this->assertSame(PaymentStatus::PAID, $recA->fresh()->payment_status);
        $this->assertSame(PaymentStatus::PAID, $recB->fresh()->payment_status);
        $this->assertEquals(17000.0, FinancialTreasuryService::balance($this->companyA->id));
        $this->assertEquals(16000.0, FinancialTreasuryService::balance($this->companyB->id));

        foreach ($executed->items as $item) {
            $this->assertSame(FinancialApprovalItemStatus::EXECUTED, $item->status);
            $this->assertNotNull($item->payroll_payment_id);
        }
    }

    public function test_all_or_nothing_rollback_when_second_company_lacks_treasury_balance(): void
    {
        $this->fundTreasury($this->companyA, 10000);
        // Company B has only 500, while item B requests 4000.
        $this->fundTreasury($this->companyB, 500);

        $recA = $this->createPayrollRecord($this->companyA, 3000);
        $recB = $this->createPayrollRecord($this->companyB, 4000);

        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
            ['payroll_record_id' => $recB->id, 'amount' => 4000, 'payment_method' => 'bank_transfer'],
        ], $this->submitter, '2026-11');

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);

        try {
            FinancialApprovalService::approveByManager($reviewed, $this->layer1Manager);
            $this->fail('Expected ValidationException due to insufficient balance in Company B.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        // Entire transaction rolled back: Company A was NOT deducted, recA is NOT paid, request stays PENDING_MANAGER.
        $this->assertEquals(10000.0, FinancialTreasuryService::balance($this->companyA->id));
        $this->assertEquals(500.0, FinancialTreasuryService::balance($this->companyB->id));
        $this->assertSame(0, PayrollPayment::count());
        $this->assertSame(PaymentStatus::UNPAID, $recA->fresh()->payment_status);

        $freshRequest = $reviewed->fresh();
        $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $freshRequest->status);
        $this->assertNotNull($freshRequest->last_error);
        $this->assertTrue($freshRequest->logs()->where('action', 'execution_failed')->exists());

        // Fund Company B and retry -> succeeds cleanly!
        $this->fundTreasury($this->companyB, 5000);
        $retried = FinancialApprovalService::approveByManager($freshRequest, $this->layer1Manager);
        $this->assertSame(FinancialApprovalStatus::EXECUTED, $retried->status);
        $this->assertSame(2, PayrollPayment::count());
        $this->assertEquals(7000.0, FinancialTreasuryService::balance($this->companyA->id));
        $this->assertEquals(1500.0, FinancialTreasuryService::balance($this->companyB->id));
    }

    public function test_overlapping_requests_rejection_and_direct_disbursement_bypass_blocking(): void
    {
        $this->fundTreasury($this->companyA, 10000);
        $recA = $this->createPayrollRecord($this->companyA, 3000);

        // Direct PayrollDisbursementService::disburse() without approved request must fail.
        try {
            PayrollDisbursementService::disburse($recA, [
                'request_key' => PayrollDisbursementService::newRequestKey(),
                'amount' => 3000,
                'payment_method' => 'cash',
            ], $this->superAdmin);
            $this->fail('Expected ValidationException blocking direct payroll disbursement.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval_request', $e->errors());
        }

        // Submit request reserves the remaining 3000.
        $request = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitter, '2026-11');

        // Overlapping submission for the same balance is rejected.
        try {
            FinancialApprovalService::submitPayrollBatch([
                ['payroll_record_id' => $recA->id, 'amount' => 1000, 'payment_method' => 'cash'],
            ], $this->submitter, '2026-11');
            $this->fail('Expected ValidationException for overlapping reservation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Returning the request frees the reservation for resubmission.
        $returned = FinancialApprovalService::returnRequest($request, $this->groupReviewer, 'Needs adjustment');
        $this->assertSame(FinancialApprovalStatus::RETURNED, $returned->status);

        $resubmitted = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $recA->id, 'amount' => 3000, 'payment_method' => 'cash'],
        ], $this->submitter, '2026-11');
        $this->assertSame(FinancialApprovalStatus::PENDING_TREASURY, $resubmitted->status);
    }

    public function test_same_person_can_review_and_approve_with_explicit_two_step_logging_and_collection_unchanged(): void
    {
        $this->fundTreasury($this->companyA, 15000);

        // Give groupReviewer both submit and approve permissions, company A access as submitter, and make them Layer 1 manager too.
        $this->groupReviewer->givePermissionTo(['submit_financial_request', 'approve_financial_request']);
        $this->groupReviewer->employee->update(['company_id' => $this->companyA->id]);
        $this->adminDept->update(['manager_id' => $this->groupReviewer->employee->id]);

        $recA = $this->createPayrollRecord($this->companyA, 2500);

        $this->actingAs($this->groupReviewer);
        $sheetResult = PayrollSheetService::saveSheetDetailed(
            employeeRows: [[
                'employee_id' => $recA->employee_id,
                'company_id' => $this->companyA->id,
                'basic_salary' => 2500,
                'additional_amount' => 0,
                'deduction_amount' => 0,
                'net_amount' => 2500,
                'paid_amount' => 0,
                'remaining_amount' => 2500,
            ]],
            checkedEmployees: [$recA->employee_id => true],
            notes: [],
            operationType: 'payroll',
            payrollMonth: '2026-11',
            attemptKey: 'sheet:same-person-test',
        );

        /** @var FinancialApprovalRequest $request */
        $request = $sheetResult['approval_request'];
        $this->assertSame(FinancialApprovalStatus::PENDING_TREASURY, $request->status);
        $this->assertSame(0, PayrollPayment::count());

        $reviewed = FinancialApprovalService::approveByTreasury($request, $this->groupReviewer);
        $this->assertSame(FinancialApprovalStatus::PENDING_MANAGER, $reviewed->status);

        $executed = FinancialApprovalService::approveByManager($reviewed, $this->groupReviewer);
        $this->assertSame(FinancialApprovalStatus::EXECUTED, $executed->status);
        $this->assertTrue($executed->logs()->where('action', 'treasury_approved')->exists());
        $this->assertTrue($executed->logs()->where('action', 'manager_approved')->exists());

        // Collection sheet still executes directly via FinancialReceiptService without FinancialApprovalRequest.
        $this->superAdmin->givePermissionTo(['record_service_receipt', 'confirm_service_receipt']);
        $this->actingAs($this->superAdmin);
        $colEmp = $this->createEmployee($this->companyA, UserType::FOREIGN_EMPLOYEE);
        $colResult = PayrollSheetService::saveSheetDetailed(
            employeeRows: [[
                'employee_id' => $colEmp->id,
                'company_id' => $this->companyA->id,
                'basic_salary' => 1000,
                'additional_amount' => 0,
                'deduction_amount' => 0,
                'net_amount' => 1000,
                'paid_amount' => 0,
                'remaining_amount' => 1000,
            ]],
            checkedEmployees: [$colEmp->id => true],
            notes: [],
            operationType: 'collection',
            payrollMonth: '2026-11',
            attemptKey: 'sheet:collection-unchanged',
            collectorEmployeeId: $colEmp->id,
        );
        $this->assertSame([], $colResult['save_failed']);
        $this->assertSame([], $colResult['failed']);
        $this->assertSame(1, count($colResult['executed']) + count($colResult['pending']));
        $this->assertArrayNotHasKey('approval_request', $colResult);
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

    private function createEmployee(Company $company, UserType $userType = UserType::SAUDI_EMPLOYEE): Employee
    {
        $user = User::factory()->create([
            'user_type' => $userType,
            'status' => 'active',
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'basic_salary' => 3000,
            'daily_working_hours' => 8,
            'status' => 'active',
        ]);
    }

    private function createPayrollRecord(Company $company, float $amount): PayrollRecord
    {
        $employee = $this->createEmployee($company);

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
