<?php

namespace App\Models;

use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialReceipt extends Model
{
    use HasFactory;

    public const SOURCE_SERVICE_TRANSACTION = 'service_transaction';

    protected $fillable = [
        'company_id',
        'branch_id',
        'source_type',
        'source_id',
        'receipt_number',
        'request_key',
        'collector_employee_id',
        'payer_name',
        'amount',
        'payment_method',
        'status',
        'reference_number',
        'proof_path',
        'destination_type',
        'destination_reference',
        'confirmed_by',
        'confirmed_at',
        'received_at',
        'notes',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => FinancialPaymentMethod::class,
            'status' => FinancialReceiptStatus::class,
            'received_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancialReceipt $receipt) {
            if (empty($receipt->receipt_number)) {
                $receipt->receipt_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'receipt_number',
                    prefix: 'RCP',
                    companyId: $receipt->company_id,
                );
            }

            if (empty($receipt->received_at)) {
                $receipt->received_at = now();
            }

            if (empty($receipt->status)) {
                $receipt->status = FinancialReceiptStatus::PENDING_CONFIRMATION->value;
            }
        });
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', FinancialReceiptStatus::CONFIRMED->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FinancialReceiptStatus::PENDING_CONFIRMATION->value);
    }

    public function scopeCash(Builder $query): Builder
    {
        return $query->where('payment_method', FinancialPaymentMethod::CASH->value);
    }

    public function scopeForSource(Builder $query, string $sourceType, int $sourceId): Builder
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'collector_employee_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function handoverItems(): HasMany
    {
        return $this->hasMany(CashHandoverItem::class);
    }

    /**
     * Amount already reserved by a handover request that is still waiting.
     */
    public function reservedAmount(): float
    {
        return (float) $this->handoverItems()
            ->whereHas('handover', fn (Builder $query) => $query->where('status', FinancialHandoverStatus::SUBMITTED->value))
            ->sum('requested_amount');
    }

    /**
     * Amount actually received in the treasury through confirmed handovers.
     */
    public function handedOverAmount(): float
    {
        if ($this->isAllocatedTreasuryCash()) {
            return (float) $this->amount;
        }

        return (float) $this->handoverItems()
            ->whereHas('handover', fn (Builder $query) => $query->where('status', FinancialHandoverStatus::CONFIRMED->value))
            ->sum('confirmed_amount');
    }

    public function availableForHandover(): float
    {
        if ($this->status !== FinancialReceiptStatus::CONFIRMED || ! $this->payment_method->isCash()) {
            return 0.0;
        }

        return max(0.0, (float) $this->amount - $this->reservedAmount() - $this->handedOverAmount());
    }

    public function isCash(): bool
    {
        return $this->payment_method === FinancialPaymentMethod::CASH;
    }

    public function isAllocatedTreasuryCash(): bool
    {
        return $this->isCash() && isset($this->metadata['cash_handover_id']);
    }
}
