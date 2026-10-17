<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class OfferPdfController extends Controller
{
    /**
     * تحميل العرض كملف PDF
     */
    public function download(Request $request, Offer $offer)
    {
        $mpdf = $this->buildPdf($offer);
        $filename = 'offer-' . $offer->offer_number . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * معاينة العرض في المتصفح (Print Preview)
     */
    public function preview(Request $request, Offer $offer)
    {
        $mpdf = $this->buildPdf($offer);
        $filename = 'offer-' . $offer->offer_number . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * بناء الـ PDF من الـ Blade view
     */
    private function buildPdf(Offer $offer): Mpdf
    {
        $offer->load([
            'company',
            'customer',
            'manager.user',
            'manager.jobTitle',
            'supervisor.user',
            'supervisor.jobTitle',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'creator',
        ]);

        // ─── إعداد mPDF مع دعم العربية ──────────────────────────────
        $defaultConfig  = (new ConfigVariables())->getDefaults();
        $defaultFonts   = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'                  => 'utf-8',
            'format'                => 'A4',
            'margin_top'            => 8,
            'margin_bottom'         => 8,
            'margin_left'           => 10,
            'margin_right'          => 10,
            'default_font'          => 'dejavusans',
            'autoScriptToLang'      => true,
            'autoLangToFont'        => true,
            'autoArabic'            => true,
            'baseScript'            => 1,
            'biDirectional'         => true,
            'fontDir'               => array_merge($defaultConfig['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata'              => $defaultFonts['fontdata'],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;

        // ─── توليد HTML من الـ Blade ──────────────────────────────────
        $html = view('offers.pdf', ['offer' => $offer])->render();
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
