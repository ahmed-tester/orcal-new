<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailConversationStatus;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Enums\Mail\MailSyncStatus;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailAttachment;
use App\Models\Mail\MailConversation;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailSyncLog;
use App\Models\UnifiedAuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client;

class MailSyncService
{
    public const ATTACHMENT_DISK = 'local';

    public const IMPORT_OUTCOME_IMPORTED = 'imported';

    public const IMPORT_OUTCOME_REJECTED = 'rejected';

    public const IMPORT_OUTCOME_DUPLICATE = 'duplicate';

    public const IMPORT_OUTCOME_SKIPPED = 'skipped';

    public const IMPORT_OUTCOME_FAILED = 'failed';

    public function __construct(
        protected EmailClassifierService $classifier,
    ) {}

    /**
     * Test connection to the email account.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(MailAccount $account): array
    {
        if ($account->isOAuth()) {
            if (empty($account->oauth_access_token)) {
                return ['success' => false, 'message' => __('mail_inbox.sync.oauth_incomplete')];
            }

            return ['success' => true, 'message' => __('mail_inbox.sync.oauth_ok')];
        }

        try {
            $client = $this->createImapClient($account);
            $client->connect();
            $client->disconnect();

            return ['success' => true, 'message' => __('mail_inbox.sync.imap_ok')];
        } catch (\Throwable $e) {
            $msg = $this->formatImapError($e->getMessage());

            Log::error('Mail sync connection test failed', [
                'account_id' => $account->id,
                'email' => $account->email,
                'host' => $account->host,
                'port' => $account->port,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('mail_inbox.sync.imap_failed', ['message' => $msg]),
            ];
        }
    }

    /**
     * Synchronize emails for a specific account with retries.
     *
     * @return array{imported: int, rejected: int, found: int}
     */
    public function syncAccount(MailAccount $account): array
    {
        $lock = Cache::lock('mail_sync_account_'.$account->id, 180);
        if (! $lock->get()) {
            Log::info('Mail sync skipped: sync already in progress for account', [
                'account_id' => $account->id,
            ]);

            return ['imported' => 0, 'rejected' => 0, 'found' => 0];
        }

        try {
            $maxRetries = 3;
            $attempt = 0;
            $lastException = null;

            while ($attempt < $maxRetries) {
                $attempt++;
                try {
                    return $this->performSync($account);
                } catch (\Throwable $e) {
                    $lastException = $e;
                    Log::warning("Mail sync attempt {$attempt} failed for {$account->email}: {$e->getMessage()}");

                    if ($this->isAuthenticationError($e->getMessage())) {
                        break;
                    }

                    if ($attempt < $maxRetries) {
                        usleep($attempt * 500000);
                    }
                }
            }

            $rawError = $lastException?->getMessage() ?? __('mail_inbox.sync.failed_after_retries');
            $errorMessage = $this->formatImapError($rawError);

            if (MailAccount::where('id', $account->id)->exists()) {
                $account->update([
                    'status' => MailAccountStatus::CONNECTION_ERROR,
                    'error_message' => $errorMessage,
                ]);

                MailSyncLog::create([
                    'company_id' => $account->company_id,
                    'email_account_id' => $account->id,
                    'folder' => 'ALL',
                    'status' => MailSyncStatus::FAILED,
                    'error_message' => $errorMessage,
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);

                $this->notifySyncErrorIfAvailable($account, $errorMessage);
            }

            return ['imported' => 0, 'rejected' => 0, 'found' => 0];
        } finally {
            $lock->release();
        }
    }

    /**
     * Core sync execution for INBOX and Spam/Junk folders.
     *
     * @return array{imported: int, rejected: int, found: int}
     */
    protected function performSync(MailAccount $account): array
    {
        $startedAt = now();
        $account->update([
            'status' => MailAccountStatus::CONNECTED,
            'error_message' => null,
        ]);

        $totalFound = 0;
        $totalImported = 0;
        $totalRejected = 0;
        $totalFailed = 0;

        if (empty($account->host)) {
            $account->update(['last_sync_at' => now()]);

            return ['imported' => 0, 'rejected' => 0, 'found' => 0, 'failed' => 0];
        }

        $client = $this->createImapClient($account);
        $client->connect();

        $isGmail = str_contains(strtolower((string) $account->host), 'gmail');
        $foldersToCheck = $isGmail
            ? ['INBOX' => MailSourceFolder::INBOX, '[Gmail]/Spam' => MailSourceFolder::SPAM_JUNK]
            : ['INBOX' => MailSourceFolder::INBOX, 'Junk' => MailSourceFolder::SPAM_JUNK, 'Spam' => MailSourceFolder::SPAM_JUNK];

        $hasSyncedSpam = false;

        foreach ($foldersToCheck as $folderName => $sourceFolder) {
            if ($sourceFolder === MailSourceFolder::SPAM_JUNK && $hasSyncedSpam) {
                continue;
            }

            try {
                $folder = $client->getFolder($folderName);
                if (! $folder) {
                    continue;
                }

                $isInbox = $sourceFolder === MailSourceFolder::INBOX;
                $uidColumn = $isInbox ? 'last_message_uid' : 'last_spam_message_uid';
                $validityColumn = $isInbox ? 'inbox_uid_validity' : 'spam_uid_validity';
                $limit = $isInbox ? 30 : 20;

                $folderMaxUid = (int) ($account->{$uidColumn} ?? 0);
                $storedValidity = $account->{$validityColumn} ? (int) $account->{$validityColumn} : null;
                $remoteValidity = $this->extractFolderUidValidity($folder);

                if ($remoteValidity !== null) {
                    if ($storedValidity !== null && $storedValidity !== $remoteValidity) {
                        $folderMaxUid = 0;
                        $account->{$uidColumn} = 0;
                    }
                    $account->{$validityColumn} = $remoteValidity;
                }

                $initialFolderUid = $folderMaxUid;

                if ($folderMaxUid > 0) {
                    $messages = $folder->query()
                        ->leaveUnread()
                        ->limit($limit)
                        ->getByUidGreater($folderMaxUid);
                } else {
                    $messages = $folder->query()
                        ->all()
                        ->limit($limit)
                        ->get();
                }

                $foundInFolder = 0;
                $importedInFolder = 0;
                $rejectedInFolder = 0;
                $failedInFolder = 0;
                $hasRetryableFailure = false;

                foreach ($messages as $message) {
                    if (! MailAccount::where('id', $account->id)->exists()) {
                        Log::info('Mail sync stopped: account was deleted during sync', [
                            'account_id' => $account->id,
                            'folder' => $folderName,
                        ]);
                        $client->disconnect();

                        return [
                            'found' => $totalFound,
                            'imported' => $totalImported,
                            'rejected' => $totalRejected,
                            'failed' => $totalFailed,
                        ];
                    }

                    $uid = is_callable([$message, 'getUid']) ? (int) $message->getUid() : 0;

                    $totalFound++;
                    $foundInFolder++;

                    $outcome = $this->importSingleMessageWithOutcome($account, $message, $sourceFolder);
                    if ($outcome === self::IMPORT_OUTCOME_IMPORTED) {
                        $totalImported++;
                        $importedInFolder++;
                    } elseif ($outcome === self::IMPORT_OUTCOME_REJECTED) {
                        $totalRejected++;
                        $rejectedInFolder++;
                    } elseif ($outcome === self::IMPORT_OUTCOME_FAILED) {
                        $totalFailed++;
                        $failedInFolder++;
                        $hasRetryableFailure = true;
                    }

                    // Never advance the folder cursor past an unhandled retryable failure
                    if (! $hasRetryableFailure && $uid > $folderMaxUid) {
                        $folderMaxUid = $uid;
                    }
                }

                if ($folderMaxUid > $initialFolderUid) {
                    $account->{$uidColumn} = $folderMaxUid;
                }

                if ($sourceFolder === MailSourceFolder::SPAM_JUNK) {
                    $hasSyncedSpam = true;
                }

                if (MailAccount::where('id', $account->id)->exists()) {
                    $folderStatus = MailSyncStatus::SUCCESS;
                    $folderErrorMessage = null;

                    if ($failedInFolder > 0) {
                        $folderStatus = ($importedInFolder > 0 || $rejectedInFolder > 0)
                            ? MailSyncStatus::PARTIAL
                            : MailSyncStatus::FAILED;
                        $folderErrorMessage = __('mail_inbox.sync.partial_folder_error', ['count' => $failedInFolder]);
                    }

                    MailSyncLog::create([
                        'company_id' => $account->company_id,
                        'email_account_id' => $account->id,
                        'folder' => $folderName,
                        'status' => $folderStatus,
                        'messages_found' => $foundInFolder,
                        'messages_imported' => $importedInFolder,
                        'messages_rejected' => $rejectedInFolder,
                        'error_message' => $folderErrorMessage,
                        'started_at' => $startedAt,
                        'completed_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Could not sync folder {$folderName} on {$account->email}: {$e->getMessage()}");
            }
        }

        $client->disconnect();

        if (MailAccount::where('id', $account->id)->exists()) {
            $account->last_sync_at = now();
            $account->save();
        }

        return [
            'found' => $totalFound,
            'imported' => $totalImported,
            'rejected' => $totalRejected,
            'failed' => $totalFailed,
        ];
    }

    /**
     * Import a single raw or Webklex message into the emails table.
     */
    public function importSingleMessage(
        MailAccount $account,
        mixed $message,
        MailSourceFolder|string $sourceFolder = MailSourceFolder::INBOX
    ): ?bool {
        return match ($this->importSingleMessageWithOutcome($account, $message, $sourceFolder)) {
            self::IMPORT_OUTCOME_IMPORTED => true,
            self::IMPORT_OUTCOME_REJECTED => false,
            default => null,
        };
    }

    /**
     * Import a single message and return an explicit outcome so callers can distinguish
     * intentional skips and duplicates from retryable failures.
     */
    public function importSingleMessageWithOutcome(
        MailAccount $account,
        mixed $message,
        MailSourceFolder|string $sourceFolder = MailSourceFolder::INBOX
    ): string {
        try {
            $sourceFolderEnum = $sourceFolder instanceof MailSourceFolder
                ? $sourceFolder
                : MailSourceFolder::from($sourceFolder);

            $parsed = $this->extractMessageData($message);
            $senderEmail = $parsed['sender_email'];
            $subject = $parsed['subject'];
            $bodyPlain = $parsed['body_plain'];
            $bodyHtml = $parsed['body_html'];

            if ($senderEmail === '') {
                return self::IMPORT_OUTCOME_SKIPPED;
            }

            $noSubjectLabel = (string) __('mail_inbox.sync.no_subject');
            $hasSubject = $subject !== '' && $subject !== $noSubjectLabel;
            $hasBody = trim($bodyPlain) !== '' || trim(strip_tags($bodyHtml)) !== '';

            if (! $hasSubject && ! $hasBody) {
                return self::IMPORT_OUTCOME_SKIPPED;
            }

            $receivedAt = $parsed['date'] ?? now();
            $dateTimestamp = $parsed['date'] ? $parsed['date']->timestamp : 0;
            $fingerprint = md5($senderEmail.'_'.$subject.'_'.$dateTimestamp);

            $exists = MailMessage::where('email_account_id', $account->id)
                ->where(function ($q) use ($parsed, $fingerprint) {
                    if ($parsed['message_id']) {
                        $q->where('message_id', $parsed['message_id']);
                    }
                    $q->orWhere('fingerprint', $fingerprint);
                })
                ->first();

            if ($exists) {
                return self::IMPORT_OUTCOME_DUPLICATE;
            }

            $classification = $this->classifier->classify(
                $senderEmail,
                $subject,
                $bodyPlain !== '' ? $bodyPlain : strip_tags($bodyHtml),
                (int) $account->company_id
            );

            if (! MailAccount::where('id', $account->id)->exists()) {
                Log::info('Mail sync skipped message: account was deleted during classification', [
                    'account_id' => $account->id,
                    'source_folder' => $sourceFolderEnum->value,
                ]);

                return self::IMPORT_OUTCOME_SKIPPED;
            }

            $email = DB::transaction(function () use (
                $account,
                $message,
                $parsed,
                $senderEmail,
                $subject,
                $bodyPlain,
                $bodyHtml,
                $receivedAt,
                $sourceFolderEnum,
                $fingerprint,
                $classification
            ): MailMessage {
                $conversation = $this->findOrCreateConversation(
                    $account,
                    $parsed['message_id'],
                    $parsed['in_reply_to'],
                    $parsed['references'],
                    $subject,
                    $senderEmail,
                    $receivedAt
                );

                $existingThreadCount = $conversation->messages()->count();
                $hasInReplyTo = ! empty($parsed['in_reply_to']);
                $replyOverrideApplied = false;

                // Reply inside an existing conversation or with In-Reply-To is never rejected
                if ($existingThreadCount > 0 || $hasInReplyTo) {
                    if (
                        $classification['status'] === MailMessageStatus::REJECTED
                        || in_array($classification['category'], [MailCategory::SPAM, MailCategory::MARKETING], true)
                    ) {
                        $classification['status'] = MailMessageStatus::NEW;
                        $classification['category'] = MailCategory::INQUIRY;
                        $classification['priority'] = MailPriority::MEDIUM;
                        $classification['is_important'] = true;
                        $classification['classification_reason'] = (string) __('mail_inbox.sync.reply_in_thread_reason');
                        $replyOverrideApplied = true;
                    }
                }

                $email = MailMessage::create([
                    'company_id' => $account->company_id,
                    'email_account_id' => $account->id,
                    'conversation_id' => $conversation->id,
                    'message_id' => $parsed['message_id'],
                    'in_reply_to' => $parsed['in_reply_to'],
                    'references' => $parsed['references'],
                    'sender_email' => $senderEmail,
                    'sender_name' => $parsed['sender_name'],
                    'recipient_email' => $account->email,
                    'subject' => $subject,
                    'body_plain' => $bodyPlain,
                    'body_html' => $bodyHtml,
                    'received_at' => $receivedAt,
                    'source_folder' => $sourceFolderEnum,
                    'is_important' => $classification['is_important'],
                    'category' => $classification['category'],
                    'confidence' => $classification['confidence'],
                    'summary' => $classification['summary'],
                    'priority' => $classification['priority'],
                    'status' => $classification['status'],
                    'classification_reason' => $classification['classification_reason'],
                    'fingerprint' => $fingerprint,
                ]);

                Log::info('Mail sync: message imported and classified', [
                    'account_id' => $account->id,
                    'company_id' => $account->company_id,
                    'mail_message_id' => $email->id,
                    'conversation_id' => $conversation->id,
                    'source_folder' => $sourceFolderEnum->value,
                    'existing_thread_count' => $existingThreadCount,
                    'has_in_reply_to' => $hasInReplyTo,
                    'reply_override_applied' => $replyOverrideApplied,
                    'status' => $classification['status']->value,
                    'category' => $classification['category']->value,
                    'priority' => $classification['priority']->value,
                    'is_important' => $classification['is_important'],
                    'confidence' => $classification['confidence'],
                    'classification_reason' => $classification['classification_reason'],
                ]);

                $this->processAttachments($message, $email);

                $conversationStatus = $classification['status'] === MailMessageStatus::REJECTED
                    ? $conversation->status
                    : MailConversationStatus::tryFrom($classification['status']->value) ?? MailConversationStatus::NEW;

                $conversation->update([
                    'last_message_at' => $receivedAt,
                    'status' => $conversationStatus,
                ]);

                $this->recordAuditLog($account, $email, $classification);

                return $email;
            });

            if ($email->is_important || $email->status === MailMessageStatus::UNDER_REVIEW) {
                $this->notifyNewEmailIfAvailable($email);
            }

            return $email->status !== MailMessageStatus::REJECTED
                ? self::IMPORT_OUTCOME_IMPORTED
                : self::IMPORT_OUTCOME_REJECTED;
        } catch (\Throwable $e) {
            Log::error('Mail sync failed to import single message', [
                'account' => $account->email,
                'uid' => is_object($message) && is_callable([$message, 'getUid']) ? $message->getUid() : null,
                'error' => $e->getMessage(),
            ]);

            return self::IMPORT_OUTCOME_FAILED;
        }
    }

    /**
     * Extract, store on private disk, and resolve inline CID images.
     */
    public function processAttachments(mixed $message, MailMessage $email): void
    {
        if (! is_object($message) || ! method_exists($message, 'getAttachments')) {
            return;
        }

        $storedPaths = [];

        try {
            $attachments = $message->getAttachments();
            if (empty($attachments)) {
                return;
            }

            $updatedHtml = (string) ($email->body_html ?? '');
            $hasHtmlReplacements = false;

            foreach ($attachments as $att) {
                $rawName = is_callable([$att, 'getName']) ? $att->getName() : ($att->name ?? null);
                $fileName = $rawName ? (string) $rawName : 'attachment_'.Str::random(8);
                $safeFileName = preg_replace('/[^\w\.\-\p{Arabic}]/u', '_', $fileName) ?: 'attachment';

                $content = method_exists($att, 'getContent') ? $att->getContent() : ($att->content ?? null);
                if ($content === null || $content === '') {
                    continue;
                }

                $mimeType = method_exists($att, 'getMimeType') ? $att->getMimeType() : ($att->content_type ?? 'application/octet-stream');
                $size = method_exists($att, 'getSize') ? (int) $att->getSize() : strlen($content);
                $rawId = is_callable([$att, 'getId']) ? $att->getId() : ($att->id ?? null);
                $contentId = $rawId ? trim((string) $rawId, '<> ') : null;

                $isImage = str_starts_with((string) $mimeType, 'image/')
                    || in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true);
                $isInline = false;

                $storageDir = 'mail-attachments/'.date('Y/m').'/'.$email->id;
                $filePath = $storageDir.'/'.Str::random(10).'_'.$safeFileName;

                if (! Storage::disk(self::ATTACHMENT_DISK)->put($filePath, $content)) {
                    throw new \RuntimeException("Failed to store attachment {$fileName} on disk.");
                }
                $storedPaths[] = $filePath;

                $attachmentRecord = MailAttachment::create([
                    'company_id' => $email->company_id,
                    'email_id' => $email->id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'content_type' => $mimeType ?: 'application/octet-stream',
                    'file_size' => $size ?: strlen($content),
                    'content_id' => $contentId,
                    'is_inline' => false,
                ]);

                $searchCids = array_filter([$contentId, $rawName]);
                if ($isImage && $searchCids !== [] && $updatedHtml !== '') {
                    $viewUrl = route('mail-attachments.view', $attachmentRecord, false);
                    foreach ($searchCids as $cid) {
                        $escaped = preg_quote((string) $cid, '/');
                        if (preg_match('/cid:<?'.$escaped.'>?/i', $updatedHtml)) {
                            $updatedHtml = preg_replace('/cid:<?'.$escaped.'>?/i', $viewUrl, $updatedHtml) ?? $updatedHtml;
                            $hasHtmlReplacements = true;
                            $isInline = true;
                        }
                    }
                }

                if ($isInline) {
                    $attachmentRecord->update(['is_inline' => true]);
                }
            }

            if ($hasHtmlReplacements && $updatedHtml !== $email->body_html) {
                $email->update(['body_html' => $updatedHtml]);
            }
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk(self::ATTACHMENT_DISK)->delete($path);
            }

            Log::warning("Failed to process attachments for mail #{$email->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Resolve conversation threading using In-Reply-To, References, and normalized subject.
     */
    protected function findOrCreateConversation(
        MailAccount $account,
        ?string $messageId,
        ?string $inReplyTo,
        ?string $references,
        string $subject,
        string $senderEmail,
        Carbon $date
    ): MailConversation {
        if (! empty($inReplyTo)) {
            $parent = MailMessage::where('email_account_id', $account->id)
                ->where('message_id', $inReplyTo)
                ->first();
            if ($parent && $parent->conversation) {
                return $parent->conversation;
            }
        }

        if (! empty($references)) {
            $refIds = array_filter(explode(' ', $references));
            $parent = MailMessage::where('email_account_id', $account->id)
                ->whereIn('message_id', $refIds)
                ->first();
            if ($parent && $parent->conversation) {
                return $parent->conversation;
            }
        }

        $cleanSubject = trim((string) preg_replace('/^(?:Re|Fwd|رد|إعادة):\s*/iu', '', $subject));

        $conversation = MailConversation::where('email_account_id', $account->id)
            ->where('participant_email', $senderEmail)
            ->where('subject', $cleanSubject)
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return MailConversation::create([
            'company_id' => $account->company_id,
            'email_account_id' => $account->id,
            'root_message_id' => $messageId,
            'subject' => $cleanSubject !== '' ? $cleanSubject : $subject,
            'participant_email' => $senderEmail,
            'status' => MailConversationStatus::NEW,
            'last_message_at' => $date,
        ]);
    }

    /**
     * Build the Webklex IMAP configuration array for an account.
     *
     * @return array<string, mixed>
     */
    public function buildImapConfig(MailAccount $account): array
    {
        $encryption = in_array(strtolower((string) $account->encryption), ['ssl', 'tls'], true)
            ? strtolower((string) $account->encryption)
            : 'ssl';

        $host = $account->host;
        $username = $account->username ?: $account->email;
        $provider = MailAccountService::inferProvider($account->email, $host);
        $password = MailAccountService::normalizePassword($account->password, $provider) ?? '';

        if (empty($host)) {
            $lowerEmail = strtolower((string) $account->email);
            if (str_contains($lowerEmail, '@gmail.com')) {
                $host = 'imap.gmail.com';
            } elseif (str_contains($lowerEmail, '@outlook.') || str_contains($lowerEmail, '@hotmail.')) {
                $host = 'outlook.office365.com';
            } elseif (str_contains($lowerEmail, '@yahoo.')) {
                $host = 'imap.mail.yahoo.com';
            }
        }

        return [
            'host' => $host,
            'port' => $account->port ?: 993,
            'encryption' => $encryption,
            'validate_cert' => true,
            'username' => $username,
            'password' => $password,
            'protocol' => 'imap',
        ];
    }

    /**
     * Create Webklex IMAP client with SSL/TLS enforcement.
     */
    public function createImapClient(MailAccount $account): object
    {
        return Client::make($this->buildImapConfig($account));
    }

    protected function extractFolderUidValidity(object $folder): ?int
    {
        if (! method_exists($folder, 'status')) {
            return null;
        }

        try {
            $status = $folder->status();
            if (! is_array($status)) {
                return null;
            }

            $value = $status['uidvalidity'] ?? $status['UIDVALIDITY'] ?? null;

            return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     message_id: ?string,
     *     subject: string,
     *     sender_email: string,
     *     sender_name: string,
     *     body_plain: string,
     *     body_html: string,
     *     date: ?Carbon,
     *     in_reply_to: ?string,
     *     references: ?string
     * }
     */
    protected function extractMessageData(mixed $message): array
    {
        $noSubject = (string) __('mail_inbox.sync.no_subject');

        if (is_array($message)) {
            return [
                'message_id' => $message['message_id'] ?? null,
                'subject' => trim((string) ($message['subject'] ?? '')) ?: $noSubject,
                'sender_email' => (string) ($message['sender_email'] ?? ''),
                'sender_name' => (string) ($message['sender_name'] ?? ''),
                'body_plain' => (string) ($message['body_plain'] ?? ''),
                'body_html' => (string) ($message['body_html'] ?? ''),
                'date' => isset($message['received_at']) ? Carbon::parse((string) $message['received_at']) : null,
                'in_reply_to' => $message['in_reply_to'] ?? null,
                'references' => $message['references'] ?? null,
            ];
        }

        if (! is_object($message)) {
            return [
                'message_id' => null,
                'subject' => $noSubject,
                'sender_email' => '',
                'sender_name' => '',
                'body_plain' => '',
                'body_html' => '',
                'date' => null,
                'in_reply_to' => null,
                'references' => null,
            ];
        }

        if (method_exists($message, 'parseBody') && empty($message->getTextBody()) && empty($message->getHTMLBody())) {
            try {
                $message->parseBody();
            } catch (\Throwable) {
            }
        }

        $messageId = is_callable([$message, 'getMessageId']) ? (string) $message->getMessageId() : null;
        $rawSubject = is_callable([$message, 'getSubject']) ? $message->getSubject() : ($message->subject ?? null);
        $subject = is_object($rawSubject) && method_exists($rawSubject, 'toString') ? $rawSubject->toString() : (string) $rawSubject;
        $subject = trim($subject) ?: $noSubject;

        $fromAttr = is_callable([$message, 'getFrom']) ? $message->getFrom() : null;
        $fromItem = null;
        if ($fromAttr && is_object($fromAttr) && method_exists($fromAttr, 'first')) {
            $fromItem = $fromAttr->first();
        } elseif (is_iterable($fromAttr)) {
            $fromItem = $fromAttr[0] ?? null;
        } elseif (isset($message->from)) {
            $fromItem = is_object($message->from) && method_exists($message->from, 'first') ? $message->from->first() : $message->from;
        }

        $senderEmail = $fromItem ? (string) ($fromItem->mail ?? ($fromItem->email ?? '')) : '';
        $senderName = $fromItem ? (string) ($fromItem->personal ?? ($fromItem->name ?? '')) : '';

        if ($senderEmail === '' && method_exists($message, 'getHeader')) {
            $fromHeader = (string) $message->getHeader()->get('from');
            if (preg_match('/<([^>]+)>/', $fromHeader, $matches)) {
                $senderEmail = $matches[1];
            } elseif (filter_var(trim($fromHeader), FILTER_VALIDATE_EMAIL)) {
                $senderEmail = trim($fromHeader);
            }
        }

        $bodyPlain = method_exists($message, 'getTextBody') ? (string) $message->getTextBody() : '';
        $bodyHtml = method_exists($message, 'getHTMLBody') ? (string) $message->getHTMLBody() : '';

        if ($bodyPlain === '' && $bodyHtml === '' && method_exists($message, 'getBodies')) {
            $bodies = $message->getBodies();
            if (is_array($bodies)) {
                $bodyHtml = (string) ($bodies['html'] ?? '');
                $bodyPlain = (string) ($bodies['text'] ?? '');
            }
        }

        if ($bodyPlain === '' && $bodyHtml === '' && method_exists($message, 'getRawBody')) {
            $raw = (string) $message->getRawBody();
            if (trim($raw) !== '') {
                $bodyPlain = strip_tags($raw);
            }
        }

        $date = (is_callable([$message, 'getDate']) && $message->getDate())
            ? Carbon::parse((string) $message->getDate())
            : null;
        $inReplyTo = is_callable([$message, 'getInReplyTo']) ? (string) ($message->getInReplyTo() ?? '') : null;
        $references = is_callable([$message, 'getReferences'])
            ? (is_array($message->getReferences()) ? implode(' ', $message->getReferences()) : (string) ($message->getReferences() ?? ''))
            : null;

        return [
            'message_id' => $messageId ?: null,
            'subject' => $subject,
            'sender_email' => $senderEmail,
            'sender_name' => $senderName,
            'body_plain' => $bodyPlain,
            'body_html' => $bodyHtml,
            'date' => $date,
            'in_reply_to' => $inReplyTo ?: null,
            'references' => $references ?: null,
        ];
    }

    protected function isAuthenticationError(string $message): bool
    {
        return str_contains($message, 'AUTHENTICATIONFAILED')
            || str_contains($message, 'Application-specific password')
            || str_contains($message, 'Bad credentials')
            || str_contains($message, 'credentials')
            || str_contains($message, 'support.google.com/accounts/answer/185833');
    }

    protected function formatImapError(string $message): string
    {
        if (str_contains($message, 'Application-specific password required') || str_contains($message, 'support.google.com/accounts/answer/185833')) {
            return (string) __('mail_inbox.sync.app_password_hint');
        }

        if (str_contains($message, 'AUTHENTICATIONFAILED')) {
            return (string) __('mail_inbox.sync.auth_failed_hint');
        }

        return $message;
    }

    protected function recordAuditLog(MailAccount $account, MailMessage $email, array $classification): void
    {
        if (! Schema::hasTable('unified_audit_logs')) {
            return;
        }

        UnifiedAuditLog::create([
            'company_id' => $account->company_id,
            'auditable_type' => MailMessage::class,
            'auditable_id' => $email->id,
            'user_id' => auth()->id(),
            'action_type' => 'mail_synced',
            'new_status' => $classification['status']->value,
            'payload' => [
                'subject' => $email->subject,
                'sender' => $email->sender_email,
                'priority' => $classification['priority']->value,
            ],
            'created_at' => now(),
        ]);
    }

    protected function notifyNewEmailIfAvailable(MailMessage $email): void
    {
        if (class_exists(MailNotificationService::class)) {
            app(MailNotificationService::class)->sendNewEmailNotification($email);
        }
    }

    protected function notifySyncErrorIfAvailable(MailAccount $account, string $errorMessage): void
    {
        if (class_exists(MailNotificationService::class)) {
            app(MailNotificationService::class)->notifySyncError($account, $errorMessage);
        }
    }
}
