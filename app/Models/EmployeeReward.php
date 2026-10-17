<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\RewardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeReward extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'reward_date',
            'paid_date',
        ];
    }


    protected $fillable = [
        'reward_number',
        'reward_date',
        'company_id',
        'employee_id',
        'hourly_rate',
        'hours_count',
        'hours_amount',
        'minute_rate',
        'minutes_count',
        'minutes_amount',
        'net_amount',
        'reason',
        'location',
        'status',
        'paid_date',
        'treasury_transaction_id',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reward_date'    => 'date',
            'paid_date'      => 'date',
            'hourly_rate'    => 'decimal:2',
            'hours_count'    => 'decimal:2',
            'hours_amount'   => 'decimal:2',
            'minute_rate'    => 'decimal:4',
            'minutes_amount' => 'decimal:2',
            'net_amount'     => 'decimal:2',
            'status'         => RewardStatus::class,
            'metadata' => 'array',
        ];
    }

    protected $attributes = [
        'hourly_rate' => 0,
        'hours_count' => 0,
        'hours_amount' => 0,
        'minute_rate' => 0,
        'minutes_count' => 0,
        'minutes_amount' => 0,
        'net_amount' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (EmployeeReward $reward) {
            if (empty($reward->company_id)) {
                $user = auth()->user();
                $reward->company_id = $reward->employee?->company_id ?? $user?->company_id ?? $user?->employee?->company_id;
            }

            if (empty($reward->reward_number)) {
                $reward->reward_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'reward_number',
                    prefix: 'B',
                    companyId: $reward->company_id,
                );
            }

            if (empty($reward->reward_date)) {
                $reward->reward_date = now()->toDateString();
            }

            if (empty($reward->created_by)) {
                $reward->created_by = auth()->id();
            }

            static::calculateAmounts($reward);
        });

        static::updating(function (EmployeeReward $reward) {
            static::calculateAmounts($reward);
        });

        static::saving(function (EmployeeReward $reward) {
            static::calculateAmounts($reward);
        });
    }

    public static function calculateAmounts(EmployeeReward $reward): void
    {
        $hourlyRate = (float)($reward->hourly_rate ?? 0);
        $hoursCount = (float)($reward->hours_count ?? 0);
        $minutesCount = (int)($reward->minutes_count ?? 0);

        $reward->hourly_rate = $hourlyRate;
        $reward->hours_count = $hoursCount;
        $reward->minutes_count = $minutesCount;

        $reward->hours_amount = round($hourlyRate * $hoursCount, 2);

        $minuteRate = round($hourlyRate / 60, 4);
        $reward->minute_rate = $minuteRate;

        $reward->minutes_amount = round($minuteRate * $minutesCount, 2);
        $reward->net_amount = round($reward->hours_amount + $reward->minutes_amount, 2);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }
}
