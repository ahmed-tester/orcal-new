<?php

namespace Tests\Feature;

use App\Enums\AssetFundStatus;
use App\Enums\AssetFundVoucherType;
use App\Enums\UserType;
use App\Filament\Pages\AssetFundStatement;
use App\Filament\Resources\AssetFunds\AssetFundResource;
use App\Filament\Resources\AssetFundVouchers\AssetFundVoucherResource;
use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssetFundAccessScopeTest extends TestCase
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

        $this->artisan('migrate')->assertExitCode(0);
    }

    public function test_manager_and_accountant_with_permissions_see_and_act_only_on_assigned_funds(): void
    {
        $company = $this->createCompany();

        $managerUser = $this->createUser();
        $managerEmp = $this->createEmployee($managerUser, $company);

        $accountantUser = $this->createUser();
        $accountantEmp = $this->createEmployee($accountantUser, $company);

        $otherUser = $this->createUser();
        $otherEmp = $this->createEmployee($otherUser, $company);

        $allPermissions = [
            'view_any_asset_fund',
            'update_asset_fund',
            'delete_asset_fund',
            'view_any_asset_fund_voucher',
            'create_asset_fund_voucher',
            'update_asset_fund_voucher',
            'delete_asset_fund_voucher',
            'view_asset_fund_statement',
        ];

        foreach ($allPermissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName]);
            $managerUser->givePermissionTo($perm);
            $accountantUser->givePermissionTo($perm);
            $otherUser->givePermissionTo($perm);
        }

        $assignedFund = AssetFund::create([
            'fund_name' => 'Assigned Fund',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $managerEmp->id,
            'accountant_employee_id' => $accountantEmp->id,
        ]);

        $otherFund = AssetFund::create([
            'fund_name' => 'Other Fund',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $otherEmp->id,
        ]);

        $assignedVoucher = AssetFundVoucher::create([
            'asset_fund_id' => $assignedFund->id,
            'type' => AssetFundVoucherType::DEPOSIT->value,
            'voucher_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);

        $otherVoucher = AssetFundVoucher::create([
            'asset_fund_id' => $otherFund->id,
            'type' => AssetFundVoucherType::DEPOSIT->value,
            'voucher_date' => now()->toDateString(),
            'total_amount' => 2000,
        ]);

        foreach ([$managerUser, $accountantUser] as $user) {
            $this->actingAs($user);

            // Policies on assigned fund & voucher pass
            $this->assertTrue($user->can('view', $assignedFund));
            $this->assertTrue($user->can('update', $assignedFund));
            $this->assertTrue($user->can('delete', $assignedFund));
            $this->assertTrue($user->can('view', $assignedVoucher));
            $this->assertTrue($user->can('update', $assignedVoucher));
            $this->assertTrue($user->can('delete', $assignedVoucher));

            // Policies on other fund & voucher fail even though user has Spatie permissions
            $this->assertFalse($user->can('view', $otherFund));
            $this->assertFalse($user->can('update', $otherFund));
            $this->assertFalse($user->can('delete', $otherFund));
            $this->assertFalse($user->can('view', $otherVoucher));
            $this->assertFalse($user->can('update', $otherVoucher));
            $this->assertFalse($user->can('delete', $otherVoucher));

            // Resource queries return only assigned fund and its vouchers
            $fundIds = AssetFundResource::getEloquentQuery()->pluck('id')->all();
            $this->assertSame([$assignedFund->id], $fundIds);

            $voucherIds = AssetFundVoucherResource::getEloquentQuery()->pluck('id')->all();
            $this->assertSame([$assignedVoucher->id], $voucherIds);

            // Statement page exposes only assigned fund
            $statement = new AssetFundStatement();
            $this->assertSame([$assignedFund->id], $statement->funds->pluck('id')->all());

            $statement->fund_id = $otherFund->id;
            $this->assertNull($statement->selected_fund);
            $this->assertCount(0, $statement->deposit_vouchers);

            // PDF route allows assigned fund and blocks other fund with 403
            $this->get(route('asset-fund.statement.pdf', ['fund_id' => $assignedFund->id]))
                ->assertOk();
            $this->get(route('asset-fund.statement.pdf', ['fund_id' => $otherFund->id]))
                ->assertForbidden();
        }
    }

    public function test_assigned_manager_without_spatie_permissions_is_blocked(): void
    {
        $company = $this->createCompany();
        $managerUser = $this->createUser();
        $managerEmp = $this->createEmployee($managerUser, $company);

        $fund = AssetFund::create([
            'fund_name' => 'No Perm Fund',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $managerEmp->id,
        ]);

        $voucher = AssetFundVoucher::create([
            'asset_fund_id' => $fund->id,
            'type' => AssetFundVoucherType::DEPOSIT->value,
            'voucher_date' => now()->toDateString(),
            'total_amount' => 500,
        ]);

        $this->actingAs($managerUser);

        $this->assertFalse($managerUser->can('viewAny', AssetFund::class));
        $this->assertFalse($managerUser->can('view', $fund));
        $this->assertFalse($managerUser->can('update', $fund));
        $this->assertFalse($managerUser->can('viewAny', AssetFundVoucher::class));
        $this->assertFalse($managerUser->can('create', AssetFundVoucher::class));
        $this->assertFalse($managerUser->can('view', $voucher));
        $this->assertFalse($managerUser->can('update', $voucher));
        $this->assertFalse($managerUser->can('delete', $voucher));
        $this->assertFalse(AssetFundStatement::canAccess());

        $this->get(route('asset-fund.statement.pdf', ['fund_id' => $fund->id]))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_all_funds_and_vouchers(): void
    {
        $company = $this->createCompany();
        $otherUser = $this->createUser();
        $otherEmp = $this->createEmployee($otherUser, $company);

        $superAdmin = $this->createUser(UserType::SUPER_ADMIN);

        $fundA = AssetFund::create([
            'fund_name' => 'Fund A',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $otherEmp->id,
        ]);

        $fundB = AssetFund::create([
            'fund_name' => 'Fund B',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->can('view', $fundA));
        $this->assertTrue($superAdmin->can('update', $fundB));
        $this->assertCount(2, AssetFundResource::getEloquentQuery()->get());
    }

    public function test_linked_deposit_withdrawal_rejects_deposit_from_another_fund(): void
    {
        $company = $this->createCompany();
        $managerUser = $this->createUser();
        $managerEmp = $this->createEmployee($managerUser, $company);

        $fundA = AssetFund::create([
            'fund_name' => 'Fund A',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $managerEmp->id,
        ]);

        $fundB = AssetFund::create([
            'fund_name' => 'Fund B',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $managerEmp->id,
        ]);

        $depositInB = AssetFundVoucher::create([
            'asset_fund_id' => $fundB->id,
            'type' => AssetFundVoucherType::DEPOSIT->value,
            'voucher_date' => now()->toDateString(),
            'gross_amount' => 1000,
            'total_amount' => 1000,
        ]);

        $this->actingAs($managerUser);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        AssetFundVoucher::create([
            'asset_fund_id' => $fundA->id,
            'type' => AssetFundVoucherType::WITHDRAWAL->value,
            'disbursement_type' => 'linked_deposit',
            'linked_deposit_voucher_id' => $depositInB->id,
            'voucher_date' => now()->toDateString(),
            'amount_without_tax' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
        ]);
    }

    public function test_linked_deposit_withdrawal_requires_existing_deposit_voucher(): void
    {
        $company = $this->createCompany();
        $managerUser = $this->createUser();
        $managerEmp = $this->createEmployee($managerUser, $company);

        $fund = AssetFund::create([
            'fund_name' => 'Fund Test',
            'established_date' => now()->toDateString(),
            'status' => AssetFundStatus::ACTIVE->value,
            'manager_employee_id' => $managerEmp->id,
        ]);

        $deposit = AssetFundVoucher::create([
            'asset_fund_id' => $fund->id,
            'type' => AssetFundVoucherType::DEPOSIT->value,
            'voucher_date' => now()->toDateString(),
            'gross_amount' => 1000,
            'total_amount' => 1000,
        ]);

        $withdrawal = AssetFundVoucher::create([
            'asset_fund_id' => $fund->id,
            'type' => AssetFundVoucherType::WITHDRAWAL->value,
            'disbursement_type' => 'general',
            'voucher_date' => now()->toDateString(),
            'amount_without_tax' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
        ]);

        $this->actingAs($managerUser);

        // Missing linked_deposit_voucher_id
        try {
            AssetFundVoucher::create([
                'asset_fund_id' => $fund->id,
                'type' => AssetFundVoucherType::WITHDRAWAL->value,
                'disbursement_type' => 'linked_deposit',
                'voucher_date' => now()->toDateString(),
                'amount_without_tax' => 50,
                'tax_amount' => 0,
                'total_amount' => 50,
            ]);
            $this->fail('Expected ValidationException when linked_deposit_voucher_id is missing.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('linked_deposit_voucher_id', $e->errors());
        }

        // Linking to a withdrawal voucher instead of a deposit voucher
        try {
            AssetFundVoucher::create([
                'asset_fund_id' => $fund->id,
                'type' => AssetFundVoucherType::WITHDRAWAL->value,
                'disbursement_type' => 'linked_deposit',
                'linked_deposit_voucher_id' => $withdrawal->id,
                'voucher_date' => now()->toDateString(),
                'amount_without_tax' => 50,
                'tax_amount' => 0,
                'total_amount' => 50,
            ]);
            $this->fail('Expected ValidationException when linked voucher is not a deposit.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('linked_deposit_voucher_id', $e->errors());
        }

        // Valid linked deposit succeeds
        $valid = AssetFundVoucher::create([
            'asset_fund_id' => $fund->id,
            'type' => AssetFundVoucherType::WITHDRAWAL->value,
            'disbursement_type' => 'linked_deposit',
            'linked_deposit_voucher_id' => $deposit->id,
            'voucher_date' => now()->toDateString(),
            'amount_without_tax' => 50,
            'tax_amount' => 0,
            'total_amount' => 50,
        ]);

        $this->assertSame($deposit->id, $valid->linked_deposit_voucher_id);
    }

    private function createUser(UserType $type = UserType::SAUDI_EMPLOYEE): User
    {
        $this->seq++;

        return User::create([
            'name' => "User {$this->seq}",
            'email' => "asset-fund-user{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $type,
        ]);
    }

    private function createCompany(): Company
    {
        $this->seq++;

        return Company::create([
            'name' => "Company {$this->seq}",
            'trade_name' => "Trade {$this->seq}",
        ]);
    }

    private function createEmployee(User $user, Company $company): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);
    }
}
