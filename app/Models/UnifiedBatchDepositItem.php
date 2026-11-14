<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnifiedBatchDepositItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_deposit_id',
        'depositable_type',
        'depositable_id',
        'service_transaction_id',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function depositable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(UnifiedBatchDeposit::class, 'batch_deposit_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ServiceTransaction::class, 'service_transaction_id');
    }
}

