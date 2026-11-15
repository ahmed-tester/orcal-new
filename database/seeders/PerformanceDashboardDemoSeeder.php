<?php

namespace Database\Seeders;

use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialPaymentMethod;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\PayrollRecord;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\CashHandoverService;
use App\Services\FinancialDisbursementService;
use App\Services\FinancialReceiptService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PerformanceDashboardDemoSeeder extends Seeder
{
    private const PREFIX = 'PERF-DEMO';

    public static function isAvailable(): bool
    {
        return app()->environment(['local', 'staging', 'testing']);
    }

    /** @return list<array{id: int, label: string}> */
    public function run(): array
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('The performance demo requires a local, staging, or testing environment.');
        }

        $actor = auth()->user() ?? User::query()
            ->where('user_type', UserType::SUPER_ADMIN)
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Super Admin'))
            ->first();

        if (! $actor?->isSuperAdmin()) {
            throw new RuntimeException('An existing super administrator is required to seed the performance demo.');
        }

        $today = now()->toImmutable()->startOfDay();

        return DB::transaction(function () use ($actor, $today): array {
            $companies = [];

            foreach (['A' => 'الأفق للخدمات', 'B' => 'المسار للأعمال'] as $code => $name) {
                $company = $this->company($code, $name);
                $month = $today->format('Ym');
                $periods = $company->metadata['performance_demo_periods'] ?? [];

                if (! in_array($month, $periods, true)) {
                    $this->seedCompany($company, $actor, $today);
                    $company->update(['metadata' => array_replace($company->metadata, [
                        'performance_demo_periods' => [...$periods, $month],
                    ])]);
                }

                $companies[] = ['id' => $company->id, 'label' => $company->trade_name];
            }

            return $companies;
        }, attempts: 3);
    }

    private function company(string $code, string $name): Company
    {
        $company = Company::query()->lockForUpdate()->firstOrCreate(['company_number' => self::PREFIX.'-'.$code], [
            'trade_name' => $name.' (ديمو الأداء)',
            'status' => 'active',
            'metadata' => ['performance_demo' => true],
        ]);
        if (($company->metadata['performance_demo'] ?? false) !== true) {
            throw new RuntimeException('The performance demo company number is already in use.');
        }

        return $company;
    }

    private function seedCompany(Company $company, User $actor, CarbonImmutable $today): void
    {
        $employees = $this->seedEmployees($company, $today);
        $this->seedCompliance($company, $employees, $today);

        $service = Service::query()->firstOrCreate([
            'company_id' => $company->id,
            'name' => 'متابعة المعاملات (ديمو الأداء)',
        ], ['target_type' => 'employee', 'price' => 1000, 'default_gov_fee' => 250]);

        $previous = $today->startOfMonth()->subDays(3);
        if (! in_array($previous->format('Ym'), $company->metadata['performance_demo_periods'] ?? [], true)) {
            $this->seedOperations($company, $service, $employees, $previous);
            $this->seedAttendance($employees, $previous);
            $this->seedViolations($company, $actor, $previous);
            $this->seedPayrollAndDebt($company, $employees, $actor, $previous);
        }

        $this->seedOperations($company, $service, $employees, $today);
        $this->seedAttendance($employees, $today);
        $this->seedLeaves($company, $employees, $actor, $today);
        $this->seedViolations($company, $actor, $today);
        $this->seedPayrollAndDebt($company, $employees, $actor, $today);
        $this->seedFinancialWorkflow($company, $service, $employees, $actor, $today);
    }

    /** @return list<Employee> */
    private function seedEmployees(Company $company, CarbonImmutable $today): array
    {
        $branches = [];
        $departments = [];
        foreach (['الرياض' => 'العمليات', 'جدة' => 'الشؤون الإدارية'] as $branch => $department) {
            $branches[] = Branch::query()->firstOrCreate(['company_id' => $company->id, 'name' => $branch], ['status' => 'active']);
            $departments[] = Department::query()->firstOrCreate(['company_id' => $company->id, 'name' => $department], ['status' => 'active']);
        }

        $employees = [];
        $staff = [
            ['مشرف العمليات', UserType::SUPERVISOR],
            ['منفذ الخدمات', UserType::SAUDI_EMPLOYEE],
            ['منسق المعاملات', UserType::FOREIGN_EMPLOYEE],
            ['أخصائي المتابعة', UserType::FOREIGN_EMPLOYEE],
            ['أخصائي الوثائق', UserType::FOREIGN_EMPLOYEE],
            ['منسق الشؤون الإدارية', UserType::FOREIGN_EMPLOYEE],
            ['مسؤول السجلات', UserType::FOREIGN_EMPLOYEE],
            ['منسق الحضور', UserType::SAUDI_EMPLOYEE],
            ['مسؤول المواعيد', UserType::SAUDI_EMPLOYEE],
            ['أخصائي الدعم', UserType::SAUDI_EMPLOYEE],
        ];
        foreach ($staff as $index => [$name, $type]) {
            $office = $index % 2;
            $user = User::query()->firstOrCreate(['email' => strtolower($company->company_number).'-'.$index.'@example.test'], [
                'name' => $name.' (ديمو '.$company->company_number.')',
                'password' => Str::random(48),
                'user_type' => $type,
                'status' => 'active',
            ]);
            $employee = Employee::query()->firstOrCreate([
                'company_id' => $company->id,
                'user_id' => $user->id,
            ], [
                'branch_id' => $branches[$office]->id,
                'department_id' => $departments[$office]->id,
                'hire_date' => $today->subYear()->toDateString(),
                'basic_salary' => 4500 + 300 * $index,
                'status' => 'active',
            ]);

            if (! $user->wasRecentlyCreated && $employee->wasRecentlyCreated) {
                throw new RuntimeException('A performance demo employee email is already in use.');
            }

            $employees[] = $employee;
        }

        return $employees;
    }

    private function seedCompliance(Company $company, array $employees, CarbonImmutable $today): void
    {
        $alternate = str_ends_with($company->company_number, '-B');
        $company->update([
            'commercial_registration_expiry' => $today->addDays($alternate ? 120 : -10),
            'municipality_license_expiry' => $today->addDays($alternate ? -7 : 15),
            'civil_defense_license_expiry' => $alternate ? null : $today->addDays(120),
            'metadata' => array_replace($company->metadata, ['subscriptions' => [
                'qiwa' => ['expiry_date' => $today->addDays($alternate ? -5 : 180)->toDateString()],
                'absher' => ['expiry_date' => $today->addDays($alternate ? 120 : -5)->toDateString()],
                'spl' => $alternate ? [] : ['expiry_date' => $today->addDays(70)->toDateString()],
            ]]),
        ]);

        foreach ([-9, 40, 140, 220, 320] as $index => $days) {
            EmployeeDetail::query()->updateOrCreate(['employee_id' => $employees[$index + 2]->id], [
                'nationality' => 'egyptian',
                'iqama_expiry' => $today->addDays($days),
                'work_permit_expiry' => $today->addDays($days),
                'insurance_expiry' => $today->addDays($days + 5),
            ]);
        }
    }

    private function seedOperations(Company $company, Service $service, array $employees, CarbonImmutable $date): void
    {
        $key = $this->periodKey($company, $date);
        $due = $date->subDay()->toDateString();
        $extraStatus = str_ends_with($company->company_number, '-B') ? 'active' : 'completed';
        foreach (['completed', 'active', $extraStatus] as $index => $status) {
            $dueDate = match (true) {
                $index === 0 => $date,
                $index === 2 && $status === 'active' => $date->addDays(3),
                default => $date->subDay(),
            };
            $transaction = $this->transaction($company, $service, $employees[$index + 1], $date, $key.'-OPS-'.$index, $status, dueDate: $dueDate);

            if ($index < 2) {
                $deficiency = UnifiedDeficiencyTask::query()->firstOrNew(['task_key' => $key.'-DEF-'.$index]);
                if (! $deficiency->exists) {
                    $deficiency->fill([
                        'company_id' => $company->id,
                        'service_transaction_id' => $transaction->id,
                        'deficiency_type' => 'missing_attachment',
                        'title' => 'استكمال مستند المعاملة (ديمو الأداء)',
                        'responsible_employee_id' => $employees[$index + 1]->id,
                        'status' => $index === 0 ? 'resolved' : 'open',
                        'resolved_at' => $index === 0 ? $date : null,
                        'due_date' => $due,
                    ]);
                    $deficiency->created_at = $date;
                    $deficiency->save();
                }
            }
        }

        $this->seedAssignments($company, $employees, $date, $key);
    }

    private function seedAssignments(Company $company, array $employees, CarbonImmutable $date, string $key): void
    {
        $day = $date->toDateString();
        $assignment = TaskAssignment::query()->firstOrCreate(['transaction_number' => $key.'-TASKS'], [
            'employee_id' => $employees[1]->id,
            'assignment_date' => $day,
            'from_date' => $date->startOfMonth(),
            'to_date' => $date->endOfMonth(),
        ]);

        $tasks = [
            'DOCUMENT-REVIEW' => ['مراجعة المستندات', 'completed'],
            'REQUEST-FOLLOWUP' => ['متابعة الطلبات', 'in_progress'],
            'REPORT' => ['إعداد التقرير', 'pending'],
        ];
        foreach ($tasks as $code => [$name, $status]) {
            $task = Task::query()->firstOrCreate(['task_code' => self::PREFIX.'-'.$code], [
                'name' => $name.' (ديمو الأداء)',
            ]);
            TaskAssignmentItem::query()->firstOrCreate(['task_assignment_id' => $assignment->id, 'task_id' => $task->id], [
                'status' => $status,
                'due_date' => $status === 'in_progress' ? $date->subDay() : $day,
                'completed_at' => $status === 'completed' ? $day : null,
                'weight' => 10,
                'score' => $status === 'completed' ? 10 : 0,
            ]);
        }

        $start = $date->startOfMonth()->toDateString();
        SupervisionShift::query()->whereDate('start_date', $start)->firstOrCreate([
            'company_id' => $company->id,
            'supervisor_id' => $employees[0]->id,
            'title' => 'نوبة متابعة الأداء (ديمو)',
        ], [
            'assigned_employees' => [$employees[1]->id, $employees[2]->id, $employees[7]->id],
            'start_date' => $start,
            'end_date' => $date->endOfMonth()->toDateString(),
        ]);
    }

    private function seedAttendance(array $employees, CarbonImmutable $date): void
    {
        $day = $date->toDateString();
        foreach ([1 => 'present', 7 => 'late', 8 => 'absent', 9 => 'present'] as $index => $status) {
            Attendance::query()->whereDate('date', $day)->firstOrCreate(['employee_id' => $employees[$index]->id], [
                'date' => $day,
                'status' => $status,
                'check_in' => match ($status) {
                    'late' => '08:30:00',
                    'absent' => null,
                    default => '08:00:00',
                },
                'check_out' => $status === 'absent' ? null : ($index === 1 ? '17:00:00' : '16:00:00'),
                'late_hours' => $status === 'late' ? 30 : 0,
                'overtime_hours' => $index === 1 ? 60 : 0,
                'total_hours' => match ($index) {
                    1 => 9,
                    7 => 7.5,
                    8 => 0,
                    9 => 8,
                },
            ]);
        }
    }

    private function seedLeaves(Company $company, array $employees, User $actor, CarbonImmutable $today): void
    {
        $type = LeaveType::query()->firstOrCreate(['company_id' => $company->id, 'name' => 'إجازة سنوية (ديمو الأداء)'], ['annual_balance' => 30]);
        foreach ([6 => -7, 5 => 0, 7 => 5, 9 => 10] as $index => $offset) {
            $start = $today->addDays($offset);
            $approved = $offset !== 10;
            Leave::query()->whereDate('start_date', $start->toDateString())->firstOrCreate([
                'employee_id' => $employees[$index]->id,
                'leave_type_id' => $type->id,
            ], [
                'start_date' => $start,
                'end_date' => $start->addDays(2),
                'days' => 3,
                'status' => $approved ? 'approved' : 'pending',
                'approved_by' => $approved ? $actor->id : null,
            ]);
        }
    }

    private function seedViolations(Company $company, User $actor, CarbonImmutable $date): void
    {
        $key = $this->periodKey($company, $date);
        $cases = [
            ['البلدية', 'correction', 'pending', 0],
            ['الدفاع المدني', 'grace', 'partial', 300],
            ['وزارة الموارد البشرية', 'admin_execution', 'paid', 900],
            ['البلدية', 'objection', 'pending', 0],
        ];
        foreach ($cases as $index => [$authority, $stage, $status, $paid]) {
            $category = ViolationCategory::query()->firstOrCreate(['name' => $authority.' (ديمو الأداء)']);
            $type = ViolationType::query()->firstOrCreate(['violation_category_id' => $category->id, 'name' => 'مخالفة تجريبية']);
            Violation::query()->firstOrCreate(['company_id' => $company->id, 'external_violation_number' => $key.'-VIO-'.$index], [
                'violation_category_id' => $category->id,
                'violation_type_id' => $type->id,
                'issuing_authority' => $authority,
                'violation_date' => $date->toDateString(),
                'stage' => $stage,
                'payment_status' => $status,
                'violation_amount' => 900,
                'paid_amount' => $paid,
                'closure_date' => $status === 'paid' ? $date->toDateString() : null,
                'recorded_by' => $actor->id,
            ]);
        }
    }

    private function seedPayrollAndDebt(Company $company, array $employees, User $actor, CarbonImmutable $date): void
    {
        $key = $this->periodKey($company, $date);
        foreach (['payroll' => [4800, 2400], 'collection' => [750, 250]] as $type => [$total, $paid]) {
            PayrollRecord::query()->firstOrCreate(['record_number' => $key.'-'.strtoupper($type)], [
                'company_id' => $company->id,
                'employee_id' => $employees[1]->id,
                'operation_type' => $type,
                'payroll_month' => $date->startOfMonth()->toDateString(),
                'record_date' => $date->toDateString(),
                'basic_salary' => $type === 'payroll' ? $total : 0,
                'net_amount' => $total,
                'paid_amount' => $paid,
                'created_by' => $actor->id,
            ]);
        }

        Debt::query()->firstOrCreate(['debt_number' => $key.'-DEBT'], [
            'company_id' => $company->id,
            'employee_id' => $employees[2]->id,
            'executed_by' => $employees[2]->id,
            'debt_date' => max($date->startOfMonth(), $date->subDay())->toDateString(),
            'due_date' => $date->subDay()->toDateString(),
            'total_amount' => 1400,
            'paid_amount' => 400,
            'debt_reason' => 'عهدة معدات (ديمو الأداء)',
        ]);
    }

    private function transaction(Company $company, Service $service, Employee $employee, CarbonImmutable $date, string $number, string $status = 'active', int $amount = 0, ?CarbonImmutable $dueDate = null): ServiceTransaction
    {
        return ServiceTransaction::query()->firstOrCreate(['transaction_number' => $number], [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'executed_by' => $employee->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'service_target' => 'employee',
            'transaction_date' => $date->toDateString(),
            'due_date' => ($dueDate ?? $date->addDays(5))->toDateString(),
            'completion_date' => $status === 'completed' ? $date->toDateString() : null,
            'service_status' => $status,
            'total_amount' => $amount,
            'metadata' => ['gov_fee_expected' => $amount > 0 ? 250 : 0],
        ]);
    }

    private function seedFinancialWorkflow(Company $company, Service $service, array $employees, User $actor, CarbonImmutable $date): void
    {
        $key = $this->periodKey($company, $date);
        $employee = $employees[1];
        $transaction = $this->transaction($company, $service, $employee, $date, $key.'-CASH', amount: 1000);
        $receipts = [];

        foreach (range(1, 6) as $index) {
            $receipts[] = FinancialReceiptService::record($transaction, [
                'amount' => $index === 1 ? 500 : 100,
                'payment_method' => FinancialPaymentMethod::CASH,
                'collector_employee_id' => $employee->id,
                'payer_name' => 'عميل تجريبي',
                'request_key' => $key.'-CASH-'.$index,
            ], $actor);
        }

        $handover = CashHandoverService::request([$receipts[0]->id => 400], ['request_key' => $key.'-HND'], $actor);
        CashHandoverService::confirm($handover, [
            'signature_code' => CashHandoverService::signatureCodeForDelivery($handover, $actor),
            'confirmation_key' => $key.'-HND-CONFIRM',
            'confirmed_amounts' => $handover->items()->pluck('requested_amount', 'id')->all(),
        ], $actor);
        CashHandoverService::request([$receipts[1]->id => 75], ['request_key' => $key.'-HND-PENDING'], $actor);

        FinancialDisbursementService::record($transaction, [
            'disbursement_type' => FinancialDisbursementType::TREASURY_DIRECT,
            'amount' => 150,
            'reference_number' => $key.'-DIRECT',
            'request_key' => $key.'-DIRECT',
        ], $actor);

        $this->seedPersonalClaims($company, $service, $employees[2], $actor, $date, $key);
    }

    private function seedPersonalClaims(Company $company, Service $service, Employee $employee, User $actor, CarbonImmutable $date, string $key): void
    {
        $transaction = $this->transaction($company, $service, $employee, $date, $key.'-BANK', amount: 1000);
        $receipt = FinancialReceiptService::record($transaction, [
            'amount' => 700,
            'payment_method' => FinancialPaymentMethod::BANK_TRANSFER,
            'payer_name' => 'عميل تجريبي',
            'request_key' => $key.'-TRANSFER',
        ], $actor);
        FinancialReceiptService::confirm($receipt, [
            'destination_type' => 'company_account',
            'destination_reference' => $key,
            'reference_number' => $key.'-TRANSFER',
            'proof_path' => 'performance-demo/transfer.pdf',
        ], $actor);

        foreach (['APPROVED' => 120, 'PENDING' => 75] as $status => $amount) {
            $reference = $key.'-CLAIM-'.$status;
            $claim = FinancialDisbursementService::record($transaction, [
                'disbursement_type' => FinancialDisbursementType::EMPLOYEE_PERSONAL,
                'amount' => $amount,
                'reference_number' => $reference,
                'paid_by_employee_id' => $employee->id,
                'receipt_proof' => 'performance-demo/claim.pdf',
                'request_key' => $reference,
            ], $actor);

            if ($status === 'APPROVED') {
                FinancialDisbursementService::approve($claim, $actor);
                FinancialDisbursementService::pay($claim, ['amount' => 50, 'request_key' => $key.'-REIMBURSE'], $actor);
            }
        }
    }

    private function periodKey(Company $company, CarbonImmutable $date): string
    {
        return $company->company_number.'-'.$date->format('Ym');
    }
}
