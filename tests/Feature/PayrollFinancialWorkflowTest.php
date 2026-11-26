<?php

namespace Tests\Feature;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialPaymentMethod;
use App\Enums\PaymentItemCategory;
use App\Enums\PaymentStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\Owner;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\CashHandoverService;
use App\Services\FinancialReceiptService;
use App\Services\FinancialTreasuryService;
use App\Services\InvoiceFinancialService;
use App\Services\Payroll\FinancialApprovalService;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\PayrollDisbursementService;
use App\Services\Payroll\PayrollRecordFinancialService;
use App\Services\Payroll\PayrollSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollFinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Employee $employee;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/payroll_financial_workflow.sqlite';

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
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'trade_name' => 'Payroll Co',
            'legal_name' => 'Payroll Co LLC',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'user_type' => 'owner',
            'status' => 'active',
        ]);

        Owner::create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'basic_salary' => 3000,
            'daily_work_hours' => 8,
            'status' => 'active',
        ]);

        foreach ([
            'record_service_receipt',
            'confirm_service_receipt',
            'request_cash_handover',
            'confirm_cash_handover',
            'reverse_financial_transaction',
            'disburse_payroll_salary',
            'submit_financial_request',
            'update_treasury_transaction',
            'approve_financial_request',
            'view_financial_request',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->admin->givePermissionTo([
            'record_service_receipt',
            'confirm_service_receipt',
            'request_cash_handover',
            'confirm_cash_handover',
            'reverse_financial_transaction',
            'disburse_payroll_salary',
            'submit_financial_request',
            'update_treasury_transaction',
            'approve_financial_request',
            'view_financial_request',
        ]);

        $superAdmin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
        $this->actingAs($superAdmin);

        $rootDept = Department::create([
            'name' => 'Finance Root',
            'is_global' => true,
            'manager_id' => $this->employee->id,
            'status' => 'active',
        ]);
        $mainDept = Department::create([
            'name' => 'General Finance Dept',
            'parent_id' => $rootDept->id,
            'is_global' => true,
            'status' => 'active',
        ]);
        $subDept = Department::create([
            'name' => 'Company Sub Dept',
            'parent_id' => $mainDept->id,
            'company_id' => $this->company->id,
            'is_global' => false,
            'status' => 'active',
        ]);
        $this->employee->update(['department_id' => $subDept->id]);

        $this->actingAs($this->admin);
    }

    public function test_non_cash_receipt_confirmation_posts_once_to_unified_treasury_and_reverses_cleanly(): void
    {
        $customer = Customer::create(['name' => 'Bank Client', 'status' => 'active']);

        $invoice = InvoiceFinancialService::createIndependent([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-11-23',
            'total_amount' => 1000,
        ], [], $this->admin);

        $receipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 1000,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
            'payer_name' => 'Bank Client',
        ], $this->admin);

        $this->assertSame(0.0, FinancialTreasuryService::balance($this->company->id));

        $confirmed = FinancialReceiptService::confirm($receipt, [
            'destination_type' => 'company_bank',
            'destination_reference' => 'IBAN-999',
            'reference_number' => 'WIRE-999',
            'proof_path' => 'proofs/wire.pdf',
        ], $this->admin);

        $this->assertNotEmpty($confirmed->metadata['treasury_transaction_id']);
        $this->assertSame(1000.0, FinancialTreasuryService::balance($this->company->id));

        $summary = InvoiceFinancialService::summary($invoice->refresh());
        $this->assertSame(0.0, $summary['custody']);
        $this->assertSame(1000.0, $summary['treasury_received']);

        FinancialReceiptService::reverse($confirmed, 'Returned wire', $this->admin);
        $this->assertSame(0.0, FinancialTreasuryService::balance($this->company->id));
    }

    public function test_collection_uses_receipt_and_handover_without_parallel_payroll_payment(): void
    {
        $collection = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'net_amount' => 800,
        ]);

        // Legacy disburseSalary must reject collection records
        try {
            PayrollCalculationService::disburseSalary($collection, 800);
            $this->fail('Expected ValidationException when calling disburseSalary on collection.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('operation_type', $e->errors());
        }

        $receipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, $collection->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 500,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
        ], $this->admin);

        $collection->refresh();
        $this->assertTrue($collection->isManaged());
        $this->assertSame(0, PayrollPayment::where('payroll_record_id', $collection->id)->count());
        $this->assertSame(500.0, (float) $collection->paid_amount);
        $this->assertSame(300.0, (float) $collection->remaining_amount);

        $handover = CashHandoverService::request([$receipt->id => 500], [
            'request_key' => CashHandoverService::newRequestKey(),
        ], $this->admin);
        $sig = CashHandoverService::signatureCodeForDelivery($handover, $this->admin);
        $confirmedHandover = CashHandoverService::confirm($handover, [
            'confirmation_key' => CashHandoverService::newConfirmationKey(),
            'signature_code' => $sig,
            'confirmed_amounts' => [$receipt->id => 500],
        ], $this->admin);

        $this->assertSame(PaymentItemCategory::SALARY_COLLECTION, $confirmedHandover->treasuryTransaction->paymentCollectionItem->main_item);
        $this->assertSame(500.0, FinancialTreasuryService::balance($this->company->id));
    }

    public function test_payroll_disbursement_partial_idempotency_bank_transfer_and_reversal(): void
    {
        // Seed treasury balance
        FinancialTreasuryService::post(
            companyId: $this->company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: 5000,
            description: 'Opening treasury balance',
        );

        $payroll = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'basic_salary' => 3000,
            'net_amount' => 3000,
        ]);

        // Direct disbursement is blocked; must go through approval workflow
        try {
            PayrollDisbursementService::disburse($payroll, [
                'request_key' => PayrollDisbursementService::newRequestKey(),
                'amount' => 1000,
                'payment_method' => 'cash',
            ], $this->admin);
            $this->fail('Expected ValidationException blocking direct disbursement.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval_request', $e->errors());
        }

        // Cheque is rejected in approval workflow
        try {
            FinancialApprovalService::submitPayrollBatch([
                ['payroll_record_id' => $payroll->id, 'amount' => 1000, 'payment_method' => 'cheque'],
            ], $this->admin, '2026-11');
            $this->fail('Expected ValidationException for cheque.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment_method', $e->errors());
        }

        $key1 = 'attempt:cash-1000';
        $req1 = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $payroll->id, 'amount' => 1000, 'payment_method' => FinancialPaymentMethod::CASH->value],
        ], $this->admin, '2026-11', attemptKey: $key1);
        $reviewed1 = FinancialApprovalService::approveByTreasury($req1, $this->admin);
        $executed1 = FinancialApprovalService::approveByManager($reviewed1, $this->admin);

        $this->assertSame(FinancialApprovalStatus::EXECUTED, $executed1->status);
        $this->assertSame(4000.0, FinancialTreasuryService::balance($this->company->id));

        // Retry with same attempt key must not create a new request or disburse again
        $retryReq = FinancialApprovalService::submitPayrollBatch([
            ['payroll_record_id' => $payroll->id, 'amount' => 1000, 'payment_method' => FinancialPaymentMethod::CASH->value],
        ], $this->admin, '2026-11', attemptKey: $key1);
        $this->assertSame($req1->id, $retryReq->id);
        FinancialApprovalService::approveByManager($executed1, $this->admin);
        $this->assertSame(4000.0, FinancialTreasuryService::balance($this->company->id));

        // Bank transfer via approval workflow disburses immediately upon manager approval
        $req2 = FinancialApprovalService::submitPayrollBatch([[
            'payroll_record_id' => $payroll->fresh()->id,
            'amount' => 2000,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
            'destination_reference' => 'EMP-IBAN-1',
            'reference_number' => 'SAL-WIRE-1',
            'proof_path' => 'proofs/sal.pdf',
        ]], $this->admin, '2026-11');
        $reviewed2 = FinancialApprovalService::approveByTreasury($req2, $this->admin);
        $executed2 = FinancialApprovalService::approveByManager($reviewed2, $this->admin);

        $bankPayment = PayrollPayment::findOrFail($executed2->items->first()->payroll_payment_id);
        $this->assertNotNull($bankPayment->treasury_transaction_id);
        $this->assertSame(3000.0, (float) $payroll->refresh()->paid_amount);
        $this->assertSame(PaymentStatus::PAID, $payroll->payment_status);
        $this->assertSame(2000.0, FinancialTreasuryService::balance($this->company->id));

        // Reverse cash payment preserving original row
        $cashPayment = PayrollPayment::findOrFail($executed1->items->first()->payroll_payment_id);
        PayrollDisbursementService::reverse($cashPayment, 'Correction', $this->admin);
        $payroll->refresh();
        $this->assertSame(2000.0, (float) $payroll->paid_amount);
        $this->assertSame(1000.0, (float) $payroll->remaining_amount);
        $this->assertSame(3000.0, FinancialTreasuryService::balance($this->company->id));
    }

    public function test_coordinated_salary_deduction_settlement_and_reversal(): void
    {
        $collection = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'net_amount' => 500,
        ]);

        $payroll = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'basic_salary' => 3000,
            'net_amount' => 3000,
        ]);

        // Pay 200 independently, deduct 300 from salary
        FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, $collection->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 200,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
        ], $this->admin);

        $dedKey = 'ded-req-1';
        PayrollRecordFinancialService::reserveCollectionDeduction($collection->refresh(), $payroll, 300, $dedKey, $this->admin);

        // Cannot collect more than unreserved available_to_settle (500 - 200 - 300 = 0)
        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, $collection->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 100,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $this->employee->id,
            ], $this->admin);
            $this->fail('Expected ValidationException when collecting reserved amount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        PayrollRecordFinancialService::confirmCollectionDeduction($collection->refresh(), $payroll->refresh(), $dedKey, $this->admin);

        $collection->refresh();
        $payroll->refresh();

        $this->assertSame(500.0, (float) $collection->paid_amount);
        $this->assertSame(0.0, (float) $collection->remaining_amount);
        $this->assertSame(PaymentStatus::PAID, $collection->payment_status);

        $paySummary = PayrollRecordFinancialService::payrollSummary($payroll);
        $this->assertSame(2700.0, $paySummary['effective_net']);
        $this->assertSame(2700.0, $paySummary['remaining']);

        // Sheet data must reflect effective net after deduction
        $sheetRow = PayrollCalculationService::resolveEmployeePayrollData($this->employee, $payroll, '2026-11');
        $this->assertSame(2700.0, $sheetRow['net_amount']);
        $this->assertSame(2700.0, $sheetRow['remaining_amount']);

        // Reverse deduction cleanly without fake treasury movement
        PayrollRecordFinancialService::reverseCollectionDeduction($collection, $payroll, $dedKey, 'Cancelled deduction', $this->admin);
        $this->assertSame(300.0, (float) $collection->refresh()->remaining_amount);
        $this->assertSame(3000.0, PayrollRecordFinancialService::payrollSummary($payroll->refresh())['effective_net']);
    }

    public function test_save_sheet_detailed_isolates_failures_and_is_retry_safe(): void
    {
        FinancialTreasuryService::post(
            companyId: $this->company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: 2500,
            description: 'Partial treasury',
        );

        $emp2User = User::factory()->create([
            'user_type' => 'saudi_employee',
            'status' => 'active',
        ]);
        $emp2 = Employee::create([
            'user_id' => $emp2User->id,
            'company_id' => $this->company->id,
            'basic_salary' => 2000,
            'daily_work_hours' => 8,
            'status' => 'active',
        ]);

        $rows = [
            ['employee_id' => $this->employee->id, 'basic_salary' => 2000, 'additional_amount' => 0, 'deduction_amount' => 0, 'net_amount' => 2000],
            ['employee_id' => $emp2->id, 'basic_salary' => 2000, 'additional_amount' => 0, 'deduction_amount' => 0, 'net_amount' => 2000],
        ];
        $checked = [
            $this->employee->id => true,
            $emp2->id => true,
        ];

        $attemptKey = 'sheet:test-retry-1';
        $res1 = PayrollSheetService::saveSheetDetailed($rows, $checked, [], 'payroll', '2026-11', $attemptKey);

        $this->assertSame([], $res1['save_failed']);
        $this->assertSame(2, $res1['saved_count']);
        $this->assertNotEmpty($res1['approval_request']);
        $this->assertCount(2, $res1['pending']);
        $this->assertSame(2500.0, FinancialTreasuryService::balance($this->company->id));

        // Retry with same attempt key returns the same pending approval request without duplicating
        $res2 = PayrollSheetService::saveSheetDetailed($rows, $checked, [], 'payroll', '2026-11', $attemptKey);
        $this->assertSame($res1['approval_request']->id, $res2['approval_request']->id);
        $this->assertSame(2500.0, FinancialTreasuryService::balance($this->company->id));
    }

    public function test_multiple_deductions_covering_full_salary_and_empty_record_snapshot_update(): void
    {
        $payroll = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'basic_salary' => 3000,
            'net_amount' => 3000,
        ]);

        PayrollRecordFinancialService::activate($payroll, $this->admin);

        // R3: Updating empty managed record updates net_amount_snapshot
        $payroll->basic_salary = 1000;
        $payroll->save();
        $payroll->refresh();
        $this->assertSame(1000.0, (float) $payroll->net_amount);
        $this->assertSame(1000.0, PayrollRecordFinancialService::payrollSummary($payroll)['gross_net']);

        // R1: Two separate collection records of 500 + 500 = 1000 covering full salary
        $col1 = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-10-01',
            'record_date' => '2026-11-23',
            'net_amount' => 500,
        ]);
        $col2 = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-23',
            'net_amount' => 500,
        ]);

        PayrollRecordFinancialService::reserveCollectionDeduction($col1, $payroll, 500, 'ded-batch-1', $this->admin);
        PayrollRecordFinancialService::reserveCollectionDeduction($col2, $payroll->refresh(), 500, 'ded-batch-2', $this->admin);

        // Confirming both together via saveSheetDetailed settles zero-cash payroll cleanly
        $rows = [
            ['employee_id' => $this->employee->id, 'basic_salary' => 1000, 'additional_amount' => 0, 'deduction_amount' => 0, 'net_amount' => 1000],
        ];
        $res = PayrollSheetService::saveSheetDetailed($rows, [$this->employee->id => true], [], 'payroll', '2026-11', 'sheet:zero-cash-1');

        $this->assertCount(1, $res['executed']);
        $this->assertSame(PaymentStatus::PAID, $col1->refresh()->payment_status);
        $this->assertSame(PaymentStatus::PAID, $col2->refresh()->payment_status);
        $this->assertSame(PaymentStatus::PAID, $payroll->refresh()->payment_status);
        $this->assertSame(0.0, (float) $payroll->paid_amount);
    }
}
