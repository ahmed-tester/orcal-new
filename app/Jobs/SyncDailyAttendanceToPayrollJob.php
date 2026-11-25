<?php

namespace App\Jobs;

use App\Enums\GeneralStatus;
use App\Models\Employee;
use App\Services\Payroll\PayrollEntitlementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDailyAttendanceToPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $employeeId;

    public ?string $targetMonth;

    /**
     * Create a new job instance.
     *
     * @param  int|null  $employeeId  لو null هيحسب لجميع الموظفين النشطين
     * @param  string|null  $targetMonth  الشهر بصيغة 'Y-m' أو 'Y-m-d' (افتراضياً الشهر الحالي)
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

        $query = Employee::query()->where('status', GeneralStatus::ACTIVE);
        if ($this->employeeId) {
            $query->where('id', $this->employeeId);
        }

        $totalEmployees = $query->count();
        $processedCount = 0;
        $failedCount = 0;

        Log::channel('payroll')->info("=== [بدء مزامنة الرواتب التراكمية] شهر: {$monthString} | عدد الموظفين المستهدفين: {$totalEmployees} ===");

        $query->chunk(50, function ($employees) use ($monthString, $monthDate, &$processedCount, &$failedCount) {
            foreach ($employees as $employee) {
                if (empty($employee->company_id)) {
                    continue;
                }

                try {
                    $synced = $this->syncEmployeePayrollRecord($employee, $monthDate->toDateString(), $monthString);

                    if ($synced) {
                        $processedCount++;
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::channel('payroll')->error("خطأ في راتب الموظف #{$employee->id} ({$employee->user?->name}): ".$e->getMessage());
                }
            }
        });

        Log::channel('payroll')->info("=== [اكتمال مزامنة الرواتب] تم بنجاح: {$processedCount} | فشل: {$failedCount} ===");
    }

    private function syncEmployeePayrollRecord(Employee $employee, string $monthDateString, string $monthString): bool
    {
        try {
            return $this->upsertLockedPayrollRecord($employee, $monthDateString, $monthString);
        } catch (QueryException $e) {
            if (! $this->isDuplicateConstraintException($e)) {
                throw $e;
            }

            return $this->upsertLockedPayrollRecord($employee, $monthDateString, $monthString);
        }
    }

    private function upsertLockedPayrollRecord(Employee $employee, string $monthDateString, string $monthString): bool
    {
        return PayrollEntitlementService::upsertMonthlyPayroll($employee->id, $monthDateString, [
            'explicit_recalculate' => false,
            'skip_if_financially_impacted' => true,
        ]) !== null;
    }

    private function isDuplicateConstraintException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062 || $driverCode === 19;
    }
}
