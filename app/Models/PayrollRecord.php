<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Observers\PayrollRecordObserver;
use App\Services\PayrollCalculationService;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([PayrollRecordObserver::class])]
class PayrollRecord extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'payroll_month',
            'record_date',
        ];
    }

    protected $fillable = [
        'record_number',
        'company_id',
        'employee_id',
        'operation_type',
        'payroll_month',
        'record_date',
        'basic_salary',
        'overtime_amount',
        'rewards_amount',
        'additional_amount',
        'advance_deduction',
        'absence_deduction',
        'deduction_amount',
        'net_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payroll_month' => 'date',
            'record_date' => 'date',
            'basic_salary' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'rewards_amount' => 'decimal:2',
            'additional_amount' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'metadata' => 'array',
        ];
    }

    public static function calculateTotals(PayrollRecord $record): void
    {
        PayrollCalculationService::syncTotalsForRecord($record);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }
}
