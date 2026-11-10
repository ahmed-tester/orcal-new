<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyLicenseExpiryAlertEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Company $company,
        public string $licenseType,
        public ?string $licenseNumber,
        public ?string $expiryDate,
        public int $daysRemaining,
        public ?string $alertStage = null,
        public ?string $customMessage = null
    ) {}

    public function getRecord(): ?Model
    {
        return $this->company;
    }

    public function getActionUrl(): ?string
    {
        return url("/companies/{$this->company->id}/edit");
    }

    public function getNotifiable(): mixed
    {
        return $this->company;
    }

    public function getEventKey(): string
    {
        return 'CompanyLicenseExpiryAlert';
    }

    public function getPlaceholders(): array
    {
        $expiryFormatted = $this->expiryDate
            ? Carbon::parse($this->expiryDate)->format('Y-m-d')
            : '—';

        $statusText = match (true) {
            $this->daysRemaining < 0 => __('notifications.stages.days_overdue', ['days' => abs($this->daysRemaining)]),
            $this->daysRemaining == 0 => __('notifications.stages.expires_today'),
            default => __('notifications.stages.days_remaining', ['days' => $this->daysRemaining]),
        };

        $stageName = $this->alertStage ?: __('notifications.events.CompanyLicenseExpiryAlert.name');

        return [
            'company_name' => $this->company->trade_name ?? $this->company->name ?? __('notifications.alerts.company_fallback'),
            'license_type' => $this->licenseType,
            'license_number' => $this->licenseNumber ?? '—',
            'expiry_date' => $expiryFormatted,
            'days_remaining' => (string) $this->daysRemaining,
            'status_text' => $statusText,
            'alert_stage' => $stageName,
        ];
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
