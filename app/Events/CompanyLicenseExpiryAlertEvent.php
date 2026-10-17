<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use App\Models\Company;
use App\Models\Owner;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
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

    public function getNotifiable(): mixed
    {
        // Recipients are routed purely to users who have 'update_company' permission
        return collect();
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
            $this->daysRemaining < 0  => 'منتهية منذ ' . abs($this->daysRemaining) . ' يوم',
            $this->daysRemaining == 0 => 'تنتهي اليوم',
            default                   => 'متبقي ' . $this->daysRemaining . ' يوم',
        };

        $stageName = $this->alertStage ?: 'تنبيه انتهاء وثيقة منشأة';

        return [
            'company_name' => $this->company->trade_name ?? $this->company->name ?? 'الشركة',
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
