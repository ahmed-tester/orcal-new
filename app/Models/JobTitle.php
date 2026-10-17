<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'parent_id',
        'name',
        'job_code',
        'job_description',
        'sort_order',
        'status',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::saving(function (JobTitle $jobTitle) {
            if ($jobTitle->sort_order === null) {
                $jobTitle->sort_order = 0;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(JobTitle::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
