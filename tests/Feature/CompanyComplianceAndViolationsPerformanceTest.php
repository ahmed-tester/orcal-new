<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\CompanyCompliancePerformanceService;
use App\Services\CompanyViolationsPerformanceService;
use App\Services\ViolationSummaryService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            '2026_10_01_100000_create_violations_tables.php',
            '2026_10_08_141000_add_metadata_to_employee_details_table.php',
            '2026_10_13_103000_add_metadata_column_for_hijri_dates.php',
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
        $this->assertNull($stats['iqama']);
        $this->assertNull($stats['licenses']);
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
            'work_permit_expiry' => Carbon::today()->addDays(90)->toDateString(),
            'insurance_expiry' => Carbon::today()->addDays(91)->toDateString(),
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
        $kashfQuery = CompanyCompliancePerformanceService::getStatementQuery(
            ['company_id' => [$company->id]],
            'iqama_expired'
        );
        $this->assertSame(1, $kashfQuery->count());
        $this->assertSame($emp1->id, $kashfQuery->first()->id);

        $filters = ['company_id' => [$company->id]];
        $licenseQuery = CompanyCompliancePerformanceService::getStatementQuery($filters, 'municipality_expiring_soon');
        $this->assertSame([$company->id], $licenseQuery->pluck('id')->all());
        $this->assertSame(1, $stats['work_permits']['expiring_soon']);
        $this->assertSame(1, $stats['insurance']['valid']);
        $this->assertSame([$emp2->id], CompanyCompliancePerformanceService::getStatementQuery($filters, 'work_permit_expiring_soon')->pluck('id')->all());
        $this->assertSame([$emp2->id], CompanyCompliancePerformanceService::getStatementQuery($filters, 'insurance_valid')->pluck('id')->all());
        $this->assertSame(0, CompanyCompliancePerformanceService::getStatementQuery($filters, 'municipality_expiring_soon', $this->createCompany()->id)->count());
    }

    public function test_company_and_employee_document_permissions_apply_to_cards_and_statements(): void
    {
        $company = $this->createCompany();
        $viewer = $this->createEmployee($company)->user;
        $this->actingAs($viewer);
        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_company']));
        $filters = ['company_id' => [$company->id]];

        $stats = CompanyCompliancePerformanceService::getStats($filters);
        $this->assertNull($stats['iqama']);
        $this->assertSame(1, $stats['licenses']['municipality']['unspecified']);

        try {
            (new CompanyDepartmentPerformance)->openStatement('compliance', 'iqama_expired');
            $this->fail('Expected access to be denied.');
        } catch (AuthorizationException $exception) {
            $this->assertFalse($exception->response()->allowed());
        }

        $viewer->syncPermissions([Permission::create(['name' => 'view_any_foreign_employee'])]);
        $saudiUser = $this->createUser(UserType::SAUDI_EMPLOYEE);
        $saudiEmployee = Employee::create(['user_id' => $saudiUser->id, 'company_id' => $company->id, 'status' => 'active']);
        foreach ([$viewer->employee, $saudiEmployee] as $employee) {
            EmployeeDetail::create(['employee_id' => $employee->id, 'iqama_expiry' => today()->subDay()->toDateString()]);
        }

        $stats = CompanyCompliancePerformanceService::getStats($filters);
        $this->assertNull($stats['licenses']);
        $this->assertSame(1, $stats['iqama']['expired']);
        $this->assertSame([$viewer->employee->id], CompanyCompliancePerformanceService::getStatementQuery($filters)->pluck('id')->all());
        $this->expectException(AuthorizationException::class);
        CompanyCompliancePerformanceService::getStatementQuery($filters, 'municipality_unspecified');
    }

    public function test_compliance_preview_groups_and_selection(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $company = $this->createCompany();
        $expiredEmployee = $this->createEmployee($company);
        EmployeeDetail::create(['employee_id' => $expiredEmployee->id, 'iqama_expiry' => today()->subDay()->toDateString()]);
        $soonEmployee = $this->createEmployee($company);
        EmployeeDetail::create(['employee_id' => $soonEmployee->id, 'iqama_expiry' => today()->addDays(30)->toDateString()]);

        $page = new CompanyDepartmentPerformance;
        $page->activeOverviewTab = 'compliance';

        $groups = $page->complianceCardGroups;
        $this->assertSame(1, $groups['iqama']['iqama_expired']);
        $this->assertSame(1, $groups['iqama']['iqama_within_3m']);
        $this->assertSame(
            ['work_permit_expired', 'work_permit_expiring_soon', 'work_permit_valid', 'insurance_expired', 'insurance_expiring_soon', 'insurance_valid'],
            array_keys($groups['work_documents'])
        );

        $previews = $page->compliancePreviews;
        $this->assertSame('iqama_expired', $previews['iqama']['type']);
        $this->assertSame([$expiredEmployee->id], $previews['iqama']['records']->pluck('id')->all());
        $this->assertSame('work_permit_expired', $previews['work_documents']['type']);
        $this->assertSame(0, $previews['work_documents']['records']->count());

        $page->selectCompliancePreview('iqama', 'iqama_within_3m');
        $this->assertSame(['iqama' => 'iqama_within_3m'], $page->compliancePreviewTypes);

        $selected = new CompanyDepartmentPerformance;
        $selected->activeOverviewTab = 'compliance';
        $selected->compliancePreviewTypes = $page->compliancePreviewTypes;
        $this->assertSame('iqama_within_3m', $selected->compliancePreviews['iqama']['type']);
        $this->assertSame([$soonEmployee->id], $selected->compliancePreviews['iqama']['records']->pluck('id')->all());

        foreach ([
            ['unknown', 'iqama_expired'],
            ['iqama', 'municipality_expired'],
        ] as [$group, $type]) {
            try {
                $page->selectCompliancePreview($group, $type);
                $this->fail('Expected the preview selection to be rejected.');
            } catch (HttpException $exception) {
                $this->assertSame(404, $exception->getStatusCode());
            }
        }

        $page->activeOverviewTab = 'workforce';
        try {
            $page->selectCompliancePreview('iqama', 'iqama_expired');
            $this->fail('Expected a selection outside the compliance tab to be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_violations_service_stages_and_authorities(): void
    {
        $super = $this->createUser(UserType::SUPER_ADMIN);
        $this->actingAs($super);

        $company = $this->createCompany();

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
        $this->assertSame(1, $stats['totals']['payment_count']);
        $this->assertSame(0, $stats['totals']['fully_paid_count']);

        // Stages
        $this->assertSame(1, $stats['stages']['objection']['count']);
        $this->assertSame(1, $stats['stages']['execution']['count']);

        // Authorities
        $this->assertCount(2, $stats['authorities']);

        // Kashf query test
        $objectionKashf = CompanyViolationsPerformanceService::getStatementQuery(
            ['company_id' => [$company->id]],
            'objection'
        );
        $this->assertSame(1, $objectionKashf->count());

        $filters = [
            'company_id' => [$company->id],
            'date_from' => Carbon::today()->subDays(20)->toDateString(),
            'date_to' => Carbon::today()->subDays(6)->toDateString(),
        ];
        $register = ViolationSummaryService::filtered($filters);
        $dashboardFilters = $filters + ['search' => 'hidden operations search', 'branch_id' => 999];
        $filtered = CompanyViolationsPerformanceService::getStats($dashboardFilters);
        $payments = CompanyViolationsPerformanceService::getStatementQuery($dashboardFilters, 'payments');

        $this->assertSame(1, $filtered['totals']['count']);
        $this->assertSame(1, $filtered['stages']['execution']['count']);
        $this->assertCount(1, $filtered['authorities']);
        $this->assertSame($register->count(), $filtered['totals']['count']);
        $this->assertEquals($register->sum('violation_amount'), $filtered['totals']['amount']);
        $this->assertEquals($register->sum('remaining_amount'), $filtered['totals']['remaining']);
        $this->assertSame($filtered['totals']['payment_count'], $payments->count());
        $this->assertEquals($filtered['totals']['paid'], $payments->sum('paid_amount'));
        $this->assertSame(0, CompanyViolationsPerformanceService::getStatementQuery($dashboardFilters, 'paid')->count());
        $this->assertSame(0, CompanyViolationsPerformanceService::getStatementQuery($filters, 'all', $this->createCompany()->id)->count());
    }

    public function test_violation_permissions_and_company_scope_are_enforced(): void
    {
        $company = $this->createCompany();
        $otherCompany = $this->createCompany();
        $viewer = $this->createEmployee($company)->user;
        $this->actingAs($viewer);

        $page = new CompanyDepartmentPerformance;
        $this->assertFalse($page->canViewViolationsOverview());
        foreach ([
            fn () => $page->showViolationsOverview(),
            fn () => $page->openStatement('violations'),
        ] as $open) {
            try {
                $open();
                $this->fail('Expected access to be denied.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        foreach ([
            fn () => CompanyViolationsPerformanceService::getStats([]),
            fn () => CompanyViolationsPerformanceService::getStatementQuery([]),
        ] as $read) {
            try {
                $read();
                $this->fail('Expected access to be denied.');
            } catch (AuthorizationException $exception) {
                $this->assertFalse($exception->response()->allowed());
            }
        }

        $viewer->givePermissionTo(Permission::create(['name' => 'view_any_violation']));
        $this->assertTrue($page->canViewViolationsOverview());

        $category = ViolationCategory::create(['name' => 'Municipal']);
        $type = ViolationType::create(['violation_category_id' => $category->id, 'name' => 'Parking']);
        foreach ([$company, $otherCompany] as $recordCompany) {
            Violation::create([
                'company_id' => $recordCompany->id,
                'violation_category_id' => $category->id,
                'violation_type_id' => $type->id,
                'violation_date' => today()->toDateString(),
                'issuing_authority' => 'Municipality',
            ]);
        }

        $this->assertSame(1, CompanyViolationsPerformanceService::getStats([])['totals']['count']);
        $this->assertSame([$company->id], CompanyViolationsPerformanceService::getStatementQuery([])->pluck('company_id')->all());
        $this->assertSame(0, CompanyViolationsPerformanceService::getStatementQuery([], 'all', $otherCompany->id)->count());
        $this->assertSame(0, CompanyViolationsPerformanceService::getStats(['company_id' => [$otherCompany->id]])['totals']['count']);
    }

    private function createUser(UserType $type = UserType::SAUDI_EMPLOYEE): User
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
