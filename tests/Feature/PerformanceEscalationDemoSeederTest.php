<?php

namespace Tests\Feature;

use App\Enums\PerformanceEscalationClosureReason;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\PerformanceEscalation;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use Database\Seeders\PerformanceEscalationDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceEscalationDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_route_prepares_one_escalation_and_preserves_recovery_on_repeat(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $this->actingAs($admin);
        $url = route('system.seed-performance-escalation-demo');

        $this->get($url)->assertOk()->assertSee(__('performance.escalation_demo.run'));
        $this->assertDatabaseCount('companies', 0);

        $this->post($url)->assertOk()
            ->assertSee(__('performance.escalation_demo.company'))
            ->assertSee(__('performance_escalations.navigation'));

        $company = Company::query()->where('company_number', PerformanceEscalationDemoSeeder::COMPANY_NUMBER)->sole();
        $department = Department::query()->where('company_id', $company->id)->sole();
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('task_assignment_items', 9);
        $this->assertDatabaseCount('performance_escalations', 0);

        $this->artisan('performance:check-escalations')->assertSuccessful();
        $case = PerformanceEscalation::query()->open()->sole();
        $this->assertSame($company->id, $case->company_id);
        $this->assertSame($department->id, $case->subject_id);
        $this->assertSame('64.00', $case->score_at_open);
        $this->assertTrue($admin->notifications()->where('data->event_key', 'PerformanceEscalationOpened')->exists());

        TaskAssignmentItem::query()->firstOrFail()->update(['status' => TaskStatus::COMPLETED]);
        $this->artisan('performance:check-escalations')->assertSuccessful();
        $case->refresh();
        $this->assertSame(PerformanceEscalationClosureReason::RECOVERED, $case->closure_reason);
        $this->assertSame('68.00', $case->close_score);

        $this->post($url)->assertOk();
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseCount('task_assignments', 1);
        $this->assertDatabaseCount('task_assignment_items', 9);
        $this->assertSame(1, TaskAssignmentItem::query()->where('status', TaskStatus::COMPLETED)->count());
        $this->artisan('performance:check-escalations')->assertSuccessful();
        $this->assertDatabaseCount('performance_escalations', 1);
    }

    public function test_test_route_is_limited_to_super_admins_in_demo_environments(): void
    {
        $url = route('system.seed-performance-escalation-demo');
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
