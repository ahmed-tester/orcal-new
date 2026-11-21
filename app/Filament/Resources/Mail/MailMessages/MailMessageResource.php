<?php

namespace App\Filament\Resources\Mail\MailMessages;

use App\Enums\Mail\MailMessageStatus;
use App\Filament\Resources\Mail\MailMessages\Pages\ListMailMessages;
use App\Filament\Resources\Mail\MailMessages\Pages\ViewMailMessage;
use App\Filament\Resources\Mail\MailMessages\Tables\MailMessagesTable;
use App\Models\Mail\MailMessage;
use App\Services\Mail\MailAccessService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailMessageResource extends Resource
{
    protected static ?string $model = MailMessage::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    public static function getModelLabel(): string
    {
        return __('mail_inbox.models.message.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_inbox.models.message.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail_inbox.nav.messages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.mail_messages_group');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $count = MailAccessService::scopeRecords(MailMessage::query(), $user, 'emails')
            ->where('is_read', false)
            ->where('status', '!=', MailMessageStatus::REJECTED->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return MailAccessService::scopeRecords(parent::getEloquentQuery(), auth()->user(), 'emails');
    }

    public static function table(Table $table): Table
    {
        return MailMessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailMessages::route('/'),
            'view' => ViewMailMessage::route('/{record}'),
        ];
    }
}
