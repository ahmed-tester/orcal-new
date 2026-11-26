<?php

namespace App\Services\Payroll;

use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Departments\DepartmentManagerResolverService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FinancialApprovalNotificationService
{
    /**
     * Build a deterministic deduplication key per request, version, stage, event, and recipient.
     */
    public static function dedupKey(
        FinancialApprovalRequest $request,
        string $stage,
        string $eventKey,
        int $recipientId,
    ): string {
        return sprintf(
            'far:%d:v%d:stage:%s:event:%s:user:%d',
            $request->id,
            $request->version,
            $stage,
            $eventKey,
            $recipientId,
        );
    }

    /**
     * Check whether a notification with the same deduplication key was already stored for this recipient.
     */
    public static function alreadyDelivered(int $recipientId, string $dedupKey): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $recipientId)
            ->where('dedup_key_hash', hash('sha256', $dedupKey))
            ->exists();
    }

    /**
     * Dispatch one queued notification per eligible recipient, deduplicated by request/version/stage/recipient.
     *
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $extraParams
     */
    public static function dispatch(
        Collection $recipients,
        FinancialApprovalRequest $request,
        string $eventKey,
        string $titleKey,
        string $bodyKey,
        string $stage,
        string $color,
        array $extraParams = [],
    ): void {
        $params = array_merge([
            'number' => $request->request_number,
            'count' => $request->employees_count,
            'amount' => number_format((float) $request->total_amount, 2),
        ], $extraParams);

        $uniqueRecipients = $recipients
            ->filter(fn ($u): bool => $u instanceof User && DepartmentManagerResolverService::isActiveStatus($u->status))
            ->unique('id')
            ->values();

        foreach ($uniqueRecipients as $recipient) {
            $dedupKey = static::dedupKey($request, $stage, $eventKey, (int) $recipient->id);
            if (static::alreadyDelivered((int) $recipient->id, $dedupKey)) {
                continue;
            }

            $notification = new BaseSystemNotification(
                eventKey: $eventKey,
                customTitle: __($titleKey, $params),
                customMessage: __($bodyKey, $params),
                actionUrl: FinancialApprovalRequestResource::getUrl('view', ['record' => $request]),
                icon: 'heroicon-o-clipboard-document-check',
                color: $color,
                context: [
                    'routing_mode' => 'financial_approval',
                    'approval_request_id' => $request->id,
                    'approval_version' => $request->version,
                    'approval_stage' => $stage,
                    'dedup_key' => $dedupKey,
                ],
            );
            $notification->recordType = FinancialApprovalRequest::class;
            $notification->recordId = $request->id;

            Notification::send($recipient, $notification);
        }
    }
}
