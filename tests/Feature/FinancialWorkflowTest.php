<?php

namespace Tests\Feature;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Filament\Resources\ServiceTransactions\Pages\CreateServiceTransaction;
use App\Filament\Resources\ServiceTransactions\Pages\EditServiceTransaction;
use App\Filament\Resources\ServiceTransactions\Pages\FinancialIndicators;
use App\Models\Branch;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\UnifiedSuspenseAmount;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\CashHandoverService;
use App\Services\FinancialDisbursementService;
use App\Services\FinancialReceiptService;
use App\Services\FinancialSummaryService;
use App\Services\FinancialSurplusRefundService;
use App\Services\FinancialTreasuryService;
use App\Services\OperationsSettlementService;
use App\Services\ServiceFinancialIndicatorsService;
use App\Services\ServiceReceiptCreationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\MySqlConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Employee $employee;

    private Service $service;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/financial_workflow.sqlite';

        if (! is_dir(dirname($database))) {
            mkdir(dirname($database), 0777, true);
        }

        if (! file_exists($database)) {
            touch($database);
        }

        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $database;
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE='.$database);

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Company::create(['trade_name' => 'Test Company']);
        Branch::create(['company_id' => 1, 'name' => 'Main', 'branch_code' => 'B1']);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'super_admin',
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->admin->id,
            'company_id' => 1,
            'branch_id' => 1,
        ]);

        $this->service = Service::create([
            'company_id' => 1,
            'name' => 'Service',
            'price' => 1000,
            'default_gov_fee' => 1000,
        ]);

        Permission::findOrCreate('confirm_cash_handover', 'web');
        Permission::findOrCreate('request_cash_handover', 'web');

        $this->actingAs($this->admin);
    }

    public function test_receipts_and_custody_are_isolated_per_transaction(): void
    {
        $first = $this->makeTransaction(1000);
        $second = $this->makeTransaction(1000);

        $firstReceipt = $this->confirmedCashReceipt($first, 100);
        $secondReceipt = $this->confirmedCashReceipt($second, 100);

        $this->confirmHandover([$firstReceipt->id => 100], 'hnd-a');
        $this->confirmHandover([$secondReceipt->id => 50], 'hnd-b');

        $firstSummary = FinancialSummaryService::for($first->fresh());
        $secondSummary = FinancialSummaryService::for($second->fresh());

        $this->assertSame(0.0, $firstSummary['custody']);
        $this->assertSame(100.0, $firstSummary['treasury_received']);
        $this->assertSame(50.0, $secondSummary['custody']);
        $this->assertSame(50.0, $secondSummary['treasury_received']);
        $this->assertSame(150.0, FinancialTreasuryService::balance(1));
    }

    public function test_duplicate_handover_confirmation_is_idempotent(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 200);

        $handover = $this->confirmHandover([$receipt->id => 200], 'hnd-idem');

        $again = CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-confirm:hnd-idem',
            'confirmed_amounts' => $handover->items()->pluck('requested_amount', 'id')->map(fn ($amount) => (float) $amount)->all(),
        ]);

        $this->assertSame($handover->id, $again->id);
        $this->assertSame(1, TreasuryTransaction::where('transaction_type', 'incoming')->count());
    }

    public function test_handover_cannot_exceed_available_custody(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 100);

        CashHandoverService::request([$receipt->id => 100], ['request_key' => 'hnd-first']);

        $this->expectException(ValidationException::class);

        CashHandoverService::request([$receipt->id => 10], ['request_key' => 'hnd-second']);
    }

    public function test_distributed_handover_creates_one_treasury_entry(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receiptA = $this->confirmedCashReceipt($transaction, 400);
        $receiptB = $this->confirmedCashReceipt($transaction, 600);

        $handover = CashHandoverService::request([
            $receiptA->id => 200,
            $receiptB->id => 200,
        ], ['request_key' => 'hnd-distributed']);

        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-distributed-confirm',
            'confirmed_amounts' => $handover->items()->pluck('requested_amount', 'id')->map(fn ($amount) => (float) $amount)->all(),
        ]);

        $this->assertSame(1, TreasuryTransaction::count());
        $this->assertSame(400.0, (float) TreasuryTransaction::first()->amount);
        $this->assertSame(600.0, FinancialSummaryService::for($transaction->fresh())['custody']);

        $indicators = ServiceFinancialIndicatorsService::for(ServiceTransaction::query()->whereKey($transaction->id));

        $this->assertSame(400.0, $indicators['company'][1]['amounts']['treasury_received']);
        $this->assertSame(400.0, $indicators['deliverer'][$this->employee->id]['amounts']['treasury_received']);
        $this->assertSame(600.0, $indicators['collector'][$this->employee->id]['amounts']['custody']);
    }

    public function test_treasury_uses_the_same_running_balance_for_direct_and_workflow_inserts(): void
    {
        $receipt = $this->confirmedCashReceipt($this->makeTransaction(1000), 100);
        $handover = $this->confirmHandover([$receipt->id => 100], 'shared-balance');
        $incoming = $handover->treasuryTransaction;

        $manual = TreasuryTransaction::create([
            'company_id' => $incoming->company_id,
            'transaction_type' => 'outgoing',
            'transaction_date' => now(),
            'payment_collection_item_id' => $incoming->payment_collection_item_id,
            'amount' => 100,
            'balance_after' => 999,
        ]);

        $this->assertSame(0.0, (float) $manual->balance_after);
        $this->assertSame(0.0, FinancialTreasuryService::balance(1));
    }

    public function test_handover_request_notifies_the_receiver_without_service_update_permission(): void
    {
        $receiver = User::create([
            'name' => 'Treasury Receiver',
            'email' => 'receiver@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
            'status' => 'active',
        ]);
        Employee::create(['user_id' => $receiver->id, 'company_id' => 1, 'branch_id' => 1]);
        $receiver->givePermissionTo('confirm_cash_handover');
        $this->assertFalse($receiver->can('update_service_transaction'));

        Notification::fake();
        $receipt = $this->confirmedCashReceipt($this->makeTransaction(1000), 100);
        $options = ['request_key' => 'hnd:notification'];
        $handover = CashHandoverService::request([$receipt->id => 100], $options, $this->admin);
        CashHandoverService::request([$receipt->id => 100], $options, $this->admin);

        $notifications = Notification::sent($receiver, BaseSystemNotification::class,
            fn ($notification) => $notification->eventKey === 'ServiceFinancialHandoverRequested');
        $this->assertCount(1, $notifications);
        $this->assertTrue($notifications->first()->shouldSend($receiver->fresh(), 'database'));
        $this->assertSame($handover->id, $notifications->first()->recordId);
    }

    public function test_under_confirmation_records_difference_and_over_confirmation_is_rejected(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 400);

        $handover = CashHandoverService::request([$receipt->id => 400], ['request_key' => 'hnd-under']);
        $item = $handover->items()->firstOrFail();

        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-under-confirm',
            'confirmed_amounts' => [$item->id => 350],
        ]);

        $confirmed = $handover->fresh();

        $this->assertSame(350.0, (float) $confirmed->total_confirmed_amount);
        $this->assertSame(-50.0, (float) $confirmed->difference_amount);
        $this->assertSame(350.0, FinancialSummaryService::for($transaction->fresh())['treasury_received']);
        $this->assertSame(50.0, FinancialSummaryService::for($transaction->fresh())['custody']);

        $this->expectException(ValidationException::class);

        $secondHandover = CashHandoverService::request([$receipt->id => 50], ['request_key' => 'hnd-over']);
        $secondItem = $secondHandover->items()->firstOrFail();

        CashHandoverService::confirm($secondHandover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($secondHandover, $this->admin),
            'confirmation_key' => 'hnd-over-confirm',
            'confirmed_amounts' => [$secondItem->id => 60],
        ]);
    }

    public function test_extra_counted_cash_requires_note_and_creates_suspense(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 200);

        $handover = CashHandoverService::request([$receipt->id => 200], ['request_key' => 'hnd-extra']);
        $amounts = $handover->items()->pluck('requested_amount', 'id')->map(fn ($amount) => (float) $amount)->all();

        try {
            CashHandoverService::confirm($handover, [
                'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
                'confirmation_key' => 'hnd-extra-no-note',
                'confirmed_amounts' => $amounts,
                'extra_amount' => 50,
            ]);

            $this->fail('Extra cash without a note must be rejected.');
        } catch (ValidationException) {
            // Expected.
        }

        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-extra-note',
            'confirmed_amounts' => $amounts,
            'extra_amount' => 50,
            'extra_note' => 'Count report signed by collector',
        ]);

        $this->assertSame(250.0, (float) TreasuryTransaction::first()->amount);
        $this->assertSame(1, DB::table('unified_suspense_amounts')->count());
    }

    public function test_direct_disbursement_requires_funded_cash_and_respects_remaining_fee(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 400);
        $this->confirmHandover([$receipt->id => 200], 'hnd-fund');

        try {
            FinancialDisbursementService::record($transaction, [
                'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
                'amount' => 600,
                'reference_number' => 'GOV-1',
                'request_key' => 'dsb-unfunded',
            ]);

            $this->fail('An unfunded direct disbursement must be rejected.');
        } catch (ValidationException) {
            // Expected.
        }

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT,
            'amount' => 200,
            'reference_number' => 'GOV-2',
            'request_key' => 'dsb-funded',
        ]);

        $summary = FinancialSummaryService::for($transaction->fresh());

        $this->assertSame(200.0, $summary['fee_paid']);
        $this->assertSame(800.0, $summary['fee_remaining']);
        $this->assertSame(0.0, $summary['net_cash']);
    }

    public function test_two_approved_settlements_are_allowed_and_third_is_rejected(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 1000);
        $this->confirmHandover([$receipt->id => 1000], 'hnd-settle');

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
            'amount' => 400,
            'reference_number' => 'GOV-A',
            'request_key' => 'dsb-a',
        ]);

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
            'amount' => 600,
            'reference_number' => 'GOV-B',
            'request_key' => 'dsb-b',
        ]);

        $this->assertSame(1000.0, FinancialSummaryService::for($transaction->fresh())['fee_paid']);

        $this->expectException(ValidationException::class);

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
            'amount' => 1,
            'reference_number' => 'GOV-C',
            'request_key' => 'dsb-c',
        ]);
    }

    public function test_personal_claim_approval_and_partial_reimbursement(): void
    {
        $transaction = $this->makeTransaction(500);
        $receipt = $this->confirmedCashReceipt($transaction, 500);
        $this->confirmHandover([$receipt->id => 500], 'hnd-personal');

        $claim = FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::EMPLOYEE_PERSONAL,
            'amount' => 500,
            'reference_number' => 'GOV-PERSONAL',
            'paid_by_employee_id' => $this->employee->id,
            'receipt_proof' => 'proofs/personal.pdf',
            'request_key' => 'dsb-personal',
        ]);

        $this->assertSame(FinancialDisbursementStatus::PENDING_APPROVAL, $claim->status);
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['employee_due']);

        $approver = User::create([
            'name' => 'Finance Manager',
            'email' => 'finance@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'super_admin',
        ]);

        FinancialDisbursementService::approve($claim, $approver);

        $this->assertSame(500.0, FinancialSummaryService::for($transaction->fresh())['employee_due']);

        $firstPayment = FinancialDisbursementService::pay($claim, [
            'amount' => 200,
            'request_key' => 'rmb-1',
        ]);

        $this->assertSame(300.0, FinancialSummaryService::for($transaction->fresh())['employee_due']);
        $this->assertSame(FinancialDisbursementStatus::PARTIALLY_REIMBURSED, $claim->fresh()->status);

        FinancialDisbursementService::pay($claim, [
            'amount' => 300,
            'request_key' => 'rmb-2',
        ]);

        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['employee_due']);
        $this->assertSame(FinancialDisbursementStatus::FULLY_REIMBURSED, $claim->fresh()->status);
        $this->assertSame(0.0, FinancialTreasuryService::balance(1));

        try {
            FinancialDisbursementService::pay($claim, [
                'amount' => 1,
                'request_key' => 'rmb-3',
            ]);

            $this->fail('A claim cannot be reimbursed above its amount.');
        } catch (ValidationException) {
            // Expected.
        }

        $this->assertTrue($firstPayment->fresh()->treasury_transaction_id !== null);
    }

    public function test_request_key_and_confirmation_key_guards(): void
    {
        $transaction = $this->makeTransaction(1000);

        $first = FinancialReceiptService::record($transaction, [
            'amount' => 100,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-same',
        ], $this->admin);

        $again = FinancialReceiptService::record($transaction, [
            'amount' => 100,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-same',
        ], $this->admin);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, FinancialReceipt::count());

        $this->expectException(ValidationException::class);

        FinancialReceiptService::record($transaction, [
            'amount' => 50,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-same',
        ], $this->admin);
    }

    public function test_user_without_permission_or_company_scope_is_rejected(): void
    {
        $transaction = $this->makeTransaction(1000);

        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
        ]);

        $this->expectException(AuthorizationException::class);

        FinancialReceiptService::record($transaction, [
            'amount' => 100,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'payer_name' => 'Client',
            'request_key' => 'rcp-outsider',
        ], $outsider);
    }

    public function test_approved_reversal_restores_treasury_and_fee_numbers(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 1000);
        $handover = $this->confirmHandover([$receipt->id => 1000], 'hnd-reverse');

        $disbursement = FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
            'amount' => 600,
            'reference_number' => 'GOV-REV',
            'request_key' => 'dsb-rev',
        ]);

        $this->assertSame(400.0, FinancialTreasuryService::balance(1));

        FinancialDisbursementService::reverseDirect($disbursement, 'Wrong beneficiary');

        $this->assertSame(1000.0, FinancialTreasuryService::balance(1));
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['fee_paid']);

        CashHandoverService::reverse($handover, 'Cash returned to collector');

        $this->assertSame(0.0, FinancialTreasuryService::balance(1));
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['treasury_received']);
        $this->assertSame(1000.0, FinancialSummaryService::for($transaction->fresh())['custody']);
    }

    public function test_undefined_fee_rule_blocks_disbursement(): void
    {
        $this->service->update(['default_gov_fee' => null]);

        $transaction = $this->makeTransaction(null, explicitFee: false);
        $receipt = $this->confirmedCashReceipt($transaction, 500);
        $this->confirmHandover([$receipt->id => 500], 'hnd-no-fee');

        $this->expectException(ValidationException::class);

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT->value,
            'amount' => 100,
            'reference_number' => 'GOV-NO-FEE',
            'request_key' => 'dsb-no-fee',
        ]);
    }

    public function test_transaction_with_financial_records_cannot_be_deleted(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 300);

        $this->assertTrue($transaction->hasFinancialRecords());

        $this->expectException(ValidationException::class);
        $transaction->delete();
    }

    public function test_confirm_pending_receipt_from_service_and_transfer_validation(): void
    {
        $transaction = $this->makeTransaction(1000);

        $receipt = FinancialReceiptService::record($transaction, [
            'amount' => 400,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
            'payer_name' => 'Transfer Client',
            'request_key' => 'rcp:transfer-1',
        ], $this->admin);

        $this->assertSame(FinancialReceiptStatus::PENDING_CONFIRMATION, $receipt->status);
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['received']);
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['custody']);

        try {
            FinancialReceiptService::confirm($receipt, [], $this->admin);
            $this->fail('Expected non-cash destination validation failure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('destination_type', $e->errors());
        }

        $confirmed = FinancialReceiptService::confirm($receipt, [
            'destination_type' => 'company_account',
            'destination_reference' => 'AlRajhi-001',
            'reference_number' => 'TRF-9988',
            'proof_path' => 'receipts/test.png',
        ], $this->admin);

        $this->assertSame(FinancialReceiptStatus::CONFIRMED, $confirmed->status);
        $this->assertSame(400.0, FinancialSummaryService::for($transaction->fresh())['received']);
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['custody']);
        $this->assertSame(0.0, FinancialSummaryService::for($transaction->fresh())['treasury_received']);
    }

    public function test_transaction_form_uses_catalog_price_on_creation_and_keeps_saved_totals(): void
    {
        $create = new ReflectionMethod(CreateServiceTransaction::class, 'mutateFormDataBeforeCreate');
        $page = new CreateServiceTransaction;

        $internal = $create->invoke($page, [
            'scope_type' => 'internal',
            'service_id' => $this->service->id,
            'total_amount' => 1,
        ]);
        $external = $create->invoke($page, ['scope_type' => 'external', 'total_amount' => 150]);

        $this->assertEquals($this->service->price, $internal['total_amount']);
        $this->assertSame(150, $external['total_amount']);

        $transaction = $this->makeTransaction(1000);
        $edit = new ReflectionMethod(EditServiceTransaction::class, 'mutateFormDataBeforeSave');
        $transaction->update($edit->invoke(new EditServiceTransaction, ['total_amount' => 1, 'notes' => 'Edited notes']));

        $this->assertSame('1000.00', $transaction->fresh()->total_amount);
        $this->assertSame('Edited notes', $transaction->fresh()->notes);
    }

    public function test_cash_recorded_by_an_employee_counts_immediately_and_can_be_requested_for_handover(): void
    {
        $collector = User::create([
            'name' => 'Cash Collector',
            'email' => 'cash-collector@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'saudi_employee',
        ]);
        $employee = Employee::create(['user_id' => $collector->id, 'company_id' => 1, 'branch_id' => 1]);
        $collector->givePermissionTo([Permission::findOrCreate('record_service_receipt', 'web'), 'request_cash_handover']);
        $transaction = $this->makeTransaction(300);
        $transaction->update(['executed_by' => $employee->id]);
        $this->actingAs($collector);
        $this->assertFalse($collector->can('confirm_service_receipt'));
        $this->assertFalse($collector->can('confirm_cash_handover'));

        $outcome = ServiceReceiptCreationService::process($transaction, [
            'amount' => 350,
            'payment_method' => FinancialPaymentMethod::CASH,
            'request_key' => 'rcp:employee-create',
            'request_handover' => true,
            'handover_request_key' => 'hnd:employee-create',
        ]);

        $receipt = $outcome['receipt'];
        $handover = $outcome['handover'];
        $this->assertSame(FinancialReceiptStatus::CONFIRMED, $receipt->status);
        $this->assertEquals($collector->id, $receipt->confirmed_by);
        $this->assertEquals($employee->id, $receipt->collector_employee_id);
        $this->assertSame(FinancialHandoverStatus::SUBMITTED, $handover->status);
        $this->assertEquals($receipt->id, $handover->items()->sole()->financial_receipt_id);

        $data = [
            'amount' => 650,
            'payment_method' => FinancialPaymentMethod::CASH,
            'payer_name' => 'Test Client',
            'request_key' => 'rcp:employee-topup',
        ];
        $topUp = FinancialReceiptService::record($transaction, $data);
        $this->assertSame(FinancialReceiptStatus::CONFIRMED, $topUp->status);
        $this->assertSame($topUp->id, FinancialReceiptService::record($transaction, $data)->id);
        $this->assertSame(2, $transaction->financialReceipts()->count());

        $summary = FinancialSummaryService::for($transaction->fresh());
        $this->assertSame(0.0, $summary['client_remaining']);
        $this->assertSame(1000.0, $summary['custody']);
        $this->assertSame(0.0, $summary['treasury_received']);
        $this->assertSame(0, TreasuryTransaction::count());

        $transfer = FinancialReceiptService::record($transaction, array_replace($data, [
            'amount' => 10,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER,
            'request_key' => 'rcp:employee-transfer',
        ]));
        $this->assertSame(FinancialReceiptStatus::PENDING_CONFIRMATION, $transfer->status);

        $this->expectException(AuthorizationException::class);
        FinancialReceiptService::confirm($transfer);
    }

    public function test_handover_branch_scoping_enforcement(): void
    {
        $branch2 = Branch::create(['company_id' => 1, 'name' => 'Branch 2', 'branch_code' => 'B2']);

        $userRestricted = User::create([
            'name' => 'Branch 2 User',
            'email' => 'b2@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
        ]);
        Employee::create([
            'user_id' => $userRestricted->id,
            'company_id' => 1,
            'branch_id' => $branch2->id,
        ]);
        $userRestricted->givePermissionTo(['confirm_cash_handover', 'request_cash_handover']);

        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 200);

        $handover = CashHandoverService::request([$receipt->id => 200], [
            'request_key' => 'hnd:branch-scope',
        ], $this->admin);

        $this->expectException(AuthorizationException::class);
        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-conf:branch-scope',
            'confirmed_amounts' => [$handover->items()->first()->id => 200],
        ], $userRestricted);
    }

    public function test_handover_reversal_blocked_when_surplus_is_allocated(): void
    {
        $transaction = $this->makeTransaction(1000);
        $receipt = $this->confirmedCashReceipt($transaction, 500);

        $handover = CashHandoverService::request([$receipt->id => 500], [
            'request_key' => 'hnd:surplus-alloc',
        ], $this->admin);

        $confirmed = CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-conf:surplus-alloc',
            'confirmed_amounts' => [$handover->items()->first()->id => 500],
            'extra_amount' => 100,
            'extra_note' => 'Extra count surplus',
        ], $this->admin);

        $this->assertSame(600.0, FinancialTreasuryService::balance(1));
        $this->assertNotNull($confirmed->metadata['suspense_amount_id'] ?? null);

        $suspense = UnifiedSuspenseAmount::find($confirmed->metadata['suspense_amount_id']);
        $this->assertNotNull($suspense);
        $this->assertSame('pending', $suspense->status);

        $secondTx = $this->makeTransaction(1000);
        OperationsSettlementService::allocateSuspenseAmount($suspense, $secondTx, 'Allocated extra surplus', $this->admin->id);

        $this->assertSame('allocated', $suspense->fresh()->status);
        $allocated = $secondTx->financialReceipts()->firstOrFail();
        $summary = FinancialSummaryService::for($secondTx->fresh());
        $this->assertSame(FinancialPaymentMethod::CASH, $allocated->payment_method);
        $this->assertSame(0.0, $allocated->availableForHandover());
        $this->assertSame(100.0, $summary['received']);
        $this->assertSame(100.0, $summary['treasury_received']);
        $this->assertSame(0.0, $summary['custody']);
        $this->assertSame(1, TreasuryTransaction::count());

        try {
            CashHandoverService::reverse($confirmed, 'Trying to reverse handover with allocated surplus', $this->admin);
            $this->fail('A handover with an allocated surplus must stay confirmed.');
        } catch (ValidationException) {
            $this->assertSame(600.0, FinancialTreasuryService::balance(1));
        }

        OperationsSettlementService::reverseCashSurplusAllocation($secondTx, $allocated, 'Return to suspense');
        $this->assertSame('pending', $suspense->fresh()->status);
        $this->assertSame(0.0, FinancialSummaryService::for($secondTx->fresh())['received']);
        $this->assertSame(600.0, FinancialTreasuryService::balance(1));

        CashHandoverService::reverse($confirmed, 'Cash returned', $this->admin);
        $this->assertSame('cancelled', $suspense->fresh()->status);
        $this->assertSame(0.0, FinancialTreasuryService::balance(1));
    }

    public function test_financial_migration_generates_mysql_identifiers_within_the_server_limit(): void
    {
        $schema = Schema::getFacadeRoot();
        $connection = new MySqlConnection(new \PDO('sqlite::memory:'), 'migration_preview');

        try {
            Schema::swap($connection->getSchemaBuilder());
            $migration = require database_path('migrations/2026_11_11_100000_create_financial_workflow_tables.php');
            $queries = $connection->pretend(fn () => $migration->up());
        } finally {
            Schema::swap($schema);
        }

        $sql = implode("\n", array_column($queries, 'query'));
        $this->assertStringContainsString('constraint `fin_disb_pay_disb_fk`', $sql);
        preg_match_all('/`([^`]+)`/', $sql, $identifiers);
        $this->assertSame([], array_values(array_filter($identifiers[1], fn (string $name): bool => strlen($name) > 64)));
    }

    public function test_financial_indicators_page_uses_independent_filters_and_requires_service_access(): void
    {
        $this->admin->update(['status' => 'active']);
        $transaction = $this->makeTransaction(1000);
        $this->confirmedCashReceipt($transaction, 123.45);

        $this->get(route('filament.admin.resources.service-transactions.financial-indicators'))
            ->assertOk()
            ->assertSee('wire:init="applyFilters"', false)
            ->assertDontSee('123.45');

        Livewire::test(FinancialIndicators::class)
            ->assertDontSee('123.45')
            ->call('applyFilters')
            ->assertSee('123.45')
            ->set('dateFrom', now()->addDay()->toDateString())
            ->assertSee('123.45')
            ->call('applyFilters')
            ->assertDontSee('123.45');

        $this->flushSession();

        $this->actingAs(User::create([
            'name' => 'Unprivileged Staff',
            'email' => 'financial-report-staff@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
            'status' => 'active',
        ]))->get(route('filament.admin.resources.service-transactions.financial-indicators'))
            ->assertForbidden();
    }

    public function test_cash_surplus_refund_posts_once_and_resolves_the_deficiency(): void
    {
        $transaction = $this->makeTransaction(0);
        $receipt = $this->confirmedCashReceipt($transaction, 2200);

        $this->assertNull(FinancialSurplusRefundService::quote($transaction));
        $handover = $this->confirmHandover([$receipt->id => 2200], 'hnd-cash-surplus-refund');
        $this->assertSame(1200.0, FinancialSurplusRefundService::quote($transaction)['amount']);

        $key = (string) Str::uuid();
        $refund = FinancialSurplusRefundService::confirm($transaction, $receipt->id, 1200, $key, $this->admin);
        $retried = FinancialSurplusRefundService::confirm($transaction, $receipt->id, 1200, $key, $this->admin);

        $this->assertSame($refund->id, $retried->id);
        $this->assertSame(2, TreasuryTransaction::count());
        $this->assertSame(1000.0, FinancialTreasuryService::balance(1));
        $summary = FinancialSummaryService::for($transaction->fresh());
        $this->assertSame(0.0, $summary['surplus']);
        $this->assertSame(1200.0, $summary['surplus_refunded']);
        $this->assertSame(1000.0, $summary['applied_to_client']);
        $this->assertSame('resolved', $transaction->deficiencyTasks()->where('deficiency_type', 'surplus_pending')->value('status'));

        $this->expectException(ValidationException::class);
        CashHandoverService::reverse($handover, 'Cannot reverse a refunded handover', $this->admin);
    }

    private function makeTransaction(?float $fee, bool $explicitFee = true): ServiceTransaction
    {
        $metadata = ['cost_bearer' => 'client_paid'];

        if ($explicitFee) {
            $metadata['gov_fee_expected'] = $fee;
        }

        return ServiceTransaction::create([
            'transaction_number' => 'SRV-TEST-'.uniqid(),
            'transaction_date' => now()->toDateString(),
            'scope_type' => 'internal',
            'company_id' => 1,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'service_name' => 'Service',
            'beneficiary_name' => 'Test Client',
            'total_amount' => 1000,
            'executed_by' => $this->employee->id,
            'metadata' => $metadata,
        ]);
    }

    private function confirmedCashReceipt(ServiceTransaction $transaction, float $amount): FinancialReceipt
    {
        return FinancialReceiptService::record($transaction, [
            'amount' => $amount,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Test Client',
            'request_key' => 'rcp:'.uniqid('', true),
        ], $this->admin);

    }

    /**
     * @param  array<int, float>  $receiptAmounts
     */
    private function confirmHandover(array $receiptAmounts, string $key): CashHandover
    {
        $handover = CashHandoverService::request($receiptAmounts, [
            'request_key' => 'hnd:'.$key,
        ], $this->admin);

        $amounts = $handover->items()
            ->pluck('requested_amount', 'id')
            ->map(fn ($amount) => (float) $amount)
            ->all();

        return CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $this->admin),
            'confirmation_key' => 'hnd-confirm:'.$key,
            'confirmed_amounts' => $amounts,
        ], $this->admin)->fresh();
    }

    protected function tearDown(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';

        parent::tearDown();
    }
}
