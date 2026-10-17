<?php

namespace App\Events;

use App\Interfaces\NotificationEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DueDateReminderEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param mixed $recipient The selected recipient (User, Employee, etc.)
     * @param string|null $customMessage Custom message entered by the user
     * @param array<string, mixed> $placeholders Dynamic variables
     */
    public function __construct(
        public mixed $recipient,
        public ?string $customMessage = null,
        public array $placeholders = []
    ) {}

    public function getNotifiable(): mixed
    {
        return $this->recipient;
    }

    public function getEventKey(): string
    {
        return 'DueDateReminder';
    }

    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
