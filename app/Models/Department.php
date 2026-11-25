<?php

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Observers\DepartmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([DepartmentObserver::class])]
class Department extends Model
{
    use HasFactory;

    /** Final company assignment while the hierarchy service is saving a record. */
    public ?array $pendingCompanyIds = null;

    protected $fillable = [
        'company_id',
        'is_global',
        'parent_id',
        'manager_id',
        'name',
        'description',
        'department_code',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'status' => GeneralStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
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
        $ids = $this->relationLoaded('companies')
            ? $this->companies->modelKeys()
            : $this->companies()->pluck('companies.id')->toArray();
        if (! empty($ids)) {
            return $ids;
        }

        if ($this->is_global) {
            return [];
        }

        if ($this->company_id) {
            return [$this->company_id];
        }

        return [];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
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
