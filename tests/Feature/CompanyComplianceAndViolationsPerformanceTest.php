<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\CompanyCompliancePerformanceService;
use App\Services\CompanyViolationsPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyComplianceAndViolationsPerformanceTest extends TestCase
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

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_17_123833_create_permission_tables.php',
            '2026_09_17_132000_create_companies_and_organization_tables.php',
            '2026_09_17_140000_create_employees_table.php',
            '2026_10_01_100000_create_violations_tables.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    public function test_compliance_service_empty_without_authorized_companies(): void
    {
        $viewer = $this->createUser();
        $this->actingAs($viewer);

        $stats = CompanyCompliancePerformanceService::getStats([]);
        $this->assertFalse($stats['has_data']);
        $this->assertSame(0, $stats['iqama']['expired']);
    }

    public function test_compliance_service_iqama_and_license_buckets(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $company = $this->createCompany([
            'municipality_license_expiry' => Carbon::today()->addDays(10)->toDateString(), // expiring soon
            'civil_defense_license_expiry' => Carbon::today()->subDays(5)->toDateString(), // expired
            'commercial_registration_expiry' => Carbon::today()->addDays(100)->toDateString(), // valid
        ]);

        // Employee 1: Expired Iqama
        $emp1 = $this->createEmployee($company);
        EmployeeDetail::create([
            'employee_id' => $emp1->id,
            'iqama_number' => '2000000001',
            'iqama_expiry' => Carbon::today()->subDays(10)->toDateString(),
        ]);

        // Employee 2: Within 3 months
        $emp2 = $this->createEmployee($company);
        EmployeeDetail::create([
            'employee_id' => $emp2->id,
            'iqama_number' => '2000000002',
            'iqama_expiry' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        // Employee 3: Valid over 9 months
        $emp3 = $this->createEmployee($company);
        EmployeeDetail::create([
            'employee_id' => $emp3->id,
            'iqama_number' => '2000000003',
            'iqama_expiry' => Carbon::today()->addDays(300)->toDateString(),
        ]);

        $stats = CompanyCompliancePerformanceService::getStats(['company_id' => [$company->id]]);

        $this->assertTrue($stats['has_data']);
        $this->assertSame(1, $stats['iqama']['expired']);
        $this->assertSame(1, $stats['iqama']['within_3m']);
        $this->assertSame(0, $stats['iqama']['within_6m']);
        $this->assertSame(0, $stats['iqama']['within_9m']);
        $this->assertSame(1, $stats['iqama']['valid_over_9m']);
        $this->assertSame(3, $stats['iqama']['total_tracked']);

        // Licenses
        $this->assertSame(1, $stats['licenses']['municipality']['expiring_soon']);
        $this->assertSame(1, $stats['licenses']['civil_defense']['expired']);
        $this->assertSame(1, $stats['licenses']['commercial_reg']['valid']);

        // Kashf query test
        $kashfQuery = CompanyCompliancePerformanceService::getKashfQuery(
            ['company_id' => [$company->id]],
            'iqama_expired'
        );
        $this->assertSame(1, $kashfQuery->count());
        $this->assertSame($emp1->id, $kashfQuery->first()->id);
    }

    public function test_violations_service_stages_and_authorities(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $company = $this->createCompany([
            'metadata' => [
                'vehicles' => [
                    ['plate' => 'ABC-1234', 'is_authorized' => true, 'traffic_violations' => 2],
                    ['plate' => 'XYZ-5678', 'is_authorized' => false, 'traffic_violations' => 1],
                ],
            ],
        ]);

        $cat = ViolationCategory::create(['name' => 'بلدي']);
        $type = ViolationType::create(['violation_category_id' => $cat->id, 'name' => 'مخالفة نظافة']);

        // Violation 1: Objection stage
        Violation::create([
            'company_id' => $company->id,
            'violation_category_id' => $cat->id,
            'violation_type_id' => $type->id,
            'issuing_authority' => 'أمانة المنطقة',
            'violation_amount' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'stage' => ViolationStage::OBJECTION->value,
            'payment_status' => ViolationPaymentStatus::PENDING->value,
            'violation_date' => Carbon::today()->subDays(5)->toDateString(),
        ]);

        // Violation 2: Execution stage & Partial payment
        Violation::create([
            'company_id' => $company->id,
            'violation_category_id' => $cat->id,
            'violation_type_id' => $type->id,
            'issuing_authority' => 'وزارة الموارد البشرية',
            'violation_amount' => 5000,
            'paid_amount' => 2000,
            'remaining_amount' => 3000,
            'stage' => ViolationStage::ADMIN_EXECUTION->value,
            'payment_status' => ViolationPaymentStatus::PARTIAL->value,
            'violation_date' => Carbon::today()->subDays(20)->toDateString(),
        ]);

        $stats = CompanyViolationsPerformanceService::getStats(['company_id' => [$company->id]]);

        $this->assertTrue($stats['has_data']);
        $this->assertSame(2, $stats['totals']['count']);
        $this->assertEquals(6000.0, $stats['totals']['amount']);
        $this->assertEquals(2000.0, $stats['totals']['paid']);
        $this->assertEquals(4000.0, $stats['totals']['remaining']);

        // Stages
        $this->assertSame(1, $stats['stages']['objection']['count']);
        $this->assertSame(1, $stats['stages']['execution']['count']);

        // Authorities
        $this->assertCount(2, $stats['authorities']);

        // Vehicles
        $this->assertTrue($stats['vehicles']['has_registered_vehicles']);
        $this->assertSame(2, $stats['vehicles']['total_vehicles']);
        $this->assertSame(1, $stats['vehicles']['authorized_vehicles']);
        $this->assertSame(1, $stats['vehicles']['unauthorized_vehicles']);
        $this->assertSame(3, $stats['vehicles']['traffic_violations_count']);

        // Kashf query test
        $objectionKashf = CompanyViolationsPerformanceService::getKashfQuery(
            ['company_id' => [$company->id]],
            'objection'
        );
        $this->assertSame(1, $objectionKashf->count());
    }

    private function createUser(UserType $type = UserType::STAFF): User
    {
        $this->seq++;

        return User::create([
            'name' => "User {$this->seq}",
            'email' => "user{$this->seq}@test.local",
            'password' => bcrypt('password'),
            'user_type' => $type,
        ]);
    }

    private function createCompany(array $attributes = []): Company
    {
        $this->seq++;

        return Company::create(array_merge([
            'trade_name' => "Company {$this->seq}",
            'status' => 'active',
        ], $attributes));
    }

    private function createEmployee(Company $company): Employee
    {
        $user = $this->createUser(UserType::FOREIGN_EMPLOYEE);

        return Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);
    }
}
