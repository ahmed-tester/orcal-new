<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Services\CodeGeneratorService;
use App\Services\EmployeeAgeService;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
            'employment_type' => EmploymentType::class,
            'status' => GeneralStatus::class,
            'metadata' => 'array',
        ];
    }

    public static function generateNextEmployeeNumber(?int $companyId = null): string
    {
        return CodeGeneratorService::generate(
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
            if (empty($employee->employee_number)) {
                $employee->employee_number = static::generateNextEmployeeNumber($employee->company_id);
            }
        });

        static::saving(function (Employee $employee) {
            $employee->basic_salary = (float) ($employee->basic_salary ?? 0);
        });
    }

    public function scopeSaudi(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('user', fn ($sub) => $sub->whereIn('user_type', [
                UserType::SAUDI_EMPLOYEE,
                UserType::OWNER,
            ]))
                ->orWhereHas('details', fn ($sub) => $sub->where('nationality', 'like', '%سعود%')
                    ->orWhere('nationality', 'like', '%saudi%')
                );
        });
    }

    public function scopeForeign(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('user', fn ($sub) => $sub->where('user_type', UserType::FOREIGN_EMPLOYEE))
                ->orWhere(function (Builder $sub) {
                    $sub->whereDoesntHave('user', fn ($u) => $u->whereIn('user_type', [
                        UserType::SAUDI_EMPLOYEE,
                        UserType::OWNER,
                    ]))
                        ->whereHas('details', fn ($d) => $d->where('nationality', 'not like', '%سعود%')
                            ->where('nationality', 'not like', '%saudi%')
                        );
                });
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

    public function guarantor(): HasOneThrough
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

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function assignedWeeklyNotes(): HasMany
    {
        return $this->hasMany(WeeklyNote::class, 'assigned_to');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function employeeRewards(): HasMany
    {
        return $this->hasMany(EmployeeReward::class);
    }

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? $this->employee_number ?? "موظف #{$this->id}";
    }

    public function getNameEnAttribute(): ?string
    {
        return $this->metadata['name_en'] ?? null;
    }

    public function setNameEnAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        if ($value !== null && trim($value) !== '') {
            $meta['name_en'] = trim($value);
        } else {
            unset($meta['name_en']);
        }
        $this->metadata = $meta;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->details?->metadata['age'] ?? EmployeeAgeService::calculateAge($this->details?->birth_date);
    }
}
