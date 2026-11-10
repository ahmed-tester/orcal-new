<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomSulafAlertEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public mixed $recipient,
        public string $customMessage,
        public array $placeholders = [],
        public ?Model $record = null,
        public ?string $actionUrl = null
    ) {}

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function getNotifiable(): mixed
    {
        return $this->recipient;
    }

    public function getEventKey(): string
    {
        return 'CustomSulafAlert';
    }

    public function getPlaceholders(): array
    {
        return array_merge([
            'custom_message' => $this->customMessage,
        ], $this->placeholders);
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
