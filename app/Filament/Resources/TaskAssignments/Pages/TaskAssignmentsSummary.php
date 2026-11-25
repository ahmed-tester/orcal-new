<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\TaskAssignment;
use App\Services\TaskAssignmentSummaryService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAssignmentsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = TaskAssignmentResource::class;

    #[Locked]
    public ?int $detailsEmployeeId = null;

    /**
     * Selected employee IDs for batch operations (printing/exporting).
     *
     * @var array<int>
     */
    public array $selectedEmployees = [];

    public bool $selectAll = false;

    public function mount(): void
    {
        $this->dateFrom ??= now()->startOfMonth()->format('Y-m-d');
        $this->dateTo ??= now()->endOfMonth()->format('Y-m-d');
    }

    public function getView(): string
    {
        return 'filament.resources.task-assignments.pages.task-assignments-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Gate::allows('viewAny', TaskAssignment::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('tasks.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('tasks.summary.create_assignment'))
                ->visible(fn (): bool => TaskAssignmentResource::canCreate()),

            Action::make('print')
                ->label(__('tasks.summary.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => $this->buildPrintUrl())
                ->openUrlInNewTab(),

            Action::make('detailed')
                ->label(__('tasks.summary.detailed'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => TaskAssignmentResource::getUrl('detailed')),
        ];
    }

    /**
     * Build the print URL with the current active filters.
     */
    public function buildPrintUrl(?array $employeeIds = null): string
    {
        $selected = $employeeIds ?? $this->selectedEmployees;

        $params = array_filter([
            'date_from'    => $this->dateFrom,
            'date_to'      => $this->dateTo,
            'search'       => $this->search ?? null,
            'company_id'   => $this->companyId ?? null,
            'branch_id'    => $this->branchId ?? null,
            'quick_filter' => $this->quickFilter ?? null,
            'employee_ids' => ! empty($selected) ? array_values(array_map('intval', $selected)) : null,
        ]);

        return route('task-assignments.summary.print', $params);
    }

    /**
     * Build the statement print URL for a specific employee.
     */
    public function buildEmployeeStatementPrintUrl(int $employeeId): string
    {
        $params = array_filter([
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ]);

        return route('task-assignments.statement.print', array_merge(['employee' => $employeeId], $params));
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedEmployees = $this->summary->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $this->selectedEmployees = [];
        }
    }

    public function updatedSelectedEmployees(): void
    {
        $currentPageIds = $this->summary->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->selectAll = ! empty($currentPageIds)
            && count(array_diff($currentPageIds, array_map('intval', $this->selectedEmployees))) === 0;
    }

    public function handleFilterBarUpdate(): void
    {
        $this->resetPage();
        $this->detailsEmployeeId = null;
        $this->selectedEmployees = [];
        $this->selectAll = false;
        $this->dispatch('close-modal', id: 'task-assignment-details');
    }

    public function filters(): array
    {
        return $this->filterBarState([
            'only_with_items' => true,
        ]);
    }

    #[Computed]
    public function summary(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        return TaskAssignmentSummaryService::summaryQuery($this->filters())->paginate(15);
    }

    #[Computed]
    public function stats(): object
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        return TaskAssignmentSummaryService::stats($this->filters());
    }

    #[Computed]
    public function details(): ?array
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        return $this->detailsEmployeeId === null
            ? null
            : TaskAssignmentSummaryService::employeeDetails($this->detailsEmployeeId, $this->filters());
    }

    public function openDetails(int $employeeId): void
    {
        Gate::authorize('viewAny', TaskAssignment::class);
        $this->detailsEmployeeId = $employeeId;
        $this->dispatch('open-modal', id: 'task-assignment-details');
    }

    public function closeDetails(): void
    {
        $this->detailsEmployeeId = null;
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        $query = TaskAssignmentSummaryService::summaryQuery($this->filters());
        $columns = [
            'employee',
            'company',
            'branch',
            'department',
            'total_items',
            'completed_count',
            'in_progress_count',
            'pending_count',
            'postponed_count',
            'overdue_count',
            'completed_in_period',
            'due_in_period',
            'weighted_percentage',
            'completion_rate',
            'on_time_rate',
        ];

        return response()->streamDownload(function () use ($query, $columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map(fn (string $key) => __('tasks.summary.columns.'.$key), $columns));

            foreach ($query->lazy(200) as $row) {
                fputcsv($handle, [
                    $row->user?->name ?? $row->employee_number ?? '',
                    $row->company?->trade_name ?? '',
                    $row->branch?->name ?? '',
                    $row->department?->name ?? '',
                    (int) $row->total_items,
                    (int) $row->completed_count,
                    (int) $row->in_progress_count,
                    (int) $row->pending_count,
                    (int) $row->postponed_count,
                    (int) $row->overdue_count,
                    (int) $row->completed_in_period,
                    (int) $row->due_in_period,
                    number_format(TaskAssignmentSummaryService::weightedPercentage($row), 2, '.', ''),
                    $this->formatRate(TaskAssignmentSummaryService::completionRate($row)),
                    $this->formatRate(TaskAssignmentSummaryService::onTimeRate($row)),
                ]);
            }

            fclose($handle);
        }, 'task-assignments-summary-'.today()->toDateString().'.csv');
    }

    private function formatRate(?float $rate): string
    {
        return $rate === null ? '' : number_format($rate, 2, '.', '');
    }
}
