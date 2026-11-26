<?php

namespace Tests\Feature;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\DeductionBasis;
use App\Enums\EmploymentType;
use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Filament\Resources\PayrollRecords\Pages\EditPayrollRecord;
use App\Jobs\SyncDailyAttendanceToPayrollJob;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use App\Services\Attendance\SaudiAttendancePeriodReportService;
use App\Services\Payroll\EmployeeCompensationEligibilityService;
use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\PayrollEntitlementCalculator;
use App\Services\Payroll\PayrollEntitlementService;
use App\Services\Payroll\SaudiCompensationGrantService;
use App\Services\Payroll\SaudiPayrollPolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaudiSalaryAndPeriodReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Company $otherCompany;

    private Employee $saudiEmployee;

    public function createApplication()
    {
        $database = dirname(__DIR__, 2).'/storage/framework/testing/saudi_salary_period_report.sqlite';

        if (! is_dir(dirname($database))) {
            mkdir(dirname($database), 0777, true);
        }

        if (! file_exists($database)) {
            touch($database);
        }

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $database;
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $database;

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'trade_name' => 'Alpha Co',
            'legal_name' => 'Alpha Co LLC',
            'status' => 'active',
        ]);

        $this->otherCompany = Company::create([
            'trade_name' => 'Beta Co',
            'legal_name' => 'Beta Co LLC',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'سعد السعودي',
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);

        $this->saudiEmployee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'basic_salary' => 3000,
            'daily_work_hours' => 8,
            'weekly_work_hours' => 40,
            'employment_type' => EmploymentType::FULL_TIME,
            'status' => GeneralStatus::ACTIVE,
            'metadata' => ['name_en' => 'Saad Al-Saudi'],
        ]);

        EmployeeDetail::create([
            'employee_id' => $this->saudiEmployee->id,
            'nationality' => 'saudi',
            'national_id' => '1087654321',
        ]);

        foreach ([
            'manage_employee_compensation',
            'view_any_attendance',
            'view_any_payroll_record',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    public function test_salary_settings_save_computes_total_preserves_basic_and_audits(): void
    {
        $actor = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'status' => 'active',
        ]);

        $updated = EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 3000,
            'allowances_amount' => 600,
            'bonuses_amount' => 300,
            'other_allowances_amount' => 100,
            'overtime_hourly_rate' => 50,
            'compensation_hourly_rate' => 0,
            'deduction_basis' => DeductionBasis::BASIC_BONUSES->value,
            'daily_work_hours' => 8,
            'weekly_work_hours' => 40,
        ], $actor);

        $this->assertSame('3000.00', (string) $updated->basic_salary);
        $this->assertSame('600.00', (string) $updated->allowances_amount);
        $this->assertSame('300.00', (string) $updated->bonuses_amount);
        $this->assertSame('100.00', (string) $updated->other_allowances_amount);
        $this->assertSame('50.00', (string) $updated->overtime_hourly_rate);
        $this->assertSame('0.00', (string) $updated->compensation_hourly_rate);
        $this->assertSame(DeductionBasis::BASIC_BONUSES, $updated->deduction_basis);
        $this->assertFalse((bool) $updated->is_policy_complete);
        $this->assertSame('Saad Al-Saudi', $updated->metadata['name_en'] ?? null);

        $formData = EmployeeSalaryProfileService::getFormData($updated);
        $this->assertSame(4000.0, $formData['total_salary_display']);

        $audit = UnifiedAuditLog::query()
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $updated->id)
            ->where('action_type', EmployeeSalaryProfileService::AUDIT_ACTION)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->company->id, (int) $audit->company_id);
        $this->assertSame(4000.0, (float) ($audit->payload['after']['total_salary'] ?? 0));

        // Explicitly clearing work hours with null must clear them (not retain old 8 / 40)
        $cleared = EmployeeSalaryProfileService::saveForSaudiEmployee($updated, [
            'daily_work_hours' => null,
            'weekly_work_hours' => null,
        ], $actor);

        $this->assertNull($cleared->daily_work_hours);
        $this->assertNull($cleared->weekly_work_hours);
    }

    public function test_salary_settings_reject_negative_amounts_and_compensation_minutes(): void
    {
        $this->expectException(ValidationException::class);
        EmployeeSalaryProfileService::validateAndNormalizePayload([
            'basic_salary' => -100,
        ], $this->saudiEmployee);
    }

    public function test_compensation_minutes_are_rejected_even_for_authorized_user(): void
    {
        $this->expectException(ValidationException::class);
        EmployeeSalaryProfileService::validateAndNormalizePayload([
            'basic_salary' => 3000,
            'compensation_minutes' => 30,
        ], $this->saudiEmployee);
    }

    public function test_compensation_eligibility_respects_permission_scope_and_effective_dates(): void
    {
        $unauthorized = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $unauthorized->id,
            'company_id' => $this->company->id,
            'status' => GeneralStatus::ACTIVE,
        ]);

        try {
            EmployeeCompensationEligibilityService::setEligibility(
                $unauthorized,
                $this->saudiEmployee,
                true,
                now()->toDateString(),
                'Unauthorized attempt'
            );
            $this->fail('Expected AuthorizationException for user without permission.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $otherCompanyManager = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $otherCompanyManager->id,
            'company_id' => $this->otherCompany->id,
            'status' => GeneralStatus::ACTIVE,
        ]);
        $otherCompanyManager->givePermissionTo('manage_employee_compensation');

        $this->assertFalse(EmployeeCompensationEligibilityService::canManage($otherCompanyManager, $this->saudiEmployee));

        $authorizedManager = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $authorizedManager->id,
            'company_id' => $this->company->id,
            'status' => GeneralStatus::ACTIVE,
        ]);
        $authorizedManager->givePermissionTo('manage_employee_compensation');

        EmployeeCompensationEligibilityService::setEligibility(
            $authorizedManager,
            $this->saudiEmployee,
            true,
            now()->toDateString(),
            'Approved for Ramadan schedule'
        );

        $this->assertTrue(EmployeeCompensationEligibilityService::isCurrentlyEligible($this->saudiEmployee->refresh()));

        // Future revocation must not flip today's eligibility early
        $futureDate = now()->addDays(5)->toDateString();
        EmployeeCompensationEligibilityService::setEligibility(
            $authorizedManager,
            $this->saudiEmployee,
            false,
            $futureDate,
            'Scheduled end of exception'
        );

        $this->assertTrue(EmployeeCompensationEligibilityService::isCurrentlyEligible($this->saudiEmployee->refresh()));
        $this->assertFalse(EmployeeCompensationEligibilityService::isCurrentlyEligible($this->saudiEmployee, $futureDate));

        $logsCount = UnifiedAuditLog::query()
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $this->saudiEmployee->id)
            ->where('action_type', EmployeeCompensationEligibilityService::AUDIT_ACTION)
            ->count();

        $this->assertSame(2, $logsCount);
    }

    public function test_period_report_respects_financial_authorization_and_provisional_labeling(): void
    {
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 3000,
            'allowances_amount' => 500,
            'overtime_hourly_rate' => 60,
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-11-24',
            'status' => AttendanceStatus::PRESENT,
            'check_in_source' => AttendanceSource::MANUAL,
            'check_out_source' => AttendanceSource::MANUAL,
            'late_hours' => 15,
            'overtime_hours' => 90,
            'total_hours' => 9.5,
        ]);

        $attendanceViewer = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $attendanceViewer->id,
            'company_id' => $this->company->id,
            'status' => GeneralStatus::ACTIVE,
        ]);
        $attendanceViewer->givePermissionTo('view_any_attendance');

        $this->actingAs($attendanceViewer);
        $restrictedReport = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-30',
        ], $attendanceViewer);

        $this->assertFalse($restrictedReport['can_view_financial']);
        $row = $restrictedReport['rows']->first();
        $this->assertNull($row['salary_reference']);
        $this->assertNull($row['overtime_cost_estimate']);

        $payrollViewer = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $payrollViewer->id,
            'company_id' => $this->company->id,
            'status' => GeneralStatus::ACTIVE,
        ]);
        $payrollViewer->givePermissionTo(['view_any_attendance', 'view_any_payroll_record']);

        $this->actingAs($payrollViewer);
        $fullReport = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-30',
        ], $payrollViewer);

        $this->assertTrue($fullReport['can_view_financial']);
        $fullRow = $fullReport['rows']->first();
        $this->assertSame(90.0, (float) $fullRow['overtime_cost_estimate']); // 90/60 * 60
        $this->assertSame(3500.0, (float) $fullRow['salary_reference']['total_salary']);

        $details = SaudiAttendancePeriodReportService::buildEmployeePeriodDetails(
            $this->saudiEmployee,
            '2026-11-20',
            '2026-11-30',
            $payrollViewer
        );
        $this->assertSame(AttendanceSource::MANUAL->getLabel(), $details['records']->first()['source_label']);
    }

    public function test_cross_month_period_report_splits_overtime_cost_by_month_snapshot(): void
    {
        PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-10-01',
            'record_date' => '2026-10-31',
            'basic_salary' => 3000,
            'net_amount' => 3000,
            'metadata' => [
                'calculation_snapshot' => [
                    'basic_salary' => 3000,
                    'overtime_hourly_rate' => 40,
                ],
            ],
        ]);

        PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-30',
            'basic_salary' => 3000,
            'net_amount' => 3000,
            'metadata' => [
                'calculation_snapshot' => [
                    'basic_salary' => 3000,
                    'overtime_hourly_rate' => 60,
                ],
            ],
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-10-25',
            'status' => AttendanceStatus::PRESENT,
            'check_in_source' => AttendanceSource::MANUAL,
            'overtime_hours' => 60,
            'total_hours' => 9,
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-11-05',
            'status' => AttendanceStatus::PRESENT,
            'check_in_source' => AttendanceSource::FINGERPRINT,
            'overtime_hours' => 60,
            'total_hours' => 9,
        ]);

        $payrollViewer = User::factory()->create([
            'user_type' => UserType::EMPLOYEE,
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $payrollViewer->id,
            'company_id' => $this->company->id,
            'status' => GeneralStatus::ACTIVE,
        ]);
        $payrollViewer->givePermissionTo(['view_any_attendance', 'view_any_payroll_record']);
        $this->actingAs($payrollViewer);

        $report = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-10-20',
            'date_to' => '2026-11-10',
        ], $payrollViewer);

        $row = $report['rows']->first();
        // Oct: 60/60 * 40 = 40; Nov: 60/60 * 60 = 60 => Total = 100.0 (not 120.0!)
        $this->assertSame(100.0, (float) $row['overtime_cost_estimate']);

        $details = SaudiAttendancePeriodReportService::buildEmployeePeriodDetails(
            $this->saudiEmployee,
            '2026-10-20',
            '2026-11-10',
            $payrollViewer
        );
        $this->assertSame(100.0, (float) $details['totals']['overtime_cost_estimate']);
    }

    public function test_sync_job_skips_collection_and_protects_financially_impacted_payroll(): void
    {
        $collection = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-01',
            'net_amount' => 1200,
        ]);

        $payroll = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-11-01',
            'record_date' => '2026-11-01',
            'basic_salary' => 3000,
            'net_amount' => 3000,
        ]);

        PayrollPayment::create([
            'payroll_record_id' => $payroll->id,
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'amount' => 500,
            'payment_date' => '2026-11-10',
            'payment_method' => 'cash',
        ]);

        // Attempt to mutate basic_salary on financially impacted legacy record via Observer
        $payroll->refresh();
        $payroll->basic_salary = 9999;
        $payroll->save();
        $this->assertSame('3000.00', (string) $payroll->refresh()->basic_salary);
        $this->assertSame('3000.00', (string) $payroll->net_amount);

        // Run sync job for the same month: must skip both collection and impacted payroll
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-11'))->handle();

        $this->assertSame('1200.00', (string) $collection->refresh()->net_amount);
        $this->assertSame('3000.00', (string) $payroll->refresh()->basic_salary);
    }

    public function test_policy_gate_keeps_old_months_on_legacy_and_applies_new_formula_from_effective_month(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // Employee: basic 2600, allowances 400, daily_work_hours 10, deduction_basis = basic
        // New day_rate = 2600 / 26 = 100; hour_rate = 100 / 10 = 10/hr
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'allowances_amount' => 400,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'weekly_work_hours' => 50,
            'compensation_hourly_rate' => 20,
        ], $admin);

        // 60 min late in Nov 2026 (before effective_month 2026-12) and 60 min late in Dec 2026
        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-11-15',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);
        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-10',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        SaudiPayrollPolicyService::setGlobalDefaultDays(26, $admin);

        // Dec 2026 uses default 26 days: 2600 / 26 / 10 = 10.00/hr deduction; + 400 allowances => net = 2600 + 400 - 10 = 2990
        $decFinancials = PayrollCalculationService::calculateEmployeeFinancials($this->saudiEmployee->id, '2026-12');
        $this->assertSame(10.0, (float) $decFinancials['absence_deduction']);
        $this->assertSame(400.0, (float) $decFinancials['additional_amount']);
        $this->assertSame(2990.0, (float) $decFinancials['net_amount']);
        $this->assertTrue((bool) ($decFinancials['metadata']['calculation_snapshot']['use_new_policy'] ?? false));
        $this->assertSame(26, (int) ($decFinancials['metadata']['calculation_snapshot']['calculation_days'] ?? 0));

        // Changing global default days to 25 updates un-pinned months (Nov 2026): 2600 / 25 / 10 = 10.40/hr
        SaudiPayrollPolicyService::setGlobalDefaultDays(25, $admin);
        $novFinancials = PayrollCalculationService::calculateEmployeeFinancials($this->saudiEmployee->id, '2026-11');
        $this->assertSame(10.4, (float) $novFinancials['absence_deduction']);
        $this->assertSame(25, (int) ($novFinancials['metadata']['calculation_snapshot']['calculation_days'] ?? 0));
    }

    public function test_compensation_grant_enforces_ceiling_no_carryover_and_deterministic_overlap(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // New day_rate = 2600 / 26 = 100; hour_rate = 100 / 10 = 10/hr
        // Late 150 min on 2026-12-05 => deduction = 2.5 * 10 = 25.00
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'compensation_hourly_rate' => 20,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);
        EmployeeCompensationEligibilityService::setEligibility(
            $admin,
            $this->saudiEmployee->refresh(),
            true,
            '2026-12-01',
            'Eligible for December'
        );

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-05',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 150,
        ]);

        // Daily grant: 30 mins @ 20/hr = 10.00 (expires 2026-12-05)
        // Monthly grant: 120 mins @ 20/hr = 40.00 (expires 2026-12-31)
        // Total theoretical = 50.00, but ceiling is 25.00! Daily consumed first (10.00), then monthly (15.00 of 40.00).
        SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_DAILY,
            'minutes' => 30,
            'starts_on' => '2026-12-01',
            'ends_on' => '2026-12-31',
            'reason' => 'Daily allowance',
        ]);

        SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_MONTHLY,
            'minutes' => 120,
            'starts_on' => '2026-12-01',
            'reason' => 'Monthly allowance',
        ]);

        $decFinancials = PayrollCalculationService::calculateEmployeeFinancials($this->saudiEmployee->id, '2026-12');
        $snapshot = $decFinancials['metadata']['calculation_snapshot'];

        $this->assertSame(25.0, (float) $snapshot['late_deduction_amount']);
        $this->assertSame(25.0, (float) $snapshot['compensation_amount']); // Capped at 25.00, not 50.00!
        $this->assertSame(0.0, (float) $snapshot['remaining_late_deduction']);
        $this->assertSame(2600.0, (float) $decFinancials['net_amount']); // 2600 + 25 - 25 = 2600

        // Re-syncing the same PayrollRecord must exclude its own reservation and preserve 25.00 compensation
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $savedRecord = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        $this->assertSame(25.0, (float) ($savedRecord->metadata['calculation_snapshot']['compensation_amount'] ?? 0));

        // Second sync of the exact same record must keep 25.00 (not drop due to self-exclusion bug)
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $savedRecord->refresh();
        $this->assertSame(25.0, (float) ($savedRecord->metadata['calculation_snapshot']['compensation_amount'] ?? 0));

        // Period report reflects active policy breakdown
        $this->actingAs($admin);
        $report = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-31',
        ], $admin);
        $this->assertTrue($report['has_active_policy']);
        $row = $report['rows']->first();
        $this->assertSame(25.0, (float) ($row['policy_summary']['late_deduction_amount'] ?? 0));
        $this->assertSame(25.0, (float) ($row['policy_summary']['compensation_amount'] ?? 0));
        $this->assertSame(0.0, (float) ($row['policy_summary']['remaining_late_deduction'] ?? -1));
    }

    public function test_existing_legacy_draft_is_not_silently_converted_on_policy_activation_or_sync(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 3000,
            'daily_work_hours' => 8,
        ], $admin);

        // Existing legacy draft for 2026-12 created BEFORE policy activation
        $legacyDraft = PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'payroll',
            'payroll_month' => '2026-12-01',
            'record_date' => '2026-12-01',
            'basic_salary' => 3000,
            'net_amount' => 3000,
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-05',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);

        // Automatic sync must keep the existing legacy draft on legacy calculation (3000/240 = 12.50)
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $legacyDraft->refresh();

        $this->assertSame('12.50', (string) $legacyDraft->absence_deduction);
        $this->assertFalse((bool) ($legacyDraft->metadata['calculation_snapshot']['use_new_policy'] ?? false));

        // Explicit recalculation converts the legacy draft to the new policy
        $recalculated = PayrollCalculationService::recalculateExistingRecord($legacyDraft);
        $this->assertTrue((bool) ($recalculated->metadata['calculation_snapshot']['use_new_policy'] ?? false));
        $this->assertSame(round(3000 / 26 / 8, 2), (float) $recalculated->absence_deduction);
    }

    public function test_cancelling_compensation_zeroes_additional_amount_and_pinned_snapshot_ignores_hour_changes(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // 2600 / 26 / 10 = 10/hr; 60m late = 10.00 deduction
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'compensation_hourly_rate' => 20,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);
        EmployeeCompensationEligibilityService::setEligibility($admin, $this->saudiEmployee->refresh(), true, '2026-12-01', 'Eligible');

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-01',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        // Grant starts on day 20: day-1 period report must NOT pick it up!
        $grant = SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_ONCE,
            'minutes' => 30,
            'starts_on' => '2026-12-20',
            'ends_on' => '2026-12-25',
            'reason' => 'Late Dec grant',
        ]);

        $this->actingAs($admin);
        $dayOneReport = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-01',
        ], $admin);
        $dayOneRow = $dayOneReport['rows']->first();
        $this->assertSame(10.0, (float) $dayOneRow['policy_summary']['late_deduction_amount']);
        $this->assertSame(0.0, (float) $dayOneRow['policy_summary']['compensation_amount']);

        // Now create an active grant on day 1 so payroll gets 10.00 compensation (net = 2600)
        $day1Grant = SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_DAILY,
            'minutes' => 30,
            'starts_on' => '2026-12-01',
            'reason' => 'Day 1 grant',
        ]);

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        $this->assertSame('10.00', (string) $record->additional_amount);
        $this->assertSame('2600.00', (string) $record->net_amount);

        // Change employee daily_work_hours from 10 to 4 -> pinned snapshot must keep 10 hours on re-sync!
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee->refresh(), [
            'daily_work_hours' => 4,
        ], $admin);

        // Revoke the day-1 grant -> compensation becomes 0, additional_amount must drop to 0.00, net to 2590.00 (not 2600.00!)
        SaudiCompensationGrantService::revokeGrant($admin, $this->saudiEmployee->refresh(), $day1Grant['grant_id'], '2026-12-01', 'Revoked');

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record->refresh();

        $this->assertSame('10.00', (string) $record->absence_deduction); // Still 10/hr (pinned 10h), NOT 25/hr (4h)
        $this->assertSame('0.00', (string) $record->additional_amount); // Dropped to 0.00, not stuck at 10.00
        $this->assertSame('2590.00', (string) $record->net_amount); // 2600 + 0 - 10 = 2590
    }

    public function test_ref_review_001_zeroing_deductions_under_new_policy_clears_old_deduction_and_keeps_other_deductions(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // 2600 / 26 / 10 = 10/hr; 60m late = 10.00 deduction
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);

        $att = Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-03',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        $this->assertSame('10.00', (string) $record->deduction_amount);
        $this->assertSame('2590.00', (string) $record->net_amount);

        // Remove the late attendance -> sync must zero out deduction_amount and restore net_amount to 2600.00
        $att->delete();
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record->refresh();

        $this->assertSame('0.00', (string) $record->absence_deduction);
        $this->assertSame('0.00', (string) $record->deduction_amount);
        $this->assertSame('2600.00', (string) $record->net_amount);

        // Re-syncing again keeps deduction_amount at 0.00
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record->refresh();
        $this->assertSame('0.00', (string) $record->deduction_amount);
        $this->assertSame('2600.00', (string) $record->net_amount);

        // With another deduction (legacy collection 50.00) + late 10.00 -> removing late keeps 50.00
        PayrollRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'operation_type' => 'collection',
            'payroll_month' => '2026-12-01',
            'record_date' => '2026-12-05',
            'net_amount' => 50,
            'paid_amount' => 50,
        ]);

        $att2 = Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-06',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record->refresh();
        $this->assertSame('60.00', (string) $record->deduction_amount);

        $att2->delete();
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record->refresh();
        $this->assertSame('50.00', (string) $record->deduction_amount);
        $this->assertSame('2550.00', (string) $record->net_amount);
    }

    public function test_ref_review_002_daily_period_reports_do_not_reuse_monthly_or_once_grant_and_finalized_payroll_is_immutable(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // 2600 / 26 / 10 = 10/hr; 60m late = 10.00 deduction per day
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'compensation_hourly_rate' => 20,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);
        EmployeeCompensationEligibilityService::setEligibility($admin, $this->saudiEmployee->refresh(), true, '2026-12-01', 'Eligible');

        // Monthly grant: 30 minutes @ 20/hr = 10.00 max for the whole month
        $monthlyGrant = SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_MONTHLY,
            'minutes' => 30,
            'starts_on' => '2026-12-01',
            'reason' => 'Monthly 30m',
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-01',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);
        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-02',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        $this->actingAs($admin);

        $day1 = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-01',
        ], $admin)['rows']->first();

        $day2 = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-02',
            'date_to' => '2026-12-02',
        ], $admin)['rows']->first();

        $day2Details = SaudiAttendancePeriodReportService::buildEmployeePeriodDetails(
            $this->saudiEmployee->refresh(),
            '2026-12-02',
            '2026-12-02',
            $admin
        );

        $fullMonth = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-01',
            'date_to' => '2026-12-31',
        ], $admin)['rows']->first();

        $this->assertSame(10.0, (float) $day1['policy_summary']['compensation_amount']);
        $this->assertSame(0.0, (float) $day2['policy_summary']['compensation_amount']);
        $this->assertSame(0.0, (float) $day2Details['totals']['policy_summary']['compensation_amount']);
        $this->assertSame(10.0, (float) $fullMonth['policy_summary']['compensation_amount']);

        // Cross-month ONCE grant spanning Dec 2026 -> Jan 2027 must not be counted twice
        SaudiCompensationGrantService::revokeGrant($admin, $this->saudiEmployee->refresh(), $monthlyGrant['grant_id'], '2026-12-01', 'Switch to once');
        SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_ONCE,
            'minutes' => 30,
            'starts_on' => '2026-12-28',
            'ends_on' => '2027-01-05',
            'reason' => 'Cross-month once',
        ]);
        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-30',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);
        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2027-01-02',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        $crossMonth = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-28',
            'date_to' => '2027-01-05',
        ], $admin)['rows']->first();
        $this->assertSame(10.0, (float) $crossMonth['policy_summary']['compensation_amount']);

        // Finalized payroll record (with financial impact) must preserve saved allocations even if grant is later revoked
        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $decRecord = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        PayrollPayment::create([
            'payroll_record_id' => $decRecord->id,
            'company_id' => $this->company->id,
            'employee_id' => $this->saudiEmployee->id,
            'amount' => 100,
            'payment_date' => '2026-12-31',
        ]);

        // Revoke eligibility after disbursement -> historical finalized report for Dec 30 must still show 10.00
        EmployeeCompensationEligibilityService::setEligibility($admin, $this->saudiEmployee->refresh(), false, '2026-12-01', 'Post-disbursement change');

        $finalizedReport = SaudiAttendancePeriodReportService::buildReport([
            'period_preset' => 'custom',
            'date_from' => '2026-12-30',
            'date_to' => '2026-12-30',
        ], $admin)['rows']->first();
        $this->assertSame(10.0, (float) $finalizedReport['policy_summary']['compensation_amount']);
    }

    public function test_ref_review_003_explicit_recalculate_after_disabling_policy_clears_snapshot_and_reverses_allocations(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        // 2600 / 26 / 10 = 10/hr; 60m late = 10.00 deduction; allowances = 400
        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'allowances_amount' => 400,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'compensation_hourly_rate' => 20,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);
        EmployeeCompensationEligibilityService::setEligibility($admin, $this->saudiEmployee->refresh(), true, '2026-12-01', 'Eligible');
        SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_DAILY,
            'minutes' => 30,
            'starts_on' => '2026-12-01',
            'reason' => 'Daily 30m',
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-05',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        $meta = $record->metadata ?? [];
        $meta['custom_financial_note'] = 'keep-me';
        $record->metadata = $meta;
        $record->save();

        $this->assertTrue((bool) ($record->metadata['calculation_snapshot']['use_new_policy'] ?? false));
        $this->assertSame('410.00', (string) $record->additional_amount);

        // Mark employee as temporary (ineligible for new policy) and explicitly recalculate -> clean transition to legacy
        $this->saudiEmployee->update(['employment_type' => EmploymentType::TEMPORARY]);
        $this->actingAs($admin);

        $recalculated = PayrollCalculationService::recalculateExistingRecord($record);

        $this->assertArrayNotHasKey('calculation_snapshot', $recalculated->metadata ?? []);
        $this->assertSame('keep-me', $recalculated->metadata['custom_financial_note'] ?? null);
        $this->assertSame('0.00', (string) $recalculated->additional_amount);

        foreach ($recalculated->metadata['compensation_allocations'] ?? [] as $alloc) {
            $this->assertSame('reversed', $alloc['status']);
        }

        // Legacy late deduction = 60m * (2600 / 240) = 10.83
        $expectedLegacyNet = round(2600 - round((60 / 60) * (2600 / 240), 2), 2);
        $this->assertSame($expectedLegacyNet, (float) $recalculated->net_amount);

        // Second explicit recalculate must be idempotent
        $recalculatedAgain = PayrollCalculationService::recalculateExistingRecord($recalculated);
        $this->assertSame($expectedLegacyNet, (float) $recalculatedAgain->net_amount);
        $this->assertArrayNotHasKey('calculation_snapshot', $recalculatedAgain->metadata ?? []);
    }

    public function test_payroll_ref_004_once_grant_is_not_double_counted_within_same_month(): void
    {
        $result = PayrollEntitlementCalculator::calculateLateAndCompensation([
            'month' => '2026-12',
            'hour_rate' => 10.0,
            'compensation_hourly_rate' => 20.0,
            'late_incidents' => [
                ['attendance_id' => 1, 'date' => '2026-12-05', 'late_minutes' => 60],
                ['attendance_id' => 2, 'date' => '2026-12-06', 'late_minutes' => 60],
            ],
            'grants' => [
                [
                    'grant_id' => 'once-60',
                    'revision' => 1,
                    'recurrence' => SaudiCompensationGrantService::RECURRENCE_ONCE,
                    'minutes' => 60,
                    'starts_on' => '2026-12-01',
                    'ends_on' => '2026-12-31',
                    'status' => 'active',
                ],
            ],
            'eligibility_by_date' => [
                '2026-12-05' => true,
                '2026-12-06' => true,
            ],
            'other_allocations_by_cycle' => [],
            'shared_cycle_usage' => [],
        ]);

        $this->assertSame(20.0, (float) $result['late_deduction_amount']);
        $this->assertSame(60.0, (float) $result['compensation_minutes_used']);
        $this->assertSame(20.0, (float) $result['compensation_amount']);
        $this->assertSame(0.0, (float) $result['remaining_late_deduction']);
        $this->assertSame(60.0, (float) ($result['shared_once_usage']['once-60:once'] ?? 0));
    }

    public function test_derived_totals_cannot_be_overridden_by_stale_form_values_and_edit_locks_identity(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::SUPER_ADMIN,
            'status' => 'active',
        ]);

        EmployeeSalaryProfileService::saveForSaudiEmployee($this->saudiEmployee, [
            'basic_salary' => 2600,
            'deduction_basis' => DeductionBasis::BASIC->value,
            'daily_work_hours' => 10,
            'compensation_hourly_rate' => 20,
        ], $admin);

        SaudiPayrollPolicyService::saveCompanyPolicy($this->company, true, '2026-12', $admin);
        EmployeeCompensationEligibilityService::setEligibility($admin, $this->saudiEmployee->refresh(), true, '2026-12-01', 'Eligible');
        $grant = SaudiCompensationGrantService::createGrant($admin, $this->saudiEmployee->refresh(), [
            'recurrence' => SaudiCompensationGrantService::RECURRENCE_DAILY,
            'minutes' => 30,
            'starts_on' => '2026-12-01',
            'reason' => 'Daily 30m',
        ]);

        Attendance::create([
            'employee_id' => $this->saudiEmployee->id,
            'company_id' => $this->company->id,
            'date' => '2026-12-05',
            'status' => AttendanceStatus::LATE,
            'late_hours' => 60,
        ]);

        (new SyncDailyAttendanceToPayrollJob($this->saudiEmployee->id, '2026-12'))->handle();
        $record = PayrollRecord::query()
            ->where('employee_id', $this->saudiEmployee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '2026-12-01')
            ->firstOrFail();

        $this->assertSame('10.00', (string) $record->additional_amount);

        // Revoke grant so server-calculated compensation becomes 0.00
        SaudiCompensationGrantService::revokeGrant($admin, $this->saudiEmployee->refresh(), $grant['grant_id'], '2026-12-01', 'Revoked');

        // Even if stale additional_amount=10 or deduction_amount=99 is passed, derived totals must come from server calculation
        $updated = PayrollEntitlementService::upsertMonthlyPayroll($this->saudiEmployee->id, '2026-12', [
            'skip_if_financially_impacted' => false,
            'overrides' => [
                'basic_salary' => 2600,
                'additional_amount' => 10.0,
                'deduction_amount' => 99.0,
            ],
        ]);
        $this->assertSame('0.00', (string) $updated->additional_amount);
        $this->assertSame('10.00', (string) $updated->deduction_amount);
        $this->assertSame('2590.00', (string) $updated->net_amount);

        // EditPayrollRecord::handleRecordUpdate rejects identity changes (employee_id, payroll_month, operation_type)
        $editPage = new class extends EditPayrollRecord
        {
            public function callHandleRecordUpdate(PayrollRecord $rec, array $data): PayrollRecord
            {
                /** @var PayrollRecord */
                return $this->handleRecordUpdate($rec, $data);
            }
        };

        $this->expectException(ValidationException::class);
        $editPage->callHandleRecordUpdate($updated, [
            'employee_id' => $this->saudiEmployee->id + 999,
            'payroll_month' => '2026-12-01',
            'operation_type' => 'payroll',
        ]);
    }
}
