<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Owner extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'birth_date',
            'employment_date',
            'termination_date',
        ];
    }


    protected $fillable = [
        'user_id',
        'company_id',
        'nationality',
        'national_id',
        'birth_date',
        'employment_date',
        'termination_date',
        'image',
        'address',
        'ownership_percentage',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'employment_date' => 'date',
            'termination_date' => 'date',
            'ownership_percentage' => 'decimal:2',
            'status' => \App\Enums\GeneralStatus::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? "مالك #{$this->id}";
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getIdentityNumberAttribute(): ?string
    {
        return $this->national_id;
    }
}
