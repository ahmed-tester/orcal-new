<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'is_global',
        'parent_id',
        'name',
        'department_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Department $department) {
            if ($department->is_global) {
                $firstCompanyId = $department->companies()->value('companies.id');
                if ($firstCompanyId && $department->company_id !== $firstCompanyId) {
                    $department->withoutEvents(function () use ($department, $firstCompanyId) {
                        $department->updateQuietly(['company_id' => $firstCompanyId]);
                    });
                }
            } else {
                if ($department->company_id) {
                    $department->companies()->syncWithoutDetaching([$department->company_id]);
                }
            }
        });
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_department');
    }

    public function isGlobal(): bool
    {
        return $this->is_global || (bool) $this->parent?->isGlobal();
    }

    public function getAssignedCompanyIds(): array
    {
        if ($this->isGlobal()) {
            return Company::pluck('id')->toArray();
        }

        $ids = $this->companies()->pluck('companies.id')->toArray();
        if (empty($ids) && $this->company_id) {
            return [$this->company_id];
        }

        return $ids;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
