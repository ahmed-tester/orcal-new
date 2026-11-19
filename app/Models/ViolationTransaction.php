<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationTransactionType;
use App\Services\CodeGeneratorService;
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
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Prevent deletion of violation treasury transactions
        static::deleting(function (ViolationTransaction $transaction) {
            return false;
        });

        static::creating(function (ViolationTransaction $transaction) {
            // Auto generate voucher number
            if (empty($transaction->voucher_number)) {
                $transaction->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'VT',
                    companyId: $transaction->company_id,
                    padLength: 4,
                );
            }

            // Calculate running balance
            if (empty($transaction->balance_after) || (float) $transaction->balance_after === 0.0) {
                $lastTransaction = static::where('company_id', $transaction->company_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $lastTransaction ? (float) $lastTransaction->balance_after : 0.0;
                $currentAmount   = (float) $transaction->amount;
                $transType       = $transaction->transaction_type instanceof ViolationTransactionType
                    ? $transaction->transaction_type
                    : ViolationTransactionType::tryFrom($transaction->transaction_type);

                if ($transType === ViolationTransactionType::INCOMING) {
                    $transaction->balance_after = $previousBalance + $currentAmount;
                } else {
                    $transaction->balance_after = $previousBalance - $currentAmount;
                }
            }

            // Update violation paid and remaining amounts on transaction
            $transType = $transaction->transaction_type instanceof ViolationTransactionType
                ? $transaction->transaction_type
                : ViolationTransactionType::tryFrom($transaction->transaction_type);

            if ($transType === ViolationTransactionType::OUTGOING) {
                $closureDate = $transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date)->toDateString() : now()->toDateString();

                // 1. Multiple violations linked
                $selectedIds = $transaction->metadata['selected_violation_ids'] ?? [];
                if (! empty($selectedIds) && is_array($selectedIds)) {
                    $violations = Violation::whereIn('id', $selectedIds)->get();
                    foreach ($violations as $v) {
                        $v->paid_amount = (float) $v->violation_amount;
                        $v->remaining_amount = 0.0;
                        $v->payment_status = ViolationPaymentStatus::PAID;
                        $v->closure_date = $closureDate;
                        $v->save();
                    }
                } elseif ($transaction->violation_id) {
                    // 2. Single violation linked
                    $violation = Violation::find($transaction->violation_id);
                    if ($violation) {
                        $newPaid = (float) $violation->paid_amount + (float) $transaction->amount;
                        $violation->paid_amount = $newPaid;
                        $violation->remaining_amount = max(0.0, (float) $violation->violation_amount - $newPaid);

                        if ($violation->remaining_amount <= 0) {
                            $violation->payment_status = ViolationPaymentStatus::PAID;
                            $violation->closure_date = $closureDate;
                        } else {
                            $violation->payment_status = ViolationPaymentStatus::PARTIAL;
                        }
                        $violation->save();
                    }
                }
            }
        });
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
