<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\EmployeePerformanceAccessService;
use App\Services\PerformanceScoreService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceScoreServiceTest extends TestCase
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

        foreach (['0001_01_01_000000_create_users_table.php', '2026_09_17_123833_create_permission_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name')->nullable();
            $table->string('trade_name_en')->nullable();
            $table->string('company_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->string('department_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('department_id');
        });

        (require database_path('migrations/2026_09_20_160000_create_tasks_and_weekly_notes_tables.php'))->up();
        (require database_path('migrations/2026_11_18_151000_add_task_item_review_workflow.php'))->up();

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::table('task_assignment_items', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::create('unified_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->nullableMorphs('auditable');
            $table->foreignId('service_transaction_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('action_type')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

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
            $table->json('assigned_employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function actingSuperAdmin(): User
    {
        $this->seq++;

        $admin = User::create([
            'name' => 'Admin '.$this->seq,
            'email' => "admin{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        return $admin;
    }

    private function createCompany(string $name): Company
    {
        $this->seq++;

        return Company::create([
            'trade_name' => $name,
            'company_number' => 'C-'.(++$this->seq),
        ]);
    }

    private function createDepartment(string $name, int $companyId): Department
    {
        return Department::create([
            'company_id' => $companyId,
            'name' => $name,
            'department_code' => 'D-'.(++$this->seq),
        ]);
    }

    private function createEmployee(int $companyId, ?int $deptId = null, string $userType = UserType::SAUDI_EMPLOYEE->value): Employee
    {
        $this->seq++;

        $user = User::create([
            'name' => "Employee {$this->seq}",
            'email' => "emp{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $userType,
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'department_id' => $deptId,
            'employee_number' => 'EMP-'.(++$this->seq),
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);
    }

    private function createTaskItem(int $companyId, Employee $employee, string $dueDate, TaskStatus $status): TaskAssignmentItem
    {
        $this->seq++;

        $task = Task::create(['name' => "Task {$this->seq}"]);
        $assignment = TaskAssignment::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'assignment_date' => now()->toDateString(),
            'transaction_number' => 'TSK-'.(++$this->seq),
        ]);

        return TaskAssignmentItem::create([
            'task_assignment_id' => $assignment->id,
            'task_id' => $task->id,
            'due_date' => $dueDate,
            'status' => $status->value,
            'requires_review' => false,
        ]);
    }

    private function createDeficiency(int $companyId, ?Employee $responsible, string $dueDate, string $status): UnifiedDeficiencyTask
    {
        $this->seq++;

        $transaction = ServiceTransaction::create([
            'transaction_number' => 'SRV-'.(++$this->seq),
            'transaction_date' => now()->toDateString(),
            'company_id' => $companyId,
        ]);

        return UnifiedDeficiencyTask::create([
            'company_id' => $companyId,
            'service_transaction_id' => $transaction->id,
            'task_code' => 'TSKD-'.(++$this->seq),
            'deficiency_type' => 'missing_document',
            'title' => "Deficiency {$this->seq}",
            'responsible_employee_id' => $responsible?->id,
            'status' => $status,
            'due_date' => $dueDate,
        ]);
    }

    public function test_clean_scope_scores_100_and_reports_partial(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Clean');
        $this->createEmployee($company->id);

        $score = PerformanceScoreService::getScore();

        $this->assertTrue($score->available);
        $this->assertSame(100, $score->score);
        $this->assertSame(0, $score->total_deduction);
        $this->assertTrue($score->is_partial);
    }

    public function test_due_today_does_not_deduct_but_overdue_deducts(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Timing');
        $employee = $this->createEmployee($company->id);

        // Task and deficiency due today are not deducted yet
        $this->createTaskItem($company->id, $employee, now()->toDateString(), TaskStatus::PENDING);
        $this->createDeficiency($company->id, $employee, now()->toDateString(), 'open');

        $this->assertSame(100, PerformanceScoreService::getScore()->score);

        // Deduction starts the day after the due date
        $this->createTaskItem($company->id, $employee, now()->copy()->subDay()->toDateString(), TaskStatus::PENDING);
        $this->createDeficiency($company->id, $employee, now()->copy()->subDay()->toDateString(), 'open');
        $this->createDeficiency($company->id, $employee, now()->copy()->subDay()->toDateString(), 'open');

        $score = PerformanceScoreService::getScore();
        $this->assertSame(92, $score->score);
        $this->assertSame(8, $score->total_deduction);
        $this->assertEquals(4, $score->deductions->firstWhere('key', 'overdue_tasks')->points);
        $this->assertEquals(4, $score->deductions->firstWhere('key', 'overdue_deficiencies')->points);
    }

    public function test_closing_records_restores_points_in_next_read(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Recovery');
        $employee = $this->createEmployee($company->id);

        $item = $this->createTaskItem($company->id, $employee, now()->copy()->subDay()->toDateString(), TaskStatus::PENDING);
        $deficiency = $this->createDeficiency($company->id, $employee, now()->copy()->subDay()->toDateString(), 'open');

        $this->assertSame(94, PerformanceScoreService::getScore()->score);

        $item->update(['status' => TaskStatus::COMPLETED->value, 'completed_at' => now()]);
        $deficiency->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->assertSame(100, PerformanceScoreService::getScore()->score);
    }

    public function test_postponed_tasks_and_old_completed_items_do_not_deduct(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Excluded');
        $employee = $this->createEmployee($company->id);

        // Old items already completed / postponed long ago
        $this->createTaskItem($company->id, $employee, now()->copy()->subMonth()->toDateString(), TaskStatus::POSTPONED);
        $this->createTaskItem($company->id, $employee, now()->copy()->subMonth()->toDateString(), TaskStatus::COMPLETED);
        $this->createDeficiency($company->id, $employee, now()->copy()->subMonth()->toDateString(), 'resolved');

        $this->assertSame(100, PerformanceScoreService::getScore()->score);
    }

    public function test_score_never_goes_negative(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Flooded');
        $employee = $this->createEmployee($company->id);

        for ($i = 0; $i < 30; $i++) {
            $this->createTaskItem($company->id, $employee, now()->copy()->subDays($i + 1)->toDateString(), TaskStatus::PENDING);
        }

        $this->assertSame(0, PerformanceScoreService::getScore()->score);
    }

    public function test_company_and_department_scoping(): void
    {
        $this->actingSuperAdmin();
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');

        $deptA = $this->createDepartment('Dept A', $companyA->id);
        $deptB = $this->createDepartment('Dept B', $companyA->id);
        $empA = $this->createEmployee($companyA->id, $deptA->id);
        $empB = $this->createEmployee($companyA->id, $deptB->id);
        $empOther = $this->createEmployee($companyB->id);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createTaskItem($companyA->id, $empA, $overdue, TaskStatus::PENDING);
        $this->createTaskItem($companyA->id, $empB, $overdue, TaskStatus::PENDING);
        $this->createDeficiency($companyA->id, $empB, $overdue, 'open');
        $this->createTaskItem($companyB->id, $empOther, $overdue, TaskStatus::PENDING);

        $this->assertSame(96, PerformanceScoreService::getScore([], $companyA->id, $deptA->id)->score);
        $this->assertSame(94, PerformanceScoreService::getScore([], $companyA->id, $deptB->id)->score);
        $this->assertSame(90, PerformanceScoreService::getScore([], $companyA->id)->score);
        $this->assertSame(96, PerformanceScoreService::getScore([], $companyB->id)->score);
    }

    public function test_records_without_department_bucket_into_unassigned_scope(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('No Department');
        $employee = $this->createEmployee($company->id, null);

        $this->createTaskItem($company->id, $employee, now()->copy()->subDay()->toDateString(), TaskStatus::PENDING);

        $this->assertSame(96, PerformanceScoreService::getScore([], $company->id, 'unassigned')->score);
    }

    public function test_score_statements_match_deduction_counts_including_old_overdue(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Statements');
        $employee = $this->createEmployee($company->id);

        // One current-period overdue and one old overdue outside the report period
        $this->createTaskItem($company->id, $employee, now()->copy()->subDay()->toDateString(), TaskStatus::PENDING);
        $this->createTaskItem($company->id, $employee, now()->copy()->subMonths(2)->toDateString(), TaskStatus::PENDING);
        $this->createDeficiency($company->id, $employee, now()->copy()->subMonths(2)->toDateString(), 'open');

        $filters = ['company_id' => [$company->id]];
        $score = PerformanceScoreService::getScore($filters);

        $this->assertSame(2, $score->deductions->firstWhere('key', 'overdue_tasks')->count);
        $this->assertSame(1, $score->deductions->firstWhere('key', 'overdue_deficiencies')->count);

        $tasksStatement = CompanyDepartmentPerformanceService::getTasksStatementQuery($filters, 'score_overdue');
        $deficienciesStatement = CompanyDepartmentPerformanceService::getDeficienciesStatementQuery($filters, 'score_overdue');

        $this->assertSame(2, $tasksStatement->count());
        $this->assertSame(1, $deficienciesStatement->count());
    }

    public function test_user_without_authorized_companies_gets_no_score(): void
    {
        $this->seq++;

        $plainUser = User::create([
            'name' => "Plain {$this->seq}",
            'email' => "plain{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::FOREIGN_EMPLOYEE->value,
        ]);
        $this->actingAs($plainUser);

        $score = PerformanceScoreService::getScore();

        $this->assertFalse($score->available);
        $this->assertNull($score->score);
    }

    public function test_supervisor_without_team_sees_no_other_deductions(): void
    {
        $company = $this->createCompany('Supervisor');
        $this->seq++;

        Permission::findOrCreate(EmployeePerformanceAccessService::SUPERVISE_PERMISSION, 'web');
        $supervisorUser = User::create([
            'name' => 'Supervisor',
            'email' => "sup{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
        ]);
        $supervisorUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supervisor = Employee::create([
            'user_id' => $supervisorUser->id,
            'company_id' => $company->id,
            'employee_number' => 'SUP-'.(++$this->seq),
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);
        $member = $this->createEmployee($company->id);
        $this->actingAs($supervisorUser);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createTaskItem($company->id, $member, $overdue, TaskStatus::PENDING);

        // A supervisor without a shift team sees no deductions from other company employees.
        $score = PerformanceScoreService::getScore();

        $this->assertSame(100, $score->score);
        $this->assertSame(0, $score->deductions->firstWhere('key', 'overdue_tasks')->count);
    }

    public function test_departments_overview_scores_each_department_type_by_its_scope(): void
    {
        $this->actingSuperAdmin();
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');
        $companyC = $this->createCompany('Company C');
        $overdue = now()->copy()->subDay()->toDateString();

        $shared = Department::create(['name' => 'Shared', 'is_global' => true, 'department_code' => 'D-S']);
        $shared->companies()->attach([$companyA->id, $companyB->id]);
        $group = Department::create(['name' => 'Group', 'is_global' => true, 'department_code' => 'D-G']);
        $single = $this->createDepartment('Single', $companyC->id);

        $sharedA = $this->createEmployee($companyA->id, $shared->id);
        $sharedB = $this->createEmployee($companyB->id, $shared->id);
        $sharedUnlinked = $this->createEmployee($companyC->id, $shared->id);
        $groupA = $this->createEmployee($companyA->id, $group->id);
        $groupC = $this->createEmployee($companyC->id, $group->id);
        $singleC = $this->createEmployee($companyC->id, $single->id);

        $this->createTaskItem($companyA->id, $sharedA, $overdue, TaskStatus::PENDING);
        $this->createDeficiency($companyB->id, $sharedB, $overdue, 'open');
        $this->createDeficiency($companyB->id, $sharedB, $overdue, 'open');
        $this->createTaskItem($companyC->id, $sharedUnlinked, $overdue, TaskStatus::PENDING);
        $this->createTaskItem($companyC->id, $groupC, $overdue, TaskStatus::PENDING);
        $this->createTaskItem($companyC->id, $singleC, $overdue, TaskStatus::PENDING);

        $rows = CompanyDepartmentPerformanceService::getDepartmentsOverview()->keyBy('department_id');

        // Shared: 1 task in A + 2 deficiencies in B; the unlinked company C is ignored.
        $this->assertSame(92, $rows[$shared->id]->score->score);
        $this->assertSame(2, $rows[$shared->id]->companies->count());
        $this->assertSame(2, $rows[$shared->id]->employees_count);
        $this->assertSame(96, $rows[$shared->id]->lowest_company->score->score);

        // Group-wide: derived from employees in any scoped company, no links needed.
        $this->assertSame(96, $rows[$group->id]->score->score);
        $this->assertSame(2, $rows[$group->id]->companies->count());
        $this->assertTrue($rows[$group->id]->affiliated_company_names->isEmpty());

        // Single: only its own company.
        $this->assertSame(96, $rows[$single->id]->score->score);
        $this->assertSame([$companyC->id], $rows[$single->id]->companies->pluck('company_id')->all());
        $this->assertSame(['Company C'], $rows[$single->id]->affiliated_company_names->all());

        // Narrowing to one linked company reproduces the per-company score.
        $filtered = CompanyDepartmentPerformanceService::getDepartmentsOverview(['company_id' => [$companyB->id]])->keyBy('department_id');
        $this->assertSame(96, $filtered[$shared->id]->score->score);
        $this->assertSame(['Company A', 'Company B'], $filtered[$shared->id]->affiliated_company_names->all());
        $this->assertSame([$companyB->id], $filtered[$shared->id]->companies->pluck('company_id')->all());
        $this->assertArrayNotHasKey($single->id, $filtered->all());
        $this->assertArrayNotHasKey($group->id, $filtered->all());

        $this->actingAs($groupA->user);
        $restricted = CompanyDepartmentPerformanceService::getDepartmentsOverview()->keyBy('department_id');
        $this->assertSame(['Company A'], $restricted[$shared->id]->affiliated_company_names->all());
    }

    public function test_departments_overview_statements_match_card_counts_and_do_not_double_count(): void
    {
        $this->actingSuperAdmin();
        $company = $this->createCompany('Statements');
        $department = $this->createDepartment('Ops', $company->id);
        $employee = $this->createEmployee($company->id, $department->id);
        $overdue = now()->copy()->subDay()->toDateString();

        $this->createTaskItem($company->id, $employee, $overdue, TaskStatus::PENDING);
        $this->createDeficiency($company->id, $employee, $overdue, 'open');

        $row = CompanyDepartmentPerformanceService::getDepartmentsOverview()->firstWhere('department_id', $department->id);

        $this->assertSame(1, $row->overdue_tasks);
        $this->assertSame(1, $row->overdue_deficiencies);
        $this->assertSame(
            $row->overdue_tasks,
            CompanyDepartmentPerformanceService::getTasksStatementQuery([], 'score_overdue', $company->id, $department->id)->count()
        );
        $this->assertSame(
            $row->overdue_deficiencies,
            CompanyDepartmentPerformanceService::getDeficienciesStatementQuery([], 'score_overdue', $company->id, $department->id)->count()
        );
    }

    public function test_departments_overview_is_empty_without_authorized_companies(): void
    {
        $this->seq++;
        $plainUser = User::create([
            'name' => "Plain {$this->seq}",
            'email' => "plain{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::FOREIGN_EMPLOYEE->value,
        ]);
        $company = $this->createCompany('Hidden');
        $department = $this->createDepartment('Hidden Dept', $company->id);
        $this->createEmployee($company->id, $department->id);
        $this->actingAs($plainUser);

        $this->assertTrue(CompanyDepartmentPerformanceService::getDepartmentsOverview()->isEmpty());
    }

    public function test_supervisor_shift_limits_score_to_assigned_team(): void
    {
        $company = $this->createCompany('Shift Team');
        $this->seq++;

        Permission::findOrCreate(EmployeePerformanceAccessService::SUPERVISE_PERMISSION, 'web');
        $supervisorUser = User::create([
            'name' => 'Shift Supervisor',
            'email' => "shift{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
        ]);
        $supervisorUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supervisor = Employee::create([
            'user_id' => $supervisorUser->id,
            'company_id' => $company->id,
            'employee_number' => 'SUP-'.(++$this->seq),
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);
        $teamMember = $this->createEmployee($company->id);
        $outsider = $this->createEmployee($company->id);
        $this->actingAs($supervisorUser);

        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'assigned_employees' => [$teamMember->id],
            'start_date' => now()->copy()->startOfMonth()->toDateString(),
            'end_date' => now()->copy()->endOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createTaskItem($company->id, $teamMember, $overdue, TaskStatus::PENDING);
        $this->createTaskItem($company->id, $outsider, $overdue, TaskStatus::PENDING);
        $this->createTaskItem($company->id, $outsider, $overdue, TaskStatus::PENDING);

        $score = PerformanceScoreService::getScore();

        $this->assertSame(96, $score->score);
        $this->assertSame(1, $score->deductions->firstWhere('key', 'overdue_tasks')->count);
    }
}
