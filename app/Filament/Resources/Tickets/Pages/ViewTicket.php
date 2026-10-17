<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketNotificationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.resources.tickets.pages.view-ticket';

    public ?string $replyMessage = '';
    public array $replyAttachments = [];
    public bool $isInternalNote = false;
    public ?string $newStatus = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $user = auth()->user();
        $isManager = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_tickets'));

        // Default next status suggestion
        if ($isManager) {
            $this->newStatus = TicketStatus::WAITING_USER_REPLY->value;
        } else {
            $this->newStatus = TicketStatus::IN_PROGRESS->value;
        }
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isManager = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_tickets'));

        return [
            /*
            Action::make('assign_to_me')
                ->label(__('tickets.actions.assign_to_me'))
                ->icon('heroicon-m-user-plus')
                ->color('success')
                ->visible(fn () => $isManager && $this->record->assigned_to !== auth()->id() && $this->record->status !== TicketStatus::CLOSED)
                ->requiresConfirmation()
                ->action(function () {
                    $currentUser = auth()->user();
                    $this->record->update([
                        'assigned_to' => $currentUser->id,
                        'status' => $this->record->status === TicketStatus::OPEN ? TicketStatus::IN_PROGRESS : $this->record->status,
                    ]);

                    TicketNotificationService::notifyTicketAssigned($this->record, $currentUser);

                    Notification::make()
                        ->title(__('tickets.notifications.ticket_assigned_success'))
                        ->success()
                        ->send();
                }),
            */

            Action::make('resolve_ticket')
                ->label(__('tickets.actions.resolve_ticket'))
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->visible(fn () => $isManager && $this->record->status !== TicketStatus::RESOLVED && $this->record->status !== TicketStatus::CLOSED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => TicketStatus::RESOLVED,
                        'resolved_at' => now(),
                    ]);

                    TicketNotificationService::notifyStatusChanged($this->record, TicketStatus::RESOLVED);

                    Notification::make()
                        ->title(__('tickets.notifications.status_updated_success'))
                        ->success()
                        ->send();
                }),

            Action::make('close_ticket')
                ->label(__('tickets.actions.close_ticket'))
                ->icon('heroicon-m-lock-closed')
                ->color('gray')
                ->visible(fn () => ($isManager || $this->record->user_id === auth()->id()) && $this->record->status !== TicketStatus::CLOSED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => TicketStatus::CLOSED,
                        'closed_at' => now(),
                    ]);

                    TicketNotificationService::notifyStatusChanged($this->record, TicketStatus::CLOSED);

                    Notification::make()
                        ->title(__('tickets.notifications.status_updated_success'))
                        ->success()
                        ->send();
                }),

            Action::make('reopen_ticket')
                ->label(__('tickets.actions.reopen_ticket'))
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === TicketStatus::CLOSED || $this->record->status === TicketStatus::RESOLVED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => TicketStatus::OPEN,
                        'closed_at' => null,
                        'resolved_at' => null,
                    ]);

                    TicketNotificationService::notifyStatusChanged($this->record, TicketStatus::OPEN);

                    Notification::make()
                        ->title(__('tickets.notifications.status_updated_success'))
                        ->success()
                        ->send();
                }),

            EditAction::make()
                ->visible(fn () => $isManager),
        ];
    }

    public function postReply(): void
    {
        $this->validate([
            'replyMessage' => ['required', 'string', 'min:2'],
        ]);

        $user = auth()->user();
        $isManager = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_tickets'));

        $message = TicketMessage::create([
            'ticket_id' => $this->record->id,
            'user_id' => $user->id,
            'message' => $this->replyMessage,
            'attachments' => $this->replyAttachments ?: null,
            'is_internal_note' => $isManager && $this->isInternalNote,
        ]);

        // Auto update status if selected
        if (!empty($this->newStatus)) {
            $newStatusEnum = TicketStatus::tryFrom($this->newStatus);
            if ($newStatusEnum && $newStatusEnum !== $this->record->status) {
                $updateData = ['status' => $newStatusEnum];
                if ($newStatusEnum === TicketStatus::RESOLVED) {
                    $updateData['resolved_at'] = now();
                } elseif ($newStatusEnum === TicketStatus::CLOSED) {
                    $updateData['closed_at'] = now();
                }
                $this->record->update($updateData);
                TicketNotificationService::notifyStatusChanged($this->record, $newStatusEnum);
            }
        }

        // Send notifications
        TicketNotificationService::notifyTicketReplied($message);

        // Reset input fields
        $this->reset(['replyMessage', 'replyAttachments', 'isInternalNote']);
        $this->record->refresh();

        Notification::make()
            ->title(__('tickets.notifications.reply_sent_success'))
            ->success()
            ->send();
    }
}
