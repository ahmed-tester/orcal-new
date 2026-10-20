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
        $payrollMonth = $filters['payrollMonth'] ?? now()->format('Y-m');
        $searchQuery = trim($filters['searchQuery'] ?? '');
        $user = auth()->user();

        $query = Employee::query()
            ->where('status', 'active')
            ->whereHas('company')
            ->with(['user', 'company', 'branch', 'details.guarantor']);

        if ($user && !$user->isSuperAdmin()) {
            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (!empty($authorizedIds)) {
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
            if (!empty($selectedCompanyIds)) {
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
        if (!empty($searchQuery)) {
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

        $rows = [];
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
                if (!$existing && !($employeeScope === 'specific' && $selectedEmployeeId == $emp->id)) {
                    continue;
                }
            }

            if ($operationType === 'payroll' && !$existing) {
                $hasCollection = PayrollRecord::where('employee_id', $emp->id)
                    ->where('operation_type', 'collection')
                    ->whereYear('payroll_month', $year)
                    ->whereMonth('payroll_month', $month)
                    ->exists();

                if ($hasCollection) {
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

            if (!isset($notes[$emp->id])) {
                $notes[$emp->id] = $payrollData['notes'];
            }

            // Checkbox quick toggle handling (Commented out):
            /*
            $isPaid = ($paymentStatus === PaymentStatus::PAID || ($remaining <= 0 && $net > 0));

            if (!isset($checkedEmployees[$emp->id])) {
                $checkedEmployees[$emp->id] = $isPaid;
            } else {
                if ($existing && $isPaid) {
                    $checkedEmployees[$emp->id] = true;
                }
            }

            $currentChecked = $checkedEmployees[$emp->id] ?? false;

            $effectivePaid = $currentChecked ? $net : $paid;
            $effectiveRemaining = $currentChecked ? 0.0 : $remaining;
            */
            $effectivePaid = $paid;
            $effectiveRemaining = $remaining;

            $totalBasic += $basic;
            $totalAdditional += $additional;
            $totalDeduction += $deductions;
            $totalNet += $net;
            $totalPaid += $effectivePaid;
            $totalPending += $effectiveRemaining;

            $iqamaNumber = $emp->details?->iqama_number ?: $emp->details?->national_id ?: '—';
            $iqamaExpiryGregorian = $emp->details?->iqama_expiry ? Carbon::parse($emp->details->iqama_expiry)->format('Y/m/d') : '—';
            $iqamaExpiryHijri = $emp->details?->iqama_expiry ? HijriDateService::toHijriNumeric($emp->details->iqama_expiry) : '—';
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
                'employee_id'               => $emp->id,
                'name'                      => $emp->user?->name ?? 'موظف #' . $emp->id,
                'email'                     => $emp->user?->email,
                'phone'                     => $phone,
                'iqama_number'              => $iqamaNumber,
                'iqama_expiry_gregorian'    => $iqamaExpiryGregorian,
                'iqama_expiry_hijri'        => $iqamaExpiryHijri,
                'employee_number'           => $emp->employee_number ?? ('EMP-' . $emp->id),
                'company_name'              => $emp->company?->trade_name ?? '—',
                'branch_name'               => $emp->branch?->name ?? null,
                'sponsor_name'              => $emp->details?->guarantor?->name ?? $emp->details?->sponsor_name ?? '—',
                'basic_salary'              => $basic,
                'additional_amount'         => $additional,
                'deduction_amount'          => $deductions,
                'other_amount'              => $otherAmount,
                'net_amount'                => $net,
                'paid_amount'               => $effectivePaid,
                'remaining_amount'          => $effectiveRemaining,
                'payment_status'            => $paymentStatus,
                'original_paid_amount'      => $paid,
                'original_remaining_amount' => $remaining,
                'original_payment_status'   => $paymentStatus,
                'record_number'             => $recordNumber,
                'existing_id'               => $existing?->id,
            ];
        }

        return [
            'rows'             => $rows,
            'checkedEmployees' => $checkedEmployees,
            'notes'            => $notes,
            'totals'           => [
                'totalEmployees'        => count($rows),
                'totalBasicAmount'      => round($totalBasic, 2),
                'totalAdditionalAmount' => round($totalAdditional, 2),
                'totalDeductionAmount'  => round($totalDeduction, 2),
                'totalNetAmount'        => round($totalNet, 2),
                'totalPaidAmount'       => round($totalPaid, 2),
                'totalPendingAmount'    => round($totalPending, 2),
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
                if (!$emp || empty($emp->company_id) || !Company::where('id', $emp->company_id)->exists()) {
                    continue;
                }

                $isChecked = $checkedEmployees[$empId] ?? false;
                $netAmount = (float) $row['net_amount'];

                $record = PayrollRecord::firstOrNew([
                    'company_id'     => $emp->company_id,
                    'employee_id'    => $emp->id,
                    'operation_type' => $operationType,
                    'payroll_month'  => $monthDate,
                ]);

                $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $payrollMonth);

                $record->record_date       = now()->toDateString();
                $record->basic_salary      = (float) $row['basic_salary'];
                $record->additional_amount = (float) $row['additional_amount'];
                $record->deduction_amount  = (float) $row['deduction_amount'];
                $record->overtime_amount   = (float) ($financials['overtime_amount'] ?? 0);
                $record->rewards_amount    = (float) ($financials['rewards_amount'] ?? 0);
                $record->advance_deduction = (float) ($financials['advance_deduction'] ?? 0);
                $record->absence_deduction = (float) ($financials['absence_deduction'] ?? 0);
                $record->net_amount        = $netAmount;
                $record->notes             = $notes[$empId] ?? null;

                /*
                // Quick disburse when checkbox is checked (Commented out):
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
                */
                    $paidAmount = (float) ($row['paid_amount'] ?? 0.0);
                    $remainingAmount = (float) ($row['remaining_amount'] ?? max(0.0, $netAmount - $paidAmount));

                    $record->paid_amount      = $paidAmount;
                    $record->remaining_amount = $remainingAmount;

                    if ($paidAmount >= $netAmount && $netAmount > 0) {
                        $record->payment_status = PaymentStatus::PAID;
                    } elseif ($paidAmount > 0) {
                        $record->payment_status = PaymentStatus::PARTIALLY_PAID;
                    } else {
                        $record->payment_status = PaymentStatus::UNPAID;
                    }

                    $record->save();
                // }

                $savedCount++;
            }
        });

        return $savedCount;
    }

    // ==========================================
    // Checkbox quick calculation methods (Commented out):
    // ==========================================
    // public static function applyEmployeeCheck(array &$rows, array $checkedEmployees, int $employeeId): array
    // {
    //     $isChecked = $checkedEmployees[$employeeId] ?? false;
    //     $totalPaid = 0.0;
    //     $totalPending = 0.0;
    //     foreach ($rows as &$row) {
    //         if ($row['employee_id'] === $employeeId) {
    //             $net = (float) $row['net_amount'];
    //             if ($isChecked) {
    //                 $row['paid_amount'] = $net;
    //                 $row['remaining_amount'] = 0.0;
    //                 $row['payment_status'] = PaymentStatus::PAID;
    //             } else {
    //                 $row['paid_amount'] = (float) ($row['original_paid_amount'] ?? 0.0);
    //                 $row['remaining_amount'] = (float) ($row['original_remaining_amount'] ?? $net);
    //                 $row['payment_status'] = $row['original_payment_status'] ?? PaymentStatus::UNPAID;
    //             }
    //         }
    //         $totalPaid += (float) $row['paid_amount'];
    //         $totalPending += (float) $row['remaining_amount'];
    //     }
    //     unset($row);
    //     return [
    //         'totalPaidAmount'    => round($totalPaid, 2),
    //         'totalPendingAmount' => round($totalPending, 2),
    //     ];
    // }
    //
    // public static function applyCheckAll(array &$rows, bool $checkState): array
    // {
    //     $totalPaid = 0.0;
    //     $totalPending = 0.0;
    //     foreach ($rows as &$row) {
    //         $net = (float) $row['net_amount'];
    //         if ($checkState) {
    //             $row['paid_amount'] = $net;
    //             $row['remaining_amount'] = 0.0;
    //             $row['payment_status'] = PaymentStatus::PAID;
    //         } else {
    //             $row['paid_amount'] = (float) ($row['original_paid_amount'] ?? 0.0);
    //             $row['remaining_amount'] = (float) ($row['original_remaining_amount'] ?? $net);
    //             $row['payment_status'] = $row['original_payment_status'] ?? PaymentStatus::UNPAID;
    //         }
    //         $totalPaid += (float) $row['paid_amount'];
    //         $totalPending += (float) $row['remaining_amount'];
    //     }
    //     unset($row);
    //     return [
    //         'totalPaidAmount'    => round($totalPaid, 2),
    //         'totalPendingAmount' => round($totalPending, 2),
    //     ];
    // }

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
        if (!$emp || empty($emp->company_id)) {
            return null;
        }

        $fin = PayrollCalculationService::resolveEmployeePayrollData($emp, null, $payrollMonth);
        $financials = PayrollCalculationService::calculateEmployeeFinancials($emp->id, $payrollMonth);

        return PayrollRecord::create([
            'company_id'        => $emp->company_id,
            'employee_id'       => $emp->id,
            'operation_type'    => $operationType,
            'payroll_month'     => $c->copy()->startOfMonth()->toDateString(),
            'record_date'       => now()->toDateString(),
            'basic_salary'      => $fin['basic_salary'],
            'overtime_amount'   => (float) ($financials['overtime_amount'] ?? 0),
            'rewards_amount'    => (float) ($financials['rewards_amount'] ?? 0),
            'additional_amount' => $fin['additional_amount'],
            'advance_deduction' => (float) ($financials['advance_deduction'] ?? 0),
            'absence_deduction' => (float) ($financials['absence_deduction'] ?? 0),
            'deduction_amount'  => $fin['deduction_amount'],
            'net_amount'        => $fin['net_amount'],
            'paid_amount'       => $fin['paid_amount'],
            'remaining_amount'  => $fin['remaining_amount'],
            'payment_status'    => $fin['payment_status'],
            'notes'             => $note,
            'created_by'        => auth()->id(),
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

        if (!$record) {
            $c = Carbon::parse($payrollMonth ?: now());
            $record = PayrollRecord::where('employee_id', $employeeId)
                ->where('operation_type', $operationType)
                ->whereYear('payroll_month', $c->year)
                ->whereMonth('payroll_month', $c->month)
                ->first();
        }

        if ($record) {
            $record->payments()->delete();
            $record->delete();
        }
    }
}
