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
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\PerformanceScoreService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
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
            $table->integer('sort_order')->default(0);
            $table->timestamps();
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

    private function createOverdueTask(Employee $employee, int $companyId, string $dueDate, TaskStatus $status = TaskStatus::PENDING): TaskAssignmentItem
    {
        $this->seq++;
        $task = Task::create(['name' => "Score Task {$this->seq}"]);
        $assignment = TaskAssignment::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'assignment_date' => now()->toDateString(),
            'transaction_number' => 'TSK-SCORE-'.(++$this->seq),
        ]);

        return TaskAssignmentItem::create([
            'task_assignment_id' => $assignment->id,
            'task_id' => $task->id,
            'due_date' => $dueDate,
            'status' => $status->value,
        ]);
    }

    private function createOverdueDeficiency(int $companyId, ?Employee $responsible, string $dueDate): UnifiedDeficiencyTask
    {
        $this->seq++;
        $transaction = ServiceTransaction::create([
            'transaction_number' => 'SRV-SCORE-'.(++$this->seq),
            'transaction_date' => now()->toDateString(),
            'company_id' => $companyId,
        ]);

        return UnifiedDeficiencyTask::create([
            'company_id' => $companyId,
            'service_transaction_id' => $transaction->id,
            'task_code' => 'DEF-SCORE-'.(++$this->seq),
            'deficiency_type' => 'missing_document',
            'title' => "Score Deficiency {$this->seq}",
            'responsible_employee_id' => $responsible?->id,
            'status' => 'open',
            'due_date' => $dueDate,
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

    public function test_page_filters_send_ids_and_clear_a_branch_from_another_company(): void
    {
        $companyA = $this->createCompany('Filter Company Alpha');
        $companyB = $this->createCompany('Filter Company Beta');
        $branchA = DB::table('branches')->insertGetId(['company_id' => $companyA->id, 'name' => 'Filter Branch Alpha']);
        $branchB = DB::table('branches')->insertGetId(['company_id' => $companyB->id, 'name' => 'Filter Branch Beta']);
        $admin = $this->createEmployee($companyA->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($admin->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class);

        $this->assertContains(['id' => $companyA->id, 'label' => $companyA->trade_name], $page->instance()->companyFilterOptions());
        $this->assertContains(['id' => $branchA, 'label' => 'Filter Branch Alpha'], $page->instance()->branchFilterOptions());

        $page->set('companyId', (string) $companyA->id)
            ->set('branchId', (string) $branchA)
            ->assertSet('branchId', (string) $branchA)
            ->assertSeeHtml('wire:key="company-card-'.$companyA->id.'"')
            ->assertDontSeeHtml('wire:key="company-card-'.$companyB->id.'"');

        $page->set('companyId', (string) $companyB->id)
            ->assertSet('branchId', null)
            ->assertSeeHtml('wire:key="company-card-'.$companyB->id.'"')
            ->assertDontSeeHtml('wire:key="company-card-'.$companyA->id.'"');

        $this->assertSame([['id' => $branchB, 'label' => 'Filter Branch Beta']], $page->instance()->branchFilterOptions());
    }

    public function test_performance_page_rejects_guests_and_non_super_admins(): void
    {
        $this->assertFalse(CompanyDepartmentPerformance::canAccess());

        $company = $this->createCompany('Restricted Company');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach ([UserType::OWNER, UserType::SAUDI_EMPLOYEE] as $type) {
            $viewer = $this->createEmployee($company->id, null, null, $type->value);
            $this->actingAs($viewer->user);

            Livewire::test(CompanyDepartmentPerformance::class)->assertForbidden();
        }
    }

    public function test_super_admin_opens_department_overview_and_statements_in_the_card_scope(): void
    {
        $linked = $this->createCompany('Linked Company');
        $linkedB = $this->createCompany('Linked Company B');
        $linkedC = $this->createCompany('Linked Company C');
        $unlinked = $this->createCompany('Unlinked Company');
        $branch = DB::table('branches')->insertGetId(['company_id' => $linked->id, 'name' => 'Visible Branch']);
        $viewer = $this->createEmployee($linked->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $department = Department::create(['name' => 'Shared Operations', 'is_global' => true]);
        $department->companies()->attach([$linked->id, $linkedB->id, $linkedC->id]);
        $visible = $this->createEmployee($linked->id, $department->id, $branch);
        $outsideBranch = $this->createEmployee($linked->id, $department->id);
        $outsideCompany = $this->createEmployee($unlinked->id, $department->id);
        $due = today()->subDay()->toDateString();
        $this->createOverdueTask($visible, $linked->id, $due);
        $this->createOverdueTask($outsideBranch, $linked->id, $due);
        $this->createOverdueTask($outsideCompany, $unlinked->id, $due);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->assertOk()
            ->call('selectOperationsViewMode', 'department')
            ->set('companyId', [(string) $linked->id])
            ->assertSeeHtml('wire:key="department-overview-card-'.$department->id.'"')
            ->assertSee('Linked Company · Linked Company B')
            ->assertSee(__('performance.department_view.show_all_companies'))
            ->call('openDepartmentDetails', $department->id)
            ->assertDispatched('open-modal', id: 'performance-department-details-modal')
            ->assertSee('Linked Company · Linked Company B · Linked Company C')
            ->call('openDepartmentScoreKashf', 'tasks', $linked->id, $department->id)
            ->assertDispatched('open-modal', id: 'performance-kashf-modal');

        $this->assertSame(2, $page->instance()->kashfRecords()->total());

        $page->set('branchId', (string) $branch);
        $this->assertSame(1, $page->instance()->departmentsOverview()->first()->overdue_tasks);

        $page->call('openDepartmentScoreKashf', 'tasks', $linked->id, $department->id);
        $this->assertSame(1, $page->instance()->kashfRecords()->total());

        $page->set('branchId', null)
            ->set('companyId', null)
            ->call('openKashf', 'tasks', 'score_overdue', $unlinked->id, $department->id)
            ->assertStatus(404);
    }

    public function test_department_overview_weights_indicators_by_volume_and_sorts_by_aggregate_score(): void
    {
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');
        $viewer = $this->createEmployee($companyA->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);

        $shared = Department::create(['name' => 'Shared Operations', 'is_global' => true]);
        $shared->companies()->attach([$companyA->id, $companyB->id]);
        $single = $this->createDepartment('Single Operations', $companyA->id);
        $employeeA = $this->createEmployee($companyA->id, $shared->id);
        $employeeB = $this->createEmployee($companyB->id, $shared->id);
        $singleEmployee = $this->createEmployee($companyA->id, $single->id);
        $due = today()->subDays(2)->toDateString();
        $completion = today()->subDay()->toDateString();
        $filters = ['date_from' => $due, 'date_to' => today()->toDateString()];

        foreach ([$employeeA, $employeeB, $singleEmployee] as $employee) {
            $this->createOverdueTask($employee, $employee->company_id, $due);
        }
        $this->createOverdueDeficiency($companyA->id, $singleEmployee, $due);
        $completed = $this->createOverdueTask($employeeA, $companyA->id, $due, TaskStatus::COMPLETED);
        $completed->update(['completed_at' => $completion]);

        foreach ([$employeeA, $employeeA, $employeeB] as $index => $employee) {
            ServiceTransaction::create([
                'transaction_number' => 'SRV-WEIGHTED-'.$index,
                'transaction_date' => $due,
                'completion_date' => $completion,
                'due_date' => $employee->is($employeeA) ? $completion : $due,
                'company_id' => $employee->company_id,
                'executed_by' => $employee->id,
                'service_status' => 'completed',
            ]);
        }

        $rows = CompanyDepartmentPerformanceService::getDepartmentsOverview($filters);
        $this->assertSame([$shared->id, $single->id], $rows->pluck('department_id')->all());
        $this->assertSame(92, $rows->first()->score->score);
        $this->assertSame(96, $rows->first()->lowest_company->score->score);
        $this->assertSame(66.7, $rows->first()->on_time_rate);
        $this->assertSame(33.3, $rows->first()->task_completion_rate);
    }

    public function test_violations_tab_renders_and_opens_the_payments_statement(): void
    {
        (require database_path('migrations/2026_10_01_100000_create_violations_tables.php'))->up();

        $company = $this->createCompany('Violations Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CompanyDepartmentPerformance::class)
            ->set('companyId', [(string) $company->id])
            ->call('showViolationsOverview')
            ->assertSet('activeOverviewTab', 'violations')
            ->assertSee(__('performance.violations.authorities_title'))
            ->assertDontSeeHtml('id="perf-vehicles-heading"')
            ->assertSee('companyId%5B0%5D='.$company->id)
            ->assertSeeHtml('wire:click="openKashf(&#039;violations&#039;, &#039;payments&#039;)"')
            ->call('openKashf', 'violations', 'payments')
            ->assertDispatched('open-modal', id: 'performance-kashf-modal')
            ->assertSee(__('performance.kashf.violations_title', ['type' => __('performance.violations.total_paid')]));
    }

    public function test_compliance_tab_opens_employee_and_company_statements(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('municipality_license_expiry')->nullable();
            $table->date('civil_defense_license_expiry')->nullable();
            $table->date('commercial_registration_expiry')->nullable();
        });

        $company = $this->createCompany('Licensed Company');
        DB::table('companies')->where('id', $company->id)->update([
            'municipality_license_expiry' => today()->addDays(60)->toDateString(),
        ]);
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $employee = $this->createEmployee($company->id, name: 'Employee With Expired Document');
        DB::table('employee_details')->insert(['employee_id' => $employee->id, 'iqama_expiry' => today()->subDay()->toDateString()]);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('companyId', (string) $company->id)
            ->call('showComplianceOverview')
            ->assertSee(__('performance.compliance.subscriptions_unavailable'))
            ->assertSee(__('performance.compliance.recorded_documents_share'))
            ->assertSeeHtml('style="--col: #e11d48; --p: 100;"')
            ->assertSeeHtml('<span class="ring-null">—</span>')
            ->assertSeeHtml('wire:click="selectCompliancePreview(&#039;iqama&#039;, &#039;iqama_expired&#039;)"')
            ->assertSeeHtml('wire:click="openKashf(\'compliance\', \'iqama_expired\')"')
            ->assertSeeHtml('wire:click="openKashf(\'compliance\', \'municipality_valid\')"')
            ->assertDontSeeHtml('wire:click="openKashf(\'compliance\', \'municipality_expired\')"')
            ->call('selectCompliancePreview', 'iqama', 'iqama_expired')
            ->assertSet('compliancePreviewTypes.iqama', 'iqama_expired')
            ->call('openKashf', 'compliance', 'iqama_expired')
            ->assertDispatched('open-modal', id: 'performance-kashf-modal');

        $employeeRecords = $page->instance()->kashfRecords()->getCollection();
        $this->assertInstanceOf(Employee::class, $employeeRecords->sole());
        $this->assertSame($employee->id, $employeeRecords->sole()->id);

        $page->call('openKashf', 'compliance', 'municipality_valid')
            ->assertSee(__('performance.kashf.compliance_title', [
                'type' => __('performance.compliance.license_status', [
                    'license' => __('performance.compliance.municipality'),
                    'status' => __('performance.compliance.valid'),
                ]),
            ]))
            ->assertSeeHtml('<span style="color: #059669;">');

        $companyRecords = $page->instance()->kashfRecords()->getCollection();
        $this->assertInstanceOf(Company::class, $companyRecords->sole());
        $this->assertSame($company->id, $companyRecords->sole()->id);
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

    public function test_attendance_statements_require_attendance_permission(): void
    {
        $company = $this->createCompany('Leave Only Company');
        $viewer = $this->createEmployee($company->id)->user;
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_leave']));
        $this->actingAs($viewer);

        $page = new CompanyDepartmentPerformance;
        $this->assertTrue($page->canViewWorkforceOverview());
        $this->expectException(HttpException::class);
        try {
            $page->openKashf('attendance', 'present_days');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_leave_statements_require_leave_permission(): void
    {
        $company = $this->createCompany('Attendance Only Company');
        $viewer = $this->createEmployee($company->id)->user;
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_attendance']));
        $this->actingAs($viewer);

        $page = new CompanyDepartmentPerformance;
        $this->assertTrue($page->canViewWorkforceOverview());
        $this->expectException(HttpException::class);
        $page->openKashf('leaves', 'on_leave_today_count');
    }

    public function test_leave_cards_and_statements_match_the_year_today_and_supervisor_scope(): void
    {
        (require database_path('migrations/2026_09_19_130000_create_attendance_and_leaves_tables.php'))->up();
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00'));

        $company = $this->createCompany('Leave Company');
        $branch = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Leave Branch']);
        $supervisor = $this->createEmployee($company->id, null, null, UserType::SUPERVISOR->value);
        $member = $this->createEmployee($company->id, branchId: $branch, name: 'Scoped Leave Employee');
        $outsider = $this->createEmployee($company->id, branchId: $branch);
        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'assigned_employees' => [$member->id],
            'start_date' => '2025-01-01',
            'end_date' => '2026-11-30',
            'status' => 'active',
        ]);
        $type = DB::table('leave_types')->insertGetId(['name' => 'Test Leave', 'company_id' => $company->id]);

        foreach ([
            [$member->id, '2025-12-30', '2026-01-05', 'approved', 7],
            [$member->id, '2025-11-01', '2025-11-01', 'pending', 1],
            [$member->id, '2025-11-03', '2025-11-03', 'pending', 1],
            [$member->id, '2026-11-08', '2026-11-12', 'approved', 5],
            [$outsider->id, '2025-12-29', '2026-01-04', 'approved', 7],
        ] as [$employee, $start, $end, $status, $days]) {
            DB::table('leaves')->insert([
                'employee_id' => $employee,
                'leave_type_id' => $type,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
                'days' => $days,
            ]);
        }

        $supervisor->user->givePermissionTo(Permission::create(['name' => 'view_any_leave']));
        $this->actingAs($supervisor->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2025-11-01')
            ->set('dateTo', '2025-11-30')
            ->set('companyId', (string) $company->id)
            ->set('branchId', (string) $branch)
            ->call('showWorkforceOverview')
            ->assertSeeHtml('wire:click="openKashf(&#039;leaves&#039;, &#039;on_leave_today_count&#039;)"');

        $stats = $page->instance()->workforceOverview()['leaves'];
        foreach (['on_leave_today_count' => 'is_on_leave_today', 'pending_requests_count' => 'pending_leaves_count', 'total_approved_days' => 'total_approved_days'] as $metric => $column) {
            $page->call('openKashf', 'leaves', $metric)
                ->assertDispatched('open-modal', id: 'performance-kashf-modal')
                ->assertSee($member->name);
            $records = $page->instance()->kashfRecords();
            $this->assertSame([$member->id], $records->pluck('id')->all());
            $this->assertEquals($stats[$metric], $records->sum($column));
        }

        $this->assertSame(['on_leave_today_count' => 1, 'pending_requests_count' => 2, 'total_approved_days' => 2], $stats);
    }

    public function test_attendance_totals_and_statements_use_the_selected_month_branch_and_team(): void
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
        $otherCompany = $this->createCompany('Other Attendance Company');
        $branch = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Attendance Branch']);
        $otherBranch = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Other Attendance Branch']);
        $supervisor = $this->createEmployee($company->id, null, null, UserType::SUPERVISOR->value);
        $member = $this->createEmployee($company->id, null, $branch);
        $noRecords = $this->createEmployee($company->id, null, $branch);
        $outsideTeam = $this->createEmployee($company->id, null, $branch);
        $outsideBranch = $this->createEmployee($company->id, null, $otherBranch);
        $outsideCompany = $this->createEmployee($otherCompany->id);
        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'assigned_employees' => [$member->id, $noRecords->id, $outsideBranch->id],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'active',
        ]);
        $supervisor->user->givePermissionTo(Permission::create(['name' => 'view_any_attendance']));
        $this->actingAs($supervisor->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        DB::table('attendance')->insert([
            ['employee_id' => $member->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::PRESENT->value],
            ['employee_id' => $member->id, 'date' => '2026-11-02', 'status' => AttendanceStatus::LATE->value],
            ['employee_id' => $member->id, 'date' => '2026-11-03', 'status' => AttendanceStatus::PRESENT->value],
            ['employee_id' => $member->id, 'date' => '2026-11-04', 'status' => AttendanceStatus::ABSENT->value],
            ['employee_id' => $member->id, 'date' => '2026-10-31', 'status' => AttendanceStatus::ABSENT->value],
            ['employee_id' => $outsideTeam->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::ABSENT->value],
            ['employee_id' => $outsideBranch->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::ABSENT->value],
            ['employee_id' => $outsideCompany->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::ABSENT->value],
        ]);

        $filters = ['month' => '2026-11', 'company_id' => [$company->id], 'branch_id' => $branch];
        $this->assertSame(0, AttendanceSummaryService::getStats($filters, [])['recorded_days']);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2026-11-10')
            ->set('dateTo', '2026-11-30')
            ->set('companyId', (string) $company->id)
            ->set('branchId', (string) $branch)
            ->call('showWorkforceOverview')
            ->assertSeeHtml('wire:click="selectWorkforcePreview(&#039;attendance&#039;, &#039;present_days&#039;)"');

        $overview = $page->instance()->workforceOverview();
        $this->assertNull($overview['attendance_url']);
        $this->assertSame(2, $overview['attendance']['employees_count']);

        foreach (['present_days' => 2, 'late_days' => 1, 'absent_days' => 1] as $type => $count) {
            $page->call('openKashf', 'attendance', $type)
                ->assertDispatched('open-modal', id: 'performance-kashf-modal')
                ->assertSee(__('performance.kashf.attendance_title', ['type' => __('performance.overview.'.$type), 'month' => '2026-11']))
                ->assertSee($member->name);

            $records = $page->instance()->kashfRecords();
            $this->assertSame([$member->id], $records->pluck('id')->all());
            $this->assertSame($count, $overview['attendance'][$type]);
            $this->assertEquals($count, $records->sum($type));
        }
    }

    public function test_workforce_preview_selection_and_records(): void
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

        $company = $this->createCompany('Workforce Preview Company');
        $branch = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Main Branch']);
        $supervisor = $this->createEmployee($company->id, null, $branch, UserType::SUPERVISOR->value);
        $member = $this->createEmployee($company->id, null, $branch);

        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'assigned_employees' => [$member->id],
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'active',
        ]);

        $supervisor->user->givePermissionTo(Permission::findOrCreate('view_any_attendance'));
        $this->actingAs($supervisor->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        DB::table('attendance')->insert([
            ['employee_id' => $member->id, 'date' => '2026-11-01', 'status' => AttendanceStatus::PRESENT->value],
            ['employee_id' => $member->id, 'date' => '2026-11-02', 'status' => AttendanceStatus::LATE->value],
        ]);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->set('companyId', (string) $company->id)
            ->call('showWorkforceOverview')
            ->assertSee(__('performance.workforce.recorded_days_share'))
            ->assertSeeHtml('<span>50%</span>')
            ->assertSeeHtml('<span>0%</span>');

        $cardGroups = $page->instance()->workforceCardGroups();
        $this->assertArrayHasKey('attendance', $cardGroups);
        $this->assertSame(1, $cardGroups['attendance']['present_days']);
        $this->assertSame(1, $cardGroups['attendance']['late_days']);

        $previews = $page->instance()->workforcePreviews();
        $this->assertSame('present_days', $previews['attendance']['type']);
        $this->assertCount(1, $previews['attendance']['records']);

        $page->call('selectWorkforcePreview', 'attendance', 'late_days');
        $this->assertSame(['attendance' => 'late_days'], $page->get('workforcePreviewTypes'));

        $updatedPreviews = $page->instance()->workforcePreviews();
        $this->assertSame('late_days', $updatedPreviews['attendance']['type']);
        $this->assertCount(1, $updatedPreviews['attendance']['records']);

        $page->call('selectWorkforcePreview', 'attendance', 'invalid_type')
            ->assertStatus(404);
    }

    public function test_department_cards_show_scores_rank_them_and_tie_on_the_worst_score(): void
    {
        $company = $this->createCompany('Ranked Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $best = $this->createDepartment('Zeta Department', $company->id);
        $empty = $this->createDepartment('Empty Department', $company->id);
        $mid = $this->createDepartment('Mid Department', $company->id);
        $worst = $this->createDepartment('Worst Department', $company->id);

        $bestEmployee = $this->createEmployee($company->id, $best->id);
        $midEmployee = $this->createEmployee($company->id, $mid->id);
        $worstEmployee = $this->createEmployee($company->id, $worst->id);
        $this->createEmployee($company->id, null);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createOverdueDeficiency($company->id, $bestEmployee, $overdue);
        $this->createOverdueTask($midEmployee, $company->id, $overdue);
        $this->createOverdueDeficiency($company->id, $midEmployee, $overdue);
        $this->createOverdueTask($worstEmployee, $company->id, $overdue);
        $this->createOverdueDeficiency($company->id, $worstEmployee, $overdue);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->assertSet('departmentRankMode', 'all')
            ->assertSeeHtml('wire:click="selectDepartmentRankMode(&#039;best_first&#039;)"');

        $summary = $page->instance()->departmentsSummary();
        $scoreOf = fn (int $departmentId) => $summary->first(fn (object $row) => $row->department_id === $departmentId);

        $this->assertSame(98, $scoreOf($best->id)->score->score);
        $this->assertSame(94, $scoreOf($mid->id)->score->score);
        $this->assertSame(94, $scoreOf($worst->id)->score->score);
        $this->assertTrue($scoreOf($best->id)->is_rankable);
        $this->assertNull($scoreOf($empty->id)->score);
        $this->assertFalse($scoreOf($empty->id)->is_rankable);
        $this->assertFalse($summary->firstWhere('department_id', 'unassigned')->is_rankable);

        foreach ([$best, $mid, $worst] as $department) {
            $this->assertSame(
                PerformanceScoreService::getScore([], $company->id, $department->id)->score,
                $scoreOf($department->id)->score->score,
            );
        }

        $this->assertSame(
            [$empty->id, $mid->id, $worst->id, $best->id, 'unassigned'],
            $page->instance()->displayedDepartments()->pluck('department_id')->all(),
        );

        $page->call('selectDepartmentRankMode', 'best_first')
            ->assertSet('departmentRankMode', 'best_first');

        $this->assertSame(
            [$best->id, $mid->id, $worst->id, $empty->id, 'unassigned'],
            $page->instance()->displayedDepartments()->pluck('department_id')->all(),
        );

        $page->call('selectDepartmentRankMode', 'worst')
            ->assertSet('departmentRankMode', 'worst')
            ->assertSeeHtml('dept-score-pill');

        $this->assertSame(
            [$mid->id, $worst->id],
            $page->instance()->displayedDepartments()->pluck('department_id')->all(),
        );

        $page->call('selectDepartmentRankMode', 'all');
        $this->assertCount(5, $page->instance()->displayedDepartments());

        $page->call('selectDepartmentRankMode', 'invalid_mode')
            ->assertStatus(404);
    }

    public function test_department_card_is_rankable_from_overdue_items_without_visible_employees(): void
    {
        $company = $this->createCompany('Overdue Only Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);

        $department = $this->createDepartment('Temporary Department', $company->id);
        $temporary = $this->createEmployee($company->id, $department->id);
        $temporary->update(['employment_type' => 'temporary']);
        $this->createOverdueTask($temporary, $company->id, now()->copy()->subDay()->toDateString());

        $row = CompanyDepartmentPerformanceService::getDepartmentsSummary($company->id)
            ->first(fn (object $item) => $item->department_id === $department->id);

        $this->assertSame(0, $row->employees_count);
        $this->assertTrue($row->is_rankable);
        $this->assertSame(96, $row->score->score);
    }

    public function test_worst_mode_reports_when_no_department_is_rankable(): void
    {
        $company = $this->createCompany('Unranked Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->createDepartment('Idle Department', $company->id);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->call('selectDepartmentRankMode', 'worst')
            ->assertSet('departmentRankMode', 'worst')
            ->assertSee(__('performance.department_ranking.no_rankable'));

        $this->assertTrue($page->instance()->displayedDepartments()->isEmpty());
    }

    public function test_department_ranking_respects_the_branch_scope(): void
    {
        $company = $this->createCompany('Branch Ranking Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $branchA = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Ranking Branch A']);
        $branchB = DB::table('branches')->insertGetId(['company_id' => $company->id, 'name' => 'Ranking Branch B']);
        $departmentA = $this->createDepartment('Ranking Alpha', $company->id);
        $departmentB = $this->createDepartment('Ranking Beta', $company->id);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createOverdueTask($this->createEmployee($company->id, $departmentA->id, $branchA), $company->id, $overdue);
        $this->createOverdueTask($this->createEmployee($company->id, $departmentB->id, $branchB), $company->id, $overdue);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->set('branchId', (string) $branchA)
            ->call('selectDepartmentRankMode', 'worst');

        $this->assertSame(
            [$departmentA->id],
            $page->instance()->displayedDepartments()->pluck('department_id')->all(),
        );
    }

    public function test_department_ranking_only_includes_companies_in_the_active_filter(): void
    {
        $included = $this->createCompany('Included Ranking Company');
        $excluded = $this->createCompany('Excluded Ranking Company');
        $viewer = $this->createEmployee($included->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $includedDepartment = $this->createDepartment('Included Ranking Department', $included->id);
        $excludedDepartment = $this->createDepartment('Excluded Ranking Department', $excluded->id);
        $this->createEmployee($included->id, $includedDepartment->id);
        $this->createEmployee($excluded->id, $excludedDepartment->id);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->set('companyId', (string) $included->id)
            ->call('selectCompany', $excluded->id)
            ->call('selectDepartmentRankMode', 'worst')
            ->assertDontSee($excludedDepartment->name);

        $this->assertFalse($page->instance()->performanceScore()->available);
        $this->assertTrue($page->instance()->displayedDepartments()->isEmpty());

        $page->call('selectCompany', $included->id)
            ->call('selectDepartmentRankMode', 'worst')
            ->assertSee($includedDepartment->name);

        $this->assertTrue($page->instance()->performanceScore()->available);
        $this->assertSame([$includedDepartment->id], $page->instance()->displayedDepartments()->pluck('department_id')->all());
    }

    public function test_department_ranking_respects_the_supervisor_team_scope(): void
    {
        $company = $this->createCompany('Ranking Supervision Company');

        $supervisorUser = User::create([
            'name' => 'Ranking Supervisor',
            'email' => 'ranking-sup@test.local',
            'password' => bcrypt('password'),
            'user_type' => UserType::SUPERVISOR->value,
        ]);
        $supervisor = Employee::create([
            'user_id' => $supervisorUser->id,
            'company_id' => $company->id,
            'employee_number' => 'SUP-RANK',
            'employment_type' => 'full_time',
            'hire_date' => '2026-01-01',
        ]);

        $teamDepartment = $this->createDepartment('Ranking Team', $company->id);
        $outsideDepartment = $this->createDepartment('Ranking Outside', $company->id);
        $member = $this->createEmployee($company->id, $teamDepartment->id, null, UserType::SAUDI_EMPLOYEE->value, 'Ranking Member');
        $outsider = $this->createEmployee($company->id, $outsideDepartment->id, null, UserType::SAUDI_EMPLOYEE->value, 'Ranking Outsider');

        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'assigned_employees' => [$member->id],
            'start_date' => '2025-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $overdue = now()->copy()->subDay()->toDateString();
        $this->createOverdueTask($member, $company->id, $overdue);
        $this->createOverdueTask($outsider, $company->id, $overdue);

        $this->actingAs($supervisorUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->call('selectDepartmentRankMode', 'worst');

        $displayed = $page->instance()->displayedDepartments();
        $this->assertSame([$teamDepartment->id], $displayed->pluck('department_id')->all());
        $this->assertTrue($displayed->first()->is_team_scope);
    }

    public function test_department_ranking_follows_search_and_resets_when_switching_company(): void
    {
        $companyA = $this->createCompany('Ranking Search Alpha');
        $companyB = $this->createCompany('Ranking Search Beta');
        $viewer = $this->createEmployee($companyA->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $alpha = $this->createDepartment('Alpha Ranking', $companyA->id);
        $beta = $this->createDepartment('Beta Ranking', $companyA->id);
        $gamma = $this->createDepartment('Gamma Ranking', $companyB->id);
        $overdue = now()->copy()->subDay()->toDateString();

        $this->createOverdueTask($this->createEmployee($companyA->id, $alpha->id), $companyA->id, $overdue);
        $this->createOverdueTask($this->createEmployee($companyA->id, $beta->id), $companyA->id, $overdue);
        $this->createOverdueTask($this->createEmployee($companyB->id, $gamma->id), $companyB->id, $overdue);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $companyA->id)
            ->call('selectDepartmentRankMode', 'best_first')
            ->set('search', 'Alpha')
            ->assertSet('departmentRankMode', 'best_first');

        $this->assertSame([$alpha->id, 'unassigned'], $page->instance()->displayedDepartments()->pluck('department_id')->all());

        $page->call('selectCompany', $companyB->id)
            ->assertSet('departmentRankMode', 'all')
            ->set('search', '');
        $this->assertSame([$gamma->id], $page->instance()->displayedDepartments()->pluck('department_id')->all());

        $page->call('selectDepartmentRankMode', 'worst')
            ->call('backToCompanies')
            ->assertSet('departmentRankMode', 'all');
    }

    public function test_operational_rate_pair_preserves_zero_missing_data_and_statement_scope(): void
    {
        $company = $this->createCompany('Operational Rates');
        $department = $this->createDepartment('Operations', $company->id);
        $employee = $this->createEmployee($company->id, $department->id);
        $this->createOverdueTask($employee, $company->id, now()->startOfMonth()->toDateString());
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CompanyDepartmentPerformance::class)
            ->assertSeeHtml('<div class="perf-operational-rates perf-card perf-operations-rate-card">')
            ->assertSeeHtml('wire:click="'.e("openKashf('services', 'on_time', null, null)").'"')
            ->assertSeeHtml('<span>0%</span>')
            ->assertSee(__('performance.cards.no_data'))
            ->call('selectCompany', $company->id)
            ->assertSeeHtml('<div class="perf-operational-rates">')
            ->assertSee(__('performance.supervisors.completed_services_share'))
            ->call('selectDepartment', $department->id)
            ->assertSeeHtml('type="button" wire:click="'.e("openKashf('tasks', 'all', {$company->id}, '{$department->id}', {$employee->id})").'"')
            ->call('openKashf', 'tasks', 'all', $company->id, $department->id, $employee->id)
            ->assertSet('showKashfModal', true)
            ->assertSet('kashfEmployeeId', $employee->id);
    }

    public function test_company_hero_explains_the_score_calculation(): void
    {
        $company = $this->createCompany('Company Score Rules');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->assertSeeHtml('<details class="perf-hero-score-rules">')
            ->assertSeeHtml('<summary>'.e(__('performance.hero.score_rules_toggle')).'</summary>')
            ->assertSee(__('performance.score.rules_start'))
            ->assertSee(__('performance.score.rules_task'))
            ->assertSee(__('performance.score.rules_deficiency'))
            ->assertSee(__('performance.score.rules_timing'))
            ->assertSee(__('performance.score.rules_recovery'));
    }

    public function test_company_sprint_track_displays_top_5_eligible_departments_with_leader_and_gaps(): void
    {
        $company = $this->createCompany('Sprint Track Alpha');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $overdue = now()->copy()->subDay()->toDateString();

        // Create 7 departments with different scores:
        // Dept 1: 0 overdue -> 100
        // Dept 2: 1 overdue task -> 96 (diff 4)
        // Dept 3: 1 overdue task -> 96 (tied with Dept 2)
        // Dept 4: 2 overdue tasks -> 92 (diff 4)
        // Dept 5: 3 overdue tasks -> 88 (diff 4)
        // Dept 6: 4 overdue tasks -> 84 (should be cut off by 5 limit)
        // Dept 7: 5 overdue tasks -> 80 (should be cut off by 5 limit)
        $depts = [];
        $overdueCounts = [
            1 => 0, // score 100
            2 => 1, // score 96
            3 => 1, // score 96 (tied with dept 2)
            4 => 2, // score 92
            5 => 3, // score 88
            6 => 4, // score 84 (cut off)
            7 => 5, // score 80 (cut off)
        ];

        for ($i = 1; $i <= 7; $i++) {
            $dept = $this->createDepartment("Sprint Dept {$i}", $company->id);
            $emp = $this->createEmployee($company->id, $dept->id);
            for ($k = 0; $k < $overdueCounts[$i]; $k++) {
                $this->createOverdueTask($emp, $company->id, $overdue);
            }
            $depts[$i] = $dept;
        }

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $top5 = $page->instance()->topRankedDepartments();
        $this->assertCount(0, DB::getQueryLog(), 'topRankedDepartments must run 0 extra database queries');

        $this->assertCount(5, $top5);
        $this->assertSame($depts[1]->id, $top5[0]->department_id);
        $this->assertSame(100, $top5[0]->score);
        $this->assertTrue($top5[0]->is_first);
        $this->assertFalse($top5[0]->is_shared_lead);

        // Dept 2 (score 96)
        $this->assertSame(96, $top5[1]->score);
        $this->assertSame(4, $top5[1]->points_behind);
        $this->assertFalse($top5[1]->is_tied_with_prev);

        // Dept 3 (score 96 - tied with dept 2)
        $this->assertSame(96, $top5[2]->score);
        $this->assertSame(0, $top5[2]->points_behind);
        $this->assertTrue($top5[2]->is_tied_with_prev);

        // Dept 4 (score 92)
        $this->assertSame(92, $top5[3]->score);
        $this->assertSame(4, $top5[3]->points_behind);

        // Dept 5 (score 88)
        $this->assertSame(88, $top5[4]->score);
        $this->assertSame(4, $top5[4]->points_behind);

        // Assert 6th and 7th departments are excluded from top 5 sprint track
        $this->assertFalse($top5->pluck('department_name')->contains('Sprint Dept 6'));
        $this->assertFalse($top5->pluck('department_name')->contains('Sprint Dept 7'));

        // In HTML, assert sprint track is rendered with leader label and clickable buttons
        $page->assertSeeHtml('perf-sprint-card')
            ->assertSeeHtml('perf-sprint-leader-label')
            ->assertSee('Sprint Dept 1')
            ->assertSee('Sprint Dept 5');
    }

    public function test_company_sprint_track_handles_shared_lead(): void
    {
        $company = $this->createCompany('Sprint Shared Lead Company');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $deptA = $this->createDepartment('Lead Dept A', $company->id);
        $this->createEmployee($company->id, $deptA->id);

        $deptB = $this->createDepartment('Lead Dept B', $company->id);
        $this->createEmployee($company->id, $deptB->id);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id);

        $top = $page->instance()->topRankedDepartments();

        $this->assertCount(2, $top);
        $this->assertSame(100, $top[0]->score);
        $this->assertSame(100, $top[1]->score);
        $this->assertTrue($top[0]->is_shared_lead);
        $this->assertTrue($top[1]->is_tied_with_prev);

        $page->assertSeeHtml('perf-sprint-leader-label');
    }

    public function test_sprint_track_remains_stable_when_switching_grid_rank_modes(): void
    {
        $company = $this->createCompany('Sprint Stability Co');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $overdue = now()->copy()->subDay()->toDateString();
        $goodDept = $this->createDepartment('Good Dept', $company->id);
        $this->createEmployee($company->id, $goodDept->id);

        $worstDept = $this->createDepartment('Worst Dept', $company->id);
        $badEmp = $this->createEmployee($company->id, $worstDept->id);
        $this->createOverdueTask($badEmp, $company->id, $overdue);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->call('selectDepartmentRankMode', 'worst');

        // Cards grid below shows only the worst department
        $displayed = $page->instance()->displayedDepartments();
        $this->assertSame([$worstDept->id], $displayed->pluck('department_id')->all());

        // But top sprint track still ranks top departments (Good Dept is #1)
        $sprint = $page->instance()->topRankedDepartments();
        $this->assertSame($goodDept->id, $sprint[0]->department_id);
        $this->assertSame(100, $sprint[0]->score);
        $this->assertSame($worstDept->id, $sprint[1]->department_id);

        // Clicking a sprint row selects the department
        $page->call('selectDepartment', $goodDept->id)
            ->assertSet('selectedDepartmentId', (string) $goodDept->id);
    }

    public function test_sprint_track_hidden_when_no_eligible_departments(): void
    {
        $company = $this->createCompany('Sprint Empty Co');
        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Department with no employees and no tasks -> is_rankable === false
        $this->createDepartment('Ghost Dept', $company->id);

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id);

        $this->assertCount(0, $page->instance()->topRankedDepartments());
        $page->assertDontSeeHtml('<div class="perf-sprint-card"')
            ->assertDontSee(__('performance.sprint.title'));
    }

    public function test_employee_operational_score_formula_and_deductions(): void
    {
        // 1. Both rates available
        $evalBoth = PerformanceScoreService::employeeOperationalScore(90.0, 80.0, 1, 1, true);
        $this->assertTrue($evalBoth->available);
        $this->assertTrue($evalBoth->is_rankable);
        $this->assertSame(85.0, $evalBoth->base_rate);
        $this->assertSame(6, $evalBoth->total_deduction); // 1*4 + 1*2 = 6
        $this->assertSame(79, $evalBoth->score); // 85 - 6 = 79

        // 2. On-time rate only (foreign or no tasks)
        $evalOnTime = PerformanceScoreService::employeeOperationalScore(100.0, null, 0, 0, true);
        $this->assertTrue($evalOnTime->available);
        $this->assertSame(100.0, $evalOnTime->base_rate);
        $this->assertSame(100, $evalOnTime->score);

        // 3. Task completion only
        $evalTask = PerformanceScoreService::employeeOperationalScore(null, 60.0, 0, 2, true);
        $this->assertTrue($evalTask->available);
        $this->assertSame(60.0, $evalTask->base_rate);
        $this->assertSame(4, $evalTask->total_deduction);
        $this->assertSame(56, $evalTask->score);

        // 4. No rates but active services
        $evalActive = PerformanceScoreService::employeeOperationalScore(null, null, 1, 0, true);
        $this->assertTrue($evalActive->available);
        $this->assertSame(100.0, $evalActive->base_rate);
        $this->assertSame(4, $evalActive->total_deduction);
        $this->assertSame(96, $evalActive->score);

        // 5. Zero activity
        $evalZero = PerformanceScoreService::employeeOperationalScore(null, null, 0, 0, false);
        $this->assertFalse($evalZero->available);
        $this->assertFalse($evalZero->is_rankable);
        $this->assertNull($evalZero->score);

        // 6. Deductions exceeding base rate clamped to 0
        $evalClamp = PerformanceScoreService::employeeOperationalScore(20.0, null, 10, 5, true);
        $this->assertSame(0, $evalClamp->score);
    }

    public function test_employee_sprint_track_and_ranking_modes(): void
    {
        $company = $this->createCompany('Emp Sprint Co');
        $dept = $this->createDepartment('Operations', $company->id);

        $topEmp = $this->createEmployee($company->id, $dept->id);
        $midEmp = $this->createEmployee($company->id, $dept->id);
        $worstEmp = $this->createEmployee($company->id, $dept->id);

        // Overdue tasks deduct from employee score
        $overdue = now()->startOfMonth()->toDateString();
        $this->createOverdueTask($worstEmp, $company->id, $overdue);

        $viewer = $this->createEmployee($company->id, null, null, UserType::SUPER_ADMIN->value);
        $this->actingAs($viewer->user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Livewire::test(CompanyDepartmentPerformance::class)
            ->call('selectCompany', $company->id)
            ->call('selectDepartment', $dept->id);

        $topRanked = $page->instance()->topRankedEmployees();
        $this->assertNotEmpty($topRanked);
        $this->assertSame(1, $topRanked->first()->rank);
        $this->assertTrue($topRanked->first()->is_first);

        // Rank modes
        $page->call('selectEmployeeRankMode', 'worst');
        $this->assertSame('worst', $page->get('employeeRankMode'));
        $displayed = $page->instance()->displayedEmployees();
        $this->assertSame([$worstEmp->id], $displayed->pluck('employee_id')->all());

        // Reset on back to departments
        $page->call('backToDepartments')
            ->assertSet('employeeRankMode', 'all');
    }
}
