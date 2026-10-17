<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isManager = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_tickets'));

        return $schema
            ->columns(1)
            ->components([
                // 1. Basic Ticket Information
                Section::make(__('tickets.sections.ticket_info'))
                    ->icon('heroicon-o-ticket')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ticket_number')
                            ->label(__('tickets.fields.ticket_number'))
                            ->placeholder('Auto Generated')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record)),

                        TextInput::make('subject')
                            ->label(__('tickets.fields.subject'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(fn ($record) => filled($record) ? 1 : 2),

                        Select::make('category')
                            ->label(__('tickets.fields.category'))
                            ->options(TicketCategory::class)
                            ->default(TicketCategory::TECHNICAL->value)
                            ->required(),

                        Select::make('priority')
                            ->label(__('tickets.fields.priority'))
                            ->options(TicketPriority::class)
                            ->default(TicketPriority::MEDIUM->value)
                            ->required(),

                        Textarea::make('description')
                            ->label(__('tickets.fields.description'))
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                // 2. Attachments
                Section::make(__('tickets.sections.ticket_attachments'))
                    ->icon('heroicon-o-paper-clip')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('attachments')
                            ->label(__('tickets.fields.attachments'))
                            ->multiple()
                            ->directory('tickets/attachments')
                            ->maxFiles(5)
                            ->maxSize(10240) // 10MB
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ]),

                // 3. Support & Management Section (Visible to Admins / Managers or in Edit mode)
                Section::make(__('tickets.sections.ticket_management'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns(2)
                    ->visible($isManager)
                    ->schema([
                        Select::make('assigned_to')
                            ->label(__('tickets.fields.assigned_to'))
                            ->options(function () {
                                return User::all()->mapWithKeys(function (User $u) {
                                    return [$u->id => "{$u->name} ({$u->email})"];
                                });
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make('status')
                            ->label(__('tickets.fields.status'))
                            ->options(TicketStatus::class)
                            ->default(TicketStatus::OPEN->value)
                            ->required(),
                    ]),
            ]);
    }
}
