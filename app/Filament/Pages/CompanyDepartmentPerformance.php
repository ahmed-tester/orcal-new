<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use App\Filament\Resources\Attendance\Pages\AttendanceSummary;
use App\Filament\Resources\Leaves\LeaveResource;
use App\Filament\Resources\Leaves\Pages\LeavesSummary;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Filament\Resources\Violations\Pages\ViolationsSummary;
use App\Filament\Resources\Violations\ViolationResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\ServiceTransaction;
use App\Models\TaskAssignment;
use App\Services\AttendanceSummaryService;
use App\Services\CompanyCompliancePerformanceService;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\CompanyFinancialPerformanceService;
use App\Services\CompanySummaryOverviewService;
use App\Services\CompanyTicketsPerformanceService;
use App\Services\CompanyViolationsPerformanceService;
use App\Services\LeaveSummaryService;
use App\Services\PerformanceScoreService;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
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

    private const ATTENDANCE_STATEMENT_TYPES = ['present_days', 'late_days', 'absent_days'];

    /** Department card display modes inside an open company. */
    public const DEPARTMENT_RANK_MODE_ALL = 'all';

    public const DEPARTMENT_RANK_MODE_BEST_FIRST = 'best_first';

    public const DEPARTMENT_RANK_MODE_WORST = 'worst';

    public const DEPARTMENT_RANK_MODES = [
        self::DEPARTMENT_RANK_MODE_ALL,
        self::DEPARTMENT_RANK_MODE_BEST_FIRST,
        self::DEPARTMENT_RANK_MODE_WORST,
    ];

    /** Operations tab grouping modes. */
    public const OPERATIONS_VIEW_COMPANY = 'company';

    public const OPERATIONS_VIEW_DEPARTMENT = 'department';

    public const EMPLOYEE_RANK_MODE_ALL = 'all';

    public const EMPLOYEE_RANK_MODE_BEST_FIRST = 'best_first';

    public const EMPLOYEE_RANK_MODE_WORST = 'worst';

    public const EMPLOYEE_RANK_MODES = [
        self::EMPLOYEE_RANK_MODE_ALL,
        self::EMPLOYEE_RANK_MODE_BEST_FIRST,
        self::EMPLOYEE_RANK_MODE_WORST,
    ];

    #[Locked]
    public bool $showAttendanceAndLeaves = false;

    #[Locked]
    public string $activeOverviewTab = 'summary';

    #[Locked]
    public ?int $summaryDepartmentId = null;

    #[Locked]
    public array $financialPreviewTypes = [];

    #[Locked]
    public array $workforcePreviewTypes = [];

    #[Locked]
    public array $compliancePreviewTypes = [];

    #[Locked]
    public ?int $selectedCompanyId = null;

    #[Locked]
    public string $departmentRankMode = self::DEPARTMENT_RANK_MODE_ALL;

    #[Locked]
    public string $employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;

    #[Locked]
    public string $operationsViewMode = self::OPERATIONS_VIEW_COMPANY;

    #[Locked]
    public ?int $detailsDepartmentId = null;

    #[Locked]
    public mixed $selectedDepartmentId = null;

    #[Locked]
    public bool $showStatementModal = false;

    #[Locked]
    public string $statementDomain = 'services';

    #[Locked]
    public string $statementType = 'all';

    #[Locked]
    public ?int $statementCompanyId = null;

    #[Locked]
    public mixed $statementDepartmentId = null;

    #[Locked]
    public ?int $statementEmployeeId = null;

    public function mount(): void
    {
        $this->dateFrom ??= now()->startOfMonth()->format('Y-m-d');
        $this->dateTo ??= now()->endOfMonth()->format('Y-m-d');
        $this->clearUnavailableBranchFilter();
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

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user) !== [];
        }

        return CompanyDepartmentPerformanceService::isSupervisorViewer($user);
    }

    public function handleFilterBarUpdate(): void
    {
        unset($this->branchFilterOptions, $this->departmentsOverview, $this->summaryData);
        $this->clearUnavailableBranchFilter();

        $this->resetPage();
        $this->resetPage('statementPage');
        $this->selectedDepartmentId = null;

        if ($this->summaryDepartmentId !== null && ! $this->departmentsOverview->contains('department_id', $this->summaryDepartmentId)) {
            $this->summaryDepartmentId = null;
        }

        if (CompanyDepartmentPerformanceService::resolveBranchId($this->filters()) !== null) {
            unset($this->financialPreviewTypes['treasury']);
        }

        if (! empty($this->companyId) && $this->selectedCompanyId && ! in_array($this->selectedCompanyId, (array) $this->companyId)) {
            $this->selectedCompanyId = null;
        }

        if ($this->selectedCompanyId === null) {
            $this->departmentRankMode = self::DEPARTMENT_RANK_MODE_ALL;
            $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        }

        if ($this->selectedDepartmentId === null) {
            $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        }

        $this->showStatementModal = false;
        $this->dispatch('close-modal', id: 'performance-statement-modal');
    }

    public function filters(): array
    {
        return $this->filterBarState();
    }

    public function showSummaryOverview(): void
    {
        $this->activeOverviewTab = 'summary';
        $this->showAttendanceAndLeaves = false;
        $this->closeStatement();
    }

    public function selectSummaryDepartment(int $departmentId): void
    {
        abort_unless($this->departmentsOverview->contains('department_id', $departmentId), 404);

        $this->summaryDepartmentId = $departmentId;
        unset($this->summaryData);
        $this->closeStatement();
    }

    public function backToSummaryOverview(): void
    {
        $this->summaryDepartmentId = null;
        unset($this->summaryData);
        $this->closeStatement();
    }

    #[Computed]
    public function summaryData(): ?array
    {
        if ($this->activeOverviewTab !== 'summary') {
            return null;
        }

        if ($this->summaryDepartmentId !== null && ! $this->departmentsOverview->contains('department_id', $this->summaryDepartmentId)) {
            return null;
        }

        return CompanySummaryOverviewService::build(
            $this->filters(),
            $this->authorizedCompanyIds,
            $this->summaryDepartmentId,
        );
    }

    #[Computed]
    public function selectedSummaryDepartment(): ?object
    {
        if ($this->summaryDepartmentId === null) {
            return null;
        }

        return $this->departmentsOverview->firstWhere('department_id', $this->summaryDepartmentId);
    }

    public function showOperationsOverview(): void
    {
        $this->activeOverviewTab = 'operations';
        $this->showAttendanceAndLeaves = false;
    }

    public function showWorkforceOverview(): void
    {
        abort_unless($this->canViewWorkforceOverview, 403);

        $this->activeOverviewTab = 'workforce';
        $this->showAttendanceAndLeaves = true;
        $this->closeStatement();
    }

    public function showComplianceOverview(): void
    {
        abort_unless($this->canViewComplianceOverview, 403);

        $this->activeOverviewTab = 'compliance';
        $this->showAttendanceAndLeaves = false;
    }

    public function showViolationsOverview(): void
    {
        abort_unless($this->canViewViolationsOverview, 403);

        $this->activeOverviewTab = 'violations';
        $this->showAttendanceAndLeaves = false;
    }

    public function showFinancialOverview(): void
    {
        abort_unless($this->canViewFinancialOverview, 403);

        $this->activeOverviewTab = 'financial';
        $this->showAttendanceAndLeaves = false;
        $this->closeStatement();
    }

    public function showTicketsOverview(): void
    {
        abort_unless($this->canViewTicketsOverview, 403);

        $this->activeOverviewTab = 'tickets';
        $this->showAttendanceAndLeaves = false;
        $this->closeStatement();
    }

    #[Computed]
    public function canViewWorkforceOverview(): bool
    {
        return AttendanceSummary::canAccess() || LeavesSummary::canAccess();
    }

    #[Computed]
    public function canViewViolationsOverview(): bool
    {
        return ! CompanyDepartmentPerformanceService::isSupervisorViewer() && ViolationsSummary::canAccess();
    }

    #[Computed]
    public function canViewComplianceOverview(): bool
    {
        return CompanyCompliancePerformanceService::canView();
    }

    #[Computed]
    public function canViewFinancialOverview(): bool
    {
        return CompanyFinancialPerformanceService::canView();
    }

    #[Computed]
    public function canViewTicketsOverview(): bool
    {
        return CompanyTicketsPerformanceService::canView();
    }

    #[Computed]
    public function financialOverview(): array
    {
        abort_unless($this->canViewFinancialOverview, 403);

        return CompanyFinancialPerformanceService::overview($this->filters(), $this->authorizedCompanyIds);
    }

    #[Computed]
    public function financialCardGroups(): array
    {
        $overview = $this->financialOverview;
        $groups = $overview['groups'];
        $treasury = $overview['treasury'];

        if ($treasury !== null && ! $treasury['branch_restricted']) {
            $groups = ['treasury' => [
                'current_balance' => $treasury['current_balance'],
                'incoming' => $treasury['total_incoming'],
                'outgoing' => $treasury['total_outgoing'],
                'net_flow' => $treasury['net_flow'],
            ]] + $groups;
        }

        return $groups;
    }

    public function selectFinancialPreview(string $group, string $type): void
    {
        abort_unless($this->activeOverviewTab === 'financial', 404);
        CompanyFinancialPerformanceService::authorizeStatement($type, $this->filters());
        abort_unless(isset($this->financialCardGroups[$group][$type]), 404);

        $this->financialPreviewTypes[$group] = $type;
    }

    #[Computed]
    public function financialPreviews(): array
    {
        if ($this->activeOverviewTab !== 'financial') {
            return [];
        }

        $filters = $this->filters();
        $companyIds = $this->authorizedCompanyIds;
        $previews = [];
        foreach ($this->financialCardGroups as $group => $cards) {
            $type = $this->financialPreviewTypes[$group]
                ?? array_key_first(array_filter($cards))
                ?? array_key_first($cards);
            $previews[$group] = [
                'type' => $type,
                'records' => CompanyFinancialPerformanceService::preview($type, $filters, $companyIds),
            ];
        }

        return $previews;
    }

    #[Computed]
    public function treasurySummaryUrl(): ?string
    {
        if (! CompanyFinancialPerformanceService::canViewTreasury()
            || CompanyDepartmentPerformanceService::resolveBranchId($this->filters()) !== null) {
            return null;
        }

        return TreasuryTransactionResource::getUrl('index', parameters: array_filter([
            'companyId' => $this->companyId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ], filled(...)));
    }

    #[Computed]
    public function complianceOverview(): array
    {
        abort_unless($this->canViewComplianceOverview, 403);

        return CompanyCompliancePerformanceService::getStats($this->filters());
    }

    #[Computed]
    public function complianceCardGroups(): array
    {
        $overview = $this->complianceOverview;
        $groups = [];

        if ($overview['iqama'] !== null) {
            $groups['iqama'] = [
                'iqama_expired' => $overview['iqama']['expired'],
                'iqama_within_3m' => $overview['iqama']['within_3m'],
                'iqama_within_6m' => $overview['iqama']['within_6m'],
                'iqama_within_9m' => $overview['iqama']['within_9m'],
                'iqama_valid_over_9m' => $overview['iqama']['valid_over_9m'],
            ];
        }

        if ($overview['work_permits'] !== null) {
            $groups['work_documents'] = [
                'work_permit_expired' => $overview['work_permits']['expired'],
                'work_permit_expiring_soon' => $overview['work_permits']['expiring_soon'],
                'work_permit_valid' => $overview['work_permits']['valid'],
                'insurance_expired' => $overview['insurance']['expired'],
                'insurance_expiring_soon' => $overview['insurance']['expiring_soon'],
                'insurance_valid' => $overview['insurance']['valid'],
            ];
        }

        return $groups;
    }

    public function selectCompliancePreview(string $group, string $type): void
    {
        abort_unless($this->activeOverviewTab === 'compliance', 404);
        CompanyCompliancePerformanceService::authorizeStatement($type);
        abort_unless(isset($this->complianceCardGroups[$group][$type]), 404);

        $this->compliancePreviewTypes[$group] = $type;
    }

    #[Computed]
    public function compliancePreviews(): array
    {
        if ($this->activeOverviewTab !== 'compliance') {
            return [];
        }

        $filters = $this->filters();
        $previews = [];
        foreach ($this->complianceCardGroups as $group => $cards) {
            $type = $this->compliancePreviewTypes[$group]
                ?? array_key_first(array_filter($cards))
                ?? array_key_first($cards);
            $previews[$group] = [
                'type' => $type,
                'records' => CompanyCompliancePerformanceService::getStatementQuery($filters, $type)->limit(5)->get(),
            ];
        }

        return $previews;
    }

    #[Computed]
    public function violationsOverview(): array
    {
        return CompanyViolationsPerformanceService::getStats($this->filters());
    }

    #[Computed]
    public function violationsRegisterUrl(): string
    {
        return ViolationResource::getUrl(parameters: [
            'companyId' => $this->companyId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }

    #[Computed]
    public function ticketsOverview(): array
    {
        abort_unless($this->canViewTicketsOverview, 403);

        return CompanyTicketsPerformanceService::getStats($this->filters());
    }

    #[Computed]
    public function ticketsRegisterUrl(): ?string
    {
        if (! TicketResource::canViewAny()) {
            return null;
        }

        return TicketResource::getUrl('index');
    }

    #[Computed]
    public function workforceOverview(): array
    {
        abort_unless($this->showAttendanceAndLeaves && $this->canViewWorkforceOverview, 403);

        [$filters, $employeeIds, $month, $year] = $this->workforceScope();
        $branchId = $filters['branch_id'];
        $linkFilters = ['companyId' => $filters['company_id'], 'branchId' => $branchId === null ? [] : [$branchId]];
        $canViewAttendance = AttendanceSummary::canAccess();
        $canViewLeaves = LeavesSummary::canAccess();

        return [
            'month' => $month,
            'year' => $year,
            'attendance' => $canViewAttendance ? AttendanceSummaryService::getStats($filters + ['month' => $month], $employeeIds) : null,
            'leaves' => $canViewLeaves ? LeaveSummaryService::getOverviewStats($filters + ['year' => $year], $employeeIds) : null,
            'attendance_url' => $canViewAttendance && $employeeIds === null ? AttendanceResource::getUrl(parameters: $linkFilters + ['month' => $month]) : null,
            'leaves_url' => $canViewLeaves && $employeeIds === null ? LeaveResource::getUrl(parameters: $linkFilters + ['year' => $year]) : null,
        ];
    }

    #[Computed]
    public function workforceCardGroups(): array
    {
        $overview = $this->workforceOverview;
        $groups = [];

        if ($overview['attendance'] !== null) {
            $groups['attendance'] = [
                'present_days' => $overview['attendance']['present_days'],
                'late_days' => $overview['attendance']['late_days'],
                'absent_days' => $overview['attendance']['absent_days'],
            ];
        }

        if ($overview['leaves'] !== null) {
            $groups['leaves'] = [
                'on_leave_today_count' => $overview['leaves']['on_leave_today_count'],
                'pending_requests_count' => $overview['leaves']['pending_requests_count'],
                'total_approved_days' => $overview['leaves']['total_approved_days'],
            ];
        }

        return $groups;
    }

    public function selectWorkforcePreview(string $group, string $type): void
    {
        abort_unless($this->activeOverviewTab === 'workforce', 404);
        abort_unless(isset($this->workforceCardGroups[$group][$type]), 404);

        if ($group === 'attendance') {
            $this->authorizeAttendanceStatement($type);
        } elseif ($group === 'leaves') {
            abort_unless(LeavesSummary::canAccess(), 403);
        }

        $this->workforcePreviewTypes[$group] = $type;
    }

    #[Computed]
    public function workforcePreviews(): array
    {
        if ($this->activeOverviewTab !== 'workforce') {
            return [];
        }

        $previews = [];

        foreach ($this->workforceCardGroups as $group => $cards) {
            $type = $this->workforcePreviewTypes[$group]
                ?? array_key_first(array_filter($cards))
                ?? array_key_first($cards);

            $previews[$group] = [
                'type' => $type,
                'records' => $this->workforcePreviewRecords($group, $type),
            ];
        }

        return $previews;
    }

    private function workforcePreviewRecords(string $group, string $type): Collection
    {
        if ($group === 'attendance') {
            return $this->attendanceStatementQuery($type)->limit(5)->get();
        }

        if ($group === 'leaves') {
            return $this->leaveStatementQuery($type)->limit(5)->get();
        }

        return collect();
    }

    private function workforceScope(): array
    {
        [$start, $end] = CompanyDepartmentPerformanceService::resolveDateRange($this->filters());
        $companyIds = $this->authorizedCompanyIds;

        if (filled($this->companyId)) {
            $companyIds = array_values(array_intersect($companyIds, (array) $this->companyId));
        }

        $user = auth()->user();
        $isSupervisor = CompanyDepartmentPerformanceService::isSupervisorViewer($user);
        $employeeIds = $isSupervisor ? CompanyDepartmentPerformanceService::supervisorTeamIds($user, $start, $end) : null;
        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($this->filters(), $user);

        return [
            ['company_id' => $companyIds ?: [0], 'branch_id' => $branchId],
            $employeeIds,
            substr($start, 0, 7),
            (int) substr($start, 0, 4),
        ];
    }

    #[Computed]
    public function authorizedCompanyIds(): array
    {
        return CompanyDepartmentPerformanceService::getAuthorizedCompanyIds();
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
    public function branchFilterOptions(): array
    {
        $ids = $this->authorizedCompanyIds;

        if (filled($this->companyId)) {
            $ids = array_intersect($ids, (array) $this->companyId);
        }

        if ($ids === []) {
            return [];
        }

        $restrictedBranch = CompanyDepartmentPerformanceService::resolveBranchId([]);

        return Branch::query()
            ->whereIn('company_id', $ids)
            ->when($restrictedBranch !== null, fn ($query) => $query->whereKey($restrictedBranch))
            ->orderBy('id')
            ->pluck('name', 'id')
            ->map(fn ($name, $id) => ['id' => $id, 'label' => $name])
            ->values()
            ->all();
    }

    private function clearUnavailableBranchFilter(): void
    {
        if (filled($this->branchId) && ! in_array($this->branchId, array_column($this->branchFilterOptions, 'id'))) {
            $this->branchId = null;
        }
    }

    #[Computed]
    public function overallStats(): object
    {
        return CompanyDepartmentPerformanceService::getOverallStats($this->filters());
    }

    #[Computed]
    public function performanceScore(): object
    {
        return PerformanceScoreService::getScore(
            $this->filters(),
            $this->selectedCompanyId,
            $this->selectedDepartmentId,
        );
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

    /**
     * Department cards after applying the selected display mode.
     *
     * - all: keeps the service order.
     * - best_first: sorts rankable departments by score descending (ties keep
     *   the service order) and puts non-rankable cards last.
     * - worst: keeps every rankable department tied on the lowest score.
     */
    #[Computed]
    public function displayedDepartments(): Collection
    {
        $departments = $this->departmentsSummary;

        return match ($this->departmentRankMode) {
            self::DEPARTMENT_RANK_MODE_BEST_FIRST => $this->bestFirstDepartments($departments),
            self::DEPARTMENT_RANK_MODE_WORST => $this->worstDepartments($departments),
            default => $departments,
        };
    }

    public function selectDepartmentRankMode(string $mode): void
    {
        abort_unless(in_array($mode, self::DEPARTMENT_RANK_MODES, true), 404);

        $this->departmentRankMode = $mode;
    }

    private function rankableDepartments(Collection $departments): Collection
    {
        return $departments->filter(
            fn (object $row) => ($row->is_rankable ?? false) && $row->score !== null
        );
    }

    private function bestFirstDepartments(Collection $departments): Collection
    {
        $rankable = $this->rankableDepartments($departments);
        $others = $departments->reject(
            fn (object $row) => ($row->is_rankable ?? false) && $row->score !== null
        );

        return $rankable
            ->sortByDesc(fn (object $row) => $row->score->score)
            ->concat($others)
            ->values();
    }

    private function worstDepartments(Collection $departments): Collection
    {
        $rankable = $this->rankableDepartments($departments);

        if ($rankable->isEmpty()) {
            return collect();
        }

        $lowestScore = $rankable->min(fn (object $row) => $row->score->score);

        return $rankable
            ->filter(fn (object $row) => $row->score->score === $lowestScore)
            ->values();
    }

    /**
     * Top 5 eligible departments for the sprint track inside the selected company.
     *
     * Rankable departments with available score, excluding 'unassigned', sorted
     * descending by score (ties preserve original order), max 5 rows.
     */
    #[Computed]
    public function topRankedDepartments(): Collection
    {
        $departments = $this->departmentsSummary;

        if ($departments->isEmpty()) {
            return collect();
        }

        $rankable = $departments->filter(
            fn (object $row) => ($row->is_rankable ?? false)
                && $row->score !== null
                && ($row->score->available ?? false)
                && $row->score->score !== null
                && $row->department_id !== 'unassigned'
        );

        if ($rankable->isEmpty()) {
            return collect();
        }

        $sorted = $rankable->sortByDesc(fn (object $row) => $row->score->score)->values();

        $highestScore = $sorted->first()->score->score;
        $sharedLeaderCount = $sorted->filter(fn (object $row) => $row->score->score === $highestScore)->count();
        $isSharedLead = $sharedLeaderCount > 1;

        $top5 = $sorted->take(5)->values();

        return $top5->map(function (object $dept, int $index) use ($top5, $isSharedLead) {
            $score = (int) $dept->score->score;
            $prevScore = $index > 0 ? (int) $top5[$index - 1]->score->score : null;
            $pointsBehind = $prevScore !== null ? $prevScore - $score : 0;
            $isTiedWithPrev = $index > 0 && $pointsBehind === 0;

            return (object) [
                'department_id' => $dept->department_id,
                'department_name' => $dept->department_name,
                'score' => $score,
                'rank' => $index + 1,
                'is_first' => $index === 0,
                'is_shared_lead' => $index === 0 && $isSharedLead,
                'is_tied_with_prev' => $isTiedWithPrev,
                'points_behind' => $pointsBehind,
                'raw' => $dept,
            ];
        });
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
    public function displayedEmployees(): Collection
    {
        $employees = $this->employeesSummary;

        return match ($this->employeeRankMode) {
            self::EMPLOYEE_RANK_MODE_BEST_FIRST => $this->bestFirstEmployees($employees),
            self::EMPLOYEE_RANK_MODE_WORST => $this->worstEmployees($employees),
            default => $employees,
        };
    }

    public function selectEmployeeRankMode(string $mode): void
    {
        abort_unless(in_array($mode, self::EMPLOYEE_RANK_MODES, true), 404);

        $this->employeeRankMode = $mode;
    }

    private function rankableEmployees(Collection $employees): Collection
    {
        return $employees->filter(
            fn (object $row) => ($row->is_rankable ?? false)
                && $row->score !== null
                && ($row->score->available ?? false)
                && $row->score->score !== null
        );
    }

    private function bestFirstEmployees(Collection $employees): Collection
    {
        $rankable = $this->rankableEmployees($employees);
        $others = $employees->reject(
            fn (object $row) => ($row->is_rankable ?? false)
                && $row->score !== null
                && ($row->score->available ?? false)
                && $row->score->score !== null
        );

        return $rankable
            ->sortByDesc(fn (object $row) => $row->score->score)
            ->concat($others)
            ->values();
    }

    private function worstEmployees(Collection $employees): Collection
    {
        $rankable = $this->rankableEmployees($employees);

        if ($rankable->isEmpty()) {
            return collect();
        }

        $lowestScore = $rankable->min(fn (object $row) => $row->score->score);

        return $rankable
            ->filter(fn (object $row) => $row->score->score === $lowestScore)
            ->values();
    }

    /**
     * Top 5 eligible employees for the sprint track inside the selected department.
     */
    #[Computed]
    public function topRankedEmployees(): Collection
    {
        $employees = $this->employeesSummary;

        if ($employees->isEmpty()) {
            return collect();
        }

        $rankable = $employees->filter(
            fn (object $row) => ($row->is_rankable ?? false)
                && $row->score !== null
                && ($row->score->available ?? false)
                && $row->score->score !== null
        );

        if ($rankable->isEmpty()) {
            return collect();
        }

        $sorted = $rankable->sortByDesc(fn (object $row) => $row->score->score)->values();

        $highestScore = $sorted->first()->score->score;
        $sharedLeaderCount = $sorted->filter(fn (object $row) => $row->score->score === $highestScore)->count();
        $isSharedLead = $sharedLeaderCount > 1;

        $top5 = $sorted->take(5)->values();

        return $top5->map(function (object $emp, int $index) use ($top5, $isSharedLead) {
            $score = (int) $emp->score->score;
            $prevScore = $index > 0 ? (int) $top5[$index - 1]->score->score : null;
            $pointsBehind = $prevScore !== null ? $prevScore - $score : 0;
            $isTiedWithPrev = $index > 0 && $pointsBehind === 0;

            return (object) [
                'employee_id' => $emp->employee_id,
                'employee_name' => $emp->name,
                'score' => $score,
                'rank' => $index + 1,
                'is_first' => $index === 0,
                'is_shared_lead' => $index === 0 && $isSharedLead,
                'is_tied_with_prev' => $isTiedWithPrev,
                'points_behind' => $pointsBehind,
                'performance_url' => $emp->performance_url ?? null,
                'raw' => $emp,
            ];
        });
    }

    #[Computed]
    public function statementTitle(): string
    {
        return match ($this->statementDomain) {
            'services' => __('performance.statement.services_title', ['type' => __('performance.types.'.$this->statementType)]),
            'deficiencies' => $this->statementType === 'score_overdue'
                ? __('performance.statement.score_deficiencies_title')
                : __('performance.statement.deficiencies_title'),
            'tasks' => $this->statementType === 'score_overdue'
                ? __('performance.statement.score_tasks_title')
                : __('performance.statement.tasks_title', ['type' => __('performance.types.'.$this->statementType)]),
            'compliance' => __('performance.statement.compliance_title', ['type' => CompanyCompliancePerformanceService::statementTitle($this->statementType)]),
            'violations' => __('performance.statement.violations_title', [
                'type' => __('performance.violations.'.match ($this->statementType) {
                    'all' => 'total_violations',
                    'payments' => 'total_paid',
                    'unpaid' => 'total_remaining',
                    default => $this->statementType,
                }),
            ]),
            'attendance' => __('performance.statement.attendance_title', [
                'type' => __('performance.overview.'.$this->statementType),
                'month' => substr(CompanyDepartmentPerformanceService::resolveDateRange($this->filters())[0], 0, 7),
            ]),
            'leaves' => __('performance.statement.leaves_title', ['type' => __('performance.overview.'.$this->statementType)]),
            'financial' => match ($this->statementType) {
                'current_balance' => __('performance.financial.statement_current_balance'),
                'incoming' => __('performance.financial.statement_incoming'),
                'outgoing' => __('performance.financial.statement_outgoing'),
                'net_flow' => __('performance.financial.statement_net_flow'),
                default => __('performance.financial.metrics.'.$this->statementType),
            },
            'tickets' => __('performance.statement.tickets_title', [
                'type' => __('performance.tickets.'.($this->statementType === 'all' ? 'total' : $this->statementType)),
            ]),
            default => __('performance.statement.title'),
        };
    }

    #[Computed]
    public function statementRecords(): ?LengthAwarePaginator
    {
        if (! $this->showStatementModal) {
            return null;
        }

        if ($this->isDepartmentOverviewScoreStatement($this->statementDomain, $this->statementType, $this->statementDepartmentId)
            && ! $this->departmentOverviewIncludesCompany($this->statementCompanyId, $this->statementDepartmentId)) {
            return null;
        }

        return match ($this->statementDomain) {
            'services' => CompanyDepartmentPerformanceService::getServicesStatementQuery(
                $this->filters(),
                $this->statementType,
                $this->statementCompanyId,
                $this->statementDepartmentId,
                $this->statementEmployeeId
            )->paginate(15, ['*'], 'statementPage'),
            'deficiencies' => CompanyDepartmentPerformanceService::getDeficienciesStatementQuery(
                $this->filters(),
                $this->statementType,
                $this->statementCompanyId,
                $this->statementDepartmentId,
                $this->statementEmployeeId
            )->paginate(15, ['*'], 'statementPage'),
            'tasks' => CompanyDepartmentPerformanceService::getTasksStatementQuery(
                $this->filters(),
                $this->statementType,
                $this->statementCompanyId,
                $this->statementDepartmentId,
                $this->statementEmployeeId
            )->paginate(15, ['*'], 'statementPage'),
            'compliance' => CompanyCompliancePerformanceService::getStatementQuery(
                $this->filters(),
                $this->statementType,
                $this->statementCompanyId,
                $this->statementDepartmentId
            )->paginate(15, ['*'], 'statementPage'),
            'violations' => CompanyViolationsPerformanceService::getStatementQuery(
                $this->filters(),
                $this->statementType,
                $this->statementCompanyId
            )->paginate(15, ['*'], 'statementPage'),
            'attendance' => $this->attendanceStatement(),
            'leaves' => $this->leaveStatement(),
            'financial' => $this->financialStatement(),
            'tickets' => CompanyTicketsPerformanceService::getStatementQuery(
                $this->filters(),
                $this->statementType
            )->paginate(15, ['*'], 'statementPage'),
            default => null,
        };
    }

    private function financialStatement(): LengthAwarePaginator
    {
        return CompanyFinancialPerformanceService::statement(
            $this->statementType,
            $this->filters(),
            $this->authorizedCompanyIds,
            $this->statementCompanyId,
            $this->statementDepartmentId
        );
    }

    private function attendanceStatementQuery(string $type): Builder
    {
        $this->authorizeAttendanceStatement($type);
        [$filters, $employeeIds, $month] = $this->workforceScope();
        $column = 'att.'.$type;

        return AttendanceSummaryService::summaryQuery($filters + ['month' => $month], $employeeIds)
            ->where($column, '>', 0)
            ->orderByDesc($column)
            ->orderBy('employees.id');
    }

    private function leaveStatementQuery(string $type): Builder
    {
        abort_unless(LeavesSummary::canAccess(), 403);
        [$filters, $employeeIds, , $year] = $this->workforceScope();

        return LeaveSummaryService::getOverviewStatementQuery($filters + ['year' => $year], $type, $employeeIds);
    }

    private function attendanceStatement(): LengthAwarePaginator
    {
        return $this->attendanceStatementQuery($this->statementType)->paginate(15, ['*'], 'statementPage');
    }

    private function leaveStatement(): LengthAwarePaginator
    {
        return $this->leaveStatementQuery($this->statementType)->paginate(15, ['*'], 'statementPage');
    }

    private function authorizeAttendanceStatement(string $type): void
    {
        abort_unless(AttendanceSummary::canAccess(), 403);
        abort_unless(in_array($type, self::ATTENDANCE_STATEMENT_TYPES, true), 404);
    }

    public function selectOperationsViewMode(string $mode): void
    {
        abort_unless(in_array($mode, [self::OPERATIONS_VIEW_COMPANY, self::OPERATIONS_VIEW_DEPARTMENT], true), 404);

        $this->operationsViewMode = $mode;
        $this->closeDepartmentDetails();
        $this->closeStatement();
        $this->backToCompanies();
    }

    #[Computed]
    public function departmentsOverview(): Collection
    {
        return CompanyDepartmentPerformanceService::getDepartmentsOverview($this->filters());
    }

    #[Computed]
    public function detailsDepartment(): ?object
    {
        if ($this->detailsDepartmentId === null) {
            return null;
        }

        return $this->departmentsOverview->firstWhere('department_id', $this->detailsDepartmentId);
    }

    public function openDepartmentDetails(int $departmentId): void
    {
        abort_unless($this->departmentsOverview->contains('department_id', $departmentId), 404);

        $this->detailsDepartmentId = $departmentId;
        $this->dispatch('open-modal', id: 'performance-department-details-modal');
    }

    public function closeDepartmentDetails(): void
    {
        $this->detailsDepartmentId = null;
        $this->dispatch('close-modal', id: 'performance-department-details-modal');
    }

    public function openDepartmentScoreStatement(string $domain, int $companyId, int $departmentId): void
    {
        abort_unless($this->operationsViewMode === self::OPERATIONS_VIEW_DEPARTMENT, 404);
        abort_unless(in_array($domain, ['tasks', 'deficiencies'], true), 404);

        $this->closeDepartmentDetails();
        $this->openStatement($domain, 'score_overdue', $companyId, $departmentId);
    }

    private function isDepartmentOverviewScoreStatement(string $domain, string $type, mixed $departmentId): bool
    {
        return $this->operationsViewMode === self::OPERATIONS_VIEW_DEPARTMENT
            && $type === 'score_overdue'
            && in_array($domain, ['tasks', 'deficiencies'], true)
            && $departmentId !== null;
    }

    private function departmentOverviewIncludesCompany(?int $companyId, mixed $departmentId): bool
    {
        $department = $this->departmentsOverview->firstWhere('department_id', $departmentId);

        return $companyId !== null && ($department?->companies->contains('company_id', $companyId) ?? false);
    }

    public function selectCompany(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        $this->selectedDepartmentId = null;
        $this->departmentRankMode = self::DEPARTMENT_RANK_MODE_ALL;
        $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        $this->resetPage();
    }

    public function selectDepartment(mixed $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
        $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        $this->resetPage();
    }

    public function backToCompanies(): void
    {
        $this->selectedCompanyId = null;
        $this->selectedDepartmentId = null;
        $this->departmentRankMode = self::DEPARTMENT_RANK_MODE_ALL;
        $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        $this->resetPage();
    }

    public function backToDepartments(): void
    {
        $this->selectedDepartmentId = null;
        $this->employeeRankMode = self::EMPLOYEE_RANK_MODE_ALL;
        $this->resetPage();
    }

    public function openStatement(
        string $domain,
        string $type = 'all',
        ?int $companyId = null,
        mixed $departmentId = null,
        ?int $employeeId = null
    ): void {
        if ($this->isDepartmentOverviewScoreStatement($domain, $type, $departmentId)) {
            abort_unless($employeeId === null && $this->departmentOverviewIncludesCompany($companyId, $departmentId), 404);
        } elseif ($this->activeOverviewTab === 'summary' && $departmentId !== null) {
            abort_unless(
                is_numeric($departmentId) && $this->departmentsOverview->contains('department_id', (int) $departmentId),
                404
            );
        }

        if ($domain === 'tasks') {
            abort_unless(Gate::allows('viewAny', TaskAssignment::class), 403);
        }

        if (in_array($domain, ['services', 'deficiencies'], true)) {
            abort_unless(Gate::allows('viewAny', ServiceTransaction::class), 403);
        }

        if ($domain === 'compliance') {
            CompanyCompliancePerformanceService::authorizeStatement($type);
            if ($departmentId !== null && ! str_starts_with($type, 'iqama_') && ! str_starts_with($type, 'work_permit_') && ! str_starts_with($type, 'insurance_')) {
                abort(403);
            }
        }

        if ($domain === 'violations') {
            abort_unless($this->canViewViolationsOverview, 403);
        }

        if ($domain === 'attendance') {
            $this->authorizeAttendanceStatement($type);
        }

        if ($domain === 'leaves') {
            abort_unless(LeavesSummary::canAccess(), 403);
        }

        if ($domain === 'financial') {
            CompanyFinancialPerformanceService::authorizeStatement($type, $this->filters());
            $isSupervisor = CompanyDepartmentPerformanceService::isSupervisorViewer();
            if ($isSupervisor && ! isset(CompanyFinancialPerformanceService::PAYROLL_METRICS[$type])) {
                abort(403);
            }
        }

        if ($domain === 'tickets') {
            abort_unless($this->canViewTicketsOverview, 403);
        }

        $this->statementDomain = $domain;
        $this->statementType = $type;
        $this->statementCompanyId = $companyId;
        $this->statementDepartmentId = $departmentId;
        $this->statementEmployeeId = $employeeId;
        $this->showStatementModal = true;
        $this->resetPage('statementPage');
        $this->dispatch('open-modal', id: 'performance-statement-modal');
    }

    public function closeStatement(): void
    {
        $this->showStatementModal = false;
        $this->dispatch('close-modal', id: 'performance-statement-modal');
    }
}
