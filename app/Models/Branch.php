<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'branch_code',
        'address',
        'phone',
        'email',
        'attendance_settings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'attendance_settings' => 'array',
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (blank($branch->branch_code)) {
                $branch->branch_code = static::generateNextBranchCode($branch->company_id);
            }
        });
    }

    public static function generateNextBranchCode(?int $companyId = null): string
    {
        return \App\Services\CodeGeneratorService::generate(
            modelClass: static::class,
            column: 'branch_code',
            prefix: 'BR',
            companyId: $companyId,
            padLength: 2,
        );
    }
}
