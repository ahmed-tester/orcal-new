<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Filament\Resources\SaudiEmployees\Pages\EditSaudiEmployee;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeIdentityService;
use App\Services\PersonCapacityService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_from_owner_rejects_the_identity_on_a_different_employee_account(): void
    {
        $number = '1000000001';
        $this->employeeWithNumber('national_id', $number);
        $ownerUserId = $this->createUser('owner@example.test');
        $ownerId = DB::table('owners')->insertGetId(['user_id' => $ownerUserId, 'national_id' => $number]);

        try {
            PersonCapacityService::resolveUserForRegistration('employee', [
                'registration_source' => 'from_owner',
                'selected_owner_id' => $ownerId,
                'user_name' => 'Changed name',
            ], ['national_id' => $number]);
            $this->fail('The duplicate national ID should be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('data.national_id', $error->errors());
        }

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('users', ['id' => $ownerUserId, 'name' => 'Test user']);
    }

    public function test_an_employee_can_keep_their_number_but_cannot_change_it_to_another_employees_number(): void
    {
        $employee = $this->employeeWithNumber('national_id', '1000000001');
        $this->employeeWithNumber('national_id', '1000000002', 'other@example.test');

        EmployeeIdentityService::ensureAvailable('national_id', '1000000001', $employee);

        $this->expectException(ValidationException::class);
        EmployeeIdentityService::ensureAvailable('national_id', '1000000002', $employee);
    }

    public function test_the_saudi_employee_edit_form_displays_the_duplicate_error_on_the_identity_field(): void
    {
        $employee = $this->employeeWithNumber('national_id', '1000000001');
        $this->employeeWithNumber('national_id', '1000000002', 'other@example.test');
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'Test company']);
        DB::table('employees')->where('id', $employee->id)->update(['company_id' => $companyId]);
        DB::table('users')->where('id', $employee->user_id)->update(['phone' => '0500000001']);
        $adminId = $this->createUser('admin@example.test');
        DB::table('users')->where('id', $adminId)->update(['user_type' => UserType::SUPER_ADMIN->value]);

        $this->actingAs(User::findOrFail($adminId));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditSaudiEmployee::class, ['record' => $employee->id])
            ->fillForm(['national_id' => '1000000002'])
            ->call('save')
            ->assertHasFormErrors(['national_id' => __('employees.fields.errors.national_id_exists')]);

        $this->assertDatabaseHas('employee_details', ['employee_id' => $employee->id, 'national_id' => '1000000001']);
    }

    public function test_temporary_registration_can_share_the_identity_with_the_linked_account(): void
    {
        $number = '1000000001';
        $employee = $this->employeeWithNumber('national_id', $number);
        EmployeeIdentityService::ensureAvailable('national_id', $number, sharedUserId: $employee->user_id);

        $this->expectException(ValidationException::class);
        EmployeeIdentityService::ensureAvailable('national_id', $number, sharedUserId: $this->createUser('other@example.test'));
    }

    public function test_an_employee_cannot_change_to_another_employees_iqama(): void
    {
        $employee = $this->employeeWithNumber('iqama_number', '2000000001');
        $this->employeeWithNumber('iqama_number', '2000000002', 'other@example.test');

        $this->expectException(ValidationException::class);
        EmployeeIdentityService::ensureAvailable('iqama_number', '2000000002', $employee);
    }

    private function employeeWithNumber(string $field, string $number, string $email = 'employee@example.test'): Employee
    {
        $userId = $this->createUser($email);
        $employeeId = DB::table('employees')->insertGetId(['user_id' => $userId]);
        DB::table('employee_details')->insert(['employee_id' => $employeeId, $field => $number]);

        return Employee::findOrFail($employeeId);
    }

    private function createUser(string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Test user',
            'email' => $email,
            'password' => 'unused',
        ]);
    }
}
