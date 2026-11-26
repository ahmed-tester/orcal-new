<?php

namespace App\Models\Payroll;

use App\Enums\Payroll\FinancialApprovalStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialApprovalRequest extends Model
{
    use HasFactory;

    public const SOURCE_PAYROLL = 'payroll';

    protected $fillable = [
        'request_number',
        'source_type',
        'parent_request_id',
        'payroll_month',
        'version',
        'status',
        'submitted_by',
        'submitted_at',
        'treasury_reviewer_id',
        'treasury_reviewer_department_id',
        'treasury_reviewed_at',
        'manager_department_id',
        'manager_employee_id',
        'manager_user_id',
        'manager_decided_at',
        'executed_at',
        'employees_count',
        'total_amount',
        'notes',
        'attempt_key',
        'input_fingerprint',
        'last_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => FinancialApprovalStatus::class,
            'version' => 'integer',
            'employees_count' => 'integer',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'treasury_reviewed_at' => 'datetime',
            'manager_decided_at' => 'datetime',
            'executed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function treasuryReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasury_reviewer_id');
    }

    public function treasuryReviewerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'treasury_reviewer_department_id');
    }

    public function managerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'manager_department_id');
    }

    public function managerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function managerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'financial_approval_request_companies', 'request_id', 'company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinancialApprovalRequestItem::class, 'request_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FinancialApprovalRequestLog::class, 'request_id')->orderByDesc('id');
    }
}
