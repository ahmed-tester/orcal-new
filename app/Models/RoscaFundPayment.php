<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\FundPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoscaFundPayment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'period_month',
            'payment_date',
        ];
    }


    protected $fillable = [
        'rosca_fund_id',
        'rosca_fund_member_id',
        'period_month',
        'installment_order',
        'scheduled_amount',
        'paid_amount',
        'payment_status',
        'is_bond_closed',
        'payment_date',
        'received_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'payment_date' => 'date',
            'scheduled_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'installment_order' => 'integer',
            'payment_status' => FundPaymentStatus::class,
            'is_bond_closed' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoscaFundPayment $payment) {
            if ($payment->rosca_fund_member_id && empty($payment->rosca_fund_id)) {
                $payment->rosca_fund_id = $payment->member?->rosca_fund_id;
            }

            if (empty($payment->received_by)) {
                $payment->received_by = auth()->id();
            }

            if (empty($payment->scheduled_amount) && $payment->member) {
                $payment->scheduled_amount = $payment->member->monthly_installment;
            }
        });

        static::saving(function (RoscaFundPayment $payment) {
            $payment->scheduled_amount = (float)($payment->scheduled_amount ?? 0);
            $payment->paid_amount = (float)($payment->paid_amount ?? 0);
            $payment->installment_order = max(1, (int)($payment->installment_order ?: 1));
            static::determineStatus($payment);
        });

        static::saved(function (RoscaFundPayment $payment) {
            if ($payment->member) {
                $totalPaid = RoscaFundPayment::where('rosca_fund_member_id', $payment->rosca_fund_member_id)->sum('paid_amount');
                $member = $payment->member;
                $member->paid_amount = $totalPaid;
                RoscaFundMember::calculateMemberTotals($member);
                $member->saveQuietly();
            }
        });
    }

    public static function determineStatus(RoscaFundPayment $payment): void
    {
        $scheduled = (float)($payment->scheduled_amount ?? 0);
        $paid = (float)($payment->paid_amount ?? 0);

        if ($paid >= $scheduled && $scheduled > 0) {
            $payment->payment_status = FundPaymentStatus::PAID;
            if (empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }
        } elseif ($paid > 0) {
            $payment->payment_status = FundPaymentStatus::PARTIAL;
        } else {
            $payment->payment_status = FundPaymentStatus::UNPAID;
        }
    }

    public function roscaFund(): BelongsTo
    {
        return $this->belongsTo(RoscaFund::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(RoscaFundMember::class, 'rosca_fund_member_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
