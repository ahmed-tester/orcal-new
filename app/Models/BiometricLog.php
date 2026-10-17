<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'employee_id',
        'device_sn',
        'device_user_id',
        'punched_at',
        'punch_type',
        'raw_status',
        'verify_mode',
        'work_code',
        'raw_payload',
        'is_processed',
        'processed_at',
        'unique_key',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'processed_at' => 'datetime',
            'is_processed' => 'boolean',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
