<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupervisorPerformanceDemoSeeder extends Seeder
{
    /** @return list<array{companyId: int, supervisorId: int, company: string, supervisor: string, month: string}> */
    public function run(): array
    {
        if (! PerformanceDashboardDemoSeeder::isAvailable()) {
            throw new RuntimeException('The supervisor demo requires a local, staging, or testing environment.');
        }

        $actor = auth()->user() ?? User::query()
            ->where('user_type', UserType::SUPER_ADMIN)
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Super Admin'))
            ->first();

        if (! $actor?->isSuperAdmin()) {
            throw new RuntimeException('An existing super administrator is required to seed the supervisor demo.');
        }

        $today = now()->toImmutable()->startOfDay();
        $results = DB::transaction(function () use ($actor, $today): array {
            $results = [];
            $companies = Company::query()->whereIn('company_number', ['PERF-DEMO-A', 'PERF-DEMO-B'])
                ->orderBy('id')->lockForUpdate()->get();

            foreach ($companies as $company) {
                if (($company->metadata['performance_demo'] ?? false) !== true) {
                    continue;
                }

                $supervisor = $this->demoSupervisor($company);

                if ($supervisor === null) {
                    continue;
                }

                $months = $this->demoMonths($company, $supervisor, $today);

                if ($months === []) {
                    continue;
                }

                $details = $supervisor->details()->firstOrNew();

                if (blank($details->nationality)) {
                    $details->nationality = 'saudi';
                    $details->save();
                }

                if (! $supervisor->isSaudi()) {
                    throw new RuntimeException('The demo supervisor has a non-Saudi nationality and cannot have personal task assignments.');
                }

                foreach ($months as $month) {
                    $this->seedPersonalAssignment($company, $supervisor, $actor, $month, $today);
                    $results[] = [
                        'companyId' => $company->id,
                        'supervisorId' => $supervisor->id,
                        'company' => $company->trade_name,
                        'supervisor' => $supervisor->user->name,
                        'month' => $month->format('Y-m'),
                    ];
                }
            }

            if ($results === []) {
                throw new DomainException(__('supervisor_performance.demo.no_demo_period'));
            }

            return $results;
        }, attempts: 3);

        foreach ($results as $result) {
            $this->command?->info(__('supervisor_performance.demo.summary', $result));
        }

        return $results;
    }

    private function demoSupervisor(Company $company): ?Employee
    {
        return Employee::query()
            ->where('company_id', $company->id)
            ->whereHas('user', fn ($users) => $users
                ->where('email', strtolower($company->company_number).'-0@example.test'))
            ->with('user')
            ->first();
    }

    /** @return list<CarbonInterface> */
    private function demoMonths(Company $company, Employee $supervisor, CarbonInterface $today): array
    {
        $currentMonth = $today->startOfMonth();
        $shifts = SupervisionShift::query()
            ->where('company_id', $company->id)
            ->where('supervisor_id', $supervisor->id)
            ->where('title', PerformanceDashboardDemoSeeder::SUPERVISION_TITLE)
            ->whereIn('status', ['active', 'completed'])
            ->whereColumn('start_date', '<=', 'end_date');

        $previous = (clone $shifts)->where('end_date', '<', $currentMonth->toDateString())->latest('end_date')->first();
        $currentExists = $shifts->where('start_date', '<=', $today->toDateString())
            ->where('end_date', '>=', $currentMonth->toDateString())
            ->exists();

        return array_values(array_filter([
            $previous?->end_date->toImmutable()->startOfMonth(),
            $currentExists ? $currentMonth : null,
        ]));
    }

    private function seedPersonalAssignment(Company $company, Employee $supervisor, User $actor, CarbonInterface $month, CarbonInterface $today): void
    {
        $assignment = TaskAssignment::withoutEvents(fn () => TaskAssignment::query()->firstOrCreate(
            ['transaction_number' => $company->company_number.'-'.$month->format('Ym').'-SUP-PERSONAL'],
            [
                'employee_id' => $supervisor->id,
                'assignment_date' => $month->toDateString(),
                'from_date' => $month->toDateString(),
                'to_date' => $month->endOfMonth()->toDateString(),
                'created_by' => $actor->id,
            ],
        ));

        if ((int) $assignment->employee_id !== (int) $supervisor->id) {
            throw new RuntimeException('The demo personal assignment number is already in use.');
        }

        $due = $month->addDays(9)->min($today->subDay()->max($month));

        foreach (range(1, 4) as $number) {
            $task = Task::query()->firstOrCreate(['task_code' => 'PERF-DEMO-SUP-PERSONAL-'.$number], [
                'name' => __('supervisor_performance.demo.personal_task', ['number' => $number]),
                'default_weight' => 10,
            ]);

            $assignment->items()->firstOrCreate(['task_id' => $task->id], [
                'status' => TaskStatus::COMPLETED,
                'due_date' => $due->toDateString(),
                'completed_at' => ($number === 4 ? $due->addDay()->min($today) : $due)->toDateString(),
                'weight' => 10,
                'score' => 10,
            ]);
        }
    }
}
