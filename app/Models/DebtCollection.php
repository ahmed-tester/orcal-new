<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Traits\HasApprovalStatus;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtCollection extends Model
{
    use HasApprovalStatus, HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'collection_date',
        ];
    }

    protected $fillable = [
        'voucher_number',
        'debt_id',
        'company_id',
        'employee_id',
        'collection_date',
        'amount',
        'payment_method',
        'treasury_transaction_id',
        'description',
        'executed_by',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DebtCollection $collection) {
            if ($collection->debt) {
                if (empty($collection->company_id)) {
                    $collection->company_id = $collection->debt->company_id;
                }
                if (empty($collection->employee_id)) {
                    $collection->employee_id = $collection->debt->employee_id;
                }
            }

            if (empty($collection->company_id)) {
                $collection->company_id = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($collection->voucher_number)) {
                $collection->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'REC-DBT',
                    companyId: $collection->company_id,
                );
            }

            if (empty($collection->collection_date)) {
                $collection->collection_date = now()->toDateString();
            }

            if (empty($collection->executed_by)) {
                $collection->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saved(function (DebtCollection $collection) {
            $collection->debt?->recalculateAndSave();
        });

        static::deleted(function (DebtCollection $collection) {
            $collection->debt?->recalculateAndSave();
        });
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }
}
