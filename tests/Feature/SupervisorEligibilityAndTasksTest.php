<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use App\Services\AI\Tools\CreateSupervisionShiftTool;
use App\Services\AI\Tools\CreateUserTool;
use App\Services\EmployeePerformanceAccessService;
use App\Services\TaskAssignmentItemReviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupervisorEligibilityAndTasksTest extends TestCase
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
            $table->string('company_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'is_temporary')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('is_temporary')->default(false);
            });
        }
        if (! Schema::hasColumn('employees', 'metadata')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }
        if (! Schema::hasColumn('employee_details', 'metadata')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        Schema::create('supervision_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('supervisor_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->string('type')->default('supervision');
            $table->string('title')->nullable();
            $table->text('tasks')->nullable();
            $table->json('assigned_employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        (require database_path('migrations/2026_09_20_160000_create_tasks_and_weekly_notes_tables.php'))->up();
        (require database_path('migrations/2026_11_18_151000_add_task_item_review_workflow.php'))->up();
        Permission::firstOrCreate([
            'name' => EmployeePerformanceAccessService::SUPERVISE_PERMISSION,
            'guard_name' => 'web',
        ]);

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });
        Schema::table('task_assignment_items', function (Blueprint $table) {
            $table->json('metadata')->nullable();
        });

        Role::findOrCreate(UserType::SUPER_ADMIN->value, 'web');
    }

    public function test_saudi_supervisor_eligibility_and_shift_windows(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);

        $saudiUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $saudiEmp = Employee::create(['user_id' => $saudiUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $foreignUser = User::factory()->create(['user_type' => UserType::FOREIGN_EMPLOYEE->value]);
        Employee::create(['user_id' => $foreignUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $this->assertFalse(EmployeePerformanceAccessService::isSupervisor($saudiUser));

        $saudiUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $this->assertTrue(EmployeePerformanceAccessService::isSupervisor($saudiUser));

        $foreignUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $this->assertFalse(EmployeePerformanceAccessService::isSupervisor($foreignUser));

        $memberUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $memberEmp = Employee::create(['user_id' => $memberUser->id, 'company_id' => $company->id, 'status' => 'active']);

        // Future shift must not grant early access.
        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $saudiEmp->id,
            'status' => 'active',
            'assigned_employees' => [$memberEmp->id],
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $this->assertSame([], EmployeePerformanceAccessService::teamWindows($saudiUser));
        $this->assertSame([], EmployeePerformanceAccessService::teamWindows($saudiUser, currentOnly: true));
    }

    public function test_foreign_employee_blocked_from_task_assignment_and_transfer(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);
        $saudiUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $saudiEmp = Employee::create(['user_id' => $saudiUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $foreignUser = User::factory()->create(['user_type' => UserType::FOREIGN_EMPLOYEE->value]);
        $foreignEmp = Employee::create(['user_id' => $foreignUser->id, 'company_id' => $company->id, 'status' => 'active']);

        try {
            TaskAssignment::create([
                'employee_id' => $foreignEmp->id,
                'assignment_date' => now()->toDateString(),
            ]);
            $this->fail('Expected ValidationException for foreign employee task assignment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('employee_id', $e->errors());
        }

        $task = Task::create(['name' => 'Task 1']);
        $assignment = TaskAssignment::create([
            'employee_id' => $saudiEmp->id,
            'assignment_date' => now()->toDateString(),
        ]);
        $item = TaskAssignmentItem::create([
            'task_assignment_id' => $assignment->id,
            'task_id' => $task->id,
            'status' => TaskStatus::PENDING->value,
            'due_date' => now()->toDateString(),
        ]);

        try {
            $item->update([
                'status' => TaskStatus::TRANSFERRED,
                'metadata' => ['transferred_to_employee_id' => $foreignEmp->id],
            ]);
            $this->fail('Expected ValidationException for foreign employee transfer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metadata.transferred_to_employee_id', $e->errors());
        }
    }

    public function test_supervisor_review_scope_cannot_bypass_team_and_completed_shift_blocks_current_review(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);

        $supUser = User::factory()->create([
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
            'company_id' => $company->id,
        ]);
        $supUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supEmp = Employee::create(['user_id' => $supUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $outsiderUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $outsiderEmp = Employee::create(['user_id' => $outsiderUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $memberUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $memberEmp = Employee::create(['user_id' => $memberUser->id, 'company_id' => $company->id, 'status' => 'active']);

        SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supEmp->id,
            'status' => 'completed',
            'assigned_employees' => [$memberEmp->id],
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $task = Task::create(['name' => 'Task 1']);

        $outsiderAssignment = TaskAssignment::create(['employee_id' => $outsiderEmp->id, 'assignment_date' => now()->toDateString()]);
        $outsiderItem = TaskAssignmentItem::create(['task_assignment_id' => $outsiderAssignment->id, 'task_id' => $task->id]);

        $memberAssignment = TaskAssignment::create(['employee_id' => $memberEmp->id, 'assignment_date' => now()->toDateString()]);
        $memberItem = TaskAssignmentItem::create(['task_assignment_id' => $memberAssignment->id, 'task_id' => $task->id]);

        $this->assertFalse(TaskAssignmentItemReviewService::isWithinUserScope($supUser, $outsiderItem));
        $this->assertFalse(TaskAssignmentItemReviewService::isWithinUserScope($supUser, $memberItem));
    }

    public function test_ai_tools_reject_supervisor_user_type_and_require_supervise_permission(): void
    {
        $userTool = new CreateUserTool;
        $rejected = $userTool->execute(['name' => 'Test', 'user_type' => 'supervisor']);
        $this->assertFalse($rejected['success']);

        $rejectedMissing = $userTool->execute(['name' => 'Test']);
        $this->assertFalse($rejectedMissing['success']);

        $company = Company::create(['trade_name' => 'Alpha']);
        $admin = User::factory()->create(['user_type' => UserType::SUPER_ADMIN->value, 'company_id' => $company->id]);
        $this->actingAs($admin);

        $saudiUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $saudiEmp = Employee::create(['user_id' => $saudiUser->id, 'company_id' => $company->id, 'status' => 'active', 'employee_number' => 'S-100']);

        $shiftTool = new CreateSupervisionShiftTool;
        $noPerm = $shiftTool->execute(['title' => 'Shift 1', 'supervisor_identifier' => 'S-100']);
        $this->assertFalse($noPerm['success']);

        $saudiUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $withPerm = $shiftTool->execute(['title' => 'Shift 1', 'supervisor_identifier' => 'S-100']);
        $this->assertTrue($withPerm['success']);
    }

    public function test_revoke_supervision_permission_respects_active_shifts_and_shared_roles(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);
        $user = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $emp = Employee::create(['user_id' => $user->id, 'company_id' => $company->id, 'status' => 'active']);
        $user->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);

        $shift = SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $emp->id,
            'status' => 'active',
            'assigned_employees' => [],
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $blocked = EmployeePerformanceAccessService::revokeSupervisionPermission($user);
        $this->assertFalse($blocked['revoked']);
        $this->assertSame('active_shift_exists', $blocked['reason']);

        $shift->update(['status' => 'completed']);
        $revoked = EmployeePerformanceAccessService::revokeSupervisionPermission($user);
        $this->assertTrue($revoked['revoked']);
        $this->assertFalse($user->fresh()->can(EmployeePerformanceAccessService::SUPERVISE_PERMISSION));

        // Shared multi-purpose role must not be mutated.
        Permission::findOrCreate('view_any_faq', 'web');
        $sharedRole = Role::findOrCreate('shared_role', 'web');
        $sharedRole->syncPermissions([EmployeePerformanceAccessService::SUPERVISE_PERMISSION, 'view_any_faq']);
        $user->assignRole($sharedRole);

        $sharedResult = EmployeePerformanceAccessService::revokeSupervisionPermission($user);
        $this->assertFalse($sharedResult['revoked']);
        $this->assertSame('inherited_from_shared_role', $sharedResult['reason']);
        $this->assertTrue($sharedRole->fresh()->hasPermissionTo('view_any_faq'));
    }

    public function test_supervision_shift_observer_blocks_ineligible_supervisor_and_foreign_members(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);
        $saudiWithoutPerm = Employee::create([
            'user_id' => User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value])->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        try {
            SupervisionShift::create([
                'company_id' => $company->id,
                'supervisor_id' => $saudiWithoutPerm->id,
                'status' => 'active',
                'assigned_employees' => [],
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
            ]);
            $this->fail('Expected ValidationException for supervisor without supervise_task_team permission.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('supervisor_id', $e->errors());
        }

        $supUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value]);
        $supUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supEmp = Employee::create(['user_id' => $supUser->id, 'company_id' => $company->id, 'status' => 'active']);
        $foreignEmp = Employee::create([
            'user_id' => User::factory()->create(['user_type' => UserType::FOREIGN_EMPLOYEE->value])->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        try {
            SupervisionShift::create([
                'company_id' => $company->id,
                'supervisor_id' => $supEmp->id,
                'status' => 'active',
                'assigned_employees' => [$foreignEmp->id],
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
            ]);
            $this->fail('Expected ValidationException for foreign team member.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('assigned_employees', $e->errors());
        }
    }

    public function test_revoking_supervision_permission_does_not_expand_scope_to_company(): void
    {
        $company = Company::create(['trade_name' => 'Alpha']);

        $supUser = User::factory()->create([
            'user_type' => UserType::SAUDI_EMPLOYEE->value,
            'company_id' => $company->id,
        ]);
        $supUser->givePermissionTo(EmployeePerformanceAccessService::SUPERVISE_PERMISSION);
        $supEmp = Employee::create(['user_id' => $supUser->id, 'company_id' => $company->id, 'status' => 'active']);

        $otherEmp = Employee::create([
            'user_id' => User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE->value])->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $shift = SupervisionShift::create([
            'company_id' => $company->id,
            'supervisor_id' => $supEmp->id,
            'status' => 'active',
            'assigned_employees' => [],
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $shift->update(['status' => 'completed']);
        $revoked = EmployeePerformanceAccessService::revokeSupervisionPermission($supUser);
        $this->assertTrue($revoked['revoked']);

        $task = Task::create(['name' => 'Task 1']);
        $otherAssignment = TaskAssignment::create(['employee_id' => $otherEmp->id, 'assignment_date' => now()->toDateString()]);
        $otherItem = TaskAssignmentItem::create(['task_assignment_id' => $otherAssignment->id, 'task_id' => $task->id]);

        $this->assertTrue(EmployeePerformanceAccessService::isFormerOrRevokedSupervisor($supUser->fresh()));
        $this->assertFalse(TaskAssignmentItemReviewService::isWithinUserScope($supUser->fresh(), $otherItem));
        $this->assertFalse(EmployeePerformanceAccessService::supervisorCovers($supUser->fresh(), $otherEmp));

        // Even after deleting the last shift record, the former supervisor marker persists and keeps scope restricted.
        $shift->delete();
        $this->assertTrue(EmployeePerformanceAccessService::isFormerOrRevokedSupervisor($supUser->fresh()));
        $this->assertFalse(EmployeePerformanceAccessService::supervisorCovers($supUser->fresh(), $otherEmp));
    }
}
