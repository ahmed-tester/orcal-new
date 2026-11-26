<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentArchiveItem extends Model
{
    protected $fillable = [
        'batch_id',
        'item_type',
        'item_id',
        'parent_department_id',
        'company_id',
        'previous_state',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'restored_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DepartmentArchiveBatch::class, 'batch_id');
    }
}
