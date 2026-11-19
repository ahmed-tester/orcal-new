<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DepartmentPerformanceDemoSeeder extends Seeder
{
    private const PREFIX = 'PERF-DEPT-DEMO';

    private const COMPANY_CODES = ['PERF-DEMO-A', 'PERF-DEMO-B'];

    /** @return list<array{id: int, label: string}> */
    public function run(): array
    {
        if (! PerformanceDashboardDemoSeeder::isAvailable()) {
            throw new RuntimeException('The department performance demo requires a local, staging, or testing environment.');
        }

        $actor = auth()->user() ?? User::query()
            ->where('user_type', UserType::SUPER_ADMIN)
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Super Admin'))
            ->first();

        if (! $actor?->isSuperAdmin()) {
            throw new RuntimeException('An existing super administrator is required to seed the department performance demo.');
        }

        $today = now()->toImmutable()->startOfDay();

        return DB::transaction(function () use ($actor, $today): array {
            $companies = Company::query()
                ->whereIn('company_number', self::COMPANY_CODES)
                ->orderBy('company_number')
                ->lockForUpdate()
                ->get();

            if ($companies->count() !== count(self::COMPANY_CODES)
                || $companies->contains(fn (Company $company) => ($company->metadata['performance_demo'] ?? false) !== true)) {
                throw new DomainException(__('performance.department_demo.requires_dashboard_demo'));
            }

            [$companyA, $companyB] = $companies->all();
            $shared = $this->department('shared', $companies->modelKeys());
            $group = $this->department('group', []);
            $this->department('empty', [$companyA->id], $companyA);

            $sharedA = $this->employee($companyA, $shared, 'shared-a');
            $sharedB = $this->employee($companyB, $shared, 'shared-b');
            $groupA = $this->employee($companyA, $group, 'group-a');
            $this->employee($companyB, $group, 'group-b');

            $this->seedOverdueTask($sharedA, $actor, $today, 'SHARED-A');
            $this->seedOverdueTask($groupA, $actor, $today, 'GROUP-A');
            $this->seedServices($sharedA, $sharedB, $today);

            return $companies->map(fn (Company $company) => [
                'id' => $company->id,
                'label' => $company->trade_name,
            ])->all();
        }, attempts: 3);
    }

    /** @param  list<int>  $linkedCompanyIds */
    private function department(string $scope, array $linkedCompanyIds, ?Company $company = null): Department
    {
        $department = Department::query()->firstOrCreate(['department_code' => self::PREFIX.'-'.strtoupper($scope)], [
            'name' => __('performance.department_demo.'.$scope),
            'company_id' => $company?->id,
            'is_global' => $company === null,
            'status' => 'active',
        ]);

        if ($scope === 'shared' && $department->wasRecentlyCreated) {
            $department->companies()->attach($linkedCompanyIds);
            $department->save();
        }

        $actualCompanyIds = $department->companies()->orderBy('companies.id')->pluck('companies.id')->all();
        sort($linkedCompanyIds);
        if ($department->is_global !== ($company === null)
            || ($scope !== 'shared' && $department->company_id !== $company?->id)
            || $actualCompanyIds !== $linkedCompanyIds) {
            throw new RuntimeException('A department performance demo code is already in use.');
        }

        return $department;
    }

    private function employee(Company $company, Department $department, string $key): Employee
    {
        $user = User::query()->firstOrCreate(['email' => strtolower(self::PREFIX).'-'.$key.'@example.test'], [
            'name' => __('performance.department_demo.employee', ['department' => $department->name, 'company' => $company->trade_name]),
            'password' => Str::random(48),
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);

        if ($user->wasRecentlyCreated) {
            return Employee::query()->create([
                'company_id' => $company->id,
                'branch_id' => $company->branches()->oldest('id')->value('id'),
                'department_id' => $department->id,
                'user_id' => $user->id,
                'hire_date' => today()->subYear(),
                'status' => 'active',
            ]);
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('department_id', $department->id)
            ->first();

        if ($employee === null || $user->user_type !== UserType::SAUDI_EMPLOYEE) {
            throw new RuntimeException('A department performance demo email is already in use.');
        }

        return $employee;
    }

    private function seedOverdueTask(Employee $employee, User $actor, CarbonImmutable $today, string $key): void
    {
        $due = $today->subDay()->toDateString();
        $assignment = TaskAssignment::query()->firstOrCreate(['transaction_number' => self::PREFIX.'-'.$key], [
            'employee_id' => $employee->id,
            'assignment_date' => $due,
            'created_by' => $actor->id,
        ]);

        if ($assignment->employee_id !== $employee->id) {
            throw new RuntimeException('A department performance demo assignment number is already in use.');
        }

        $task = Task::query()->firstOrCreate(['task_code' => self::PREFIX.'-FOLLOWUP'], [
            'name' => __('performance.department_demo.task'),
        ]);

        $assignment->items()->firstOrCreate(['task_id' => $task->id], [
            'status' => TaskStatus::PENDING,
            'due_date' => $due,
            'weight' => 10,
        ]);
    }

    private function seedServices(Employee $sharedA, Employee $sharedB, CarbonImmutable $today): void
    {
        $serviceDate = $today->toDateString();
        $overdueDate = $today->subDay()->toDateString();
        $parent = $this->transaction($sharedB, 'DEFICIENCIES', $overdueDate, $overdueDate);

        foreach ([1, 2] as $number) {
            UnifiedDeficiencyTask::query()->firstOrCreate(['task_key' => self::PREFIX.'-DEF-'.$number], [
                'company_id' => $sharedB->company_id,
                'service_transaction_id' => $parent->id,
                'deficiency_type' => 'missing_attachment',
                'title' => __('performance.department_demo.deficiency', ['number' => $number]),
                'responsible_employee_id' => $sharedB->id,
                'status' => 'open',
                'due_date' => $overdueDate,
            ]);
        }

        // Add the indicator sample once per reporting month.
        $month = $today->format('Ym');
        $this->transaction($sharedA, $month.'-A1', $serviceDate, $serviceDate, $serviceDate);
        $this->transaction($sharedA, $month.'-A2', $serviceDate, $serviceDate, $serviceDate);
        $this->transaction($sharedB, $month.'-B1', $overdueDate, $overdueDate, $serviceDate);
    }

    private function transaction(Employee $employee, string $key, string $date, string $dueDate, ?string $completionDate = null): ServiceTransaction
    {
        $transaction = ServiceTransaction::query()->firstOrCreate(['transaction_number' => self::PREFIX.'-'.$key], [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'executed_by' => $employee->id,
            'service_name' => __('performance.department_demo.service'),
            'service_target' => 'employee',
            'transaction_date' => $date,
            'due_date' => $dueDate,
            'completion_date' => $completionDate,
            'service_status' => $completionDate === null ? 'active' : 'completed',
        ]);

        if ($transaction->company_id !== $employee->company_id || $transaction->executed_by !== $employee->id) {
            throw new RuntimeException('A department performance demo transaction number is already in use.');
        }

        return $transaction;
    }
}
