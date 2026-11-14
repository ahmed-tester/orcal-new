<?php

namespace Tests\Feature;

use App\Filament\Pages\MyTasks;
use App\Filament\Resources\SaudiEmployees\Pages\EmployeePerformance;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Filament\Resources\TaskAssignments\Pages\TaskAssignmentsSummary;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Services\ServicePerformanceSummaryService;
use App\Models\User;
use App\Services\TaskAssignmentSummaryService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TaskAssignmentSummaryServiceTest extends TestCase
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

        Schema::create('service_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->string('scope_type')->default('internal');
            $table->string('service_target')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->string('service_type')->nullable();
            $table->string('first_party')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_phone')->nullable();
            $table->string('beneficiary_national_id')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->string('service_status')->default('active');
            $table->string('workflow_status')->default('in_progress');
            $table->foreignId('executed_by')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('unified_deficiency_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->nullableMorphs('taskable');
            $table->foreignId('service_transaction_id')->nullable();
            $table->string('task_code')->unique();
            $table->string('deficiency_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('responsible_employee_id')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable();
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

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
            ['id' => 2, 'company_id' => 1, 'name' => 'Second branch'],
            ['id' => 3, 'company_id' => 2, 'name' => 'Other company branch'],
        ]);

        $this->travelTo(now()->setDate(2026, 11, 12)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_it_aggregates_period_metrics_and_keeps_employees_without_assignments(): void
    {
        $this->actingAs($this->superAdmin());

        $employee = $this->employee('Employee One', 1, 1);
        $idle = $this->employee('Employee Two', 1, 1);
        $assignment = $this->assignment($employee);

        $onTime = $this->task('On time task');
        $late = $this->task('Late task');
        $overdue = $this->task('Overdue task');
        $postponed = $this->task('Postponed task');
        $old = $this->task('Old task');

        $this->item($assignment, $onTime, [
            'status' => 'completed', 'due_date' => '2026-11-10', 'completed_at' => '2026-11-09', 'weight' => 2, 'score' => 1.8,
        ]);
        $this->item($assignment, $late, [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-11-08', 'weight' => 1, 'score' => 0.5,
        ]);
        $this->item($assignment, $overdue, [
            'status' => 'pending', 'due_date' => '2026-11-01', 'weight' => 3, 'score' => 0,
        ]);
        $this->item($assignment, $postponed, [
            'status' => 'postponed', 'due_date' => '2026-11-15', 'weight' => 1, 'score' => 0,
        ]);

        // Outside the period and not overdue: must not leak into November.
        $this->item($assignment, $old, [
            'status' => 'completed', 'due_date' => '2026-10-10', 'completed_at' => '2026-10-09', 'weight' => 5, 'score' => 5,
        ]);

        $row = TaskAssignmentSummaryService::employeeRow($employee, ['month' => '2026-11']);

        $this->assertNotNull($row);
        $this->assertSame(4, (int) $row->total_items);
        $this->assertSame(2, (int) $row->completed_count);
        $this->assertSame(1, (int) $row->pending_count);
        $this->assertSame(1, (int) $row->postponed_count);
        $this->assertSame(0, (int) $row->in_progress_count);
        $this->assertSame(1, (int) $row->overdue_count);
        $this->assertSame(2, (int) $row->completed_in_period);
        $this->assertSame(4, (int) $row->due_in_period);
        $this->assertSame(1, (int) $row->on_time_count);
        $this->assertSame(2, (int) $row->dated_completed_count);
        $this->assertSame(7.0, (float) $row->weight_sum);
        $this->assertSame(2.3, (float) $row->score_sum);

        $this->assertEqualsWithDelta(32.86, TaskAssignmentSummaryService::weightedPercentage($row), 0.001);
        $this->assertEqualsWithDelta(50.0, TaskAssignmentSummaryService::completionRate($row), 0.001);
        $this->assertEqualsWithDelta(50.0, TaskAssignmentSummaryService::onTimeRate($row), 0.001);

        // Items are ordered by effective date: overdue, late, on time.
        $details = TaskAssignmentSummaryService::employeeDetails($employee, ['month' => '2026-11']);
        $this->assertSame(
            ['Overdue task', 'Late task', 'On time task', 'Postponed task'],
            $details['items']->pluck('task.name')->all()
        );

        $stats = TaskAssignmentSummaryService::stats(['month' => '2026-11']);
        $this->assertSame(2, (int) $stats->employees_count);
        $this->assertSame(4, (int) $stats->total_items);
        $this->assertSame(2, (int) $stats->completed_in_period);
        $this->assertSame(1, (int) $stats->overdue_count);
        $this->assertSame(1, (int) $stats->postponed_count);

        $idleRow = TaskAssignmentSummaryService::employeeRow($idle, ['month' => '2026-11']);
        $this->assertNotNull($idleRow);
        $this->assertSame(0, (int) $idleRow->total_items);
        $this->assertNull(TaskAssignmentSummaryService::completionRate($idleRow));
        $this->assertNull(TaskAssignmentSummaryService::onTimeRate($idleRow));
        $this->assertSame(0.0, TaskAssignmentSummaryService::weightedPercentage($idleRow));

        // Status filtering reuses the same period and scope.
        $completedStats = TaskAssignmentSummaryService::stats(['month' => '2026-11', 'status' => 'completed']);
        $this->assertSame(2, (int) $completedStats->total_items);
        $this->assertSame(1, (int) $completedStats->on_time_count);
    }

    public function test_items_without_due_dates_follow_the_period_and_rate_rules(): void
    {
        $this->actingAs($this->superAdmin());

        $employee = $this->employee('Employee Without Dates', 1, 1);
        $assignment = $this->assignment($employee);

        // Open item with no due date follows the assignment period and is
        // displayed without entering overdue counts or the rate denominator.
        $this->item($assignment, $this->task('Undated open task'), ['status' => 'pending']);

        // Completed without a due date: counted in the period, excluded from rates.
        $this->item($assignment, $this->task('Completed without due date'), [
            'status' => 'completed', 'completed_at' => '2026-11-07', 'weight' => 1, 'score' => 1,
        ]);

        // Completed late, with a due date outside the period: counted, not on time.
        $this->item($assignment, $this->task('Late but completed in period'), [
            'status' => 'completed', 'due_date' => '2026-10-30', 'completed_at' => '2026-11-02', 'weight' => 1, 'score' => 1,
        ]);

        $row = TaskAssignmentSummaryService::employeeRow($employee, ['month' => '2026-11']);

        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->total_items);
        $this->assertSame(2, (int) $row->completed_count);
        $this->assertSame(1, (int) $row->pending_count);
        $this->assertSame(0, (int) $row->overdue_count);
        $this->assertSame(0, (int) $row->due_in_period);
        $this->assertSame(0, (int) $row->due_completed_count);
        $this->assertSame(1, (int) $row->dated_completed_count);
        $this->assertSame(0, (int) $row->on_time_count);
        $this->assertNull(TaskAssignmentSummaryService::completionRate($row));
        $this->assertSame(0.0, TaskAssignmentSummaryService::onTimeRate($row));
        $this->assertEqualsWithDelta(100.0, TaskAssignmentSummaryService::weightedPercentage($row), 0.001);

        $this->assertSame(
            ['Late but completed in period', 'Completed without due date', 'Undated open task'],
            TaskAssignmentSummaryService::employeeDetails($employee, ['month' => '2026-11'])['items']->pluck('task.name')->all()
        );

        // The same undated item is outside a period where it was not assigned.
        $this->assertSame(
            [],
            TaskAssignmentSummaryService::employeeDetails($employee, ['month' => '2026-10'])['items']->pluck('task.name')->all()
        );
    }

    public function test_completion_rate_uses_items_due_and_completed_by_the_period_end(): void
    {
        $this->actingAs($this->superAdmin());

        $employee = $this->employee('Completion Rate Employee', 1, 1);
        $assignment = $this->assignment($employee);

        // Closed during November but due in October: increases completed in
        // period without entering the completion-rate numerator.
        $this->item($assignment, $this->task('Old task closed in period'), [
            'status' => 'completed', 'due_date' => '2026-10-10', 'completed_at' => '2026-11-05', 'weight' => 1, 'score' => 1,
        ]);

        // Due inside November but completed after its end.
        $this->item($assignment, $this->task('Due in period, completed later'), [
            'status' => 'completed', 'due_date' => '2026-11-05', 'completed_at' => '2026-12-02', 'weight' => 1, 'score' => 0,
        ]);

        // Due and completed inside November.
        $this->item($assignment, $this->task('Due and done in period'), [
            'status' => 'completed', 'due_date' => '2026-11-10', 'completed_at' => '2026-11-09', 'weight' => 1, 'score' => 1,
        ]);

        $row = TaskAssignmentSummaryService::employeeRow($employee, ['month' => '2026-11']);

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->completed_in_period);
        $this->assertSame(2, (int) $row->due_in_period);
        $this->assertSame(1, (int) $row->due_completed_count);

        // 1 due-and-completed / 2 due-in-period, not 1 completed-in-period.
        $this->assertEqualsWithDelta(50.0, TaskAssignmentSummaryService::completionRate($row), 0.001);

        $stats = TaskAssignmentSummaryService::stats(['month' => '2026-11']);
        $this->assertSame(1, (int) $stats->completed_in_period);
        $this->assertSame(2, (int) $stats->due_in_period);
        $this->assertSame(1, (int) $stats->due_completed_count);
    }

    public function test_it_resolves_the_active_period_and_defaults_to_the_current_month(): void
    {
        $this->assertSame(['2026-11-01', '2026-11-30'], TaskAssignmentSummaryService::resolveDateRange([]));
        $this->assertSame(['2026-02-01', '2026-02-28'], TaskAssignmentSummaryService::resolveDateRange(['month' => '2026-02']));
        $this->assertSame(['2026-11-01', '2026-11-30'], TaskAssignmentSummaryService::resolveDateRange(['month' => '2026-13']));
        $this->assertSame(
            ['2026-10-05', '2026-10-20'],
            TaskAssignmentSummaryService::resolveDateRange(['date_from' => '2026-10-05', 'date_to' => '2026-10-20'])
        );
        $this->assertArrayHasKey('2026-11', TaskAssignmentSummaryService::monthOptions());
    }

    public function test_scope_isolates_branches_companies_and_details(): void
    {
        $staff = $this->staff(['view_any_task_assignment'], company: 1, branch: 1);
        $local = (int) $staff->employee->id;
        $remote = $this->employee('Remote branch employee', 1, 2);
        $otherCompany = $this->employee('Other company employee', 2, 3);

        $this->assignment($local);
        $this->assignment($remote);
        $this->assignment($otherCompany);

        $ids = TaskAssignmentSummaryService::summaryQuery(['month' => '2026-11'])->pluck('id')->all();

        $this->assertContains($local, $ids);
        $this->assertContains((int) $staff->employee->id, $ids);
        $this->assertNotContains($remote, $ids);
        $this->assertNotContains($otherCompany, $ids);
        $this->assertSame(0, TaskAssignmentSummaryService::summaryQuery(['month' => '2026-11', 'company_id' => 2])->count());

        $this->assertFalse(TaskAssignmentSummaryService::canAccessEmployee(Employee::findOrFail($remote)));
        $this->assertTrue(TaskAssignmentSummaryService::canAccessEmployee(Employee::findOrFail($local)));

        try {
            TaskAssignmentSummaryService::employeeDetails($remote, ['month' => '2026-11']);
            $this->fail('Out-of-scope employee details must abort with 404.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_summary_page_and_details_modal_use_the_same_scoped_data(): void
    {
        $staff = $this->staff(['view_any_task_assignment'], company: 1, branch: 1);
        $local = $this->employee('Local employee', 1, 1);
        $remote = $this->employee('Remote employee', 1, 2);
        $task = $this->task('Visible task');
        $this->item($this->assignment($local), $task, [
            'status' => 'completed', 'due_date' => '2026-11-10', 'completed_at' => '2026-11-09', 'weight' => 2, 'score' => 2,
        ]);
        $this->item($this->assignment($remote), $this->task('Hidden task'), [
            'status' => 'pending', 'due_date' => '2026-11-10',
        ]);

        Livewire::actingAs($staff)->test(TaskAssignmentsSummary::class)
            ->assertOk()
            ->assertSee('Local employee')
            ->assertDontSee('Remote employee')
            ->call('openDetails', $local)
            ->assertDispatched('open-modal', id: 'task-assignment-details')
            ->assertSee('Visible task', escape: false)
            ->call('exportCsv')
            ->assertFileDownloaded('task-assignments-summary-2026-11-12.csv')
            ->call('openDetails', $remote)
            ->assertNotFound();
    }

    public function test_mytasks_and_employee_performance_share_the_same_statement(): void
    {
        $employeeUser = User::factory()->create([
            'name' => 'Saudi Employee',
            'user_type' => 'saudi_employee',
            'status' => 'active',
        ]);
        $employee = $this->employee('Saudi Employee', 1, 1, $employeeUser, 'saudi_employee');
        $other = $this->employee('Other employee', 1, 1);
        $task = $this->task('My task');
        $this->item($this->assignment($employee), $task, [
            'status' => 'completed', 'due_date' => '2026-11-10', 'completed_at' => '2026-11-09', 'weight' => 1, 'score' => 1,
        ]);
        $this->item($this->assignment($other), $this->task('Not mine'), [
            'status' => 'pending', 'due_date' => '2026-11-10',
        ]);

        Livewire::actingAs($employeeUser)->test(MyTasks::class)
            ->assertOk()
            ->assertSee('My task')
            ->assertDontSee('Not mine')
            ->set('month', '2026-10')
            ->assertSet('month', '2026-10')
            ->assertDontSee('My task');

        Livewire::actingAs($this->superAdmin())->test(EmployeePerformance::class, ['record' => $employee])
            ->assertOk()
            ->assertSee('My task')
            ->assertDontSee('Not mine');

        // A Saudi employee record is not available through the foreign employee entry point.
        $this->actingAs($this->superAdmin());

        try {
            Livewire::test(
                \App\Filament\Resources\ForeignEmployees\Pages\EmployeePerformance::class,
                ['record' => $employee]
            );
            $this->fail('The foreign employee page must not resolve a Saudi employee.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_supervisor_can_read_team_profiles_and_only_sees_assignments_inside_the_shift(): void
    {
        $supervisor = $this->staff(['view_any_task_assignment', 'view_any_saudi_employee']);
        $supervisor->forceFill(['user_type' => 'supervisor'])->save();
        $member = $this->employee('Shift member', 1, 1);
        $outsider = $this->employee('Same-branch outsider', 1, 1);
        $undated = $this->employee('Incomplete shift member', 1, 1);
        $this->shift($supervisor, [$member]);
        $this->shift($supervisor, [$undated], end: null);

        $inside = $this->assignment($member);
        DB::table('task_assignments')->where('id', $inside)->update(['assignment_date' => '2026-11-06']);
        $this->item($inside, $this->task('Inside shift task'), ['due_date' => '2026-11-08']);
        $this->item($this->assignment($member), $this->task('Outside shift task'), ['due_date' => '2026-11-08']);
        $this->item($this->assignment($outsider), $this->task('Outsider task'), ['due_date' => '2026-11-08']);

        $filters = ['month' => '2026-11'];
        $this->assertEquals([$member], TaskAssignmentSummaryService::summaryQuery($filters)->pluck('employees.id')->all());
        $this->assertSame(1, (int) TaskAssignmentSummaryService::stats($filters)->total_items);
        $this->assertSame(['Inside shift task'], TaskAssignmentSummaryService::employeeItems($member, $filters)->pluck('task.name')->all());

        $this->assertFalse($supervisor->can('update_saudi_employee'));
        $this->get(SaudiEmployeeResource::getUrl('view', ['record' => $member]))
            ->assertOk()
            ->assertSee(SaudiEmployeeResource::getUrl('performance', ['record' => $member]), false);
        $this->get(SaudiEmployeeResource::getUrl('performance', ['record' => $member]))
            ->assertOk()
            ->assertSee('Inside shift task')
            ->assertDontSee('Outside shift task');
        $this->get(SaudiEmployeeResource::getUrl('view', ['record' => $outsider]))->assertForbidden();
        $this->get(SaudiEmployeeResource::getUrl('performance', ['record' => $outsider]))->assertForbidden();

        Livewire::test(TaskAssignmentsSummary::class)
            ->assertSee('Shift member')
            ->assertDontSee('Same-branch outsider')
            ->call('openDetails', $outsider)
            ->assertNotFound();
    }

    public function test_service_cards_filter_deficiencies_by_month_and_use_the_completion_date_for_shift_scope(): void
    {
        $supervisor = $this->staff(['view_any_service_transaction']);
        $supervisor->forceFill(['user_type' => 'supervisor'])->save();
        $member = Employee::findOrFail($this->employee('Service executor', 1, 1));
        $beneficiary = $this->employee('Service beneficiary', 1, 1);
        $this->shift($supervisor, [$member->id]);
        $inShift = $this->service($member->id, $beneficiary, 'SVC-IN', '2026-11-04', '2026-11-07');
        $this->service($member->id, $beneficiary, 'SVC-OUT', '2026-11-08', '2026-11-11');
        $this->service($beneficiary, $member->id, 'SVC-BENEFICIARY', '2026-11-08', '2026-11-08');

        foreach (['2026-10-10 12:00:00', '2026-11-04 10:00:00', '2026-11-09 15:00:00'] as $index => $createdAt) {
            DB::table('unified_deficiency_tasks')->insert([
                'company_id' => 1,
                'service_transaction_id' => $inShift,
                'task_code' => 'DEF-'.$index,
                'deficiency_type' => 'custody_handover',
                'title' => 'Pending handover',
                'responsible_employee_id' => $member->id,
                'status' => 'open',
                'created_at' => $createdAt,
            ]);
        }

        $filters = ['month' => '2026-11'];
        $category = ServicePerformanceSummaryService::CATEGORY_COMPLETED;
        $this->assertSame(['SVC-IN'], ServicePerformanceSummaryService::transactionsQuery($member->id, $filters, $category, $member)
            ->pluck('transaction_number')->all());
        $this->assertSame(1, ServicePerformanceSummaryService::stats($member->id, $filters, $member)->completed);
        $this->assertSame(['DEF-2'], ServicePerformanceSummaryService::assignedDeficienciesQuery($member->id, $filters, $member)
            ->pluck('task_code')->all());

        $this->superAdmin();
        $this->assertSame(2, ServicePerformanceSummaryService::stats($member->id, $filters, $member)->assigned_deficiencies);
        $this->assertSame(1, ServicePerformanceSummaryService::stats($member->id, ['month' => '2026-10'], $member)->assigned_deficiencies);
    }

    public function test_resolved_escalation_keeps_its_history_without_showing_as_pending(): void
    {
        $transaction = new ServiceTransaction([
            'service_status' => 'active',
            'workflow_status' => 'escalated',
            'metadata' => ['is_escalated' => false, 'escalated_at' => '2026-11-05 12:00:00'],
        ]);

        $this->assertSame('in_progress', $transaction->resolveWorkflowStatus());
        $this->assertNull(ServicePerformanceSummaryService::pendingAction($transaction)['key']);

        $transaction->metadata = ['is_escalated' => true, 'escalated_at' => '2026-11-05 12:00:00'];
        $this->assertSame('escalated', $transaction->resolveWorkflowStatus());
        $this->assertSame('escalated', ServicePerformanceSummaryService::pendingAction($transaction)['key']);
    }

    private function shift(User $supervisor, array $members, ?string $end = '2026-11-09'): void
    {
        DB::table('supervision_shifts')->insert([
            'supervisor_id' => $supervisor->employee->id,
            'company_id' => 1,
            'assigned_employees' => json_encode($members, JSON_THROW_ON_ERROR),
            'start_date' => '2026-11-05',
            'end_date' => $end,
            'status' => 'active',
        ]);
    }

    private function service(int $executor, int $beneficiary, string $number, string $due, string $completion): int
    {
        return DB::table('service_transactions')->insertGetId([
            'transaction_number' => $number,
            'transaction_date' => '2026-11-06',
            'company_id' => 1,
            'employee_id' => $beneficiary,
            'executed_by' => $executor,
            'service_status' => 'completed',
            'workflow_status' => 'completed',
            'due_date' => $due,
            'completion_date' => $completion,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['name' => 'Super Admin', 'user_type' => 'super_admin', 'status' => 'active']);

        $this->actingAs($user);

        return $user;
    }

    private function staff(array $permissions = [], int $company = 1, int $branch = 1): User
    {
        $user = User::factory()->create(['name' => 'Staff User', 'user_type' => 'saudi_employee', 'status' => 'active']);
        $this->employee('Staff User', $company, $branch, $user, 'saudi_employee');

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->actingAs($user);

        return $user->refresh();
    }

    private function employee(string $name, int $companyId, int $branchId, ?User $user = null, string $userType = 'saudi_employee'): int
    {
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

    private function assignment(int $employeeId): int
    {
        return DB::table('task_assignments')->insertGetId([
            'transaction_number' => 'TA-'.(++$this->assignmentSequence),
            'assignment_date' => '2026-11-01',
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
