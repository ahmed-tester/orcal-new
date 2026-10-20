<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\PayrollCalculationService;
use App\Services\PayrollSheetService;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListPayrollRecords extends ListRecords
{
    protected static string $resource = PayrollRecordResource::class;

    protected string $view = 'filament.resources.payroll-records.pages.list-payroll-records';

    // Active view tab: 'sheet' (Interactive Payroll & Collection Sheet) or 'records' (History Table)
    public string $pageViewTab = 'sheet';

    // Filter Mode: 'single_company' | 'multiple_companies' | 'all_companies' | 'by_guarantor'
    public string $filterMode = 'single_company';

    // Mode 1: Single Company
    public int|string|null $selectedCompanyId = null;

    // Mode 2: Multiple Companies
    public array $selectedCompanyIds = [];

    // Mode 4: By Guarantor / Sponsor (Guarantor Table)
    public int|string|null $selectedGuarantorId = null;
    public ?string $selectedGuarantor = null;

    // Operation Type: 'payroll' (رواتب) or 'collection' (تحصيل)
    public string $operationType = 'payroll';
    public string $printReportType = 'payroll';

    // Employee Scope: 'all' (جميع موظفي المنشأة) or 'specific' (موظف معين)
    public string $employeeScope = 'all';
    public int|string|null $selectedEmployeeId = null;

    // Date & Search Filters
    public string $payrollMonth;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $searchQuery = '';

    // Sheet Data
    public array $employeeRows = [];
    public array $checkedEmployees = []; // [emp_id => bool] - Checkbox (تم التحصيل / الصرف)
    public array $notes = [];            // [emp_id => string]
    public array $deletedEmployeeIds = []; // [emp_id] - Employees deleted from this sheet

    // Summary Statistics
    public int $totalEmployees = 0;
    public float $totalBasicAmount = 0.0;
    public float $totalAdditionalAmount = 0.0;
    public float $totalDeductionAmount = 0.0;
    public float $totalNetAmount = 0.0;
    public float $totalPaidAmount = 0.0;
    public float $totalPendingAmount = 0.0;

    // Pagination for Custom Sheet
    public int $sheetPage = 1;
    public int|string $sheetPerPage = 25;
    public int $totalSheetPages = 1;
    public array $paginatedRows = [];

    public function mount(): void
    {
        parent::mount();

        $this->payrollMonth = now()->format('Y-m');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();

        $requestedOp = request()->query('op');
        if (in_array($requestedOp, ['payroll', 'collection'])) {
            $this->operationType = $requestedOp;
        }

        $requestedCompanyId = (int) request()->query('company_id');
        $user = auth()->user();
        $authorizedCompanyIds = $user ? \App\Services\CompanyFormService::getAuthorizedCompanyIds($user) : [];

        if ($requestedCompanyId > 0 && (empty($authorizedCompanyIds) || in_array($requestedCompanyId, $authorizedCompanyIds))) {
            $this->selectedCompanyId = $requestedCompanyId;
            $this->selectedCompanyIds = [$requestedCompanyId];
        } else {
            $firstCompanyId = !empty($authorizedCompanyIds) ? reset($authorizedCompanyIds) : Company::value('id');
            if ($firstCompanyId) {
                $this->selectedCompanyId = $firstCompanyId;
                $this->selectedCompanyIds = [$firstCompanyId];
            }
        }

        $firstGuarantor = \App\Models\Guarantor::where('status', 'active')->first();
        if ($firstGuarantor) {
            $this->selectedGuarantorId = $firstGuarantor->id;
            $this->selectedGuarantor = $firstGuarantor->name;
        }

        $this->form->fill([
            'selectedCompanyIds' => array_map('intval', (array) $this->selectedCompanyIds),
        ]);

        $this->loadSheet();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedCompanyIds')
                    ->hiddenLabel()
                    ->placeholder(__('finance.payroll_records.sheet.search_companies_placeholder'))
                    ->options(function () {
                        $user = auth()->user();
                        $query = Company::orderBy('trade_name');
                        if ($user && !$user->isSuperAdmin()) {
                            $authorized = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                            $query->whereIn('id', !empty($authorized) ? $authorized : [0]);
                        }
                        return $query->pluck('trade_name', 'id');
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedCompanyIds = array_map('intval', (array) $state);
                        $this->loadSheet();
                    }),
            ]);
    }

    public function updatedFilterMode(): void
    {
        $this->deletedEmployeeIds = [];
        $this->sheetPage = 1;
        if ($this->filterMode === 'all_companies' && ! (auth()->user()?->canAccessAllCompanies() ?? false)) {
            $this->filterMode = 'single_company';
        }
        if ($this->filterMode === 'by_guarantor' && empty($this->selectedGuarantorId)) {
            $firstGuar = $this->guarantors->first();
            $this->selectedGuarantorId = $firstGuar?->id;
            $this->selectedGuarantor = $firstGuar?->name;
        }
        $this->loadSheet();
    }

    public function updatedSheetPerPage(): void
    {
        $this->sheetPage = 1;
        $this->recalculatePagination();
    }

    public function setSheetPage(int $pageNumber): void
    {
        $this->sheetPage = max(1, min($pageNumber, $this->totalSheetPages));
        $this->recalculatePagination();
    }

    public function nextSheetPage(): void
    {
        if ($this->sheetPage < $this->totalSheetPages) {
            $this->sheetPage++;
            $this->recalculatePagination();
        }
    }

    public function previousSheetPage(): void
    {
        if ($this->sheetPage > 1) {
            $this->sheetPage--;
            $this->recalculatePagination();
        }
    }

    /**
     * Recalculate pagination: totalSheetPages and paginatedRows.
     * Called after loadSheet() or whenever sheetPage/sheetPerPage changes.
     */
    public function recalculatePagination(): void
    {
        // Calculate total pages
        if ($this->sheetPerPage === 'all' || (int) $this->sheetPerPage <= 0) {
            $this->totalSheetPages = 1;
        } else {
            $count = count($this->employeeRows);
            $this->totalSheetPages = $count > 0 ? (int) ceil($count / (int) $this->sheetPerPage) : 1;
        }

        // Clamp current page
        if ($this->sheetPage > $this->totalSheetPages) {
            $this->sheetPage = $this->totalSheetPages;
        }
        if ($this->sheetPage < 1) {
            $this->sheetPage = 1;
        }

        // Calculate paginated rows
        if ($this->sheetPerPage === 'all' || (int) $this->sheetPerPage <= 0) {
            $this->paginatedRows = $this->employeeRows;
        } else {
            $offset = ($this->sheetPage - 1) * (int) $this->sheetPerPage;
            $this->paginatedRows = array_slice($this->employeeRows, $offset, (int) $this->sheetPerPage);
        }
    }

    public function updatedSelectedCompanyId(): void
    {
        $this->deletedEmployeeIds = [];
        if ($this->selectedCompanyId !== null && $this->selectedCompanyId !== '') {
            $this->selectedCompanyId = (int) $this->selectedCompanyId;
        } else {
            $this->selectedCompanyId = null;
        }
        $this->selectedEmployeeId = null;
        $this->loadSheet();
    }

    public function updatedSelectedCompanyIds(): void
    {
        $this->deletedEmployeeIds = [];
        $this->loadSheet();
    }

    public function selectAllCompanies(): void
    {
        $this->selectedCompanyIds = $this->companies->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $this->form->fill(['selectedCompanyIds' => $this->selectedCompanyIds]);
        $this->loadSheet();
    }

    public function deselectAllCompanies(): void
    {
        $this->selectedCompanyIds = [];
        $this->form->fill(['selectedCompanyIds' => []]);
        $this->loadSheet();
    }

    public function removeSelectedCompany(int $companyId): void
    {
        $this->selectedCompanyIds = array_values(array_filter(
            array_map('intval', $this->selectedCompanyIds),
            fn ($id) => $id !== $companyId
        ));
        $this->form->fill(['selectedCompanyIds' => $this->selectedCompanyIds]);
        $this->loadSheet();
    }

    public function updatedSelectedGuarantorId(): void
    {
        if ($this->selectedGuarantorId !== null && $this->selectedGuarantorId !== '') {
            $this->selectedGuarantorId = (int) $this->selectedGuarantorId;
            $this->selectedGuarantor = \App\Models\Guarantor::find($this->selectedGuarantorId)?->name;
        } else {
            $this->selectedGuarantorId = null;
            $this->selectedGuarantor = null;
        }
        $this->loadSheet();
    }

    public function updatedSelectedGuarantor(): void
    {
        $this->loadSheet();
    }

    public function clearGuarantor(): void
    {
        $this->selectedGuarantorId = null;
        $this->selectedGuarantor = null;
        $this->loadSheet();
    }

    public function setOperationType(string $type): void
    {
        $this->operationType = $type;
        $this->checkedEmployees = [];
        $this->notes = [];
        $this->loadSheet();
    }

    public function updatedOperationType(): void
    {
        $this->checkedEmployees = [];
        $this->notes = [];
        $this->loadSheet();
    }

    public function updatedEmployeeScope(): void
    {
        if ($this->employeeScope === 'all') {
            $this->selectedEmployeeId = null;
        }
        $this->loadSheet();
    }

    public function updatedSelectedEmployeeId(): void
    {
        $this->loadSheet();
    }

    public function updatedPayrollMonth(): void
    {
        $this->deletedEmployeeIds = [];
        $this->sheetPage = 1;
        if ($this->payrollMonth) {
            try {
                $c = Carbon::parse($this->payrollMonth);
                $this->dateFrom = $c->copy()->startOfMonth()->toDateString();
                $this->dateTo = $c->copy()->endOfMonth()->toDateString();
            } catch (\Exception) {}
        }
        $this->loadSheet();
    }

    public function updatedSearchQuery(): void
    {
        $this->sheetPage = 1;
        $this->loadSheet();
    }

    /* switchTab method — commented out
    public function switchTab(string $tab): void
    {
        $this->pageViewTab = $tab;
    }
    */

    public function getCompaniesProperty(): Collection
    {
        $user = auth()->user();
        $query = Company::orderBy('trade_name');
        if ($user && !$user->isSuperAdmin()) {
            $authorized = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
            $query->whereIn('id', !empty($authorized) ? $authorized : [0]);
        }
        return $query->get(['id', 'trade_name']);
    }

    public function getGuarantorsProperty(): Collection
    {
        return \App\Models\Guarantor::where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'national_id', 'phone', 'type', 'company_id']);
    }

    public function getCompanyEmployeesProperty(): Collection
    {
        $query = Employee::query()->where('status', 'active')->with('user');

        if ($this->filterMode === 'single_company' && $this->selectedCompanyId) {
            $query->where('company_id', $this->selectedCompanyId);
        } elseif ($this->filterMode === 'multiple_companies' && !empty($this->selectedCompanyIds)) {
            $query->whereIn('company_id', $this->selectedCompanyIds);
        }

        return $query->get();
    }

    public function loadSheet(): void
    {
        $result = PayrollSheetService::buildSheetData(
            filters: [
                'filterMode'          => $this->filterMode,
                'selectedCompanyId'   => $this->selectedCompanyId,
                'selectedCompanyIds'  => $this->selectedCompanyIds,
                'selectedGuarantorId' => $this->selectedGuarantorId,
                'selectedGuarantor'   => $this->selectedGuarantor,
                'operationType'       => $this->operationType,
                'employeeScope'       => $this->employeeScope,
                'selectedEmployeeId'  => $this->selectedEmployeeId,
                'payrollMonth'        => $this->payrollMonth,
                'searchQuery'         => $this->searchQuery,
            ],
            deletedEmployeeIds: $this->deletedEmployeeIds,
            checkedEmployees: $this->checkedEmployees,
            notes: $this->notes,
        );

        $this->employeeRows          = $result['rows'];
        $this->checkedEmployees      = $result['checkedEmployees'];
        $this->notes                 = $result['notes'];
        $this->totalEmployees        = $result['totals']['totalEmployees'];
        $this->totalBasicAmount      = $result['totals']['totalBasicAmount'];
        $this->totalAdditionalAmount = $result['totals']['totalAdditionalAmount'];
        $this->totalDeductionAmount  = $result['totals']['totalDeductionAmount'];
        $this->totalNetAmount        = $result['totals']['totalNetAmount'];
        $this->totalPaidAmount       = $result['totals']['totalPaidAmount'];
        $this->totalPendingAmount    = $result['totals']['totalPendingAmount'];

        $this->recalculatePagination();
    }

    public function setOperationTypeAndPrint(string $type): void
    {
        $this->operationType = in_array($type, ['payroll', 'collection']) ? $type : 'payroll';
        $this->printReportType = $this->operationType;
        $this->sheetPerPage = 'all';
        $this->loadSheet();
        $this->js('window.print()');
    }

    /*
    public function toggleEmployeeCheck(int $employeeId): void
    {
        $this->checkedEmployees[$employeeId] = !($this->checkedEmployees[$employeeId] ?? false);

        $totals = PayrollSheetService::applyEmployeeCheck($this->employeeRows, $this->checkedEmployees, $employeeId);
        $this->totalPaidAmount = $totals['totalPaidAmount'];
        $this->totalPendingAmount = $totals['totalPendingAmount'];

        $this->recalculatePagination();
    }

    public function toggleCheckAll(bool $checkState): void
    {
        foreach ($this->employeeRows as $row) {
            $this->checkedEmployees[$row['employee_id']] = $checkState;
        }

        $totals = PayrollSheetService::applyCheckAll($this->employeeRows, $checkState);
        $this->totalPaidAmount = $totals['totalPaidAmount'];
        $this->totalPendingAmount = $totals['totalPendingAmount'];

        $this->recalculatePagination();
    }
    */

    public function savePayrollSheet(): void
    {
        if (empty($this->employeeRows)) {
            Notification::make()
                ->title(__('finance.payroll_records.sheet.empty_save_warning'))
                ->warning()
                ->send();
            return;
        }

        $savedCount = PayrollSheetService::saveSheet(
            employeeRows: $this->employeeRows,
            checkedEmployees: $this->checkedEmployees,
            notes: $this->notes,
            operationType: $this->operationType,
            payrollMonth: $this->payrollMonth,
        );

        $opLabel = $this->operationType === 'payroll'
            ? __('finance.payroll_records.sheet.op_payroll')
            : __('finance.payroll_records.sheet.op_collection');

        Notification::make()
            ->title(__('finance.payroll_records.sheet.save_success_title', ['op' => $opLabel]))
            ->body(__('finance.payroll_records.sheet.save_success_body', ['count' => $savedCount, 'month' => $this->payrollMonth]))
            ->success()
            ->send();

        $this->loadSheet();
    }

    public function openEmployeeRecord(int $employeeId)
    {
        $record = PayrollSheetService::findOrCreateEmployeeRecord(
            employeeId: $employeeId,
            operationType: $this->operationType,
            payrollMonth: $this->payrollMonth,
            note: $this->notes[$employeeId] ?? null
        );

        if (!$record) {
            return null;
        }

        return redirect()->to(PayrollRecordResource::getUrl('edit', ['record' => $record]));
    }

    public function deleteRecord(int $employeeId, ?int $recordId = null): void
    {
        PayrollSheetService::deleteEmployeeRecord(
            employeeId: $employeeId,
            operationType: $this->operationType,
            payrollMonth: $this->payrollMonth,
            recordId: $recordId
        );

        if (!in_array($employeeId, $this->deletedEmployeeIds)) {
            $this->deletedEmployeeIds[] = $employeeId;
        }

        unset($this->checkedEmployees[$employeeId]);
        unset($this->notes[$employeeId]);

        Notification::make()
            ->title(__('finance.payroll_records.sheet.delete_success'))
            ->success()
            ->send();

        $this->loadSheet();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('finance.payroll_records.actions.create_single')),
        ];
    }
}
