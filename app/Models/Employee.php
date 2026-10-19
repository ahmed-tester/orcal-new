<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'hire_date',
            'termination_date',
            'contract_end_date',
        ];
    }


    protected $fillable = [
        'user_id',
        'company_id',
        'branch_id',
        'department_id',
        'job_title_id',
        'employee_number',
        'employment_type',
        'hire_date',
        'annual_leave_days',
        'termination_date',
        'basic_salary',
        'bank_iban',
        'hourly_rate',
        'daily_work_hours',
        'weekly_work_hours',
        'contract_duration',
        'contract_end_date',
        'contract_file',
        'notes',
        'status',
        'metadata',
    ];

    protected $attributes = [
        'basic_salary' => 0,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'annual_leave_days' => 'integer',
            'termination_date' => 'date',
            'contract_end_date' => 'date',
            'basic_salary' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'daily_work_hours' => 'decimal:1',
            'weekly_work_hours' => 'decimal:1',
            'employment_type' => \App\Enums\EmploymentType::class,
            'status' => \App\Enums\GeneralStatus::class,
            'metadata' => 'array',
        ];
    }

    public static function generateNextEmployeeNumber(?int $companyId = null): string
    {
        return \App\Services\CodeGeneratorService::generate(
            modelClass: static::class,
            column: 'employee_number',
            prefix: 'EMP',
            companyId: $companyId,
            padLength: 4,
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->employee_number) || static::withoutGlobalScopes()->where('employee_number', $employee->employee_number)->exists()) {
                $employee->employee_number = static::generateNextEmployeeNumber($employee->company_id);
            }
        });

        static::saving(function (Employee $employee) {
            $employee->basic_salary = (float)($employee->basic_salary ?? 0);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function details(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class, 'employee_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class, 'employee_id');
    }

    public function guarantor(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Guarantor::class,
            EmployeeDetail::class,
            'employee_id',
            'id',
            'id',
            'guarantor_id'
        );
    }

    public function actingGuarantor(): HasOne
    {
        return $this->hasOne(Guarantor::class, 'employee_id');
    }

    public function leaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function taskAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function assignedWeeklyNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WeeklyNote::class, 'assigned_to');
    }

    public function installments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function installmentPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function payrollRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function employeeRewards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeReward::class);
    }

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? $this->employee_number ?? "موظف #{$this->id}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->details?->metadata['age'] ?? \App\Services\EmployeeAgeService::calculateAge($this->details?->birth_date);
    }
}
