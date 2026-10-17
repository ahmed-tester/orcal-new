<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'payroll_month'     => 'date',
            'record_date'       => 'date',
            'basic_salary'      => 'decimal:2',
            'overtime_amount'   => 'decimal:2',
            'rewards_amount'    => 'decimal:2',
            'additional_amount' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'deduction_amount'  => 'decimal:2',
            'net_amount'        => 'decimal:2',
            'paid_amount'       => 'decimal:2',
            'remaining_amount'  => 'decimal:2',
            'payment_status'    => PaymentStatus::class,
            'metadata'          => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollRecord $record) {
            if (empty($record->record_number)) {
                $record->record_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'record_number',
                    prefix: 'P',
                    companyId: $record->company_id,
                    padLength: 4,
                    datePart: 'Ym',
                );
            }

            if (empty($record->record_date)) {
                $record->record_date = now()->toDateString();
            }

            if (empty($record->company_id)) {
                $record->company_id = $record->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($record->created_by)) {
                $record->created_by = auth()->id();
            }
        });

        static::saving(function (PayrollRecord $record) {
            if (!empty($record->payroll_month)) {
                $record->payroll_month = \Carbon\Carbon::parse($record->payroll_month)->startOfMonth()->toDateString();
            }

            $record->basic_salary = (float)($record->basic_salary ?? 0);
            $record->overtime_amount = (float)($record->overtime_amount ?? 0);
            $record->rewards_amount = (float)($record->rewards_amount ?? 0);
            $record->advance_deduction = (float)($record->advance_deduction ?? 0);
            $record->absence_deduction = (float)($record->absence_deduction ?? 0);
            $record->paid_amount = (float)($record->paid_amount ?? 0);

            static::calculateTotals($record);
        });
    }

    public static function calculateTotals(PayrollRecord $record): void
    {
        $basic = (float)($record->basic_salary ?? 0);
        $overtime = (float)($record->overtime_amount ?? 0);
        $rewards = (float)($record->rewards_amount ?? 0);
        
        $record->additional_amount = round($overtime + $rewards, 2);

        $advance = (float)($record->advance_deduction ?? 0);
        $absence = (float)($record->absence_deduction ?? 0);
        $record->deduction_amount = round($advance + $absence, 2);

        $record->net_amount = round($basic + $record->additional_amount - $record->deduction_amount, 2);
        $paid = (float)($record->paid_amount ?? 0);
        $record->remaining_amount = round(max(0, $record->net_amount - $paid), 2);

        if ($paid <= 0) {
            $record->payment_status = PaymentStatus::UNPAID;
        } elseif ($paid >= $record->net_amount) {
            $record->payment_status = PaymentStatus::PAID;
        } else {
            $record->payment_status = PaymentStatus::PARTIALLY_PAID;
        }
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
