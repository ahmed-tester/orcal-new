<?php

namespace Tests\Feature;

use App\Enums\DepartmentScope;
use App\Enums\EmploymentType;
use App\Enums\UserType;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\SaudiEmployees\Pages\ListSaudiEmployees;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;
use App\Services\DepartmentScopeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentScopeServiceTest extends TestCase
{
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
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->string('department_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->foreignId('company_id');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('employment_type')->nullable();
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('company_id');
        });
    }

    public function test_resolve_scope_identifies_fawaz_group_when_global_with_no_companies(): void
    {
        $dept = Department::create([
            'name' => 'General Management',
            'is_global' => true,
            'company_id' => null,
        ]);

        $scope = DepartmentScopeService::resolveScope($dept);
        $this->assertEquals(DepartmentScope::FAWAZ_GROUP, $scope);
    }

    public function test_resolve_scope_identifies_shared_when_global_with_attached_companies(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $c2 = Company::create(['trade_name' => 'Company B']);

        $dept = Department::create([
            'name' => 'Shared Accounting',
            'is_global' => true,
            'company_id' => $c1->id,
        ]);
        $dept->companies()->sync([$c1->id, $c2->id]);

        $scope = DepartmentScopeService::resolveScope($dept);
        $this->assertEquals(DepartmentScope::SHARED, $scope);
    }

    public function test_resolve_scope_identifies_single_when_not_global(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);

        $dept = Department::create([
            'name' => 'Local HR',
            'is_global' => false,
            'company_id' => $c1->id,
        ]);

        $scope = DepartmentScopeService::resolveScope($dept);
        $this->assertEquals(DepartmentScope::SINGLE, $scope);
    }

    public function test_mutate_before_save_clears_company_id_for_fawaz_group(): void
    {
        $data = [
            'name' => 'Board of Directors',
            'company_id' => 123,
        ];

        $mutated = DepartmentScopeService::mutateBeforeSave($data, DepartmentScope::FAWAZ_GROUP->value);

        $this->assertTrue($mutated['is_global']);
        $this->assertNull($mutated['company_id']);
    }

    public function test_after_save_detaches_all_companies_for_fawaz_group(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $dept = Department::create([
            'name' => 'Executive Office',
            'is_global' => true,
            'company_id' => null,
        ]);
        $dept->companies()->sync([$c1->id]);
        $this->assertEquals(1, $dept->companies()->count());

        DepartmentScopeService::afterSave($dept, DepartmentScope::FAWAZ_GROUP->value);

        $this->assertEquals(0, $dept->companies()->count());
        $this->assertNull($dept->fresh()->company_id);
    }

    public function test_fawaz_group_department_does_not_grant_access_to_other_companies(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $c2 = Company::create(['trade_name' => 'Company B']);

        $dept = Department::create([
            'name' => 'HQ Internal Audit',
            'is_global' => true,
            'company_id' => null,
        ]);

        $user = $this->employeeUser($dept, $c1->id);
        $this->actingAs($user);

        $c3 = Company::create(['trade_name' => 'Company C']);
        DB::table('employees')->insert([
            ['id' => 1, 'company_id' => $c1->id],
            ['id' => 2, 'company_id' => $c2->id],
            ['id' => 3, 'company_id' => $c3->id],
        ]);

        $this->assertSame([], $dept->getAssignedCompanyIds());
        $this->assertSame([$c1->id], CompanyFormService::getAuthorizedCompanyIds());
        $this->assertFalse($user->canAccessAllCompanies());
        $this->assertTrue(DepartmentResource::getEloquentQuery()->whereKey($dept->id)->exists());
        $this->assertSame([$c1->id], CompanyResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([1], CompanyAccessScopeService::apply(Employee::query())->pluck('id')->all());
        $this->assertSame([1], SaudiEmployeeResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($this->employeeUser($dept));
        $this->assertSame([], CompanyFormService::getAuthorizedCompanyIds());
        $this->assertFalse(CompanyAccessScopeService::apply(Employee::query())->exists());
        $this->assertFalse(SaudiEmployeeResource::getEloquentQuery()->exists());
    }

    public function test_shared_and_local_department_assignments_preserve_access(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $c2 = Company::create(['trade_name' => 'Company B']);
        $c3 = Company::create(['trade_name' => 'Company C']);

        $shared = Department::create(['name' => 'Shared', 'is_global' => true]);
        $shared->companies()->sync([$c1->id, $c2->id]);

        $this->assertSame([$c1->id, $c2->id], $shared->getAssignedCompanyIds());
        $this->assertSame([$c1->id, $c2->id], $this->employeeUser($shared, $c1->id)->getAccessibleCompanyIds());
        $this->assertTrue($this->employeeUser($shared, $c3->id)->canAccessAllCompanies());

        $parent = Department::create(['name' => 'Group', 'is_global' => true]);
        $local = Department::create(['name' => 'Local', 'company_id' => $c1->id, 'parent_id' => $parent->id]);
        $local->companies()->detach();

        $this->assertSame([$c1->id], $local->getAssignedCompanyIds());
        $this->assertSame([$c1->id], $this->employeeUser($local, $c1->id)->getAccessibleCompanyIds());
    }

    public function test_owner_and_super_admin_keep_their_company_scopes(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $c2 = Company::create(['trade_name' => 'Company B']);
        $dept = Department::create(['name' => 'Group', 'is_global' => true]);

        $owner = $this->employeeUser($dept, $c2->id);
        $owner->forceFill(['id' => 1, 'user_type' => UserType::OWNER]);
        DB::table('owners')->insert(['user_id' => $owner->id, 'company_id' => $c1->id]);

        $admin = $this->employeeUser($dept, $c1->id);
        $admin->user_type = UserType::SUPER_ADMIN;

        $this->assertSame([$c1->id], $owner->getAccessibleCompanyIds());
        $this->assertSame([$c1->id, $c2->id], $admin->getAccessibleCompanyIds());
    }

    public function test_branch_restriction_still_applies_to_staff_in_a_group_department(): void
    {
        $c1 = Company::create(['trade_name' => 'Company A']);
        $c2 = Company::create(['trade_name' => 'Company B']);
        $dept = Department::create(['name' => 'Group', 'is_global' => true]);

        $user = $this->employeeUser($dept, $c1->id);
        $user->employee->branch_id = 1;
        $this->actingAs($user);
        (require database_path('migrations/2026_09_17_123833_create_permission_tables.php'))->up();

        DB::table('employees')->insert([
            ['id' => 1, 'company_id' => $c1->id, 'branch_id' => 1, 'employment_type' => null],
            ['id' => 2, 'company_id' => $c1->id, 'branch_id' => 2, 'employment_type' => null],
            ['id' => 3, 'company_id' => $c2->id, 'branch_id' => 3, 'employment_type' => null],
            ['id' => 4, 'company_id' => $c1->id, 'branch_id' => 1, 'employment_type' => EmploymentType::TEMPORARY->value],
        ]);

        $this->assertSame([1, 4], CompanyAccessScopeService::apply(Employee::query())->orderBy('id')->pluck('id')->all());
        $this->assertSame([1], SaudiEmployeeResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame(1, SaudiEmployeeResource::resolveRecordRouteBinding(1)?->getKey());
        $this->assertNull(SaudiEmployeeResource::resolveRecordRouteBinding(2));
        $this->assertNull(SaudiEmployeeResource::resolveRecordRouteBinding(3));
        $this->assertNull(SaudiEmployeeResource::resolveRecordRouteBinding(4));

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
        });
        DB::table('users')->insert(['id' => 1, 'user_type' => UserType::SAUDI_EMPLOYEE->value]);
        DB::table('employees')->update(['user_id' => 1]);

        $tabs = (new ListSaudiEmployees)->getTabs();
        $this->assertSame('1', $tabs['all']->getBadge());
        $this->assertSame('1', $tabs['saudi']->getBadge());
        $this->assertSame('0', $tabs['foreign']->getBadge());
    }

    public function test_department_auto_generates_code_if_blank(): void
    {
        $dept = Department::create([
            'name' => 'Public Relations',
            'is_global' => true,
            'company_id' => null,
        ]);

        $this->assertNotEmpty($dept->department_code);
        $this->assertStringStartsWith('ADM-', $dept->department_code);
    }

    private function employeeUser(Department $department, ?int $companyId = null): User
    {
        $user = new User(['user_type' => UserType::SAUDI_EMPLOYEE]);
        $user->setRelation('roles', collect());
        $user->setRelation('employee', (new Employee(['company_id' => $companyId]))->setRelation('department', $department));

        return $user;
    }
}
