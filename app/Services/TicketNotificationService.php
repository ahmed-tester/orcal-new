<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use Illuminate\Support\Collection;

class TicketNotificationService
{
    public static function getSupportManagers(Ticket $ticket, array $excludeUserIds = []): Collection
    {
        return NotificationRecipientService::getQualifiedRecipients($ticket, excludeUserIds: $excludeUserIds);
    }

    public static function notifyTicketCreated(Ticket $ticket): void
    {
        static::send($ticket, 'TicketCreated', 'new_ticket', [
            'user' => $ticket->user?->name ?? __('tickets.unknown_user'),
        ], 'heroicon-o-ticket', 'info', [$ticket->user_id]);
    }

    public static function notifyTicketReplied(TicketMessage $message): void
    {
        if (! $ticket = $message->ticket) {
            return;
        }

        [$event, $key, $icon, $color] = match (true) {
            (bool) $message->is_internal_note => ['TicketInternalNote', 'internal_note', 'heroicon-o-chat-bubble-left-ellipsis', 'warning'],
            $message->user_id === $ticket->user_id => ['TicketUserReplied', 'user_replied', 'heroicon-o-chat-bubble-bottom-center-text', 'info'],
            default => ['TicketStaffReplied', 'staff_replied', 'heroicon-o-chat-bubble-left-right', 'success'],
        };

        static::send($ticket, $event, $key, [
            'user' => $message->user?->name ?? __('tickets.unknown_user'),
        ], $icon, $color, [$message->user_id]);
    }

    public static function notifyStatusChanged(Ticket $ticket, TicketStatus $newStatus): void
    {
        static::send($ticket, 'TicketStatusChanged', 'status_changed', ['status' => $newStatus->getLabel()], 'heroicon-o-arrow-path');
    }

    public static function notifyTicketAssigned(Ticket $ticket, User $assignedUser): void
    {
        static::send($ticket, 'TicketAssigned', 'assigned', [], 'heroicon-o-user-plus');
    }

    private static function send(
        Ticket $ticket,
        string $event,
        string $key,
        array $parameters = [],
        string $icon = 'heroicon-o-bell',
        string $color = 'info',
        array $excludeUserIds = [],
    ): void {
        NotificationDispatcher::translated(
            record: $ticket,
            eventKey: $event,
            titleKey: "tickets.notifications.{$key}_title",
            bodyKey: "tickets.notifications.{$key}_body",
            parameters: $parameters + ['number' => $ticket->ticket_number, 'subject' => $ticket->subject],
            icon: $icon,
            color: $color,
            excludeUserIds: array_filter($excludeUserIds),
        );
    }
}
