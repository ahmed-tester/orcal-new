<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDailyAttendanceToPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $employeeId;
    public ?string $targetMonth;

    /**
     * Create a new job instance.
     *
     * @param int|null $employeeId لو null هيحسب لجميع الموظفين النشطين
     * @param string|null $targetMonth الشهر بصيغة 'Y-m' أو 'Y-m-d' (افتراضياً الشهر الحالي)
     */
    public function __construct(?int $employeeId = null, ?string $targetMonth = null)
    {
        $this->employeeId = $employeeId;
        $this->targetMonth = $targetMonth;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monthDate = Carbon::parse($this->targetMonth ?: now())->startOfMonth();
        $monthString = $monthDate->toDateString();

        $query = Employee::query()->where('is_active', true);
        if ($this->employeeId) {
            $query->where('id', $this->employeeId);
        }

        $totalEmployees = $query->count();
        $processedCount = 0;
        $failedCount = 0;

        Log::channel('payroll')->info("=== [بدء مزامنة الرواتب التراكمية] شهر: {$monthString} | عدد الموظفين المستهدفين: {$totalEmployees} ===");

        $query->chunk(50, function ($employees) use ($monthString, $monthDate, &$processedCount, &$failedCount) {
            foreach ($employees as $employee) {
                try {
                    $financials = PayrollCalculationService::calculateEmployeeFinancials(
                        $employee->id,
                        $monthString
                    );

                    if (empty($financials)) {
                        continue;
                    }

                    DB::transaction(function () use ($employee, $monthDate, $financials) {
                        $payrollRecord = PayrollRecord::firstOrNew([
                            'employee_id'   => $employee->id,
                            'payroll_month' => $monthDate->toDateString(),
                        ]);

                        $payrollRecord->company_id        = $employee->company_id;
                        $payrollRecord->basic_salary      = $financials['basic_salary'];
                        $payrollRecord->overtime_amount   = $financials['overtime_amount'];
                        $payrollRecord->absence_deduction = $financials['absence_deduction'];
                        $payrollRecord->rewards_amount    = $financials['rewards_amount'];
                        $payrollRecord->advance_deduction = $financials['advance_deduction'];

                        $payrollRecord->save();
                    });

                    $processedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::channel('payroll')->error("خطأ في راتب الموظف #{$employee->id} ({$employee->user?->name}): " . $e->getMessage());
                }
            }
        });

        Log::channel('payroll')->info("=== [اكتمال مزامنة الرواتب] تم بنجاح: {$processedCount} | فشل: {$failedCount} ===");
    }
}
