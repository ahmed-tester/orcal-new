<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'date',
        ];
    }


    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'device_id',
        'date',
        'check_in',
        'check_out',
        'late_hours',
        'overtime_hours',
        'total_hours',
        'check_in_source',
        'check_out_source',
        'status',
        'notes',
        'created_by',
        'metadata',
    ];

    protected $attributes = [
        'late_hours' => 0,
        'overtime_hours' => 0,
        'total_hours' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance) {
            $attendance->late_hours = $attendance->late_hours ?? 0;
            $attendance->overtime_hours = $attendance->overtime_hours ?? 0;
            $attendance->total_hours = $attendance->total_hours ?? 0;
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'late_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'status' => \App\Enums\AttendanceStatus::class,
            'check_in_source' => \App\Enums\AttendanceSource::class,
            'check_out_source' => \App\Enums\AttendanceSource::class,
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
