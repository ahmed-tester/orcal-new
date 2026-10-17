<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.add_new'))
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $baseQuery = Ticket::accessibleBy($user);

        $openCount = (clone $baseQuery)->whereIn('status', [
            TicketStatus::OPEN->value,
            TicketStatus::IN_PROGRESS->value,
            TicketStatus::WAITING_USER_REPLY->value,
        ])->count();

        $tabs = [
            'all' => Tab::make(__('tickets.filters.all')),
            
            'open' => Tab::make(__('tickets.filters.open_tickets'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    TicketStatus::OPEN->value,
                    TicketStatus::IN_PROGRESS->value,
                    TicketStatus::WAITING_USER_REPLY->value,
                ]))
                ->badge($openCount)
                ->badgeColor('warning'),

            'resolved' => Tab::make(__('tickets.statuses.resolved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TicketStatus::RESOLVED->value))
                ->badgeColor('success'),

            'closed' => Tab::make(__('tickets.statuses.closed'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TicketStatus::CLOSED->value))
                ->badgeColor('gray'),
        ];

        return $tabs;
    }
}
