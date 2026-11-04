<?php

namespace App\Services;

use App\Models\AssetFundVoucher;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the asset fund statement:
 * column definitions (screen + PDF), selectable cards and totals.
 */
class AssetFundStatementService
{
    /**
     * All selectable columns of the deposit table, in display order.
     *
     * @return array<string, array{label: string, width?: string, html: callable, total?: callable}>
     */
    public static function depositColumns(): array
    {
        return [
            'serial' => [
                'label' => __('asset_funds.statement.cols.serial'),
                'width' => '30px',
                'html' => fn (AssetFundVoucher $row, int $index): string => (string) ($index + 1),
            ],
            'contractor' => [
                'label' => __('asset_funds.statement.cols.contractor'),
                'html' => fn (AssetFundVoucher $row): string => e($row->contractor_name ?: '—'),
            ],
            'company_name' => [
                'label' => __('asset_funds.statement.cols.company_name'),
                'html' => fn (AssetFundVoucher $row): string => e(
                    $row->party_name ?: ($row->company?->trade_name ?? $row->company?->name ?? '—')
                ),
            ],
            'gross_amount' => [
                'label' => __('asset_funds.statement.cols.gross_amount'),
                'width' => '70px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700">'
                    .($row->gross_amount !== null ? number_format((float) $row->gross_amount, 2) : '—')
                    .'</span>',
                'total' => fn (array $summary): string => number_format($summary['gross_amount'], 2),
            ],
            'tax_amount' => [
                'label' => __('asset_funds.statement.cols.tax_amount'),
                'width' => '70px',
                'html' => fn (AssetFundVoucher $row): string => number_format((float) ($row->tax_amount ?? 0), 2),
                'total' => fn (array $summary): string => number_format($summary['tax_amount'], 2),
            ],
            'net_after_tax' => [
                'label' => __('asset_funds.statement.cols.net_after_tax'),
                'width' => '75px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700">'
                    .($row->net_after_tax !== null ? number_format((float) $row->net_after_tax, 2) : '—')
                    .'</span>',
                'total' => fn (array $summary): string => number_format($summary['net_after_tax'], 2),
            ],
            'retention_pct' => [
                'label' => __('asset_funds.statement.cols.retention_pct'),
                'width' => '80px',
                'html' => fn (AssetFundVoucher $row): string => self::amountWithPercentage(
                    $row->retention_amount,
                    $row->retention_percentage,
                ),
                'total' => fn (array $summary): string => number_format($summary['retention_amount'], 2),
            ],
            'fund_share_pct' => [
                'label' => __('asset_funds.statement.cols.fund_share_pct'),
                'width' => '80px',
                'html' => fn (AssetFundVoucher $row): string => self::amountWithPercentage(
                    $row->fund_share_amount,
                    $row->fund_share_percentage,
                ),
                'total' => fn (array $summary): string => number_format($summary['fund_share_amount'], 2),
            ],
            'net_after_deductions' => [
                'label' => __('asset_funds.statement.cols.net_after_deductions'),
                'width' => '85px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:800;color:#16a34a">'
                    .number_format((float) $row->total_amount, 2)
                    .'</span>',
                'total' => fn (array $summary): string => '<span style="color:#16a34a">'
                    .number_format($summary['net_after_deductions'], 2)
                    .'</span>',
            ],
            'date' => [
                'label' => __('asset_funds.statement.cols.date'),
                'width' => '70px',
                'html' => fn (AssetFundVoucher $row): string => $row->voucher_date?->format('Y/m/d') ?? '—',
            ],
            'invoice_no' => [
                'label' => __('asset_funds.statement.cols.invoice_no'),
                'width' => '90px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700;color:#082c50">'
                    .e($row->tax_invoice_number ?: '—')
                    .'</span>',
            ],
            'notes' => [
                'label' => __('asset_funds.statement.cols.notes'),
                'width' => '200px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="white-space:normal;word-break:break-word">'
                    .e($row->notes ?: '—')
                    .'</span>',
            ],
        ];
    }

