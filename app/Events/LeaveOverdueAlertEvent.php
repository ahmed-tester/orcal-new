<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveOverdueAlertEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Employee $employee,
        public Leave $leave,
        public int $daysOverdue,
        public ?string $customMessage = null
    ) {}

    public function getRecord(): ?Model
    {
        return $this->leave;
    }

    public function getActionUrl(): ?string
    {
        return url("/leaves/{$this->leave->id}/edit");
    }

    public function getNotifiable(): mixed
    {
        return $this->employee->user ?? $this->employee;
    }

    public function getEventKey(): string
    {
        return 'LeaveOverdueAlert';
    }

    public function getPlaceholders(): array
    {
        $expectedReturn = $this->leave->end_date
            ? Carbon::parse($this->leave->end_date)->addDay()->format('Y-m-d')
            : '—';

        return [
            'employee_name' => $this->employee->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $this->employee->id]),
            'company_name' => $this->employee->company?->trade_name ?? __('notifications.alerts.company_fallback'),
            'leave_type' => $this->leave->leaveType?->name ?? __('notifications.general.leave'),
            'expected_return_date' => $expectedReturn,
            'days_overdue' => (string) $this->daysOverdue,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
