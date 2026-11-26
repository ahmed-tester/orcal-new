<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentArchiveBatch extends Model
{
    protected $fillable = [
        'action_type',
        'actor_user_id',
        'root_department_id',
        'root_department_name',
        'root_department_code',
        'target_department_id',
        'target_department_name',
        'department_ids',
        'summary',
        'restored_at',
        'restored_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'department_ids' => 'array',
            'summary' => 'array',
            'restored_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by_user_id');
    }

    public function rootDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'root_department_id')->withTrashed();
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DepartmentArchiveItem::class, 'batch_id');
    }
}
