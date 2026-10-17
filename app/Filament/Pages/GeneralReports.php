<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\Reports\GeneralReportService;
use App\Models\Branch;
use App\Models\Guarantor;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Enums\Width;
use Livewire\WithPagination;

class GeneralReports extends Page
{
    use WithPagination;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.general-reports';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('view_general_reports'));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.reports_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('reports.navigation.label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.navigation.title');
    }

    // Filter states
    public string $reportType = 'users';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $branchId = null;
    public ?int $guarantorId = null;
    public string $status = 'all';
    public ?string $search = null;
    public int $perPage = 10;
    
    // Sort states
    public string $sortColumn = 'id';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'reportType' => ['except' => 'users'],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'branchId' => ['except' => null],
        'guarantorId' => ['except' => null],
        'status' => ['except' => 'all'],
        'search' => ['except' => null],
        'sortColumn' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatedReportType()
    {
        // Reset specific filters when changing report type
        $this->status = 'all';
        $this->search = null;
        $this->guarantorId = null;
        $this->sortColumn = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function updatedGuarantorId($value)
    {
        $this->guarantorId = !empty($value) ? (int)$value : null;
        $this->resetPage();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['reportType', 'dateFrom', 'dateTo', 'branchId', 'guarantorId', 'status', 'search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['dateFrom', 'dateTo', 'branchId', 'guarantorId', 'status', 'search', 'sortColumn', 'sortDirection']);
        $this->sortColumn = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return !empty($this->dateFrom) ||
            !empty($this->dateTo) ||
            !empty($this->branchId) ||
            !empty($this->guarantorId) ||
            $this->status !== 'all' ||
            !empty($this->search);
    }

    public function exportCsv()
    {
        $data = $this->getRawReportData();
        $columns = $this->columns;
        $reportInfo = $this->currentReportInfo;

        return response()->streamDownload(function () use ($data, $columns, $reportInfo) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 Excel support
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Headers
            fputcsv($handle, array_column($columns, 'label'));
            
            // Data
            foreach ($data as $item) {
                $row = GeneralReportService::formatRow($this->reportType, $item, true);
                $csvRow = [];
                foreach ($columns as $col) {
                    $csvRow[] = $row[$col['key']] ?? '';
                }
                fputcsv($handle, $csvRow);
            }
            
            fclose($handle);
        }, $reportInfo['export_filename'] . '_' . date('Y_m_d') . '.csv');
    }

    // --- Computed Properties for the View ---

    public function getAvailableReportsProperty(): array
    {
        return GeneralReportService::getAvailableReports();
    }

    public function getCurrentReportInfoProperty(): array
    {
        return $this->availableReports[$this->reportType] ?? $this->availableReports['users'];
    }

    public function getBranchesProperty(): Collection
    {
        return Branch::where('status', 'active')->get();
    }

    public function getGuarantorsProperty(): Collection
    {
        $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds();
        $query = Guarantor::query()->where('status', 'active');
        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }
        return $query->orderBy('name')->get();
    }

    public function getSelectedGuarantorProperty(): ?Guarantor
    {
        if (!$this->guarantorId) {
            return null;
        }
        return Guarantor::find($this->guarantorId);
    }

    public function getStatusOptionsProperty(): array
    {
        $options = [
            'all' => __('reports.filters.status_all'),
            'active' => __('reports.status.active'),
            'inactive' => __('reports.status.inactive'),
        ];

        if ($this->reportType === 'attendance') {
            $options += [
                'present' => __('reports.status.present'),
                'absent' => __('reports.status.absent'),
                'late' => __('reports.status.late'),
            ];
        } elseif ($this->reportType === 'leaves') {
            $options += [
                'pending' => __('reports.status.pending'),
                'approved' => __('reports.status.approved'),
                'rejected' => __('reports.status.rejected'),
            ];
        } elseif (in_array($this->reportType, ['invoices', 'payroll_records'])) {
            $options += [
                'paid' => __('reports.status.paid'),
                'unpaid' => __('reports.status.unpaid'),
                'partially_paid' => __('reports.status.partially_paid'),
            ];
        } elseif ($this->reportType === 'installments') {
            $options += [
                'open' => __('reports.status.open'),
                'completed' => __('reports.status.completed'),
            ];
        } elseif ($this->reportType === 'employee_rewards') {
            $options += [
                'pending' => __('reports.status.pending'),
                'approved' => __('reports.status.approved'),
                'paid' => __('reports.status.paid'),
                'rejected' => __('reports.status.rejected'),
            ];
        } elseif ($this->reportType === 'offers') {
            $options += [
                'draft' => __('reports.status.draft'),
                'sent' => __('reports.status.sent'),
                'approved' => __('reports.status.approved'),
                'accepted' => __('reports.status.accepted'),
                'rejected' => __('reports.status.rejected'),
            ];
        } elseif ($this->reportType === 'treasury_transactions') {
            $options += [
                'incoming' => __('reports.status.incoming'),
                'outgoing' => __('reports.status.outgoing'),
            ];
        } elseif ($this->reportType === 'tickets') {
            $options += [
                'open' => __('reports.status.open'),
                'in_progress' => __('reports.status.in_progress'),
                'pending' => __('reports.status.pending'),
                'resolved' => __('reports.status.resolved'),
                'closed' => __('reports.status.closed'),
            ];
        } elseif ($this->reportType === 'loan_fund_loans') {
            $options += [
                'active' => __('reports.status.active'),
                'completed' => __('reports.status.completed'),
            ];
        } elseif ($this->reportType === 'rosca_fund_payments') {
            $options += [
                'paid' => __('reports.status.paid'),
                'partial' => __('reports.status.partial'),
                'unpaid' => __('reports.status.unpaid'),
            ];
        } elseif ($this->reportType === 'weekly_notes') {
            $options += [
                'draft' => __('reports.status.draft'),
                'published' => __('reports.status.published'),
                'archived' => __('reports.status.archived'),
            ];
        }

        return $options;
    }

    public function getStatsProperty(): array
    {
        return GeneralReportService::getStats(
            $this->reportType,
            $this->dateFrom,
            $this->dateTo,
            $this->branchId,
            $this->guarantorId
        );
    }

    public function getColumnsProperty(): array
    {
        return GeneralReportService::getColumns($this->reportType);
    }

    protected function getBaseQuery()
    {
        $query = GeneralReportService::getQuery(
            $this->reportType,
            $this->dateFrom,
            $this->dateTo,
            $this->branchId,
            $this->status,
            $this->search,
            $this->guarantorId
        );

        $sortableMap = GeneralReportService::getSortableColumns($this->reportType);
        $dbColumn = $sortableMap[$this->sortColumn] ?? 'id';

        return $query->orderBy($dbColumn, $this->sortDirection);
    }

    public function getReportDataProperty()
    {
        return $this->getBaseQuery()->paginate($this->perPage);
    }

    public function getRawReportDataProperty()
    {
        return $this->getBaseQuery()->get();
    }

    public function getRawReportData()
    {
        return $this->getRawReportDataProperty();
    }

    // Provide these methods for pagination view
    public function getPageProperty()
    {
        return request()->get('page', 1);
    }

    public function resetPage()
    {
        // Simple manual clear of page query param in Livewire component
        $this->page = 1;
    }
}
