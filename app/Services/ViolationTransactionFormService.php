<?php

namespace App\Services;

use App\Models\Violation;
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
     * Get available link type options for toggle buttons.
     *
     * @return array<string, string>
     */
    public static function getLinkTypeOptions(): array
    {
        return [
            self::LINK_SINGLE   => __('violations.transactions.fields.link_types.single'),
            self::LINK_MULTIPLE => __('violations.transactions.fields.link_types.multiple'),
            self::LINK_UNLINKED => __('violations.transactions.fields.link_types.unlinked'),
        ];
    }

    /**
     * Get query for open/unpaid violations scoped by company.
     */
    public static function getOpenViolationsQuery(?int $companyId): Collection
    {
        if (! $companyId) {
            return collect();
        }

        return Violation::query()
            ->where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->get();
    }

    /**
     * Options for the single violation dropdown select.
     *
     * @return array<int, string>
     */
    public static function getSingleViolationOptions(?int $companyId): array
    {
        return self::getOpenViolationsQuery($companyId)
            ->mapWithKeys(function (Violation $v) {
                $code = $v->violation_number ?? "ID: {$v->id}";
                $authority = $v->issuing_authority ?? '—';
                $remaining = number_format((float) $v->remaining_amount, 2);

                return [$v->id => "{$code} — {$authority} (المتبقي: {$remaining} ريال)"];
            })
            ->all();
    }

    /**
     * Options for the multiple violations checkbox list with HTML formatting.
     *
     * @return array<int, HtmlString>
     */
    public static function getMultipleViolationOptions(?int $companyId): array
    {
        return self::getOpenViolationsQuery($companyId)
            ->mapWithKeys(function (Violation $v) {
                $code = e($v->violation_number ?? "ID: {$v->id}");
                $authority = e($v->issuing_authority ?? '—');
                $remaining = number_format((float) $v->remaining_amount, 2);

                $html = new HtmlString(
                    '<span style="display:inline-flex; align-items:center; gap:0.4rem; flex-wrap:wrap; font-size:0.82rem;">' .
                        '<strong style="color:#1d4ed8;">' . $code . '</strong>' .
                        '<span style="color:#64748b;">(' . $authority . ')</span>' .
                        '<span style="background:#fee2e2; color:#b91c1c; font-weight:700; font-size:0.75rem; padding:1px 6px; border-radius:4px;">المتبقي: ' . $remaining . ' ريال</span>' .
                    '</span>'
                );

                return [$v->id => $html];
            })
            ->all();
    }

    /**
     * Calculate the total remaining amount for an array of violation IDs.
     *
     * @param array<int> $violationIds
     */
    public static function calculateTotalRemaining(array $violationIds): float
    {
        if (empty($violationIds)) {
            return 0.0;
        }

        return (float) Violation::query()
            ->whereIn('id', $violationIds)
            ->sum('remaining_amount');
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
            ],
            self::LINK_SINGLE => [
                $set('metadata.selected_violation_ids', []),
            ],
            self::LINK_MULTIPLE => [
                $set('violation_id', null),
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
    public static function handleMultipleViolationsUpdated(mixed $selectedIds, callable $set): void
    {
        if (! empty($selectedIds) && is_array($selectedIds)) {
            $total = self::calculateTotalRemaining($selectedIds);
            $set('amount', number_format($total, 2, '.', ''));
        } else {
            $set('amount', 0);
        }
    }

    /**
     * Handle auto-fill when single violation is selected.
     */
    public static function handleSingleViolationUpdated(mixed $violationId, callable $set, callable $get): void
    {
        if ($violationId && empty($get('amount'))) {
            $violation = Violation::find($violationId);
            if ($violation) {
                $set('amount', (float) $violation->remaining_amount);
            }
        }
    }
}
