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

            // Calculate running balance
            if (empty($transaction->balance_after) || (float) $transaction->balance_after === 0.0) {
                $transaction->balance_after = ViolationTransactionService::calculateBalanceAfter($transaction);
            }

            // Settle linked violation(s)
            ViolationTransactionService::settleLinkedViolations($transaction);
        });

        static::saved(function (ViolationTransaction $transaction) {
            // Ensure multiple selected violations are marked paid when saved
            $selectedIds = $transaction->metadata['selected_violation_ids'] ?? [];
            if (! empty($selectedIds) && is_array($selectedIds)) {
                ViolationTransactionService::settleMultipleViolations(
                    $selectedIds,
                    $transaction->transaction_date ? Carbon::parse($transaction->transaction_date)->toDateString() : null
                );
            }
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
