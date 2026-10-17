<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ShowUnreadNotificationsToast
{
    /**
     * Handle an incoming request.
     * Checks for new unread database notifications upon page navigation/load
     * and flashes them to the session as persistent toast alerts with a close button.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->isMethod('GET') && ! $request->ajax() && ! $request->header('X-Livewire')) {
            $user = Auth::user();
            $alertedIds = session('alerted_notification_ids', []);

            // Retrieve unread notifications that haven't been alerted as toasts yet in this session
            $unreadNotifications = $user->unreadNotifications()
                ->whereNotIn('id', $alertedIds)
                ->latest()
                ->take(5)
                ->get();

            if ($unreadNotifications->isNotEmpty()) {
                foreach ($unreadNotifications as $notification) {
                    try {
                        FilamentNotification::fromArray($notification->data ?? [])
                            ->id('toast_' . $notification->id)
                            ->persistent()
                            ->send();
                    } catch (\Throwable $e) {
                        $data = $notification->data ?? [];
                        FilamentNotification::make('toast_' . $notification->id)
                            ->title($data['title'] ?? 'إشعار جديد')
                            ->body($data['body'] ?? '')
                            ->icon($data['icon'] ?? 'heroicon-o-bell')
                            ->status($data['status'] ?? 'info')
                            ->persistent()
                            ->send();
                    }

                    $alertedIds[] = $notification->id;
                }

                // Keep the last 100 IDs to avoid session bloat
                session(['alerted_notification_ids' => array_slice($alertedIds, -100)]);
            }
        }

        return $next($request);
    }
}
