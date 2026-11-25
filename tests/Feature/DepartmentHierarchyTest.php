<?php

namespace Tests\Feature;

use App\Enums\DepartmentScope;
use App\Enums\UserType;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\AI\Tools\CreateDepartmentTool;
use App\Services\CodeGeneratorService;
use App\Services\CompanyAccessScopeService;
use App\Services\DepartmentHierarchyService;
use App\Services\DepartmentScopeService;
use App\Services\Reports\Types\DepartmentsReport;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentHierarchyTest extends TestCase
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
            $table->foreignId('manager_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
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
            $table->foreignId('department_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->string('nationality')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('user_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('company_id');
        });
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('company_id')->nullable();
        });
        (require database_path('migrations/2026_09_17_123833_create_permission_tables.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = new User(['user_type' => UserType::SUPER_ADMIN]);
        $user->forceFill(['id' => 99, 'name' => 'Hierarchy reviewer', 'status' => 'active']);
        $user->setRelation('roles', collect());
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function group(string $name, ?int $parentId = null): Department
    {
        return Department::create(['name' => $name, 'is_global' => true, 'parent_id' => $parentId]);
    }

    private function sharedWith(Department $department, array $companyIds): Department
    {
        $department->companies()->sync($companyIds);

        return $department;
    }

    public function test_codes_use_prefix_by_creation_position_and_stay_fixed_after_move(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $administration = $this->group('Operations');
        $otherAdmin = $this->group('Support');
        $main = $this->group('Supervision', $administration->id);
        $sub = Department::create(['name' => 'Riyadh', 'parent_id' => $main->id, 'company_id' => $company->id]);

        $this->assertSame('DEP1-0001', $administration->department_code);
        $this->assertSame('DEP1-0002', $otherAdmin->department_code);
        $this->assertSame('DEP2-0001', $main->department_code);
        $this->assertSame('DEP3-0001', $sub->department_code);

        DepartmentHierarchyService::move($main, $otherAdmin->id);

        $this->assertSame('DEP2-0001', $main->fresh()->department_code);
        $this->assertSame(2, DepartmentHierarchyService::levelOf($main->fresh()));
        $this->assertSame(3, DepartmentHierarchyService::levelOf($sub->fresh()));
    }

    public function test_code_sequence_ignores_company_scope(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);

        $first = Department::create(['name' => 'One', 'company_id' => $c1->id]);
        $second = Department::create(['name' => 'Two', 'company_id' => $c2->id]);

        $this->assertSame('DEP1-0001', $first->department_code);
        $this->assertSame('DEP1-0002', $second->department_code);
    }

    public function test_code_generator_default_behavior_is_unchanged(): void
    {
        $company = Company::create(['trade_name' => 'A']);

        $code = CodeGeneratorService::generate(Department::class, 'department_code', 'DEP', $company->id, 3);

        $this->assertSame('DEP-C'.$company->id.'-001', $code);
    }

    public function test_fourth_level_is_rejected_across_model_service_and_ui(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $admin = $this->group('Level 1');
        $main = $this->group('Level 2', $admin->id);
        $sub = Department::create(['name' => 'Level 3', 'parent_id' => $main->id, 'company_id' => $company->id]);

        $this->assertSame(3, DepartmentHierarchyService::levelOf($sub));
        $this->assertCount(2, DepartmentHierarchyService::ancestorIds($sub));

        try {
            DepartmentHierarchyService::save(['name' => 'Level 4', 'parent_id' => $sub->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
            $this->fail('Fourth level was accepted by save().');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
        }

        try {
            DepartmentHierarchyService::createFromContext(['name' => 'Level 4', 'company_id' => $company->id], $sub->id);
            $this->fail('Fourth level was accepted by createFromContext().');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
        }

        Livewire::withQueryParams(['parent' => $sub->id])->test(CreateDepartment::class)->assertNotFound();
        Livewire::withQueryParams(['parent' => $sub->id])->test(ListDepartments::class)->assertNotFound();
    }

    public function test_display_labels_and_siblings_reuse_ancestor_queries(): void
    {
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $children = collect(range(1, 25))->map(fn ($i) => $this->group('Child '.$i, $main->id));
        DepartmentHierarchyService::clearDisplayCache();
        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach ($children as $child) {
            $this->assertSame(3, DepartmentHierarchyService::levelOf($child));
            DepartmentForm::label('name', $main->id);
            DepartmentForm::label('section', $main->id);
        }

        $this->assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_display_cache_is_cleared_after_moving_an_ancestor(): void
    {
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $child = $this->group('Child', $main->id);
        $this->assertSame(3, DepartmentHierarchyService::levelOf($child));

        DepartmentHierarchyService::move($main, null);

        $this->assertSame(2, DepartmentHierarchyService::levelOf($child));
    }

    public function test_scope_resolution_uses_loaded_companies_without_queries(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $shared = $this->sharedWith($this->group('Shared'), [$company->id]);
        $root->load('companies');
        $shared->load('companies');
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->assertSame(DepartmentScope::FAWAZ_GROUP, DepartmentScopeService::resolveScope($root));
        $this->assertSame(DepartmentScope::SHARED, DepartmentScopeService::resolveScope($shared));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_cycles_are_rejected(): void
    {
        $a = $this->group('A');
        $b = $this->group('B', $a->id);
        $c = $this->group('C', $b->id);

        foreach ([$a->id, $c->id] as $newParent) {
            try {
                $a->parent_id = $newParent;
                $a->save();
                $this->fail('Cycle was accepted.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('parent_id', $e->errors());
            }
            $a->refresh();
        }

        $this->assertNull($a->fresh()->parent_id);
    }

    public function test_sort_order_is_per_sibling_group_and_new_goes_last(): void
    {
        $root = $this->group('Root');
        $other = $this->group('Other');
        $one = $this->group('One', $root->id);
        $two = $this->group('Two', $root->id);
        $otherChild = $this->group('Child', $other->id);

        $this->assertSame([1, 2], [$one->sort_order, $two->sort_order]);
        $this->assertSame(1, $otherChild->sort_order);

        DepartmentHierarchyService::reorder($root->id, [$two->id, $one->id]);

        $this->assertSame([$two->id, $one->id], Department::query()->where('parent_id', $root->id)->ordered()->pluck('id')->all());
        $this->assertSame(1, $otherChild->fresh()->sort_order);
    }

    public function test_reorder_rejects_partial_or_foreign_lists(): void
    {
        $root = $this->group('Root');
        $one = $this->group('One', $root->id);
        $this->group('Two', $root->id);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::reorder($root->id, [$one->id]);
    }

    public function test_move_appends_last_and_keeps_branch(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $x = $this->group('X');
        $y = $this->group('Y');
        $existing = Department::create(['name' => 'Existing', 'parent_id' => $y->id, 'is_global' => true, 'department_code' => 'DEP2-0009']);
        $moved = $this->group('Moved', $x->id);
        $child = Department::create(['name' => 'Child', 'parent_id' => $moved->id, 'company_id' => $company->id]);

        DepartmentHierarchyService::move($moved, $y->id);

        $this->assertSame($y->id, $moved->fresh()->parent_id);
        $this->assertSame([$existing->id, $moved->id], Department::query()->where('parent_id', $y->id)->ordered()->pluck('id')->all());
        $this->assertSame($moved->id, $child->fresh()->parent_id);
        $this->assertSame(3, DepartmentHierarchyService::levelOf($child->fresh()));
    }

    public function test_move_into_own_descendant_is_rejected_without_partial_save(): void
    {
        $a = $this->group('A');
        $b = $this->group('B', $a->id);

        try {
            DepartmentHierarchyService::move($a, $b->id);
            $this->fail('Move into descendant was accepted.');
        } catch (ValidationException) {
            $this->assertNull($a->fresh()->parent_id);
        }
    }

    public function test_move_to_root_requires_group_scope(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $local = Department::create(['name' => 'Local', 'company_id' => $company->id, 'parent_id' => $main->id]);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::move($local, null);
    }

    public function test_child_companies_must_be_inside_parent_companies_when_parent_is_restricted(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);
        $c3 = Company::create(['trade_name' => 'C']);

        $group = $this->group('Group');
        $restricted = $this->sharedWith($this->group('Restricted', $group->id), [$c1->id, $c2->id]);

        $okChild = Department::create(['name' => 'Ok', 'company_id' => $c1->id, 'parent_id' => $restricted->id]);
        $this->assertSame($restricted->id, $okChild->parent_id);

        $this->expectException(ValidationException::class);
        Department::create(['name' => 'Outside', 'company_id' => $c3->id, 'parent_id' => $restricted->id]);
    }

    public function test_main_parent_accepts_subdepartments_of_any_company_and_rejects_empty(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);
        $group = $this->group('Group');
        $main = $this->group('Main', $group->id);

        DepartmentHierarchyService::save(['name' => 'One', 'company_id' => $c1->id, 'parent_id' => $main->id], null, DepartmentScope::SINGLE);
        DepartmentHierarchyService::save(['name' => 'Two', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$c1->id, $c2->id]);

        $this->assertSame(2, $main->children()->count());

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::save(['name' => 'Empty', 'parent_id' => $main->id], null, DepartmentScope::FAWAZ_GROUP);
    }

    public function test_shared_child_company_set_is_validated_by_form_rule(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);
        $restricted = $this->sharedWith($this->group('Restricted'), [$c1->id]);

        DepartmentHierarchyService::assertValidSave(['parent_id' => $restricted->id, 'is_global' => true], null, [$c1->id]);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::assertValidSave(['parent_id' => $restricted->id, 'is_global' => true], null, [$c1->id, $c2->id]);
    }

    public function test_moving_branch_rejects_exceeding_three_layers_or_incompatible_descendants(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $admin1 = $this->group('Admin 1');
        $admin2 = $this->group('Admin 2');
        $targetMain = $this->group('Target Main', $admin2->id);
        $branchMain = $this->group('Branch Main', $admin1->id);
        Department::create(['name' => 'Leaf', 'company_id' => $c1->id, 'parent_id' => $branchMain->id]);

        try {
            DepartmentHierarchyService::move($branchMain, $targetMain->id);
            $this->fail('Move exceeding three layers was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
            $this->assertSame($admin1->id, $branchMain->fresh()->parent_id);
        }
    }

    public function test_delete_is_blocked_by_children_and_employees_but_allowed_for_leaf(): void
    {
        $parent = $this->group('Parent');
        $child = $this->group('Child', $parent->id);

        $this->assertFalse($parent->delete());
        $this->assertSame(['has_children'], DepartmentHierarchyService::deletionBlockers($parent));

        DB::table('employees')->insert(['department_id' => $child->id]);
        $this->assertFalse($child->delete());
        $this->assertSame(['has_employees'], DepartmentHierarchyService::deletionBlockers($child));

        DB::table('employees')->delete();
        $this->assertSame([], DepartmentHierarchyService::deletionBlockers($child));
        $this->assertTrue($child->delete());
        $this->assertNotNull(Department::query()->find($parent->id));
        $this->assertNull(Department::query()->find($child->id));
    }

    public function test_shift_swaps_neighbours_only_within_the_same_sibling_group(): void
    {
        $root = $this->group('Root');
        $a = $this->group('A', $root->id);
        $b = $this->group('B', $root->id);
        $c = $this->group('C', $root->id);

        DepartmentHierarchyService::shift($c, -1);
        $this->assertSame([$a->id, $c->id, $b->id], $root->children()->ordered()->pluck('id')->all());

        DepartmentHierarchyService::shift($a, -1);
        $this->assertSame([$a->id, $c->id, $b->id], $root->children()->ordered()->pluck('id')->all());

        DepartmentHierarchyService::shift($b, 1);
        $this->assertSame([$a->id, $c->id, $b->id], $root->children()->ordered()->pluck('id')->all());
    }

    public function test_parent_options_show_paths_and_exclude_self_descendants_and_third_level(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sub = Department::create(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id]);
        $other = $this->group('Other');

        $optionsForMain = DepartmentHierarchyService::parentOptions(Department::query(), $main);

        $this->assertSame('Root', $optionsForMain[$root->id]);
        $this->assertSame('Other', $optionsForMain[$other->id]);
        $this->assertArrayNotHasKey($main->id, $optionsForMain);
        $this->assertArrayNotHasKey($sub->id, $optionsForMain);

        $all = DepartmentHierarchyService::parentOptions(Department::query());
        $this->assertSame('Root / Main', $all[$main->id]);
        $this->assertArrayNotHasKey($sub->id, $all);
    }

    public function test_path_labels_never_reveal_ancestors_outside_the_visible_set(): void
    {
        $root = $this->group('Hidden Root');
        $main = $this->group('Main', $root->id);
        $sub = $this->group('Sub', $main->id);

        $visible = Department::query()->whereKey([$main->id, $sub->id])->get(['id', 'parent_id', 'name']);

        $labels = DepartmentHierarchyService::pathLabels($visible);

        $this->assertSame('Main / Sub', $labels[$sub->id]);
        $this->assertStringNotContainsString('Hidden Root', implode('|', $labels));
    }

    public function test_description_is_nullable_and_blank_becomes_null(): void
    {
        $withText = Department::create(['name' => 'A', 'is_global' => true, 'description' => 'Handles shared accounts']);
        $blank = Department::create(['name' => 'B', 'is_global' => true, 'description' => '']);

        $this->assertSame('Handles shared accounts', $withText->fresh()->description);
        $this->assertNull($blank->fresh()->description);
    }

    public function test_roots_scope_and_existing_unclassified_departments_stay_listed(): void
    {
        $legacy = Department::create(['name' => 'Legacy local', 'company_id' => Company::create(['trade_name' => 'A'])->id]);
        $root = $this->group('Root');
        $this->group('Child', $root->id);

        $ids = Department::query()->roots()->ordered()->pluck('id')->all();

        $this->assertSame([$legacy->id, $root->id], $ids);
    }

    public function test_shared_assignment_normalizes_single_company_and_allows_organizational_dependents(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        DB::table('employees')->insert(['department_id' => $main->id, 'company_id' => $b->id]);

        $singleFromShared = DepartmentHierarchyService::save(
            ['name' => 'Normalized', 'parent_id' => $main->id],
            null,
            DepartmentScope::SHARED,
            [$a->id],
        );

        $this->assertFalse($singleFromShared->is_global);
        $this->assertSame($a->id, $singleFromShared->company_id);
        $this->assertSame([$a->id], $singleFromShared->getAssignedCompanyIds());
        $this->assertSame(2, DepartmentHierarchyService::levelOf($main));
    }

    public function test_scope_change_cannot_strand_an_employee(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $department = DepartmentHierarchyService::save(['name' => 'Team', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        DB::table('employees')->insert(['department_id' => $department->id, 'company_id' => $b->id]);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::save(['parent_id' => $main->id, 'company_id' => $a->id], $department, DepartmentScope::SINGLE);
    }

    public function test_local_new_root_is_rejected_but_unchanged_legacy_root_can_be_edited(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $legacy = Department::create(['name' => 'Legacy', 'company_id' => $company->id]);
        $saved = DepartmentHierarchyService::save(['name' => 'Legacy edited', 'company_id' => $company->id], $legacy, DepartmentScope::SINGLE);
        $this->assertSame('Legacy edited', $saved->name);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::save(['name' => 'New root', 'company_id' => $company->id], null, DepartmentScope::SINGLE);
    }

    public function test_staff_cannot_reorder_a_sibling_group_containing_hidden_records(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $one = Department::create(['name' => 'Visible', 'company_id' => $a->id, 'parent_id' => $main->id]);
        $two = Department::create(['name' => 'Hidden', 'company_id' => $b->id, 'parent_id' => $main->id]);
        $user = new User(['user_type' => UserType::SAUDI_EMPLOYEE]);
        $user->forceFill(['id' => 100]);
        $user->setRelation('roles', collect());
        $user->setRelation('employee', new Employee(['company_id' => $a->id]));
        $this->actingAs($user);
        Gate::define('update', fn () => true);

        $this->assertFalse(DepartmentHierarchyService::canReorder($one));
        try {
            DepartmentHierarchyService::shift($one, 1);
            $this->fail('A hidden sibling was reordered.');
        } catch (AuthorizationException) {
            $this->assertSame([$one->id, $two->id], $main->children()->ordered()->pluck('id')->all());
        }
    }

    public function test_filament_creates_and_browses_three_layers_and_hides_fourth_layer_creation(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        Livewire::test(CreateDepartment::class)->fillForm([
            'name' => 'Operations', 'status' => 'active',
        ])->call('create')->assertHasNoFormErrors();
        $root = Department::query()->where('name', 'Operations')->sole();

        Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')
            ->assertFormFieldIsHidden('companies')
            ->fillForm(['name' => 'Supervision', 'status' => 'active'])
            ->call('create')->assertHasNoFormErrors();
        $main = Department::query()->where('name', 'Supervision')->sole();
        $this->assertTrue($main->is_global);
        $this->assertNull($main->company_id);

        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsVisible('department_scope')
            ->assertFormFieldIsVisible('company_id')
            ->fillForm([
                'name' => 'Team', 'department_scope' => 'single',
                'company_id' => $company->id, 'status' => 'active',
            ])->call('create')->assertHasNoFormErrors();
        $sub = Department::query()->where('name', 'Team')->sole();

        Livewire::withQueryParams([])->test(ListDepartments::class)->assertCanSeeTableRecords([$root])->assertCanNotSeeTableRecords([$main, $sub]);
        Livewire::withQueryParams(['parent' => $root->id])->test(ListDepartments::class)
            ->assertCanSeeTableRecords([$main])->assertCanNotSeeTableRecords([$root, $sub])
            ->assertActionVisible('create');
        Livewire::withQueryParams(['parent' => $main->id])->test(ListDepartments::class)
            ->assertCanSeeTableRecords([$sub])->assertCanNotSeeTableRecords([$root, $main])
            ->assertActionVisible('create');
    }

    public function test_filament_rejects_removing_a_local_parent_without_changing_scope(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $child = Department::create(['name' => 'Child', 'company_id' => $company->id, 'parent_id' => $main->id]);
        Livewire::test(EditDepartment::class, ['record' => $child->id])
            ->fillForm(['parent_id' => null, 'department_scope' => 'single', 'company_id' => $company->id])
            ->call('save')->assertHasFormErrors();
        $this->assertSame($main->id, $child->fresh()->parent_id);
    }

    public function test_filament_scope_rejection_does_not_sync_companies_before_validation(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        DB::table('employees')->insert(['department_id' => $sub->id, 'company_id' => $b->id]);
        Livewire::test(EditDepartment::class, ['record' => $sub->id])
            ->fillForm(['department_scope' => 'single', 'company_id' => $a->id])->call('save')->assertHasFormErrors(['company_id']);
        $this->assertSame([$a->id, $b->id], $sub->companies()->orderBy('companies.id')->pluck('companies.id')->all());
    }

    public function test_filament_moves_a_main_department_between_administrations_and_keeps_code_and_children(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $first = $this->group('First');
        $second = $this->group('Second');
        $existing = Department::create(['name' => 'Existing', 'parent_id' => $second->id, 'is_global' => true, 'department_code' => 'DEP2-0009']);
        $main = $this->group('Main', $first->id);
        $leaf = Department::create(['name' => 'Leaf', 'company_id' => $company->id, 'parent_id' => $main->id]);
        $code = $main->department_code;

        Livewire::test(EditDepartment::class, ['record' => $main->id])
            ->fillForm(['parent_id' => $second->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($code, $main->fresh()->department_code);
        $this->assertSame($main->id, $leaf->fresh()->parent_id);
        $this->assertSame([$existing->id, $main->id], $second->children()->ordered()->pluck('id')->all());
        $this->assertSame(2, DepartmentHierarchyService::levelOf($main->fresh()));
        $this->assertSame(3, DepartmentHierarchyService::levelOf($leaf->fresh()));
    }

    public function test_old_unclassified_roots_are_available_from_their_filter(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $legacy = Department::create(['name' => 'Legacy', 'company_id' => $company->id]);
        $root = $this->group('Root');
        Livewire::test(ListDepartments::class)->assertCanSeeTableRecords([$root])->assertCanNotSeeTableRecords([$legacy])
            ->filterTable('unclassified')->assertCanSeeTableRecords([$legacy])->assertCanNotSeeTableRecords([$root]);
    }

    public function test_search_stays_inside_current_level_unless_all_levels_is_selected(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $child = Department::create(['name' => 'Needle', 'company_id' => $company->id, 'parent_id' => $main->id]);
        Livewire::test(ListDepartments::class)->searchTable('Needle')->assertCanNotSeeTableRecords([$child])
            ->filterTable('all_levels')->assertCanSeeTableRecords([$child]);
    }

    public function test_scope_change_cannot_strand_an_employee_or_a_job_title(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        foreach (['employees', 'job_titles'] as $table) {
            $department = DepartmentHierarchyService::save(['name' => $table, 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
            DB::table($table)->insert(['department_id' => $department->id, 'company_id' => $b->id]);
            try {
                DepartmentHierarchyService::save(['company_id' => $a->id], $department, DepartmentScope::SINGLE);
                $this->fail('An assigned record was stranded.');
            } catch (ValidationException) {
                $this->assertCount(2, $department->fresh()->companies);
            }
        }
    }

    public function test_deleting_a_department_with_job_titles_is_blocked(): void
    {
        $department = $this->group('Root');
        DB::table('job_titles')->insert(['department_id' => $department->id]);
        $this->assertSame(['has_job_titles'], DepartmentHierarchyService::deletionBlockers($department));
        $this->assertFalse($department->delete());
    }

    public function test_ai_creates_three_layers_and_rejects_fourth_or_companies_on_general_levels(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $tool = new CreateDepartmentTool;

        $adminResult = $tool->execute(['name' => 'AI administration']);
        $this->assertTrue($adminResult['success']);
        $root = Department::query()->where('name', 'AI administration')->sole();
        $this->assertNull($root->company_id);

        $badMain = $tool->execute(['name' => 'AI main with company', 'parent_id' => $root->id, 'company_id' => $a->id]);
        $this->assertFalse($badMain['success']);

        $mainResult = $tool->execute(['name' => 'AI main', 'parent_id' => $root->id]);
        $this->assertTrue($mainResult['success']);
        $main = Department::query()->where('name', 'AI main')->sole();
        $this->assertTrue($main->is_global);
        $this->assertNull($main->company_id);

        $subResult = $tool->execute(['name' => 'AI shared sub', 'parent_id' => $main->id, 'companies' => [$a->id, $b->id]]);
        $this->assertTrue($subResult['success']);
        $sub = Department::query()->where('name', 'AI shared sub')->sole();
        $this->assertSame([$a->id, $b->id], $sub->companies()->orderBy('companies.id')->pluck('companies.id')->all());

        $fourthResult = $tool->execute(['name' => 'AI fourth', 'parent_id' => $sub->id, 'company_id' => $a->id]);
        $this->assertFalse($fourthResult['success']);
    }

    public function test_unauthorized_parent_returns_404_and_cannot_be_used_to_create_a_department(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $hidden = Department::create(['name' => 'Hidden', 'company_id' => $b->id]);
        $this->actAsStaff($a->id);
        Livewire::withQueryParams(['parent' => $hidden->id])->test(ListDepartments::class)->assertNotFound();
        $this->expectException(AuthorizationException::class);
        DepartmentHierarchyService::save(['name' => 'Forbidden', 'parent_id' => $hidden->id, 'company_id' => $a->id], null, DepartmentScope::SINGLE);
    }

    public function test_authorized_staff_can_reorder_their_own_sibling_group(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $a = Department::create(['name' => 'A', 'company_id' => $company->id, 'parent_id' => $main->id]);
        $b = Department::create(['name' => 'B', 'company_id' => $company->id, 'parent_id' => $main->id]);
        $this->actAsStaff($company->id);
        $this->assertTrue(DepartmentHierarchyService::canReorder($a));
        DepartmentHierarchyService::shift($a, 1);
        $this->assertSame([$b->id, $a->id], $main->children()->ordered()->pluck('id')->all());
    }

    public function test_numeric_order_cannot_bypass_hidden_sibling_protection(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $one = Department::create(['name' => 'Visible', 'company_id' => $a->id, 'parent_id' => $main->id]);
        $two = Department::create(['name' => 'Hidden', 'company_id' => $b->id, 'parent_id' => $main->id]);
        $this->actAsStaff($a->id);
        try {
            DepartmentHierarchyService::save(['name' => 'Changed', 'sort_order' => 2], $one, DepartmentScope::SINGLE);
            $this->fail('Numeric sorting changed hidden siblings.');
        } catch (AuthorizationException) {
            $this->assertSame('Visible', $one->fresh()->name);
            $this->assertSame([$one->id, $two->id], $main->children()->ordered()->pluck('id')->all());
        }
    }

    public function test_shared_company_pointer_and_nullable_description_are_saved_atomically(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $department = DepartmentHierarchyService::save(['name' => 'Shared', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        $code = $department->department_code;
        $department = DepartmentHierarchyService::save(['description' => ''], $department, DepartmentScope::SHARED, [$b->id]);
        $this->assertSame($b->id, $department->company_id);
        $this->assertFalse($department->is_global);
        $this->assertSame([$b->id], $department->getAssignedCompanyIds());
        $this->assertNull($department->description);
        $this->assertSame($code, $department->department_code);
    }

    public function test_service_accepts_three_layers_and_rejects_fourth_layer_without_rewriting_ancestors(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = DepartmentHierarchyService::save(['name' => 'Main', 'parent_id' => $root->id], null, DepartmentScope::FAWAZ_GROUP);
        $sub = DepartmentHierarchyService::save(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);

        $this->assertSame(3, DepartmentHierarchyService::levelOf($sub));
        $this->assertSame(__('organization.departments.hierarchy.levels.sub'), DepartmentHierarchyService::levelLabel($sub));
        $this->assertNull($root->fresh()->parent_id);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::save(['name' => 'Level 4', 'parent_id' => $sub->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
    }

    public function test_administration_and_main_creation_hide_parent_and_affiliation_and_use_dynamic_labels(): void
    {
        $page = Livewire::test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')
            ->assertFormSet(['parent_id' => null, 'department_scope' => 'fawaz_group'])
            ->assertSee(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.administration')]));
        $this->assertSame(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.administration')]), $page->instance()->getTitle());

        $root = $this->group('Root');
        $mainPage = Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')
            ->assertFormFieldIsHidden('companies')
            ->assertFormSet(['parent_id' => $root->id, 'department_scope' => 'fawaz_group'])
            ->assertSee(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.main')]));
        $this->assertSame(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.main')]), $mainPage->instance()->getTitle());
    }

    public function test_subdepartment_inside_main_requires_explicit_company_and_rejects_forged_parent(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);

        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsVisible('department_scope')
            ->assertFormFieldIsVisible('company_id')
            ->fillForm(['name' => 'Sub without company'])->call('create')->assertHasFormErrors(['company_id']);

        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->set('data.parent_id', null)
            ->fillForm(['name' => 'Forged sub', 'department_scope' => 'single', 'company_id' => $a->id])
            ->call('create')->assertHasNoFormErrors();

        $sub = Department::query()->where('name', 'Forged sub')->sole();
        $this->assertSame($main->id, $sub->parent_id);
        $this->assertSame([$a->id], $sub->getAssignedCompanyIds());
        $this->assertStringStartsWith('DEP3-', $sub->department_code);
    }

    public function test_create_another_keeps_the_locked_creation_context(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->fillForm(['name' => 'First child', 'department_scope' => 'single', 'company_id' => $company->id])->call('create', true)->assertHasNoFormErrors()
            ->assertSet('contextParentId', $main->id)
            ->fillForm(['name' => 'Second child', 'department_scope' => 'single', 'company_id' => $company->id])->call('create')->assertHasNoFormErrors();
        $this->assertSame(2, $main->children()->count());
    }

    public function test_edit_keeps_parent_selector_and_dynamic_labels_follow_the_current_parent(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sub = Department::create(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id]);
        $page = Livewire::test(EditDepartment::class, ['record' => $sub->id])->assertFormFieldIsVisible('parent_id');
        $this->assertSame(__('organization.departments.hierarchy.form.edit', ['type' => __('organization.departments.hierarchy.levels.sub')]), $page->instance()->getTitle());
        $page->fillForm(['parent_id' => $root->id]);
        $this->assertSame(__('organization.departments.hierarchy.form.edit', ['type' => __('organization.departments.hierarchy.levels.main')]), $page->instance()->getTitle());
    }

    public function test_navigation_parent_cannot_be_changed_through_a_livewire_update(): void
    {
        $root = $this->group('Root');
        $page = Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $page->set('contextParentId', null);
    }

    public function test_staff_can_create_subdepartment_for_authorized_company_but_not_general_structure(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $this->actAsStaff($a->id);

        Livewire::test(CreateDepartment::class)->assertForbidden();
        Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class)->assertForbidden();

        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->fillForm(['name' => 'Staff sub', 'department_scope' => 'single', 'company_id' => $a->id])
            ->call('create')->assertHasNoFormErrors();
        $this->assertSame([$a->id], Department::query()->where('name', 'Staff sub')->sole()->getAssignedCompanyIds());

        $this->expectException(AuthorizationException::class);
        DepartmentHierarchyService::save(['name' => 'Unauthorized', 'parent_id' => $main->id, 'company_id' => $b->id], null, DepartmentScope::SINGLE);
    }

    public function test_editing_an_independent_administration_hides_and_preserves_its_scope(): void
    {
        $root = $this->group('Root');
        Livewire::test(EditDepartment::class, ['record' => $root->id])
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')->assertFormFieldIsHidden('companies')
            ->assertFormFieldIsVisible('parent_id')
            ->fillForm(['name' => 'Updated root'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Updated root', $root->fresh()->name);
        $this->assertTrue($root->fresh()->is_global);
        $this->assertNull($root->fresh()->company_id);
        $this->assertSame([], $root->fresh()->getAssignedCompanyIds());
    }

    public function test_moving_an_administration_under_a_main_department_reveals_affiliation_choices(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $other = $this->group('Other');
        $otherMain = $this->group('Other Main', $other->id);
        Livewire::test(EditDepartment::class, ['record' => $root->id])
            ->fillForm(['parent_id' => $otherMain->id])
            ->assertFormFieldIsVisible('department_scope')->assertFormFieldIsVisible('company_id')
            ->fillForm(['company_id' => $company->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($otherMain->id, $root->fresh()->parent_id);
        $this->assertSame([$company->id], $root->fresh()->getAssignedCompanyIds());
    }

    public function test_shared_subdepartment_is_not_visible_to_unrelated_company_staff(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $c = Company::create(['trade_name' => 'C']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $shared = DepartmentHierarchyService::save(['name' => 'Shared AB', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);

        $this->actAsStaff($c->id);
        $visibleIds = CompanyAccessScopeService::apply(Department::query())->pluck('id')->all();
        $this->assertContains($root->id, $visibleIds);
        $this->assertContains($main->id, $visibleIds);
        $this->assertNotContains($shared->id, $visibleIds);
    }

    public function test_branch_restricted_staff_cannot_see_shared_departments_outside_their_companies(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $c = Company::create(['trade_name' => 'C']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sharedAB = DepartmentHierarchyService::save(['name' => 'Shared AB', 'parent_id' => $main->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        $localC = DepartmentHierarchyService::save(['name' => 'Local C', 'parent_id' => $main->id, 'company_id' => $c->id], null, DepartmentScope::SINGLE);

        $user = new User(['user_type' => UserType::SAUDI_EMPLOYEE, 'branch_id' => 10]);
        $user->forceFill(['id' => 101, 'status' => 'active']);
        $user->exists = true;
        $user->setRelation('roles', collect());
        $user->setRelation('employee', new Employee(['company_id' => $c->id, 'branch_id' => 10]));
        $this->actingAs($user);

        $visibleIds = CompanyAccessScopeService::apply(Department::query())->pluck('id')->all();
        $this->assertContains($root->id, $visibleIds);
        $this->assertContains($main->id, $visibleIds);
        $this->assertContains($localC->id, $visibleIds);
        $this->assertNotContains($sharedAB->id, $visibleIds);
    }

    public function test_owner_cannot_update_or_delete_general_structure_even_with_global_gate_before(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sub = Department::create(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id]);

        $owner = new User(['user_type' => UserType::OWNER]);
        $owner->forceFill(['id' => 102, 'status' => 'active']);
        $owner->exists = true;
        $owner->setRelation('roles', collect());
        DB::table('owners')->insert(['user_id' => $owner->id, 'company_id' => $company->id]);
        $this->actingAs($owner);

        $this->assertFalse(Gate::allows('update', $root));
        $this->assertFalse(Gate::allows('delete', $root));
        $this->assertFalse(Gate::allows('update', $main));
        $this->assertFalse(Gate::allows('delete', $main));
        $this->assertTrue(Gate::allows('update', $sub));
        $this->assertTrue(Gate::allows('delete', $sub));
    }

    public function test_edit_form_move_between_layer_two_and_layer_three_enforces_company_rules(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $admin = $this->group('Admin');
        $main = $this->group('Main', $admin->id);
        $movingMain = $this->group('Moving Main', $admin->id);
        $sub = Department::create(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id]);

        Livewire::test(EditDepartment::class, ['record' => $movingMain->id])
            ->fillForm(['parent_id' => $main->id])
            ->call('save')
            ->assertHasFormErrors(['company_id']);
        $this->assertSame($admin->id, $movingMain->fresh()->parent_id);

        Livewire::test(EditDepartment::class, ['record' => $sub->id])
            ->fillForm(['parent_id' => $admin->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame($admin->id, $sub->fresh()->parent_id);
        $this->assertTrue($sub->fresh()->is_global);
        $this->assertNull($sub->fresh()->company_id);
        $this->assertSame([], $sub->fresh()->getAssignedCompanyIds());
    }

    public function test_edit_form_rejects_moving_parent_with_children_when_children_would_violate_layer_rules(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $admin1 = $this->group('Admin 1');
        $admin2 = $this->group('Admin 2');
        $main = $this->group('Main', $admin1->id);
        Department::create(['name' => 'Sub', 'parent_id' => $main->id, 'company_id' => $company->id]);

        // Promoting a Main (with company-assigned Sub) to Layer 1 would leave Sub in Layer 2 with companies
        Livewire::test(EditDepartment::class, ['record' => $main->id])
            ->fillForm(['parent_id' => null])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
        $this->assertSame($admin1->id, $main->fresh()->parent_id);

        // Moving Admin 1 (with general Main) under Admin 2 would leave Main in Layer 3 without companies
        Livewire::test(EditDepartment::class, ['record' => $admin1->id])
            ->fillForm(['parent_id' => $admin2->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
        $this->assertNull($admin1->fresh()->parent_id);
    }

    public function test_fixed_root_affiliation_is_enforced_by_the_save_service(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $saved = DepartmentHierarchyService::save(['name' => 'Updated', 'parent_id' => null, 'company_id' => $company->id],
            $root, DepartmentScope::SINGLE);
        $this->assertTrue($saved->is_global);
        $this->assertNull($saved->company_id);
        $this->assertSame([], $saved->getAssignedCompanyIds());
    }

    public function test_codes_restart_for_each_parent_and_continue_across_companies(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $otherCompany = Company::create(['trade_name' => 'B']);
        $admin1 = $this->group('Admin One');
        $admin2 = $this->group('Admin Two');
        $main1 = $this->group('Main One', $admin1->id);
        $main2 = $this->group('Main Two', $admin1->id);
        $main3 = $this->group('Main Three', $admin2->id);
        $sub1 = Department::create(['name' => 'Sub One', 'parent_id' => $main1->id, 'company_id' => $company->id]);
        $sub2 = Department::create(['name' => 'Sub Two', 'parent_id' => $main1->id, 'company_id' => $otherCompany->id]);
        $sub3 = Department::create(['name' => 'Sub Three', 'parent_id' => $main3->id, 'company_id' => $company->id]);

        $this->assertSame('DEP2-0001', $main1->department_code);
        $this->assertSame('DEP2-0002', $main2->department_code);
        $this->assertSame('DEP2-0001', $main3->department_code);
        $this->assertSame('DEP3-0001', $sub1->department_code);
        $this->assertSame('DEP3-0002', $sub2->department_code);
        $this->assertSame('DEP3-0001', $sub3->department_code);
    }

    public function test_new_codes_append_after_maximum_and_ignore_display_order(): void
    {
        $admin = $this->group('Admin');
        $first = $this->group('First', $admin->id);
        $second = $this->group('Second', $admin->id);
        DB::table('departments')->where('id', $second->id)->update(['department_code' => 'DEP2-0010']);

        DepartmentHierarchyService::reorder($admin->id, [$second->id, $first->id]);
        $third = $this->group('Third', $admin->id);

        $this->assertTrue($first->id < $second->id);
        $this->assertSame('DEP2-0001', $first->fresh()->department_code);
        $this->assertSame('DEP2-0010', $second->fresh()->department_code);
        $this->assertSame('DEP2-0011', $third->department_code);
    }

    public function test_move_rejects_duplicate_sibling_code_without_changing_the_record(): void
    {
        $admin1 = $this->group('Admin One');
        $admin2 = $this->group('Admin Two');
        $main1 = $this->group('Main One', $admin1->id);
        $this->group('Main Two', $admin2->id);

        try {
            DepartmentHierarchyService::move($main1, $admin2->id);
            $this->fail('Expected a duplicate sibling code to prevent the move.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parent_id', $exception->errors());
        }

        $this->assertSame($admin1->id, $main1->fresh()->parent_id);
        $this->assertSame('DEP2-0001', $main1->fresh()->department_code);
    }

    public function test_explicit_duplicate_sibling_codes_are_rejected(): void
    {
        $admin = $this->group('Admin');
        $main = $this->group('Main', $admin->id);

        $this->expectException(ValidationException::class);
        Department::create(['name' => 'Duplicate', 'parent_id' => $admin->id, 'is_global' => true, 'department_code' => $main->department_code]);
    }

    public function test_filament_rejects_a_move_with_a_duplicate_code_without_changing_parent(): void
    {
        $first = $this->group('First');
        $second = $this->group('Second');
        $main = $this->group('Main', $first->id);
        $this->group('Existing', $second->id);

        Livewire::test(EditDepartment::class, ['record' => $main->id])
            ->fillForm(['parent_id' => $second->id])->call('save')->assertHasFormErrors(['parent_id']);

        $this->assertSame($first->id, $main->fresh()->parent_id);
        $this->assertSame('DEP2-0001', $main->fresh()->department_code);
    }

    public function test_default_generator_still_checks_uniqueness_across_the_table(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $admin = $this->group('Admin');
        $main = $this->group('Main', $admin->id);
        DB::table('departments')->where('id', $main->id)->update(['department_code' => 'TEST-0001']);

        $code = CodeGeneratorService::generate(
            Department::class, 'department_code', 'TEST', $company->id,
            scopeQuery: fn ($query) => $query->whereKey($admin->id),
            ignoreCompany: true,
        );

        $this->assertSame('TEST-0002', $code);
    }

    public function test_super_admin_can_assign_and_update_saudi_manager_on_first_layer_only(): void
    {
        $saudiUser = new User;
        $saudiUser->forceFill(['name' => 'Saudi Manager', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $saudiEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $saudiUser->id,
            'status' => 'active',
        ]));

        $otherSaudiUser = new User;
        $otherSaudiUser->forceFill(['name' => 'Second Saudi', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $otherSaudiEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $otherSaudiUser->id,
            'status' => 'active',
        ]));

        Livewire::test(CreateDepartment::class)
            ->assertFormFieldIsVisible('manager_id')
            ->fillForm([
                'name' => 'Group Operations',
                'manager_id' => $saudiEmployee->id,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $administration = Department::where('name', 'Group Operations')->firstOrFail();
        $this->assertSame($saudiEmployee->id, $administration->manager_id);
        $this->assertSame('DEP1-0001', $administration->department_code);

        Livewire::test(EditDepartment::class, ['record' => $administration->id])
            ->assertFormFieldIsVisible('manager_id')
            ->fillForm(['manager_id' => $otherSaudiEmployee->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($otherSaudiEmployee->id, $administration->fresh()->manager_id);
        $this->assertSame('DEP1-0001', $administration->fresh()->department_code);

        Livewire::test(ListDepartments::class)
            ->assertCanSeeTableRecords([$administration])
            ->assertSee('Second Saudi');
    }

    public function test_manager_is_rejected_for_non_saudi_or_inactive_employee_and_lower_layers(): void
    {
        $foreignUser = new User;
        $foreignUser->forceFill(['name' => 'Foreign Employee', 'user_type' => UserType::FOREIGN_EMPLOYEE, 'status' => 'active'])->save();
        $foreignEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $foreignUser->id,
            'status' => 'active',
        ]));

        $inactiveUser = new User;
        $inactiveUser->forceFill(['name' => 'Inactive Saudi', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $inactiveEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $inactiveUser->id,
            'status' => 'inactive',
        ]));

        $saudiUser = new User;
        $saudiUser->forceFill(['name' => 'Active Saudi', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $saudiEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $saudiUser->id,
            'status' => 'active',
        ]));

        try {
            DepartmentHierarchyService::createFromContext([
                'name' => 'Invalid Foreign',
                'manager_id' => $foreignEmployee->id,
            ], null);
            $this->fail('Expected validation error for foreign employee.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }

        try {
            DepartmentHierarchyService::createFromContext([
                'name' => 'Invalid Inactive',
                'manager_id' => $inactiveEmployee->id,
            ], null);
            $this->fail('Expected validation error for inactive employee.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }

        $administration = $this->group('Administration');

        Livewire::withQueryParams(['parent' => $administration->id])
            ->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('manager_id');

        try {
            DepartmentHierarchyService::createFromContext([
                'name' => 'Main Dept',
                'manager_id' => $saudiEmployee->id,
            ], $administration->id);
            $this->fail('Expected validation error for second layer manager.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }
    }

    public function test_moving_administration_to_second_layer_clears_manager_and_staff_cannot_assign_manager(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $saudiUser = new User;
        $saudiUser->forceFill(['name' => 'Active Saudi', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $saudiEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $saudiUser->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]));

        $targetAdmin = $this->group('Target Admin');
        $movableAdmin = DepartmentHierarchyService::createFromContext([
            'name' => 'Movable Admin',
            'manager_id' => $saudiEmployee->id,
        ], null);

        $this->assertSame($saudiEmployee->id, $movableAdmin->manager_id);

        $moved = DepartmentHierarchyService::save([
            'name' => 'Movable Admin',
            'parent_id' => $targetAdmin->id,
            'manager_id' => $saudiEmployee->id,
        ], $movableAdmin, DepartmentScope::FAWAZ_GROUP, []);

        $this->assertNull($moved->manager_id);
        $this->assertSame($targetAdmin->id, $moved->parent_id);

        $this->actAsStaff($company->id);
        $this->expectException(AuthorizationException::class);
        DepartmentHierarchyService::createFromContext([
            'name' => 'Staff Admin',
            'manager_id' => $saudiEmployee->id,
        ], null);
    }

    public function test_legacy_shared_root_direct_model_save_and_departments_report_respect_manager_rules(): void
    {
        $companyA = Company::create(['trade_name' => 'A']);
        $companyB = Company::create(['trade_name' => 'B']);

        $saudiUser = new User;
        $saudiUser->forceFill(['name' => 'Report Manager', 'user_type' => UserType::SAUDI_EMPLOYEE, 'status' => 'active'])->save();
        $saudiEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $saudiUser->id,
            'status' => 'active',
        ]));

        $foreignUser = new User;
        $foreignUser->forceFill(['name' => 'Foreign Direct', 'user_type' => UserType::FOREIGN_EMPLOYEE, 'status' => 'active'])->save();
        $foreignEmployee = Employee::withoutEvents(fn () => Employee::forceCreate([
            'user_id' => $foreignUser->id,
            'status' => 'active',
        ]));

        // Legacy shared root (parent_id = null, is_global = true, linked companies) cannot have a manager.
        $legacySharedRoot = $this->sharedWith($this->group('Legacy Shared'), [$companyA->id, $companyB->id]);

        Livewire::test(EditDepartment::class, ['record' => $legacySharedRoot->id])
            ->assertFormFieldIsHidden('manager_id');

        try {
            DepartmentHierarchyService::save([
                'name' => 'Legacy Shared',
                'manager_id' => $saudiEmployee->id,
            ], $legacySharedRoot, DepartmentScope::SHARED, [$companyA->id, $companyB->id]);
            $this->fail('Expected validation error for legacy shared root manager.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }

        // Direct model save cannot bypass active Saudi employee check.
        try {
            Department::create([
                'name' => 'Direct Invalid',
                'is_global' => true,
                'manager_id' => $foreignEmployee->id,
            ]);
            $this->fail('Expected validation error on direct model create.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }

        // Valid Layer 1 administration appears with manager_name in DepartmentsReport.
        $admin = DepartmentHierarchyService::createFromContext([
            'name' => 'Executive Group',
            'manager_id' => $saudiEmployee->id,
        ], null);

        $report = app(DepartmentsReport::class);
        $columnKeys = array_column($report->getColumns(), 'key');
        $this->assertContains('manager_name', $columnKeys);

        $rows = $report->getQuery(null, null, null, 'all', 'Report Manager')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Report Manager', $report->formatRow($rows->first())['manager_name']);
        $this->assertSame($admin->id, $rows->first()->id);

        // Changing affiliation away from group scope while keeping manager_id must be rejected.
        try {
            $admin->update([
                'is_global' => false,
                'company_id' => $companyA->id,
            ]);
            $this->fail('Expected validation error when changing affiliation of managed administration.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }
        $this->assertTrue((bool) $admin->fresh()->is_global);
        $this->assertSame($saudiEmployee->id, $admin->fresh()->manager_id);

        // Editing an existing legacy administration with 4-layer descendants without changing parent_id succeeds.
        $legacyMain = $this->group('Legacy Main', $admin->id);
        $legacySub = Department::create(['name' => 'Legacy Sub', 'parent_id' => $legacyMain->id, 'company_id' => $companyA->id]);
        Department::withoutEvents(fn () => Department::create([
            'name' => 'Legacy Fourth',
            'parent_id' => $legacySub->id,
            'company_id' => $companyA->id,
        ]));

        Livewire::test(EditDepartment::class, ['record' => $admin->id])
            ->fillForm(['name' => 'Executive Group Updated', 'manager_id' => $saudiEmployee->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Executive Group Updated', $admin->fresh()->name);

        // Non-super-admin cannot clear manager_id directly on the model.
        $this->actAsStaff($companyA->id);
        $this->expectException(AuthorizationException::class);
        $admin->fresh()->update(['manager_id' => null]);
    }

    private function actAsStaff(int $companyId): void
    {
        $user = new User(['user_type' => UserType::SAUDI_EMPLOYEE]);
        $user->forceFill(['id' => 100, 'status' => 'active']);
        $user->exists = true;
        $user->setRelation('roles', collect());
        $user->setRelation('employee', new Employee(['company_id' => $companyId]));
        foreach (['view_any_department', 'create_department', 'update_department'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
    }
}
