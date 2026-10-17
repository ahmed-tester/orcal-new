<?php

namespace App\Livewire;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationAlertPoller extends Component
{
    /**
     * Timestamp of the last check — only notifications created AFTER this are shown as toasts.
     */
    public string $lastCheckedAt = '';

    public function mount(): void
    {
        $this->lastCheckedAt = now()->toIso8601String();
    }

    /**
     * Called every polling interval.
     * Checks for new unread notifications and dispatches Filament toasts.
     */
    public function checkForNewNotifications(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $newNotifications = $user->unreadNotifications()
            ->where('created_at', '>', $this->lastCheckedAt)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($newNotifications->isEmpty()) {
            return;
        }

        foreach ($newNotifications as $notification) {
            $data = $notification->data;

            FilamentNotification::make()
                ->title($data['title'] ?? 'إشعار جديد')
                ->body($data['body'] ?? '')
                ->icon($data['icon'] ?? 'heroicon-o-bell')
                ->status($data['status'] ?? 'info')
                ->duration(5000)
                ->send();
        }

        $this->lastCheckedAt = $newNotifications->last()->created_at->toIso8601String();
    }

    public function render()
    {
        return view('livewire.notification-alert-poller');
    }
}
