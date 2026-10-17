<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Guarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', // 'internal' | 'external'
        'employee_id',
        'company_id',
        'name',
        'national_id',
        'phone',
        'nationality',
        'job_title',
        'address',
        'notes',
        'status',
    ];

    protected $attributes = [
        'type' => 'external',
        'status' => 'active',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeDetails(): HasMany
    {
        return $this->hasMany(EmployeeDetail::class, 'guarantor_id');
    }

    public function guaranteedEmployees(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            EmployeeDetail::class,
            'guarantor_id',
            'id',
            'id',
            'employee_id'
        );
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    public function isExternal(): bool
    {
        return $this->type === 'external';
    }
}
