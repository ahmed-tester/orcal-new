<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Filament\Resources\SaudiEmployees\Pages\EditSaudiEmployee;
use App\Filament\Resources\SaudiEmployees\Pages\ListSaudiEmployees;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PersonCapacityTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_two_lists_display_and_filter_by_account_relationships(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => UserType::SUPER_ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Test company']);
        $linkedUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE]);
        $employeeUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE]);
        $ownerUser = User::factory()->create(['user_type' => UserType::OWNER]);

        $linkedEmployee = Employee::create([
            'user_id' => $linkedUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'employee'],
        ]);
        $linkedOwner = Owner::create([
            'user_id' => $linkedUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'employee_and_owner'],
        ]);
        $employeeOnly = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'employee_and_owner'],
        ]);
        $ownerOnly = Owner::create([
            'user_id' => $ownerUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'employee_and_owner'],
        ]);

        Livewire::test(ListSaudiEmployees::class)
            ->assertTableColumnStateSet('person_capacity', __('employees.fields.capacity_employee_and_owner'), $linkedEmployee)
            ->assertTableColumnStateSet('person_capacity', __('employees.fields.capacity_employee'), $employeeOnly)
            ->filterTable('person_capacity', 'employee_and_owner')
            ->assertCanSeeTableRecords([$linkedEmployee])
            ->assertCanNotSeeTableRecords([$employeeOnly])
            ->filterTable('person_capacity', 'employee')
            ->assertCanSeeTableRecords([$employeeOnly])
            ->assertCanNotSeeTableRecords([$linkedEmployee]);

        Livewire::test(ListOwners::class)
            ->assertTableColumnStateSet('person_capacity', __('owners.fields.capacity_employee_and_owner'), $linkedOwner)
            ->assertTableColumnStateSet('person_capacity', __('owners.fields.capacity_owner'), $ownerOnly)
            ->filterTable('person_capacity', 'employee_and_owner')
            ->assertCanSeeTableRecords([$linkedOwner])
            ->assertCanNotSeeTableRecords([$ownerOnly])
            ->filterTable('person_capacity', 'owner')
            ->assertCanSeeTableRecords([$ownerOnly])
            ->assertCanNotSeeTableRecords([$linkedOwner]);
    }

    public function test_edit_pages_resolve_person_capacity_from_account_relationships(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => UserType::SUPER_ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Test company']);
        $linkedUser = User::factory()->create(['user_type' => UserType::SAUDI_EMPLOYEE]);

        $linkedEmployee = Employee::create([
            'user_id' => $linkedUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'employee'],
        ]);
        $linkedOwner = Owner::create([
            'user_id' => $linkedUser->id,
            'company_id' => $companyId,
            'metadata' => ['person_capacity' => 'owner'],
        ]);

        Livewire::test(EditSaudiEmployee::class, ['record' => $linkedEmployee->id])
            ->assertFormSet(['person_capacity' => 'employee_and_owner']);

        Livewire::test(EditOwner::class, ['record' => $linkedOwner->id])
            ->assertFormSet(['person_capacity' => 'employee_and_owner']);
    }
}
