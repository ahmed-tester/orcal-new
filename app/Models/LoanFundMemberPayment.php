<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\FundPaymentStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanFundMemberPayment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'payment_date',
            'period_month',
        ];
    }


    protected $fillable = [
        'loan_fund_id',
        'loan_fund_member_id',
        'voucher_number',
        'payment_date',
        'period_month',
        'installment_order',
        'amount',
        'payment_method',
        'payment_status',
        'received_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'period_month' => 'date',
            'amount' => 'decimal:2',
            'installment_order' => 'integer',
            'payment_status' => FundPaymentStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanFundMemberPayment $payment) {
            if (empty($payment->voucher_number)) {
                $payment->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'SUB',
                    companyId: $payment->loanFund?->company_id,
                );
            }

            if (empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }

            if (empty($payment->received_by)) {
                $payment->received_by = auth()->id();
            }

            if ($payment->loan_fund_member_id && empty($payment->loan_fund_id)) {
                $payment->loan_fund_id = $payment->member?->loan_fund_id;
            }
        });

        static::saving(function (LoanFundMemberPayment $payment) {
            $payment->amount = (float)($payment->amount ?? 0);
            $payment->installment_order = max(1, (int)($payment->installment_order ?: 1));
        });

        static::saved(function (LoanFundMemberPayment $payment) {
            if ($payment->member) {
                $totalPaid = LoanFundMemberPayment::where('loan_fund_member_id', $payment->loan_fund_member_id)->sum('amount');
                $member = $payment->member;
                $member->paid_amount = $totalPaid;
                LoanFundMember::calculateTotals($member);
                $member->saveQuietly();
            }
        });

        static::deleted(function (LoanFundMemberPayment $payment) {
            if ($payment->member) {
                $totalPaid = LoanFundMemberPayment::where('loan_fund_member_id', $payment->loan_fund_member_id)->sum('amount');
                $member = $payment->member;
                $member->paid_amount = $totalPaid;
                LoanFundMember::calculateTotals($member);
                $member->saveQuietly();
            }
        });
    }

    public function loanFund(): BelongsTo
    {
        return $this->belongsTo(LoanFund::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LoanFundMember::class, 'loan_fund_member_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
