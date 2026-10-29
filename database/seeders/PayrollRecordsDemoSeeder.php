<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\RewardStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\EmployeeReward;
use App\Models\Guarantor;
use App\Models\JobTitle;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات تجريبية لصفحة المسيرات والتحصيل (/payroll-records).
 *
 * - ينشئ شركة مخصصة للتجربة "شركة تجارب المسيرات (ديمو)" حتى لا تختلط بالبيانات الحقيقية.
 * - ينشئ 8 موظفين (5 سعوديين + 3 وافدين) بمسيرات رواتب شهرية من يناير حتى الشهر الحالي.
 * - الشهور المنتهية: كل الموظفين مصروفين (مع حركة خزينة وسندات صرف).
 * - الشهر الحالي: مزيج (مصروف / جزئي / غير مصروف) + مكافآت معتمدة + حضور وأوفر تايم وغياب.
 * - كشوف تحصيل في شهور سابقة + الشهر الحالي + الشهر القادم (لنفس الموظفين) لاختبار الخصم التلقائي.
 *
 * التشغيل: php artisan db:seed --class=PayrollRecordsDemoSeeder
 * آمن لإعادة التشغيل (Idempotent): لا يكرر أي سجل موجود مسبقاً ولا يعدل على بيانات حقيقية.
 */
class PayrollRecordsDemoSeeder extends Seeder
{
    protected const DEMO_COMPANY_NAME = 'شركة تجارب المسيرات (ديمو)';

    protected int $countPayroll = 0;

    protected int $countPaidDisbursed = 0;

    protected int $countCollections = 0;

    protected int $countRewards = 0;

    protected int $countAttendance = 0;

