<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashHandoverItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_handover_id',
        'financial_receipt_id',
        'company_id',
        'requested_amount',
        'confirmed_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'confirmed_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(CashHandover::class, 'cash_handover_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(FinancialReceipt::class, 'financial_receipt_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
