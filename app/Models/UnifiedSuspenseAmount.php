<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnifiedSuspenseAmount extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'received_date',
        ];
    }

    protected $fillable = [
        'company_id',
        'suspense_number',
        'amount',
        'received_date',
        'bank_name',
        'reference_number',
        'depositor_name',
        'receipt_path',
        'status',
        'allocatable_type',
        'allocatable_id',
        'allocated_service_transaction_id',
        'allocated_at',
        'allocated_by',
        'approved_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'amount' => 'decimal:2',
            'allocated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UnifiedSuspenseAmount $suspense) {
            if (empty($suspense->suspense_number)) {
                $suspense->suspense_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'suspense_number',
                    prefix: 'SUSP',
                    companyId: $suspense->company_id,
                );
            }

            if (empty($suspense->received_date)) {
                $suspense->received_date = now()->toDateString();
            }

            if (empty($suspense->status)) {
                $suspense->status = 'pending';
            }
        });
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allocatedTransaction(): BelongsTo
    {
        return $this->belongsTo(ServiceTransaction::class, 'allocated_service_transaction_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

