<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Collection;


class TicketNotificationService
{
    /**
     * Get all users who should receive support staff alerts.
     */
    public static function getSupportManagers(): Collection
    {
        return User::all()->filter(function (User $user) {
            return $user->isSuperAdminOrOwner()
                || $user->can('manage_tickets')
                || $user->can('update_ticket');
        })->unique('id');
    }

    /**
     * Send notification when a new ticket is created.
     */
    public static function notifyTicketCreated(Ticket $ticket): void
    {


        $creatorName = $ticket->user?->name ?? __('tickets.unknown_user');
        $managers = self::getSupportManagers()->reject(fn ($u) => $u->id === $ticket->user_id);

        $viewUrl = url("/tickets/{$ticket->id}");



        foreach ($managers as $manager) {
            FilamentNotification::make()
                ->title(__('tickets.notifications.new_ticket_title'))
                ->body(__('tickets.notifications.new_ticket_body', [
                    'number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'user' => $creatorName,
                ]))
                ->icon('heroicon-o-ticket')
                ->status('info')
                ->actions([
                    Action::make('view')
                        ->label(__('tickets.actions.view_ticket'))
                        ->url($viewUrl)
                        ->button(),
                ])
                ->sendToDatabase($manager);


        }
    }

    /**
     * Send notification when a new reply/message is added.
     */
    public static function notifyTicketReplied(TicketMessage $message): void
    {


        $ticket = $message->ticket;
        if (!$ticket) {
            return;
        }

        $author = $message->user;
        $authorName = $author?->name ?? __('tickets.unknown_user');
        $viewUrl = url("/tickets/{$ticket->id}");

        // If internal note -> only notify managers/assigned agent
        if ($message->is_internal_note) {
            $recipients = self::getSupportManagers()->reject(fn ($u) => $u->id === $message->user_id);
            foreach ($recipients as $recipient) {
                FilamentNotification::make()
                    ->title(__('tickets.notifications.internal_note_title', ['number' => $ticket->ticket_number]))
                    ->body(__('tickets.notifications.internal_note_body', [
                        'user' => $authorName,
                        'subject' => $ticket->subject,
                    ]))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->status('warning')
                    ->actions([
                        Action::make('view')
                            ->label(__('tickets.actions.view_ticket'))
                            ->url($viewUrl)
                            ->button(),
                    ])
                    ->sendToDatabase($recipient);


            }
            return;
        }

        // Check if author is the ticket creator
        if ($message->user_id === $ticket->user_id) {
            // User replied -> notify assigned agent or managers
            $recipients = collect();
            if ($ticket->assignedTo && $ticket->assignedTo->id !== $message->user_id) {
                $recipients->push($ticket->assignedTo);
            } else {
                $recipients = self::getSupportManagers()->reject(fn ($u) => $u->id === $message->user_id);
            }

            foreach ($recipients as $recipient) {
                FilamentNotification::make()
                    ->title(__('tickets.notifications.user_replied_title', ['number' => $ticket->ticket_number]))
                    ->body(__('tickets.notifications.user_replied_body', [
                        'user' => $authorName,
                        'subject' => $ticket->subject,
                    ]))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->status('info')
                    ->actions([
                        Action::make('view')
                            ->label(__('tickets.actions.view_ticket'))
                            ->url($viewUrl)
                            ->button(),
                    ])
                    ->sendToDatabase($recipient);


            }
        } else {
            // Support agent replied -> notify the ticket owner
            if ($ticket->user && $ticket->user->id !== $message->user_id) {
                FilamentNotification::make()
                    ->title(__('tickets.notifications.staff_replied_title', ['number' => $ticket->ticket_number]))
                    ->body(__('tickets.notifications.staff_replied_body', [
                        'subject' => $ticket->subject,
                    ]))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->status('success')
                    ->actions([
                        Action::make('view')
                            ->label(__('tickets.actions.view_ticket'))
                            ->url($viewUrl)
                            ->button(),
                    ])
                    ->sendToDatabase($ticket->user);


            }
        }
    }

    /**
     * Send notification when ticket status changes.
     */
    public static function notifyStatusChanged(Ticket $ticket, TicketStatus $newStatus): void
    {


        if (!$ticket->user) {

            return;
        }

        $viewUrl = url("/tickets/{$ticket->id}");

        FilamentNotification::make()
            ->title(__('tickets.notifications.status_changed_title', ['number' => $ticket->ticket_number]))
            ->body(__('tickets.notifications.status_changed_body', [
                'subject' => $ticket->subject,
                'status' => $newStatus->getLabel(),
            ]))
            ->icon('heroicon-o-arrow-path')
            ->status('info')
            ->actions([
                Action::make('view')
                    ->label(__('tickets.actions.view_ticket'))
                    ->url($viewUrl)
                    ->button(),
            ])
            ->sendToDatabase($ticket->user);


    }

    /**
     * Send notification when ticket is assigned to staff.
     */
    public static function notifyTicketAssigned(Ticket $ticket, User $assignedUser): void
    {


        $viewUrl = url("/tickets/{$ticket->id}");

        FilamentNotification::make()
            ->title(__('tickets.notifications.assigned_title', ['number' => $ticket->ticket_number]))
            ->body(__('tickets.notifications.assigned_body', [
                'subject' => $ticket->subject,
            ]))
            ->icon('heroicon-o-user-plus')
            ->status('info')
            ->actions([
                Action::make('view')
                    ->label(__('tickets.actions.view_ticket'))
                    ->url($viewUrl)
                    ->button(),
            ])
            ->sendToDatabase($assignedUser);


    }
}
