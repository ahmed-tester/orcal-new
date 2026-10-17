<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentItemCategory;
use App\Enums\PaymentStatus;
use App\Enums\RewardStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Models\Installment;
use App\Models\PaymentCollectionItem;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\TreasuryTransaction;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollCalculationService
{
    /**
     * حساب جميع مستحقات واستقطاعات الموظف لشهر معين
     */
    public static function calculateEmployeeFinancials(?int $employeeId, ?string $monthStr = null): array
    {
        if (!$employeeId) {
            return [
                'company_id'        => null,
                'basic_salary'      => 0,
                'rewards_amount'    => 0,
                'overtime_amount'   => 0,
                'additional_amount' => 0,
                'advance_deduction' => 0,
                'absence_deduction' => 0,
                'deduction_amount'  => 0,
                'net_amount'        => 0,
                'remaining_amount'  => 0,
                'payment_status'    => PaymentStatus::UNPAID->value,
            ];
        }

        $employee = Employee::with('branch')->find($employeeId);
        if (!$employee) return [];

        $basic = (float) $employee->basic_salary;
        $month = Carbon::parse($monthStr ?: now());
        $branch = $employee->branch;
        $branchConfig = AttendancePenaltyService::getBranchSettings($branch);

        // 1. المكافآت المعتمدة
        $rewards = (float) EmployeeReward::where('employee_id', $employee->id)
            ->where('status', RewardStatus::APPROVED)
            ->whereYear('reward_date', $month->year)
            ->whereMonth('reward_date', $month->month)
            ->sum('net_amount');

        // 2. أقساط السلف والديون
        $advances = (float) Installment::where('employee_id', $employee->id)
            ->where('status', '!=', InstallmentStatus::COMPLETED)
            ->where('remaining_amount', '>', 0)
            ->sum('monthly_amount');

        // 3. الإضافي والغياب من البصمة مع سياسات الفرع
        $hourRate = $basic > 0 ? ($basic / 240) : 0;
        $dayRate  = $basic > 0 ? ($basic / 30) : 0;

        $overtimeMinutes = (float) Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $month->year)->whereMonth('date', $month->month)
            ->sum('overtime_hours');

        $overtimeHours = $overtimeMinutes > 0 ? ($overtimeMinutes / 60) : 0;
        $overtimeAmount = round($overtimeHours * $hourRate, 2);

        // حساب خصم التأخير (بالشرائح اليومية إذا كانت مفعلة بالفرع، أو بالساعات الدقيقة إذا كانت معطلة)
        if ($branchConfig['late_deduction_enabled']) {
            $lateDeductionDays = AttendancePenaltyService::calculateMonthlyLateDeductionDays($employee, $month);
            $lateAmount = round($lateDeductionDays * $dayRate, 2);
        } else {
            $lateMinutes = (float) Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $month->year)->whereMonth('date', $month->month)
                ->sum('late_hours');
            $lateHours = $lateMinutes > 0 ? ($lateMinutes / 60) : 0;
            $lateAmount = round($lateHours * $hourRate, 2);
        }

        // حساب خصم الغياب (مع تطبيق معامل الفرع إن وُجد)
        $rawAbsentDays = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $month->year)->whereMonth('date', $month->month)
            ->where('status', AttendanceStatus::ABSENT)
            ->count();

        $effectiveAbsentDays = AttendancePenaltyService::calculateAbsenceDeductionDays($rawAbsentDays, $branch);
        $absencePenaltyAmount = round($effectiveAbsentDays * $dayRate, 2);

        $absenceAmount = round($lateAmount + $absencePenaltyAmount, 2);
        $rewardsAmount = round($rewards, 2);
        $advanceAmount = round($advances, 2);

        $additions  = round($overtimeAmount + $rewardsAmount, 2);
        $deductions = round($advanceAmount + $absenceAmount, 2);
        $net        = round($basic + $additions - $deductions, 2);

        return [
            'company_id'        => $employee->company_id,
            'basic_salary'      => $basic,
            'rewards_amount'    => $rewardsAmount,
            'overtime_amount'   => $overtimeAmount,
            'additional_amount' => $additions,
            'advance_deduction' => $advanceAmount,
            'absence_deduction' => $absenceAmount,
            'deduction_amount'  => $deductions,
            'net_amount'        => $net,
            'remaining_amount'  => $net,
            'payment_status'    => PaymentStatus::UNPAID->value,
        ];
    }

    /**
     * إعادة احتساب الصافي والمتبقي وحالة الدفع
     */
    public static function computeTotals(float $basic, float $additional, float $deductions, float $paid = 0): array
    {
        $net = round($basic + $additional - $deductions, 2);
        $remaining = round(max(0, $net - $paid), 2);

        $status = PaymentStatus::UNPAID->value;
        if ($paid >= $net && $net > 0) {
            $status = PaymentStatus::PAID->value;
        } elseif ($paid > 0) {
            $status = PaymentStatus::PARTIALLY_PAID->value;
        }

        return [
            'net_amount'       => $net,
            'remaining_amount' => $remaining,
            'payment_status'   => $status,
        ];
    }

    /**
     * قاعدة التحقق من عدم تكرار مسير الراتب
     */
    public static function uniquePayrollRule(callable $get, $record): Closure
    {
        return function (string $attribute, $value, Closure $fail) use ($get, $record) {
            $empId = $get('employee_id');
            if (!$empId || !$value) return;

            $date = Carbon::parse($value);
            $companyId = $get('company_id') ?? auth()->user()?->company_id;
            $opType = $get('operation_type') ?? 'payroll';

            $exists = PayrollRecord::where('employee_id', $empId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('operation_type', $opType)
                ->whereYear('payroll_month', $date->year)
                ->whereMonth('payroll_month', $date->month)
                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                ->exists();

            if ($exists) {
                $name = Employee::find($empId)?->user?->name ?? "#{$empId}";
                $fail("تنبيه: مسير الرواتب للموظف ({$name}) عن شهر ({$date->translatedFormat('F Y')}) مسجل مسبقاً في النظام ولا يمكن تكراره.");
            }
        };
    }

    /**
     * Process salary disbursement, create Treasury transaction and Payroll payment record
     */
    public static function disburseSalary(
        PayrollRecord $record,
        float $amount,
        ?string $paymentDate = null,
        string $paymentMethod = 'cash',
        ?string $notes = null
    ): PayrollPayment {
        return DB::transaction(function () use ($record, $amount, $paymentDate, $paymentMethod, $notes) {
            $paymentDate = $paymentDate ?: now()->toDateString();
            $companyId = $record->company_id ?? auth()->user()?->company_id ?? $record->employee?->company_id;

            $isCollection = ($record->operation_type === 'collection');
            $mainCategory = $isCollection ? PaymentItemCategory::SALARY_COLLECTION : PaymentItemCategory::SALARY_PAYMENT;
            $subItemLabel = $isCollection
                ? __('finance.payment_collection_items.categories.salary_collection')
                : __('finance.payment_collection_items.categories.salary_payment');
            $txType = $isCollection ? TreasuryTransactionType::INCOMING : TreasuryTransactionType::OUTGOING;

            $item = PaymentCollectionItem::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'main_item'  => $mainCategory,
                ],
                [
                    'sub_item'      => $subItemLabel,
                    'service_price' => 0,
                ]
            );

            $employeeName = $record->employee?->user?->name ?? "#{$record->employee_id}";
            $monthFormatted = Carbon::parse($record->payroll_month)->format('Y-m');
            $txTitle = $isCollection ? "Salary Collection" : "Salary Payment";

            $treasuryTx = TreasuryTransaction::create([
                'company_id'                 => $companyId,
                'transaction_type'           => $txType,
                'payment_collection_item_id' => $item->id,
                'recipient_or_payer'         => $employeeName,
                'amount'                     => $amount,
                'description'                => "{$txTitle}: {$employeeName} ({$monthFormatted}) [{$record->record_number}]",
                'executed_by'                => auth()->user()?->employee?->id,
                'transaction_date'           => $paymentDate,
            ]);

            $payment = PayrollPayment::create([
                'payroll_record_id'       => $record->id,
                'company_id'              => $companyId,
                'employee_id'             => $record->employee_id,
                'payment_date'            => $paymentDate,
                'amount'                  => $amount,
                'payment_method'          => $paymentMethod,
                'treasury_transaction_id' => $treasuryTx->id,
                'executed_by'             => auth()->user()?->employee?->id,
                'notes'                   => $notes,
            ]);

            $totalPaid = (float) $record->payments()->sum('amount');
            $record->paid_amount = $totalPaid;
            $record->remaining_amount = round(max(0, (float)$record->net_amount - $totalPaid), 2);
            $record->payment_status = ($record->remaining_amount <= 0 && (float)$record->net_amount > 0) 
                ? PaymentStatus::PAID 
                : ($totalPaid > 0 ? PaymentStatus::PARTIALLY_PAID : PaymentStatus::UNPAID);
            $record->saveQuietly();

            Log::channel('payroll')->info("💵 [Salary Disbursed] Amount: {$amount} SAR for Employee: {$employeeName} (#{$record->employee_id}) - Payment: #{$payment->payment_number} | Treasury: #{$treasuryTx->transaction_number}");

            return $payment;
        });
    }
}
