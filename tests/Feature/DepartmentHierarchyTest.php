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
use App\Services\DepartmentHierarchyService;
use App\Services\DepartmentScopeService;
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
            $table->boolean('is_global')->default(false);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_code')->nullable()->unique();
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
            $table->foreignId('department_id')->nullable();
            $table->foreignId('company_id')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('company_id')->nullable();
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
        $administration = $this->group('Operations');
        $main = $this->group('Supervision', $administration->id);
        $sub = $this->group('Riyadh', $main->id);
        $deeper = $this->group('North Riyadh', $sub->id);

        $this->assertSame('ADM-0001', $administration->department_code);
        $this->assertSame('SEC-0001', $main->department_code);
        $this->assertSame('SUB-0001', $sub->department_code);
        $this->assertSame('SUB-0002', $deeper->department_code);

        DepartmentHierarchyService::move($sub, $administration->id);

        $this->assertSame('SUB-0001', $sub->fresh()->department_code);
        $this->assertSame(2, DepartmentHierarchyService::levelOf($sub->fresh()));
    }

    public function test_code_sequence_ignores_company_scope(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);

        $first = Department::create(['name' => 'One', 'company_id' => $c1->id]);
        $second = Department::create(['name' => 'Two', 'company_id' => $c2->id]);

        $this->assertSame('ADM-0001', $first->department_code);
        $this->assertSame('ADM-0002', $second->department_code);
    }

    public function test_code_generator_default_behavior_is_unchanged(): void
    {
        $company = Company::create(['trade_name' => 'A']);

        $code = CodeGeneratorService::generate(Department::class, 'department_code', 'DEP', $company->id, 3);

        $this->assertSame('DEP-C'.$company->id.'-001', $code);
    }

    public function test_chain_deeper_than_three_levels_is_allowed(): void
    {
        $parent = null;
        foreach (range(1, 6) as $i) {
            $parent = $this->group('Level '.$i, $parent?->id);
        }

        $this->assertSame(6, DepartmentHierarchyService::levelOf($parent));
        $this->assertCount(5, DepartmentHierarchyService::ancestorIds($parent));
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
        $x = $this->group('X');
        $y = $this->group('Y');
        $existing = $this->group('Existing', $y->id);
        $moved = $this->group('Moved', $x->id);
        $child = $this->group('Child', $moved->id);

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
        $local = Department::create(['name' => 'Local', 'company_id' => $company->id, 'parent_id' => $root->id]);

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

    public function test_group_parent_accepts_children_of_any_company(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);
        $group = $this->group('Group');

        Department::create(['name' => 'One', 'company_id' => $c1->id, 'parent_id' => $group->id]);
        Department::create(['name' => 'Two', 'company_id' => $c2->id, 'parent_id' => $group->id]);

        $this->assertSame(2, $group->children()->count());
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

    public function test_moving_branch_checks_descendants_against_new_parent(): void
    {
        $c1 = Company::create(['trade_name' => 'A']);
        $c2 = Company::create(['trade_name' => 'B']);
        $restricted = $this->sharedWith($this->group('Restricted'), [$c1->id]);

        $group = $this->group('Group');
        $branch = Department::create(['name' => 'Branch', 'company_id' => $c1->id, 'parent_id' => $group->id]);
        $leaf = Department::create(['name' => 'Leaf', 'company_id' => $c2->id, 'parent_id' => $group->id]);
        DB::table('departments')->where('id', $leaf->id)->update(['parent_id' => $branch->id]);

        try {
            DepartmentHierarchyService::move($branch, $restricted->id);
            $this->fail('Move with conflicting descendant was accepted.');
        } catch (ValidationException) {
            $this->assertSame($group->id, $branch->fresh()->parent_id);
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
        DB::table('users')->insert(['department_id' => $child->id]);
        $this->assertFalse($child->delete());

        DB::table('users')->delete();
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

    public function test_parent_options_show_paths_and_exclude_self_and_descendants(): void
    {
        $root = $this->group('Root');
        $main = $this->group('Main', $root->id);
        $sub = $this->group('Sub', $main->id);
        $other = $this->group('Other');

        $options = DepartmentHierarchyService::parentOptions(Department::query(), $main);

        $this->assertSame('Root', $options[$root->id]);
        $this->assertSame('Other', $options[$other->id]);
        $this->assertArrayNotHasKey($main->id, $options);
        $this->assertArrayNotHasKey($sub->id, $options);

        $all = DepartmentHierarchyService::parentOptions(Department::query());
        $this->assertSame('Root / Main / Sub', $all[$sub->id]);
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

    public function test_shared_assignment_cannot_be_narrowed_past_a_child_and_nothing_is_saved(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $parent = DepartmentHierarchyService::save(['name' => 'Parent', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        DepartmentHierarchyService::save(['name' => 'Child', 'parent_id' => $parent->id, 'company_id' => $b->id], null, DepartmentScope::SINGLE);

        try {
            DepartmentHierarchyService::save(['name' => 'Changed', 'parent_id' => $root->id], $parent, DepartmentScope::SHARED, [$a->id]);
            $this->fail('Conflicting child assignment was accepted.');
        } catch (ValidationException) {
            $this->assertSame('Parent', $parent->fresh()->name);
            $this->assertSame([$a->id, $b->id], $parent->companies()->orderBy('companies.id')->pluck('companies.id')->all());
        }
    }

    public function test_scope_change_cannot_strand_an_employee(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $department = DepartmentHierarchyService::save(['name' => 'Team', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        DB::table('employees')->insert(['department_id' => $department->id, 'company_id' => $b->id]);

        $this->expectException(ValidationException::class);
        DepartmentHierarchyService::save(['parent_id' => $root->id, 'company_id' => $a->id], $department, DepartmentScope::SINGLE);
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
        $one = Department::create(['name' => 'Visible', 'company_id' => $a->id, 'parent_id' => $root->id]);
        $two = Department::create(['name' => 'Hidden', 'company_id' => $b->id, 'parent_id' => $root->id]);
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
            $this->assertSame([$one->id, $two->id], $root->children()->ordered()->pluck('id')->all());
        }
    }

    public function test_filament_creates_and_browses_an_administration_and_its_child(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        Livewire::test(CreateDepartment::class)->fillForm([
            'name' => 'Operations', 'department_scope' => 'fawaz_group', 'status' => 'active',
        ])->call('create')->assertHasNoFormErrors();
        $root = Department::query()->where('name', 'Operations')->sole();
        Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class)->fillForm([
            'name' => 'Team', 'parent_id' => $root->id, 'department_scope' => 'single',
            'company_id' => $company->id, 'status' => 'active',
        ])->call('create')->assertHasNoFormErrors();
        $child = Department::query()->where('name', 'Team')->sole();

        Livewire::withQueryParams([])->test(ListDepartments::class)->assertCanSeeTableRecords([$root])->assertCanNotSeeTableRecords([$child]);
        Livewire::withQueryParams(['parent' => $root->id])->test(ListDepartments::class)
            ->assertCanSeeTableRecords([$child])->assertCanNotSeeTableRecords([$root]);
    }

    public function test_filament_rejects_removing_a_local_parent_without_changing_scope(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $child = Department::create(['name' => 'Child', 'company_id' => $company->id, 'parent_id' => $root->id]);
        Livewire::test(EditDepartment::class, ['record' => $child->id])
            ->fillForm(['parent_id' => null, 'department_scope' => 'single', 'company_id' => $company->id])
            ->call('save')->assertHasFormErrors();
        $this->assertSame($root->id, $child->fresh()->parent_id);
    }

    public function test_filament_scope_rejection_does_not_sync_companies_before_validation(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $parent = DepartmentHierarchyService::save(['name' => 'Parent', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        Department::create(['name' => 'Child', 'parent_id' => $parent->id, 'company_id' => $b->id]);
        Livewire::test(EditDepartment::class, ['record' => $parent->id])
            ->fillForm(['companies' => [$a->id]])->call('save')->assertHasFormErrors(['company_id']);
        $this->assertSame([$a->id, $b->id], $parent->companies()->orderBy('companies.id')->pluck('companies.id')->all());
    }

    public function test_filament_moves_a_local_department_and_keeps_its_code_and_children(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $first = $this->group('First');
        $second = $this->group('Second');
        $main = Department::create(['name' => 'Main', 'company_id' => $company->id, 'parent_id' => $first->id]);
        $sub = Department::create(['name' => 'Sub', 'company_id' => $company->id, 'parent_id' => $main->id]);
        $leaf = Department::create(['name' => 'Leaf', 'company_id' => $company->id, 'parent_id' => $sub->id]);
        $existing = Department::create(['name' => 'Existing', 'company_id' => $company->id, 'parent_id' => $second->id]);
        $code = $sub->department_code;

        Livewire::test(EditDepartment::class, ['record' => $sub->id])
            ->fillForm(['parent_id' => $second->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($code, $sub->fresh()->department_code);
        $this->assertSame($sub->id, $leaf->fresh()->parent_id);
        $this->assertSame([$existing->id, $sub->id], $second->children()->ordered()->pluck('id')->all());
        $this->assertSame(2, DepartmentHierarchyService::levelOf($sub->fresh()));
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
        $child = Department::create(['name' => 'Needle', 'company_id' => $company->id, 'parent_id' => $root->id]);
        Livewire::test(ListDepartments::class)->searchTable('Needle')->assertCanNotSeeTableRecords([$child])
            ->filterTable('all_levels')->assertCanSeeTableRecords([$child]);
    }

    public function test_scope_change_cannot_strand_a_user_or_a_job_title(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        foreach (['users', 'job_titles'] as $table) {
            $department = DepartmentHierarchyService::save(['name' => $table, 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
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

    public function test_ai_creates_group_roots_and_shared_children_through_the_same_save_service(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $tool = new CreateDepartmentTool;
        $result = $tool->execute(['name' => 'AI administration']);
        $this->assertTrue($result['success']);
        $root = Department::query()->where('name', 'AI administration')->sole();
        $this->assertNull($root->company_id);
        $result = $tool->execute(['name' => 'AI shared', 'parent_id' => $root->id, 'companies' => [$a->id, $b->id]]);
        $this->assertTrue($result['success']);
        $child = Department::query()->where('name', 'AI shared')->sole();
        $this->assertSame([$a->id, $b->id], $child->companies()->orderBy('companies.id')->pluck('companies.id')->all());
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
        $a = Department::create(['name' => 'A', 'company_id' => $company->id, 'parent_id' => $root->id]);
        $b = Department::create(['name' => 'B', 'company_id' => $company->id, 'parent_id' => $root->id]);
        $this->actAsStaff($company->id);
        $this->assertTrue(DepartmentHierarchyService::canReorder($a));
        DepartmentHierarchyService::shift($a, 1);
        $this->assertSame([$b->id, $a->id], $root->children()->ordered()->pluck('id')->all());
    }

    public function test_numeric_order_cannot_bypass_hidden_sibling_protection(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $one = Department::create(['name' => 'Visible', 'company_id' => $a->id, 'parent_id' => $root->id]);
        $two = Department::create(['name' => 'Hidden', 'company_id' => $b->id, 'parent_id' => $root->id]);
        $this->actAsStaff($a->id);
        try {
            DepartmentHierarchyService::save(['name' => 'Changed', 'sort_order' => 2], $one, DepartmentScope::SINGLE);
            $this->fail('Numeric sorting changed hidden siblings.');
        } catch (AuthorizationException) {
            $this->assertSame('Visible', $one->fresh()->name);
            $this->assertSame([$one->id, $two->id], $root->children()->ordered()->pluck('id')->all());
        }
    }

    public function test_shared_company_pointer_and_nullable_description_are_saved_atomically(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $department = DepartmentHierarchyService::save(['name' => 'Shared', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        $code = $department->department_code;
        $department = DepartmentHierarchyService::save(['description' => ''], $department, DepartmentScope::SHARED, [$b->id]);
        $this->assertSame($b->id, $department->company_id);
        $this->assertSame([$b->id], $department->getAssignedCompanyIds());
        $this->assertNull($department->description);
        $this->assertSame($code, $department->department_code);
    }

    public function test_service_accepts_deep_local_subdepartments_without_loading_or_rewriting_ancestors(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $parent = $root;
        foreach (range(2, 12) as $depth) {
            $parent = DepartmentHierarchyService::save(['name' => 'Level '.$depth, 'parent_id' => $parent->id, 'company_id' => $company->id], null, DepartmentScope::SINGLE);
        }
        $this->assertSame(12, DepartmentHierarchyService::levelOf($parent));
        $this->assertSame(__('organization.departments.hierarchy.levels.sub'), DepartmentHierarchyService::levelLabel($parent));
        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_administration_creation_hides_parent_and_affiliation_and_uses_dynamic_labels(): void
    {
        $page = Livewire::test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')
            ->assertFormSet(['parent_id' => null, 'department_scope' => 'fawaz_group'])
            ->assertSee(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.administration')]));
        $this->assertSame(__('organization.departments.hierarchy.form.create', ['type' => __('organization.departments.hierarchy.levels.administration')]), $page->instance()->getTitle());
    }

    public function test_subdepartment_inherits_single_company_and_does_not_offer_parent_or_company_selects(): void
    {
        $company = Company::create(['trade_name' => 'Inherited company']);
        $root = $this->group('Root');
        $main = Department::create(['name' => 'Main', 'parent_id' => $root->id, 'company_id' => $company->id]);
        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsHidden('department_scope')
            ->assertFormFieldIsHidden('company_id')
            ->assertFormFieldIsHidden('companies')
            ->assertFormSet(['company_id' => $company->id, 'parent_id' => $main->id])
            ->assertSee('Inherited company')->assertSee('Main')
            ->fillForm(['name' => 'Inherited child'])->call('create')->assertHasNoFormErrors();
        $child = Department::query()->where('name', 'Inherited child')->sole();
        $this->assertSame($main->id, $child->parent_id);
        $this->assertSame([$company->id], $child->getAssignedCompanyIds());
        $this->assertStringStartsWith('SUB-', $child->department_code);
    }

    public function test_creation_inside_group_administration_still_requires_explicit_company(): void
    {
        Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        Livewire::withQueryParams(['parent' => $root->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')
            ->assertFormFieldIsVisible('department_scope')
            ->assertFormFieldIsVisible('company_id')
            ->fillForm(['name' => 'Main without company'])->call('create')->assertHasFormErrors(['company_id']);
    }

    public function test_shared_parent_defaults_all_companies_and_allows_narrowing_the_selection(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $shared = DepartmentHierarchyService::save(['name' => 'Shared parent', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        Livewire::withQueryParams(['parent' => $shared->id])->test(CreateDepartment::class)
            ->assertFormFieldIsHidden('parent_id')->assertFormFieldIsVisible('companies')
            ->assertFormSet(['department_scope' => 'shared', 'companies' => [$a->id, $b->id]])
            ->fillForm(['name' => 'Subset child', 'companies' => [$b->id]])->call('create')->assertHasNoFormErrors();
        $this->assertSame([$b->id], Department::query()->where('name', 'Subset child')->sole()->getAssignedCompanyIds());
    }

    public function test_creation_context_overrides_forged_parent_and_inherited_company_fields(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $main = Department::create(['name' => 'Main', 'parent_id' => $root->id, 'company_id' => $a->id]);
        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->set('data.parent_id', null)->set('data.company_id', $b->id)
            ->set('data.department_scope', 'shared')->set('data.companies', [$b->id])
            ->set('data.name', 'Forged child')->call('create')->assertHasNoFormErrors();
        $child = Department::query()->where('name', 'Forged child')->sole();
        $this->assertSame($main->id, $child->parent_id);
        $this->assertFalse($child->is_global);
        $this->assertSame([$a->id], $child->getAssignedCompanyIds());
    }

    public function test_shared_parent_rejects_companies_outside_its_assignment(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $shared = DepartmentHierarchyService::save(['name' => 'Shared', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id]);
        Livewire::withQueryParams(['parent' => $shared->id])->test(CreateDepartment::class)
            ->fillForm(['name' => 'Outside', 'companies' => [$b->id]])->call('create')->assertHasFormErrors();
        $this->assertFalse(Department::query()->where('name', 'Outside')->exists());
    }

    public function test_create_another_keeps_the_locked_creation_context_and_company_defaults(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = Department::create(['name' => 'Main', 'parent_id' => $root->id, 'company_id' => $company->id]);
        Livewire::withQueryParams(['parent' => $main->id])->test(CreateDepartment::class)
            ->fillForm(['name' => 'First child'])->call('create', true)->assertHasNoFormErrors()
            ->assertSet('contextParentId', $main->id)->assertFormSet(['company_id' => $company->id])
            ->fillForm(['name' => 'Second child'])->call('create')->assertHasNoFormErrors();
        $this->assertSame(2, $main->children()->count());
    }

    public function test_edit_keeps_parent_selector_and_dynamic_labels_follow_the_current_parent(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $main = Department::create(['name' => 'Main', 'parent_id' => $root->id, 'company_id' => $company->id]);
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

    public function test_shared_defaults_only_include_the_parents_companies_authorized_for_the_creator(): void
    {
        $a = Company::create(['trade_name' => 'A']);
        $b = Company::create(['trade_name' => 'B']);
        $root = $this->group('Root');
        $shared = DepartmentHierarchyService::save(['name' => 'Shared', 'parent_id' => $root->id], null, DepartmentScope::SHARED, [$a->id, $b->id]);
        $this->actAsStaff($a->id);
        Livewire::withQueryParams(['parent' => $shared->id])->test(CreateDepartment::class)
            ->assertFormSet(['companies' => [$a->id]])
            ->fillForm(['name' => 'Authorized subset'])->call('create')->assertHasNoFormErrors();
        $this->assertSame([$a->id], Department::query()->where('name', 'Authorized subset')->sole()->getAssignedCompanyIds());
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

    public function test_moving_an_administration_under_a_parent_reveals_affiliation_choices(): void
    {
        $company = Company::create(['trade_name' => 'A']);
        $root = $this->group('Root');
        $other = $this->group('Other');
        Livewire::test(EditDepartment::class, ['record' => $root->id])
            ->fillForm(['parent_id' => $other->id])
            ->assertFormFieldIsVisible('department_scope')->assertFormFieldIsVisible('company_id')
            ->fillForm(['company_id' => $company->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($other->id, $root->fresh()->parent_id);
        $this->assertSame([$company->id], $root->fresh()->getAssignedCompanyIds());
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
