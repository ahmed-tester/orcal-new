<?php

namespace App\Models;

use App\Enums\JobTitleScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'is_global',
        'department_id',
        'parent_id',
        'name',
        'job_code',
        'job_description',
        'sort_order',
        'status',
    ];

    protected $attributes = [
        'is_global' => false,
        'sort_order' => 0,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::saving(function (JobTitle $jobTitle) {
            if ($jobTitle->is_global) {
                $jobTitle->company_id = null;
            }
            if ($jobTitle->sort_order === null) {
                $jobTitle->sort_order = 0;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'sort_order' => 'integer',
            'status' => \App\Enums\GeneralStatus::class,
        ];
    }

    public function isGlobal(): bool
    {
        return (bool) ($this->is_global || empty($this->company_id));
    }

    public function getScopeAttribute(): JobTitleScope
    {
        return $this->isGlobal() ? JobTitleScope::GLOBAL : JobTitleScope::COMPANY;
    }

    public function getCompanyDisplayNameAttribute(): string
    {
        return $this->isGlobal() ? 'عام للمجموعة بالكامل 🌐' : ($this->company?->trade_name ?? '—');
    }

    public function getFormattedNameWithScopeAttribute(): string
    {
        $suffix = $this->isGlobal()
            ? ' [عام للمجموعة 🌐]'
            : ($this->company ? " [{$this->company->trade_name}]" : '');

        return $this->name.$suffix;
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('is_global', true)->orWhereNull('company_id'));
    }

    public function scopeForScope(Builder $query, string|JobTitleScope $scope): Builder
    {
        $value = $scope instanceof JobTitleScope ? $scope->value : $scope;

        if ($value === JobTitleScope::GLOBAL->value) {
            return $query->where(fn ($q) => $q->where('is_global', true)->orWhereNull('company_id'));
        }

        if ($value === JobTitleScope::COMPANY->value) {
            return $query->where('is_global', false)->whereNotNull('company_id');
        }

        return $query;
    }

    public function scopeAvailableForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId) {
            if ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhere('is_global', true)
                    ->orWhereNull('company_id');
            } else {
                $q->where('is_global', true)
                    ->orWhereNull('company_id');
            }
        });
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
