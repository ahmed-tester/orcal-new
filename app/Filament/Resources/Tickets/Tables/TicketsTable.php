<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketNotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        $currentUser = auth()->user();
        $isManager = $currentUser && ($currentUser->isSuperAdminOrOwner() || $currentUser->can('manage_tickets'));

        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                TextColumn::make('ticket_number')
                    ->label(__('tickets.fields.ticket_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('subject')
                    ->label(__('tickets.fields.subject'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->subject)
                    ->weight('medium'),

                TextColumn::make('user.name')
                    ->label(__('tickets.fields.user'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->visible($isManager),

                TextColumn::make('category')
                    ->label(__('tickets.fields.category'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('tickets.fields.status'))
                    ->badge()
                    ->sortable(),

                /*
                TextColumn::make('assignedTo.name')
                    ->label(__('tickets.fields.assigned_to'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->visible($isManager),
                */

                TextColumn::make('last_reply_at')
                    ->label(__('tickets.fields.last_reply_at'))
                    ->since()
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('my_tickets')
                    ->label(__('tickets.filters.my_tickets'))
                    ->query(fn (Builder $query) => $query->where('user_id', auth()->id()))
                    ->visible($isManager),

                /*
                Filter::make('assigned_to_me')
                    ->label(__('tickets.filters.assigned_to_me'))
                    ->query(fn (Builder $query) => $query->where('assigned_to', auth()->id()))
                    ->visible($isManager),
                */

                Filter::make('open_tickets')
                    ->label(__('tickets.filters.open_tickets'))
                    ->query(fn (Builder $query) => $query->whereIn('status', [
                        TicketStatus::OPEN->value,
                        TicketStatus::IN_PROGRESS->value,
                        TicketStatus::WAITING_USER_REPLY->value,
                    ])),

                SelectFilter::make('status')
                    ->label(__('tickets.fields.status'))
                    ->options(TicketStatus::class),

                SelectFilter::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->options(TicketPriority::class),

                SelectFilter::make('category')
                    ->label(__('tickets.fields.category'))
                    ->options(TicketCategory::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('tickets.actions.view_ticket'))
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('primary'),

                /*
                Action::make('assign_to_me')
                    ->label(__('tickets.actions.assign_to_me'))
                    ->icon('heroicon-m-user-plus')
                    ->color('success')
                    ->visible(fn (Ticket $record) => $isManager && $record->assigned_to !== auth()->id() && $record->status !== TicketStatus::CLOSED)
                    ->requiresConfirmation()
                    ->action(function (Ticket $record) {
                        $user = auth()->user();
                        $record->update([
                            'assigned_to' => $user->id,
                            'status' => $record->status === TicketStatus::OPEN ? TicketStatus::IN_PROGRESS : $record->status,
                        ]);

                        TicketNotificationService::notifyTicketAssigned($record, $user);

                        Notification::make()
                            ->title(__('tickets.notifications.ticket_assigned_success'))
                            ->success()
                            ->send();
                    }),
                */

                Action::make('change_status')
                    ->label(__('tickets.actions.close_ticket'))
                    ->icon('heroicon-m-check-circle')
                    ->color('warning')
                    ->visible(fn (Ticket $record) => $isManager && $record->status !== TicketStatus::CLOSED)
                    ->form([
                        Select::make('status')
                            ->label(__('tickets.fields.status'))
                            ->options(TicketStatus::class)
                            ->required(),
                    ])
                    ->fillForm(fn (Ticket $record) => [
                        'status' => $record->status->value,
                    ])
                    ->action(function (Ticket $record, array $data) {
                        $oldStatus = $record->status;
                        $newStatus = TicketStatus::from($data['status']);
                        
                        $updateData = ['status' => $newStatus];
                        if ($newStatus === TicketStatus::RESOLVED) {
                            $updateData['resolved_at'] = now();
                        } elseif ($newStatus === TicketStatus::CLOSED) {
                            $updateData['closed_at'] = now();
                        }

                        $record->update($updateData);

                        if ($oldStatus !== $newStatus) {
                            TicketNotificationService::notifyStatusChanged($record, $newStatus);
                        }

                        Notification::make()
                            ->title(__('tickets.notifications.status_updated_success'))
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->visible(fn (Ticket $record) => $isManager),

                DeleteAction::make()
                    ->visible(fn (Ticket $record) => $isManager),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isManager),
                ]),
            ]);
    }
}
