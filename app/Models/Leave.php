<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'start_date',
            'end_date',
            'actual_return_date',
        ];
    }

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'actual_return_date',
        'days',
        'reason',
        'attachment_file',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'metadata',
    ];

    protected $attributes = [
        'days' => 1,
        'status' => LeaveStatus::PENDING,
    ];

    protected static function booted(): void
    {
        static::saving(function (Leave $leave) {
            $leave->days = max(1, (int) ($leave->days ?: 1));
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_return_date' => 'date',
            'days' => 'integer',
            'status' => LeaveStatus::class,
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getReturnDifferenceDaysAttribute(): ?int
    {
        if (! $this->actual_return_date || ! $this->end_date) {
            return null;
        }

        return $this->end_date->diffInDays($this->actual_return_date, false);
    }

    public function getReturnStatusLabelAttribute(): string
    {

        if ($this->status !== LeaveStatus::APPROVED) {
            return match ($this->status) {
                LeaveStatus::PENDING => __('leaves.return_statuses.pending_approval'),
                LeaveStatus::REJECTED => __('leaves.return_statuses.rejected'),
                LeaveStatus::CANCELLED => __('leaves.return_statuses.cancelled'),
                default => '—',
            };
        }

        if ($this->actual_return_date) {
            $diff = $this->return_difference_days;
            $unit = abs($diff) == 1 ? __('leaves.units.day') : __('leaves.units.days');
            if ($diff < 0) {
                return __('leaves.return_statuses.early', ['days' => abs($diff), 'unit' => $unit]);
            } elseif ($diff === 0) {
                return __('leaves.return_statuses.on_time');
            } else {
                return __('leaves.return_statuses.late', ['days' => $diff, 'unit' => $unit]);
            }
        }

        $expectedReturnDate = $this->end_date ? $this->end_date->copy()->addDay()->startOfDay() : null;
        if ($expectedReturnDate && now()->startOfDay()->greaterThan($expectedReturnDate)) {
            $overdueDays = (int) $expectedReturnDate->diffInDays(now()->startOfDay());
            $unit = $overdueDays == 1 ? __('leaves.units.day') : __('leaves.units.days');

            return __('leaves.return_statuses.overdue', ['days' => $overdueDays, 'unit' => $unit]);
        }

        return __('leaves.return_statuses.on_leave');
    }

    public function getReturnStatusColorAttribute(): string
    {

        if ($this->status !== LeaveStatus::APPROVED) {
            return match ($this->status) {
                LeaveStatus::PENDING => 'warning',
                LeaveStatus::REJECTED => 'danger',
                LeaveStatus::CANCELLED => 'gray',
                default => 'gray',
            };
        }

        if ($this->actual_return_date) {
            $diff = $this->return_difference_days;
            if ($diff < 0) {
                return 'success';
            } elseif ($diff === 0) {
                return 'success';
            } else {
                return 'warning';
            }
        }

        $expectedReturnDate = $this->end_date ? $this->end_date->copy()->addDay()->startOfDay() : null;
        if ($expectedReturnDate && now()->startOfDay()->greaterThan($expectedReturnDate)) {
            return 'danger';
        }

        return 'info';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
