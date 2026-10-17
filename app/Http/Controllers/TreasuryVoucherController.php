<?php

namespace App\Http\Controllers;

use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;

class TreasuryVoucherController extends Controller
{
    public function print(TreasuryTransaction $transaction)
    {
        $transaction->load(['company', 'paymentCollectionItem', 'executor']);
        return view('treasury.voucher-print', compact('transaction'));
    }
}
