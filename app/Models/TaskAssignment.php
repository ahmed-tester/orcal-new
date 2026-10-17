<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Observers\TaskAssignmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([TaskAssignmentObserver::class])]
class TaskAssignment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'assignment_date',
            'from_date',
            'to_date',
        ];
    }


    protected $fillable = [
        'transaction_number',
        'assignment_date',
        'employee_id',
        'from_date',
        'to_date',
        'is_completed',
        'total_weight',
        'total_score',
        'overall_percentage',
        'notes',
        'created_by',
        'metadata',
    ];

    protected $attributes = [
        'is_completed' => false,
        'total_weight' => 0.00,
        'total_score' => 0.00,
        'overall_percentage' => 0.00,
    ];

    protected static function booted(): void
    {
        static::saving(function (TaskAssignment $assignment) {
            $assignment->total_weight = (float)($assignment->total_weight ?? 0);
            $assignment->total_score = (float)($assignment->total_score ?? 0);
            $assignment->overall_percentage = (float)($assignment->overall_percentage ?? 0);
        });
    }

    protected $casts = [
        'assignment_date' => 'date',
        'from_date' => 'date',
        'to_date' => 'date',
        'is_completed' => 'boolean',
        'total_weight' => 'decimal:2',
        'total_score' => 'decimal:2',
        'overall_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskAssignmentItem::class);
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $totalWeight = (float) $this->items->sum('weight');
        $totalScore = (float) $this->items->sum('score');
        $overallPercentage = $totalWeight > 0 ? round(($totalScore / $totalWeight) * 100, 2) : 0.00;

        $this->updateQuietly([
            'total_weight' => $totalWeight,
            'total_score' => $totalScore,
            'overall_percentage' => $overallPercentage,
            'is_completed' => $this->items->isNotEmpty() && $this->items->every(fn ($item) => $item->status === \App\Enums\TaskStatus::COMPLETED),
        ]);
    }
}
