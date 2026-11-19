<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PerformanceEscalationDemoSeeder extends Seeder
{
    public const COMPANY_NUMBER = 'PERF-ESC-DEMO';

    public static function isAvailable(): bool
    {
        return app()->environment(['local', 'staging', 'testing']);
    }

    /** @return list<array{id: int, label: string}> */
    public function run(): array
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('The escalation demo requires a local, staging, or testing environment.');
        }

        $actor = auth()->user() ?? User::query()
            ->where('user_type', UserType::SUPER_ADMIN)
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Super Admin'))
            ->first();

        if (! $actor?->isSuperAdmin()) {
            throw new RuntimeException('An existing super administrator is required to seed the escalation demo.');
        }

        return DB::transaction(function () use ($actor): array {
            $company = Company::query()->lockForUpdate()->firstOrCreate(
                ['company_number' => self::COMPANY_NUMBER],
                [
                    'trade_name' => __('performance.escalation_demo.company'),
                    'status' => 'active',
                    'metadata' => ['performance_escalation_demo' => true],
                ],
            );

            if (($company->metadata['performance_escalation_demo'] ?? false) !== true) {
                throw new RuntimeException('The escalation demo company number is already in use.');
            }

            $department = Department::query()->firstOrCreate(
                ['company_id' => $company->id, 'department_code' => self::COMPANY_NUMBER.'-DEP'],
                ['name' => __('performance.escalation_demo.department'), 'status' => 'active'],
            );

            $employee = $this->demoEmployee($department);
            $this->seedAssignment($employee, $actor);

            return [['id' => $company->id, 'label' => $company->trade_name]];
        }, attempts: 3);
    }

    private function demoEmployee(Department $department): Employee
    {
        $user = User::query()->firstOrCreate(['email' => 'perf-esc-demo@example.test'], [
            'name' => __('performance.escalation_demo.employee'),
            'password' => Str::random(48),
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);

        $employee = Employee::query()->where('company_id', $department->company_id)
            ->where('user_id', $user->id)
            ->first();

        if ($employee !== null) {
            return $employee;
        }

        if (! $user->wasRecentlyCreated) {
            throw new RuntimeException('The escalation demo employee email is already in use.');
        }

        return Employee::query()->create([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'hire_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function seedAssignment(Employee $employee, User $actor): void
    {
        $yesterday = now()->subDay()->toDateString();
        $assignment = TaskAssignment::query()->firstOrCreate(
            ['transaction_number' => self::COMPANY_NUMBER.'-TASKS'],
            ['employee_id' => $employee->id, 'assignment_date' => $yesterday, 'created_by' => $actor->id],
        );

        if ((int) $assignment->employee_id !== (int) $employee->id) {
            throw new RuntimeException('The escalation demo assignment number is already in use.');
        }

        foreach (range(1, 9) as $number) {
            $task = Task::query()->firstOrCreate(
                ['task_code' => self::COMPANY_NUMBER.'-'.$number],
                ['name' => __('performance.escalation_demo.task', ['number' => $number]), 'default_weight' => 10],
            );

            $assignment->items()->firstOrCreate(['task_id' => $task->id], [
                'status' => TaskStatus::PENDING,
                'due_date' => $yesterday,
                'weight' => 10,
            ]);
        }
    }
}
