<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialDisbursementPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_disbursement_id',
        'company_id',
        'branch_id',
        'payment_number',
        'request_key',
        'amount',
        'treasury_transaction_id',
        'paid_by',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancialDisbursementPayment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'payment_number',
                    prefix: 'RMB',
                    companyId: $payment->company_id,
                );
            }

            if (empty($payment->paid_at)) {
                $payment->paid_at = now();
            }
        });
    }

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(FinancialDisbursement::class, 'financial_disbursement_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isReversed(): bool
    {
        return ! empty($this->metadata['reversed_at']);
    }
}
