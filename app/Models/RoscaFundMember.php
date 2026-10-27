<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoscaFundMember extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'payout_month',
            'payout_date',
        ];
    }


    protected $fillable = [
        'rosca_fund_id',
        'member_type',
        'employee_id',
        'full_name',
        'national_id',
        'phone',
        'address',
        'turn_order',
        'payout_month',
        'installments_count',
        'monthly_installment',
        'total_final_amount',
        'paid_amount',
        'remaining_amount',
        'has_received_payout',
        'payout_date',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'turn_order' => 'integer',
            'installments_count' => 'integer',
            'monthly_installment' => 'decimal:2',
            'total_final_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'payout_month' => 'date',
            'payout_date' => 'date',
            'has_received_payout' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoscaFundMember $member) {
            if ($member->member_type === 'employee' && $member->employee_id) {
                $employee = Employee::find($member->employee_id);
                if ($employee) {
                    $member->full_name = $member->full_name ?: ($employee->user?->name ?? $employee->id);
                    $member->phone = $member->phone ?: ($employee->user?->phone ?? $employee->phone);
                    // $member->national_id = $member->national_id ?: ($employee->saudiDetail?->national_id ?? $employee->foreignDetail?->iqama_number);
                    $member->national_id = $member->national_id ?: ($employee->details?->national_id ?? $employee->saudiDetail?->national_id);
                }
            }

            if ($member->roscaFund) {
                if (empty($member->installments_count)) {
                    $member->installments_count = $member->roscaFund->members_count;
                }
                if (empty($member->monthly_installment)) {
                    $member->monthly_installment = $member->roscaFund->monthly_installment;
                }
            }
        });

        static::saving(function (RoscaFundMember $member) {
            static::calculateMemberTotals($member);
        });
    }

    public static function calculateMemberTotals(RoscaFundMember $member): void
    {
        $member->installments_count = max(1, (int)($member->installments_count ?: 1));
        $member->monthly_installment = (float)($member->monthly_installment ?? 0);
        $member->paid_amount = (float)($member->paid_amount ?? 0);

        $count = $member->installments_count;
        $monthly = $member->monthly_installment;
        $total = round($count * $monthly, 2);
        $paid = $member->paid_amount;

        $member->total_final_amount = $total;
        $remaining = max(0, $total - $paid);
        $member->remaining_amount = round($remaining, 2);
    }

    public function roscaFund(): BelongsTo
    {
        return $this->belongsTo(RoscaFund::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RoscaFundPayment::class);
    }
}
