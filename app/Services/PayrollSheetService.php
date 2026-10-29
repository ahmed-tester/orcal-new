<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Guarantor;
use App\Models\PayrollRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollSheetService
{
    /**
     * Build employee rows and summary metrics for the interactive payroll / collection sheet.
     */
    public static function buildSheetData(
        array $filters,
        array $deletedEmployeeIds = [],
        array $checkedEmployees = [],
        array $notes = []
    ): array {
        $filterMode = $filters['filterMode'] ?? 'single_company';
        $selectedCompanyId = $filters['selectedCompanyId'] ?? null;
        $selectedCompanyIds = $filters['selectedCompanyIds'] ?? [];
        $selectedGuarantorId = $filters['selectedGuarantorId'] ?? null;
        $selectedGuarantor = $filters['selectedGuarantor'] ?? null;
        $operationType = $filters['operationType'] ?? 'payroll';
        $employeeScope = $filters['employeeScope'] ?? 'all';
        $selectedEmployeeId = $filters['selectedEmployeeId'] ?? null;
        $paymentScope = $filters['paymentScope'] ?? 'all';
        $payrollMonth = $filters['payrollMonth'] ?? now()->format('Y-m');
        $searchQuery = trim($filters['searchQuery'] ?? '');
        $user = auth()->user();

        $query = Employee::query()
            ->where('status', 'active')
            ->whereHas('company')
            ->with(['user', 'company', 'branch', 'details.guarantor']);

        if ($user && ! $user->isSuperAdmin()) {
            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (! empty($authorizedIds)) {
                $query->whereIn('company_id', $authorizedIds);
            }
        }

        // 1. Filter by Mode
        if ($filterMode === 'single_company') {
            if ($selectedCompanyId) {
                $query->where('company_id', $selectedCompanyId);
            }
            if ($employeeScope === 'specific' && $selectedEmployeeId) {
                $query->where('id', $selectedEmployeeId);
            }
        } elseif ($filterMode === 'multiple_companies') {
            if (! empty($selectedCompanyIds)) {
                $query->whereIn('company_id', $selectedCompanyIds);
            }
        } elseif ($filterMode === 'all_companies') {
            // All companies
        } elseif ($filterMode === 'by_guarantor') {
            if ($selectedGuarantorId) {
                $guarId = (int) $selectedGuarantorId;
                $guarObj = Guarantor::find($guarId);
                $guarName = $guarObj?->name;

                $query->whereHas('details', function ($q) use ($guarId, $guarName) {
                    $q->where('guarantor_id', $guarId);
                    if ($guarName) {
                        $q->orWhere('sponsor_name', $guarName);
                    }
                });
            } elseif ($selectedGuarantor) {
                $query->whereHas('details', fn ($q) => $q->where('sponsor_name', $selectedGuarantor));
            } else {
                $query->whereHas('details', fn ($q) => $q->whereNotNull('guarantor_id')->orWhereNotNull('sponsor_name'));
            }
        }

        // 2. Search Query
        if (! empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$searchQuery}%")->orWhere('email', 'like', "%{$searchQuery}%"))
                    ->orWhere('employee_number', 'like', "%{$searchQuery}%")
                    ->orWhereHas('company', fn ($c) => $c->where('trade_name', 'like', "%{$searchQuery}%"))
                    ->orWhereHas('details', fn ($d) => $d->where('sponsor_name', 'like', "%{$searchQuery}%"));
            });
        }

        $employees = $query->get();

        $monthDate = Carbon::parse($payrollMonth ?: now());
        $year = $monthDate->year;
        $month = $monthDate->month;

        $allRows = [];
        $totalBasic = 0.0;
        $totalAdditional = 0.0;
        $totalDeduction = 0.0;
        $totalNet = 0.0;
        $totalPaid = 0.0;
        $totalPending = 0.0;

        foreach ($employees as $emp) {
            if (empty($emp->company_id) || empty($emp->company)) {
                continue;
            }

            if (in_array($emp->id, $deletedEmployeeIds)) {
                continue;
            }

            $existing = PayrollRecord::where('employee_id', $emp->id)
                ->where('operation_type', $operationType)
                ->whereYear('payroll_month', $year)
                ->whereMonth('payroll_month', $month)
                ->first();

            if ($operationType === 'collection') {
                if (! $existing && ! ($employeeScope === 'specific' && $selectedEmployeeId == $emp->id)) {
                    continue;
                }
            }

            $payrollData = PayrollCalculationService::resolveEmployeePayrollData($emp, $existing, $payrollMonth);

            $basic = $payrollData['basic_salary'];
            $additional = $payrollData['additional_amount'];
            $deductions = $payrollData['deduction_amount'];
            $net = $payrollData['net_amount'];
            $paid = $payrollData['paid_amount'];
            $remaining = $payrollData['remaining_amount'];
            $paymentStatus = $payrollData['payment_status'];
            $recordNumber = $payrollData['record_number'];

            if (! isset($notes[$emp->id])) {
                $notes[$emp->id] = $payrollData['notes'];
            }

            $isPaid = ($paymentStatus === PaymentStatus::PAID || ($remaining <= 0 && $net > 0));

            if (! isset($checkedEmployees[$emp->id])) {
                $checkedEmployees[$emp->id] = $isPaid;
            } else {
                if ($existing && $isPaid) {
                    $checkedEmployees[$emp->id] = true;
                }
            }

            $currentChecked = $checkedEmployees[$emp->id] ?? false;

            $effectivePaid = $currentChecked ? $net : $paid;
            $effectiveRemaining = $currentChecked ? 0.0 : $remaining;
            $isRowPaid = ($currentChecked || $isPaid || ($effectiveRemaining <= 0 && $net > 0));

            $iqamaNumber = $emp->details?->iqama_number ?: $emp->details?->national_id ?: '—';
            $iqamaExpiryGregorian = $emp->details?->iqama_expiry ? Carbon::parse($emp->details->iqama_expiry)->format('Y/m/d') : '—';
            $iqamaExpiryHijri = $emp->details?->iqama_expiry ? HijriDateService::toHijriNumeric($emp->details->iqama_expiry) : '—';
            $phone = $emp->user?->phone ?: ($emp->details?->emergency_contact_phone ?: '—');

            $otherParts = [];
            if ($additional > 0) {
                $otherParts[] = '+'.number_format($additional, 2);
            }
            if ($deductions > 0) {
                $otherParts[] = '-'.number_format($deductions, 2);
            }
            $otherAmount = ! empty($otherParts) ? implode(' / ', $otherParts) : '—';

            $allRows[] = [
                'employee_id' => $emp->id,
                'name' => $emp->user?->name ?? 'موظف #'.$emp->id,
                'email' => $emp->user?->email,
                'phone' => $phone,
                'iqama_number' => $iqamaNumber,
                'iqama_expiry_gregorian' => $iqamaExpiryGregorian,
                'iqama_expiry_hijri' => $iqamaExpiryHijri,
                'employee_number' => $emp->employee_number ?? ('EMP-'.$emp->id),
                'company_name' => $emp->company?->trade_name ?? '—',
                'branch_name' => $emp->branch?->name ?? null,
                'sponsor_name' => $emp->details?->guarantor?->name ?? $emp->details?->sponsor_name ?? '—',
                'basic_salary' => $basic,
                'additional_amount' => $additional,
                'deduction_amount' => $deductions,
                'other_amount' => $otherAmount,
                'net_amount' => $net,
                'paid_amount' => $effectivePaid,
                'remaining_amount' => $effectiveRemaining,
                'payment_status' => $paymentStatus,
                'original_paid_amount' => $paid,
                'original_remaining_amount' => $remaining,
                'original_payment_status' => $paymentStatus,
                'record_number' => $recordNumber,
                'existing_id' => $existing?->id,
                'is_paid' => $isRowPaid,
            ];
        }

        $allRows = $allRows ?? [];
        $countAll = count($allRows);
        $countPaid = 0;
        $countUnpaid = 0;
        foreach ($allRows as $r) {
            if ($r['is_paid']) {
                $countPaid++;
            } else {
                $countUnpaid++;
            }
        }

        $rows = [];
        foreach ($allRows as $r) {
            if ($paymentScope === 'paid' && ! $r['is_paid']) {
                continue;
            }
            if ($paymentScope === 'unpaid' && $r['is_paid']) {
                continue;
            }

            $rows[] = $r;

            $totalBasic += (float) ($r['basic_salary'] ?? 0);
            $totalAdditional += (float) ($r['additional_amount'] ?? 0);
            $totalDeduction += (float) ($r['deduction_amount'] ?? 0);
            $totalNet += (float) ($r['net_amount'] ?? 0);
            $totalPaid += (float) ($r['paid_amount'] ?? 0);
            $totalPending += (float) ($r['remaining_amount'] ?? 0);
        }

        return [
            'rows' => $rows,
            'checkedEmployees' => $checkedEmployees,
            'notes' => $notes,
            'counts' => [
                'all'    => $countAll,
                'paid'   => $countPaid,
                'unpaid' => $countUnpaid,
            ],
            'totals' => [
                'totalEmployees' => count($rows),
                'totalBasicAmount' => round($totalBasic, 2),
                'totalAdditionalAmount' => round($totalAdditional, 2),
                'totalDeductionAmount' => round($totalDeduction, 2),
                'totalNetAmount' => round($totalNet, 2),
                'totalPaidAmount' => round($totalPaid, 2),
                'totalPendingAmount' => round($totalPending, 2),
            ],
        ];
    }

    /**
     * Save/update batch payroll or collection records and process disbursements.
     */
    public static function saveSheet(
        array $employeeRows,
        array $checkedEmployees,
        array $notes,
        string $operationType,
        string $payrollMonth
    ): int {
        if (empty($employeeRows)) {
            return 0;
        }

        $savedCount = 0;
        $monthDate = Carbon::parse($payrollMonth)->startOfMonth()->toDateString();

        DB::transaction(function () use ($employeeRows, $checkedEmployees, $notes, $operationType, $payrollMonth, $monthDate, &$savedCount) {
            foreach ($employeeRows as $row) {
                $empId = $row['employee_id'];
                $emp = Employee::find($empId);
                if (! $emp || empty($emp->company_id) || ! Company::where('id', $emp->company_id)->exists()) {
                    continue;
                }

                $isChecked = $checkedEmployees[$empId] ?? false;
                $netAmount = (float) $row['net_amount'];

                $record = PayrollRecord::firstOrNew([
                    'company_id' => $emp->company_id,
                    'employee_id' => $emp->id,
                    'operation_type' => $operationType,
                    'payroll_month' => $monthDate,
                ]);

                $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $payrollMonth);

                $record->record_date = now()->toDateString();
                $record->basic_salary = (float) $row['basic_salary'];
                $record->additional_amount = (float) $row['additional_amount'];
                $record->deduction_amount = (float) $row['deduction_amount'];
                $record->overtime_amount = (float) ($financials['overtime_amount'] ?? 0);
                $record->rewards_amount = (float) ($financials['rewards_amount'] ?? 0);
                $record->advance_deduction = (float) ($financials['advance_deduction'] ?? 0);
                $record->absence_deduction = (float) ($financials['absence_deduction'] ?? 0);
                if (! empty($financials['metadata'])) {
                    $record->metadata = array_merge($record->metadata ?? [], $financials['metadata']);
                }
                $record->net_amount = $netAmount;
                $record->notes = $notes[$empId] ?? null;

                if ($isChecked) {
                    $record->paid_amount = $netAmount;
                    $record->remaining_amount = 0.0;
                    $record->payment_status = PaymentStatus::PAID;
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
                        } catch (\Throwable $e) {
                        }
                    }
                } else {
                    $paidAmount = (float) ($row['paid_amount'] ?? 0.0);
                    $remainingAmount = (float) ($row['remaining_amount'] ?? max(0.0, $netAmount - $paidAmount));

                    $record->paid_amount = $paidAmount;
                    $record->remaining_amount = $remainingAmount;

                    if ($paidAmount >= $netAmount && $netAmount > 0) {
                        $record->payment_status = PaymentStatus::PAID;
                    } elseif ($paidAmount > 0) {
                        $record->payment_status = PaymentStatus::PARTIALLY_PAID;
                    } else {
                        $record->payment_status = PaymentStatus::UNPAID;
                    }

                    $record->save();
                }

                $savedCount++;
            }
        });

        return $savedCount;
    }

    /**
     * Update row and totals when an employee checkbox is toggled.
     */
    public static function applyEmployeeCheck(array &$rows, array $checkedEmployees, int $employeeId): array
    {
        $isChecked = $checkedEmployees[$employeeId] ?? false;
        $totalPaid = 0.0;
        $totalPending = 0.0;

        foreach ($rows as &$row) {
            if ($row['employee_id'] === $employeeId) {
                $net = (float) $row['net_amount'];
                if ($isChecked) {
                    $row['paid_amount'] = $net;
                    $row['remaining_amount'] = 0.0;
                    $row['payment_status'] = PaymentStatus::PAID;
                } else {
                    $row['paid_amount'] = (float) ($row['original_paid_amount'] ?? 0.0);
                    $row['remaining_amount'] = (float) ($row['original_remaining_amount'] ?? $net);
                    $row['payment_status'] = $row['original_payment_status'] ?? PaymentStatus::UNPAID;
                }
            }
            $totalPaid += (float) $row['paid_amount'];
            $totalPending += (float) $row['remaining_amount'];
        }
        unset($row);

        return [
            'totalPaidAmount' => round($totalPaid, 2),
            'totalPendingAmount' => round($totalPending, 2),
        ];
    }

    /**
     * Update all rows and totals when check-all / uncheck-all is triggered.
     */
    public static function applyCheckAll(array &$rows, bool $checkState): array
    {
        $totalPaid = 0.0;
        $totalPending = 0.0;

        foreach ($rows as &$row) {
            $net = (float) $row['net_amount'];
            if ($checkState) {
                $row['paid_amount'] = $net;
                $row['remaining_amount'] = 0.0;
                $row['payment_status'] = PaymentStatus::PAID;
            } else {
                $row['paid_amount'] = (float) ($row['original_paid_amount'] ?? 0.0);
                $row['remaining_amount'] = (float) ($row['original_remaining_amount'] ?? $net);
                $row['payment_status'] = $row['original_payment_status'] ?? PaymentStatus::UNPAID;
            }
            $totalPaid += (float) $row['paid_amount'];
            $totalPending += (float) $row['remaining_amount'];
        }
        unset($row);

        return [
            'totalPaidAmount' => round($totalPaid, 2),
            'totalPendingAmount' => round($totalPending, 2),
        ];
    }

    /**
     * Retrieve or instantiate a new PayrollRecord for single employee edit modal.
     */
    public static function findOrCreateEmployeeRecord(
        int $employeeId,
        string $operationType,
        string $payrollMonth,
        ?string $note = null
    ): ?PayrollRecord {
        $c = Carbon::parse($payrollMonth ?: now());

        $record = PayrollRecord::where('employee_id', $employeeId)
            ->where('operation_type', $operationType)
            ->whereYear('payroll_month', $c->year)
            ->whereMonth('payroll_month', $c->month)
            ->first();

        if ($record) {
            return $record;
        }

        $emp = Employee::find($employeeId);
        if (! $emp || empty($emp->company_id)) {
            return null;
        }

        if ($operationType === 'collection') {
            return PayrollRecord::create([
                'company_id' => $emp->company_id,
                'employee_id' => $emp->id,
                'operation_type' => 'collection',
                'payroll_month' => $c->copy()->startOfMonth()->toDateString(),
                'record_date' => now()->toDateString(),
                'basic_salary' => 0,
                'overtime_amount' => 0,
                'rewards_amount' => 0,
                'additional_amount' => 0,
                'advance_deduction' => 0,
                'absence_deduction' => 0,
                'deduction_amount' => 0,
                'net_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'payment_status' => PaymentStatus::UNPAID,
                'notes' => $note,
                'created_by' => auth()->id(),
            ]);
        }

        $fin = PayrollCalculationService::resolveEmployeePayrollData($emp, null, $payrollMonth);
        $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $payrollMonth);

        return PayrollRecord::create([
            'company_id' => $emp->company_id,
            'employee_id' => $emp->id,
            'operation_type' => $operationType,
            'payroll_month' => $c->copy()->startOfMonth()->toDateString(),
            'record_date' => now()->toDateString(),
            'basic_salary' => $fin['basic_salary'],
            'overtime_amount' => (float) ($financials['overtime_amount'] ?? 0),
            'rewards_amount' => (float) ($financials['rewards_amount'] ?? 0),
            'additional_amount' => $fin['additional_amount'],
            'advance_deduction' => (float) ($financials['advance_deduction'] ?? 0),
            'absence_deduction' => (float) ($financials['absence_deduction'] ?? 0),
            'deduction_amount' => $fin['deduction_amount'],
            'metadata' => $financials['metadata'] ?? null,
            'net_amount' => $fin['net_amount'],
            'paid_amount' => $fin['paid_amount'],
            'remaining_amount' => $fin['remaining_amount'],
            'payment_status' => $fin['payment_status'],
            'notes' => $note,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Delete an employee payroll record and its linked payments.
     */
    public static function deleteEmployeeRecord(
        int $employeeId,
        string $operationType,
        string $payrollMonth,
        ?int $recordId = null
    ): void {
        $record = null;
        if ($recordId) {
            $record = PayrollRecord::find($recordId);
        }

        if (! $record) {
            $c = Carbon::parse($payrollMonth ?: now());
            $record = PayrollRecord::where('employee_id', $employeeId)
                ->where('operation_type', $operationType)
                ->whereYear('payroll_month', $c->year)
                ->whereMonth('payroll_month', $c->month)
                ->first();
        }

        if ($record) {
            $record->delete();
        }
    }

    /**
     * Retrieve all available fiscal years present in payroll records.
     */
    public static function getAvailablePayrollYears(?int $companyId = null, ?array $companyIds = null): array
    {
        $query = PayrollRecord::query();

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (! empty($authorizedIds)) {
                $query->whereIn('company_id', $authorizedIds);
            }
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (! empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }

        $dbYears = $query->selectRaw('DISTINCT YEAR(payroll_month) as yr')
            ->whereNotNull('payroll_month')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 2000)
            ->values()
            ->toArray();

        $currentYear = (int) now()->format('Y');

        // Always include a continuous range of years around the current year (e.g. 3 years back, 2 years forward) + all DB years
        $rangeYears = range($currentYear - 3, $currentYear + 2);
        $allYears = array_unique(array_merge($dbYears, $rangeYears));
        rsort($allYears);

        return array_values($allYears);
    }

    /**
     * Retrieve aggregated monthly folder files for a given fiscal year and operation type.
     */
    public static function getArchivedMonthsSummary(
        int $year,
        string $operationType = 'payroll',
        ?int $companyId = null,
        ?array $companyIds = null
    ): array {
        $query = PayrollRecord::query()
            ->where('operation_type', $operationType)
            ->whereYear('payroll_month', $year);

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (! empty($authorizedIds)) {
                $query->whereIn('company_id', $authorizedIds);
            }
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (! empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }

        $records = $query->selectRaw('
            MONTH(payroll_month) as m,
            DATE_FORMAT(payroll_month, "%Y-%m") as month_key,
            COUNT(id) as total_employees,
            SUM(net_amount) as total_net,
            SUM(paid_amount) as total_paid,
            SUM(remaining_amount) as total_remaining,
            SUM(CASE WHEN payment_status = "paid" OR remaining_amount <= 0 THEN 1 ELSE 0 END) as paid_count
        ')
            ->groupBy('m', 'month_key')
            ->orderBy('m', 'asc')
            ->get();

        $result = [];
        foreach ($records as $rec) {
            $m = (int) $rec->m;
            $monthKey = $rec->month_key;
            $monthName = self::getArabicMonthName($m);
            $totalEmployees = (int) $rec->total_employees;
            $totalNet = (float) $rec->total_net;
            $totalPaid = (float) $rec->total_paid;
            $totalRemaining = (float) $rec->total_remaining;
            $paidCount = (int) $rec->paid_count;
            $unpaidCount = max(0, $totalEmployees - $paidCount);

            $completionRate = $totalNet > 0 ? round(($totalPaid / $totalNet) * 100, 1) : ($paidCount > 0 ? 100 : 0);
            $isFullyPaid = ($totalRemaining <= 0 && $totalNet > 0) || ($paidCount === $totalEmployees && $totalEmployees > 0);

            $opLabel = $operationType === 'payroll' ? 'مسير رواتب' : 'كشف تحصيل';
            $title = "{$opLabel} شهر {$monthName} {$year}";

            $result[] = [
                'month_num'            => $m,
                'month_key'            => $monthKey,
                'month_name'           => $monthName,
                'year'                 => $year,
                'title'                => $title,
                'operation_type'       => $operationType,
                'total_employees'      => $totalEmployees,
                'total_net'            => $totalNet,
                'total_paid'           => $totalPaid,
                'total_remaining'      => $totalRemaining,
                'paid_count'           => $paidCount,
                'unpaid_count'         => $unpaidCount,
                'completion_rate'      => $completionRate,
                'is_fully_paid'        => $isFullyPaid,
            ];
        }

        return $result;
    }

    /**
     * Map integer month (1..12) to Arabic month name.
     */
    public static function getArabicMonthName(int $month): string
    {
        $months = [
            1  => 'يناير',
            2  => 'فبراير',
            3  => 'مارس',
            4  => 'أبريل',
            5  => 'مايو',
            6  => 'يونيو',
            7  => 'يوليو',
            8  => 'أغسطس',
            9  => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        return $months[$month] ?? (string) $month;
    }
}