<?php

namespace App\Models;

use App\Enums\RoscaAllocationMethod;
use App\Services\CodeGeneratorService;
use App\Traits\HasHijriDates;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoscaFund extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'start_date',
            'end_date',
        ];
    }

    protected $fillable = [
        // 'company_id',
        'fund_number',
        'fund_name',
        'start_date',
        'end_date',
        'members_count',
        'monthly_installment',
        'total_pool_amount',
        'allocation_method',
        'status',
        'assigned_manager_id',
        'manager_name',
        'rules_and_conditions',
        'notes',
        'metadata',
    ];

    protected $attributes = [
        'members_count' => 0,
        'monthly_installment' => 0,
        'total_pool_amount' => 0,
        'allocation_method' => RoscaAllocationMethod::CONSENSUS,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'members_count' => 'integer',
            'monthly_installment' => 'decimal:2',
            'total_pool_amount' => 'decimal:2',
            'allocation_method' => RoscaAllocationMethod::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoscaFund $fund) {
            // if (empty($fund->company_id)) {
            //     $fund->company_id = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            // }

            if (empty($fund->fund_number)) {
                $fund->fund_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'fund_number',
                    prefix: 'RF',
                    // companyId: $fund->company_id,
                    companyId: null,
                );
            }

            static::calculatePool($fund);
        });

        static::updating(function (RoscaFund $fund) {
            static::calculatePool($fund);
        });
    }

    public static function calculatePool(RoscaFund $fund): void
    {
        $count = (int) ($fund->members_count ?? 0);
        $monthly = (float) ($fund->monthly_installment ?? 0);
        $fund->total_pool_amount = round($count * $monthly, 2);

        if ($fund->start_date && $count > 0 && empty($fund->end_date)) {
            $fund->end_date = Carbon::parse($fund->start_date)->addMonths($count)->toDateString();
        }

        if ($fund->assigned_manager_id) {
            $manager = User::find($fund->assigned_manager_id);
            $fund->manager_name = $manager?->name;
        } else {
            $fund->manager_name = null;
        }
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(RoscaFundMember::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RoscaFundPayment::class);
    }
}
