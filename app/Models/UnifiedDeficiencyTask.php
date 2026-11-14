<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnifiedDeficiencyTask extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'due_date',
        ];
    }

    protected $fillable = [
        'company_id',
        'taskable_type',
        'taskable_id',
        'service_transaction_id',
        'task_key',
        'task_code',
        'deficiency_type',
        'title',
        'description',
        'responsible_employee_id',
        'assigned_by_user_id',
        'status',
        'due_date',
        'resolved_at',
        'resolved_by_user_id',
        'resolution_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UnifiedDeficiencyTask $task) {
            if (empty($task->taskable_type) && ! empty($task->service_transaction_id)) {
                $task->taskable_type = ServiceTransaction::class;
                $task->taskable_id = $task->service_transaction_id;
            } elseif (! empty($task->taskable_type) && $task->taskable_type === ServiceTransaction::class && empty($task->service_transaction_id)) {
                $task->service_transaction_id = $task->taskable_id;
            }

            if (empty($task->task_code)) {
                $task->task_code = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'task_code',
                    prefix: 'TSK',
                    companyId: $task->company_id,
                );
            }

            if (empty($task->status)) {
                $task->status = 'open';
            }

            if (empty($task->due_date)) {
                $task->due_date = now()->addDays(2)->toDateString();
            }
        });
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ServiceTransaction::class, 'service_transaction_id');
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }
}
