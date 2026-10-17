<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\PayrollCalculationService;
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

    // Summary Statistics
    public int $totalEmployees = 0;
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
        if ($requestedCompanyId > 0 && Company::where('id', $requestedCompanyId)->exists()) {
            $this->selectedCompanyId = $requestedCompanyId;
            $this->selectedCompanyIds = [$requestedCompanyId];
        } else {
            $firstCompany = Company::first();
            if ($firstCompany) {
                $this->selectedCompanyId = $firstCompany->id;
                $this->selectedCompanyIds = [$firstCompany->id];
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
                    ->placeholder('ابحث واختر المؤسسات...')
                    ->options(fn () => Company::orderBy('trade_name')->pluck('trade_name', 'id'))
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
        $this->sheetPage = 1;
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

    public function switchTab(string $tab): void
    {
        $this->pageViewTab = $tab;
    }

    public function getCompaniesProperty(): Collection
    {
        return Company::orderBy('trade_name')->get(['id', 'trade_name']);
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
        $query = Employee::query()
            ->where('status', 'active')
            ->whereHas('company')
            ->with(['user', 'company', 'branch', 'details.guarantor']);

        // 1. Filter by Mode
        if ($this->filterMode === 'single_company') {
            if ($this->selectedCompanyId) {
                $query->where('company_id', $this->selectedCompanyId);
            }
            if ($this->employeeScope === 'specific' && $this->selectedEmployeeId) {
                $query->where('id', $this->selectedEmployeeId);
            }
        } elseif ($this->filterMode === 'multiple_companies') {
            if (!empty($this->selectedCompanyIds)) {
                $query->whereIn('company_id', $this->selectedCompanyIds);
            }
        } elseif ($this->filterMode === 'all_companies') {
            // All companies
        } elseif ($this->filterMode === 'by_guarantor') {
            if ($this->selectedGuarantorId) {
                $guarId = (int) $this->selectedGuarantorId;
                $guarObj = \App\Models\Guarantor::find($guarId);
                $guarName = $guarObj?->name;

                $query->whereHas('details', function ($q) use ($guarId, $guarName) {
                    $q->where('guarantor_id', $guarId);
                    if ($guarName) {
                        $q->orWhere('sponsor_name', $guarName);
                    }
                });
            } elseif ($this->selectedGuarantor) {
                $query->whereHas('details', fn ($q) => $q->where('sponsor_name', $this->selectedGuarantor));
            } else {
                $query->whereHas('details', fn ($q) => $q->whereNotNull('guarantor_id')->orWhereNotNull('sponsor_name'));
            }
        }

        // 2. Search Query
        if (!empty($this->searchQuery)) {
            $search = trim($this->searchQuery);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhereHas('company', fn ($c) => $c->where('trade_name', 'like', "%{$search}%"))
                  ->orWhereHas('details', fn ($d) => $d->where('sponsor_name', 'like', "%{$search}%"));
            });
        }

        $employees = $query->get();

        $monthDate = Carbon::parse($this->payrollMonth ?: now());
        $year = $monthDate->year;
        $month = $monthDate->month;

        $rows = [];
        $totalNet = 0.0;
        $totalPaid = 0.0;
        $totalPending = 0.0;

        foreach ($employees as $emp) {
            // Safely skip orphaned employees without existing company
            if (empty($emp->company_id) || empty($emp->company)) {
                continue;
            }

            // Check for existing saved record in database
            $existing = PayrollRecord::where('employee_id', $emp->id)
                ->where('operation_type', $this->operationType)
                ->whereYear('payroll_month', $year)
                ->whereMonth('payroll_month', $month)
                ->first();

            // في وضع التحصيل: لا نظهر إلا من له سجل تحصيل محفوظ بالفعل (أو تم تحديده بالاسم لإنشاء تحصيل له)
            if ($this->operationType === 'collection') {
                if (!$existing && !($this->employeeScope === 'specific' && $this->selectedEmployeeId == $emp->id)) {
                    continue;
                }
            }

            // في وضع الرواتب: إذا كان الموظف مسجل له تحصيل لهذا الشهر وليس له راتب، يُستبعد من كشف الرواتب
            if ($this->operationType === 'payroll' && !$existing) {
                $hasCollection = PayrollRecord::where('employee_id', $emp->id)
                    ->where('operation_type', 'collection')
                    ->whereYear('payroll_month', $year)
                    ->whereMonth('payroll_month', $month)
                    ->exists();

                if ($hasCollection) {
                    continue;
                }
            }

            if ($existing) {
                $basic = (float) $existing->basic_salary;
                $additional = (float) $existing->additional_amount;
                $deductions = (float) $existing->deduction_amount;
                $net = (float) $existing->net_amount;
                $isPaid = ($existing->payment_status === PaymentStatus::PAID || (float) $existing->remaining_amount <= 0);
                $recordNumber = $existing->record_number;
                $this->notes[$emp->id] = $existing->notes ?? '';
            } else {
                $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $this->payrollMonth);
                $basic = (float) $emp->basic_salary;
                $additional = (float) ($financials['additional_amount'] ?? 0);
                $deductions = (float) ($financials['deduction_amount'] ?? 0);
                $net = max(0.0, (float) ($financials['net_amount'] ?? ($basic + $additional - $deductions)));
                $isPaid = $this->checkedEmployees[$emp->id] ?? false;
                $recordNumber = null;
                if (!isset($this->notes[$emp->id])) {
                    $this->notes[$emp->id] = '';
                }
            }

            // Keep checkbox state synchronized
            if (!isset($this->checkedEmployees[$emp->id])) {
                $this->checkedEmployees[$emp->id] = $isPaid;
            } else {
                if ($existing && $isPaid) {
                    $this->checkedEmployees[$emp->id] = true;
                }
            }

            $currentChecked = $this->checkedEmployees[$emp->id] ?? false;

            if ($currentChecked) {
                $totalPaid += $net;
            } else {
                $totalPending += $net;
            }
            $totalNet += $net;

            $iqamaNumber = $emp->details?->iqama_number ?: $emp->details?->national_id ?: '—';
            $iqamaExpiryGregorian = $emp->details?->iqama_expiry ? \Carbon\Carbon::parse($emp->details->iqama_expiry)->format('Y/m/d') : '—';
            $iqamaExpiryHijri = $emp->details?->iqama_expiry ? \App\Services\HijriDateService::toHijriNumeric($emp->details->iqama_expiry) : '—';
            $phone = $emp->user?->phone ?: ($emp->details?->emergency_contact_phone ?: '—');

            $otherParts = [];
            if ($additional > 0) {
                $otherParts[] = '+' . number_format($additional, 2);
            }
            if ($deductions > 0) {
                $otherParts[] = '-' . number_format($deductions, 2);
            }
            $otherAmount = !empty($otherParts) ? implode(' / ', $otherParts) : '—';

            $rows[] = [
                'employee_id'            => $emp->id,
                'name'                   => $emp->user?->name ?? 'موظف #' . $emp->id,
                'email'                  => $emp->user?->email,
                'phone'                  => $phone,
                'iqama_number'           => $iqamaNumber,
                'iqama_expiry_gregorian' => $iqamaExpiryGregorian,
                'iqama_expiry_hijri'     => $iqamaExpiryHijri,
                'employee_number'        => $emp->employee_number ?? ('EMP-' . $emp->id),
                'company_name'           => $emp->company?->trade_name ?? '—',
                'branch_name'            => $emp->branch?->name ?? null,
                'sponsor_name'           => $emp->details?->guarantor?->name ?? $emp->details?->sponsor_name ?? '—',
                'basic_salary'           => $basic,
                'additional_amount'      => $additional,
                'deduction_amount'       => $deductions,
                'other_amount'           => $otherAmount,
                'net_amount'             => $net,
                'record_number'          => $recordNumber,
                'existing_id'            => $existing?->id,
            ];
        }

        $this->employeeRows = $rows;
        $this->totalEmployees = count($rows);
        $this->totalNetAmount = round($totalNet, 2);
        $this->totalPaidAmount = round($totalPaid, 2);
        $this->totalPendingAmount = round($totalPending, 2);

        // Recalculate pagination after data changes
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

    public function toggleEmployeeCheck(int $employeeId): void
    {
        $this->checkedEmployees[$employeeId] = !($this->checkedEmployees[$employeeId] ?? false);
        $this->recomputeSummaryStats();
    }

    public function toggleCheckAll(bool $checkState): void
    {
        foreach ($this->employeeRows as $row) {
            $this->checkedEmployees[$row['employee_id']] = $checkState;
        }
        $this->recomputeSummaryStats();
    }

    protected function recomputeSummaryStats(): void
    {
        $totalPaid = 0.0;
        $totalPending = 0.0;

        foreach ($this->employeeRows as $row) {
            $empId = $row['employee_id'];
            $isPaid = $this->checkedEmployees[$empId] ?? false;
            if ($isPaid) {
                $totalPaid += $row['net_amount'];
            } else {
                $totalPending += $row['net_amount'];
            }
        }

        $this->totalPaidAmount = round($totalPaid, 2);
        $this->totalPendingAmount = round($totalPending, 2);
    }

    public function savePayrollSheet(): void
    {
        if (empty($this->employeeRows)) {
            Notification::make()
                ->title('لا يوجد موظفون في الكشف للحفظ')
                ->warning()
                ->send();
            return;
        }

        $savedCount = 0;
        $monthDate = Carbon::parse($this->payrollMonth)->startOfMonth()->toDateString();

        DB::transaction(function () use ($monthDate, &$savedCount) {
            foreach ($this->employeeRows as $row) {
                $empId = $row['employee_id'];
                $emp = Employee::find($empId);
                if (!$emp || empty($emp->company_id) || !Company::where('id', $emp->company_id)->exists()) {
                    continue;
                }

                $isChecked = $this->checkedEmployees[$empId] ?? false;
                $netAmount = (float) $row['net_amount'];

                $record = PayrollRecord::firstOrNew([
                    'company_id'     => $emp->company_id,
                    'employee_id'    => $emp->id,
                    'operation_type' => $this->operationType,
                    'payroll_month'  => $monthDate,
                ]);

                $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $this->payrollMonth);

                $record->record_date       = now()->toDateString();
                $record->basic_salary      = (float) $row['basic_salary'];
                $record->additional_amount = (float) $row['additional_amount'];
                $record->deduction_amount  = (float) $row['deduction_amount'];
                $record->overtime_amount   = (float) ($financials['overtime_amount'] ?? 0);
                $record->rewards_amount    = (float) ($financials['rewards_amount'] ?? 0);
                $record->advance_deduction = (float) ($financials['advance_deduction'] ?? 0);
                $record->absence_deduction = (float) ($financials['absence_deduction'] ?? 0);
                $record->net_amount        = $netAmount;
                $record->notes             = $this->notes[$empId] ?? null;

                if ($isChecked) {
                    $record->paid_amount      = $netAmount;
                    $record->remaining_amount = 0.0;
                    $record->payment_status   = PaymentStatus::PAID;
                    $record->save();

                    // If not disbursed yet, create treasury transaction and payment
                    if ($record->payments()->count() === 0 && $netAmount > 0) {
                        try {
                            PayrollCalculationService::disburseSalary(
                                record: $record,
                                amount: $netAmount,
                                paymentDate: now()->toDateString(),
                                paymentMethod: 'cash',
                                notes: $record->notes
                            );
                        } catch (\Throwable $e) {}
                    }
                } else {
                    if ($record->payments()->count() === 0) {
                        $record->paid_amount      = 0.0;
                        $record->remaining_amount = $netAmount;
                        $record->payment_status   = PaymentStatus::UNPAID;
                        $record->save();
                    }
                }

                $savedCount++;
            }
        });

        $opLabel = $this->operationType === 'payroll' ? 'الرواتب' : 'التحصيل';

        Notification::make()
            ->title("تم حفظ وتحديث مسير {$opLabel} بنجاح")
            ->body("تم تحديث وحفظ بيانات {$savedCount} موظف عن شهر {$this->payrollMonth}")
            ->success()
            ->send();

        $this->loadSheet();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('finance.payroll_records.actions.create_single') ?? 'إضافة مسير منفرد'),
        ];
    }
}
