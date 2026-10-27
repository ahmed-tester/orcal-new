<?php

namespace App\Models;

use App\Observers\PayrollPaymentObserver;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PayrollPaymentObserver::class])]
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
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
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