    public function run(): void
    {
        $company = Company::where('trade_name', self::DEMO_COMPANY_NAME)->first();
        if (! $company) {
            $company = Company::create([
                'trade_name' => self::DEMO_COMPANY_NAME,
                'address' => 'الرياض - بيانات تجريبية لاختبار المسيرات',
                'phone' => '0110000000',
                'email' => 'payroll.demo@demo.test',
                'status' => 'active',
            ]);
        }

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'الفرع الرئيسي (ديمو)'],
            ['branch_code' => 'BR-DEMO-01', 'status' => 'active']
        );
        $department = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'الإدارة العامة (ديمو)'],
            ['department_code' => 'DP-DEMO-01', 'status' => 'active']
        );
        $jobTitle = JobTitle::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'موظف (ديمو)'],
            ['job_code' => 'JB-DEMO-01', 'status' => 'active']
        );
        $guarantor = Guarantor::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'كفيل تجريبي - دمو'],
            [
                'type' => 'external',
                'national_id' => '1088888888',
                'phone' => '0558888888',
                'nationality' => 'saudi',
                'job_title' => 'موظف كفالة',
                'status' => 'active',
            ]
        );

        $employees = $this->seedEmployees($company, $branch, $department, $jobTitle, $guarantor);

        $now = Carbon::now();
        $startMonth = Carbon::create($now->year, 1, 1);

        // 1) كشوف التحصيل أولاً (حتى تُخصم من مسيرات نفس الشهر عبر metadata)
        $collectionPlan = [
            ['month_offset' => 2, 'emp_idx' => 6, 'net' => 1800.0, 'paid' => true],  // مارس
            ['month_offset' => 5, 'emp_idx' => 2, 'net' => 1500.0, 'paid' => true],  // يونيو
            ['month_offset' => $now->month - 1, 'emp_idx' => 4, 'net' => 1200.0, 'paid' => true], // الشهر الحالي
            ['month_offset' => $now->month - 1, 'emp_idx' => 6, 'net' => 900.0, 'paid' => false],
            ['month_offset' => $now->month, 'emp_idx' => 3, 'net' => 1000.0, 'paid' => false], // الشهر القادم (خصم مباشر)
            ['month_offset' => $now->month, 'emp_idx' => 7, 'net' => 1400.0, 'paid' => false],
        ];
        $collectionsByMonth = [];
        foreach ($collectionPlan as $plan) {
            $monthKey = $startMonth->copy()->addMonths($plan['month_offset'])->format('Y-m');
            if (Carbon::parse($monthKey.'-01')->gt($now->copy()->addMonth()->endOfMonth())) {
                continue;
            }
            $record = $this->upsertCollectionRecord($employees[$plan['emp_idx']], $monthKey, $plan['net'], $plan['paid']);
            if ($record) {
                $this->countCollections++;
                $collectionsByMonth[$plan['emp_idx']][$monthKey] = $record;
            }
        }

        // 2) مسيرات الرواتب الشهرية
        foreach (range(0, $now->month - 1) as $monthOffset) {
            $monthKey = $startMonth->copy()->addMonths($monthOffset)->format('Y-m');
            $isCurrentMonth = ($monthOffset === $now->month - 1);

            foreach ($employees as $idx => $employee) {
                $overtimeHours = (($idx + $monthOffset) % 2 === 0) ? 4 + $idx * 2 : 0;
                $reward = (($monthOffset + 1) % 3 === 0) ? 500 + $idx * 100 : 0;
                $absentDays = (($idx + $monthOffset) % 6 === 0) ? 1 : 0;

                if ($isCurrentMonth) {
                    // الشهر الحالي: مزيج حالات الصرف
                    $payState = match (true) {
                        $idx <= 2 => 'paid',
                        $idx <= 4 => 'partial',
                        default => 'unpaid',
                    };
                } else {
                    $payState = 'paid';
                }

                $metadata = [];
                $collection = $collectionsByMonth[$idx][$monthKey] ?? null;
                if ($collection) {
                    $metadata['collected_deduction'] = (float) ($collection->paid_amount > 0 ? $collection->paid_amount : $collection->net_amount);
                    $metadata['collection_notes'] = $collection->notes ?: 'تحصيل تجريبي';
                }

                $this->upsertPayrollRecord($employee, $monthKey, [
                    'overtime_hours' => $overtimeHours,
                    'reward' => $reward,
                    'absent_days' => $absentDays,
                    'pay_state' => $payState,
                    'metadata' => $metadata,
                ]);
            }
        }

        // 3) مكافآت معتمدة للشهر الحالي
        foreach ([0 => 800.0, 5 => 600.0] as $idx => $amount) {
            $reward = EmployeeReward::firstOrCreate(
                ['employee_id' => $employees[$idx]->id, 'reward_date' => $now->copy()->startOfMonth()->addDays(9)->toDateString()],
                [
                    'reward_number' => 'RW-DEMO-'.str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                    'company_id' => $company->id,
                    'net_amount' => $amount,
                    'reason' => 'مكافأة أداء مميز (بيانات تجريبية)',
                    'status' => RewardStatus::APPROVED,
                    'notes' => 'ديمو - المسيرات',
                ]
            );
            if ($reward->wasRecentlyCreated) {
                $this->countRewards++;
            }
        }

        // 4) حضور تجريبي للشهر الحالي (أوفر تايم + غياب) لاختبار الحساب التلقائي
        $attendancePlan = [
            ['emp_idx' => 1, 'day' => 5, 'overtime' => 2.0, 'status' => AttendanceStatus::PRESENT],
            ['emp_idx' => 1, 'day' => 12, 'overtime' => 3.0, 'status' => AttendanceStatus::PRESENT],
            ['emp_idx' => 1, 'day' => 20, 'overtime' => 0.0, 'status' => AttendanceStatus::ABSENT],
            ['emp_idx' => 6, 'day' => 7, 'overtime' => 2.5, 'status' => AttendanceStatus::PRESENT],
            ['emp_idx' => 6, 'day' => 18, 'overtime' => 0.0, 'status' => AttendanceStatus::ABSENT],
        ];
        foreach ($attendancePlan as $row) {
            $date = $now->copy()->startOfMonth()->addDays($row['day'] - 1);
            if ($date->gt($now)) {
                continue;
            }
            $attendance = Attendance::firstOrCreate(
                ['employee_id' => $employees[$row['emp_idx']]->id, 'date' => $date->toDateString()],
                [
                    'check_in' => '08:00',
                    'check_out' => '17:00',
                    'late_hours' => 0,
                    'overtime_hours' => $row['overtime'],
                    'total_hours' => 8,
                    'check_in_source' => 'manual',
                    'check_out_source' => 'manual',
                    'status' => $row['status'],
                    'notes' => 'بيانات تجريبية للمسيرات',
                ]
            );
            if ($attendance->wasRecentlyCreated) {
                $this->countAttendance++;
            }
        }

        $this->command->info('✅ تم توليد بيانات المسيرات التجريبية بنجاح.');
        $this->command->info("   الشركة: {$company->trade_name} (#{$company->id})");
        $this->command->info('   - مسيرات رواتب جديدة: '.$this->countPayroll);
        $this->command->info('   - سندات صرف/تحصيل بالخزينة: '.$this->countPaidDisbursed);
        $this->command->info('   - كشوف تحصيل جديدة: '.$this->countCollections);
        $this->command->info('   - مكافآت معتمدة: '.$this->countRewards);
        $this->command->info('   - سجلات حضور: '.$this->countAttendance);
        $this->command->info('   افتح صفحة المسيرات واختر الشركة (ديمو) من قائمة الشركات للاختبار.');
    }

    /**
     * موظفو الديمو: 5 سعوديين + 3 وافدين برواتب متفاوتة.
     */
    protected function seedEmployees(
        Company $company,
        Branch $branch,
        Department $department,
        JobTitle $jobTitle,
        Guarantor $guarantor
    ): array {
        $list = [
            ['name' => 'فهد بن خالد العتيبي (تجريبي)', 'email' => 'payroll.demo01@demo.test', 'phone' => '0550000001', 'saudi' => true, 'emp_no' => 'EMP-DEMO-01', 'salary' => 14500, 'national_id' => '1090000001'],
            ['name' => 'عبدالله بن محمد القحطاني (تجريبي)', 'email' => 'payroll.demo02@demo.test', 'phone' => '0550000002', 'saudi' => true, 'emp_no' => 'EMP-DEMO-02', 'salary' => 12000, 'national_id' => '1090000002'],
            ['name' => 'ريم بنت صالح المطيري (تجريبي)', 'email' => 'payroll.demo03@demo.test', 'phone' => '0550000003', 'saudi' => true, 'emp_no' => 'EMP-DEMO-03', 'salary' => 9800, 'national_id' => '1090000003'],
            ['name' => 'سلطان بن عبدالعزيز الدوسري (تجريبي)', 'email' => 'payroll.demo04@demo.test', 'phone' => '0550000004', 'saudi' => true, 'emp_no' => 'EMP-DEMO-04', 'salary' => 8600, 'national_id' => '1090000004'],
            ['name' => 'فيصل بن ناصر الشهري (تجريبي)', 'email' => 'payroll.demo05@demo.test', 'phone' => '0550000005', 'saudi' => true, 'emp_no' => 'EMP-DEMO-05', 'salary' => 7200, 'national_id' => '1090000005'],
            ['name' => 'أحمد عبدالهادي محمود (تجريبي)', 'email' => 'payroll.demo06@demo.test', 'phone' => '0560000006', 'saudi' => false, 'emp_no' => 'EMP-DEMO-06', 'salary' => 6200, 'nationality' => 'egyptian', 'iqama' => '2340000001'],
            ['name' => 'محمد طارق إبراهيم (تجريبي)', 'email' => 'payroll.demo07@demo.test', 'phone' => '0560000007', 'saudi' => false, 'emp_no' => 'EMP-DEMO-07', 'salary' => 5400, 'nationality' => 'pakistani', 'iqama' => '2340000002'],
            ['name' => 'سليم راشد خان (تجريبي)', 'email' => 'payroll.demo08@demo.test', 'phone' => '0560000008', 'saudi' => false, 'emp_no' => 'EMP-DEMO-08', 'salary' => 4800, 'nationality' => 'indian', 'iqama' => '2340000003'],
        ];

        $employees = [];
        foreach ($list as $idx => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'user_type' => $data['saudi'] ? UserType::SAUDI_EMPLOYEE : UserType::FOREIGN_EMPLOYEE,
                    'status' => 'active',
                ]
            );

            $employee = Employee::updateOrCreate(
                ['employee_number' => $data['emp_no']],
                [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'job_title_id' => $jobTitle->id,
                    'employment_type' => 'full_time',
                    'hire_date' => '2025-01-01',
                    'basic_salary' => $data['salary'],
                    'bank_iban' => 'SA0380000'.str_pad((string) $user->id, 15, '0', STR_PAD_LEFT),
                    'daily_work_hours' => 8.0,
                    'weekly_work_hours' => 48.0,
                    'status' => 'active',
                ]
            );

            $isSaudi = $data['saudi'];
            EmployeeDetail::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'nationality' => $isSaudi ? 'saudi' : $data['nationality'],
                    'national_id' => $isSaudi ? $data['national_id'] : null,
                    'iqama_number' => $isSaudi ? null : $data['iqama'],
                    'iqama_expiry' => $isSaudi ? null : now()->addYear()->toDateString(),
                    'passport_number' => $isSaudi ? null : 'P-DEMO-'.($idx + 1),
                    'work_permit_number' => $isSaudi ? null : 'WP-DEMO-'.($idx + 1),
                    'work_permit_expiry' => $isSaudi ? null : now()->addMonths(10)->toDateString(),
                    'sponsor_name' => $isSaudi ? null : $company->trade_name,
                    'guarantor_id' => (! $isSaudi && in_array($idx, [5, 6])) ? $guarantor->id : null,
                    'emergency_contact_phone' => '0599'.str_pad((string) ($idx + 1), 6, '0', STR_PAD_LEFT),
                    'gender' => ($idx === 2) ? 'female' : 'male',
                    'marital_status' => 'married',
                    'city' => 'الرياض',
                    'address' => 'الرياض - بيانات تجريبية',
                ]
            );

            $employees[$idx] = $employee;
        }

        return $employees;
    }

    /**
     * إنشاء مسير راتب لشهر معين (مع سند صرف تلقائي للمصروف) — يتخطى الموجود مسبقاً.
     */
    protected function upsertPayrollRecord(Employee $employee, string $monthKey, array $options = []): ?PayrollRecord
    {
        $monthDate = Carbon::parse($monthKey.'-01')->startOfMonth();
        $existing = PayrollRecord::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('operation_type', 'payroll')
            ->where('payroll_month', $monthDate->toDateString())
            ->first();

        if ($existing) {
            return null;
        }

        $basic = (float) $employee->basic_salary;
        $hourRate = $basic > 0 ? $basic / 240 : 0;
        $dayRate = $basic > 0 ? $basic / 30 : 0;

        $record = PayrollRecord::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'operation_type' => 'payroll',
            'payroll_month' => $monthDate->toDateString(),
            'record_date' => $monthDate->copy()->endOfMonth()->toDateString(),
            'basic_salary' => $basic,
            'overtime_amount' => round((float) ($options['overtime_hours'] ?? 0) * $hourRate, 2),
            'rewards_amount' => (float) ($options['reward'] ?? 0),
            'absence_deduction' => round((float) ($options['absent_days'] ?? 0) * $dayRate, 2),
            'metadata' => $options['metadata'] ?? [],
            'notes' => 'بيانات تجريبية - مسير رواتب',
        ]);
        $record->refresh();
        $this->countPayroll++;

        $net = (float) $record->net_amount;
        if ($net <= 0) {
            return $record;
        }

        $payState = $options['pay_state'] ?? 'paid';
        // تاريخ الصرف: نهاية الشهر (أو اليوم لو الشهر الحالي لسه ماشي)
        $payDate = $monthDate->copy()->endOfMonth()->min(Carbon::today())->toDateString();
        if ($payState === 'paid') {
            $this->disburse($record, $net, $payDate);
        } elseif ($payState === 'partial') {
            $this->disburse($record, round($net / 2, 2), $payDate);
        }

        return $record;
    }

    /**
     * إنشاء كشف تحصيل لشهر معين — يتخطى الموجود مسبقاً.
     */
    protected function upsertCollectionRecord(Employee $employee, string $monthKey, float $netAmount, bool $paid): ?PayrollRecord
    {
        $monthDate = Carbon::parse($monthKey.'-01')->startOfMonth();
        $existing = PayrollRecord::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('operation_type', 'collection')
            ->where('payroll_month', $monthDate->toDateString())
            ->first();

        if ($existing) {
            return null;
        }

        $record = PayrollRecord::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'operation_type' => 'collection',
            'payroll_month' => $monthDate->toDateString(),
            'record_date' => $monthDate->copy()->endOfMonth()->min(Carbon::today())->toDateString(),
            'net_amount' => $netAmount,
            'notes' => 'تحصيل من كشف تجريبي',
        ]);

        if ($paid) {
            $this->disburse($record, $netAmount, $monthDate->copy()->endOfMonth()->min(Carbon::today())->toDateString());
        }

        return $record;
    }

    /**
     * صرف مبلغ على المسير (ينشئ حركة خزينة + سند دفع/قبض) بنفس منطق النظام.
     */
    protected function disburse(PayrollRecord $record, float $amount, string $paymentDate): void
    {
        if ($amount <= 0 || $record->payments()->exists()) {
            return;
        }

        try {
            PayrollCalculationService::disburseSalary(
                record: $record,
                amount: $amount,
                paymentDate: $paymentDate,
                paymentMethod: 'cash',
                notes: 'بيانات تجريبية'
            );
            $this->countPaidDisbursed++;
        } catch (\Throwable $e) {
            $this->command->warn('⚠ تعذر إنشاء سند الصرف للمسير '.$record->record_number.': '.$e->getMessage());
        }
    }
}
