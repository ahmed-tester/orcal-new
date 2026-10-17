<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'beneficiary_name',
        'target_type',
        'category',
        'price',
        'default_gov_fee',
        'status',
        'is_system',
        'notes',
    ];

    protected $attributes = [
        'target_type' => 'general',
        'category'    => 'other',
        'price'       => 0,
        'default_gov_fee' => 0,
        'status'      => 'active',
        'is_system'   => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            $service->price = (float)($service->price ?? 0);
            $service->default_gov_fee = (float)($service->default_gov_fee ?? 0);
        });
    }

    protected function casts(): array
    {
        return [
            'price'           => 'decimal:2',
            'default_gov_fee' => 'decimal:2',
            'status'          => \App\Enums\GeneralStatus::class,
            'is_system'       => 'boolean',
        ];
    }

    public function scopeForCompanies(Builder $query): Builder
    {
        return $query->whereIn('target_type', ['company', 'general']);
    }

    public function scopeForEmployees(Builder $query): Builder
    {
        return $query->whereIn('target_type', ['employee', 'general']);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ServiceTransaction::class);
    }
}
