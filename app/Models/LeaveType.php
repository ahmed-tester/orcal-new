<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'annual_balance',
        'is_paid',
        'requires_approval',
        'status',
    ];

    protected $attributes = [
        'annual_balance' => 0,
        'is_paid' => true,
        'requires_approval' => true,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::saving(function (LeaveType $leaveType) {
            $leaveType->annual_balance = (int)($leaveType->annual_balance ?? 0);
        });
    }

    protected function casts(): array
    {
        return [
            'annual_balance' => 'integer',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function isDedicatedAnnual(): bool
    {
        return \App\Enums\AnnualLeaveType::DEDICATED_ANNUAL->matches($this->id);
    }
}
