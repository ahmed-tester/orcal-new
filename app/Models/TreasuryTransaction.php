<?php

namespace App\Models;


use App\Enums\TreasuryTransactionType;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Builder;
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

        });
    }

    protected function performInsert(Builder $query)
    {
        $connection = $this->getConnection();

        return $connection->transaction(function () use ($connection, $query) {
            if ($this->company_id) {
                $connection->table('companies')->where('id', $this->company_id)->lockForUpdate()->first();
            }

            $last = $this->newQuery()->where('company_id', $this->company_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $amount = round((float) $this->amount, 2);
            $signed = $this->transaction_type === TreasuryTransactionType::INCOMING ? $amount : -$amount;
            $this->balance_after = round((float) ($last?->balance_after ?? 0) + $signed, 2);

            return parent::performInsert($query);
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
