<?php

namespace App\Filament\Resources\FinancialApprovalRequests\Pages;

use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use App\Filament\Resources\FinancialApprovalRequests\Tables\FinancialApprovalRequestsTable;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Services\Payroll\PayrollApprovalScopeService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class ViewFinancialApprovalRequest extends ViewRecord
{
    use WithPagination;

    protected static string $resource = FinancialApprovalRequestResource::class;

    protected string $view = 'filament.financial-approvals.view-request';

    public string $search = '';

    public string $companyFilter = '';

    public int $perPage = 25;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless(
            auth()->user() !== null && PayrollApprovalScopeService::canViewRequest(auth()->user(), $this->getApprovalRecord()),
            403
        );

        $this->getApprovalRecord()->load([
            'companies',
            'submitter',
            'treasuryReviewer',
            'treasuryReviewerDepartment',
            'managerDepartment',
            'managerUser',
            'logs.actor',
        ]);
    }

    public function getTitle(): string
    {
        return __('financial.approval.modals.details_heading', ['number' => $this->getApprovalRecord()->request_number]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [25, 50, 100], true) ? $this->perPage : 25;
        $this->resetPage();
    }

    public function filterByCompany(?int $companyId): void
    {
        $value = $companyId ? (string) $companyId : '';
        $this->companyFilter = $this->companyFilter === $value ? '' : $value;
        $this->resetPage();
    }

    protected function getHeaderActions(): array
    {
        return [
            ...FinancialApprovalRequestsTable::workflowActions(),
            Action::make('back_to_list')
                ->label(__('financial.approval.actions.back_to_list'))
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(FinancialApprovalRequestResource::getUrl('index')),
        ];
    }

    #[Computed]
    public function companySummaries(): Collection
    {
        $request = $this->getApprovalRecord();

        return $request->items()
            ->selectRaw('company_id, COUNT(*) as employees_count, COALESCE(SUM(requested_amount), 0) as total_requested')
            ->with('company:id,trade_name')
            ->where('version', $request->version)
            ->groupBy('company_id')
            ->orderByDesc('total_requested')
            ->get();
    }

    #[Computed]
    public function paginatedItems(): LengthAwarePaginator
    {
        return $this->filteredItemsQuery()
            ->with('company:id,trade_name')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /**
     * @return array{count: int, net: float, prev_paid: float, requested: float}
     */
    #[Computed]
    public function filteredTotals(): array
    {
        $row = $this->filteredItemsQuery()
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(net_amount), 0) as net_sum, COALESCE(SUM(previously_paid_amount), 0) as prev_sum, COALESCE(SUM(requested_amount), 0) as req_sum')
            ->first();

        return [
            'count' => (int) ($row?->cnt ?? 0),
            'net' => (float) ($row?->net_sum ?? 0),
            'prev_paid' => (float) ($row?->prev_sum ?? 0),
            'requested' => (float) ($row?->req_sum ?? 0),
        ];
    }

    /**
     * @return array{count: int, requested: float}
     */
    #[Computed]
    public function overallTotals(): array
    {
        $request = $this->getApprovalRecord();
        $row = $request->items()
            ->where('version', $request->version)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(requested_amount), 0) as req_sum')
            ->first();

        return [
            'count' => (int) ($row?->cnt ?? 0),
            'requested' => (float) ($row?->req_sum ?? 0),
        ];
    }

    protected function filteredItemsQuery(): Builder
    {
        $request = $this->getApprovalRecord();
        $query = $request->items()->getQuery()->where('version', $request->version);

        $term = trim($this->search);
        if ($term !== '') {
            $query->where('employee_name_snapshot', 'like', '%'.$term.'%');
        }

        if ($this->companyFilter !== '') {
            $query->where('company_id', (int) $this->companyFilter);
        }

        return $query;
    }

    public function getApprovalRecord(): FinancialApprovalRequest
    {
        /** @var FinancialApprovalRequest $record */
        $record = $this->record;

        return $record;
    }
}
