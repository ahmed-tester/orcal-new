<?php

namespace App\Observers;

use App\Enums\AssetFundSourceType;
use App\Enums\AssetFundVoucherType;
use App\Models\AssetFundVoucher;
use App\Services\CodeGeneratorService;
use Illuminate\Validation\ValidationException;

class AssetFundVoucherObserver
{
    public function creating(AssetFundVoucher $voucher): void
    {
        if (empty($voucher->voucher_number)) {
            $type = $voucher->type instanceof AssetFundVoucherType
                ? $voucher->type->value
                : (string) $voucher->type;

            $prefix = $type === AssetFundVoucherType::WITHDRAWAL->value ? 'WDR' : 'DEP';

            $voucher->voucher_number = CodeGeneratorService::generate(
                modelClass: AssetFundVoucher::class,
                column: 'voucher_number',
                prefix: $prefix,
                companyId: null,
                scopeQuery: fn ($query) => $query->where('voucher_number', 'like', $prefix . '-%'),
            );
        }

        if (empty($voucher->created_by)) {
            $voucher->created_by = auth()->id();
        }
    }

    public function saving(AssetFundVoucher $voucher): void
    {
        $source = $voucher->source_type instanceof AssetFundSourceType
            ? $voucher->source_type->value
            : (string) ($voucher->source_type ?? 'free');

        if ($source === 'free') {
            $voucher->company_id = null;
        } else {
            $voucher->party_name = null;
        }

        $type = $voucher->type instanceof AssetFundVoucherType
            ? $voucher->type->value
            : (string) ($voucher->type ?? 'deposit');

        if ($type === 'deposit') {
            $this->processDeposit($voucher);
        } elseif ($type === 'withdrawal') {
            $this->processWithdrawal($voucher);
        }
    }

    public function deleting(AssetFundVoucher $voucher): void
    {
        if ($voucher->linkedWithdrawals()->exists()) {
            throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.cannot_delete_deposit_with_withdrawals'));
        }
    }

    private function processDeposit(AssetFundVoucher $voucher): void
    {
        $voucher->disbursement_type = 'general';
        $voucher->linked_deposit_voucher_id = null;

        if ($voucher->gross_amount === null) {
            return;
        }

        $gross = (float) $voucher->gross_amount;
        $tax = (float) ($voucher->tax_amount ?? 0);
        $net = round($gross - $tax, 2);
        $voucher->net_after_tax = $net;

        $retPct = (float) ($voucher->retention_percentage ?? 0);
        $retAmt = round($net * ($retPct / 100), 2);
        $voucher->retention_amount = $retAmt;

        $fndPct = (float) ($voucher->fund_share_percentage ?? 0);
        $fndAmt = round($net * ($fndPct / 100), 2);
        $voucher->fund_share_amount = $fndAmt;

        $afterDeductions = round($net - $retAmt - $fndAmt, 2);
        $voucher->net_after_deductions = $afterDeductions;
        $voucher->total_amount = $afterDeductions;
    }

    private function processWithdrawal(AssetFundVoucher $voucher): void
    {
        if ($voucher->disbursement_type !== 'linked_deposit') {
            $voucher->linked_deposit_voucher_id = null;
        } else {
            if (empty($voucher->linked_deposit_voucher_id)) {
                throw ValidationException::withMessages([
                    'linked_deposit_voucher_id' => __('validation.required', [
                        'attribute' => __('asset_funds.asset_fund_vouchers.fields.linked_deposit_voucher'),
                    ]),
                ]);
            }

            $linkedDeposit = AssetFundVoucher::find($voucher->linked_deposit_voucher_id);
            $linkedDepositType = $linkedDeposit?->type instanceof AssetFundVoucherType
                ? $linkedDeposit->type->value
                : $linkedDeposit?->type;

            if (
                ! $linkedDeposit
                || $linkedDepositType !== AssetFundVoucherType::DEPOSIT->value
                || (int) $linkedDeposit->asset_fund_id !== (int) $voucher->asset_fund_id
            ) {
                throw ValidationException::withMessages([
                    'linked_deposit_voucher_id' => __('asset_funds.asset_fund_vouchers.errors.linked_deposit_fund_mismatch'),
                ]);
            }
        }

        if ($voucher->amount_without_tax === null) {
            return;
        }

        $net = (float) $voucher->amount_without_tax;
        $tax = (float) ($voucher->tax_amount ?? 0);
        $total = round($net + $tax, 2);
        $voucher->gross_amount = $total;
        $voucher->total_amount = $total;

        $this->validateWithdrawalBalances($voucher, $total);
    }

    private function validateWithdrawalBalances(AssetFundVoucher $voucher, float $total): void
    {
        if (! $voucher->asset_fund_id || $total <= 0) {
            return;
        }

        $fundAvailable = AssetFundVoucher::getFundGrossAvailableBalance((int) $voucher->asset_fund_id, $voucher->id);
        if ($total > $fundAvailable) {
            throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_fund_gross_balance', [
                'amount' => number_format($total, 2),
                'available' => number_format($fundAvailable, 2),
            ]));
        }

        if ($voucher->disbursement_type === 'linked_deposit' && $voucher->linked_deposit_voucher_id) {
            $depositRemaining = AssetFundVoucher::getRemainingBalanceForDeposit((int) $voucher->linked_deposit_voucher_id, $voucher->id);
            if ($total > $depositRemaining) {
                throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_deposit_balance', [
                    'remaining' => number_format($depositRemaining, 2),
                    'amount' => number_format($total, 2),
                ]));
            }
        }
    }
}
