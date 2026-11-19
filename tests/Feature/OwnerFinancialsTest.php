<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerSalaryService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OwnerFinancialsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_17_123833_create_permission_tables.php',
            '2026_09_17_132000_create_companies_and_organization_tables.php',
            '2026_09_21_150000_create_settings_table.php',
            '2026_10_13_103000_add_metadata_column_for_hijri_dates.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        DB::table('companies')->insert([
            ['id' => 1, 'trade_name' => 'First Company'],
            ['id' => 2, 'trade_name' => 'Second Company'],
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_owner_form_saves_other_amount_and_fixed_salary_separately_from_notes(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => UserType::SUPER_ADMIN]));

        Livewire::test(CreateOwner::class)
            ->fillForm([
                'company_id' => 1,
                'user_name' => 'New owner',
                'user_email' => 'new-owner@example.test',
                'user_phone' => '0501234567',
                'user_password' => 'testing-password',
                'employment_date' => '2026-01-01',
                'other_amount' => '25.50',
                'basic_salary' => '1800.75',
                'other_financials' => 'Keep these notes',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $owner = Owner::sole();
        $this->assertSame(25.5, $owner->other_amount);
        $this->assertSame(1800.75, $owner->basic_salary);
        $this->assertSame('Keep these notes', $owner->other_financials);

        Livewire::test(EditOwner::class, ['record' => $owner->getRouteKey()])
            ->assertFormSet(['other_amount' => 25.5, 'basic_salary' => 1800.75])
            ->fillForm(['other_amount' => 0, 'basic_salary' => '1900.25'])
            ->call('save')
            ->assertHasNoFormErrors();

        $owner->refresh();
        $this->assertSame(0.0, $owner->other_amount);
        $this->assertSame(1900.25, $owner->basic_salary);
        $this->assertSame('Keep these notes', $owner->other_financials);
    }

    public function test_owner_table_applies_one_salary_and_saves_an_individual_override(): void
    {
        $editor = $this->editor();
        $first = $this->owner(1);
        $second = $this->owner(1);
        $outside = $this->owner(2);
        $this->actingAs($editor);

        $undoRepeaterFake = Repeater::fake();

        try {
            $action = TestAction::make('bulkBasicSalary')->table();
            $apply = TestAction::make('applySalary')->schemaComponent('amount');

            Livewire::test(ListOwners::class)
                ->assertCanSeeTableRecords([$first, $second])
                ->assertCanNotSeeTableRecords([$outside])
                ->mountAction($action)
                ->assertSet('mountedActions.0.data.owners.0.owner_id', $first->id)
                ->assertSet('mountedActions.0.data.owners.1.owner_id', $second->id)
                ->set('mountedActions.0.data.amount', '1500.50')
                ->callAction($apply)
                ->assertSet('mountedActions.0.data.owners.0.basic_salary', 1500.5)
                ->assertSet('mountedActions.0.data.owners.1.basic_salary', 1500.5)
                ->set('mountedActions.0.data.owners.1.basic_salary', '1750.25')
                ->callMountedAction()
                ->assertHasNoActionErrors();
        } finally {
            $undoRepeaterFake();
        }

        $this->assertSame(1500.5, $first->refresh()->basic_salary);
        $this->assertSame(1750.25, $second->refresh()->basic_salary);
        $this->assertNull($outside->refresh()->basic_salary);
        $this->assertSame(25.5, $first->other_amount);
        $this->assertSame('Existing notes', $first->other_financials);
    }

    public function test_bulk_save_rejects_an_owner_outside_the_editors_company(): void
    {
        $this->actingAs($this->editor());
        $inside = $this->owner(1);
        $outside = $this->owner(2);

        try {
            OwnerSalaryService::save([
                ['owner_id' => $inside->id, 'basic_salary' => 800],
                ['owner_id' => $outside->id, 'basic_salary' => 900],
            ]);
            $this->fail('The out-of-scope owner should be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertNull($inside->refresh()->basic_salary);
        $this->assertNull($outside->refresh()->basic_salary);
    }

    public function test_branch_editors_only_review_owners_in_their_branch_company(): void
    {
        $editor = $this->editor();
        $branchId = DB::table('branches')->insertGetId(['company_id' => 1, 'name' => 'Main Branch']);
        Employee::where('user_id', $editor->id)->update(['branch_id' => $branchId]);
        $inside = $this->owner(1);
        $this->owner(2);
        $this->actingAs($editor);

        $this->assertSame([$inside->id], array_column(OwnerSalaryService::rowsForReview(), 'owner_id'));
    }

    private function editor(): User
    {
        $user = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE]);
        Employee::withoutEvents(fn () => Employee::create(['user_id' => $user->id, 'company_id' => 1]));
        $user->givePermissionTo(Permission::findOrCreate('view_any_owner'), Permission::findOrCreate('update_owner'));

        return $user;
    }

    private function owner(int $companyId): Owner
    {
        return Owner::create([
            'user_id' => User::factory()->create(['user_type' => UserType::OWNER])->id,
            'company_id' => $companyId,
            'other_amount' => 25.5,
            'other_financials' => 'Existing notes',
        ]);
    }
}