    /**
     * All selectable columns of the withdrawal table, in display order.
     *
     * @return array<string, array{label: string, width?: string, html: callable, total?: callable}>
     */
    public static function withdrawalColumns(): array
    {
        return [
            'serial' => [
                'label' => __('asset_funds.statement.cols.serial'),
                'width' => '30px',
                'html' => fn (AssetFundVoucher $row, int $index): string => (string) ($index + 1),
            ],
            'payee' => [
                'label' => __('asset_funds.statement.cols.payee'),
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700">'
                    .e($row->party_name ?: '—')
                    .'</span>',
            ],
            'reason' => [
                'label' => __('asset_funds.statement.cols.reason'),
                'html' => fn (AssetFundVoucher $row): string => e($row->expense_reason ?: '—'),
            ],
            'amount_without_tax' => [
                'label' => __('asset_funds.statement.cols.amount_without_tax'),
                'width' => '85px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700">'
                    .($row->amount_without_tax !== null ? number_format((float) $row->amount_without_tax, 2) : '—')
                    .'</span>',
                'total' => fn (array $summary): string => number_format($summary['amount_without_tax'], 2),
            ],
            'tax_amount' => [
                'label' => __('asset_funds.statement.cols.tax_amount_opt'),
                'width' => '70px',
                'html' => fn (AssetFundVoucher $row): string => number_format((float) ($row->tax_amount ?? 0), 2),
                'total' => fn (array $summary): string => number_format($summary['tax_amount'], 2),
            ],
            'gross_amount' => [
                'label' => __('asset_funds.statement.cols.gross_with_tax'),
                'width' => '90px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:800;color:#dc2626">'
                    .number_format((float) $row->total_amount, 2)
                    .'</span>',
                'total' => fn (array $summary): string => '<span style="color:#dc2626">'
                    .number_format($summary['total_amount'], 2)
                    .'</span>',
            ],
            'date' => [
                'label' => __('asset_funds.statement.cols.date'),
                'width' => '70px',
                'html' => fn (AssetFundVoucher $row): string => $row->voucher_date?->format('Y/m/d') ?? '—',
            ],
            'voucher_no' => [
                'label' => __('asset_funds.statement.cols.voucher_no'),
                'width' => '80px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="font-weight:700;color:#082c50">'
                    .e((string) $row->voucher_number)
                    .'</span>',
            ],
            'notes' => [
                'label' => __('asset_funds.statement.cols.notes'),
                'width' => '200px',
                'html' => fn (AssetFundVoucher $row): string => '<span style="white-space:normal;word-break:break-word">'
                    .e($row->notes ?: '—')
                    .'</span>',
            ],
        ];
    }

    /**
     * All selectable summary cards, keyed by summary array key.
     *
     * @return array<string, string>
     */
    public static function summaryCards(): array
    {
        return [
            'total_deposits' => __('asset_funds.statement.summary.total_deposits'),
            'total_withdrawals' => __('asset_funds.statement.summary.total_withdrawals'),
            'total_tax' => __('asset_funds.statement.summary.total_tax'),
            'total_retention' => __('asset_funds.statement.summary.total_retention'),
            'net_balance' => __('asset_funds.statement.summary.net_balance'),
        ];
    }

    /**
     * Essential identity columns that MUST always appear in the deposit table.
     * These are not toggleable in the customization panel.
     *
     * @return array<string>
     */
    public static function permanentDepositColumns(): array
    {
        return ['serial', 'contractor', 'company_name', 'date', 'invoice_no'];
    }

    /**
     * Essential identity columns that MUST always appear in the withdrawal table.
     * These are not toggleable in the customization panel.
     *
     * @return array<string>
     */
    public static function permanentWithdrawalColumns(): array
    {
        return ['serial', 'payee', 'reason', 'date', 'voucher_no'];
    }

