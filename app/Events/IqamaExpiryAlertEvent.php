<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Owner;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IqamaExpiryAlertEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Employee $employee,
        public EmployeeDetail $detail,
        public int $daysRemaining,
        public ?string $alertStage = null,
        public ?string $customMessage = null
    ) {}

    public function getNotifiable(): mixed
    {
        $recipients = collect();

        // 1. Employee User (for their personal alert)
        if ($this->employee->user) {
            $recipients->push($this->employee->user);
        } else {
            $recipients->push($this->employee);
        }

        return $recipients->unique('id')->values();
    }

    public function getEventKey(): string
    {
        return 'IqamaExpiryAlert';
    }

    public function getPlaceholders(): array
    {
        $expiryDate = $this->detail->iqama_expiry 
            ? Carbon::parse($this->detail->iqama_expiry)->format('Y-m-d') 
            : '—';

        $statusText = match (true) {
            $this->daysRemaining < 0  => 'منتهية منذ ' . abs($this->daysRemaining) . ' يوم',
            $this->daysRemaining == 0 => 'تنتهي اليوم',
            default                   => 'متبقي ' . $this->daysRemaining . ' يوم',
        };

        $stageName = $this->alertStage ?: 'تنبيه انتهاء الإقامة';
        $companyName = $this->employee->company?->trade_name ?? 'الشركة';

        return [
            'alert_stage' => $stageName,
            'employee_name' => $this->employee->user?->name ?? ('موظف #' . $this->employee->id),
            'company_name' => $companyName,
            'iqama_number' => $this->detail->iqama_number ?? '—',
            'expiry_date' => $expiryDate,
            'days_remaining' => (string) $this->daysRemaining,
            'status_text' => $statusText,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}

