<?php

namespace App\Filament\Concerns;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\TaskAssignmentItemReview;
use App\Services\EmployeePerformanceAccessService;
use App\Services\ServicePerformanceSummaryService;
use App\Services\Tasks\TaskAssignmentItemReviewService;
use App\Services\Tasks\TaskAssignmentSummaryService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared "employee performance" dashboard used by "My Tasks" and by the
 * employee performance page opened from the employee file.
 *
 * Task and service cards have separate eligibility rules. All task numbers
 * come from TaskAssignmentSummaryService and all service numbers come from
 * ServicePerformanceSummaryService, so every entry point matches. Every
 * computed property and export re-authorizes the employee on the server.
 */
trait HasTaskPerformanceDashboard
{
    public ?string $month = null;

    public ?string $cardFilter = null;

    public ?string $serviceCardFilter = null;

    public string $search = '';

    public string $statusQuickFilter = 'all';

    #[Locked]
    public ?int $activeReviewItemId = null;

    #[Locked]
    public ?int $expectedReviewId = null;

    public ?string $submissionNote = null;

    public ?string $approvalScore = null;

    public ?string $approvalNote = null;

    public string $returnCategory = TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT;

    public ?string $returnReason = null;

    public ?string $extensionNewDueDate = null;

    public ?string $extensionReason = null;

    public ?string $escalationReason = null;

    abstract protected function performanceEmployee(): ?Employee;

    /**
     * Entry-point specific authorization checks.
     */
    protected function authorizePerformance(Employee $employee): void {}

    public function mountHasTaskPerformanceDashboard(): void
    {
        $this->month = TaskAssignmentSummaryService::monthStart($this->month)->format('Y-m');
    }

    public function getView(): string
    {
        return 'filament.tasks.performance';
    }

    public function getTitle(): string|Htmlable
    {
        return __('tasks.performance.title');
    }

