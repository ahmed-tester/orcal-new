<?php

namespace App\Models;

use App\Enums\ViolationTransactionType;
use App\Services\ViolationTransactionService;
use App\Traits\HasHijriDates;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViolationTransaction extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'transaction_date',
        ];
    }

    protected $fillable = [
        'voucher_number',
        'transaction_date',
        'company_id',
        'violation_id',
        'transaction_type',
        'amount',
        'description',
        'executed_by',
        'balance_after',
        'metadata',
        'link_type',
    ];

    public function getLinkTypeAttribute(): string
    {
        return $this->metadata['link_type'] ?? 'single';
    }

    public function setLinkTypeAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['link_type'] = $value ?? 'single';
        $this->metadata = $meta;
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount'           => 'decimal:2',
            'balance_after'    => 'decimal:2',
            'transaction_type' => ViolationTransactionType::class,
            'metadata'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Prevent deletion of violation treasury transactions
        static::deleting(fn () => false);

        static::creating(function (ViolationTransaction $transaction) {
            // Auto generate voucher number
            if (empty($transaction->voucher_number)) {
                $transaction->voucher_number = ViolationTransactionService::generateVoucherNumber($transaction->company_id);
            }

            // Validate multiple-violation amount against locked DB rows before insert
            ViolationTransactionService::validateMultipleSettlementAmount($transaction, false);

            // Calculate running balance
            if (empty($transaction->balance_after) || (float) $transaction->balance_after === 0.0) {
                $transaction->balance_after = ViolationTransactionService::calculateBalanceAfter($transaction);
            }
        });

        static::created(function (ViolationTransaction $transaction) {
            // Settle linked violation(s) once after the outgoing transaction is persisted
            ViolationTransactionService::settleLinkedViolations($transaction);
        });

        static::updating(function (ViolationTransaction $transaction) {
            // Validate multiple-violation amount against locked DB rows before update
            ViolationTransactionService::validateMultipleSettlementAmount($transaction, true);
        });

        static::updated(function (ViolationTransaction $transaction) {
            // Reverse old financial effects, apply new settlement, and recalculate balances
            ViolationTransactionService::syncUpdatedTransaction($transaction);
        });
    }

    /**
     * Settle linked violation(s) on transaction.
     */
    public function settleViolations(): void
    {
        ViolationTransactionService::settleLinkedViolations($this);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function violation(): BelongsTo
    {
        return $this->belongsTo(Violation::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