    /**
     * Optional / customizable financial columns for the deposit table.
     *
     * @return array<string, array{label: string, width?: string, html: callable, total?: callable}>
     */
    public static function customizableDepositColumns(): array
    {
        $permanent = self::permanentDepositColumns();

        return array_filter(
            self::depositColumns(),
            fn (string $key): bool => ! in_array($key, $permanent, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Optional / customizable financial columns for the withdrawal table.
     *
     * @return array<string, array{label: string, width?: string, html: callable, total?: callable}>
     */
    public static function customizableWithdrawalColumns(): array
    {
        $permanent = self::permanentWithdrawalColumns();

        return array_filter(
            self::withdrawalColumns(),
            fn (string $key): bool => ! in_array($key, $permanent, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Resolves the full list of deposit columns to display/print:
     * merges permanent identity columns with user-selected optional columns,
     * maintaining the canonical column order.
     *
     * @param  array<string>  $selectedOptional
     * @return array<string>
     */
    public static function resolveActiveDepositColumns(array $selectedOptional, bool $hideTable = false): array
    {
        if ($hideTable) {
            return [];
        }

        $permanent = self::permanentDepositColumns();
        $all = array_keys(self::depositColumns());

        return array_values(array_filter(
            $all,
            fn (string $key): bool => in_array($key, $permanent, true) || in_array($key, $selectedOptional, true),
        ));
    }

    /**
     * Resolves the full list of withdrawal columns to display/print:
     * merges permanent identity columns with user-selected optional columns,
     * maintaining the canonical column order.
     *
     * @param  array<string>  $selectedOptional
     * @return array<string>
     */
    public static function resolveActiveWithdrawalColumns(array $selectedOptional, bool $hideTable = false): array
    {
        if ($hideTable) {
            return [];
        }

        $permanent = self::permanentWithdrawalColumns();
        $all = array_keys(self::withdrawalColumns());

        return array_values(array_filter(
            $all,
            fn (string $key): bool => in_array($key, $permanent, true) || in_array($key, $selectedOptional, true),
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function depositColumnOptions(): array
    {
        return array_map(fn (array $column): string => $column['label'], self::customizableDepositColumns());
    }

    /**
     * @return array<string, string>
     */
    public static function withdrawalColumnOptions(): array
    {
        return array_map(fn (array $column): string => $column['label'], self::customizableWithdrawalColumns());
    }

    /**
     * Keep only the requested keys, in the canonical column order.
     * A null value means "not provided" (defaults to every available column),
     * while an empty string/array means "nothing selected".
     *
     * @param  array<string>  $available
     * @return array<string>
     */
    public static function sanitizeColumns(mixed $requested, array $available): array
    {
        if ($requested === null) {
            return $available;
        }

        if (is_string($requested)) {
            $requested = $requested === '' ? [] : explode(',', $requested);
        }

        if (! is_array($requested)) {
            return $available;
        }

        $requested = array_map('strval', $requested);

        return array_values(array_filter(
            $available,
            fn (string $key): bool => in_array($key, $requested, true),
        ));
    }

    /**
     * @return array<string, float>
     */
    public static function depositsSummary(Collection $deposits): array
    {
        return [
            'gross_amount' => (float) $deposits->sum('gross_amount'),
            'tax_amount' => (float) $deposits->sum('tax_amount'),
            'net_after_tax' => (float) $deposits->sum('net_after_tax'),
            'retention_amount' => (float) $deposits->sum('retention_amount'),
            'fund_share_amount' => (float) $deposits->sum('fund_share_amount'),
            'net_after_deductions' => (float) $deposits->sum('total_amount'),
        ];
    }

    /**
     * @return array<string, float>
     */
    public static function withdrawalsSummary(Collection $withdrawals): array
    {
        return [
            'amount_without_tax' => (float) $withdrawals->sum('amount_without_tax'),
            'tax_amount' => (float) $withdrawals->sum('tax_amount'),
            'total_amount' => (float) $withdrawals->sum('total_amount'),
        ];
    }

    /**
     * @return array<string, float>
     */
    public static function summary(Collection $deposits, Collection $withdrawals): array
    {
        $totalDeposits = (float) $deposits->sum('total_amount');
        $totalWithdrawals = (float) $withdrawals->sum('total_amount');

        return [
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'total_tax' => (float) $deposits->sum('tax_amount') + (float) $withdrawals->sum('tax_amount'),
            'total_retention' => (float) $deposits->sum('retention_amount'),
            'total_fund_share' => (float) $deposits->sum('fund_share_amount'),
            'net_balance' => $totalDeposits - $totalWithdrawals,
        ];
    }

    private static function amountWithPercentage(mixed $amount, mixed $percentage): string
    {
        $amount = (float) ($amount ?? 0);

        if ($amount <= 0) {
            return '0';
        }

        $html = number_format($amount, 2);

        if ((float) ($percentage ?? 0) > 0) {
            $html .= ' <small style="color:#64748b">('.((float) $percentage).'%)</small>';
        }

        return $html;
    }
}
