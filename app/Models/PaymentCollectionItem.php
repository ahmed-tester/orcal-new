<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'company_id',
        'main_item',
        'sub_item',
        'service_price',
    ];

    protected $attributes = [
        'service_price' => 0,
    ];

    protected function casts(): array
    {
        return [
            'main_item'     => \App\Enums\PaymentItemCategory::class,
            'service_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentCollectionItem $item) {
            if (empty($item->company_id)) {
                $user = auth()->user();
                $item->company_id = $user?->company_id ?? $user?->employee?->company_id;
            }

            if (empty($item->transaction_number)) {
                $item->transaction_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'transaction_number',
                    prefix: 'I',
                    companyId: $item->company_id,
                );
            }
        });
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class, 'payment_collection_item_id');
    }

    public function getUsageCountAttribute(): int
    {
        return $this->treasuryTransactions()->count();
    }
}
