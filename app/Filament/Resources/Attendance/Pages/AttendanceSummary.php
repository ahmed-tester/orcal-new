<?php

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceSummaryService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithPagination;

class AttendanceSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = AttendanceResource::class;

    public ?string $month = null;

    public ?string $employmentStatus = 'all';

    public int $perPage = 10;

    public string $sortColumn = 'absent_days';

    public string $sortDirection = 'desc';

    public ?array $details = null;

    public ?int $detailsEmployeeId = null;

    public ?string $detailsMonth = null;

    protected $queryString = [
        'month' => ['except' => null],
        'employmentStatus' => ['except' => 'all'],
        'perPage' => ['except' => 10],
        'sortColumn' => ['except' => 'absent_days'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        $this->month = AttendanceSummaryService::monthStart($this->month)->format('Y-m');
    }

    public function getView(): string
    {
        return 'filament.resources.attendance.pages.attendance-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Attendance::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('attendance.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('attendance.summary.create_record'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => AttendanceResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', Attendance::class) ?? false),

            Action::make('detailedView')
                ->label(__('attendance.summary.detailed_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => AttendanceResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['month', 'employmentStatus'];
    }

    public function filterState(): array
    {
        return $this->filterBarState([
            'month' => $this->month ?? now()->format('Y-m'),
            'employment_status' => $this->employmentStatus ?: 'all',
        ]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar()
            || ($this->month !== null && $this->month !== now()->format('Y-m'))
            || ($this->employmentStatus !== 'all' && ! empty($this->employmentStatus));
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'companyId',
            'branchId',
            'dateFrom',
            'dateTo',
            'status',
            'employmentStatus',
        ]);
        $this->month = now()->format('Y-m');
        $this->resetPage();
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

    public function openDetails(int $employeeId, ?string $month = null): void
    {
        $this->detailsEmployeeId = $employeeId;
        $this->detailsMonth = AttendanceSummaryService::monthStart($month ?? $this->month)->format('Y-m');
        $this->details = AttendanceSummaryService::getEmployeeMonthDetails($employeeId, $this->detailsMonth);
    }

    public function setDetailsMonth(string $month): void
    {
        if (! $this->detailsEmployeeId) {
            return;
        }

        $this->detailsMonth = AttendanceSummaryService::monthStart($month)->format('Y-m');
        $this->details = AttendanceSummaryService::getEmployeeMonthDetails($this->detailsEmployeeId, $this->detailsMonth);
    }

    public function previousDetailsMonth(): void
    {
        if (! $this->detailsMonth) {
            $this->detailsMonth = now()->format('Y-m');
        }

        $newMonth = AttendanceSummaryService::monthStart($this->detailsMonth)->subMonth()->format('Y-m');
        $this->setDetailsMonth($newMonth);
    }

    public function nextDetailsMonth(): void
    {
        if (! $this->detailsMonth) {
            $this->detailsMonth = now()->format('Y-m');
        }

        $newMonth = AttendanceSummaryService::monthStart($this->detailsMonth)->addMonth()->format('Y-m');
        $this->setDetailsMonth($newMonth);
    }

    public function currentDetailsMonth(): void
    {
        $this->setDetailsMonth(now()->format('Y-m'));
    }

    public function closeDetails(): void
    {
        $this->details = null;
        $this->detailsEmployeeId = null;
        $this->detailsMonth = null;
    }

    public function getSummaryProperty(): LengthAwarePaginator
    {
        $query = AttendanceSummaryService::summaryQuery($this->filterState());

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
        return AttendanceSummaryService::getStats($this->filterState());
    }

    public function getMonthOptionsProperty(): array
    {
        return AttendanceSummaryService::monthOptions(24);
    }

    public function nameOf(Employee $row): string
    {
        return $row->user?->name ?? __('attendance.fields.employee')." #{$row->id}";
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

    public function exportCsv()
    {
        $rows = AttendanceSummaryService::exportRows($this->filterState());

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('attendance.summary.columns.number'),
                __('attendance.summary.columns.employee'),
                __('attendance.fields.record_number'),
                __('attendance.summary.columns.national_id'),
                __('attendance.summary.columns.company'),
                __('attendance.summary.columns.branch'),
                __('attendance.summary.columns.job_title'),
                __('attendance.summary.columns.recorded_days'),
                __('attendance.summary.columns.present_days'),
                __('attendance.summary.columns.late_days'),
                __('attendance.summary.columns.absent_days'),
                __('attendance.summary.columns.leave_days'),
                __('attendance.summary.columns.late_minutes'),
                __('attendance.summary.columns.total_hours'),
                __('attendance.summary.columns.last_date'),
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $this->nameOf($row),
                    $row->employee_number ?? '',
                    $this->nationalIdOf($row) ?? '',
                    $this->companyOf($row) ?? '',
                    $this->branchOf($row) ?? '',
                    $this->jobTitleOf($row) ?? '',
                    (int) $row->recorded_days,
                    (int) $row->present_days,
                    (int) $row->late_days,
                    (int) $row->absent_days,
                    (int) $row->leave_days,
                    (int) round((float) $row->late_minutes),
                    number_format((float) $row->total_hours, 2, '.', ''),
                    $row->last_date ? Carbon::parse($row->last_date)->format('Y-m-d') : '',
                ]);
            }

            fclose($handle);
        }, 'attendance_summary_'.($this->month ?? now()->format('Y_m')).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
