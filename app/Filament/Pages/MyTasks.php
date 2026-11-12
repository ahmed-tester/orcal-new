<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasTaskPerformanceDashboard;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Services\EmployeePerformanceAccessService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

class MyTasks extends Page
{
    use HasTaskPerformanceDashboard;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'my-tasks';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.tasks_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('tasks.performance.my_navigation');
    }

    public static function canAccess(array $parameters = []): bool
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return false;
        }

        // Saudi employees keep the self-service task log. Foreign employees
        // only see the services card here when they are authorized.
        return Employee::query()->saudi()->whereKey($employee->getKey())->exists()
            || Gate::allows('viewAny', ServiceTransaction::class);
    }

    public function getTitle(): string|Htmlable
    {
        $employee = $this->performanceEmployee();

        if ($employee && ! Employee::query()->saudi()->whereKey($employee->getKey())->exists()) {
            return __('tasks.performance.my_services_title');
        }

        return __('tasks.performance.my_title');
    }

    protected function performanceEmployee(): ?Employee
    {
        return auth()->user()?->employee;
    }

    protected function authorizePerformance(Employee $employee): void
    {
        abort_unless((int) $employee->user_id === (int) auth()->id(), 403);
        abort_unless(EmployeePerformanceAccessService::canViewEmployeeRecord(auth()->user(), $employee), 403);
    }
}
