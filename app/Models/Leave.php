<?php

namespace App\Models;


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
        'status' => \App\Enums\LeaveStatus::PENDING,
    ];

    protected static function booted(): void
    {
        static::saving(function (Leave $leave) {
            $leave->days = max(1, (int)($leave->days ?: 1));
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_return_date' => 'date',
            'days' => 'integer',
            'status' => \App\Enums\LeaveStatus::class,
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * حساب الفرق بالأيام بين تاريخ العودة الفعلي ونهاية الإجازة
     * سالب: عاد قبل الموعد (مبكراً)
     * صفر: في الموعد بالضبط
     * موجب: تأخير
     */
    public function getReturnDifferenceDaysAttribute(): ?int
    {
        if (!$this->actual_return_date || !$this->end_date) {
            return null;
        }

        return $this->end_date->diffInDays($this->actual_return_date, false);
    }

    /**
     * وصف حالة العودة
     */
    public function getReturnStatusLabelAttribute(): string
    {
        // أولاً: إذا كانت الإجازة غير معتمدة نعرض حالة الإجازة لا حالة العودة
        if ($this->status !== \App\Enums\LeaveStatus::APPROVED) {
            return match ($this->status) {
                \App\Enums\LeaveStatus::PENDING   => __('leaves.return_statuses.pending_approval'),
                \App\Enums\LeaveStatus::REJECTED  => __('leaves.return_statuses.rejected'),
                \App\Enums\LeaveStatus::CANCELLED => __('leaves.return_statuses.cancelled'),
                default => '—',
            };
        }

        // الإجازة معتمدة: نحسب حالة العودة الفعلية
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

        if (now()->startOfDay()->greaterThan($this->end_date)) {
            $overdueDays = $this->end_date->diffInDays(now()->startOfDay());
            $unit = $overdueDays == 1 ? __('leaves.units.day') : __('leaves.units.days');
            return __('leaves.return_statuses.overdue', ['days' => $overdueDays, 'unit' => $unit]);
        }

        return __('leaves.return_statuses.on_leave');
    }

    /**
     * لون البادج لحالة العودة
     */
    public function getReturnStatusColorAttribute(): string
    {
        // أولاً: إذا كانت الإجازة غير معتمدة نعرض لون حالة الإجازة
        if ($this->status !== \App\Enums\LeaveStatus::APPROVED) {
            return match ($this->status) {
                \App\Enums\LeaveStatus::PENDING   => 'warning',
                \App\Enums\LeaveStatus::REJECTED  => 'danger',
                \App\Enums\LeaveStatus::CANCELLED => 'gray',
                default => 'gray',
            };
        }

        // الإجازة معتمدة: نحسب لون حالة العودة
        if ($this->actual_return_date) {
            $diff = $this->return_difference_days;
            if ($diff < 0) {
                return 'success'; // عاد مبكراً
            } elseif ($diff === 0) {
                return 'success'; // مكتملة
            } else {
                return 'warning'; // تأخير
            }
        }

        if (now()->startOfDay()->greaterThan($this->end_date)) {
            return 'danger'; // تأخر عن العودة
        }

        return 'info'; // في إجازة
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
