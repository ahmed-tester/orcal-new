<?php

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([TaskObserver::class])]
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_code',
        'name',
        'default_weight',
        'description',
        'status',
    ];

    protected $attributes = [
        'default_weight' => 0.00,
        'status' => GeneralStatus::ACTIVE,
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            $task->default_weight = (float)($task->default_weight ?? 0);
        });
    }

    protected $casts = [
        'status' => GeneralStatus::class,
        'default_weight' => 'decimal:2',
    ];

    public function assignmentItems(): HasMany
    {
        return $this->hasMany(TaskAssignmentItem::class);
    }
}
