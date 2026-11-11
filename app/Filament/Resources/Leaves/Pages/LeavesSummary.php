<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Enums\LeaveStatus;
use App\Filament\Resources\Leaves\LeaveResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Employee;
use App\Models\Leave;
use App\Services\LeaveSummaryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithPagination;

class LeavesSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = LeaveResource::class;

    public ?int $year = null;

    public string $rejectionReason = '';

    public ?int $rejectingLeaveId = null;

    public ?string $employmentStatus = 'all';

    public ?string $leaveFilter = 'all';

    public int $perPage = 10;

    public string $sortColumn = 'employees.id';

    public string $sortDirection = 'desc';

    public ?array $details = null;

    public ?int $detailsEmployeeId = null;

    public ?int $detailsYear = null;

    protected $queryString = [
        'year' => ['alwaysShow' => true],
        'employmentStatus' => ['except' => 'all'],
        'leaveFilter' => ['except' => 'all'],
        'perPage' => ['except' => 10],
        'sortColumn' => ['except' => 'employees.id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        $this->year = request()->has('year')
            ? LeaveSummaryService::resolveYear(request()->query('year'))
            : ($this->year ?? (int) now()->year);
        $this->detailsYear = $this->year;
    }

    public function getView(): string
    {
        return 'filament.resources.leaves.pages.leaves-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Leave::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leaves.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('leaves.summary.create_record'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => LeaveResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', Leave::class) ?? false),

            Action::make('detailedView')
                ->label(__('leaves.summary.detailed_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => LeaveResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['year', 'employmentStatus', 'leaveFilter'];
    }

    public function filterState(): array
    {
        return $this->filterBarState([
            'year' => $this->year ?? 'all',
            'employment_status' => $this->employmentStatus ?: 'all',
            'leave_filter' => $this->leaveFilter ?: 'all',
        ]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar()
            || ($this->year && $this->year !== (int) now()->year)
            || ($this->employmentStatus && $this->employmentStatus !== 'all')
            || ($this->leaveFilter && $this->leaveFilter !== 'all');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'companyId', 'branchId', 'employmentStatus', 'leaveFilter']);
        $this->year = (int) now()->year;
        $this->updatedYear();
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->year = LeaveSummaryService::resolveYear($this->year);
        $this->detailsYear = $this->year;
        $this->refreshDetails();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openDetails(int $employeeId): void
    {
        $this->detailsEmployeeId = $employeeId;
        $this->detailsYear = $this->year;
        $this->refreshDetails();
    }

    public function refreshDetails(): void
    {
        if (! $this->detailsEmployeeId) {
            return;
        }

        $this->details = LeaveSummaryService::getEmployeeLeaveDetails($this->detailsEmployeeId, $this->detailsYear);
    }

    public function setDetailsYear(?int $year): void
    {
        $this->detailsYear = $year;
        $this->refreshDetails();
    }

    public function closeDetails(): void
    {
        $this->details = null;
        $this->detailsEmployeeId = null;
        $this->cancelRejection();
    }

    public function approveLeave(int $leaveId): void
    {
        LeaveSummaryService::changeStatus($leaveId, LeaveStatus::APPROVED);
        Notification::make()->title(__('leaves.notifications.approved_success'))->success()->send();
        $this->refreshDetails();
    }

    public function startRejection(int $leaveId): void
    {
        $leave = Leave::findOrFail($leaveId);
        abort_unless(LeaveSummaryService::canChangeStatus($leave), 403);
        $this->rejectingLeaveId = $leaveId;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function cancelRejection(): void
    {
        $this->rejectingLeaveId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function rejectLeave(int $leaveId): void
    {
        $this->validate(['rejectionReason' => ['required', 'string', 'max:1000']]);
        LeaveSummaryService::changeStatus($leaveId, LeaveStatus::REJECTED, $this->rejectionReason);
        Notification::make()->title(__('leaves.notifications.rejected_success'))->success()->send();
        $this->cancelRejection();
        $this->refreshDetails();
    }

    public function getSummaryProperty(): LengthAwarePaginator
    {
        $query = LeaveSummaryService::summaryQuery($this->filterState());

        if ($this->sortColumn === 'name') {
            $query->leftJoin('users', 'users.id', '=', 'employees.user_id')
                ->orderBy('users.name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    public function getStatsProperty(): array
    {
        return LeaveSummaryService::getStats($this->filterState());
    }

    public function getYearOptionsProperty(): array
    {
        return LeaveSummaryService::yearOptions(5);
    }

    public function nameOf(Employee $row): string
    {
        return $row->user?->name ?? __('leaves.fields.employee')." #{$row->id}";
    }

    public function nationalIdOf(Employee $row): ?string
    {
        $details = $row->details;

        return $details?->national_id ?: $details?->iqama_number;
    }

    public function companyOf(Employee $row): ?string
    {
        return $row->company?->trade_name;
    }

    public function branchOf(Employee $row): ?string
    {
        return $row->branch?->name;
    }

    public function jobTitleOf(Employee $row): ?string
    {
        return $row->jobTitle?->name;
    }

    public function balanceFor(Employee $row): array
    {
        return LeaveSummaryService::computeEmployeeBalance($row, (float) ($row->historical_annual_used_days ?? 0));
    }

    public function exportCsv()
    {
        $rows = LeaveSummaryService::exportRows($this->filterState());

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('leaves.summary.columns.number'),
                __('leaves.summary.columns.employee'),
                __('leaves.fields.record_number'),
                __('leaves.summary.columns.national_id'),
                __('leaves.summary.columns.company'),
                __('leaves.summary.columns.branch'),
                __('leaves.summary.columns.job_title'),
                __('leaves.summary.columns.annual_rate'),
                __('leaves.summary.columns.accrued_days'),
                __('leaves.summary.columns.historical_used_days'),
                __('leaves.summary.columns.available_days'),
                __('leaves.summary.columns.year_used_days'),
                __('leaves.summary.columns.pending_leaves'),
                __('leaves.summary.columns.status_today'),
            ]);

            $index = 1;
            foreach ($rows as $row) {
                $balance = $this->balanceFor($row);
                $statusToday = (int) ($row->is_on_leave_today ?? 0) === 1
                    ? __('leaves.summary.status.on_leave')
                    : __('leaves.summary.status.working');

                fputcsv($handle, [
                    $index++,
                    $this->nameOf($row),
                    $row->employee_number ?? '—',
                    $this->nationalIdOf($row) ?? '—',
                    $this->companyOf($row) ?? '—',
                    $this->branchOf($row) ?? '—',
                    $this->jobTitleOf($row) ?? '—',
                    $balance['annual_rate'],
                    $balance['accrued_days'],
                    $balance['used_days'],
                    $balance['available_days'],
                    (int) ($row->total_approved_days ?? 0),
                    (int) ($row->pending_leaves_count ?? 0),
                    $statusToday,
                ]);
            }

            fclose($handle);
        }, 'leaves-summary-'.($this->year ?? 'all').'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
