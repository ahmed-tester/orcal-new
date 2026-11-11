<?php

namespace App\Models;

use App\Enums\FinancialHandoverStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'handover_number',
        'request_key',
        'confirmation_key',
        'collector_employee_id',
        'delivered_by_employee_id',
        'received_by',
        'total_requested_amount',
        'total_confirmed_amount',
        'difference_amount',
        'status',
        'treasury_transaction_id',
        'requested_at',
        'confirmed_at',
        'notes',
        'rejection_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => FinancialHandoverStatus::class,
            'total_requested_amount' => 'decimal:2',
            'total_confirmed_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CashHandover $handover) {
            if (empty($handover->handover_number)) {
                $handover->handover_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'handover_number',
                    prefix: 'HND',
                    companyId: $handover->company_id,
                );
            }

            if (empty($handover->requested_at)) {
                $handover->requested_at = now();
            }

            if (empty($handover->status)) {
                $handover->status = FinancialHandoverStatus::SUBMITTED->value;
            }
        });
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', FinancialHandoverStatus::SUBMITTED->value);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', FinancialHandoverStatus::CONFIRMED->value);
    }

    public function scopeAccessibleBy(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);

        if ($companyIds === [] || $companyIds === null) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('company_id', $companyIds);

        if ($user->branch_id && ! $user->canAccessAllBranches()) {
            $query->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $user->branch_id));
        }

        return $query;
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

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delivered_by_employee_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashHandoverItem::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === FinancialHandoverStatus::SUBMITTED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === FinancialHandoverStatus::CONFIRMED;
    }
}
