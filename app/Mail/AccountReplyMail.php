<?php

namespace App\Mail;

use App\Services\Mail\MailSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AccountReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fromEmail,
        public string $fromName,
        public string $replySubject,
        public string $replyBody,
        public string $sentMessageId,
        public ?string $inReplyTo = null,
        public ?string $references = null,
        public array $attachmentPaths = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: $this->replySubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: nl2br(e($this->replyBody)),
        );
    }

    public function headers(): Headers
    {
        $textHeaders = [];
        if (! empty($this->inReplyTo)) {
            $textHeaders['In-Reply-To'] = $this->inReplyTo;
        }

        return new Headers(
            messageId: trim($this->sentMessageId, '<> '),
            references: array_filter(explode(' ', $this->references ?? '')),
            text: $textHeaders,
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentPaths as $path) {
            if (Storage::disk(MailSyncService::ATTACHMENT_DISK)->exists($path)) {
                $attachments[] = Attachment::fromStorageDisk(MailSyncService::ATTACHMENT_DISK, $path);
            }
        }

        return $attachments;
    }
}
