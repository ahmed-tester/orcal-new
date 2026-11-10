<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
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

    public function getRecord(): ?Model
    {
        return $this->employee;
    }

    public function getActionUrl(): ?string
    {
        return url("/foreign-employees/{$this->employee->id}/edit");
    }

    public function getNotifiable(): mixed
    {
        return $this->employee->user ?? $this->employee;
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
            $this->daysRemaining < 0 => __('notifications.stages.days_overdue', ['days' => abs($this->daysRemaining)]),
            $this->daysRemaining == 0 => __('notifications.stages.expires_today'),
            default => __('notifications.stages.days_remaining', ['days' => $this->daysRemaining]),
        };

        $stageName = $this->alertStage ?: __('notifications.events.IqamaExpiryAlert.name');
        $companyName = $this->employee->company?->trade_name ?? __('notifications.alerts.company_fallback');

        return [
            'alert_stage' => $stageName,
            'employee_name' => $this->employee->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $this->employee->id]),
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
