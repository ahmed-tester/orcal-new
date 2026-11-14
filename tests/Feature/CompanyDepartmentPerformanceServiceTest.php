<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use App\Services\CompanyDepartmentPerformanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompanyDepartmentPerformanceServiceTest extends TestCase
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

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

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
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('department_id');
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

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
            $table->foreignId('employee_id')->nullable(); // beneficiary
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
            $table->foreignId('executed_by')->nullable(); // executor
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

    private function createCompany(string $name): Company
    {
        return Company::create([
            'trade_name' => $name,
            'company_number' => 'C-'.(++$this->seq),
        ]);
    }

    private function createDepartment(string $name, ?int $companyId = null, ?int $parentId = null): Department
    {
        return Department::create([
            'company_id' => $companyId,
            'parent_id' => $parentId,
            'name' => $name,
            'department_code' => 'D-'.(++$this->seq),
        ]);
    }

    private function createEmployee(
        int $companyId,
        ?int $deptId = null,
        ?int $branchId = null,
        string $userType = 'saudi_employee',
        ?string $name = null
    ): Employee {
        $this->seq++;
        $user = User::create([
            'name' => $name ?? "User {$this->seq}",
            'email' => "user{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $userType,
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'department_id' => $deptId,
            'branch_id' => $branchId,
            'employee_number' => "EMP-{$this->seq}",
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);
    }

    public function test_companies_summary_aggregates_kpis_correctly(): void
    {
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $empA = $this->createEmployee($companyA->id);
        $empB = $this->createEmployee($companyB->id);

        $now = now();
        $date = $now->format('Y-m-15');

        // Service on company A: 1 active, 1 completed
        ServiceTransaction::create([
            'transaction_number' => 'SRV-1',
            'transaction_date' => $date,
            'company_id' => $companyA->id,
            'executed_by' => $empA->id,
            'service_status' => 'active',
        ]);

        ServiceTransaction::create([
            'transaction_number' => 'SRV-2',
            'transaction_date' => $date,
            'completion_date' => $date,
            'company_id' => $companyA->id,
            'executed_by' => $empA->id,
            'service_status' => 'completed',
        ]);

        // Service on company B: 1 active overdue
        ServiceTransaction::create([
            'transaction_number' => 'SRV-3',
            'transaction_date' => $date,
            'due_date' => $now->copy()->subDays(2)->format('Y-m-d'),
            'company_id' => $companyB->id,
            'executed_by' => $empB->id,
            'service_status' => 'active',
        ]);

        $summary = CompanyDepartmentPerformanceService::getCompaniesSummary();

        $this->assertCount(2, $summary);
        $rowA = $summary->firstWhere('company_id', $companyA->id);
        $rowB = $summary->firstWhere('company_id', $companyB->id);

        $this->assertEquals(1, $rowA->active_services);
        $this->assertEquals(1, $rowA->completed_services);
        $this->assertEquals(0, $rowA->overdue_services);

        $this->assertEquals(1, $rowB->active_services);
        $this->assertEquals(1, $rowB->overdue_services);
        $this->assertEquals(0, $rowB->completed_services);

        $matching = CompanyDepartmentPerformanceService::getCompaniesSummary(['search' => $companyA->trade_name]);

        $this->assertCount(1, $matching);
        $this->assertEquals($rowA, $matching->first());
        $this->assertTrue(CompanyDepartmentPerformanceService::getCompaniesSummary(['search' => 'unmatched company'])->isEmpty());
    }

    public function test_departments_summary_isolates_shared_departments_by_company(): void
    {
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        // Shared department linked to both companies
        $dept = Department::create([
            'name' => 'Support',
            'is_global' => true,
        ]);
        DB::table('company_department')->insert([
            ['company_id' => $companyA->id, 'department_id' => $dept->id],
            ['company_id' => $companyB->id, 'department_id' => $dept->id],
        ]);

        $empA = $this->createEmployee($companyA->id, $dept->id);
        $empB = $this->createEmployee($companyB->id, $dept->id);

        $date = now()->format('Y-m-10');

        // Transaction in Company A executed by empA
        ServiceTransaction::create([
            'transaction_number' => 'SRV-A',
            'transaction_date' => $date,
            'company_id' => $companyA->id,
            'executed_by' => $empA->id,
            'service_status' => 'active',
        ]);

        // Transaction in Company B executed by empB
        ServiceTransaction::create([
            'transaction_number' => 'SRV-B',
            'transaction_date' => $date,
            'company_id' => $companyB->id,
            'executed_by' => $empB->id,
            'service_status' => 'active',
        ]);

        $summaryA = CompanyDepartmentPerformanceService::getDepartmentsSummary($companyA->id);
        $summaryB = CompanyDepartmentPerformanceService::getDepartmentsSummary($companyB->id);

        $rowA = $summaryA->firstWhere('department_id', $dept->id);
        $rowB = $summaryB->firstWhere('department_id', $dept->id);

        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);
        $this->assertEquals(1, $rowA->active_services);
        $this->assertEquals(1, $rowB->active_services);
    }

    public function test_sub_departments_display_with_parent_name(): void
    {
        $company = $this->createCompany('Main Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $parent = $this->createDepartment('الشؤون الإدارية', $company->id);
        $child = $this->createDepartment('الأرشيف', $company->id, $parent->id);

        $summary = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id);
        $childRow = $summary->firstWhere('department_id', $child->id);

        $this->assertNotNull($childRow);
        $this->assertEquals('الأرشيف (الشؤون الإدارية)', $childRow->department_name);
    }

    public function test_executor_department_attribution_separated_from_beneficiary(): void
    {
        $company = $this->createCompany('Main Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $deptExecutor = $this->createDepartment('Operations', $company->id);
        $deptBeneficiary = $this->createDepartment('HR', $company->id);

        $executor = $this->createEmployee($company->id, $deptExecutor->id);
        $beneficiary = $this->createEmployee($company->id, $deptBeneficiary->id);

        $date = now()->format('Y-m-12');

        ServiceTransaction::create([
            'transaction_number' => 'SRV-EXEC',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'employee_id' => $beneficiary->id, // client/beneficiary in HR
            'executed_by' => $executor->id,     // executor in Operations
            'service_status' => 'active',
        ]);

        $summary = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id);

        $execRow = $summary->firstWhere('department_id', $deptExecutor->id);
        $benRow = $summary->firstWhere('department_id', $deptBeneficiary->id);

        $this->assertEquals(1, $execRow->active_services);
        $this->assertEquals(0, $benRow->active_services);
    }

    public function test_unassigned_records_bucket_in_department_summary(): void
    {
        $company = $this->createCompany('Main Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $date = now()->format('Y-m-12');

        // Service without executor
        ServiceTransaction::create([
            'transaction_number' => 'SRV-NO-EXEC',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'executed_by' => null,
            'service_status' => 'active',
        ]);

        $summary = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id);
        $unassignedRow = $summary->firstWhere('is_unassigned', true);

        $this->assertNotNull($unassignedRow);
        $this->assertEquals(1, $unassignedRow->active_services);
    }

    public function test_on_time_completion_rate_math_avoids_percentage_averaging(): void
    {
        $company = $this->createCompany('Math Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $dept1 = $this->createDepartment('Dept 1', $company->id);
        $dept2 = $this->createDepartment('Dept 2', $company->id);

        $emp1 = $this->createEmployee($company->id, $dept1->id);
        $emp2 = $this->createEmployee($company->id, $dept2->id);

        $now = now();
        $date = $now->format('Y-m-10');

        // Dept 1: 9 on time out of 10 completed with due date
        for ($i = 1; $i <= 10; $i++) {
            ServiceTransaction::create([
                'transaction_number' => "SRV-D1-{$i}",
                'transaction_date' => $date,
                'due_date' => $date,
                'completion_date' => $i <= 9 ? $date : $now->copy()->addDay()->format('Y-m-d'),
                'company_id' => $company->id,
                'executed_by' => $emp1->id,
                'service_status' => 'completed',
            ]);
        }

        // Dept 2: 1 on time out of 2 completed with due date
        for ($i = 1; $i <= 2; $i++) {
            ServiceTransaction::create([
                'transaction_number' => "SRV-D2-{$i}",
                'transaction_date' => $date,
                'due_date' => $date,
                'completion_date' => $i <= 1 ? $date : $now->copy()->addDay()->format('Y-m-d'),
                'company_id' => $company->id,
                'executed_by' => $emp2->id,
                'service_status' => 'completed',
            ]);
        }

        // Overall for Company:
        // Numerator: 9 + 1 = 10
        // Denominator: 10 + 2 = 12
        // Correct rate: 10/12 = 83.3%
        // (Wrong average of percentages would be (90% + 50%) / 2 = 70%)
        $overall = CompanyDepartmentPerformanceService::getOverallStats();
        $this->assertEquals(83.3, $overall->on_time_rate);
    }

    public function test_branch_filtering_on_services_and_tasks(): void
    {
        $company = $this->createCompany('Branch Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $branchA = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Branch A']);
        $branchB = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Branch B']);

        $empBeneficiaryA = $this->createEmployee($company->id, null, $branchA);
        $empBeneficiaryB = $this->createEmployee($company->id, null, $branchB);
        $executor = $this->createEmployee($company->id);

        $date = now()->format('Y-m-15');

        // Service where beneficiary is in Branch A
        ServiceTransaction::create([
            'transaction_number' => 'SRV-BR-A',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'employee_id' => $empBeneficiaryA->id,
            'executed_by' => $executor->id,
            'service_status' => 'active',
        ]);

        // Service where beneficiary is in Branch B
        ServiceTransaction::create([
            'transaction_number' => 'SRV-BR-B',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'employee_id' => $empBeneficiaryB->id,
            'executed_by' => $executor->id,
            'service_status' => 'active',
        ]);

        // Filter by Branch A
        $statsA = CompanyDepartmentPerformanceService::getOverallStats(['branch_id' => $branchA]);
        $this->assertEquals(1, $statsA->active_services);

        // Filter by Branch B
        $statsB = CompanyDepartmentPerformanceService::getOverallStats(['branch_id' => $branchB]);
        $this->assertEquals(1, $statsB->active_services);
    }

    public function test_saudi_only_for_tasks_vs_foreign_for_services(): void
    {
        $company = $this->createCompany('Nationality Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $dept = $this->createDepartment('Tech', $company->id);

        $saudiEmp = $this->createEmployee($company->id, $dept->id, null, UserType::SAUDI_EMPLOYEE->value, 'Saudi Dev');
        $foreignEmp = $this->createEmployee($company->id, $dept->id, null, UserType::FOREIGN_EMPLOYEE->value, 'Foreign Dev');

        $now = now();
        $date = $now->format('Y-m-10');

        // Service executed by foreign employee
        ServiceTransaction::create([
            'transaction_number' => 'SRV-FOR-1',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'executed_by' => $foreignEmp->id,
            'service_status' => 'active',
        ]);

        // Task assigned to foreign employee (e.g. legacy/direct record)
        $task = Task::create(['name' => 'Test Task']);
        $assignForeign = TaskAssignment::create([
            'company_id' => $company->id,
            'employee_id' => $foreignEmp->id,
            'assignment_date' => $date,
            'transaction_number' => 'TSK-FOR-1',
        ]);
        TaskAssignmentItem::create([
            'task_assignment_id' => $assignForeign->id,
            'task_id' => $task->id,
            'due_date' => $date,
            'status' => TaskStatus::PENDING->value,
        ]);

        // Task assigned to saudi employee
        $assignSaudi = TaskAssignment::create([
            'company_id' => $company->id,
            'employee_id' => $saudiEmp->id,
            'assignment_date' => $date,
            'transaction_number' => 'TSK-SAU-1',
        ]);
        TaskAssignmentItem::create([
            'task_assignment_id' => $assignSaudi->id,
            'task_id' => $task->id,
            'due_date' => $date,
            'status' => TaskStatus::PENDING->value,
        ]);

        $summary = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id);
        $deptRow = $summary->firstWhere('department_id', $dept->id);

        // Service by foreign employee must be counted
        $this->assertEquals(1, $deptRow->active_services);

        // Tasks must ONLY count Saudi employee task, ignoring foreign employee task
        $this->assertEquals(1, $deptRow->task_due_in_period);

        $employees = CompanyDepartmentPerformanceService::getEmployeesSummary($company->id, $dept->id);
        $foreignRow = $employees->firstWhere('employee_id', $foreignEmp->id);

        $this->assertNotNull($foreignRow);
        $this->assertFalse($foreignRow->is_saudi);
        $this->assertNull($foreignRow->task_due_in_period);
        $this->assertNull($foreignRow->task_completion_rate);
    }

    public function test_supervisor_shift_scoping_and_team_badge(): void
    {
        $company = $this->createCompany('Supervision Company');

        $supervisorUser = User::create([
            'name' => 'Supervisor',
            'email' => 'sup@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPERVISOR->value,
        ]);
        $supervisorEmp = Employee::create([
            'user_id' => $supervisorUser->id,
            'company_id' => $company->id,
            'employee_number' => 'SUP-1',
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);

        $dept = $this->createDepartment('Support', $company->id);

        $teamMember = $this->createEmployee($company->id, $dept->id, null, UserType::SAUDI_EMPLOYEE->value, 'Team Member');
        $outsider = $this->createEmployee($company->id, $dept->id, null, UserType::SAUDI_EMPLOYEE->value, 'Outsider');

        $now = now();
        $date = $now->format('Y-m-10');

        // Supervision shift covering team member only
        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisorEmp->id,
            'assigned_employees' => [$teamMember->id],
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'status' => 'active',
        ]);

        // Service by team member
        ServiceTransaction::create([
            'transaction_number' => 'SRV-TEAM',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'executed_by' => $teamMember->id,
            'service_status' => 'active',
        ]);

        // Service by outsider
        ServiceTransaction::create([
            'transaction_number' => 'SRV-OUTSIDER',
            'transaction_date' => $date,
            'company_id' => $company->id,
            'executed_by' => $outsider->id,
            'service_status' => 'active',
        ]);

        $this->actingAs($supervisorUser);

        DB::enableQueryLog();
        try {
            $summary = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id);
            $shiftReads = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], 'supervision_shifts'));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $this->assertCount(1, $shiftReads);
        $deptRow = $summary->firstWhere('department_id', $dept->id);

        $this->assertNotNull($deptRow);
        $this->assertTrue($deptRow->is_team_scope);
        // Only sees team member's service
        $this->assertEquals(1, $deptRow->active_services);
    }

    public function test_kashf_queries_pagination_and_filtering(): void
    {
        $company = $this->createCompany('Kashf Company');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPER_ADMIN->value,
        ]);
        $this->actingAs($admin);

        $emp = $this->createEmployee($company->id);
        $date = now()->format('Y-m-10');

        for ($i = 1; $i <= 5; $i++) {
            ServiceTransaction::create([
                'transaction_number' => "SRV-KASHF-{$i}",
                'transaction_date' => $date,
                'company_id' => $company->id,
                'executed_by' => $emp->id,
                'service_status' => 'active',
            ]);
        }

        $query = CompanyDepartmentPerformanceService::getKashfServicesQuery([], 'active', $company->id);
        $paginator = $query->paginate(2);

        $this->assertEquals(5, $paginator->total());
        $this->assertCount(2, $paginator->items());

        $otherCompany = $this->createCompany('Other Company');
        ServiceTransaction::create([
            'transaction_number' => 'SRV-KASHF-OTHER',
            'transaction_date' => $date,
            'company_id' => $otherCompany->id,
            'service_status' => 'active',
        ]);
        $filters = ['company_id' => [$company->id]];

        $this->assertSame(5, CompanyDepartmentPerformanceService::getKashfServicesQuery($filters, 'active')->count());
        $this->assertSame(0, CompanyDepartmentPerformanceService::getKashfServicesQuery($filters, 'active', $otherCompany->id)->count());
    }

    public function test_workforce_overview_requires_a_summary_permission(): void
    {
        $company = $this->createCompany('Restricted Company');
        $employee = $this->createEmployee($company->id);
        $this->actingAs($employee->user);

        $page = new CompanyDepartmentPerformance;
        $this->assertFalse($page->canViewWorkforceOverview());

        $this->expectException(HttpException::class);
        $page->showWorkforceOverview();
    }

    public function test_attendance_totals_use_the_selected_month_and_team(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->date('date');
            $table->string('status');
            $table->integer('late_hours')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->decimal('total_hours')->default(0);
        });

        $company = $this->createCompany('Attendance Company');
        $admin = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $member = $this->createEmployee($company->id, null, null, UserType::SAUDI_EMPLOYEE->value);
        $other = $this->createEmployee($company->id, null, null, UserType::SAUDI_EMPLOYEE->value);
        $this->actingAs($admin->user);

        DB::table('attendance')->insert([
            ['employee_id' => $member->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::PRESENT->value],
            ['employee_id' => $member->id, 'date' => '2026-11-02', 'status' => AttendanceStatus::LATE->value],
            ['employee_id' => $member->id, 'date' => '2026-10-31', 'status' => AttendanceStatus::ABSENT->value],
            ['employee_id' => $other->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::ABSENT->value],
        ]);

        $filters = ['month' => '2026-11', 'company_id' => [$company->id]];
        $stats = AttendanceSummaryService::getStats($filters, [$member->id]);

        $this->assertSame(1, $stats['employees_count']);
        $this->assertSame(1, $stats['present_days']);
        $this->assertSame(1, $stats['late_days']);
        $this->assertSame(0, $stats['absent_days']);
        $this->assertSame(0, AttendanceSummaryService::getStats($filters, [])['recorded_days']);
    }
}
