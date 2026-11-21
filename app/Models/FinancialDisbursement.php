<?php

namespace App\Models;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialDisbursement extends Model
{
    use HasFactory;

    public const SOURCE_SERVICE_TRANSACTION = 'service_transaction';

    protected $fillable = [
        'company_id',
        'branch_id',
        'source_type',
        'source_id',
        'disbursement_number',
        'request_key',
        'disbursement_type',
        'amount',
        'beneficiary_name',
        'reference_number',
        'receipt_proof',
        'status',
        'paid_by_employee_id',
        'approved_by',
        'approved_at',
        'treasury_transaction_id',
        'notes',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'disbursement_type' => FinancialDisbursementType::class,
            'status' => FinancialDisbursementStatus::class,
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancialDisbursement $disbursement) {
            if (empty($disbursement->disbursement_number)) {
                $disbursement->disbursement_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'disbursement_number',
                    prefix: 'DSB',
                    companyId: $disbursement->company_id,
                );
            }
        });
    }

    public function scopeOfType(Builder $query, FinancialDisbursementType $type): Builder
    {
        return $query->where('disbursement_type', $type->value);
    }

    public function scopeForSource(Builder $query, string $sourceType, int $sourceId): Builder
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }

    public function scopeOpenClaims(Builder $query): Builder
    {
        return $query->whereIn('status', [
            FinancialDisbursementStatus::PENDING_APPROVAL->value,
            FinancialDisbursementStatus::APPROVED->value,
            FinancialDisbursementStatus::PARTIALLY_REIMBURSED->value,
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paidByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'paid_by_employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancialDisbursementPayment::class);
    }

    public function isReversed(): bool
    {
        return ! empty($this->metadata['reversed_at']);
    }

    /**
     * Sum of valid (non reversed) reimbursement payments.
     */
    public function reimbursedTotal(): float
    {
        return (float) $this->payments
            ->filter(fn (FinancialDisbursementPayment $payment) => ! $payment->isReversed())
            ->sum(fn (FinancialDisbursementPayment $payment) => (float) $payment->amount);
    }

    public function remainingDue(): float
    {
        return max(0.0, (float) $this->amount - $this->reimbursedTotal());
    }

    /**
     * Confirmed cost charged to the government beneficiary, excluding
     * reimbursements (they are employee credit, not a second payment).
     */
    public function settledCost(): float
    {
        if ($this->isReversed()) {
            return 0.0;
        }

        return (float) $this->amount;
    }
}
