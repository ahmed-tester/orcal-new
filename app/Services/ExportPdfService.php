<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TreasuryTransaction;
use App\Models\ViolationTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class ExportPdfService
{
    /**
     * Build an mPDF instance with Arabic UTF-8 RTL settings.
     */
    public static function createMpdf(array $options = []): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts  = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => $options['format'] ?? 'A4',
            'orientation'      => $options['orientation'] ?? 'P',
            'margin_top'       => $options['margin_top'] ?? 10,
            'margin_bottom'    => $options['margin_bottom'] ?? 10,
            'margin_left'      => $options['margin_left'] ?? 10,
            'margin_right'     => $options['margin_right'] ?? 10,
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

        return $mpdf;
    }

    /**
     * Render raw HTML into a PDF response (inline or attachment).
     */
    public static function renderHtml(string $html, string $filename = 'document.pdf', bool $download = false, array $options = []): Response
    {
        $mpdf = self::createMpdf($options);
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $mpdf->WriteHTML($html);

        $disposition = $download ? 'attachment' : 'inline';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export a generic table report (used for any section/resource like Violations, Types, Regulations).
     */
    public static function exportTableReport(string $title, array $headers, array $rows, array $options = [], bool $download = false): Response
    {
        $html = view('exports.pdf-table-report', [
            'title'        => $title,
            'subtitle'     => $options['subtitle'] ?? null,
            'company_name' => $options['company_name'] ?? (auth()->user()?->company?->trade_name ?? 'مجموعة فواز'),
            'headers'      => $headers,
            'rows'         => $rows,
            'totals'       => $options['totals'] ?? null,
            'dual_date'    => HijriDateService::formatDual(now(), true),
            'printed_by'   => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = ($options['filename'] ?? 'report-' . date('Ymd-His')) . '.pdf';

        return self::renderHtml($html, $filename, $download, $options);
    }

    /**
     * Export a Statement (كشف حساب / حركة) for Treasury or Violations Box.
     * Supports filtering by: single company or all companies, transaction type (incoming/outgoing/all), and date range.
     */
    public static function exportStatement(string $title, array $records, array $options = [], bool $download = false): Response
    {
        $totalIncoming = 0.0;
        $totalOutgoing = 0.0;

        foreach ($records as $item) {
            $rawType = is_object($item['type'] ?? null) && isset($item['type']->value) ? $item['type']->value : (string)($item['type'] ?? '');
            $isIncoming = in_array(strtolower($rawType), ['incoming', 'وارد', 'قبض']);
            $amt = (float)($item['amount'] ?? 0);
            if ($isIncoming) {
                $totalIncoming += $amt;
            } else {
                $totalOutgoing += $amt;
            }
        }

        $html = view('exports.pdf-statement', [
            'title'             => $title,
            'company_title'     => $options['company_title'] ?? 'كافة المؤسسات والشركات',
            'type_label'        => $options['type_label'] ?? 'كشف شامل (قبض وصرف)',
            'date_range_label'  => $options['date_range_label'] ?? null,
            'records'           => $records,
            'total_incoming'    => $totalIncoming,
            'total_outgoing'    => $totalOutgoing,
            'dual_date'         => HijriDateService::formatDual(now(), true),
            'printed_by'        => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = ($options['filename'] ?? 'statement-' . date('Ymd-His')) . '.pdf';

        return self::renderHtml($html, $filename, $download, $options);
    }

    /**
     * Export a single official Voucher (سند قبض / سند صرف) as PDF for Treasury or Violation Box.
     */
    public static function exportVoucher(Model $transaction, bool $download = false): Response
    {
        $isIncoming = false;
        $voucherTitle = 'سند مالي';
        $categoryName = null;
        $partyName = '—';
        $companyName = $transaction->company?->trade_name ?? 'مجموعة فواز';
        $crNumber = $transaction->company?->commercial_registration_number ?? null;
        $departmentLabel = 'قسم العمليات والمالية';

        if ($transaction instanceof ViolationTransaction) {
            $isIncoming = ($transaction->transaction_type === \App\Enums\ViolationTransactionType::INCOMING || $transaction->transaction_type?->value === 'incoming');
            $voucherTitle = $isIncoming ? 'سند تحصيل مخالفة (قبض)' : 'سند صرف من صندوق المخالفات';
            $departmentLabel = 'قسم المخالفات — صندوق التحصيل';
            
            $violation = $transaction->violation;
            if ($violation) {
                $partyName = $violation->violator_name ?? ($violation->employee?->user?->name ?? 'مخالفة #' . $violation->violation_number);
                $categoryName = $violation->type?->name ?? $violation->category?->name;
            }
        } elseif ($transaction instanceof TreasuryTransaction) {
            $isIncoming = ($transaction->transaction_type === 'incoming');
            $voucherTitle = $isIncoming ? 'سند قبض رسمي' : 'سند صرف رسمي';
            $departmentLabel = 'إدارة الحسابات — الخزينة الرئيسية';
            $partyName = $transaction->recipient_or_payer ?? '—';
            $categoryName = $transaction->paymentCollectionItem?->full_name ?? $transaction->paymentCollectionItem?->sub_item;
        }

        $amount = (float)$transaction->amount;
        $amountWords = TafqeetService::inArabic($amount);
        $transDate = $transaction->transaction_date ?? now();
        $gregorianDate = $transDate->format('Y/m/d');
        $hijriDate = HijriDateService::toHijri($transDate);

        $html = view('exports.pdf-voucher', [
            'voucher_title'    => $voucherTitle,
            'voucher_number'   => $transaction->voucher_number ?? ('TR-' . $transaction->id),
            'is_incoming'      => $isIncoming,
            'company_name'     => $companyName,
            'cr_number'        => $crNumber,
            'department_label' => $departmentLabel,
            'gregorian_date'   => $gregorianDate,
            'hijri_date'       => $hijriDate,
            'amount'           => $amount,
            'amount_words'     => $amountWords,
            'party_name'       => $partyName,
            'category_name'    => $categoryName,
            'description'      => $transaction->description ?? '—',
            'balance_after'    => $transaction->balance_after,
            'executed_by'      => $transaction->executor?->user?->name ?? (auth()->user()?->name ?? 'أمين الصندوق'),
        ])->render();

        $filename = 'voucher-' . ($transaction->voucher_number ?? $transaction->id) . '.pdf';

        return self::renderHtml($html, $filename, $download, ['margin_top' => 12, 'margin_bottom' => 12]);
    }
}
