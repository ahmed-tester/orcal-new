<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SupervisionShift extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'start_date',
            'end_date',
        ];
    }


    protected $table = 'supervision_shifts';

    protected $fillable = [
        'type',
        'company_id',
        'supervisor_id',
        'task_id',
        'title',
        'tasks',
        'assigned_employees',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'assigned_employees' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'type' => 'supervision',
        'status' => 'active',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the team members (assigned employees) collection.
     */
    public function getAssignedEmployeesModelsAttribute(): Collection
    {
        if (empty($this->assigned_employees) || !is_array($this->assigned_employees)) {
            return collect();
        }

        return Employee::whereIn('id', $this->assigned_employees)->with('user', 'jobTitle', 'department')->get();
    }

    public function isSupervision(): bool
    {
        return $this->type === 'supervision';
    }

    public function isShift(): bool
    {
        return $this->type === 'shift';
    }
}
