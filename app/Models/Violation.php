<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Services\CodeGeneratorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Violation extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'violation_date',
            'correction_deadline',
            'objection_deadline',
            'grace_deadline',
            'legal_deadline',
            'admin_execution_date',
            'closure_date',
        ];
    }


    protected $fillable = [
        'violation_number',
        'company_id',
        'violation_category_id',
        'violation_type_id',
        'external_violation_number',
        'violation_date',
        'issuing_authority',
        'violation_amount',
        'description',
        'attachment',
        'correction_deadline',
        'objection_deadline',
        'grace_deadline',
        'legal_deadline',
        'admin_execution_date',
        'stage',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'closure_date',
        'recorded_by',
        'notes',
        'metadata',
    ];

    protected $attributes = [
        'violation_amount' => 0,
        'paid_amount' => 0,
        'remaining_amount' => 0,
        'stage' => ViolationStage::CORRECTION,
        'payment_status' => ViolationPaymentStatus::PENDING,
    ];

    protected function casts(): array
    {
        return [
            'violation_date'       => 'date',
            'correction_deadline'  => 'date',
            'objection_deadline'   => 'date',
            'grace_deadline'       => 'date',
            'legal_deadline'       => 'date',
            'admin_execution_date' => 'date',
            'closure_date'         => 'date',
            'violation_amount'     => 'decimal:2',
            'paid_amount'          => 'decimal:2',
            'remaining_amount'     => 'decimal:2',
            'stage'                => ViolationStage::class,
            'payment_status'       => ViolationPaymentStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Violation $violation) {
            // Auto generate violation number
            if (empty($violation->violation_number)) {
                $violation->violation_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'violation_number',
                    prefix: 'VIO',
                    companyId: $violation->company_id,
                    padLength: 4,
                );
            }
        });

        static::saving(function (Violation $violation) {
            // Auto calculate stage deadlines from violation date
            $date = $violation->violation_date ?? now()->toDateString();
            if (empty($violation->correction_deadline)) {
                $violation->correction_deadline = Carbon::parse($date)->addDays(14)->toDateString();
            }
            if (empty($violation->objection_deadline)) {
                $violation->objection_deadline = Carbon::parse($date)->addDays(60)->toDateString();
            }
            if (empty($violation->grace_deadline)) {
                $violation->grace_deadline = Carbon::parse($date)->addDays(90)->toDateString();
            }
            if (empty($violation->legal_deadline)) {
                $violation->legal_deadline = Carbon::parse($date)->addDays(60)->toDateString();
            }

            // Calculate remaining amount
            $violation->remaining_amount = (float) $violation->violation_amount - (float) ($violation->paid_amount ?? 0);

            // Update closure date when fully paid
            if ($violation->payment_status === ViolationPaymentStatus::PAID && empty($violation->closure_date)) {
                $violation->closure_date = now()->toDateString();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ViolationCategory::class, 'violation_category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ViolationType::class, 'violation_type_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ViolationTransaction::class);
    }
}
