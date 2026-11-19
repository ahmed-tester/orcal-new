<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\CompanyDepartmentPerformanceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentPerformanceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_demo_completes_the_dashboard_data_and_can_be_repeated(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(12, 0));
        $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
        $url = route('system.seed-department-performance-demo');

        $this->get($url)->assertOk()->assertSee(__('performance.department_demo.run'));
        $this->post($url)->assertRedirect($url)->assertSessionHasErrors();
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('departments', 0);

        $this->post(route('system.seed-performance-demo'))->assertOk();
        $this->get($url)->assertOk();
        $this->assertDatabaseCount('departments', 4);

        $this->post($url)->assertOk()->assertSee(__('performance.department_demo.ready'));
        $companies = Company::query()->pluck('id', 'company_number');
        $companyA = $companies['PERF-DEMO-A'];
        $companyB = $companies['PERF-DEMO-B'];
        $departments = Department::query()->where('department_code', 'like', 'PERF-DEPT-DEMO-%')->pluck('id', 'department_code');
        $sharedId = $departments['PERF-DEPT-DEMO-SHARED'];
        $groupId = $departments['PERF-DEPT-DEMO-GROUP'];
        $emptyId = $departments['PERF-DEPT-DEMO-EMPTY'];
        $cards = CompanyDepartmentPerformanceService::getDepartmentsOverview()->keyBy('department_id');

        $this->assertSame('shared', $cards[$sharedId]->scope);
        $this->assertSame(92, $cards[$sharedId]->score->score);
        $this->assertSame(66.7, $cards[$sharedId]->on_time_rate);
        $this->assertSame([$companyA => 96, $companyB => 96], $cards[$sharedId]->companies->pluck('score.score', 'company_id')->all());
        $this->assertSame('group', $cards[$groupId]->scope);
        $this->assertSame(96, $cards[$groupId]->score->score);
        $this->assertSame([$companyA => 96, $companyB => 100], $cards[$groupId]->companies->pluck('score.score', 'company_id')->all());
        $this->assertArrayNotHasKey($emptyId, $cards->all());

        $filters = ['company_id' => [$companyB]];
        $filtered = CompanyDepartmentPerformanceService::getDepartmentsOverview($filters)->keyBy('department_id');
        $this->assertSame(96, $filtered[$sharedId]->score->score);
        $this->assertSame(100, $filtered[$groupId]->score->score);
        $this->assertSame(1, CompanyDepartmentPerformanceService::getKashfTasksQuery([], 'score_overdue', $companyA, $sharedId)->count());
        $this->assertSame(2, CompanyDepartmentPerformanceService::getKashfDeficienciesQuery($filters, 'score_overdue', $companyB, $sharedId)->count());

        $tables = ['companies', 'departments', 'company_department', 'users', 'employees', 'task_assignments', 'task_assignment_items', 'service_transactions', 'unified_deficiency_tasks'];
        $counts = collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);
        $this->post($url)->assertOk();

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
    }

    public function test_department_demo_route_requires_a_super_admin_in_a_demo_environment(): void
    {
        $url = route('system.seed-department-performance-demo');
        $this->get($url)->assertRedirect(Filament::getPanel('admin')->getLoginUrl());

        $this->actingAs(User::factory()->create(['user_type' => 'saudi_employee']));
        $this->get($url)->assertForbidden();
        $this->post($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['user_type' => 'super_admin']));
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->app->instance('env', 'production');
        $this->get($url)->assertNotFound();
        $this->post($url)->assertNotFound();
        $this->assertDatabaseCount('departments', 0);
    }
}
