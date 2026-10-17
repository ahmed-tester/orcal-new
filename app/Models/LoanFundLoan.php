<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanFundLoan extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'loan_date',
            'start_due_date',
            'closure_date',
        ];
    }


    protected $fillable = [
        'loan_fund_id',
        'loan_fund_member_id',
        'loan_number',
        'loan_amount',
        'loan_date',
        'start_due_date',
        'installments_count',
        'monthly_installment',
        'paid_amount',
        'remaining_amount',
        'status',
        'is_closed',
        'closure_date',
        'approved_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'start_due_date' => 'date',
            'closure_date' => 'date',
            'loan_amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'installments_count' => 'integer',
            'is_closed' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanFundLoan $loan) {
            if (empty($loan->loan_number)) {
                $loan->loan_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'loan_number',
                    prefix: 'LN',
                    companyId: $loan->loanFund?->company_id,
                );
            }

            if (empty($loan->loan_date)) {
                $loan->loan_date = now()->toDateString();
            }

            if (empty($loan->start_due_date)) {
                $loan->start_due_date = now()->addMonth()->toDateString();
            }

            if (empty($loan->approved_by)) {
                $loan->approved_by = auth()->id();
            }
        });

        static::saving(function (LoanFundLoan $loan) {
            static::calculateLoan($loan);
        });
    }

    public static function calculateLoan(LoanFundLoan $loan): void
    {
        $loan->loan_amount = (float)($loan->loan_amount ?? 0);
        $loan->installments_count = max(1, (int)($loan->installments_count ?: 1));
        $loan->paid_amount = (float)($loan->paid_amount ?? 0);

        $amount = $loan->loan_amount;
        $count = $loan->installments_count;
        $paid = $loan->paid_amount;

        $loan->monthly_installment = round($amount / $count, 2);
        $remaining = max(0, $amount - $paid);
        $loan->remaining_amount = round($remaining, 2);

        if ($remaining <= 0 && $amount > 0) {
            $loan->status = 'completed';
            $loan->is_closed = true;
            if (empty($loan->closure_date)) {
                $loan->closure_date = now()->toDateString();
            }
        } elseif ($loan->is_closed) {
            $loan->status = 'completed';
        }
    }

    public function loanFund(): BelongsTo
    {
        return $this->belongsTo(LoanFund::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LoanFundMember::class, 'loan_fund_member_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanFundLoanPayment::class);
    }
}
