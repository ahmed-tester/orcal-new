<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\FundPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanFundMember extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'joined_date',
        ];
    }


    protected $fillable = [
        'loan_fund_id',
        'member_type',
        'employee_id',
        'full_name',
        'national_id',
        'phone',
        'address',
        'installments_count',
        'monthly_installment',
        'total_annual_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'joined_date',
        'status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'installments_count' => 'integer',
            'monthly_installment' => 'decimal:2',
            'total_annual_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'payment_status' => FundPaymentStatus::class,
            'joined_date' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanFundMember $member) {
            if (empty($member->joined_date)) {
                $member->joined_date = now()->toDateString();
            }

            if ($member->member_type === 'employee' && $member->employee_id) {
                $employee = Employee::find($member->employee_id);
                if ($employee) {
                    $member->full_name = $member->full_name ?: ($employee->user?->name ?? $employee->id);
                    $member->phone = $member->phone ?: ($employee->user?->phone ?? $employee->phone);
                    $member->national_id = $member->national_id ?: ($employee->saudiDetail?->national_id ?? $employee->foreignDetail?->iqama_number);
                }
            }
        });

        static::saving(function (LoanFundMember $member) {
            static::calculateTotals($member);
        });
    }

    public static function calculateTotals(LoanFundMember $member): void
    {
        $member->installments_count = max(1, (int)($member->installments_count ?: 12));
        $member->monthly_installment = (float)($member->monthly_installment ?? 0);
        $member->paid_amount = (float)($member->paid_amount ?? 0);

        $count = $member->installments_count;
        $monthly = $member->monthly_installment;
        $total = round($count * $monthly, 2);
        $paid = $member->paid_amount;

        $member->total_annual_amount = $total;
        $remaining = max(0, $total - $paid);
        $member->remaining_amount = round($remaining, 2);

        if ($paid >= $total && $total > 0) {
            $member->payment_status = FundPaymentStatus::PAID;
        } elseif ($paid > 0) {
            $member->payment_status = FundPaymentStatus::PARTIAL;
        } else {
            $member->payment_status = FundPaymentStatus::UNPAID;
        }
    }

    public function loanFund(): BelongsTo
    {
        return $this->belongsTo(LoanFund::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LoanFundLoan::class);
    }
}
