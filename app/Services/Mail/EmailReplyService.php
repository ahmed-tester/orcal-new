<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailConversationStatus;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailReplyStatus;
use App\Mail\AccountReplyMail;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailReply;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class EmailReplyService
{
    /**
     * Send a reply to an existing email strictly from the receiving account.
     *
     * @param  array<string>  $attachmentPaths
     * @return array{success: bool, reply: ?MailReply, message: string}
     */
    public function sendReply(MailMessage $email, string $body, ?User $user = null, array $attachmentPaths = []): array
    {
        $user ??= auth()->user();
        abort_unless($user && $user->status === 'active', 403);
        Gate::forUser($user)->authorize('reply', $email);
        $directory = self::attachmentDirectory($email, $user).'/';
        foreach ($attachmentPaths as $path) {
            if (! is_string($path) || ! str_starts_with($path, $directory)
                || str_contains($path, '..') || str_contains($path, '\\')
                || ! Storage::disk(MailSyncService::ATTACHMENT_DISK)->exists($path)) {
                throw ValidationException::withMessages(['attachments' => __('mail_inbox.validation.attachment_not_allowed')]);
            }
        }

        $account = $email->account;

        if (! $account) {
            return [
                'success' => false,
                'reply' => null,
                'message' => (string) __('mail_inbox.reply.missing_account'),
            ];
        }

        $user = $user ?? auth()->user();
        $replyPrefix = (string) __('mail_inbox.reply.subject_prefix');
        $hasPrefix = str_starts_with($email->subject, 'Re:')
            || str_starts_with($email->subject, 'RE:')
            || ($replyPrefix !== '' && str_starts_with($email->subject, $replyPrefix));

        $replySubject = $hasPrefix ? $email->subject : $replyPrefix.$email->subject;
        $sentMessageId = Str::uuid().'@'.substr(strrchr((string) $account->email, '@') ?: '', 1);

        try {
            $this->configureAccountMailer($account);

            $mailable = new AccountReplyMail(
                fromEmail: (string) $account->email,
                fromName: (string) $account->name,
                replySubject: $replySubject,
                replyBody: $body,
                sentMessageId: $sentMessageId,
                inReplyTo: $email->message_id,
                references: trim(($email->references ?? '').' '.$email->message_id),
                attachmentPaths: $attachmentPaths,
            );

            $this->deliverMail($mailable, $email);

            $reply = MailReply::create([
                'company_id' => $account->company_id,
                'email_id' => $email->id,
                'email_account_id' => $account->id,
                'user_id' => $user?->id,
                'to_email' => $email->sender_email,
                'subject' => $replySubject,
                'body' => $body,
                'sent_message_id' => '<'.$sentMessageId.'>',
                'status' => MailReplyStatus::SENT,
                'sent_at' => now(),
            ]);

            $disk = Storage::disk(MailSyncService::ATTACHMENT_DISK);
            foreach ($attachmentPaths as $path) {
                if ($disk->exists($path)) {
                    MailAttachment::create([
                        'company_id' => $account->company_id,
                        'email_id' => $email->id,
                        'reply_id' => $reply->id,
                        'file_name' => basename($path),
                        'file_path' => $path,
                        'content_type' => $disk->mimeType($path) ?: 'application/octet-stream',
                        'file_size' => $disk->size($path) ?: 0,
                        'is_inline' => false,
                    ]);
                }
            }

            $email->update([
                'status' => MailMessageStatus::REPLIED,
                'replied_at' => now(),
            ]);

            if ($email->conversation) {
                $email->conversation->update([
                    'status' => MailConversationStatus::REPLIED,
                    'last_message_at' => now(),
                ]);
            }

            $this->recordAuditLog($account, $email, $reply, $user);

            return [
                'success' => true,
                'reply' => $reply,
                'message' => (string) __('mail_inbox.reply.sent_success', ['email' => $account->email]),
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to send mail reply from {$account->email} to {$email->sender_email}: {$e->getMessage()}");

            $failedReply = MailReply::create([
                'company_id' => $account->company_id,
                'email_id' => $email->id,
                'email_account_id' => $account->id,
                'user_id' => $user?->id,
                'to_email' => $email->sender_email,
                'subject' => $replySubject,
                'body' => $body,
                'status' => MailReplyStatus::FAILED,
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ]);

            return [
                'success' => false,
                'reply' => $failedReply,
                'message' => (string) __('mail_inbox.reply.sent_failed', ['message' => $e->getMessage()]),
            ];
        }
    }

    public static function attachmentDirectory(MailMessage $message, User $user): string
    {
        return 'mail-replies/'.$message->company_id.'/'.$message->id.'/'.$user->id;
    }

    /**
     * Dynamically configure SMTP mailer for the specific account.
     */
    protected function configureAccountMailer(MailAccount $account): void
    {
        if (app()->environment('testing')) {
            config([
                'mail.mailers.account_smtp' => [
                    'transport' => 'array',
                ],
            ]);

            return;
        }

        config([
            'mail.mailers.account_smtp' => $this->buildSmtpConfig($account),
        ]);

        Mail::purge('account_smtp');

        if ($account->isOutlookOAuth()) {
            $transport = Mail::mailer('account_smtp')->getSymfonyTransport();
            if ($transport instanceof EsmtpTransport) {
                $transport->setAuthenticators([new XOAuth2Authenticator]);
            }
        }
    }

    /**
     * Build the SMTP mailer configuration array for an account.
     *
     * @return array<string, mixed>
     */
    public function buildSmtpConfig(MailAccount $account): array
    {
        $emailDomain = strtolower(substr(strrchr((string) $account->email, '@') ?: '', 1));

        if ($account->isOutlookOAuth()) {
            $accessToken = app(MicrosoftMailOAuthService::class)->ensureValidAccessToken($account);
            $isPersonal = in_array($emailDomain, ['outlook.com', 'hotmail.com', 'live.com', 'msn.com'], true);

            return [
                'transport' => 'smtp',
                'host' => $isPersonal ? 'smtp-mail.outlook.com' : 'smtp.office365.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => $account->email,
                'password' => $accessToken,
                'timeout' => 25,
                'verify_peer' => true,
            ];
        }

        $host = strtolower(trim((string) ($account->host ?? '')));
        $isGmail = str_contains($host, 'gmail') || $emailDomain === 'gmail.com';

        $provider = MailAccountService::inferProvider($account->email, $account->host);
        $username = $isGmail ? $account->email : ($account->username ?: $account->email);
        $password = MailAccountService::normalizePassword($account->password, $provider) ?? '';

        $smtpHost = $account->host;
        $smtpPort = 465;
        $encryption = 'ssl';

        if ($isGmail) {
            $smtpHost = 'smtp.gmail.com';
        } elseif (str_contains($host, 'outlook') || str_contains($host, 'office365') || in_array($emailDomain, ['outlook.com', 'hotmail.com', 'live.com'], true)) {
            $smtpHost = 'smtp.office365.com';
            $smtpPort = 587;
            $encryption = 'tls';
        } elseif (str_starts_with($host, 'imap.')) {
            $smtpHost = 'mail.'.substr($host, 5);
        } elseif (str_starts_with($host, 'mail.')) {
            $smtpHost = $host;
        }

        return [
            'transport' => 'smtp',
            'host' => $smtpHost,
            'port' => $smtpPort,
            'encryption' => $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => 25,
            'verify_peer' => true,
        ];
    }

    /**
     * Deliver mail with automatic 465 <-> 587 port fallback.
     */
    protected function deliverMail(AccountReplyMail $mailable, MailMessage $email): void
    {
        try {
            Mail::mailer('account_smtp')
                ->to($email->sender_email, $email->sender_name)
                ->send($mailable);
        } catch (\Throwable $e) {
            if ($email->account?->isOutlookOAuth()) {
                throw $e;
            }

            $currentPort = config('mail.mailers.account_smtp.port');

            if ($currentPort == 465) {
                config([
                    'mail.mailers.account_smtp.port' => 587,
                    'mail.mailers.account_smtp.encryption' => 'tls',
                    'mail.mailers.account_smtp.verify_peer' => true,
                ]);
                Mail::purge('account_smtp');

                Mail::mailer('account_smtp')
                    ->to($email->sender_email, $email->sender_name)
                    ->send($mailable);
            } elseif ($currentPort == 587) {
                config([
                    'mail.mailers.account_smtp.port' => 465,
                    'mail.mailers.account_smtp.encryption' => 'ssl',
                    'mail.mailers.account_smtp.verify_peer' => true,
                ]);
                Mail::purge('account_smtp');

                Mail::mailer('account_smtp')
                    ->to($email->sender_email, $email->sender_name)
                    ->send($mailable);
            } else {
                throw $e;
            }
        }
    }

    protected function recordAuditLog(MailAccount $account, MailMessage $email, MailReply $reply, ?User $user): void
    {
        if (! Schema::hasTable('unified_audit_logs')) {
            return;
        }

        UnifiedAuditLog::create([
            'company_id' => $account->company_id,
            'auditable_type' => MailMessage::class,
            'auditable_id' => $email->id,
            'user_id' => $user?->id,
            'action_type' => 'mail_replied',
            'new_status' => MailMessageStatus::REPLIED->value,
            'payload' => [
                'reply_id' => $reply->id,
                'from' => $account->email,
                'to' => $email->sender_email,
                'subject' => $reply->subject,
            ],
            'created_at' => now(),
        ]);
    }
}
