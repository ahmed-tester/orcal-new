<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
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

    public function getNotifiable(): mixed
    {
        $recipients = collect();

        if ($this->employee->user) {
            $recipients->push($this->employee->user);
        }

        return $recipients->unique('id')->values();
    }

    public function getEventKey(): string
    {
        return 'LeaveReturnUpcomingAlert';
    }

    public function getPlaceholders(): array
    {
        $endDate = $this->leave->end_date 
            ? Carbon::parse($this->leave->end_date)->format('Y-m-d') 
            : '—';
        $expectedReturn = $this->leave->end_date 
            ? Carbon::parse($this->leave->end_date)->addDay()->format('Y-m-d') 
            : '—';

        return [
            'employee_name' => $this->employee->user?->name ?? ('موظف #' . $this->employee->id),
            'company_name' => $this->employee->company?->trade_name ?? 'الشركة',
            'leave_end_date' => $endDate,
            'expected_return_date' => $expectedReturn,
            'days_remaining' => (string) $this->daysRemaining,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
