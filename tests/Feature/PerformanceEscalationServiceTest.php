<?php

namespace Tests\Feature;

use App\Enums\PerformanceEscalationClosureReason;
use App\Enums\PerformanceEscalationStatus;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Filament\Resources\PerformanceEscalations\Pages\ListPerformanceEscalations;
use App\Filament\Resources\PerformanceEscalations\Tables\PerformanceEscalationsTable;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceEscalation;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Services\PerformanceEscalationService;
use App\Services\PerformanceScoreService;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceEscalationServiceTest extends TestCase
{
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['cache.default' => 'array']);
        config(['queue.default' => 'sync']);
        DB::purge();
        DB::reconnect('sqlite');

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_17_123833_create_permission_tables.php',
            '2026_09_21_110421_create_notifications_table.php',
        ] as $file) {
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
            $table->string('branch_code')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('department_id');
        });

        (require database_path('migrations/2026_11_15_100000_create_performance_escalations_table.php'))->up();

        (require database_path('migrations/2026_09_20_160000_create_tasks_and_weekly_notes_tables.php'))->up();

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::table('task_assignment_items', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Schema::table('owners', function (Blueprint $table) {
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
    }

    private function createCompany(string $name): Company
    {
        $this->seq++;

        return Company::create([
            'trade_name' => $name,
            'company_number' => 'C-'.(++$this->seq),
        ]);
    }

    private function createDepartment(string $name, ?int $companyId): Department
    {
        return Department::create([
            'company_id' => $companyId,
            'name' => $name,
            'department_code' => 'D-'.(++$this->seq),
        ]);
    }

    private function createUser(string $type, ?Company $company = null, ?int $branchId = null, array $permissions = []): User
    {
        $this->seq++;

        $user = User::create([
            'name' => "User {$this->seq}",
            'email' => "user{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $type,
        ]);

        if ($company !== null) {
            Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'employee_number' => 'EMP-'.(++$this->seq),
                'employment_type' => 'full_time',
                'hire_date' => '2026-01-01',
            ]);
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function createEmployee(Company $company, Department $department): Employee
    {
        $this->seq++;

        $user = User::create([
            'name' => "Employee {$this->seq}",
            'email' => "emp{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_number' => 'EMP-'.(++$this->seq),
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);
    }

    private function createOverdueTask(Company $company, Employee $employee, string $dueDate): TaskAssignmentItem
    {
        $task = Task::create(['name' => 'Task '.(++$this->seq)]);
        $assignment = TaskAssignment::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'assignment_date' => now()->toDateString(),
            'transaction_number' => 'TSK-'.(++$this->seq),
        ]);

        return TaskAssignmentItem::create([
            'task_assignment_id' => $assignment->id,
            'task_id' => $task->id,
            'due_date' => $dueDate,
            'status' => TaskStatus::PENDING->value,
        ]);
    }

    private function createDeficiency(Company $company, Employee $employee, string $dueDate): UnifiedDeficiencyTask
    {
        $transaction = ServiceTransaction::create([
            'transaction_number' => 'SRV-'.(++$this->seq),
            'transaction_date' => now()->toDateString(),
            'company_id' => $company->id,
        ]);

        return UnifiedDeficiencyTask::create([
            'company_id' => $company->id,
            'service_transaction_id' => $transaction->id,
            'task_code' => 'TSKD-'.(++$this->seq),
            'deficiency_type' => 'missing_document',
            'title' => 'Deficiency '.(++$this->seq),
            'responsible_employee_id' => $employee->id,
            'status' => 'open',
            'due_date' => $dueDate,
        ]);
    }

    private function service(): PerformanceEscalationService
    {
        return app(PerformanceEscalationService::class);
    }

    private function overdueDate(): string
    {
        return now()->copy()->subDay()->toDateString();
    }

    public function test_threshold_decision_rule_at_64_65_and_66(): void
    {
        $this->assertTrue(PerformanceEscalationService::shouldOpen(64, 65));
        $this->assertFalse(PerformanceEscalationService::shouldOpen(65, 65));
        $this->assertFalse(PerformanceEscalationService::shouldOpen(66, 65));
    }

    public function test_department_case_opens_from_deductions_recovers_and_reopens(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));

        $company = $this->createCompany('Escalation Co');
        $department = $this->createDepartment('Operations', $company->id);
        $employee = $this->createEmployee($company, $department);

        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }

        $summary = $this->service()->evaluateAll();

        $this->assertSame(1, $summary['opened']);
        $this->assertSame(0, $summary['closed']);

        $openCase = PerformanceEscalation::query()->open()->sole();
        $this->assertSame(PerformanceEscalation::SUBJECT_DEPARTMENT, $openCase->subject_type);
        $this->assertSame($department->id, $openCase->subject_id);
        $this->assertSame(PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL, $openCase->rule_key);
        $this->assertSame('64.00', $openCase->score_at_open);
        $this->assertSame(65, $openCase->threshold);
        $this->assertSame(PerformanceScoreService::RULES_VERSION, $openCase->rules_version);

        // Recovery on the next run: 8 overdue items = score 68.
        TaskAssignmentItem::query()->latest('id')->first()->delete();
        $summary = $this->service()->evaluateAll();

        $this->assertSame(0, $summary['opened']);
        $this->assertSame(1, $summary['closed']);

        $openCase->refresh();
        $this->assertSame(PerformanceEscalationStatus::CLOSED, $openCase->status);
        $this->assertSame(PerformanceEscalationClosureReason::RECOVERED, $openCase->closure_reason);
        $this->assertSame('68.00', $openCase->close_score);
        $this->assertNotNull($openCase->closed_at);
        $this->assertNull($openCase->open_slot);

        // A new drop opens a new case while the recovered one stays historical.
        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }
        $summary = $this->service()->evaluateAll();

        $this->assertSame(1, $summary['opened']);
        $this->assertSame(2, PerformanceEscalation::query()->count());
        $this->assertSame(1, PerformanceEscalation::query()->open()->count());
    }

    public function test_clean_department_opens_nothing(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $company = $this->createCompany('Clean Co');
        $this->createDepartment('Clean Dept', $company->id);

        $summary = $this->service()->evaluateAll();

        $this->assertSame(0, $summary['opened']);
        $this->assertSame(0, $summary['closed']);
        $this->assertSame(0, PerformanceEscalation::query()->count());
    }

    public function test_escalation_is_independent_of_viewer_branch_filters(): void
    {
        $company = $this->createCompany('Branch Co');
        $department = $this->createDepartment('Branch Dept', $company->id);
        $employee = $this->createEmployee($company, $department);

        // A branch-restricted viewer must not change the department-wide score.
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main']);
        $viewer = $this->createUser(UserType::SAUDI_EMPLOYEE->value, $company, $branch->id);
        $this->actingAs($viewer);

        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }

        $this->service()->evaluateAll();

        $this->assertSame(1, PerformanceEscalation::query()->open()->count());
        $this->assertSame('64.00', PerformanceEscalation::query()->open()->sole()->score_at_open);
    }

    public function test_open_snapshot_is_kept_after_later_score_changes(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $company = $this->createCompany('Snapshot Co');
        $department = $this->createDepartment('Snapshot Dept', $company->id);
        $employee = $this->createEmployee($company, $department);

        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }
        $this->service()->evaluateAll();

        for ($i = 0; $i < 2; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }
        $this->service()->evaluateAll();

        $case = PerformanceEscalation::query()->sole();
        $this->assertSame('64.00', $case->score_at_open);
        $this->assertSame(PerformanceScoreService::RULES_VERSION, $case->rules_version);
    }

    public function test_polymorphic_subject_and_rule_keys_are_separate(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $company = $this->createCompany('Polymorphic Co');
        $department = $this->createDepartment('Shared', $company->id);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Same Id Branch']);
        $this->assertSame($department->id, $branch->id, 'Both subjects intentionally share the same numeric id.');

        $score = PerformanceScoreService::scoreFromCounts(9, 0);

        $departmentCase = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $department->id,
            PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            $score,
        );
        $branchCase = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_BRANCH,
            $branch->id,
            'branch_operational',
            $score,
        );

        $this->assertNotNull($departmentCase);
        $this->assertNotNull($branchCase);
        $this->assertSame(2, PerformanceEscalation::query()->count());

        // A second independent rule for the same subject is also allowed.
        $secondRule = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $department->id,
            'department_financial',
            $score,
        );
        $this->assertNotNull($secondRule);
        $this->assertSame(2, PerformanceEscalation::query()
            ->where('subject_type', PerformanceEscalation::SUBJECT_DEPARTMENT)
            ->where('subject_id', $department->id)
            ->count());
    }

    public function test_subject_must_belong_to_the_company(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');
        $departmentA = $this->createDepartment('Dept A', $companyA->id);

        $this->assertFalse(PerformanceEscalationService::subjectBelongsToCompany(
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $departmentA->id,
            $companyB->id,
        ));
        $this->assertNull($this->service()->open(
            $companyB->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $departmentA->id,
            PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            PerformanceScoreService::scoreFromCounts(9, 0),
        ));
        $this->assertSame(0, PerformanceEscalation::query()->count());
    }

    public function test_shared_department_is_isolated_per_company(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $companyA = $this->createCompany('Shared A');
        $companyB = $this->createCompany('Shared B');
        $department = $this->createDepartment('Global Shared', null);
        $department->companies()->sync([$companyA->id, $companyB->id]);

        $employeeA = $this->createEmployee($companyA, $department);
        $this->createEmployee($companyB, $department);

        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($companyA, $employeeA, $this->overdueDate());
        }

        $this->service()->evaluateAll();

        $this->assertSame(1, PerformanceEscalation::query()->count());
        $this->assertSame($companyA->id, (int) PerformanceEscalation::query()->sole()->company_id);
    }

    public function test_duplicate_open_for_same_case_is_prevented(): void
    {
        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $company = $this->createCompany('Duplicate Co');
        $department = $this->createDepartment('Duplicate Dept', $company->id);
        $score = PerformanceScoreService::scoreFromCounts(9, 0);

        $first = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $department->id,
            PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            $score,
        );
        $second = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $department->id,
            PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            $score,
        );

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, PerformanceEscalation::query()->count());
    }

    public function test_management_action_keeps_the_same_case_open_until_recovery(): void
    {
        $admin = $this->createUser(UserType::SUPER_ADMIN->value);
        $this->actingAs($admin);
        $company = $this->createCompany('Manual Co');
        $department = $this->createDepartment('Manual Dept', $company->id);
        $employee = $this->createEmployee($company, $department);

        $items = collect(range(1, 9))->map(fn () => $this->createOverdueTask($company, $employee, $this->overdueDate()));
        $this->service()->evaluateAll();
        $case = PerformanceEscalation::query()->open()->sole();

        $this->assertTrue($this->service()->recordManagementAction($case, $admin, 'Reprimand and plan'));
        $case->refresh();
        $this->assertSame('Reprimand and plan', $case->management_action);
        $this->assertSame($admin->id, $case->management_action_by_user_id);
        $this->assertNotNull($case->management_action_at);

        $this->assertSame(0, $this->service()->evaluateAll()['opened']);
        $this->assertTrue(PerformanceEscalation::query()->open()->sole()->is($case));
        $this->assertSame(1, $admin->notifications()->where('data->event_key', 'PerformanceEscalationOpened')->count());

        $items->first()->update(['status' => TaskStatus::COMPLETED->value]);
        $this->assertSame(1, $this->service()->evaluateAll()['closed']);
        $case->refresh();
        $this->assertSame(PerformanceEscalationStatus::CLOSED, $case->status);
        $this->assertSame(PerformanceEscalationClosureReason::RECOVERED, $case->closure_reason);
        $this->assertSame('Reprimand and plan', $case->management_action);
        $this->assertNotNull($case->closed_at);
        $this->assertNull($case->open_slot);
    }

    public function test_view_permissions_respect_company_and_scope(): void
    {
        $company = $this->createCompany('Permission Co');
        $otherCompany = $this->createCompany('Other Co');
        $department = $this->createDepartment('Permission Dept', $company->id);
        $otherDepartment = $this->createDepartment('Other Dept', $otherCompany->id);

        $score = PerformanceScoreService::scoreFromCounts(9, 0);

        $case = PerformanceEscalation::create([
            'company_id' => $company->id,
            'subject_type' => PerformanceEscalation::SUBJECT_DEPARTMENT,
            'subject_id' => $department->id,
            'rule_key' => PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            'status' => PerformanceEscalationStatus::OPEN,
            'score_at_open' => $score->score,
            'threshold' => 65,
            'rules_version' => $score->rules_version,
            'opened_at' => now(),
            'open_slot' => 1,
        ]);

        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch']);
        $restricted = $this->createUser(
            UserType::SAUDI_EMPLOYEE->value,
            $company,
            $branch->id,
            ['view_any_performance_escalation', 'resolve_performance_escalation'],
        );
        $this->actingAs($restricted);
        $this->assertFalse(Gate::allows('viewAny', PerformanceEscalation::class));
        $this->assertFalse(Gate::allows('view', $case));

        $allowed = $this->createUser(
            UserType::SAUDI_EMPLOYEE->value,
            $company,
            null,
            ['view_any_performance_escalation', 'resolve_performance_escalation'],
        );
        $this->actingAs($allowed);
        $this->assertTrue(Gate::allows('viewAny', PerformanceEscalation::class));
        $this->assertTrue(Gate::allows('view', $case));
        $this->assertTrue(Gate::allows('manage', $case));

        $otherCase = PerformanceEscalation::create([
            'company_id' => $otherCompany->id,
            'subject_type' => PerformanceEscalation::SUBJECT_DEPARTMENT,
            'subject_id' => $otherDepartment->id,
            'rule_key' => PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            'status' => PerformanceEscalationStatus::OPEN,
            'score_at_open' => $score->score,
            'threshold' => 65,
            'rules_version' => $score->rules_version,
            'opened_at' => now(),
            'open_slot' => 1,
        ]);
        $this->assertFalse(Gate::allows('view', $otherCase));
        $this->assertFalse(Gate::allows('manage', $otherCase));

        $this->actingAs($this->createUser(UserType::SUPER_ADMIN->value));
        $this->assertTrue(Gate::allows('view', $otherCase));
    }

    public function test_notifications_go_to_super_admin_owners_and_permitted_users_in_scope_only(): void
    {
        $company = $this->createCompany('Notify Co');
        $otherCompany = $this->createCompany('Notify Other');
        $department = $this->createDepartment('Notify Dept', $company->id);
        $employee = $this->createEmployee($company, $department);

        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $owner = $this->createUser(UserType::OWNER->value);
        $owner->owners()->create(['company_id' => $company->id]);
        $otherOwner = $this->createUser(UserType::OWNER->value, permissions: ['view_any_performance_escalation']);
        $otherOwner->owners()->create(['company_id' => $otherCompany->id]);
        $permitted = $this->createUser(
            UserType::SAUDI_EMPLOYEE->value,
            $company,
            null,
            ['view_any_performance_escalation'],
        );
        $outside = $this->createUser(
            UserType::SAUDI_EMPLOYEE->value,
            $otherCompany,
            null,
            ['view_any_performance_escalation'],
        );
        $plain = $this->createUser(UserType::SAUDI_EMPLOYEE->value, $company);

        $this->actingAs($superAdmin);

        for ($i = 0; $i < 9; $i++) {
            $this->createOverdueTask($company, $employee, $this->overdueDate());
        }
        $this->service()->evaluateAll();

        foreach ([$superAdmin, $owner, $permitted] as $recipient) {
            $notification = $recipient->notifications()->where('data->event_key', 'PerformanceEscalationOpened')->sole();
            $this->assertSame(User::class, $notification->notifiable_type);
            $this->assertSame('persistent', $notification->data['duration']);
        }

        foreach ([$outside, $plain, $otherOwner] as $excluded) {
            $this->assertFalse($excluded->notifications()->where('data->event_key', 'PerformanceEscalationOpened')->exists());
        }

        $case = PerformanceEscalation::query()->open()->sole();
        $this->assertNotNull($case->notified_at);
        $ownerAlert = $owner->notifications()->where('data->event_key', 'PerformanceEscalationOpened')->sole();
        $this->assertSame($case->id, $ownerAlert->data['record_id']);
        $this->assertStringContainsString('/performance-escalations/'.$case->id, $ownerAlert->data['action_url'] ?? '');
        $this->assertTrue(PerformanceEscalation::query()->visibleTo($owner)->sole()->is($case));
        $this->assertFalse(PerformanceEscalation::query()->visibleTo($otherOwner)->exists());
    }

    public function test_company_filter_options_use_the_same_company_scope_as_cases(): void
    {
        $company = $this->createCompany('Allowed company');
        $otherCompany = $this->createCompany('Other company');
        $owner = $this->createUser(UserType::OWNER->value);
        $owner->owners()->create(['company_id' => $company->id]);
        $staff = $this->createUser(
            UserType::SAUDI_EMPLOYEE->value,
            $company,
            permissions: ['view_any_performance_escalation'],
        );
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $table = PerformanceEscalationsTable::configure(Table::make(new ListPerformanceEscalations));
        $filter = $table->getFilter('company_id');

        foreach ([$owner, $staff] as $viewer) {
            $this->actingAs($viewer);
            $this->assertSame([$company->id => $company->trade_name], $filter->getOptions());
        }

        $this->actingAs($superAdmin);
        $this->assertSame([
            $company->id => $company->trade_name,
            $otherCompany->id => $otherCompany->trade_name,
        ], $filter->getOptions());
    }

    public function test_pending_notification_rolls_back_partial_delivery_and_retries_without_duplicates(): void
    {
        $company = $this->createCompany('Retry Co');
        $department = $this->createDepartment('Retry Dept', $company->id);
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $owner = $this->createUser(UserType::OWNER->value);
        $owner->owners()->create(['company_id' => $company->id]);
        $this->actingAs($superAdmin);

        $case = $this->service()->open(
            $company->id,
            PerformanceEscalation::SUBJECT_DEPARTMENT,
            $department->id,
            PerformanceEscalationService::RULE_DEPARTMENT_OPERATIONAL,
            PerformanceScoreService::scoreFromCounts(9, 0),
        );

        $failDelivery = true;
        Event::listen(NotificationSending::class, function () use (&$failDelivery): void {
            if ($failDelivery && DB::table('notifications')->exists()) {
                throw new RuntimeException('Delivery interrupted after the first notification.');
            }
        });
        Log::shouldReceive('error')->once();

        $this->assertSame(['sent' => 0, 'failed' => 1], $this->service()->notifyPending());
        $this->assertNull($case->refresh()->notified_at);
        $this->assertSame(0, DB::table('notifications')->count());

        $failDelivery = false;
        $this->assertSame(['sent' => 1, 'failed' => 0], $this->service()->notifyPending());
        $this->assertNotNull($case->refresh()->notified_at);
        $this->assertSame(['sent' => 0, 'failed' => 0], $this->service()->notifyPending());
        $this->assertSame(1, $superAdmin->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count());
    }
}
