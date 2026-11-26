<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialApprovalRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'version',
        'action',
        'actor_id',
        'actor_role',
        'from_status',
        'to_status',
        'reason',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancialApprovalRequest::class, 'request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
