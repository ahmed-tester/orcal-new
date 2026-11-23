<?php

namespace Tests\Feature;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Enums\PaymentItemCategory;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use App\Services\CashHandoverService;
use App\Services\FinancialReceiptService;
use App\Services\FinancialTreasuryService;
use App\Services\InvoiceFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceFinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Employee $employee;

    private Customer $customer;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/invoice_financial_workflow.sqlite';

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

        $this->company = Company::create([
            'trade_name' => 'Test Co',
            'legal_name' => 'Test Co LLC',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'name' => 'Acme Client',
            'status' => 'active',
        ]);

        foreach ([
            'record_service_receipt',
            'confirm_service_receipt',
            'request_cash_handover',
            'confirm_cash_handover',
            'reverse_financial_transaction',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->admin->givePermissionTo([
            'record_service_receipt',
            'confirm_service_receipt',
            'request_cash_handover',
            'confirm_cash_handover',
            'reverse_financial_transaction',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_treasury_post_supports_optional_category_and_date_and_detects_payroll_payment_link(): void
    {
        $defaultTx = FinancialTreasuryService::post(
            companyId: $this->company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: 500,
            description: 'Default category tx',
        );

        $this->assertSame(PaymentItemCategory::SERVICES, $defaultTx->paymentCollectionItem->main_item);
        $this->assertSame(now()->toDateString(), $defaultTx->transaction_date->toDateString());

        $invoiceTx = FinancialTreasuryService::post(
            companyId: $this->company->id,
            direction: FinancialCashDirection::INCOMING,
            amount: 750,
            description: 'Invoice category tx',
            category: PaymentItemCategory::INVOICES,
            transactionDate: '2026-11-10',
        );

        $this->assertSame(PaymentItemCategory::INVOICES, $invoiceTx->paymentCollectionItem->main_item);
        $this->assertSame('2026-11-10', $invoiceTx->transaction_date->toDateString());

        $record = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-01',
            'net_amount' => 750,
        ]);

        PayrollPayment::create([
            'payroll_record_id' => $record->id,
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'payment_date' => '2026-11-10',
            'amount' => 750,
            'payment_method' => 'cash',
            'treasury_transaction_id' => $invoiceTx->id,
        ]);

        $this->assertTrue(FinancialTreasuryService::isAlreadyLinked($invoiceTx));
    }

    public function test_independent_invoice_receipt_handover_idempotency_and_reversal(): void
    {
        $invoice = InvoiceFinancialService::createIndependent([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => '2026-11-23',
            'due_date' => '2026-11-30',
            'paid_amount' => 999, // must be ignored
        ], [
            [
                'item_name' => 'Consultation',
                'quantity' => 2,
                'unit_price' => 500,
                'tax_rate' => 0,
            ],
        ], $this->admin);

        $this->assertTrue($invoice->isManaged());
        $this->assertSame('2026-11-30', $invoice->due_date?->toDateString());
        $this->assertSame('2026-11-30', $invoice->fresh()->attributesToArray()['due_date'] ?? null);
        $this->assertSame(1000.0, (float) $invoice->total_amount);
        $this->assertSame(0.0, (float) $invoice->paid_amount);
        $this->assertSame(1000.0, (float) $invoice->remaining_amount);

        $requestKey = FinancialReceiptService::newRequestKey();
        $receipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
            'request_key' => $requestKey,
            'amount' => 600,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        // Retry with same key returns same receipt without duplicating
        $retry = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
            'request_key' => $requestKey,
            'amount' => 600,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        $this->assertSame($receipt->id, $retry->id);

        // Reuse same key with different amount must fail
        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
                'request_key' => $requestKey,
                'amount' => 700,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $this->employee->id,
                'payer_name' => 'Acme Client',
            ], $this->admin);
            $this->fail('Expected ValidationException on request_key reuse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('request_key', $e->errors());
        }

        $invoice->refresh();
        $this->assertSame(600.0, (float) $invoice->paid_amount);
        $this->assertSame(400.0, (float) $invoice->remaining_amount);
        $this->assertSame(PaymentStatus::PARTIALLY_PAID, $invoice->payment_status);

        $handover = CashHandoverService::request([$receipt->id => 600], [
            'request_key' => CashHandoverService::newRequestKey(),
        ], $this->admin);

        $signature = CashHandoverService::signatureCodeForDelivery($handover, $this->admin);
        $confirmedHandover = CashHandoverService::confirm($handover, [
            'confirmation_key' => CashHandoverService::newConfirmationKey(),
            'signature_code' => $signature,
            'confirmed_amounts' => [$receipt->id => 600],
        ], $this->admin);

        $this->assertSame(PaymentItemCategory::INVOICES, $confirmedHandover->treasuryTransaction->paymentCollectionItem->main_item);
        $summary = InvoiceFinancialService::summary($invoice->refresh());
        $this->assertSame(0.0, $summary['custody']);
        $this->assertSame(600.0, $summary['treasury_received']);

        // Non-cash receipt starts pending
        $nonCash = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 400,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        $this->assertSame(FinancialReceiptStatus::PENDING_CONFIRMATION, $nonCash->status);
        $this->assertSame(600.0, (float) $invoice->refresh()->paid_amount);

        FinancialReceiptService::confirm($nonCash, [
            'destination_type' => 'company_bank',
            'destination_reference' => 'IBAN-123',
            'reference_number' => 'REF-123',
            'proof_path' => 'proofs/test.pdf',
        ], $this->admin);

        $invoice->refresh();
        $this->assertSame(1000.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->remaining_amount);
        $this->assertSame(PaymentStatus::PAID, $invoice->payment_status);

        // Reverse non-cash receipt
        FinancialReceiptService::reverse($nonCash, 'Bank transfer returned', $this->admin);
        $invoice->refresh();
        $this->assertSame(600.0, (float) $invoice->paid_amount);
        $this->assertSame(400.0, (float) $invoice->remaining_amount);

        // Cannot collect more than remaining balance (400)
        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 450,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $this->employee->id,
                'payer_name' => 'Acme Client',
            ], $this->admin);
            $this->fail('Expected ValidationException when exceeding invoice remaining amount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }

    public function test_service_mirror_invoice_is_protected_and_equal_ids_do_not_cross_contaminate(): void
    {
        $service = Service::create([
            'company_id' => $this->company->id,
            'name' => 'Visa Service',
            'price' => 500,
            'status' => 'active',
        ]);

        $tx = ServiceTransaction::create([
            'company_id' => $this->company->id,
            'service_id' => $service->id,
            'customer_id' => $this->customer->id,
            'executed_by' => $this->employee->id,
            'transaction_date' => '2026-11-23',
            'total_amount' => 500,
        ]);

        $mirrorInvoice = Invoice::findOrFail($tx->metadata['auto_invoice_id']);
        $this->assertTrue($mirrorInvoice->isServiceMirror());

        // Cannot record receipt directly on service mirror invoice
        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $mirrorInvoice->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 100,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $this->employee->id,
                'payer_name' => 'Acme Client',
            ], $this->admin);
            $this->fail('Expected ValidationException on service mirror invoice.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }

        // Independent invoice with its own ID must not write service_transaction_id in audit
        $independent = InvoiceFinancialService::createIndependent([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => '2026-11-23',
            'total_amount' => 300,
        ], [], $this->admin);

        $invReceipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $independent->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 300,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        $svcReceipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_SERVICE_TRANSACTION, $tx->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 200,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        // Mixed source handover must be rejected
        try {
            CashHandoverService::request([
                $invReceipt->id => 300,
                $svcReceipt->id => 200,
            ], [
                'request_key' => CashHandoverService::newRequestKey(),
            ], $this->admin);
            $this->fail('Expected ValidationException on mixed handover sources.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $audit = UnifiedAuditLog::where('auditable_type', Invoice::class)
            ->where('auditable_id', $independent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->service_transaction_id);
    }

    public function test_empty_legacy_invoice_activates_on_first_receipt_while_paid_legacy_is_rejected(): void
    {
        $emptyLegacy = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => '2026-10-01',
            'total_amount' => 400,
            'paid_amount' => 0,
        ]);

        $this->assertFalse($emptyLegacy->isManaged());

        FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $emptyLegacy->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 150,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $this->employee->id,
            'payer_name' => 'Acme Client',
        ], $this->admin);

        $emptyLegacy->refresh();
        $this->assertTrue($emptyLegacy->isManaged());
        $this->assertSame(150.0, (float) $emptyLegacy->paid_amount);

        $paidLegacy = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => '2026-10-01',
            'total_amount' => 400,
            'paid_amount' => 100,
        ]);

        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $paidLegacy->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 100,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $this->employee->id,
                'payer_name' => 'Acme Client',
            ], $this->admin);
            $this->fail('Expected ValidationException on paid legacy invoice.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }
    }

    public function test_create_invoice_page_saves_items_and_records_full_cash_receipt_in_single_transaction(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'issue_date' => '2026-11-23',
                'is_paid_by_applicant' => true,
                'creation_payment_method' => FinancialPaymentMethod::CASH->value,
                'items' => [
                    [
                        'item_name' => 'Service A',
                        'quantity' => 2,
                        'unit_price' => 150,
                        'tax_rate' => 0,
                        'total_amount' => 300,
                    ],
                    [
                        'item_name' => 'Service B',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'tax_rate' => 15,
                        'total_amount' => 230,
                    ],
                ],
                'creation_received_amount' => 530,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertSame(530.0, (float) $invoice->total_amount);
        $this->assertSame(530.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->remaining_amount);
        $this->assertSame(PaymentStatus::PAID, $invoice->payment_status);
        $this->assertCount(2, $invoice->items);

        $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, $invoice->id)->firstOrFail();
        $this->assertSame(530.0, (float) $receipt->amount);
        $this->assertSame(FinancialPaymentMethod::CASH, $receipt->payment_method);
        $this->assertSame(FinancialReceiptStatus::CONFIRMED, $receipt->status);
        $this->assertSame($this->employee->id, (int) $receipt->collector_employee_id);
    }

    public function test_create_invoice_form_preserves_manual_partial_amount_when_items_change_and_records_partial_receipt(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'issue_date' => '2026-11-23',
                'items' => [
                    [
                        'item_name' => 'Service A',
                        'quantity' => 1,
                        'unit_price' => 500,
                        'tax_rate' => 0,
                        'total_amount' => 500,
                    ],
                ],
            ])
            ->set('data.is_paid_by_applicant', true)
            ->assertSet('data.creation_received_amount', 500)
            ->set('data.creation_received_amount', 200)
            ->set('data.items', [
                'item-1' => [
                    'item_name' => 'Service A',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'tax_rate' => 0,
                    'total_amount' => 500,
                ],
                'item-2' => [
                    'item_name' => 'Service B',
                    'quantity' => 1,
                    'unit_price' => 300,
                    'tax_rate' => 0,
                    'total_amount' => 300,
                ],
            ])
            ->assertSet('data.creation_received_amount', 200);

        $component->call('create')->assertHasNoFormErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertSame(800.0, (float) $invoice->total_amount);
        $this->assertSame(200.0, (float) $invoice->paid_amount);
        $this->assertSame(600.0, (float) $invoice->remaining_amount);
        $this->assertSame(PaymentStatus::PARTIALLY_PAID, $invoice->payment_status);
        $this->assertCount(2, $invoice->items);

        $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, $invoice->id)->firstOrFail();
        $this->assertSame(200.0, (float) $receipt->amount);
    }

    public function test_create_invoice_records_pending_pos_and_bank_transfer_receipts_independently(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'issue_date' => '2026-11-23',
                'is_paid_by_applicant' => true,
                'creation_payment_method' => FinancialPaymentMethod::POS->value,
                'items' => [
                    [
                        'item_name' => 'POS Item',
                        'quantity' => 1,
                        'unit_price' => 400,
                        'tax_rate' => 0,
                        'total_amount' => 400,
                    ],
                ],
                'creation_received_amount' => 400,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $posInvoice = Invoice::latest('id')->firstOrFail();
        $posReceipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, $posInvoice->id)->firstOrFail();
        $this->assertSame(FinancialPaymentMethod::POS, $posReceipt->payment_method);
        $this->assertSame(FinancialReceiptStatus::PENDING_CONFIRMATION, $posReceipt->status);
        $this->assertSame(0.0, (float) $posInvoice->paid_amount);
        $this->assertSame(400.0, (float) $posInvoice->remaining_amount);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'issue_date' => '2026-11-23',
                'is_paid_by_applicant' => true,
                'creation_payment_method' => FinancialPaymentMethod::BANK_TRANSFER->value,
                'items' => [
                    [
                        'item_name' => 'Bank Item',
                        'quantity' => 1,
                        'unit_price' => 350,
                        'tax_rate' => 0,
                        'total_amount' => 350,
                    ],
                ],
                'creation_received_amount' => 350,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $bankInvoice = Invoice::latest('id')->firstOrFail();
        $bankReceipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, $bankInvoice->id)->firstOrFail();
        $this->assertSame(FinancialPaymentMethod::BANK_TRANSFER, $bankReceipt->payment_method);
        $this->assertSame(FinancialReceiptStatus::PENDING_CONFIRMATION, $bankReceipt->status);
    }

    public function test_create_invoice_rolls_back_invoice_and_items_when_creation_receipt_fails(): void
    {
        $ownerWithoutEmployee = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
        $ownerWithoutEmployee->givePermissionTo('record_service_receipt');
        $this->actingAs($ownerWithoutEmployee);

        $beforeInvoices = Invoice::count();
        $beforeItems = InvoiceItem::count();
        $beforeReceipts = FinancialReceipt::count();

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'employee_id' => $this->employee->id,
                'issue_date' => '2026-11-23',
                'is_paid_by_applicant' => true,
                'creation_payment_method' => FinancialPaymentMethod::CASH->value,
                'items' => [
                    [
                        'item_name' => 'Rollback Item',
                        'quantity' => 1,
                        'unit_price' => 250,
                        'tax_rate' => 0,
                        'total_amount' => 250,
                    ],
                ],
                'creation_received_amount' => 250,
            ])
            ->call('create')
            ->assertHasErrors(['collector_employee_id']);

        $this->assertSame($beforeInvoices, Invoice::count());
        $this->assertSame($beforeItems, InvoiceItem::count());
        $this->assertSame($beforeReceipts, FinancialReceipt::count());
    }

    public function test_record_creation_receipt_is_idempotent_for_repeated_submission(): void
    {
        $invoice = InvoiceFinancialService::createIndependent([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => '2026-11-23',
            'total_amount' => 500,
        ], [
            [
                'item_name' => 'Idempotent Service',
                'quantity' => 1,
                'unit_price' => 500,
                'tax_rate' => 0,
            ],
        ], $this->admin);

        $payload = [
            'is_paid_by_applicant' => true,
            'creation_received_amount' => 500,
            'creation_payment_method' => FinancialPaymentMethod::CASH->value,
            'creation_request_key' => FinancialReceiptService::newRequestKey(),
        ];

        $first = InvoiceFinancialService::recordCreationReceipt($invoice->refresh(), $payload, $this->admin);
        $second = InvoiceFinancialService::recordCreationReceipt($invoice->refresh(), $payload, $this->admin);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, $invoice->id)->count());

        $invoice->refresh();
        $this->assertSame(500.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->remaining_amount);
    }

    public function test_collector_selection_and_editable_payer_name_respect_role_and_branch_scope(): void
    {
        $branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch A',
            'status' => 'active',
        ]);
        $branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch B',
            'status' => 'active',
        ]);

        $collectorUser = User::factory()->create([
            'user_type' => 'saudi_employee',
            'status' => 'active',
        ]);
        $branchAEmployee = Employee::create([
            'user_id' => $collectorUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $branchA->id,
            'status' => 'active',
        ]);
        $branchBEmployee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $this->company->id,
            'branch_id' => $branchB->id,
            'status' => 'active',
        ]);

        $invoice = InvoiceFinancialService::createIndependent([
            'company_id' => $this->company->id,
            'branch_id' => $branchA->id,
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'issue_date' => '2026-11-23',
            'total_amount' => 600,
        ], [], $this->admin);

        // Owner with an employee record can still select another collector in the invoice's branch and edit payer_name
        $receipt = FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
            'request_key' => FinancialReceiptService::newRequestKey(),
            'amount' => 200,
            'payment_method' => FinancialPaymentMethod::CASH->value,
            'collector_employee_id' => $branchAEmployee->id,
            'payer_name' => 'Proxy Payer',
        ], $this->admin);

        $this->assertSame($branchAEmployee->id, (int) $receipt->collector_employee_id);
        $this->assertSame('Proxy Payer', $receipt->payer_name);

        // Selecting an employee from another branch must be rejected
        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 100,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'collector_employee_id' => $branchBEmployee->id,
                'payer_name' => 'Proxy Payer',
            ], $this->admin);
            $this->fail('Expected ValidationException for out-of-branch collector.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('collector_employee_id', $e->errors());
        }

        // Staff user without an employee record must not fall back to invoice->employee_id
        $staffWithoutEmployee = User::factory()->create([
            'user_type' => 'super_admin',
            'status' => 'active',
        ]);
        $staffWithoutEmployee->givePermissionTo('record_service_receipt');

        try {
            FinancialReceiptService::recordFor(FinancialReceipt::SOURCE_INVOICE, $invoice->id, [
                'request_key' => FinancialReceiptService::newRequestKey(),
                'amount' => 100,
                'payment_method' => FinancialPaymentMethod::CASH->value,
                'payer_name' => 'Acme Client',
            ], $staffWithoutEmployee);
            $this->fail('Expected ValidationException when staff has no employee record.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('collector_employee_id', $e->errors());
        }
    }
}
