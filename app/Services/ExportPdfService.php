<?php

namespace App\Services;

use App\Enums\ViolationTransactionType;
use App\Models\Company;
use App\Models\DebtCollection;
use App\Models\InstallmentPayment;
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
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFonts = (new FontVariables)->getDefaults();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $options['format'] ?? 'A4',
            'orientation' => $options['orientation'] ?? 'P',
            'margin_top' => $options['margin_top'] ?? 10,
            'margin_bottom' => $options['margin_bottom'] ?? 10,
            'margin_left' => $options['margin_left'] ?? 10,
            'margin_right' => $options['margin_right'] ?? 10,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoArabic' => true,
            'baseScript' => 1,
            'biDirectional' => true,
            'fontDir' => array_merge($defaultConfig['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata' => $defaultFonts['fontdata'],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

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
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export a generic table report (used for any section/resource like Violations, Types, Regulations).
     */
    public static function exportTableReport(string $title, array $headers, array $rows, array $options = [], bool $download = false): Response
    {
        $html = view('exports.pdf-table-report', [
            'title' => $title,
            'subtitle' => $options['subtitle'] ?? null,
            'company_name' => $options['company_name'] ?? (auth()->user()?->company?->trade_name ?? 'مجموعة فواز'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => $options['totals'] ?? null,
            'dual_date' => HijriDateService::formatDual(now(), true),
            'printed_by' => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = ($options['filename'] ?? 'report-'.date('Ymd-His')).'.pdf';

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
            $rawType = is_object($item['type'] ?? null) && isset($item['type']->value) ? $item['type']->value : (string) ($item['type'] ?? '');
            $isIncoming = in_array(strtolower($rawType), ['incoming', 'وارد', 'قبض']);
            $amt = (float) ($item['amount'] ?? 0);
            if ($isIncoming) {
                $totalIncoming += $amt;
            } else {
                $totalOutgoing += $amt;
            }
        }

        $html = view('exports.pdf-statement', [
            'title' => $title,
            'company_title' => $options['company_title'] ?? 'كافة المؤسسات والشركات',
            'type_label' => $options['type_label'] ?? 'كشف شامل (قبض وصرف)',
            'date_range_label' => $options['date_range_label'] ?? null,
            'records' => $records,
            'total_incoming' => $totalIncoming,
            'total_outgoing' => $totalOutgoing,
            'dual_date' => HijriDateService::formatDual(now(), true),
            'printed_by' => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = ($options['filename'] ?? 'statement-'.date('Ymd-His')).'.pdf';

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
        $companyName = $transaction->company?->trade_name
            ?? ($transaction->debt?->company?->trade_name ?? 'مجموعة فواز');
        $crNumber = $transaction->company?->commercial_registration_number
            ?? ($transaction->debt?->company?->commercial_registration_number ?? null);
        $departmentLabel = 'قسم العمليات والمالية';

        if ($transaction instanceof ViolationTransaction) {
            $isIncoming = ($transaction->transaction_type === ViolationTransactionType::INCOMING || $transaction->transaction_type?->value === 'incoming');
            $voucherTitle = $isIncoming ? 'سند تحصيل مخالفة (قبض)' : 'سند صرف من صندوق المخالفات';
            $departmentLabel = 'قسم المخالفات — صندوق التحصيل';

            $violation = $transaction->violation;
            if ($violation) {
                $partyName = $violation->violator_name ?? ($violation->employee?->user?->name ?? 'مخالفة #'.$violation->violation_number);
                $categoryName = $violation->type?->name ?? $violation->category?->name;
            }
        } elseif ($transaction instanceof TreasuryTransaction) {
            $isIncoming = ($transaction->transaction_type === 'incoming');
            $voucherTitle = $isIncoming ? 'سند قبض رسمي' : 'سند صرف رسمي';
            $departmentLabel = 'إدارة الحسابات — الخزينة الرئيسية';
            $partyName = $transaction->recipient_or_payer ?? '—';
            $categoryName = $transaction->paymentCollectionItem?->full_name ?? $transaction->paymentCollectionItem?->sub_item;
        } elseif ($transaction instanceof InstallmentPayment) {
            $isIncoming = true;
            $isAdvance = ($transaction->payment_type === 'advance');
            $voucherTitle = $isAdvance ? 'سند تسديد سلفة (قبض)' : 'سند تسديد قسط (قبض)';
            $departmentLabel = 'إدارة التمويل والأقساط — سندات التحصيل';
            $partyName = $transaction->installment?->beneficiary_name
                ?? ($transaction->employee?->user?->name ?? '—');
            $categoryName = ($isAdvance ? 'سداد سلفة مالية' : 'سداد قسط تمويل')
                .($transaction->installment?->installment_number ? ' — رقم: '.$transaction->installment->installment_number : '')
                .($transaction->installment?->installmentType?->name ? ' ('.$transaction->installment->installmentType->name.')' : '');
        } elseif ($transaction instanceof DebtCollection) {
            $isIncoming = true;
            $voucherTitle = 'سند تحصيل دين (قبض)';
            $departmentLabel = 'إدارة التحصيل والديون — سندات القبض';
            $partyName = ($transaction->debt?->debtor_type === 'external'
                ? $transaction->debt->external_name
                : $transaction->debt->employee?->user?->name)
                ?? ($transaction->employee?->user?->name ?? '—');
            $categoryName = 'تحصيل دين'
                .($transaction->debt?->debt_number ? ' — رقم: '.$transaction->debt->debt_number : '')
                .($transaction->debt?->debt_reason ? ' ('.$transaction->debt->debt_reason.')' : '');
        }

        $amount = (float) $transaction->amount;
        $amountWords = TafqeetService::inArabic($amount);
        $transDate = $transaction->transaction_date ?? $transaction->payment_date ?? $transaction->collection_date ?? now();
        $gregorianDate = $transDate->format('Y/m/d');
        $hijriDate = HijriDateService::toHijri($transDate);

        $balanceAfter = $transaction->balance_after ?? null;
        if ($transaction instanceof InstallmentPayment && $balanceAfter === null) {
            if ($transaction->installment) {
                $total = (float) $transaction->installment->total_amount;
                $paid = (float) $transaction->installment->paid_amount;
                $balanceAfter = max(0, $total - $paid);
            }
        } elseif ($transaction instanceof DebtCollection && $balanceAfter === null) {
            if ($transaction->debt) {
                $balanceAfter = max(0, (float) $transaction->debt->remaining_amount);
            }
        }

        $description = $transaction->description;
        if (empty($description) && $transaction instanceof InstallmentPayment) {
            $sourceText = $transaction->payment_source instanceof \BackedEnum
                ? $transaction->payment_source->getLabel()
                : ($transaction->payment_source ?? '');
            $description = ($transaction->payment_type === 'advance' ? 'سداد دفعة من سلفة' : 'سداد قسط مجدول')
                .($sourceText ? " [طريقة السداد: {$sourceText}]" : '');
        } elseif (empty($description) && $transaction instanceof DebtCollection) {
            $paymentMethodLabel = match ($transaction->payment_method) {
                'bank_transfer' => 'تحويل بنكي',
                'cheque' => 'شيك',
                default => 'نقداً',
            };
            $description = 'تحصيل دفعة من دين'." [طريقة الدفع: {$paymentMethodLabel}]";
        }

        $executedBy = $transaction->executor?->user?->name
            ?? ($transaction->executor?->name ?? (auth()->user()?->name ?? 'أمين الصندوق'));

        $html = view('exports.pdf-voucher', [
            'voucher_title' => $voucherTitle,
            'voucher_number' => $transaction->voucher_number ?? ('TR-'.$transaction->id),
            'is_incoming' => $isIncoming,
            'company_name' => $companyName,
            'cr_number' => $crNumber,
            'department_label' => $departmentLabel,
            'gregorian_date' => $gregorianDate,
            'hijri_date' => $hijriDate,
            'amount' => $amount,
            'amount_words' => $amountWords,
            'party_name' => $partyName,
            'category_name' => $categoryName,
            'description' => $description ?? '—',
            'balance_after' => $balanceAfter,
            'executed_by' => $executedBy,
        ])->render();

        $filename = 'voucher-'.($transaction->voucher_number ?? $transaction->id).'.pdf';

        return self::renderHtml($html, $filename, $download);
    }
}
