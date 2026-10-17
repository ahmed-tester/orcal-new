<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\TaskStatus;
use App\Observers\TaskAssignmentItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TaskAssignmentItemObserver::class])]
class TaskAssignmentItem extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'due_date',
            'completed_at',
        ];
    }


    protected $fillable = [
        'task_assignment_id',
        'task_id',
        'status',
        'weight',
        'score',
        'score_percentage',
        'due_date',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $attributes = [
        'status' => TaskStatus::PENDING,
        'weight' => 0.00,
        'score' => 0.00,
        'score_percentage' => 0.00,
    ];

    protected static function booted(): void
    {
        static::saving(function (TaskAssignmentItem $item) {
            $item->weight = (float)($item->weight ?? 0);
            $item->score = (float)($item->score ?? 0);
            $item->score_percentage = (float)($item->score_percentage ?? 0);
        });
    }

    protected $casts = [
        'status' => TaskStatus::class,
        'weight' => 'decimal:2',
        'score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
        'due_date' => 'date',
        'completed_at' => 'date',
        'metadata' => 'array',
    ];

    public function taskAssignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