    public function updatedMonth(): void
    {
        $this->cardFilter = null;
        $this->serviceCardFilter = null;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function monthOptions(): array
    {
        return TaskAssignmentSummaryService::monthOptions();
    }

    #[Computed]
    public function performance(): ?array
    {
        $employee = $this->performanceEmployee();

        if (! $employee) {
            return null;
        }

        $this->authorizePerformance($employee);

        $user = auth()->user();
        $periodStart = TaskAssignmentSummaryService::monthStart($this->month);
        $periodEnd = $periodStart->copy()->endOfMonth();

        $taskVisible = $user !== null
            && EmployeePerformanceAccessService::canViewTaskData($user, $employee, $periodStart, $periodEnd);
        $serviceVisible = $user !== null
            && EmployeePerformanceAccessService::canViewServiceData($user, $employee, $periodStart, $periodEnd);

        abort_unless($taskVisible || $serviceVisible, 403);

        $filters = ['month' => $this->month];
        $row = $taskVisible
            ? TaskAssignmentSummaryService::employeeRow($employee->id, $filters)
            : null;

        if ($taskVisible) {
            abort_if($row === null, 404);
        }

        return [
            'employee' => $row ?? $employee,
            'items' => $taskVisible
                ? TaskAssignmentSummaryService::employeeItems($employee->id, $filters)
                : collect(),
            'filters' => $filters,
            'task_visible' => $taskVisible,
            'service_visible' => $serviceVisible,
            'weighted_percentage' => ($row && (float) ($row->weight_sum ?? 0) > 0)
                ? TaskAssignmentSummaryService::weightedPercentage($row)
                : null,
            'completion_rate' => $row ? TaskAssignmentSummaryService::completionRate($row) : null,
            'on_time_rate' => $row ? TaskAssignmentSummaryService::onTimeRate($row) : null,
            'is_closed_month' => $periodEnd->isBefore(today()),
        ];
    }

    /**
     * Operational services, deficiencies and their statement for the current
     * employee and month. The card filter selects the same query behind the
     * number that was clicked.
     */
    #[Computed]
    public function servicePerformance(): ?array
    {
        $data = $this->performance;

        if (! $data || ! $data['service_visible']) {
            return null;
        }

        $employee = $data['employee'];
        $target = $this->performanceEmployee();
        $filters = $data['filters'];

        $category = match ($this->serviceCardFilter) {
            'active' => ServicePerformanceSummaryService::CATEGORY_ACTIVE,
            'completed' => ServicePerformanceSummaryService::CATEGORY_COMPLETED,
            'overdue' => ServicePerformanceSummaryService::CATEGORY_OVERDUE,
            default => null,
        };

        $showDeficienciesOnly = $this->serviceCardFilter === 'assigned_deficiencies';
        $deficiencyFilters = $filters;

        return [
            'stats' => ServicePerformanceSummaryService::stats($employee->id, $filters, $target),
            'transactions' => $showDeficienciesOnly
                ? collect()
                : ServicePerformanceSummaryService::transactions($employee->id, $filters, $category, $target),
            'deficiencies' => ServicePerformanceSummaryService::assignedDeficiencies(
                $employee->id,
                $deficiencyFilters,
                $target
            ),
            'deficiencies_only' => $showDeficienciesOnly,
        ];
    }

    /**
     * Items filtered by the clicked task KPI card.
     *
     * @return Collection<int, TaskAssignmentItem>
     */
    #[Computed]
    public function performanceItems(): Collection
    {
        $items = collect($this->performance['items'] ?? []);
        $monthStart = TaskAssignmentSummaryService::monthStart($this->month);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $filtered = match ($this->cardFilter) {
            'completed' => $items->filter(fn (TaskAssignmentItem $item) => $item->status === TaskStatus::COMPLETED
                && $item->completed_at !== null
                && $item->completed_at->betweenIncluded($monthStart, $monthEnd)),
            'due' => $items->filter(fn (TaskAssignmentItem $item) => $item->due_date !== null
                && $item->due_date->betweenIncluded($monthStart, $monthEnd)),
            'overdue' => $items->filter(fn (TaskAssignmentItem $item) => ($item->status?->isEmployeeOpen() ?? false)
                && $item->due_date !== null
                && $item->due_date->lt(today())),
            'pending_review' => $items->filter(fn (TaskAssignmentItem $item) => $item->status === TaskStatus::PENDING_REVIEW),
            'postponed' => $items->filter(fn (TaskAssignmentItem $item) => $item->status === TaskStatus::POSTPONED),
            default => $items,
        };

        if ($this->statusQuickFilter === 'action_needed') {
            $filtered = $filtered->filter(fn (TaskAssignmentItem $item) => ($item->status?->isEmployeeOpen() ?? false)
                || $item->status === TaskStatus::RETURNED_FOR_REVISION);
        }

        $needle = mb_strtolower(trim($this->search));
        if ($needle !== '') {
            $filtered = $filtered->filter(function (TaskAssignmentItem $item) use ($needle): bool {
                $taskName = mb_strtolower((string) ($item->task?->name ?? ''));
                $assignmentNo = mb_strtolower((string) ($item->taskAssignment?->transaction_number ?? ''));

                return str_contains($taskName, $needle) || str_contains($assignmentNo, $needle);
            });
        }

        return $filtered->values();
    }

    public function clearTaskFilters(): void
    {
        $this->cardFilter = null;
        $this->statusQuickFilter = 'all';
        $this->search = '';
    }

    public function filterCard(?string $filter): void
    {
        $this->cardFilter = $filter === $this->cardFilter ? null : $filter;
    }

    public function filterServiceCard(?string $filter): void
    {
        $this->serviceCardFilter = $filter === $this->serviceCardFilter ? null : $filter;
    }

    public function clearServiceCardFilter(): void
    {
        $this->serviceCardFilter = null;
    }

    public function setMonth(string $month): void
    {
        $this->month = TaskAssignmentSummaryService::monthStart($month)->format('Y-m');
        $this->cardFilter = null;
        $this->serviceCardFilter = null;
    }

    public function previousMonth(): void
    {
        $this->setMonth(TaskAssignmentSummaryService::monthStart($this->month)->subMonth()->format('Y-m'));
    }

    public function nextMonth(): void
    {
        $this->setMonth(TaskAssignmentSummaryService::monthStart($this->month)->addMonth()->format('Y-m'));
    }

    public function currentMonth(): void
    {
        $this->setMonth(now()->format('Y-m'));
    }

    /**
     * Link to the assignment edit page only when the user may update it.
     */
    public function assignmentUrl(TaskAssignmentItem $item): ?string
    {
        $assignment = $item->taskAssignment;

        if (! $assignment || ! TaskAssignmentResource::canEdit($assignment)) {
            return null;
        }

        return TaskAssignmentResource::getUrl('edit', ['record' => $assignment]);
    }

    public function exportCsv(): StreamedResponse
    {
        $employee = $this->performanceEmployee();
        abort_if($employee === null, 403);
        $this->authorizePerformance($employee);

        $data = $this->performance;
        abort_unless($data['task_visible'] ?? false, 403);

        $items = $this->performanceItems;

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                __('tasks.summary.columns.task'),
                __('tasks.fields.transaction_number'),
                __('tasks.fields.item_status'),
                __('tasks.fields.assignment_date'),
                __('tasks.fields.due_date'),
                __('tasks.fields.completed_at'),
                __('tasks.fields.weight'),
                __('tasks.fields.score'),
                __('tasks.fields.score_percentage'),
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->task?->name ?? '',
                    $item->taskAssignment?->transaction_number ?? '',
                    $item->status?->getLabel() ?? '',
                    $item->taskAssignment?->assignment_date?->format('Y-m-d') ?? '',
                    $item->due_date?->format('Y-m-d') ?? '',
                    $item->completed_at?->format('Y-m-d') ?? '',
                    number_format((float) $item->weight, 2, '.', ''),
                    number_format((float) $item->score, 2, '.', ''),
                    number_format((float) $item->score_percentage, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'my-tasks-'.($this->month ?? now()->format('Y-m')).'.csv');
    }

    public function canViewAssignments(): bool
    {
        return Gate::allows('viewAny', TaskAssignment::class);
    }

    public function canSubmitItem(TaskAssignmentItem $item): bool
    {
        $user = auth()->user();

        return $user !== null && TaskAssignmentItemReviewService::canSubmit($user, $item);
    }

    public function canEscalateItem(TaskAssignmentItem $item): bool
    {
        $user = auth()->user();

        return $user !== null && TaskAssignmentItemReviewService::canEscalate($user, $item);
    }

    public function canReviewItem(TaskAssignmentItem $item): bool
    {
        $user = auth()->user();

        return $user !== null && TaskAssignmentItemReviewService::canReview($user, $item);
    }

    public function canExtendItemDueDate(TaskAssignmentItem $item): bool
    {
        $user = auth()->user();

        return $user !== null && TaskAssignmentItemReviewService::canExtendDueDate($user, $item);
    }

    #[Computed]
    public function activeReviewItem(): ?TaskAssignmentItem
    {
        if (! $this->activeReviewItemId) {
            return null;
        }

        return TaskAssignmentItem::query()
            ->with(['task', 'taskAssignment.employee', 'reviews.submitter', 'reviews.decider'])
            ->find($this->activeReviewItemId);
    }

    public function openSubmitModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->submissionNote = null;
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'task-item-submit-modal');
    }

    public function submitItemForReview(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $item = $this->resolveAuthorizedPerformanceItem((int) $this->activeReviewItemId);

        try {
            TaskAssignmentItemReviewService::submitForReview($user, $item, $this->submissionNote);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->resetReviewModalState();
        $this->dispatch('close-modal', id: 'task-item-submit-modal');
        Notification::make()->title(__('tasks.review.notifications.saved_submitted'))->success()->send();
    }

    public function openApproveModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->expectedReviewId = TaskAssignmentItemReviewService::latestPendingReviewId($item);
        $this->approvalScore = number_format((float) $item->weight, 2, '.', '');
        $this->approvalNote = null;
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'task-item-approve-modal');
    }

    public function approveItemReview(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $item = $this->resolveAuthorizedPerformanceItem((int) $this->activeReviewItemId);

        if (! is_numeric($this->approvalScore)) {
            $this->addError('approvalScore', __('tasks.review.errors.invalid_score'));

            return;
        }

        if ($this->expectedReviewId === null) {
            $this->addError('score', __('tasks.review.errors.no_pending_submission'));

            return;
        }

        try {
            TaskAssignmentItemReviewService::approve($user, $item, (float) $this->approvalScore, $this->approvalNote, $this->expectedReviewId);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->resetReviewModalState();
        $this->dispatch('close-modal', id: 'task-item-approve-modal');
        Notification::make()->title(__('tasks.review.notifications.saved_approved'))->success()->send();
    }

    public function openReturnModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->expectedReviewId = TaskAssignmentItemReviewService::latestPendingReviewId($item);
        $this->returnCategory = TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT;
        $this->returnReason = null;
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'task-item-return-modal');
    }

    public function returnItemForRevision(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $item = $this->resolveAuthorizedPerformanceItem((int) $this->activeReviewItemId);

        if ($this->expectedReviewId === null) {
            $this->addError('reason', __('tasks.review.errors.no_pending_submission'));

            return;
        }

        try {
            TaskAssignmentItemReviewService::returnForRevision(
                $user,
                $item,
                (string) ($this->returnReason ?? ''),
                $this->returnCategory,
                $this->expectedReviewId,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->resetReviewModalState();
        $this->dispatch('close-modal', id: 'task-item-return-modal');
        Notification::make()->title(__('tasks.review.notifications.saved_returned'))->warning()->send();
    }

    public function openExtendModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->extensionNewDueDate = $item->due_date?->toDateString() ?? now()->toDateString();
        $this->extensionReason = null;
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'task-item-extend-modal');
    }

    public function extendItemDueDate(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $item = $this->resolveAuthorizedPerformanceItem((int) $this->activeReviewItemId);

        try {
            TaskAssignmentItemReviewService::extendDueDate(
                $user,
                $item,
                (string) ($this->extensionNewDueDate ?? ''),
                (string) ($this->extensionReason ?? ''),
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->resetReviewModalState();
        $this->dispatch('close-modal', id: 'task-item-extend-modal');
        Notification::make()->title(__('tasks.review.notifications.saved_extended'))->info()->send();
    }

    public function openHistoryModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->dispatch('open-modal', id: 'task-item-history-modal');
    }

    public function openEscalateModal(int $itemId): void
    {
        $item = $this->resolveAuthorizedPerformanceItem($itemId);
        $this->activeReviewItemId = $item->getKey();
        $this->escalationReason = null;
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'task-item-escalate-modal');
    }

    public function escalateItem(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $item = $this->resolveAuthorizedPerformanceItem((int) $this->activeReviewItemId);

        try {
            TaskAssignmentItemReviewService::escalate($user, $item, (string) ($this->escalationReason ?? ''));
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->resetReviewModalState();
        $this->dispatch('close-modal', id: 'task-item-escalate-modal');
        Notification::make()->title(__('tasks.review.notifications.saved_escalated'))->warning()->send();
    }

    private function resolveAuthorizedPerformanceItem(int $itemId): TaskAssignmentItem
    {
        $employee = $this->performanceEmployee();
        abort_if($employee === null, 403);
        $this->authorizePerformance($employee);

        $item = TaskAssignmentItem::query()
            ->with(['task', 'taskAssignment.employee'])
            ->findOrFail($itemId);

        abort_unless((int) ($item->taskAssignment?->employee_id ?? 0) === (int) $employee->getKey(), 403);

        return $item;
    }

    private function resetReviewModalState(): void
    {
        $this->activeReviewItemId = null;
        $this->expectedReviewId = null;
        $this->submissionNote = null;
        $this->approvalScore = null;
        $this->approvalNote = null;
        $this->returnCategory = TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT;
        $this->returnReason = null;
        $this->extensionNewDueDate = null;
        $this->extensionReason = null;
        $this->escalationReason = null;
        unset($this->performance, $this->performanceItems, $this->activeReviewItem);
    }
}
