<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPayment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'payment_date',
        ];
    }


    protected $fillable = [
        'payment_number',
        'payroll_record_id',
        'company_id',
        'employee_id',
        'payment_date',
        'amount',
        'payment_method',
        'treasury_transaction_id',
        'executed_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount'       => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollPayment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'payment_number',
                    prefix: 'PAY',
                    companyId: $payment->company_id,
                    padLength: 4,
                    datePart: 'Ym',
                );
            }

            if (empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }

            if (empty($payment->executed_by)) {
                $payment->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saved(function (PayrollPayment $payment) {
            $record = $payment->payrollRecord;
            if ($record) {
                $totalPaid = $record->payments()->sum('amount');
                $record->paid_amount = $totalPaid;
                $record->save();
            }
        });

        static::deleted(function (PayrollPayment $payment) {
            $record = $payment->payrollRecord;
            if ($record) {
                $totalPaid = $record->payments()->sum('amount');
                $record->paid_amount = $totalPaid;
                $record->save();
            }
        });
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }
}
