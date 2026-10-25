<?php

namespace App\Services;

use App\Models\LoanFund;
use App\Models\LoanFundLoan;
use App\Models\LoanFundLoanPayment;
use App\Models\LoanFundMemberPayment;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class LoanFundLimitService
{
    /**
     * Memoized fund cache per request lifecycle.
     */
    protected static array $fundCache = [];

    /**
     * Retrieve the active loan fund from form state with request-level memoization.
     */
    public static function getFund(callable $get, string $foreignKey = 'loan_fund_id'): ?LoanFund
    {
        $fundId = $get($foreignKey);
        if (!$fundId) {
            return null;
        }

        return static::$fundCache[$fundId] ??= LoanFund::find($fundId);
    }

    /**
     * Clear cached fund instances (useful for testing or dynamic resets).
     */
    public static function clearCache(): void
    {
        static::$fundCache = [];
    }

    /**
     * Compute full liquidity breakdown for a fund.
     */
    public static function getFundLiquidity(int|LoanFund $fund, ?int $exceptLoanId = null): array
    {
        $fundModel = $fund instanceof LoanFund ? $fund : LoanFund::find($fund);
        if (!$fundModel) {
            return [
                'subscriptions_collected'   => 0.0,
                'loan_repayments_collected' => 0.0,
                'total_inflows'             => 0.0,
                'loans_disbursed'           => 0.0,
                'available_balance'         => 0.0,
            ];
        }

        // 1. Total member subscription payments collected for this fund
        $subscriptions = (float) LoanFundMemberPayment::where('loan_fund_id', $fundModel->id)->sum('amount');

        // 2. Total loan repayments collected
        $loanRepayments = (float) LoanFundLoanPayment::whereHas('loan', function ($q) use ($fundModel) {
            $q->where('loan_fund_id', $fundModel->id);
        })->sum('collected_amount');

        $totalInflows = $subscriptions + $loanRepayments;

        // 3. Total active/disbursed loans from this fund (excluding current loan if editing)
        $loansQuery = LoanFundLoan::where('loan_fund_id', $fundModel->id);
        if ($exceptLoanId) {
            $loansQuery->where('id', '!=', $exceptLoanId);
        }
        $loansDisbursed = (float) $loansQuery->sum('loan_amount');

        $availableBalance = max(0.0, round($totalInflows - $loansDisbursed, 2));

        return [
            'subscriptions_collected'   => round($subscriptions, 2),
            'loan_repayments_collected' => round($loanRepayments, 2),
            'total_inflows'             => round($totalInflows, 2),
            'loans_disbursed'           => round($loansDisbursed, 2),
            'available_balance'         => $availableBalance,
        ];
    }

    /**
     * Get available cash in the fund treasury.
     */
    public static function getAvailableFundBalance(callable $get, string $foreignKey = 'loan_fund_id', ?int $exceptLoanId = null): float
    {
        $fund = self::getFund($get, $foreignKey);
        if (!$fund) {
            return 0.0;
        }

        return self::getFundLiquidity($fund, $exceptLoanId)['available_balance'];
    }

    /**
     * Get effective maximum loan amount permitted (min of fund limit and available cash).
     */
    public static function getEffectiveMaxLoanAmount(callable $get, string $foreignKey = 'loan_fund_id', ?int $exceptLoanId = null): ?float
    {
        $fund = self::getFund($get, $foreignKey);
        if (!$fund) {
            return null;
        }

        $available = self::getAvailableFundBalance($get, $foreignKey, $exceptLoanId);
        $fundLimit = (float) $fund->max_loan_limit;

        if ($fundLimit > 0) {
            return min($fundLimit, $available);
        }

        return $available;
    }

    /**
     * Apply maximum loan amount limit, helper text, and validation rule to a TextInput.
     */
    public static function applyLoanAmountLimit(TextInput $component, string $foreignKey = 'loan_fund_id'): TextInput
    {
        return $component
            ->minValue(1)
            ->maxValue(fn (callable $get, ?Model $record = null) => self::getEffectiveMaxLoanAmount($get, $foreignKey, $record?->id))
            ->helperText(fn (callable $get, ?Model $record = null) => self::getLoanAmountHelperText($get, $foreignKey, $record?->id))
            ->rules([self::makeLoanAmountRule($foreignKey)]);
    }

    /**
     * Apply maximum repayment installments limit, helper text, and validation rule to a TextInput.
     */
    public static function applyInstallmentsLimit(TextInput $component, string $foreignKey = 'loan_fund_id'): TextInput
    {
        return $component
            ->minValue(1)
            ->maxValue(fn (callable $get) => self::getMaxInstallments($get, $foreignKey))
            ->helperText(fn (callable $get) => self::getInstallmentsHelperText($get, $foreignKey))
            ->rules([self::makeInstallmentsRule($foreignKey)]);
    }

    /**
     * Get maximum loan amount for the selected fund.
     */
    public static function getMaxLoanAmount(callable $get, string $foreignKey = 'loan_fund_id'): ?float
    {
        $fund = self::getFund($get, $foreignKey);
        return ($fund && (float) $fund->max_loan_limit > 0) ? (float) $fund->max_loan_limit : null;
    }

    /**
     * Helper text for loan amount.
     */
    public static function getLoanAmountHelperText(callable $get, string $foreignKey = 'loan_fund_id', ?int $exceptLoanId = null): ?string
    {
        $fund = self::getFund($get, $foreignKey);
        if (!$fund) {
            return null;
        }

        $available = self::getAvailableFundBalance($get, $foreignKey, $exceptLoanId);
        $fundLimit = (float) $fund->max_loan_limit;

        $parts = [];
        $parts[] = __('funds.loan_fund_loans.helpers.available_balance_hint', [
            'balance' => number_format($available, 2),
        ]);

        if ($fundLimit > 0) {
            $parts[] = __('funds.loan_fund_loans.helpers.max_loan_limit_hint', [
                'limit' => number_format($fundLimit, 2),
            ]);
        }

        return implode(' • ', $parts);
    }

    /**
     * Validation rule Closure for loan amount (checks liquidity + limit).
     */
    public static function makeLoanAmountRule(string $foreignKey = 'loan_fund_id'): Closure
    {
        return function (callable $get, ?Model $record = null) use ($foreignKey) {
            return function (string $attribute, $value, Closure $fail) use ($get, $record, $foreignKey) {
                $fundId = $get($foreignKey);
                if (!$fundId) {
                    return;
                }

                $fund = self::getFund($get, $foreignKey);
                if (!$fund) {
                    return;
                }

                $val = (float) $value;
                $currentId = $record?->id;
                $available = self::getAvailableFundBalance($get, $foreignKey, $currentId);
                $fundLimit = (float) $fund->max_loan_limit;

                // 1. Strict Liquidity Check: Fund Treasury Balance
                if ($val > $available) {
                    $fail(__('funds.loan_fund_loans.validation.insufficient_fund_balance', [
                        'available' => number_format($available, 2),
                    ]));
                    return;
                }

                // 2. Member Cap Check: Max loan limit per member
                if ($fundLimit > 0 && $val > $fundLimit) {
                    $fail(__('funds.loan_fund_loans.validation.max_limit_exceeded', [
                        'limit' => number_format($fundLimit, 2),
                    ]));
                }
            };
        };
    }

    /**
     * Get maximum repayment months/installments for the selected fund.
     */
    public static function getMaxInstallments(callable $get, string $foreignKey = 'loan_fund_id'): ?int
    {
        $fund = self::getFund($get, $foreignKey);
        return ($fund && (int) $fund->max_repayment_months > 0) ? (int) $fund->max_repayment_months : null;
    }

    /**
     * Helper text for installments count.
     */
    public static function getInstallmentsHelperText(callable $get, string $foreignKey = 'loan_fund_id'): ?string
    {
        $months = self::getMaxInstallments($get, $foreignKey);
        return $months ? __('funds.loan_fund_loans.helpers.max_months_hint', ['months' => $months]) : null;
    }

    /**
     * Validation rule Closure for installments count.
     */
    public static function makeInstallmentsRule(string $foreignKey = 'loan_fund_id'): Closure
    {
        return function (callable $get) use ($foreignKey) {
            return function (string $attribute, $value, Closure $fail) use ($get, $foreignKey) {
                $maxMonths = self::getMaxInstallments($get, $foreignKey);
                if ($maxMonths !== null && (int) $value > $maxMonths) {
                    $fail(__('funds.loan_fund_loans.validation.max_months_exceeded', [
                        'months' => $maxMonths,
                    ]));
                }
            };
        };
    }
}
