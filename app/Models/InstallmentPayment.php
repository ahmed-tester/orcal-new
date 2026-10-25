<?php

namespace App\Models;

use App\Enums\InstallmentPaymentSource;
use App\Services\CodeGeneratorService;
use App\Traits\HasApprovalStatus;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasApprovalStatus, HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'payment_date',
        ];
    }

    protected $fillable = [
        'voucher_number',
        'company_id',
        'employee_id',
        'installment_id',
        'payment_type',
        'installment_schedule_id',
        'payment_date',
        'amount',
        'payment_source',
        'treasury_transaction_id',
        'payroll_record_id',
        'description',
        'executed_by',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_source' => InstallmentPaymentSource::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InstallmentPayment $payment) {
            if ($payment->installment) {
                if (empty($payment->payment_type)) {
                    $payment->payment_type = $payment->installment->transaction_type ?? 'installment';
                }
                if (empty($payment->company_id)) {
                    $payment->company_id = $payment->installment->company_id;
                }
                if (empty($payment->employee_id)) {
                    $payment->employee_id = $payment->installment->employee_id;
                }
            }

            if (empty($payment->company_id)) {
                $payment->company_id = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($payment->voucher_number)) {
                $prefix = ($payment->payment_type === 'advance') ? 'PADV' : 'P';
                $payment->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: $prefix,
                    companyId: $payment->company_id,
                );
            }

            if (empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }

            if (empty($payment->executed_by)) {
                $payment->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saving(function (InstallmentPayment $payment) {
            $payment->amount = (float) ($payment->amount ?? 0);
        });

        static::saved(function (InstallmentPayment $payment) {
            $installment = $payment->installment;
            if ($installment) {
                $totalPaid = $installment->payments()->sum('amount');
                $installment->paid_amount = $totalPaid;
                $installment->save();
            }

            $schedule = $payment->schedule;
            if ($schedule) {
                $schedulePaid = $schedule->payments()->sum('amount');
                $schedule->paid_amount = $schedulePaid;
                $schedule->remaining_amount = max(0, (float) $schedule->amount - $schedulePaid);
                if ($schedule->remaining_amount <= 0) {
                    $schedule->status = 'paid';
                } elseif ($schedulePaid > 0) {
                    $schedule->status = 'partial';
                }
                $schedule->save();
            }
        });

        static::deleted(function (InstallmentPayment $payment) {
            $installment = $payment->installment;
            if ($installment) {
                $totalPaid = $installment->payments()->sum('amount');
                $installment->paid_amount = $totalPaid;
                $installment->save();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }
}
