<?php

namespace App\Models;

use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTransaction extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'transaction_date',
            'due_date',
            'completion_date',
        ];
    }

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'scope_type',
        'service_target',
        'company_id',
        'employee_id',
        'service_id',
        'service_name',
        'service_type',
        'first_party',
        'beneficiary_name',
        'beneficiary_phone',
        'beneficiary_national_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'service_status',
        'executed_by',
        'due_date',
        'completion_date',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'due_date'         => 'date',
            'completion_date'  => 'date',
            'total_amount'     => 'decimal:2',
            'paid_amount'      => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'metadata'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceTransaction $transaction) {
            if (empty($transaction->scope_type)) {
                $transaction->scope_type = !empty($transaction->service_name) ? 'external' : 'internal';
            }

            if ($transaction->scope_type === 'internal' && empty($transaction->company_id)) {
                $transaction->company_id = $transaction->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'transaction_number',
                    prefix: 'SRV',
                    companyId: $transaction->company_id,
                );
            }

            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now()->toDateString();
            }

            if (empty($transaction->executed_by)) {
                $transaction->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saving(function (ServiceTransaction $transaction) {
            $total = (float)($transaction->total_amount ?? 0);
            $paid = (float)($transaction->paid_amount ?? 0);
            $remaining = max(0, $total - $paid);

            $transaction->total_amount = $total;
            $transaction->paid_amount = $paid;
            $transaction->remaining_amount = $remaining;

            if ($total > 0 && $paid >= $total) {
                $transaction->payment_status = 'paid';
            } elseif ($paid > 0) {
                $transaction->payment_status = 'partial';
            } else {
                $transaction->payment_status = 'unpaid';
            }

            if ($transaction->service_status === 'completed' && empty($transaction->completion_date)) {
                $transaction->completion_date = now()->toDateString();
            }
        });
    }

    public function getDisplayServiceNameAttribute(): string
    {
        if ($this->scope_type === 'external' || !empty($this->service_name)) {
            return $this->service_name ?? __('services_tracking.badges.external');
        }

        return $this->service?->name ?? '—';
    }

    public function getDisplayBeneficiaryAttribute(): string
    {
        if ($this->scope_type === 'external') {
            return $this->beneficiary_name ?? '—';
        }

        if ($this->service_target === 'employee' && $this->employee) {
            return $this->employee->user?->name ?? "موظف #{$this->employee->id}";
        }

        return $this->company?->name ?? '—';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }
}
