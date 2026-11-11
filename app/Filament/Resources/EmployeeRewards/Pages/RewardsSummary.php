<?php

namespace App\Filament\Resources\EmployeeRewards\Pages;

use App\Filament\Resources\EmployeeRewards\EmployeeRewardResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Services\RewardSummaryService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithPagination;

class RewardsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = EmployeeRewardResource::class;

    public ?int $year = null;

    public ?string $employmentStatus = 'all';

    public ?string $rewardFilter = 'all';

    public int $perPage = 10;

    public string $sortColumn = 'total_amount';

    public string $sortDirection = 'desc';

    public ?array $details = null;

    public ?int $detailsEmployeeId = null;

    public ?int $detailsYear = null;

    protected $queryString = [
        'year' => ['alwaysShow' => true],
        'employmentStatus' => ['except' => 'all'],
        'rewardFilter' => ['except' => 'all'],
        'perPage' => ['except' => 10],
        'sortColumn' => ['except' => 'total_amount'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        $this->year = request()->has('year')
            ? RewardSummaryService::resolveYear(request()->query('year'))
            : ($this->year ?? (int) now()->year);
        $this->detailsYear = $this->year;
    }

    public function getView(): string
    {
        return 'filament.resources.employee-rewards.pages.rewards-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', EmployeeReward::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('finance.employee_rewards.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('finance.employee_rewards.summary.create_record'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => EmployeeRewardResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', EmployeeReward::class) ?? false),

            Action::make('detailedView')
                ->label(__('finance.employee_rewards.summary.detailed_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => EmployeeRewardResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['year', 'employmentStatus', 'rewardFilter'];
    }

    public function filterState(): array
    {
        return $this->filterBarState([
            'year' => $this->year ?? 'all',
            'employment_status' => $this->employmentStatus ?: 'all',
            'reward_filter' => $this->rewardFilter ?: 'all',
        ]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar()
            || ($this->year && $this->year !== (int) now()->year)
            || ($this->employmentStatus && $this->employmentStatus !== 'all')
            || ($this->rewardFilter && $this->rewardFilter !== 'all');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'companyId', 'branchId', 'employmentStatus', 'rewardFilter']);
        $this->year = (int) now()->year;
        $this->updatedYear();
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->year = RewardSummaryService::resolveYear($this->year);
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

        $this->details = RewardSummaryService::getEmployeeRewardDetails($this->detailsEmployeeId, $this->detailsYear);
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
    }

    public function getSummaryProperty(): LengthAwarePaginator
    {
        $query = RewardSummaryService::summaryQuery($this->filterState());

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
        return RewardSummaryService::getStats($this->filterState());
    }

    public function getYearOptionsProperty(): array
    {
        return RewardSummaryService::yearOptions(5);
    }

    public function nameOf(Employee $row): string
    {
        return $row->user?->name ?? __('finance.employee_rewards.fields.employee')." #{$row->id}";
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
        $rows = RewardSummaryService::exportRows($this->filterState());

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('finance.employee_rewards.summary.columns.number'),
                __('finance.employee_rewards.summary.columns.employee'),
                __('finance.employee_rewards.summary.columns.employee_number'),
                __('finance.employee_rewards.summary.columns.national_id'),
                __('finance.employee_rewards.summary.columns.company'),
                __('finance.employee_rewards.summary.columns.branch'),
                __('finance.employee_rewards.summary.columns.job_title'),
                __('finance.employee_rewards.summary.columns.total_rewards'),
                __('finance.employee_rewards.summary.columns.total_hours'),
                __('finance.employee_rewards.summary.columns.total_amount'),
                __('finance.employee_rewards.summary.columns.paid_amount'),
                __('finance.employee_rewards.summary.columns.pending_count'),
                __('finance.employee_rewards.summary.columns.last_date'),
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
                    (int) $row->total_rewards,
                    number_format((float) $row->total_hours, 2, '.', ''),
                    number_format((float) $row->total_amount, 2, '.', ''),
                    number_format((float) $row->paid_amount, 2, '.', ''),
                    (int) $row->pending_count,
                    $row->last_reward_date ? Carbon::parse($row->last_reward_date)->format('Y-m-d') : '',
                ]);
            }

            fclose($handle);
        }, 'rewards-summary-'.($this->year ?? 'all').'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

