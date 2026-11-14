<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use App\Filament\Resources\Attendance\Pages\AttendanceSummary;
use App\Filament\Resources\Leaves\LeaveResource;
use App\Filament\Resources\Leaves\Pages\LeavesSummary;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\ServiceTransaction;
use App\Models\TaskAssignment;
use App\Services\AttendanceSummaryService;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\EmployeePerformanceAccessService;
use App\Services\LeaveSummaryService;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

class CompanyDepartmentPerformance extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 2;

    #[Locked]
    public bool $showAttendanceAndLeaves = false;

    #[Locked]
    public ?int $selectedCompanyId = null;

    #[Locked]
    public mixed $selectedDepartmentId = null;

    #[Locked]
    public bool $showKashfModal = false;

    #[Locked]
    public string $kashfDomain = 'services';

    #[Locked]
    public string $kashfType = 'all';

    #[Locked]
    public ?int $kashfCompanyId = null;

    #[Locked]
    public mixed $kashfDepartmentId = null;

    #[Locked]
    public ?int $kashfEmployeeId = null;

    public function mount(): void
    {
        $this->dateFrom ??= now()->startOfMonth()->format('Y-m-d');
        $this->dateTo ??= now()->endOfMonth()->format('Y-m-d');
    }

    public function getView(): string
    {
        return 'filament.pages.company-department-performance';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('performance.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return __('performance.title');
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOwner()) {
            return true;
        }

        if (EmployeePerformanceAccessService::isSupervisor($user)) {
            return true;
        }

        return Gate::allows('viewAny', ServiceTransaction::class)
            || Gate::allows('viewAny', TaskAssignment::class);
    }

    public function handleFilterBarUpdate(): void
    {
        $this->resetPage();
        $this->resetPage('kashfPage');
        $this->selectedDepartmentId = null;

        if (! empty($this->companyId) && $this->selectedCompanyId && ! in_array($this->selectedCompanyId, (array) $this->companyId)) {
            $this->selectedCompanyId = null;
        }

        $this->showKashfModal = false;
        $this->dispatch('close-modal', id: 'performance-kashf-modal');
    }

    public function filters(): array
    {
        return $this->filterBarState();
    }

    public function showOperationsOverview(): void
    {
        $this->showAttendanceAndLeaves = false;
    }

    public function showWorkforceOverview(): void
    {
        abort_unless($this->canViewWorkforceOverview, 403);

        $this->showAttendanceAndLeaves = true;
    }

    #[Computed]
    public function canViewWorkforceOverview(): bool
    {
        return AttendanceSummary::canAccess() || LeavesSummary::canAccess();
    }

    #[Computed]
    public function workforceOverview(): array
    {
        abort_unless($this->showAttendanceAndLeaves && $this->canViewWorkforceOverview, 403);

        [$start, $end] = CompanyDepartmentPerformanceService::resolveDateRange($this->filters());
        $month = substr($start, 0, 7);
        $year = (int) substr($start, 0, 4);
        $companyIds = $this->authorizedCompanyIds;

        if (filled($this->companyId)) {
            $companyIds = array_values(array_intersect($companyIds, (array) $this->companyId));
        }

        $user = auth()->user();
        $isSupervisor = CompanyDepartmentPerformanceService::isSupervisorViewer($user);
        $employeeIds = $isSupervisor ? CompanyDepartmentPerformanceService::supervisorTeamIds($user, $start, $end) : null;
        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($this->filters(), $user);
        $filters = ['company_id' => $companyIds ?: [0], 'branch_id' => $branchId];
        $linkFilters = ['companyId' => $filters['company_id'], 'branchId' => $branchId === null ? [] : [$branchId]];
        $canViewAttendance = AttendanceSummary::canAccess();
        $canViewLeaves = LeavesSummary::canAccess();

        return [
            'month' => $month,
            'year' => $year,
            'attendance' => $canViewAttendance ? AttendanceSummaryService::getStats($filters + ['month' => $month], $employeeIds) : null,
            'leaves' => $canViewLeaves ? LeaveSummaryService::getOverviewStats($filters + ['year' => $year], $employeeIds) : null,
            'attendance_url' => $canViewAttendance && ! $isSupervisor ? AttendanceResource::getUrl(parameters: $linkFilters + ['month' => $month]) : null,
            'leaves_url' => $canViewLeaves && ! $isSupervisor ? LeaveResource::getUrl(parameters: $linkFilters + ['year' => $year]) : null,
        ];
    }

    #[Computed]
    public function authorizedCompanyIds(): array
    {
        return CompanyDepartmentPerformanceService::getAuthorizedCompanyIds();
    }

    #[Computed]
    public function companies(): array
    {
        $ids = $this->authorizedCompanyIds;

        if ($ids === []) {
            return [];
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->pluck('trade_name', 'id')
            ->all();
    }

    #[Computed]
    public function branches(): array
    {
        $ids = $this->authorizedCompanyIds;

        if ($ids === []) {
            return [];
        }

        return Branch::query()
            ->whereIn('company_id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    #[Computed]
    public function overallStats(): object
    {
        return CompanyDepartmentPerformanceService::getOverallStats($this->filters());
    }

    #[Computed]
    public function companiesSummary(): Collection
    {
        return CompanyDepartmentPerformanceService::getCompaniesSummary($this->filters());
    }

    #[Computed]
    public function selectedCompany(): ?Company
    {
        if (! $this->selectedCompanyId) {
            return null;
        }

        return Company::query()->find($this->selectedCompanyId);
    }

    #[Computed]
    public function selectedDepartment(): ?Department
    {
        if (! $this->selectedDepartmentId || $this->selectedDepartmentId === 'unassigned') {
            return null;
        }

        return Department::query()->with('parent')->find($this->selectedDepartmentId);
    }

    #[Computed]
    public function departmentsSummary(): Collection
    {
        if (! $this->selectedCompanyId) {
            return collect();
        }

        return CompanyDepartmentPerformanceService::getDepartmentsSummary($this->selectedCompanyId, $this->filters());
    }

    #[Computed]
    public function employeesSummary(): Collection
    {
        if (! $this->selectedCompanyId || $this->selectedDepartmentId === null) {
            return collect();
        }

        return CompanyDepartmentPerformanceService::getEmployeesSummary(
            $this->selectedCompanyId,
            $this->selectedDepartmentId,
            $this->filters()
        );
    }

    #[Computed]
    public function kashfTitle(): string
    {
        return match ($this->kashfDomain) {
            'services' => __('performance.kashf.services_title', ['type' => __('performance.types.'.$this->kashfType)]),
            'deficiencies' => __('performance.kashf.deficiencies_title'),
            'tasks' => __('performance.kashf.tasks_title', ['type' => __('performance.types.'.$this->kashfType)]),
            default => __('performance.kashf.title'),
        };
    }

    #[Computed]
    public function kashfRecords(): ?LengthAwarePaginator
    {
        if (! $this->showKashfModal) {
            return null;
        }

        return match ($this->kashfDomain) {
            'services' => CompanyDepartmentPerformanceService::getKashfServicesQuery(
                $this->filters(),
                $this->kashfType,
                $this->kashfCompanyId,
                $this->kashfDepartmentId,
                $this->kashfEmployeeId
            )->paginate(15, ['*'], 'kashfPage'),
            'deficiencies' => CompanyDepartmentPerformanceService::getKashfDeficienciesQuery(
                $this->filters(),
                $this->kashfCompanyId,
                $this->kashfDepartmentId,
                $this->kashfEmployeeId
            )->paginate(15, ['*'], 'kashfPage'),
            'tasks' => CompanyDepartmentPerformanceService::getKashfTasksQuery(
                $this->filters(),
                $this->kashfType,
                $this->kashfCompanyId,
                $this->kashfDepartmentId,
                $this->kashfEmployeeId
            )->paginate(15, ['*'], 'kashfPage'),
            default => null,
        };
    }

    public function selectCompany(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        $this->selectedDepartmentId = null;
        $this->resetPage();
    }

    public function selectDepartment(mixed $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
        $this->resetPage();
    }

    public function backToCompanies(): void
    {
        $this->selectedCompanyId = null;
        $this->selectedDepartmentId = null;
        $this->resetPage();
    }

    public function backToDepartments(): void
    {
        $this->selectedDepartmentId = null;
        $this->resetPage();
    }

    public function openKashf(
        string $domain,
        string $type = 'all',
        ?int $companyId = null,
        mixed $departmentId = null,
        ?int $employeeId = null
    ): void {
        $this->kashfDomain = $domain;
        $this->kashfType = $type;
        $this->kashfCompanyId = $companyId;
        $this->kashfDepartmentId = $departmentId;
        $this->kashfEmployeeId = $employeeId;
        $this->showKashfModal = true;
        $this->resetPage('kashfPage');
        $this->dispatch('open-modal', id: 'performance-kashf-modal');
    }

    public function closeKashf(): void
    {
        $this->showKashfModal = false;
        $this->dispatch('close-modal', id: 'performance-kashf-modal');
    }
}
