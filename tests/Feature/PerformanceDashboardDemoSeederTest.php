<?php

namespace Tests\Feature;

use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use App\Services\CompanyCompliancePerformanceService;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\CompanyFinancialPerformanceService;
use App\Services\CompanyViolationsPerformanceService;
use App\Services\LeaveSummaryService;
use Database\Seeders\PerformanceDashboardDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceDashboardDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_load_demo_data_and_view_all_dashboard_tabs(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $this->actingAs($admin);
        $url = route('system.seed-performance-demo');

        $this->get($url)->assertOk();
        $this->assertDatabaseCount('companies', 0);
        $this->post($url)->assertOk()->assertSee('الأفق للخدمات')->assertSee('المسار للأعمال');

        $this->assertDatabaseCount('tasks', 3);
        $this->assertSame(3, DB::table('task_assignment_items')->distinct()->count('task_id'));
        $tables = ['companies', 'employees', 'service_transactions', 'unified_deficiency_tasks', 'tasks', 'task_assignment_items', 'attendance', 'leaves', 'violations', 'payroll_records', 'debts', 'financial_receipts', 'cash_handovers', 'financial_disbursements', 'financial_disbursement_payments', 'treasury_transactions'];
        $counts = collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);
        $this->assertGreaterThan(0, $counts->min());
        $this->post($url)->assertOk();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        $company = Company::query()->where('company_number', 'PERF-DEMO-A')->firstOrFail();
        $this->assertSame($admin->password, $admin->fresh()->password);
        $filters = ['date_from' => '2026-11-01', 'date_to' => '2026-11-30', 'company_id' => $company->id];
        $stats = CompanyDepartmentPerformanceService::getOverallStats($filters);
        $this->assertSame(2, $stats->completed_services);
        $this->assertSame(1, $stats->overdue_services);
        $this->assertSame(3, $stats->task_due_in_period);
        $this->assertSame(2, CompanyDepartmentPerformanceService::getOverallStats(array_replace($filters, [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-31',
        ]))->completed_services);

        $attendance = AttendanceSummaryService::getStats($filters + ['month' => '2026-11']);
        $this->assertSame([2, 1, 1, 30], [$attendance['present_days'], $attendance['late_days'], $attendance['absent_days'], $attendance['late_minutes']]);
        $this->assertSame(['on_leave_today_count' => 1, 'pending_requests_count' => 1, 'total_approved_days' => 9], LeaveSummaryService::getOverviewStats($filters));
        $this->assertSame(5, CompanyCompliancePerformanceService::getStats($filters)['iqama']['total_tracked']);
        $violations = CompanyViolationsPerformanceService::getStats($filters);
        $this->assertSame(1, $violations['stages']['objection']['count']);
        $this->assertSame(2, $violations['totals']['payment_count']);

        $finance = CompanyFinancialPerformanceService::overview($filters, [$company->id]);
        $this->assertSame(1000.0, $finance['groups']['receipts_and_custody']['receipt_cash']);
        $this->assertSame(75.0, $finance['groups']['receipts_and_custody']['handover_pending']);
        $this->assertSame(70.0, $finance['groups']['settlements']['reimbursement_due']);
        $this->assertSame(250.0, $finance['groups']['collection']['collection_paid']);
        $this->assertSame(1000.0, $finance['groups']['debts']['debt_overdue']);
        $this->assertCount(5, CompanyFinancialPerformanceService::preview('receipt_cash', $filters, [$company->id]));
        $this->assertSame(6, CompanyFinancialPerformanceService::statement('receipt_cash', $filters, [$company->id])->total());
        $branch = Branch::query()->where('company_id', $company->id)->where('name', 'جدة')->firstOrFail();
        $branchFinance = CompanyFinancialPerformanceService::overview($filters + ['branch_id' => $branch->id], [$company->id]);
        $this->assertTrue($branchFinance['treasury']['branch_restricted']);
        $this->assertSame(0.0, $branchFinance['groups']['receipts_and_custody']['receipt_non_cash']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->assertSee($company->trade_name)
            ->call('showWorkforceOverview')->assertSet('activeOverviewTab', 'workforce')
            ->call('showComplianceOverview')->assertSet('activeOverviewTab', 'compliance')
            ->call('showViolationsOverview')->assertSet('activeOverviewTab', 'violations')
            ->call('showFinancialOverview')->assertSet('activeOverviewTab', 'financial')
            ->assertSeeHtml('id="financial-preview-receipt_cash"')
            ->call('openKashf', 'financial', 'receipt_cash')->assertSet('showKashfModal', true);

        auth()->logout();
        $this->artisan('db:seed', ['--class' => PerformanceDashboardDemoSeeder::class])->assertSuccessful();
        $this->assertDatabaseCount('financial_receipts', $counts['financial_receipts']);
    }

    public function test_route_requires_an_admin_in_a_demo_environment(): void
    {
        $url = route('system.seed-performance-demo');
        $this->get($url)->assertRedirect(Filament::getPanel('admin')->getLoginUrl());

        $this->actingAs(User::factory()->create(['user_type' => 'saudi_employee']));
        $this->get($url)->assertForbidden();
        $this->post($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->app->instance('env', 'production');
        $this->get($url)->assertNotFound();
        $this->post($url)->assertNotFound();
        $this->assertDatabaseCount('companies', 0);
    }
}
