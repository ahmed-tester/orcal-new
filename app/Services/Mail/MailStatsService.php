<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MailStatsService
{
    /**
     * @return array{
     *     total: int,
     *     unread: int,
     *     important: int,
     *     review: int,
     *     today: int,
     *     reply_rate: int,
     *     avg_reply_hours: float,
     *     urgent_unanswered: int,
     *     connected_accounts: int
     * }
     */
    public function forUser(?User $user): array
    {
        $active = fn (): Builder => $this->activeMessages($user);
        $messageService = app(MailMessageService::class);

        $total = $active()->count();
        $unread = $active()->where('is_read', false)->count();
        $important = $messageService->applyTabScope(
            MailAccessService::scopeRecords(MailMessage::query(), $user, 'emails'),
            MailMessageService::TAB_IMPORTANT
        )->count();
        $review = $messageService->applyTabScope(
            MailAccessService::scopeRecords(MailMessage::query(), $user, 'emails'),
            MailMessageService::TAB_UNDER_REVIEW
        )->count();
        $today = $active()->whereDate('received_at', today())->count();
        $urgentUnanswered = $active()
            ->where('priority', MailPriority::HIGH->value)
            ->whereNull('replied_at')
            ->count();

        $repliedCount = $active()->whereNotNull('replied_at')->count();
        $replyRate = $total > 0 ? (int) round(($repliedCount / $total) * 100) : 0;

        $repliedMessages = $active()
            ->whereNotNull('replied_at')
            ->whereNotNull('received_at')
            ->get(['received_at', 'replied_at']);

        $avgReplyHours = $repliedMessages->isEmpty()
            ? 0.0
            : round($repliedMessages->avg(
                fn (MailMessage $message): float => abs($message->received_at->diffInMinutes($message->replied_at)) / 60
            ), 1);

        $connectedAccounts = MailAccessService::scopeAccounts(MailAccount::query(), $user)
            ->where('status', MailAccountStatus::CONNECTED->value)
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'important' => $important,
            'review' => $review,
            'today' => $today,
            'reply_rate' => $replyRate,
            'avg_reply_hours' => $avgReplyHours,
            'urgent_unanswered' => $urgentUnanswered,
            'connected_accounts' => $connectedAccounts,
        ];
    }

    protected function activeMessages(?User $user): Builder
    {
        return app(MailMessageService::class)->applyNotExcludedScope(
            MailAccessService::scopeRecords(MailMessage::query(), $user, 'emails')
        );
    }
}
