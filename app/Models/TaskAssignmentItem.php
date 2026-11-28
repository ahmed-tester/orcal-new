<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Observers\TaskAssignmentItemObserver;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([TaskAssignmentItemObserver::class])]
class TaskAssignmentItem extends Model
{
    use HasFactory, HasHijriDates;

    private static int $reviewGuardBypassDepth = 0;

    public function getHijriDateFieldNames(): array
    {
        return [
            'due_date',
            'completed_at',
            'original_due_date',
        ];
    }

    protected $fillable = [
        'task_assignment_id',
        'task_id',
        'renewal_request_id',
        'company_id',
        'status',
        'requires_review',
        'weight',
        'score',
        'score_percentage',
        'due_date',
        'original_due_date',
        'completed_at',
        'submitted_at',
        'accepted_submission_at',
        'approved_at',
        'approved_by',
        'returned_count',
        'defect_return_count',
        'latest_submission_note',
        'latest_return_reason',
        'due_date_extended_at',
        'due_date_extended_by',
        'due_date_extension_reason',
        'notes',
        'metadata',
    ];

    protected $attributes = [
        'status' => TaskStatus::PENDING,
        'requires_review' => true,
        'weight' => 0.00,
        'score' => 0.00,
        'score_percentage' => 0.00,
        'returned_count' => 0,
        'defect_return_count' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (TaskAssignmentItem $item) {
            $item->weight = (float) ($item->weight ?? 0);
            $item->score = (float) ($item->score ?? 0);
            $item->score_percentage = (float) ($item->score_percentage ?? 0);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'requires_review' => 'boolean',
            'weight' => 'decimal:2',
            'score' => 'decimal:2',
            'score_percentage' => 'decimal:2',
            'due_date' => 'date',
            'original_due_date' => 'date',
            'completed_at' => 'date',
            'submitted_at' => 'datetime',
            'accepted_submission_at' => 'datetime',
            'approved_at' => 'datetime',
            'returned_count' => 'integer',
            'defect_return_count' => 'integer',
            'due_date_extended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public static function withoutReviewGuard(callable $callback): mixed
    {
        self::$reviewGuardBypassDepth++;

        try {
            return $callback();
        } finally {
            self::$reviewGuardBypassDepth = max(0, self::$reviewGuardBypassDepth - 1);
        }
    }

    public static function isReviewGuardBypassed(): bool
    {
        return self::$reviewGuardBypassDepth > 0;
    }

    public function isEligibleForQuality(): bool
    {
        return ! $this->requires_review || $this->status === TaskStatus::COMPLETED;
    }

    public function taskAssignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function renewalRequest(): BelongsTo
    {
        return $this->belongsTo(UnifiedDeficiencyTask::class, 'renewal_request_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isDocumentRenewal(): bool
    {
        return $this->renewal_request_id !== null;
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TaskAssignmentItemReview::class)->orderBy('attempt_number');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dueDateExtender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'due_date_extended_by');
    }
}
