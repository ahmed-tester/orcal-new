<?php

namespace Tests\Feature;

use App\Enums\PaymentItemCategory;
use App\Enums\UserType;
use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PaymentCollectionItem;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\CompanyFinancialPerformanceService;
use App\Services\FinancialSummaryService;
use App\Services\TreasurySummaryService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompanyFinancialPerformanceTest extends TestCase
{
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['cache.default' => 'array']);
        config(['queue.default' => 'sync']);
        Notification::fake();
        DB::purge();
        DB::reconnect('sqlite');

        $this->artisan('migrate')->assertExitCode(0);
    }

    public function test_financial_overview_empty_without_authorized_companies(): void
    {
        $viewer = $this->createUser();
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_treasury_transaction']));
        $this->actingAs($viewer);

        $stats = TreasurySummaryService::getPerformanceStats([], []);
        $this->assertSame(0.0, $stats['current_balance']);
        $this->assertSame(0.0, $stats['total_incoming']);
        $this->assertSame(0.0, $stats['total_outgoing']);
        $this->assertSame(0.0, $stats['net_flow']);
        $this->assertFalse($stats['branch_restricted']);
    }

    public function test_permission_checks_on_financial_tab_and_statements(): void
    {
        $company = $this->createCompany();
        $viewer = $this->createEmployee($company)->user;
        $this->actingAs($viewer);

        $page = new CompanyDepartmentPerformance;
        $this->assertFalse($page->canViewFinancialOverview());

        try {
            $page->showFinancialOverview();
            $this->fail('Expected showFinancialOverview to throw 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        try {
            $page->openKashf('financial', 'current_balance');
            $this->fail('Expected openKashf to throw 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_treasury_transaction']));
        $this->assertTrue($page->canViewFinancialOverview());
    }

    public function test_financial_overview_metrics_calculation_and_multi_company(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $companyA = $this->createCompany();
        $companyB = $this->createCompany();

        $itemA = $this->createPaymentItem($companyA);
        $itemB = $this->createPaymentItem($companyB);

        // Company A: 3 transactions
        $this->createTransaction($companyA, $itemA, 'incoming', 1000, '2026-11-01');
        $this->createTransaction($companyA, $itemA, 'outgoing', 300, '2026-11-05');
        $this->createTransaction($companyA, $itemA, 'incoming', 500, '2026-11-10');

        // Company B: 2 transactions
        $this->createTransaction($companyB, $itemB, 'incoming', 2000, '2026-11-02');
        $this->createTransaction($companyB, $itemB, 'outgoing', 800, '2026-11-08');

        // Current balance should be 1200 (Co A: 1000 - 300 + 500) + 1200 (Co B: 2000 - 800) = 2400
        $allStats = TreasurySummaryService::getPerformanceStats([], [$companyA->id, $companyB->id]);
        $this->assertSame(2400.0, $allStats['current_balance']);
        $this->assertSame(3500.0, $allStats['total_incoming']);
        $this->assertSame(1100.0, $allStats['total_outgoing']);
        $this->assertSame(2400.0, $allStats['net_flow']);
        $this->assertSame(5, $allStats['total_count']);

        // Date-filtered stats (2026-11-04 to 2026-11-09):
        // Co A: Tx 2 (outgoing 300)
        // Co B: Tx 2 (outgoing 800)
        $filteredStats = TreasurySummaryService::getPerformanceStats([
            'date_from' => '2026-11-04',
            'date_to' => '2026-11-09',
        ], [$companyA->id, $companyB->id]);

        $this->assertSame(2400.0, $filteredStats['current_balance']); // current snapshot
        $this->assertSame(0.0, $filteredStats['total_incoming']);
        $this->assertSame(1100.0, $filteredStats['total_outgoing']);
        $this->assertSame(-1100.0, $filteredStats['net_flow']);
        $this->assertSame(2, $filteredStats['total_count']);
        $register = TreasurySummaryService::getStats(['date_from' => '2026-11-04', 'date_to' => '2026-11-09']);
        $this->assertSame($register, array_diff_key($filteredStats, ['branch_restricted' => true]));

        // Filter by Company A only
        $companyAStats = TreasurySummaryService::getPerformanceStats([
            'company_id' => [$companyA->id],
        ], [$companyA->id, $companyB->id]);
        $this->assertSame(1200.0, $companyAStats['current_balance']);
        $this->assertSame(1500.0, $companyAStats['total_incoming']);
        $this->assertSame(300.0, $companyAStats['total_outgoing']);
        $this->assertSame(1200.0, $companyAStats['net_flow']);
    }

    public function test_branch_restriction_hides_treasury(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $company = $this->createCompany();
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main Branch']);

        $stats = TreasurySummaryService::getPerformanceStats([
            'branch_id' => $branch->id,
        ], [$company->id]);

        $this->assertTrue($stats['branch_restricted']);
        $this->assertSame(0.0, $stats['current_balance']);

        // Kashf query should return empty query when branch is specified
        $kashf = TreasurySummaryService::getPerformanceKashfQuery([
            'branch_id' => $branch->id,
        ], 'current_balance', [$company->id]);
        $this->assertSame(0, $kashf->count());

        // Livewire page openKashf throws 403 when branch is restricted
        $page = new CompanyDepartmentPerformance;
        $page->branchId = $branch->id;
        try {
            $page->openKashf('financial', 'current_balance');
            $this->fail('Expected openKashf to throw 403 on branch restriction');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_kashf_queries(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $companyA = $this->createCompany();
        $companyB = $this->createCompany();
        $itemA = $this->createPaymentItem($companyA);
        $itemB = $this->createPaymentItem($companyB);

        $this->createTransaction($companyA, $itemA, 'incoming', 1000, '2026-11-01');
        $this->createTransaction($companyA, $itemA, 'outgoing', 300, '2026-11-05');
        $this->createTransaction($companyB, $itemB, 'incoming', 500, '2026-11-06');

        $authorized = [$companyA->id, $companyB->id];

        // 1. Current balance statement: exactly 1 record per company (latest)
        $latestKashf = TreasurySummaryService::getPerformanceKashfQuery([], 'current_balance', $authorized)->get();
        $this->assertCount(2, $latestKashf);
        $this->assertSame(700.0, (float) $latestKashf->firstWhere('company_id', $companyA->id)->balance_after);
        $this->assertSame(500.0, (float) $latestKashf->firstWhere('company_id', $companyB->id)->balance_after);

        // 2. Incoming statement in period (2026-11-02 to 2026-11-10)
        $incomingKashf = TreasurySummaryService::getPerformanceKashfQuery([
            'date_from' => '2026-11-02',
            'date_to' => '2026-11-10',
        ], 'incoming', $authorized)->get();
        $this->assertCount(1, $incomingKashf);
        $this->assertSame(500.0, (float) $incomingKashf->first()->amount);

        // 3. Outgoing statement in period
        $outgoingKashf = TreasurySummaryService::getPerformanceKashfQuery([
            'date_from' => '2026-11-02',
            'date_to' => '2026-11-10',
        ], 'outgoing', $authorized)->get();
        $this->assertCount(1, $outgoingKashf);
        $this->assertSame(300.0, (float) $outgoingKashf->first()->amount);

        // 4. Net flow statement
        $allKashf = TreasurySummaryService::getPerformanceKashfQuery([], 'net_flow', $authorized)->get();
        $this->assertCount(3, $allKashf);
    }

    public function test_unauthorized_company_isolation(): void
    {
        $companyAllowed = $this->createCompany();
        $companyForbidden = $this->createCompany();

        $viewer = $this->createEmployee($companyAllowed)->user;
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_treasury_transaction']));
        $this->actingAs($viewer);

        $itemAllowed = $this->createPaymentItem($companyAllowed);
        $itemForbidden = $this->createPaymentItem($companyForbidden);

        $this->createTransaction($companyAllowed, $itemAllowed, 'incoming', 1000, '2026-11-01');
        $this->createTransaction($companyForbidden, $itemForbidden, 'incoming', 5000, '2026-11-01');

        // Trying to query with forbidden company_id in filters
        $stats = TreasurySummaryService::getPerformanceStats(
            ['company_id' => [$companyForbidden->id]],
            [$companyAllowed->id]
        );
        $this->assertSame(0.0, $stats['current_balance']);
        $this->assertSame(0.0, $stats['total_incoming']);

        $kashf = TreasurySummaryService::getPerformanceKashfQuery(
            ['company_id' => [$companyForbidden->id]],
            'current_balance',
            [$companyAllowed->id]
        );
        $this->assertSame(0, $kashf->count());
    }

    public function test_livewire_renders_treasury_cards_filters_link_and_paginates_statements(): void
    {
        $company = $this->createCompany();
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN));

        $item = $this->createPaymentItem($company);
        $first = $this->createTransaction($company, $item, 'incoming', 1500, '2026-11-02');
        $latestIncoming = $first;
        for ($index = 0; $index < 15; $index++) {
            $latestIncoming = $this->createTransaction($company, $item, 'incoming', 100, '2026-11-03');
        }
        $last = $this->createTransaction($company, $item, 'outgoing', 500, '2026-11-01');
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Treasury Test Branch']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('companyId', [(string) $company->id])
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->call('showFinancialOverview')
            ->assertSet('activeOverviewTab', 'financial')
            ->assertSee('companyId%5B0%5D='.$company->id)
            ->assertSee('dateFrom=2026-11-01')
            ->assertSee('2,500.00')
            ->assertSeeHtml('class="perf-financial-grid"')
            ->assertSeeHtml('wire:click="selectFinancialPreview(&#039;treasury&#039;, &#039;incoming&#039;)"')
            ->assertSeeHtml('id="financial-preview-current_balance"')
            ->assertSeeHtml('id="financial-preview-receipt_cash"')
            ->assertSeeHtml('id="financial-preview-fee_direct"')
            ->assertSeeHtml('id="financial-preview-payroll_net"')
            ->assertSeeHtml('id="financial-preview-collection_net"')
            ->assertSeeHtml('id="financial-preview-debt_total"')
            ->call('selectFinancialPreview', 'treasury', 'incoming')
            ->assertDontSeeHtml('id="financial-preview-current_balance"')
            ->assertSeeHtml('id="financial-preview-incoming"')
            ->assertSee(__('performance.financial.preview_count', ['count' => 5]))
            ->assertSee($latestIncoming->transaction_number)
            ->assertDontSee($first->transaction_number)
            ->assertSee(__('performance.financial.full_statement'))
            ->set('dateTo', '2026-11-02')
            ->assertSee($first->transaction_number)
            ->assertDontSee($latestIncoming->transaction_number)
            ->set('dateTo', '2026-11-30')
            ->call('selectFinancialPreview', 'treasury', 'outgoing')
            ->assertDontSeeHtml('id="financial-preview-incoming"')
            ->assertSeeHtml('id="financial-preview-outgoing"')
            ->call('selectFinancialPreview', 'treasury', 'outgoing')
            ->assertSet('financialPreviewTypes', ['treasury' => 'outgoing'])
            ->assertSeeHtml('id="financial-preview-outgoing"')
            ->call('selectFinancialPreview', 'treasury', 'incoming')
            ->call('openKashf', 'financial', 'current_balance')
            ->assertDispatched('open-modal', id: 'performance-kashf-modal')
            ->assertSee($last->transaction_number)
            ->call('openKashf', 'financial', 'outgoing')
            ->assertSee($last->transaction_number)
            ->call('openKashf', 'financial', 'net_flow')
            ->assertSee(__('performance.financial.kashf_net_flow'))
            ->call('openKashf', 'financial', 'incoming')
            ->assertSee(__('performance.financial.kashf_incoming'))
            ->call('setPage', 2, 'kashfPage')
            ->assertSee($first->transaction_number)
            ->call('closeKashf')
            ->set('branchId', (string) $branch->id)
            ->assertSet('financialPreviewTypes', [])
            ->assertDontSeeHtml('id="financial-preview-incoming"')
            ->assertSeeHtml('id="financial-preview-receipt_cash"')
            ->assertDontSee(__('performance.financial.open_treasury'))
            ->assertSee(__('performance.financial.branch_restricted_title'));

        $page->call('selectFinancialPreview', 'treasury', 'incoming')->assertForbidden();
    }

    public function test_workflow_cards_and_statements_use_event_dates_and_open_balances(): void
    {
        $company = $this->createCompany();
        $employee = $this->createEmployee($company);
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN));
        $item = $this->createPaymentItem($company);
        $sourceId = DB::table('service_transactions')->insertGetId([
            'transaction_number' => 'SERVICE-001',
            'transaction_date' => '2026-10-01',
            'company_id' => $company->id,
            'metadata' => json_encode(['financial_workflow_version' => 1]),
        ]);
        $source = ['company_id' => $company->id, 'source_type' => 'service_transaction', 'source_id' => $sourceId];
        $receiptData = $source + ['received_at' => '2026-10-31', 'status' => 'confirmed', 'collector_employee_id' => $employee->id];
        $receipt = DB::table('financial_receipts')->insertGetId($receiptData + [
            'receipt_number' => 'CASH-001', 'request_key' => 'cash', 'payment_method' => 'cash',
            'amount' => 1000, 'confirmed_at' => '2026-10-31',
        ]);
        DB::table('financial_receipts')->insert($receiptData + [
            'receipt_number' => 'BANK-001', 'request_key' => 'bank', 'payment_method' => 'bank_transfer',
            'amount' => 250, 'confirmed_at' => '2026-11-05',
        ]);
        DB::table('financial_receipts')->insert(array_replace($receiptData, [
            'receipt_number' => 'REVERSED-001', 'request_key' => 'reversed', 'payment_method' => 'cash',
            'status' => 'reversed', 'amount' => 500, 'confirmed_at' => '2026-11-05',
        ]));

        $deposit = $this->createTransaction($company, $item, 'incoming', 620, '2026-11-06');
        $direct = $this->createTransaction($company, $item, 'outgoing', 120, '2026-11-07');
        $payout = $this->createTransaction($company, $item, 'outgoing', 50, '2026-11-10');
        $handover = DB::table('cash_handovers')->insertGetId([
            'company_id' => $company->id, 'handover_number' => 'HANDOVER-001', 'request_key' => 'handover',
            'collector_employee_id' => $employee->id, 'status' => 'confirmed',
            'total_requested_amount' => 600, 'total_confirmed_amount' => 600, 'difference_amount' => 20,
            'treasury_transaction_id' => $deposit->id, 'requested_at' => '2026-11-01', 'confirmed_at' => '2026-11-06',
            'metadata' => json_encode(['extra_amount' => 20]),
        ]);
        $pending = DB::table('cash_handovers')->insertGetId([
            'company_id' => $company->id, 'handover_number' => 'HANDOVER-002', 'request_key' => 'pending',
            'collector_employee_id' => $employee->id, 'status' => 'submitted',
            'total_requested_amount' => 100, 'requested_at' => '2026-11-12',
        ]);
        DB::table('cash_handover_items')->insert([
            ['cash_handover_id' => $handover, 'financial_receipt_id' => $receipt, 'company_id' => $company->id, 'requested_amount' => 600, 'confirmed_amount' => 600],
            ['cash_handover_id' => $pending, 'financial_receipt_id' => $receipt, 'company_id' => $company->id, 'requested_amount' => 100, 'confirmed_amount' => 0],
        ]);
        DB::table('financial_disbursements')->insert($source + [
            'disbursement_number' => 'DIRECT-001', 'request_key' => 'direct', 'disbursement_type' => 'treasury_direct',
            'amount' => 120, 'treasury_transaction_id' => $direct->id, 'status' => 'paid',
        ]);
        $claim = DB::table('financial_disbursements')->insertGetId($source + [
            'disbursement_number' => 'CLAIM-001', 'request_key' => 'claim', 'disbursement_type' => 'employee_personal',
            'amount' => 200, 'status' => 'partially_reimbursed', 'approved_at' => '2026-11-09', 'paid_by_employee_id' => $employee->id,
        ]);
        DB::table('financial_disbursements')->insert($source + [
            'disbursement_number' => 'CLAIM-002', 'request_key' => 'claim-pending', 'disbursement_type' => 'employee_personal',
            'amount' => 70, 'status' => 'pending_approval',
        ]);
        DB::table('financial_disbursement_payments')->insert([
            'financial_disbursement_id' => $claim, 'company_id' => $company->id, 'payment_number' => 'PAY-001',
            'request_key' => 'reimbursement', 'amount' => 50, 'paid_at' => '2026-11-10',
            'treasury_transaction_id' => $payout->id,
        ]);
        DB::table('financial_disbursement_payments')->insert([
            'financial_disbursement_id' => $claim, 'company_id' => $company->id, 'payment_number' => 'PAY-002',
            'request_key' => 'reimbursement-reversed', 'amount' => 80, 'paid_at' => '2026-11-12',
            'metadata' => json_encode(['reversed_at' => '2026-11-13 00:00:00']),
        ]);

        $filters = ['date_from' => '2026-11-01', 'date_to' => '2026-11-30'];
        $companyIds = [$company->id];
        $groups = CompanyFinancialPerformanceService::overview($filters, $companyIds)['groups'];
        $expected = [
            'receipt_cash' => 0.0, 'receipt_non_cash' => 250.0, 'custody' => 400.0,
            'handover_pending' => 100.0, 'handover_confirmed' => 620.0, 'fee_direct' => 120.0,
            'fee_personal' => 200.0, 'claim_pending' => 70.0, 'reimbursement_paid' => 50.0, 'reimbursement_due' => 150.0,
        ];
        $this->assertSame($expected, $groups['receipts_and_custody'] + $groups['settlements']);
        $sourceSummary = FinancialSummaryService::for(ServiceTransaction::findOrFail($sourceId));
        $this->assertSame($sourceSummary['custody'], $expected['custody']);
        $this->assertSame($sourceSummary['employee_due'], $expected['reimbursement_due']);
        $this->assertSame($sourceSummary['reimbursed'], $expected['reimbursement_paid']);
        foreach ($expected as $type => $amount) {
            $rows = CompanyFinancialPerformanceService::statement($type, $filters, $companyIds);
            $this->assertSame($amount, (float) $rows->sum('dashboard_amount'), $type);
        }

        $restricted = CompanyFinancialPerformanceService::overview($filters + ['company_id' => 99999], $companyIds);
        $this->assertSame(0.0, $restricted['groups']['receipts_and_custody']['custody']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->call('showFinancialOverview')
            ->assertSee(__('performance.financial.metric_hints.custody'))
            ->assertSeeHtml('id="financial-preview-receipt_non_cash"')
            ->assertSee('BANK-001')
            ->call('selectFinancialPreview', 'treasury', 'outgoing')
            ->call('selectFinancialPreview', 'receipts_and_custody', 'custody')
            ->assertSeeHtml('id="financial-preview-outgoing"')
            ->assertSeeHtml('id="financial-preview-custody"')
            ->assertSee('CASH-001')
            ->assertSee('300.00')
            ->call('openKashf', 'financial', 'custody')
            ->assertSee('CASH-001')
            ->assertSee('300.00')
            ->call('openKashf', 'financial', 'reimbursement_paid')
            ->assertSee('PAY-001')
            ->assertDontSee('PAY-002');
    }

    public function test_payroll_collections_and_debts_use_existing_balances_and_branch_scope(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN));
        $company = $this->createCompany();
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Selected Branch']);
        $employee = $this->createEmployee($company);
        $employee->update(['branch_id' => $branch->id]);
        $outside = $this->createEmployee($company);

        $record = ['company_id' => $company->id, 'payroll_month' => '2026-11-01', 'record_date' => '2026-12-01'];
        DB::table('payroll_records')->insert([
            $record + ['employee_id' => $employee->id, 'operation_type' => 'payroll', 'net_amount' => 1000, 'paid_amount' => 400, 'remaining_amount' => 600, 'payment_status' => 'partial'],
            $record + ['employee_id' => $employee->id, 'operation_type' => 'collection', 'net_amount' => 300, 'paid_amount' => 100, 'remaining_amount' => 200, 'payment_status' => 'partially_paid'],
            $record + ['employee_id' => $outside->id, 'operation_type' => 'payroll', 'net_amount' => 500, 'paid_amount' => 0, 'remaining_amount' => 500, 'payment_status' => 'unpaid'],
        ]);
        $debt = ['company_id' => $company->id, 'debt_date' => '2026-11-11', 'due_date' => '2026-11-13',
            'total_amount' => 500, 'paid_amount' => 100, 'remaining_amount' => 400];
        DB::table('debts')->insert([
            $debt + ['employee_id' => $employee->id, 'debt_number' => 'DEBT-001'],
            $debt + ['employee_id' => $outside->id, 'debt_number' => 'DEBT-002'],
        ]);

        $filters = ['date_from' => '2026-11-10', 'date_to' => '2026-11-14', 'branch_id' => $branch->id];
        $overview = CompanyFinancialPerformanceService::overview($filters, [$company->id]);
        $this->assertTrue($overview['treasury']['branch_restricted']);
        $this->assertSame(['payroll_net' => 1000.0, 'payroll_paid' => 400.0, 'payroll_remaining' => 600.0], $overview['groups']['payroll']);
        $this->assertSame(100.0, $overview['groups']['collection']['collection_paid']);
        $this->assertSame(400.0, $overview['groups']['debts']['debt_overdue']);
        $debtRows = CompanyFinancialPerformanceService::statement('debt_overdue', $filters, [$company->id]);
        $this->assertSame(['DEBT-001'], $debtRows->pluck('debt_number')->all());
        $payrollRows = CompanyFinancialPerformanceService::statement('payroll_paid', $filters, [$company->id]);
        $this->assertSame(400.0, (float) $payrollRows->sum('paid_amount'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2026-11-10')
            ->set('dateTo', '2026-11-14')
            ->set('branchId', (string) $branch->id)
            ->call('showFinancialOverview')
            ->call('openKashf', 'financial', 'debt_overdue')
            ->assertSee('DEBT-001')
            ->assertDontSee('DEBT-002')
            ->call('openKashf', 'financial', 'payroll_paid')
            ->assertSee('400.00')
            ->assertSee(__('invoices.statuses.partially_paid'));
    }

    public function test_service_read_permission_does_not_grant_treasury_payroll_or_debt_statements(): void
    {
        $company = $this->createCompany();
        $viewer = $this->createEmployee($company)->user;
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_service_transaction']));
        $this->actingAs($viewer);

        $overview = CompanyFinancialPerformanceService::overview([], [$company->id]);
        $this->assertNull($overview['treasury']);
        $this->assertSame(['receipts_and_custody', 'settlements'], array_keys($overview['groups']));
        $this->assertSame(0, CompanyFinancialPerformanceService::statement('receipt_cash', [], [$company->id])->total());

        foreach (['current_balance', 'payroll_net', 'debt_total'] as $type) {
            try {
                CompanyFinancialPerformanceService::statement($type, [], [$company->id]);
                $this->fail('Expected permission denial for '.$type);
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    private function createUser(UserType $type = UserType::SAUDI_EMPLOYEE): User
    {
        $this->seq++;

        return User::create([
            'name' => "User {$this->seq}",
            'email' => "user{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $type,
        ]);
    }

    private function createCompany(array $attributes = []): Company
    {
        $this->seq++;

        return Company::create(array_merge([
            'trade_name' => "Company {$this->seq}",
            'status' => 'active',
        ], $attributes));
    }

    private function createEmployee(Company $company): Employee
    {
        $user = $this->createUser(UserType::FOREIGN_EMPLOYEE);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);
    }

    private function createPaymentItem(Company $company): PaymentCollectionItem
    {
        $this->seq++;

        return PaymentCollectionItem::create([
            'transaction_number' => "ITEM-{$this->seq}",
            'company_id' => $company->id,
            'main_item' => PaymentItemCategory::SERVICES,
            'sub_item' => 'Sub Item',
            'service_price' => 100,
        ]);
    }

    private function createTransaction(
        Company $company,
        PaymentCollectionItem $item,
        string $type,
        float $amount,
        string $date
    ): TreasuryTransaction {
        $this->seq++;

        return TreasuryTransaction::create([
            'transaction_number' => "TX-{$this->seq}",
            'voucher_number' => "V-{$this->seq}",
            'transaction_date' => $date,
            'company_id' => $company->id,
            'transaction_type' => $type,
            'payment_collection_item_id' => $item->id,
            'recipient_or_payer' => 'Beneficiary',
            'amount' => $amount,
            'description' => 'Test Transaction',
        ]);
    }
}
