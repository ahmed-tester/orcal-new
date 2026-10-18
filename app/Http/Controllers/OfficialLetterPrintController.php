<?php

namespace App\Http\Controllers;

use App\Models\OfficialLetter;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class OfficialLetterPrintController extends Controller
{
    /**
     * Preview letter as PDF inline in the browser matching offers/contracts layout.
     */
    public function print(OfficialLetter $letter, Request $request)
    {
        $letter->load('company');

        if ($request->query('format') === 'html') {
            return view('letters.print', [
                'letter'  => $letter,
                'company' => $letter->company,
            ]);
        }

        $mpdf = $this->buildPdf($letter);
        $filename = 'letter-' . ($letter->letter_number ?: $letter->id) . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download letter as PDF attachment.
     */
    public function pdf(OfficialLetter $letter, Request $request)
    {
        $letter->load('company');

        $mpdf = $this->buildPdf($letter);
        $filename = 'letter-' . ($letter->letter_number ?: $letter->id) . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Build mPDF instance for official letter.
     */
    private function buildPdf(OfficialLetter $letter): Mpdf
    {
        $defaultConfig  = (new ConfigVariables())->getDefaults();
        $defaultFonts   = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'                  => 'utf-8',
            'format'                => 'A4',
            'margin_top'            => 10,
            'margin_bottom'         => 14,
            'margin_left'           => 12,
            'margin_right'          => 12,
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

        $html = view('letters.print', [
            'letter'  => $letter,
            'company' => $letter->company,
        ])->render();
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
