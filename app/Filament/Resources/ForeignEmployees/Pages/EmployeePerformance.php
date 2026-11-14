<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Filament\Concerns\HasTaskPerformanceDashboard;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Models\Employee;
use App\Models\ForeignEmployee;
use App\Services\EmployeePerformanceAccessService;
use App\Services\TaskAssignmentSummaryService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

// Temporarily disabled until the employee performance files are deployed together.
/*
class EmployeePerformance extends Page
{
    use HasTaskPerformanceDashboard;
    use InteractsWithRecord;

    protected static string $resource = ForeignEmployeeResource::class;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;
        $user = auth()->user();

        return $record instanceof Employee
            && $user !== null
            && ForeignEmployee::query()->whereKey($record->getKey())->exists()
            && EmployeePerformanceAccessService::canViewEmployeeRecord($user, $record)
            && EmployeePerformanceAccessService::canViewAnyData($user, $record);
    }

    protected function performanceEmployee(): ?Employee
    {
        $record = $this->getRecord();

        return $record instanceof Employee ? $record : null;
    }

    protected function authorizePerformance(Employee $employee): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless(ForeignEmployee::query()->whereKey($employee->getKey())->exists(), 404);

        $periodStart = TaskAssignmentSummaryService::monthStart($this->month);
        $periodEnd = $periodStart->copy()->endOfMonth();

        abort_unless(
            EmployeePerformanceAccessService::canViewEmployeeRecord($user, $employee, $periodStart, $periodEnd),
            403
        );
        abort_unless(
            EmployeePerformanceAccessService::canViewAnyData($user, $employee, $periodStart, $periodEnd),
            403
        );
    }
}
*/
