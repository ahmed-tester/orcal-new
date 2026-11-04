<?php

namespace App\Http\Controllers;

use App\Models\TreasuryTransaction;

class TreasuryVoucherController extends Controller
{
    public function print(TreasuryTransaction $transaction)
    {
        $transaction->load(['company', 'paymentCollectionItem', 'executor.user']);

        return view('treasury.voucher-print', compact('transaction'));
    }
}
