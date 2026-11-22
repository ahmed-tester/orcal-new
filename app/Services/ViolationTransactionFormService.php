<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationTransactionType;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ViolationTransactionFormService
{
    /**
     * Link type identifiers.
     */
    public const LINK_SINGLE = 'single';
    public const LINK_MULTIPLE = 'multiple';
    public const LINK_UNLINKED = 'unlinked';

    /**
     * Resolve whether a transaction type value represents an outgoing disbursement.
     */
    public static function isOutgoingType(ViolationTransactionType|string|null $transactionType): bool
    {
        if ($transactionType instanceof ViolationTransactionType) {
            return $transactionType === ViolationTransactionType::OUTGOING;
        }

        return $transactionType === ViolationTransactionType::OUTGOING->value;
    }

    /**
     * Get the dynamic label for unlinked transactions based on transaction type.
     */
    public static function getUnlinkedLabel(ViolationTransactionType|string|null $transactionType = null): string
    {
        return self::isOutgoingType($transactionType)
            ? __('violations.transactions.fields.link_types.unlinked_outgoing')
            : __('violations.transactions.fields.link_types.unlinked');
    }

    /**
     * Build the dynamic notice banner for unlinked transactions based on transaction type.
     */
    public static function getUnlinkedNoticeHtml(ViolationTransactionType|string|null $transactionType = null): HtmlString
    {
        if (self::isOutgoingType($transactionType)) {
            return new HtmlString(
                '<div style="padding: 10px 14px; border-radius: 8px; background-color: #fffbeb; border: 1px solid #fde68a; color: #b45309; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">' .
                    '<span>💡</span> ' . e(__('violations.transactions.fields.unlinked_outgoing_note')) .
                '</div>'
            );
        }

        return new HtmlString(
            '<div style="padding: 10px 14px; border-radius: 8px; background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">' .
                '<span>💡</span> ' . e(__('violations.transactions.fields.unlinked_note')) .
            '</div>'
        );
    }

    /**
     * Get available link type options for toggle buttons.
     *
     * @return array<string, string>
     */
    public static function getLinkTypeOptions(ViolationTransactionType|string|null $transactionType = null): array
    {
        return [
            self::LINK_SINGLE   => __('violations.transactions.fields.link_types.single'),
            self::LINK_MULTIPLE => __('violations.transactions.fields.link_types.multiple'),
            self::LINK_UNLINKED => self::getUnlinkedLabel($transactionType),
        ];
    }

    /**
     * Get query for open/unpaid violations scoped by company, optionally including specific IDs (for edit mode).
     *
     * @param array<int> $includeIds
     */
    public static function getOpenViolationsQuery(?int $companyId, array $includeIds = []): Collection
    {
        if (! $companyId) {
            return collect();
        }

        $includeIds = array_values(array_filter(array_map('intval', $includeIds)));

        return Violation::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($includeIds) {
                $query->where(function ($open) {
                    $open->where('remaining_amount', '>', 0)
                        ->where('payment_status', '!=', ViolationPaymentStatus::PAID->value);
                });

                if (! empty($includeIds)) {
                    $query->orWhereIn('id', $includeIds);
                }
            })
            ->get();
    }

    /**
     * Options for the single violation dropdown select.
     *
     * @return array<int, string>
     */
    public static function getSingleViolationOptions(?int $companyId, ?int $includeId = null): array
    {
        $includeIds = $includeId ? [$includeId] : [];

        return self::getOpenViolationsQuery($companyId, $includeIds)
            ->mapWithKeys(function (Violation $v) {
                $code = $v->violation_number ?? "ID: {$v->id}";
                $authority = $v->issuing_authority ?? '—';
                $remaining = number_format((float) $v->remaining_amount, 2);
                $badge = __('violations.transactions.fields.remaining_badge', ['amount' => $remaining]);

                return [$v->id => "{$code} — {$authority} ({$badge})"];
            })
            ->all();
    }

    /**
     * Options for the multiple violations checkbox list with HTML formatting.
     *
     * @param array<int> $includeIds
     * @return array<int, HtmlString>
     */
    public static function getMultipleViolationOptions(?int $companyId, array $includeIds = []): array
    {
        return self::getOpenViolationsQuery($companyId, $includeIds)
            ->mapWithKeys(function (Violation $v) {
                $code = e($v->violation_number ?? "ID: {$v->id}");
                $authority = e($v->issuing_authority ?? '—');
                $remaining = number_format((float) $v->remaining_amount, 2);
                $badge = e(__('violations.transactions.fields.remaining_badge', ['amount' => $remaining]));

                $html = new HtmlString(
                    '<span style="display:inline-flex; align-items:center; gap:0.4rem; flex-wrap:wrap; font-size:0.82rem;">' .
                        '<strong style="color:#1d4ed8;">' . $code . '</strong>' .
                        '<span style="color:#64748b;">(' . $authority . ')</span>' .
                        '<span style="background:#fee2e2; color:#b91c1c; font-weight:700; font-size:0.75rem; padding:1px 6px; border-radius:4px;">' . $badge . '</span>' .
                    '</span>'
                );

                return [$v->id => $html];
            })
            ->all();
    }

    /**
     * Calculate the total remaining amount for an array of violation IDs,
     * including amounts already settled by the record being edited.
     *
     * @param array<int> $violationIds
     */
    public static function calculateTotalRemaining(array $violationIds, ?ViolationTransaction $record = null): float
    {
        if (empty($violationIds)) {
            return 0.0;
        }

        $ids = array_values(array_unique(array_map('intval', $violationIds)));
        $violations = Violation::query()->whereIn('id', $ids)->get();

        $recordSettled = [];
        if ($record && $record->transaction_type === ViolationTransactionType::OUTGOING) {
            $recordSettled = ViolationTransactionService::resolveOldSettledAmounts($record, $violations->keyBy('id'), true);
        }

        $total = 0.0;
        foreach ($violations as $violation) {
            $total += (float) $violation->remaining_amount + (float) ($recordSettled[$violation->id] ?? 0.0);
        }

        return round($total, 2);
    }

    /**
     * Handle state reset when link_type is changed.
     */
    public static function handleLinkTypeChange(?string $state, callable $set): void
    {
        match ($state) {
            self::LINK_UNLINKED => [
                $set('violation_id', null),
                $set('metadata.selected_violation_ids', []),
                $set('amount', null),
            ],
            self::LINK_SINGLE => [
                $set('metadata.selected_violation_ids', []),
                $set('amount', null),
            ],
            self::LINK_MULTIPLE => [
                $set('violation_id', null),
                $set('amount', null),
            ],
            default => null,
        };
    }

    /**
     * Handle state reset when company is changed.
     */
    public static function handleCompanyChange(callable $set): void
    {
        $set('violation_id', null);
        $set('metadata.selected_violation_ids', []);
        $set('amount', null);
    }

    /**
     * Handle calculation when multiple violations selection changes.
     */
    public static function handleMultipleViolationsUpdated(mixed $selectedIds, callable $set, ?ViolationTransaction $record = null): void
    {
        if (! empty($selectedIds) && is_array($selectedIds)) {
            $total = self::calculateTotalRemaining($selectedIds, $record);
            $set('amount', number_format($total, 2, '.', ''));
        } else {
            $set('amount', 0);
        }
    }

    /**
     * Handle auto-fill when single violation is selected.
     */
    public static function handleSingleViolationUpdated(mixed $violationId, callable $set): void
    {
        if (! $violationId) {
            return;
        }

        $violation = Violation::find($violationId);
        if ($violation) {
            $set('amount', (float) $violation->remaining_amount);
        }
    }

    /**
     * Normalize link_type, violation_id, and metadata before persisting a transaction.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeLinkData(array $data): array
    {
        $linkType = $data['link_type'] ?? self::LINK_SINGLE;
        $data['metadata'] = array_merge($data['metadata'] ?? [], [
            'link_type' => $linkType,
        ]);

        if ($linkType === self::LINK_UNLINKED) {
            $data['violation_id'] = null;
            unset($data['metadata']['selected_violation_ids']);
        } elseif ($linkType === self::LINK_MULTIPLE) {
            $data['violation_id'] = null;
        }

        return $data;
    }
}
