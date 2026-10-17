<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransaction extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'transaction_date',
        ];
    }


    protected $fillable = [
        'transaction_number',
        'voucher_number',
        'transaction_date',
        'company_id',
        'transaction_type',
        'payment_collection_item_id',
        'recipient_or_payer',
        'amount',
        'description',
        'executed_by',
        'balance_after',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount'           => 'decimal:2',
            'balance_after'    => 'decimal:2',
            'transaction_type' => \App\Enums\TreasuryTransactionType::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // منع حذف أي حركة من الخزينة نهائياً لحماية الأرصدة التراكمية والتسلسل المالي
        static::deleting(function (TreasuryTransaction $transaction) {
            return false;
        });

        static::saving(function (TreasuryTransaction $transaction) {
            $transaction->amount = (float)($transaction->amount ?? 0);
        });

        static::creating(function (TreasuryTransaction $transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'transaction_number',
                    prefix: 'T',
                    companyId: $transaction->company_id,
                    padLength: 5,
                );
            }

            if (empty($transaction->voucher_number)) {
                $transaction->voucher_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'R',
                    companyId: $transaction->company_id,
                    padLength: 4,
                );
            }

            // Calculate running balance for the company treasury
            if (empty($transaction->balance_after) || $transaction->balance_after == 0) {
                $companyId = $transaction->company_id;
                $lastTransaction = static::where('company_id', $companyId)
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $lastTransaction ? (float) $lastTransaction->balance_after : 0.0;
                $currentAmount = (float) $transaction->amount;

                $isIncoming = ($transaction->transaction_type instanceof \App\Enums\TreasuryTransactionType)
                    ? $transaction->transaction_type === \App\Enums\TreasuryTransactionType::INCOMING
                    : ($transaction->transaction_type === 'incoming');

                if ($isIncoming) {
                    $transaction->balance_after = $previousBalance + $currentAmount;
                } else {
                    $transaction->balance_after = $previousBalance - $currentAmount;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentCollectionItem(): BelongsTo
    {
        return $this->belongsTo(PaymentCollectionItem::class, 'payment_collection_item_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }
}
