<?php

namespace App\Models;

use App\Enums\AssetFundStatus;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'fund_number',
        'fund_name',
        'established_date',
        'description',
        'rules_and_conditions',
        'manager_employee_id',
        'manager_name',
        'accountant_employee_id',
        'accountant_name',
        'status',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'established_date' => 'date',
            'status'           => AssetFundStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssetFund $fund) {
            if (empty($fund->fund_number)) {
                $fund->fund_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'fund_number',
                    prefix: 'AF',
                    companyId: null,
                );
            }

            if (empty($fund->created_by)) {
                $fund->created_by = auth()->id();
            }
        });

        static::saving(function (AssetFund $fund) {
            if ($fund->manager_employee_id) {
                $emp = Employee::with('user')->find($fund->manager_employee_id);
                $fund->manager_name = $emp?->user?->name;
            }

            if ($fund->accountant_employee_id) {
                $emp = Employee::with('user')->find($fund->accountant_employee_id);
                $fund->accountant_name = $emp?->user?->name;
            }
        });
    }

    // ─── Computed ────────────────────────────────────────────────────────────

    public function getBalanceAttribute(): float
    {
        $deposits    = $this->vouchers()->where('type', 'deposit')->sum('total_amount');
        $withdrawals = $this->vouchers()->where('type', 'withdrawal')->sum('total_amount');

        return (float) ($deposits - $withdrawals);
    }

    public function getTotalDepositsAttribute(): float
    {
        return (float) $this->vouchers()->where('type', 'deposit')->sum('total_amount');
    }

    public function getTotalWithdrawalsAttribute(): float
    {
        return (float) $this->vouchers()->where('type', 'withdrawal')->sum('total_amount');
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function managerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function accountantEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'accountant_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(AssetFundVoucher::class);
    }
}

