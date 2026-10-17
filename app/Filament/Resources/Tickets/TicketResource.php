<?php

namespace App\Filament\Resources\Tickets;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    public static function getModelLabel(): string
    {
        return __('tickets.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tickets.model_plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('tickets.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.support_group');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) return null;

        $count = Ticket::accessibleBy($user)
            ->whereIn('status', [
                TicketStatus::OPEN->value,
                TicketStatus::IN_PROGRESS->value,
                TicketStatus::WAITING_USER_REPLY->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->accessibleBy(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
