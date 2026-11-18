<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasFilterBarState;
use App\Models\Company;
use App\Models\TaskAssignmentItem;
use App\Services\SupervisorPerformanceService;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

/**
 * Unified supervisor performance screen: the team indicators of every
 * supervision/shift record plus the supervisor's own employee performance.
 */
class SupervisorPerformance extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    public ?string $month = null;

    public mixed $supervisorId = null;

    public ?string $type = SupervisorPerformanceService::TYPE_ALL;

    #[Locked]
    public string $activeTab = 'supervision';

    #[Locked]
    public ?int $selectedShiftId = null;

    #[Locked]
    public ?int $statementShiftId = null;

    #[Locked]
    public string $statementMetric = SupervisorPerformanceService::METRIC_DUE;

    #[Locked]
    public bool $showStatementModal = false;

    public function mount(): void
    {
        $request = request();

        if ($request->filled('companyId')) {
            $this->companyId = (int) $request->input('companyId');
        }

        if ($request->filled('supervisorId')) {
            $this->supervisorId = (int) $request->input('supervisorId');
        }

        if ($request->filled('month')) {
            $this->month = (string) $request->input('month');
        }

        if (in_array($request->input('type'), ['supervision', 'shift'], true)) {
            $this->type = (string) $request->input('type');
        }

        if ($request->filled('shift')) {
            $this->selectedShiftId = (int) $request->input('shift');
        }

        if ($request->input('tab') === 'employee') {
            $this->activeTab = 'employee';
        }

        $this->month = SupervisorPerformanceService::normalizeMonth($this->month)->format('Y-m');
    }

    public function getView(): string
    {
        return 'filament.pages.supervisor-performance';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('supervisor_performance.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return __('supervisor_performance.title');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return SupervisorPerformanceService::canViewReport();
    }

    public function filters(): array
    {
        return [
            'month' => $this->month,
            'company_id' => $this->companyId,
            'supervisor_id' => $this->supervisorId,
            'type' => $this->type,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function filterBarExtraProperties(): array
    {
        return ['supervisorId', 'type', 'month'];
    }

    public function hasActiveFilterBar(): bool
    {
        return filled($this->companyId)
            || filled($this->supervisorId)
            || ($this->type !== null && $this->type !== SupervisorPerformanceService::TYPE_ALL);
    }

    public function handleFilterBarUpdate(): void
    {
        $this->closeStatement();
        $this->selectedShiftId = null;
        $this->resetPage('statementPage');

        if (filled($this->companyId) && filled($this->supervisorId)
            && ! in_array((int) $this->supervisorId, $this->supervisorIds(), true)) {
            $this->supervisorId = null;
        }
    }

    public function resetFilterBar(): void
    {
        $this->companyId = null;
        $this->supervisorId = null;
        $this->type = SupervisorPerformanceService::TYPE_ALL;
        $this->month = now()->format('Y-m');
        $this->selectedShiftId = null;

        $this->handleFilterBarUpdate();
    }

    #[Computed]
    public function authorizedCompanyIds(): array
    {
        return SupervisorPerformanceService::authorizedCompanyIds();
    }

    #[Computed]
    public function companyFilterOptions(): array
    {
        $ids = $this->authorizedCompanyIds;

        if ($ids === []) {
            return [];
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->pluck('trade_name', 'id')
            ->map(fn ($name, $id) => ['id' => $id, 'label' => $name])
            ->values()
            ->all();
    }

    #[Computed]
    public function supervisorOptions(): array
    {
        return SupervisorPerformanceService::supervisorOptions($this->filters());
    }

    #[Computed]
    public function report(): array
    {
        abort_unless(SupervisorPerformanceService::canViewReport(), 403);

        return SupervisorPerformanceService::report(
            $this->filters(),
            $this->activeTab === 'employee',
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function employeeTab(): array
    {
        return $this->report['employee'];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return [
            SupervisorPerformanceService::TYPE_ALL => __('supervisor_performance.filters.types.all'),
            'supervision' => __('supervisor_performance.filters.types.supervision'),
            'shift' => __('supervisor_performance.filters.types.shift'),
        ];
    }

    public function showSupervisionTab(): void
    {
        $this->activeTab = 'supervision';
        $this->closeStatement();
    }

    public function showEmployeeTab(): void
    {
        $this->activeTab = 'employee';
        $this->closeStatement();
    }

    public function isSelectedShift(int $shiftId): bool
    {
        return $this->selectedShiftId !== null && $this->selectedShiftId === $shiftId;
    }

    public function openStatement(int $shiftId, string $metric): void
    {
        abort_unless(SupervisorPerformanceService::canViewTeamTaskData(), 403);
        abort_unless(in_array($metric, [
            SupervisorPerformanceService::METRIC_DUE,
            SupervisorPerformanceService::METRIC_ON_TIME,
            SupervisorPerformanceService::METRIC_LATE,
            SupervisorPerformanceService::METRIC_OPEN,
            SupervisorPerformanceService::METRIC_EXCLUDED,
        ], true), 404);

        $row = collect($this->report['rows'])->first(fn (array $row) => $row['shift_id'] === $shiftId);

        abort_if($row === null, 404);

        $this->statementShiftId = $shiftId;
        $this->statementMetric = $metric;
        $this->showStatementModal = true;
        $this->resetPage('statementPage');
        $this->dispatch('open-modal', id: 'supervisor-performance-statement');
    }

    public function closeStatement(): void
    {
        $this->showStatementModal = false;
        $this->statementShiftId = null;
        $this->dispatch('close-modal', id: 'supervisor-performance-statement');
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function statementRow(): ?array
    {
        if (! $this->showStatementModal || $this->statementShiftId === null) {
            return null;
        }

        return collect($this->report['rows'])
            ->first(fn (array $row) => $row['shift_id'] === $this->statementShiftId);
    }

    /**
     * @return Collection<int, array{item: TaskAssignmentItem, state: string}>
     */
    #[Computed]
    public function statementItems(): Collection
    {
        $row = $this->statementRow;

        if ($row === null) {
            return collect();
        }

        return SupervisorPerformanceService::statementItems($row, $this->statementMetric);
    }

    #[Computed]
    public function statementPaginator(): ?LengthAwarePaginator
    {
        if ($this->statementRow === null) {
            return null;
        }

        $items = $this->statementItems;
        $perPage = 25;
        $page = max(1, (int) $this->getPage('statementPage'));

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['pageName' => 'statementPage'],
        );
    }

    #[Computed]
    public function statementTitle(): string
    {
        $row = $this->statementRow;
        $shift = $row['shift'] ?? null;

        return __('supervisor_performance.statement.title', [
            'metric' => __('supervisor_performance.statement.metrics.'.$this->statementMetric),
            'shift' => $shift?->title ?: ($shift?->task?->name ?? '#'.$this->statementShiftId),
        ]);
    }

    /**
     * @return array<int>
     */
    private function supervisorIds(): array
    {
        return collect($this->supervisorOptions)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
