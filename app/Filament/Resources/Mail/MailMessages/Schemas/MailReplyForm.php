<?php

namespace App\Filament\Resources\Mail\MailMessages\Schemas;

use App\Models\Mail\MailMessage;
use App\Services\Mail\EmailReplyService;
use App\Services\Mail\MailSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class MailReplyForm
{
    /**
     * Shared reply action used by the message table and the message details page,
     * so both entry points expose the same context, validation and attachments.
     */
    public static function configure(Action $action): Action
    {
        return $action
            ->schema([
                Placeholder::make('from_account')
                    ->label(__('mail_inbox.fields.send_from'))
                    ->content(fn (MailMessage $record): string => $record->account?->name
                        ? $record->recipient_email.' ('.$record->account->name.')'
                        : (string) $record->recipient_email),
                Placeholder::make('to_recipient')
                    ->label(__('mail_inbox.fields.to'))
                    ->content(fn (MailMessage $record): string => filled($record->sender_name) && $record->sender_name !== $record->sender_email
                        ? $record->sender_email.' ('.$record->sender_name.')'
                        : (string) $record->sender_email),
                TextInput::make('reply_subject')
                    ->label(__('mail_inbox.fields.reply_subject'))
                    ->default(fn (MailMessage $record): string => static::replySubject($record))
                    ->disabled(),
                Textarea::make('body')
                    ->label(__('mail_inbox.fields.reply_body'))
                    ->placeholder(__('mail_inbox.fields.reply_body_placeholder'))
                    ->rows(6)
                    ->required(),
                FileUpload::make('attachments')
                    ->label(__('mail_inbox.fields.reply_attachments'))
                    ->helperText(__('mail_inbox.fields.reply_attachments_helper'))
                    ->multiple()
                    ->disk(MailSyncService::ATTACHMENT_DISK)
                    ->directory(fn (MailMessage $record): string => EmailReplyService::attachmentDirectory($record, auth()->user()))
                    ->preventFilePathTampering()
                    ->maxSize(20480)
                    ->columnSpanFull(),
            ])
            ->action(function (MailMessage $record, array $data, EmailReplyService $replyService): void {
                $result = $replyService->sendReply(
                    $record,
                    (string) $data['body'],
                    auth()->user(),
                    (array) ($data['attachments'] ?? [])
                );

                Notification::make()
                    ->title($result['message'])
                    ->{$result['success'] ? 'success' : 'danger'}()
                    ->send();
            });
    }

    public static function replySubject(MailMessage $message): string
    {
        $prefix = (string) __('mail_inbox.reply.subject_prefix');

        if (str_starts_with((string) $message->subject, $prefix)
            || str_starts_with((string) $message->subject, 'Re:')) {
            return (string) $message->subject;
        }

        return $prefix.$message->subject;
    }
}
