<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnifiedBatchDeposit extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'deposit_date',
        ];
    }

    protected $fillable = [
        'company_id',
        'deposit_number',
        'deposit_date',
        'bank_name',
        'reference_number',
        'total_amount',
        'receipt_path',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UnifiedBatchDeposit $deposit) {
            if (empty($deposit->deposit_number)) {
                $deposit->deposit_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'deposit_number',
                    prefix: 'BDEP',
                    companyId: $deposit->company_id,
                );
            }

            if (empty($deposit->deposit_date)) {
                $deposit->deposit_date = now()->toDateString();
            }

            if (empty($deposit->created_by)) {
                $deposit->created_by = auth()->id();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnifiedBatchDepositItem::class, 'batch_deposit_id');
    }
}

