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

class LeaveReturnUpcomingAlertEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Employee $employee,
        public Leave $leave,
        public int $daysRemaining,
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
        return 'LeaveReturnUpcomingAlert';
    }

    public function getPlaceholders(): array
    {
        $leaveEndDate = $this->leave->end_date
            ? Carbon::parse($this->leave->end_date)->format('Y-m-d')
            : '—';

        $expectedReturn = $this->leave->end_date
            ? Carbon::parse($this->leave->end_date)->addDay()->format('Y-m-d')
            : '—';

        return [
            'employee_name' => $this->employee->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $this->employee->id]),
            'company_name' => $this->employee->company?->trade_name ?? __('notifications.alerts.company_fallback'),
            'leave_end_date' => $leaveEndDate,
            'expected_return_date' => $expectedReturn,
            'days_remaining' => (string) $this->daysRemaining,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
