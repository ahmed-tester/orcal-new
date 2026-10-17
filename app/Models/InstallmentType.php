<?php

namespace App\Models;

use App\Enums\InstallmentCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category',
        'name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category' => InstallmentCategory::class,
            'status'   => \App\Enums\GeneralStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
