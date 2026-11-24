<?php

namespace App\Filament\Resources\Mail\MailMessages\Widgets;

use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Services\Mail\MailMessageService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestImportantMail extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table->heading(__('mail_inbox.stats.latest_mail'))
            ->query(
                app(MailMessageService::class)->applyNotExcludedScope(
                    MailMessageResource::getEloquentQuery()
                )
                    ->latest('received_at')
                    ->limit(6)
            )
            ->paginated(false)
            ->recordUrl(fn ($record) => MailMessageResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('subject')->label(__('mail_inbox.fields.subject'))->limit(40),
                TextColumn::make('sender_email')->label(__('mail_inbox.fields.sender'))->limit(30),
                TextColumn::make('status')->label(__('mail_inbox.fields.status'))->badge(),
                TextColumn::make('priority')->label(__('mail_inbox.fields.priority'))->badge(),
                TextColumn::make('received_at')->label(__('mail_inbox.fields.received_at'))->since(),
            ]);
    }
}
