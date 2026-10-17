<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanFundLoanPayment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'installment_date',
        ];
    }


    protected $fillable = [
        'loan_fund_loan_id',
        'loan_fund_member_id',
        'voucher_number',
        'installment_order',
        'installment_date',
        'scheduled_amount',
        'collected_amount',
        'remaining_balance',
        'is_closed',
        'received_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'installment_date' => 'date',
            'scheduled_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'installment_order' => 'integer',
            'is_closed' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanFundLoanPayment $payment) {
            if (empty($payment->voucher_number)) {
                $payment->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'LP',
                    companyId: $payment->loan?->loanFund?->company_id,
                );
            }

            if (empty($payment->installment_date)) {
                $payment->installment_date = now()->toDateString();
            }

            if (empty($payment->received_by)) {
                $payment->received_by = auth()->id();
            }

            if ($payment->loan_fund_loan_id && empty($payment->loan_fund_member_id)) {
                $payment->loan_fund_member_id = $payment->loan?->loan_fund_member_id;
            }
        });

        static::saving(function (LoanFundLoanPayment $payment) {
            $payment->scheduled_amount = (float)($payment->scheduled_amount ?? 0);
            $payment->collected_amount = (float)($payment->collected_amount ?? 0);
            $payment->installment_order = max(1, (int)($payment->installment_order ?: 1));
            static::syncWithLoan($payment);
        });

        static::saved(function (LoanFundLoanPayment $payment) {
            if ($payment->loan) {
                $totalPaid = LoanFundLoanPayment::where('loan_fund_loan_id', $payment->loan_fund_loan_id)->sum('collected_amount');
                $loan = $payment->loan;
                $loan->paid_amount = $totalPaid;
                LoanFundLoan::calculateLoan($loan);
                $loan->saveQuietly();
            }
        });
    }

    public static function syncWithLoan(LoanFundLoanPayment $payment): void
    {
        if ($payment->loan) {
            $loan = $payment->loan;
            $currentPaid = LoanFundLoanPayment::where('loan_fund_loan_id', $loan->id)
                ->where('id', '!=', $payment->id ?? 0)
                ->sum('collected_amount');
            $newPaid = $currentPaid + (float)$payment->collected_amount;
            $remaining = max(0, (float)$loan->loan_amount - $newPaid);
            $payment->remaining_balance = round($remaining, 2);

            if ($remaining <= 0 && (float)$loan->loan_amount > 0) {
                $payment->is_closed = true;
            }
        }
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanFundLoan::class, 'loan_fund_loan_id');
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
