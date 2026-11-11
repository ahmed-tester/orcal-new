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
        'name_en',
        'monthly_deduction_percentage',
        'owner_advances',
        'debts',
        'other_financials',
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

    public function getNameEnAttribute(): ?string
    {
        return $this->metadata['name_en'] ?? null;
    }

    public function setNameEnAttribute(?string $value): void
    {
        $meta = is_array($this->metadata) ? $this->metadata : [];
        if ($value !== null && trim($value) !== '') {
            $meta['name_en'] = trim($value);
        } else {
            unset($meta['name_en']);
        }
        $this->metadata = $meta;
    }

    public function getMonthlyDeductionPercentageAttribute(): ?float
    {
        return isset($this->metadata['monthly_deduction_percentage'])
            ? (float) $this->metadata['monthly_deduction_percentage']
            : null;
    }

    public function setMonthlyDeductionPercentageAttribute($value): void
    {
        $meta = $this->metadata ?? [];
        if ($value !== null && $value !== '') {
            $meta['monthly_deduction_percentage'] = (float) $value;
        } else {
            unset($meta['monthly_deduction_percentage']);
        }
        $this->metadata = $meta;
    }

    public function getOwnerAdvancesAttribute(): ?float
    {
        return isset($this->metadata['owner_advances'])
            ? (float) $this->metadata['owner_advances']
            : null;
    }

    public function setOwnerAdvancesAttribute($value): void
    {
        $meta = $this->metadata ?? [];
        if ($value !== null && $value !== '') {
            $meta['owner_advances'] = (float) $value;
        } else {
            unset($meta['owner_advances']);
        }
        $this->metadata = $meta;
    }

    public function getDebtsAttribute(): ?float
    {
        return isset($this->metadata['debts'])
            ? (float) $this->metadata['debts']
            : null;
    }

    public function setDebtsAttribute($value): void
    {
        $meta = $this->metadata ?? [];
        if ($value !== null && $value !== '') {
            $meta['debts'] = (float) $value;
        } else {
            unset($meta['debts']);
        }
        $this->metadata = $meta;
    }

    public function getOtherFinancialsAttribute(): ?string
    {
        return $this->metadata['other_financials'] ?? null;
    }

    public function setOtherFinancialsAttribute($value): void
    {
        $meta = $this->metadata ?? [];
        if ($value !== null && $value !== '') {
            $meta['other_financials'] = is_string($value) ? trim($value) : (string) $value;
        } else {
            unset($meta['other_financials']);
        }
        $this->metadata = $meta;
    }
}
