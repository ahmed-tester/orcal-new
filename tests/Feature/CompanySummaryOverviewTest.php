<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use App\Services\CompanySummaryOverviewService;
use App\Services\EmployeePerformanceAccessService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanySummaryOverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['cache.default' => 'array']);
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
            $table->string('municipality_license_expiry')->nullable();
            $table->string('civil_defense_license_expiry')->nullable();
            $table->string('commercial_registration_expiry')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('owners')) {
            Schema::create('owners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('user_id');
                $table->timestamps();
            });
        }

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
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('department_id');
            $table->timestamps();
        });

        if (! Schema::hasColumn('employees', 'is_temporary')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('is_temporary')->default(false);
            });
        }
        if (! Schema::hasColumn('employees', 'department_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable();
            });
        }
        if (! Schema::hasColumn('employees', 'metadata')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        if (! Schema::hasTable('employee_details')) {
            Schema::create('employee_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id');
                $table->string('nationality')->nullable();
                $table->string('iqama_expiry')->nullable();
                $table->string('work_permit_expiry')->nullable();
                $table->string('insurance_expiry')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('employee_details', 'metadata')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        Schema::create('supervision_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id');
            $table->string('type')->default('supervision');
            $table->string('status')->default('active');
            $table->json('assigned_employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('service_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('executed_by')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->string('transaction_number')->nullable();
            $table->string('service_status')->default('active');
            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('unified_deficiency_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('service_transaction_id')->nullable();
            $table->string('taskable_type')->nullable();
            $table->unsignedBigInteger('taskable_id')->nullable();
            $table->foreignId('responsible_employee_id')->nullable();
            $table->string('task_code')->nullable();
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
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

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('employee_id');
            $table->string('operation_type')->default('payroll');
            $table->date('payroll_month');
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->timestamps();
        });

        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('employee_id')->nullable();
            $table->string('debtor_type')->default('external');
            $table->string('external_national_id')->nullable();
            $table->date('debt_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->date('violation_date')->nullable();
            $table->string('stage')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->decimal('violation_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Role::findOrCreate(UserType::SUPER_ADMIN->value, 'web');
        Role::findOrCreate(UserType::OWNER->value, 'web');
        Permission::findOrCreate('view_any_payroll_record', 'web');
        Permission::findOrCreate('view_any_debt', 'web');
        Permission::findOrCreate('view_any_violation', 'web');
        Permission::findOrCreate('view_any_saudi_employee', 'web');
        Permission::findOrCreate('view_any_foreign_employee', 'web');
        Permission::findOrCreate('view_any_company', 'web');
        Permission::findOrCreate('view_any_task_assignment', 'web');
        Permission::findOrCreate('view_any_service_transaction', 'web');
    }

    public function test_can_access_allows_super_admin_owner_and_supervisor_only(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);

        $superAdmin = User::factory()->create(['user_type' => UserType::SUPER_ADMIN->value]);
        $superAdmin->assignRole(UserType::SUPER_ADMIN->value);
        $this->actingAs($superAdmin);
        $this->assertTrue(CompanyDepartmentPerformance::canAccess());

        $ownerWithCompany = User::factory()->create(['user_type' => UserType::OWNER->value]);
        $ownerWithCompany->assignRole(UserType::OWNER->value);
        DB::table('owners')->insert(['company_id' => $company->id, 'user_id' => $ownerWithCompany->id]);
        $this->actingAs($ownerWithCompany);
        $this->assertTrue(CompanyDepartmentPerformance::canAccess());

        $ownerWithoutCompany = User::factory()->create(['user_type' => UserType::OWNER->value]);
        $ownerWithoutCompany->assignRole(UserType::OWNER->value);
        $this->actingAs($ownerWithoutCompany);
        $this->assertFalse(CompanyDepartmentPerformance::canAccess());

        $regularUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        Employee::create(['user_id' => $regularUser->id, 'company_id' => $company->id]);
        $this->actingAs($regularUser);
        $this->assertFalse(CompanyDepartmentPerformance::canAccess());

        Permission::findOrCreate(EmployeePerformanceAccessService::SUPERVISE_PERMISSION, 'web');

        // Saudi employee without supervise_task_team permission is NOT a supervisor.
        $saudiSupervisorUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $saudiSupervisorEmp = Employee::create(['user_id' => $saudiSupervisorUser->id, 'company_id' => $company->id]);
        $this->actingAs($saudiSupervisorUser);
        $this->assertFalse(CompanyDepartmentPerformance::canAccess());

        $saudiSupervisorUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        SupervisionShift::create([
            'supervisor_id' => $saudiSupervisorEmp->id,
            'status' => 'active',
            'assigned_employees' => [],
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);
        $this->assertTrue(CompanyDepartmentPerformance::canAccess());
    }

    public function test_summary_scopes_to_department_and_separates_company_level_data(): void
    {
        $company = Company::create([
            'trade_name' => 'Alpha Co',
            'metadata' => ['subscriptions' => ['qiwa' => ['expiry_date' => now()->addMonth()->format('Y-m-d')]]],
        ]);
        $deptA = Department::create(['company_id' => $company->id, 'name' => 'Dept A']);
        $deptB = Department::create(['company_id' => $company->id, 'name' => 'Dept B']);

        $userA = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $empA = Employee::create(['user_id' => $userA->id, 'company_id' => $company->id, 'department_id' => $deptA->id]);
        EmployeeDetail::create(['employee_id' => $empA->id, 'nationality' => 'Saudi', 'iqama_expiry' => now()->addMonths(12)->format('Y-m-d')]);

        $userB = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $empB = Employee::create(['user_id' => $userB->id, 'company_id' => $company->id, 'department_id' => $deptB->id]);

        $task = Task::create(['name' => 'Follow up']);
        $assignA = TaskAssignment::create(['employee_id' => $empA->id, 'assignment_date' => now()->toDateString()]);
        DB::table('task_assignment_items')->insert([
            'task_assignment_id' => $assignA->id,
            'task_id' => $task->id,
            'status' => TaskStatus::COMPLETED->value,
            'due_date' => now()->toDateString(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignB = TaskAssignment::create(['employee_id' => $empB->id, 'assignment_date' => now()->toDateString()]);
        TaskAssignmentItem::create([
            'task_assignment_id' => $assignB->id,
            'task_id' => $task->id,
            'status' => TaskStatus::PENDING->value,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        DB::table('payroll_records')->insert([
            [
                'company_id' => $company->id,
                'employee_id' => $empA->id,
                'operation_type' => 'payroll',
                'payroll_month' => now()->startOfMonth()->toDateString(),
                'net_amount' => 5000,
                'paid_amount' => 5000,
                'remaining_amount' => 0,
                'payment_status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'employee_id' => $empB->id,
                'operation_type' => 'payroll',
                'payroll_month' => now()->startOfMonth()->toDateString(),
                'net_amount' => 4000,
                'paid_amount' => 0,
                'remaining_amount' => 4000,
                'payment_status' => 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'employee_id' => $empA->id,
                'operation_type' => 'collection',
                'payroll_month' => now()->startOfMonth()->toDateString(),
                'net_amount' => 1000,
                'paid_amount' => 750,
                'remaining_amount' => 250,
                'payment_status' => 'partially_paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('debts')->insert([
            'company_id' => $company->id,
            'debtor_type' => 'external',
            'debt_date' => now()->toDateString(),
            'total_amount' => 10000,
            'paid_amount' => 2000,
            'remaining_amount' => 8000,
        ]);

        $superAdmin = User::factory()->create(['user_type' => UserType::SUPER_ADMIN->value]);
        $superAdmin->assignRole(UserType::SUPER_ADMIN->value);
        $this->actingAs($superAdmin);

        $filters = [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ];

        $deptSummary = CompanySummaryOverviewService::build($filters, [$company->id], $deptA->id);

        $this->assertTrue($deptSummary['is_department_view']);
        $this->assertSame(100, $deptSummary['score']->score);
        $this->assertSame(100.0, $deptSummary['cards']['tasks_deficiencies']['rate']);
        $this->assertSame(100.0, $deptSummary['cards']['payroll']['rate']);
        $this->assertSame(5000.0, $deptSummary['cards']['payroll']['net']);
        $this->assertSame(75.0, $deptSummary['cards']['collection_debts']['collection']['rate']);
        $this->assertNull($deptSummary['cards']['collection_debts']['debts']);
        $this->assertNull($deptSummary['cards']['violations']);
        $this->assertSame(20.0, $deptSummary['company_level']['debts']['rate']);
        $this->assertArrayHasKey('municipality', $deptSummary['company_level']['compliance']['licenses']);
        $this->assertSame(
            "openStatement('violations', 'unpaid')",
            CompanySummaryOverviewService::build($filters, [$company->id])['cards']['violations']['remaining_action']
        );

        // Department with an employee but zero tasks/services/deficiencies must not claim operational activity (100).
        $deptEmpty = Department::create(['company_id' => $company->id, 'name' => 'Dept Empty']);
        $userEmpty = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        Employee::create(['user_id' => $userEmpty->id, 'company_id' => $company->id, 'department_id' => $deptEmpty->id]);

        $emptySummary = CompanySummaryOverviewService::build($filters, [$company->id], $deptEmpty->id);
        $this->assertFalse($emptySummary['has_operational_activity']);
    }

    public function test_unauthorized_department_and_supervisor_company_kashf_are_blocked(): void
    {
        $companyA = Company::create(['trade_name' => 'Alpha']);
        $companyB = Company::create(['trade_name' => 'Beta']);
        $deptB = Department::create(['company_id' => $companyB->id, 'name' => 'Beta Dept']);
        $userB = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $empB = Employee::create(['user_id' => $userB->id, 'company_id' => $companyB->id, 'department_id' => $deptB->id]);

        Permission::findOrCreate(EmployeePerformanceAccessService::SUPERVISE_PERMISSION, 'web');
        $supervisorUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $supervisorUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supervisorEmp = Employee::create(['user_id' => $supervisorUser->id, 'company_id' => $companyA->id]);
        SupervisionShift::create([
            'supervisor_id' => $supervisorEmp->id,
            'assigned_employees' => [],
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $this->actingAs($supervisorUser);
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        Livewire::test(CompanyDepartmentPerformance::class)
            ->assertSet('activeOverviewTab', 'summary')
            ->call('selectSummaryDepartment', $deptB->id)
            ->assertStatus(404);

        Livewire::test(CompanyDepartmentPerformance::class)
            ->call('openStatement', 'violations', 'all')
            ->assertStatus(403);

        Livewire::test(CompanyDepartmentPerformance::class)
            ->call('openStatement', 'tasks', 'due')
            ->assertStatus(403);

        Livewire::test(CompanyDepartmentPerformance::class)
            ->call('openStatement', 'services', 'on_time')
            ->assertStatus(403);

        Livewire::test(CompanyDepartmentPerformance::class)
            ->call('openStatement', 'deficiencies', 'all')
            ->assertStatus(403);

        $supSummary = CompanySummaryOverviewService::build([
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ], [$companyA->id]);

        $this->assertFalse($supSummary['has_operational_activity']);
        $this->assertNull($supSummary['cards']['tasks_deficiencies']);
        $this->assertNull($supSummary['cards']['on_time']);

        // Grant only service_transaction permission (without task_assignment): overdue deficiencies count must still be computed, while combined score stays unavailable.
        $empInA = Employee::create([
            'user_id' => User::factory()->create(['user_type' => UserType::FOREIGN_EMPLOYEE->value])->id,
            'company_id' => $companyA->id,
        ]);
        SupervisionShift::query()->where('supervisor_id', $supervisorEmp->id)->update([
            'assigned_employees' => json_encode([$empInA->id]),
        ]);
        DB::table('unified_deficiency_tasks')->insert([
            'company_id' => $companyA->id,
            'service_transaction_id' => 1,
            'responsible_employee_id' => $empInA->id,
            'status' => 'open',
            'due_date' => now()->subDays(2)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supervisorUser->givePermissionTo('view_any_service_transaction');
        $partialSummary = CompanySummaryOverviewService::build([
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ], [$companyA->id]);

        $this->assertFalse($partialSummary['score']->available);
        $this->assertNull($partialSummary['score']->score);
        $this->assertNotNull($partialSummary['cards']['tasks_deficiencies']);
        $this->assertFalse($partialSummary['cards']['tasks_deficiencies']['can_tasks']);
        $this->assertTrue($partialSummary['cards']['tasks_deficiencies']['can_deficiencies']);
        $this->assertSame(1, $partialSummary['cards']['tasks_deficiencies']['overdue_deficiencies']);
    }
}
