<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignmentItemReview extends Model
{
    use HasFactory;

    public const DECISION_PENDING = 'pending';

    public const DECISION_APPROVED = 'approved';

    public const DECISION_RETURNED = 'returned';

    public const RETURN_EXECUTION_DEFECT = 'execution_defect';

    public const RETURN_SCOPE_CHANGE = 'scope_change';

    protected $fillable = [
        'task_assignment_item_id',
        'attempt_number',
        'submitted_by',
        'submitted_at',
        'submission_note',
        'decision',
        'decided_by',
        'decided_at',
        'return_category',
        'decision_reason',
        'awarded_score',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'awarded_score' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TaskAssignmentItem::class, 'task_assignment_item_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
