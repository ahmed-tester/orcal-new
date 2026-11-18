<?php

namespace Tests\Feature;

use App\Filament\Pages\SupervisorPerformance;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use App\Services\SupervisorPerformanceService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupervisorPerformanceServiceTest extends TestCase
{
    private int $employeeSequence = 0;

    private int $assignmentSequence = 0;

    private int $taskSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        foreach (['0001_01_01_000000_create_users_table.php', '2026_09_17_123833_create_permission_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name');
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        (require database_path('migrations/2026_09_20_160000_create_tasks_and_weekly_notes_tables.php'))->up();

        Schema::create('supervision_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->default('supervision');
            $table->foreignId('company_id')->nullable();
            $table->foreignId('supervisor_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->string('title')->nullable();
            $table->text('tasks')->nullable();
            $table->json('assigned_employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->nullable()->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->insert([
            ['id' => 1, 'trade_name' => 'First company'],
            ['id' => 2, 'trade_name' => 'Second company'],
        ]);

        DB::table('branches')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Main branch'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Other branch'],
        ]);

        $this->travelTo(now()->setDate(2026, 11, 16)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_it_aggregates_due_items_and_excludes_unreliable_and_outside_items(): void
    {
        $this->actingAs($this->superAdmin());

        $supervisor = $this->employee('Supervisor', 1, 1);
        $first = $this->employee('First member', 1, 1);
        $second = $this->employee('Second member', 1, 1);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$first, $second, $supervisor],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => 'completed',
        ]);

        $this->item($this->assignment($first), $this->task('On time'), [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-11-04',
        ]);
        $this->item($this->assignment($second), $this->task('Late'), [
            'status' => 'completed', 'due_date' => '2026-11-06', 'completed_at' => '2026-11-07',
        ]);
        $this->item($this->assignment($first), $this->task('Still open'), [
            'status' => 'in_progress', 'due_date' => '2026-11-08',
        ]);
        $this->item($this->assignment($second), $this->task('Completed without date'), [
            'status' => 'completed', 'due_date' => '2026-11-09', 'completed_at' => null,
        ]);
        $this->item($this->assignment($first), $this->task('Due after shift'), [
            'status' => 'pending', 'due_date' => '2026-11-20',
        ]);
        $this->item($this->assignment($second), $this->task('Due before shift'), [
            'status' => 'pending', 'due_date' => '2026-10-30',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $this->assertCount(1, $report['rows']);
        $row = $report['rows'][0];

        $this->assertSame(3, $row['due']);
        $this->assertSame(1, $row['on_time']);
        $this->assertSame(1, $row['late']);
        $this->assertSame(1, $row['open']);
        $this->assertSame(1, $row['missing_date']);
        $this->assertSame(0, $row['ambiguous']);
        $this->assertSame(2, $row['team_count']);
        $this->assertEqualsWithDelta(33.33, $row['rate'], 0.001);

        $this->assertSame(3, $report['totals']['due']);
        $this->assertEqualsWithDelta(33.33, $report['totals']['rate'], 0.001);
        $this->assertSame(1, $report['totals']['excluded']);

        $this->assertCount(3, SupervisorPerformanceService::statementItems($row, SupervisorPerformanceService::METRIC_DUE));
        $this->assertCount(1, SupervisorPerformanceService::statementItems($row, SupervisorPerformanceService::METRIC_ON_TIME));
        $this->assertCount(1, SupervisorPerformanceService::statementItems($row, SupervisorPerformanceService::METRIC_EXCLUDED));

        // The supervisor's own item is excluded from the team indicators.
        $this->item($this->assignment($supervisor), $this->task('Supervisor personal'), [
            'status' => 'pending', 'due_date' => '2026-11-05',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $this->assertSame(3, $report['rows'][0]['due']);
    }

    public function test_overlapping_records_are_excluded_as_ambiguous(): void
    {
        $this->actingAs($this->superAdmin());

        $supervisor = $this->employee('Overlap supervisor', 1, 1);
        $member = $this->employee('Overlap member', 1, 1);

        $first = $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => 'completed',
        ]);

        // Another supervisor's record, same company/team/day but outside the
        // selected supervisor filter. It must still cause the overlap.
        $otherSupervisor = $this->employee('Other supervisor', 1, 1);
        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $otherSupervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-06',
            'status' => 'completed',
            'type' => 'shift',
        ]);

        $this->item($this->assignment($member), $this->task('Overlapping item'), [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-11-05',
        ]);
        $this->item($this->assignment($member), $this->task('Unique item'), [
            'status' => 'completed', 'due_date' => '2026-11-08', 'completed_at' => '2026-11-08',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $row = collect($report['rows'])->first(fn (array $row) => $row['shift_id'] === $first);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['ambiguous']);
        $this->assertSame(1, $row['due']);
        $this->assertSame(1, $row['on_time']);
        $this->assertEqualsWithDelta(100.0, $row['rate'], 0.001);
    }

    public function test_task_id_narrows_the_linked_items(): void
    {
        $this->actingAs($this->superAdmin());

        $supervisor = $this->employee('Task supervisor', 1, 1);
        $member = $this->employee('Task member', 1, 1);
        $linkedTask = $this->task('Linked task');
        $otherTask = $this->task('Other task');

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'task_id' => $linkedTask,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => 'completed',
        ]);

        $assignment = $this->assignment($member);
        $this->item($assignment, $linkedTask, [
            'status' => 'completed', 'due_date' => '2026-11-03', 'completed_at' => '2026-11-03',
        ]);
        $this->item($assignment, $otherTask, [
            'status' => 'pending', 'due_date' => '2026-11-04',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $row = $report['rows'][0];

        $this->assertSame(1, $row['due']);
        $this->assertSame(1, $row['on_time']);
        $this->assertSame(1, $row['items']->count());
        $this->assertSame('Linked task', $row['items']->first()['item']->task->name);
    }

    public function test_current_evaluation_includes_today_completions_and_excludes_open_items_due_today(): void
    {
        $this->actingAs($this->superAdmin());

        $supervisor = $this->employee('Active supervisor', 1, 1);
        $member = $this->employee('Active member', 1, 1);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
            'status' => 'active',
        ]);

        $assignment = $this->assignment($member);
        $this->item($assignment, $this->task('Measured'), [
            'status' => 'completed', 'due_date' => '2026-11-14', 'completed_at' => '2026-11-14',
        ]);
        $this->item($assignment, $this->task('Completed today'), [
            'status' => 'completed', 'due_date' => '2026-11-16', 'completed_at' => '2026-11-16',
        ]);
        $this->item($assignment, $this->task('Open today'), [
            'status' => 'pending', 'due_date' => '2026-11-16',
        ]);
        $this->item($assignment, $this->task('Due tomorrow'), [
            'status' => 'pending', 'due_date' => '2026-11-17',
        ]);

        $personalAssignment = $this->assignment($supervisor);
        $this->item($personalAssignment, $this->task('Personal completion today'), [
            'status' => 'completed', 'due_date' => '2026-11-16', 'completed_at' => '2026-11-16',
        ]);

        foreach (['15', '16', '17'] as $day) {
            $this->item($personalAssignment, $this->task('Personal open '.$day), [
                'status' => 'pending', 'due_date' => '2026-11-'.$day,
            ]);
        }

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $row = $report['rows'][0];

        $this->assertSame('2026-11-10', $row['window']['start']);
        $this->assertSame('2026-11-16', $row['window']['end']);
        $this->assertTrue($row['provisional']);
        $this->assertSame(2, $row['due']);
        $this->assertSame(2, $row['on_time']);
        $this->assertTrue($report['current_month']);
        $this->assertSame('2026-11-16', $report['evaluation_through']);
        $this->assertEqualsWithDelta(75.0, $report['evaluation']['total'], 0.001);

        $future = SupervisorPerformanceService::report([
            'month' => '2026-12', 'company_id' => 1, 'supervisor_id' => $supervisor,
        ]);
        $this->assertSame('future_month', $future['evaluation']['reason']);
        $this->assertNull($future['evaluation']['total']);
    }

    public function test_non_measurable_records_report_reason_codes(): void
    {
        $this->actingAs($this->superAdmin());

        $supervisor = $this->employee('Reason supervisor', 1, 1);
        $member = $this->employee('Reason member', 1, 1);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-09',
            'status' => 'suspended',
        ]);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-09',
            'status' => 'completed',
        ]);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => null,
            'end_date' => null,
            'status' => 'completed',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $reasons = collect($report['rows'])->pluck('reason')->all();

        $this->assertContains('status_excluded', $reasons);
        $this->assertContains('no_team', $reasons);
        $this->assertContains('invalid_dates', $reasons);
        $this->assertSame(0, $report['totals']['due']);
        $this->assertNull($report['totals']['rate']);
    }

    public function test_overnight_shift_counts_both_dates_with_one_bulk_task_query(): void
    {
        $this->actingAs($this->superAdmin());
        $supervisor = $this->employee('Night supervisor', 1, 1);
        $member = $this->employee('Night team member', 1, 1);
        $assignment = $this->assignment($member);

        foreach ([['07', '08'], ['10', '11']] as [$start, $end]) {
            $this->shift([
                'type' => 'shift',
                'company_id' => 1,
                'supervisor_id' => $supervisor,
                'assigned_employees' => [$member],
                'start_date' => '2026-11-'.$start,
                'end_date' => '2026-11-'.$end,
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
            ]);

            foreach ([$start, $end] as $day) {
                $this->item($assignment, $this->task('Due on '.$day), ['due_date' => '2026-11-'.$day]);
            }
        }

        DB::enableQueryLog();

        try {
            $report = SupervisorPerformanceService::report(['company_id' => 1, 'month' => '2026-11']);
            $queries = collect(DB::getQueryLog())->pluck('query');
        } finally {
            DB::disableQueryLog();
        }

        $this->assertSame([2, 2], array_column($report['rows'], 'due'));
        $this->assertSame(4, $report['totals']['due']);
        $this->assertCount(1, $queries->filter(fn (string $sql) => str_contains($sql, 'from "task_assignment_items"')));
    }

    public function test_monthly_evaluation_combines_team_and_personal_results_regardless_of_shift_filter(): void
    {
        $this->actingAs($this->employeeUser('Report viewer', 1, null, permissions: [
            'view_supervisor_performance_report', 'view_any_task_assignment',
        ]));

        $supervisor = $this->employee('Scored supervisor', 1, 1);
        $member = $this->employee('Scored member', 1, 1);

        foreach (['supervision' => ['01', '05'], 'shift' => ['06', '31']] as $type => [$start, $end]) {
            $this->shift([
                'type' => $type,
                'company_id' => 1,
                'supervisor_id' => $supervisor,
                'assigned_employees' => [$member, $supervisor],
                'start_date' => '2026-10-'.$start,
                'end_date' => '2026-10-'.$end,
                'status' => 'completed',
            ]);
        }

        $teamAssignment = $this->assignment($member, '2026-10-01');
        $personalAssignment = $this->assignment($supervisor, '2026-10-01');

        for ($day = 1; $day <= 10; $day++) {
            $this->item($teamAssignment, $this->task('Team '.$day), [
                'status' => 'completed',
                'due_date' => sprintf('2026-10-%02d', $day),
                'completed_at' => $day <= 9 ? sprintf('2026-10-%02d', $day) : '2026-10-15',
            ]);
        }

        for ($day = 1; $day <= 10; $day++) {
            $this->item($personalAssignment, $this->task('Personal '.$day), [
                'status' => 'completed',
                'due_date' => sprintf('2026-10-%02d', $day),
                'completed_at' => $day <= 8 ? sprintf('2026-10-%02d', $day) : '2026-10-20',
            ]);
        }

        $filters = [
            'month' => '2026-10',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'supervision',
        ];

        $report = SupervisorPerformanceService::report($filters, true);

        $this->assertCount(1, $report['rows']);
        $this->assertEqualsWithDelta(100.0, $report['totals']['rate'], 0.001);
        $this->assertEqualsWithDelta(90.0, $report['evaluation']['team'], 0.001);
        $this->assertEqualsWithDelta(80.0, $report['evaluation']['personal'], 0.001);
        $this->assertEqualsWithDelta(85.0, $report['evaluation']['total'], 0.001);

        $employeeTab = $report['employee'];
        $this->assertTrue($employeeTab['available']);
        $this->assertFalse($employeeTab['service_visible']);
        $this->assertEqualsWithDelta(80.0, $employeeTab['on_time_rate'], 0.001);
        $this->assertCount(10, $employeeTab['items']);
        $this->assertSame(
            [],
            $employeeTab['items']->pluck('task.name')->filter(fn ($name) => str_starts_with((string) $name, 'Team '))->values()->all()
        );

        Livewire::test(SupervisorPerformance::class)
            ->set('companyId', 1)
            ->set('month', '2026-10')
            ->set('supervisorId', $supervisor)
            ->set('type', 'shift')
            ->assertSeeHtml('class="sup-evaluation-rings"')
            ->assertSeeHtml('<span>85.00</span>')
            ->assertSeeHtml('<span>90.00%</span>')
            ->assertSeeHtml('<span>80.00%</span>')
            ->call('showEmployeeTab')
            ->assertSeeHtml('<span>85.00</span>');

        DB::table('task_assignment_items')->where('task_assignment_id', $personalAssignment)
            ->update(['status' => 'pending', 'completed_at' => null]);
        $this->assertEqualsWithDelta(45.0, SupervisorPerformanceService::report($filters)['evaluation']['total'], 0.001);

        DB::table('task_assignment_items')->where('task_assignment_id', $personalAssignment)
            ->update(['status' => 'completed']);
        $evaluation = SupervisorPerformanceService::report($filters)['evaluation'];
        $this->assertSame('no_personal_items', $evaluation['reason']);
        $this->assertNull($evaluation['total']);

        Livewire::test(SupervisorPerformance::class)
            ->set('companyId', 1)
            ->set('month', '2026-10')
            ->set('supervisorId', $supervisor)
            ->assertSee(__('supervisor_performance.evaluation.reasons.no_personal_items'))
            ->assertDontSeeHtml('class="sup-evaluation-rings"');
    }

    public function test_report_access_requires_permission_company_scope_and_no_branch_restriction(): void
    {
        $this->actingAs($this->employeeUser('Permitted staff', 1, null, 'saudi_employee', ['view_supervisor_performance_report']));
        $this->assertTrue(SupervisorPerformanceService::canViewReport());

        $this->actingAs($this->employeeUser('No permission staff', 1, null, 'saudi_employee'));
        $this->assertFalse(SupervisorPerformanceService::canViewReport());

        $this->actingAs($this->employeeUser('Branch staff', 1, 1, 'saudi_employee', ['view_supervisor_performance_report']));
        $this->assertFalse(SupervisorPerformanceService::canViewReport());
    }

    public function test_report_permission_alone_does_not_expose_team_task_metrics(): void
    {
        $this->actingAs($this->employeeUser('Report-only viewer', 1, null, permissions: ['view_supervisor_performance_report']));
        $supervisor = $this->employee('Supervisor', 1, 1);
        $member = $this->employee('Team member', 1, 1);
        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
        ]);
        $this->item($this->assignment($member), $this->task('Team item'), ['due_date' => '2026-11-05']);

        $report = SupervisorPerformanceService::report(['company_id' => 1, 'month' => '2026-11']);

        $this->assertTrue(SupervisorPerformance::canAccess());
        $this->assertFalse(SupervisorPerformanceService::canViewTeamTaskData());
        $this->assertFalse($report['team_tasks_visible']);
        $this->assertSame('no_task_permission', $report['rows'][0]['reason']);
        $this->assertEmpty($report['rows'][0]['items']);
    }

    public function test_empty_company_scope_renders_both_tabs(): void
    {
        $this->actingAs($this->employeeUser('Scoped viewer', 1, null, permissions: ['view_supervisor_performance_report']));

        Livewire::test(SupervisorPerformance::class)
            ->set('companyId', 2)
            ->call('showEmployeeTab')
            ->assertSet('activeTab', 'employee')
            ->call('showSupervisionTab')
            ->assertOk();
    }

    public function test_employee_services_are_limited_to_the_selected_company(): void
    {
        $this->actingAs($this->superAdmin());
        Schema::create('services', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/2026_10_13_181000_create_service_transactions_table.php'))->up();
        (require database_path('migrations/2026_11_08_120000_create_unified_deficiency_tasks_table.php'))->up();
        $supervisor = $this->employee('Service supervisor', 1, 1);

        foreach ([1, 2] as $companyId) {
            $transactionId = DB::table('service_transactions')->insertGetId([
                'transaction_number' => 'TX-'.$companyId,
                'transaction_date' => '2026-11-02',
                'company_id' => $companyId,
                'executed_by' => $supervisor,
            ]);
            DB::table('unified_deficiency_tasks')->insert([
                'company_id' => $companyId,
                'service_transaction_id' => $transactionId,
                'responsible_employee_id' => $supervisor,
                'task_code' => 'DEF-'.$companyId,
                'deficiency_type' => 'document',
                'title' => 'Assigned deficiency',
                'created_at' => now(),
            ]);
        }

        $report = SupervisorPerformanceService::report([
            'company_id' => 1, 'supervisor_id' => $supervisor, 'month' => '2026-11',
        ], true);

        $services = $report['employee']['services'];
        $this->assertSame(1, $services['stats']->active);
        $this->assertSame(1, $services['stats']->assigned_deficiencies);
        $this->assertSame(['TX-1'], $services['transactions']->pluck('transaction_number')->all());
    }

    public function test_page_hides_outside_production_and_rejects_unscoped_statements(): void
    {
        $this->actingAs($this->superAdmin());
        $supervisor = $this->employee('Page supervisor', 1, 1);
        $member = $this->employee('Page member', 1, 1);

        $shift = $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-09',
            'status' => 'completed',
        ]);

        $this->item($this->assignment($member), $this->task('Page item'), [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-11-05',
        ]);

        $otherCompanySupervisor = $this->employee('Other company supervisor', 2, 2);
        $otherCompanyShift = $this->shift([
            'company_id' => 2,
            'supervisor_id' => $otherCompanySupervisor,
            'assigned_employees' => [],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-09',
            'status' => 'completed',
        ]);

        $this->assertTrue(SupervisorPerformance::canAccess());

        Livewire::test(SupervisorPerformance::class)
            ->set('companyId', 1)
            ->set('supervisorId', $supervisor)
            ->set('month', '2026-11')
            ->assertOk()
            ->call('openStatement', $shift, SupervisorPerformanceService::METRIC_DUE)
            ->assertSet('showStatementModal', true)
            ->call('closeStatement');

        Livewire::test(SupervisorPerformance::class)
            ->set('companyId', 1)
            ->set('supervisorId', $supervisor)
            ->set('month', '2026-11')
            ->call('openStatement', $otherCompanyShift, SupervisorPerformanceService::METRIC_DUE)
            ->assertNotFound();

        $this->app['env'] = 'production';

        try {
            $this->assertFalse(SupervisorPerformanceService::canViewReport());
            $this->assertFalse(SupervisorPerformance::canAccess());
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_items_are_loaded_as_task_assignment_items_with_their_relations(): void
    {
        $this->actingAs($this->superAdmin());
        $supervisor = $this->employee('Relation supervisor', 1, 1);
        $member = $this->employee('Relation member', 1, 1);

        $this->shift([
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'assigned_employees' => [$member],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-09',
            'status' => 'completed',
        ]);

        $this->item($this->assignment($member), $this->task('Relation item'), [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-11-05',
        ]);

        $report = SupervisorPerformanceService::report([
            'month' => '2026-11',
            'company_id' => 1,
            'supervisor_id' => $supervisor,
            'type' => 'all',
        ], false);

        $item = $report['rows'][0]['items']->first()['item'];

        $this->assertInstanceOf(TaskAssignmentItem::class, $item);
        $this->assertSame('Relation member', $item->taskAssignment->employee->user->name);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['name' => 'Super Admin', 'user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function employeeUser(
        string $name,
        int $companyId,
        ?int $branchId,
        string $userType = 'saudi_employee',
        array $permissions = [],
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'user_type' => $userType,
            'status' => 'active',
        ]);

        $this->employee($name, $companyId, $branchId, $user, $userType);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->refresh();
    }

    private function employee(
        string $name,
        int $companyId,
        ?int $branchId,
        ?User $user = null,
        string $userType = 'saudi_employee',
    ): int {
        $user ??= User::factory()->create(['name' => $name, 'user_type' => $userType, 'status' => 'active']);

        return DB::table('employees')->insertGetId([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'employee_number' => 'EMP-'.(++$this->employeeSequence),
            'employment_type' => 'full_time',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function shift(array $attributes): int
    {
        return DB::table('supervision_shifts')->insertGetId(array_merge([
            'type' => 'supervision',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes, [
            'assigned_employees' => json_encode($attributes['assigned_employees'] ?? []),
        ]));
    }

    private function assignment(int $employeeId, string $date = '2026-11-01'): int
    {
        return DB::table('task_assignments')->insertGetId([
            'transaction_number' => 'TA-'.(++$this->assignmentSequence),
            'assignment_date' => $date,
            'employee_id' => $employeeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function task(string $name): int
    {
        return DB::table('tasks')->insertGetId([
            'task_code' => 'TASK-'.(++$this->taskSequence),
            'name' => $name,
            'default_weight' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(int $assignmentId, int $taskId, array $attributes = []): int
    {
        return DB::table('task_assignment_items')->insertGetId(array_merge([
            'task_assignment_id' => $assignmentId,
            'task_id' => $taskId,
            'status' => 'pending',
            'weight' => 0,
            'score' => 0,
            'score_percentage' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
