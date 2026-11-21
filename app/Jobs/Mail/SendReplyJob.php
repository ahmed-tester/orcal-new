<?php

namespace App\Jobs\Mail;

use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Services\Mail\EmailReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReplyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public MailMessage $email,
        public string $body,
        public ?User $user = null,
        public array $attachmentPaths = [],
    ) {}

    public function handle(EmailReplyService $replyService): void
    {
        $replyService->sendReply($this->email, $this->body, $this->user, $this->attachmentPaths);
    }
}
