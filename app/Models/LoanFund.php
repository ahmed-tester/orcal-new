<?php

namespace App\Models;

use App\Enums\FundGeneralStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanFund extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'company_id',
        'fund_number',
        'fund_name',
        'target_amount',
        'installment_period',
        'default_installments_count',
        'max_loan_limit',
        'max_repayment_months',
        'status',
        'trustee_employee_id',
        'trustee_name',
        'rules_and_conditions',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'target_amount' => 0,
        'max_loan_limit' => 0,
        'default_installments_count' => 12,
        'max_repayment_months' => 12,
        'status' => FundGeneralStatus::ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'max_loan_limit' => 'decimal:2',
            'default_installments_count' => 'integer',
            'max_repayment_months' => 'integer',
            'status' => FundGeneralStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanFund $fund) {
            // if (empty($fund->company_id)) {
            //     $fund->company_id = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            // }

            if (empty($fund->fund_number)) {
                $fund->fund_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'fund_number',
                    prefix: 'LF',
                    // companyId: $fund->company_id,
                    companyId: null,
                );
            }

            if (empty($fund->created_by)) {
                $fund->created_by = auth()->id();
            }
        });

        static::saving(function (LoanFund $fund) {
            $fund->target_amount = (float) ($fund->target_amount ?? 0);
            $fund->max_loan_limit = (float) ($fund->max_loan_limit ?? 0);
            $fund->default_installments_count = max(1, (int) ($fund->default_installments_count ?: 12));
            $fund->max_repayment_months = max(1, (int) ($fund->max_repayment_months ?: 12));

            if ($fund->trustee_employee_id) {
                $employee = Employee::with('user')->find($fund->trustee_employee_id);
                $fund->trustee_name = $employee?->user?->name;
            } else {
                $fund->trustee_name = null;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trusteeEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'trustee_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LoanFundMember::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LoanFundLoan::class);
    }
}
