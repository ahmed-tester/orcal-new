<?php

namespace App\Models\Payroll;

use App\Enums\Payroll\FinancialApprovalItemStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialApprovalRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'version',
        'payroll_record_id',
        'employee_id',
        'company_id',
        'branch_id',
        'payroll_month',
        'employee_name_snapshot',
        'employee_number_snapshot',
        'net_amount',
        'previously_paid_amount',
        'requested_amount',
        'payment_method',
        'destination_reference',
        'reference_number',
        'proof_path',
        'reason_description',
        'data_fingerprint',
        'idempotency_key',
        'status',
        'payroll_payment_id',
        'error_message',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'net_amount' => 'decimal:2',
            'previously_paid_amount' => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'status' => FinancialApprovalItemStatus::class,
            'snapshot' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancialApprovalRequest::class, 'request_id');
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrollPayment(): BelongsTo
    {
        return $this->belongsTo(PayrollPayment::class);
    }
}
