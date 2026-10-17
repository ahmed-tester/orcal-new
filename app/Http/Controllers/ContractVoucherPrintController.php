<?php

namespace App\Http\Controllers;

use App\Models\ContractVoucher;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class ContractVoucherPrintController extends Controller
{
    public function print(ContractVoucher $voucher)
    {
        abort_unless(auth()->user()?->can('view', $voucher), 403);

        $voucher->load(['company', 'customer', 'offer', 'creator']);
        return view('contract-vouchers.print', compact('voucher'));
    }

    public function pdf(Request $request, ContractVoucher $voucher)
    {
        abort_unless(auth()->user()?->can('view', $voucher), 403);

        $voucher->load(['company', 'customer', 'offer', 'creator']);
        $html = view('contract-vouchers.print', compact('voucher'))->render();
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts  = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'margin_top'       => 10,
            'margin_bottom'    => 10,
            'margin_left'      => 12,
            'margin_right'     => 12,
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'autoArabic'       => true,
            'baseScript'       => 1,
            'biDirectional'    => true,
            'fontDir'          => array_merge($defaultConfig['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata'         => $defaultFonts['fontdata'],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;

        $mpdf->WriteHTML($html);

        $filename = 'voucher-' . $voucher->voucher_number . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
