<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
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

    public function getNotifiable(): mixed
    {
        $recipients = collect();

        // 1. Employee User
        if ($this->employee->user) {
            $recipients->push($this->employee->user);
        } else {
            $recipients->push($this->employee);
        }

        return $recipients->unique('id')->values();
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
            'employee_name' => $this->employee->user?->name ?? ('موظف #' . $this->employee->id),
            'company_name' => $this->employee->company?->trade_name ?? 'الشركة',
            'leave_type' => $this->leave->leaveType?->name ?? 'إجازة',
            'expected_return_date' => $expectedReturn,
            'days_overdue' => (string) $this->daysOverdue,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
