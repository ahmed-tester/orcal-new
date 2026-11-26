<?php

namespace Tests\Feature;

use App\Enums\DepartmentScope;
use App\Enums\UserType;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartmentArchives;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Models\Company;
use App\Models\Department;
use App\Models\DepartmentArchiveBatch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Owner;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\DepartmentDeletionService;
use App\Services\Departments\DepartmentHierarchyService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDeleteTransferRestoreTest extends TestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::reconnect('sqlite');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name')->nullable();
            $table->string('trade_name_en')->nullable();
            $table->string('company_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('manager_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->foreignId('company_id');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('user_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('company_id');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('job_title_id')->nullable();
            $table->string('employee_number')->nullable();
            $table->string('contract_duration')->nullable();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->string('nationality')->nullable();
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('name')->default('Title');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('employee_id');
            $table->string('record_number')->nullable();
            $table->date('record_date')->nullable();
            $table->date('payroll_month')->nullable();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('rewards_amount', 10, 2)->default(0);
            $table->decimal('additional_amount', 10, 2)->default(0);
            $table->decimal('advance_deduction', 10, 2)->default(0);
            $table->decimal('absence_deduction', 10, 2)->default(0);
            $table->decimal('deduction_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('payment_status')->default('paid');
            $table->foreignId('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        (require database_path('migrations/2026_09_17_123833_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_11_26_120000_add_soft_deletes_and_department_archive_tables.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'view_any_department', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_department', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_department', 'guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);
        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_impact_analysis_distinguishes_direct_and_branch_counts_and_financial_records(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $empUser1 = User::create(['name' => 'Emp 1', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $emp1 = Employee::create(['user_id' => $empUser1->id, 'company_id' => $company->id, 'department_id' => $main->id, 'basic_salary' => 5000]);

        $empUser2 = User::create(['name' => 'Emp 2', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $emp2 = Employee::create(['user_id' => $empUser2->id, 'company_id' => $company->id, 'department_id' => $sub->id, 'basic_salary' => 6000]);

        JobTitle::create(['name' => 'Manager JT', 'department_id' => $main->id, 'is_global' => true]);
        JobTitle::create(['name' => 'Specialist JT', 'department_id' => $sub->id, 'company_id' => $company->id]);

        PayrollRecord::create(['company_id' => $company->id, 'employee_id' => $emp2->id, 'basic_salary' => 6000, 'net_amount' => 6000]);

        $impact = DepartmentDeletionService::analyzeImpact($main);

        $this->assertTrue($impact['has_blockers']);
        $this->assertSame(1, $impact['counts']['direct_children']);
        $this->assertSame(1, $impact['counts']['total_descendants']);
        $this->assertSame(1, $impact['counts']['direct_employees']);
        $this->assertSame(2, $impact['counts']['total_employees']);
        $this->assertSame(1, $impact['counts']['direct_job_titles']);
        $this->assertSame(2, $impact['counts']['total_job_titles']);
        $this->assertSame(1, $impact['counts']['financial_records']);
    }

    public function test_transfer_and_archive_preserves_employee_company_salary_and_payroll_and_excludes_incompatible_targets(): void
    {
        $companyA = Company::create(['trade_name' => 'Company A']);
        $companyB = Company::create(['trade_name' => 'Company B']);

        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);

        $sourceSub = DepartmentHierarchyService::save(['name' => 'Source Sub', 'parent_id' => $main->id, 'company_id' => $companyA->id], null, DepartmentScope::SINGLE);
        $targetSubA = DepartmentHierarchyService::save(['name' => 'Target A', 'parent_id' => $main->id, 'company_id' => $companyA->id], null, DepartmentScope::SINGLE);
        $targetSubB = DepartmentHierarchyService::save(['name' => 'Target B', 'parent_id' => $main->id, 'company_id' => $companyB->id], null, DepartmentScope::SINGLE);

        $empUser = User::create(['name' => 'Ali', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'company_id' => $companyA->id,
            'department_id' => $sourceSub->id,
            'basic_salary' => 7500,
        ]);
        $payroll = PayrollRecord::create([
            'company_id' => $companyA->id,
            'employee_id' => $employee->id,
            'basic_salary' => 7500,
            'net_amount' => 7500,
        ]);
        $jobTitle = JobTitle::create([
            'name' => 'Accountant',
            'company_id' => $companyA->id,
            'department_id' => $sourceSub->id,
        ]);

        $targets = DepartmentDeletionService::compatibleTransferTargets($sourceSub);
        $this->assertArrayHasKey($targetSubA->id, $targets);
        $this->assertArrayNotHasKey($targetSubB->id, $targets);
        $this->assertArrayNotHasKey($sourceSub->id, $targets);

        $batch = DepartmentDeletionService::transferAndArchive($sourceSub, $targetSubA->id);

        $this->assertSame('transfer_and_archive', $batch->action_type);
        $this->assertSoftDeleted('departments', ['id' => $sourceSub->id]);

        $employee->refresh();
        $this->assertSame($targetSubA->id, (int) $employee->department_id);
        $this->assertSame($companyA->id, (int) $employee->company_id);
        $this->assertEquals(7500, (float) $employee->basic_salary);
        $this->assertDatabaseHas('payroll_records', ['id' => $payroll->id, 'employee_id' => $employee->id, 'company_id' => $companyA->id]);

        $jobTitle->refresh();
        $this->assertSame($targetSubA->id, (int) $jobTitle->department_id);

        // Restoring a transfer_and_archive batch restores ONLY the source department and does not undo employee transfer
        DepartmentDeletionService::restoreBatch($batch);
        $this->assertNotSoftDeleted('departments', ['id' => $sourceSub->id]);
        $this->assertSame($targetSubA->id, (int) $employee->fresh()->department_id);
    }

    public function test_archive_with_dependents_soft_deletes_subtree_restricts_pure_user_keeps_dual_owner_and_reserves_code(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Group Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main Dept', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub Dept', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        $subCode = $sub->department_code;

        $pureUser = User::create(['name' => 'Pure Emp', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $pureEmp = Employee::create(['user_id' => $pureUser->id, 'company_id' => $company->id, 'department_id' => $sub->id]);

        $dualUser = User::create(['name' => 'Dual Owner Emp', 'user_type' => UserType::OWNER, 'status' => 'active']);
        Owner::create(['user_id' => $dualUser->id, 'company_id' => $company->id]);
        $dualEmp = Employee::create(['user_id' => $dualUser->id, 'company_id' => $company->id, 'department_id' => $sub->id]);

        $jobTitle = JobTitle::create(['name' => 'Dev', 'company_id' => $company->id, 'department_id' => $sub->id]);
        $payroll = PayrollRecord::create(['company_id' => $company->id, 'employee_id' => $pureEmp->id, 'basic_salary' => 4000, 'net_amount' => 4000]);

        // Confirmation mismatch fails without deleting anything
        try {
            DepartmentDeletionService::archiveWithDependents($main, 'WRONG');
            $this->fail('Expected ValidationException for confirmation mismatch');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('confirmation_text', $e->errors());
        }

        $batch = DepartmentDeletionService::archiveWithDependents($main, $main->department_code);

        $this->assertSoftDeleted('departments', ['id' => $main->id]);
        $this->assertSoftDeleted('departments', ['id' => $sub->id]);
        $this->assertSoftDeleted('employees', ['id' => $pureEmp->id]);
        $this->assertSoftDeleted('employees', ['id' => $dualEmp->id]);
        $this->assertSoftDeleted('job_titles', ['id' => $jobTitle->id]);

        // Pure employee user cannot access panel when employee record is archived; independent user status is untouched
        $pureUser->refresh();
        $this->assertSame('active', $pureUser->status);
        $this->assertFalse($pureUser->canAccessPanel(Filament::getPanel('admin')));

        // Dual capacity owner+employee keeps active login as Owner, while employee relation is null
        $dualUser->refresh();
        $this->assertSame('active', $dualUser->status);
        $this->assertTrue($dualUser->canAccessPanel(Filament::getPanel('admin')));
        $this->assertNull($dualUser->employee);

        // Confirmed financial record remains intact and still resolves archived employee
        $this->assertDatabaseHas('payroll_records', ['id' => $payroll->id]);
        $this->assertNotNull($payroll->fresh()->employee);

        // Archived code remains reserved under the same parent
        $newMain = DepartmentHierarchyService::save(['name' => 'New Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $this->assertNotSame($main->department_code, $newMain->department_code);

        // Non-super-admin cannot restore
        $staff = User::create(['name' => 'Staff', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $this->actingAs($staff);
        $this->expectException(AuthorizationException::class);
        DepartmentDeletionService::restoreBatch($batch);
    }

    public function test_restore_batch_restores_only_batch_items_and_checks_archived_parent(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        // Pre-existing archived employee in another department should NOT be touched
        $otherSub = DepartmentHierarchyService::save(['name' => 'Other Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        $preArchivedEmp = Employee::create(['company_id' => $company->id, 'department_id' => $otherSub->id]);
        $preArchivedEmp->delete();

        $subBatch = DepartmentDeletionService::archiveWithDependents($sub, $sub->name);
        $mainBatch = DepartmentDeletionService::archiveWithDependents($main, $main->name);

        // Cannot restore child batch while parent is still archived without overrideParentId
        try {
            DepartmentDeletionService::restoreBatch($subBatch);
            $this->fail('Expected ValidationException when parent is archived');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('override_parent_id', $e->errors());
        }

        // Restore parent batch first, then child batch
        DepartmentDeletionService::restoreBatch($mainBatch);
        DepartmentDeletionService::restoreBatch($subBatch);

        $this->assertNotSoftDeleted('departments', ['id' => $main->id]);
        $this->assertNotSoftDeleted('departments', ['id' => $sub->id]);
        $this->assertSoftDeleted('employees', ['id' => $preArchivedEmp->id]);
    }

    public function test_force_delete_and_direct_delete_with_blockers_are_blocked(): void
    {
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);

        $this->assertFalse(Gate::allows('forceDelete', $main));
        $this->assertFalse(Gate::allows('forceDeleteAny', Department::class));

        // Direct delete on parent with child is blocked by observer
        $this->assertFalse($admin->delete());
        $this->assertNotSoftDeleted('departments', ['id' => $admin->id]);

        // forceDelete is blocked by observer
        $this->assertFalse($main->forceDelete());
        $this->assertDatabaseHas('departments', ['id' => $main->id]);
    }

    public function test_filament_edit_and_trash_pages_work_for_delete_and_restore(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        Livewire::test(EditDepartment::class, ['record' => $sub->getRouteKey()])
            ->callAction('delete_department');

        $this->assertSoftDeleted('departments', ['id' => $sub->id]);

        $batch = DepartmentArchiveBatch::query()->latest('id')->firstOrFail();

        Livewire::test(ListDepartmentArchives::class)
            ->assertTableActionVisible('restore', $batch)
            ->callTableAction('restore', $batch);

        $this->assertNotSoftDeleted('departments', ['id' => $sub->id]);
        $this->assertNotNull($batch->fresh()->restored_at);

        // Non-super-admin cannot access ListDepartmentArchives
        $staff = User::create(['name' => 'Staff', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        $staff->givePermissionTo(['view_any_department', 'delete_department']);
        $this->actingAs($staff);

        $this->assertFalse(ListDepartmentArchives::canAccess());
        Livewire::test(ListDepartments::class)->assertActionHidden('archives');
    }

    public function test_shared_department_deletion_and_preview_require_full_company_scope(): void
    {
        $companyA = Company::create(['trade_name' => 'Company A']);
        $companyB = Company::create(['trade_name' => 'Company B']);

        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $shared = DepartmentHierarchyService::save([
            'name' => 'Shared Sub',
            'parent_id' => $main->id,
            'company_ids' => [$companyA->id, $companyB->id],
        ], null, DepartmentScope::SHARED);
        $sourceA = DepartmentHierarchyService::save([
            'name' => 'Source A',
            'parent_id' => $main->id,
            'company_id' => $companyA->id,
        ], null, DepartmentScope::SINGLE);
        $targetA = DepartmentHierarchyService::save([
            'name' => 'Target A',
            'parent_id' => $main->id,
            'company_id' => $companyA->id,
        ], null, DepartmentScope::SINGLE);

        $ownerA = User::create(['name' => 'Owner A', 'user_type' => UserType::OWNER, 'status' => 'active']);
        Owner::create(['user_id' => $ownerA->id, 'company_id' => $companyA->id]);
        $this->actingAs($ownerA);

        // Compatible targets for non-super-admin must exclude global/company-less departments ($admin, $main)
        $targetsForOwnerA = DepartmentDeletionService::compatibleTransferTargets($sourceA);
        $this->assertArrayHasKey($targetA->id, $targetsForOwnerA);
        $this->assertArrayNotHasKey($admin->id, $targetsForOwnerA);
        $this->assertArrayNotHasKey($main->id, $targetsForOwnerA);
        $this->assertArrayNotHasKey($shared->id, $targetsForOwnerA);

        try {
            DepartmentDeletionService::analyzeImpact($shared);
            $this->fail('Expected AuthorizationException on preview for partial company access');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        try {
            DepartmentDeletionService::transferAndArchive($shared, $targetA->id);
            $this->fail('Expected AuthorizationException on transfer for partial company access');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->expectException(AuthorizationException::class);
        DepartmentDeletionService::archiveWithDependents($shared, $shared->name);
    }

    public function test_restore_revalidates_depth_and_employee_company_compatibility(): void
    {
        $companyA = Company::create(['trade_name' => 'Company A']);
        $companyB = Company::create(['trade_name' => 'Company B']);

        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $companyA->id], null, DepartmentScope::SINGLE);
        $emp = Employee::create(['company_id' => $companyA->id, 'department_id' => $sub->id]);

        $batch = DepartmentDeletionService::archiveWithDependents($sub, $sub->name);

        // 1. Change department company assignment to B only while employee belongs to A -> restore must fail
        $sub->forceFill(['company_id' => $companyB->id])->saveQuietly();
        $sub->companies()->sync([$companyB->id]);

        try {
            DepartmentDeletionService::restoreBatch($batch);
            $this->fail('Expected ValidationException when employee company no longer matches department');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('override_parent_id', $e->errors());
        }

        // Revert company back to A and verify restore fails if parent moved to layer 3 (would create layer 4)
        $sub->forceFill(['company_id' => $companyA->id])->saveQuietly();
        $sub->companies()->sync([$companyA->id]);

        $main2 = DepartmentHierarchyService::save(['name' => 'Main 2', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $originalParentId = $main->parent_id;
        $main->forceFill(['parent_id' => $main2->id])->saveQuietly();

        try {
            DepartmentDeletionService::restoreBatch($batch);
            $this->fail('Expected ValidationException when restoring would create a fourth layer');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('override_parent_id', $e->errors());
        }

        // Restore parent position back to layer 2 and verify restore succeeds
        $main->forceFill(['parent_id' => $originalParentId])->saveQuietly();
        DepartmentDeletionService::restoreBatch($batch);
        $this->assertNotSoftDeleted('departments', ['id' => $sub->id]);
        $this->assertNotSoftDeleted('employees', ['id' => $emp->id]);
    }

    public function test_restore_keeps_independently_deactivated_user_inactive(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $user = User::create(['name' => 'Emp User', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active']);
        Employee::create(['user_id' => $user->id, 'company_id' => $company->id, 'department_id' => $sub->id]);

        $batch = DepartmentDeletionService::archiveWithDependents($sub, $sub->name);
        $this->assertFalse($user->fresh()->canAccessPanel(Filament::getPanel('admin')));

        // Independent activation then independent deactivation
        $user->update(['status' => 'active']);
        $user->update(['status' => 'inactive']);

        DepartmentDeletionService::restoreBatch($batch);

        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_mail_access_excludes_archived_employee_even_when_dual_owner_user_is_active(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('email_account_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id');
            $table->foreignId('employee_id');
            $table->foreignId('company_id');
            $table->timestamps();
        });

        $company = Company::create(['trade_name' => 'Mail Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $dualUser = User::create(['name' => 'Dual Owner', 'user_type' => UserType::OWNER, 'status' => 'active']);
        Owner::create(['user_id' => $dualUser->id, 'company_id' => $company->id]);
        $emp = Employee::create(['user_id' => $dualUser->id, 'company_id' => $company->id, 'department_id' => $sub->id, 'status' => 'active']);

        $accountId = DB::table('email_accounts')->insertGetId([
            'company_id' => $company->id,
            'email' => 'info@example.com',
        ]);
        DB::table('email_account_employees')->insert([
            'email_account_id' => $accountId,
            'employee_id' => $emp->id,
            'company_id' => $company->id,
        ]);

        $this->assertTrue(\App\Services\Mail\MailAccessService::isAssignedToAccount($dualUser, $accountId, $company->id));

        DepartmentDeletionService::archiveWithDependents($sub, $sub->name);

        $this->assertFalse(\App\Services\Mail\MailAccessService::isAssignedToAccount($dualUser->fresh(), $accountId, $company->id));
    }

    public function test_archive_blocks_job_titles_with_external_employees_or_external_children(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub1 = DepartmentHierarchyService::save(['name' => 'Sub 1', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        $sub2 = DepartmentHierarchyService::save(['name' => 'Sub 2', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $jt = JobTitle::create(['name' => 'Shared Role', 'company_id' => $company->id, 'department_id' => $sub1->id]);
        $externalEmp = Employee::create(['company_id' => $company->id, 'department_id' => $sub2->id, 'job_title_id' => $jt->id]);

        try {
            DepartmentDeletionService::archiveWithDependents($sub1, $sub1->name);
            $this->fail('Expected ValidationException when external employee references job title');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('departments', $e->errors());
        }

        // Detach external employee, then test external child job title blocking
        $externalEmp->update(['job_title_id' => null]);
        JobTitle::create(['name' => 'External Child Role', 'company_id' => $company->id, 'department_id' => $sub2->id, 'parent_id' => $jt->id]);

        $this->expectException(ValidationException::class);
        DepartmentDeletionService::archiveWithDependents($sub1, $sub1->name);
    }

    public function test_empty_department_archive_rejects_when_dependents_added_before_execution(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Empty Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        // Simulate employee added after modal opened without blockers
        Employee::create(['company_id' => $company->id, 'department_id' => $sub->id]);

        try {
            DepartmentDeletionService::archiveWithDependents($sub, '', false);
            $this->fail('Expected ValidationException when department is no longer empty');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('confirmation_text', $e->errors());
        }

        $this->assertNotSoftDeleted('departments', ['id' => $sub->id]);
    }

    public function test_transfer_and_archive_logs_transferred_members_and_restore_does_not_undo_transfer(): void
    {
        $company = Company::create(['trade_name' => 'Alpha Co']);
        $admin = DepartmentHierarchyService::save(['name' => 'Admin'], null, DepartmentScope::FAWAZ_GROUP);
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $admin->id], null, DepartmentScope::FAWAZ_GROUP);
        $source1 = DepartmentHierarchyService::save(['name' => 'Source 1', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        $source2 = DepartmentHierarchyService::save(['name' => 'Source 2', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        $target = DepartmentHierarchyService::save(['name' => 'Target', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $emp1 = Employee::create(['company_id' => $company->id, 'department_id' => $source1->id]);
        $emp2 = Employee::create(['company_id' => $company->id, 'department_id' => $source2->id]);
        $jt = JobTitle::create(['name' => 'Analyst', 'company_id' => $company->id, 'department_id' => $source1->id]);

        $batch = DepartmentDeletionService::transferAndArchive(collect([$source1, $source2]), $target->id);

        $this->assertDatabaseHas('department_archive_items', [
            'batch_id' => $batch->id,
            'item_type' => 'transferred_employee',
            'item_id' => $emp1->id,
            'parent_department_id' => $source1->id,
        ]);
        $this->assertDatabaseHas('department_archive_items', [
            'batch_id' => $batch->id,
            'item_type' => 'transferred_employee',
            'item_id' => $emp2->id,
            'parent_department_id' => $source2->id,
        ]);
        $this->assertDatabaseHas('department_archive_items', [
            'batch_id' => $batch->id,
            'item_type' => 'transferred_job_title',
            'item_id' => $jt->id,
            'parent_department_id' => $source1->id,
        ]);

        DepartmentDeletionService::restoreBatch($batch);

        $this->assertSame($target->id, (int) $emp1->fresh()->department_id);
        $this->assertSame($target->id, (int) $emp2->fresh()->department_id);
        $this->assertSame($target->id, (int) $jt->fresh()->department_id);
    }
}
