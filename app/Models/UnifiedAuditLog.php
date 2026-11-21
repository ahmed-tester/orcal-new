<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnifiedAuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'service_transaction_id',
        'user_id',
        'action_type',
        'old_status',
        'new_status',
        'payload',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UnifiedAuditLog $log) {
            if (empty($log->auditable_type) && ! empty($log->service_transaction_id)) {
                $log->auditable_type = ServiceTransaction::class;
                $log->auditable_id = $log->service_transaction_id;
            } elseif (! empty($log->auditable_type) && $log->auditable_type === ServiceTransaction::class && empty($log->service_transaction_id)) {
                $log->service_transaction_id = $log->auditable_id;
            }
        });

        // Strict immutability protection: records can never be updated or deleted
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ServiceTransaction::class, 'service_transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

