<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\TreasuryTransaction;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationRegulation;
use App\Models\ViolationTransaction;
use App\Models\ViolationType;
use App\Services\ExportPdfService;
use App\Services\HijriDateService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExportPdfController extends Controller
{
    /**
     * Export sections in Violations Department (Categories, Types, Violations, Regulations).
     */
    public function exportViolations(Request $request, string $section)
    {
        $companyId = $request->query('company_id');

        switch ($section) {
            case 'categories':
                $categories = ViolationCategory::withCount('types')->orderBy('name')->get();
                $headers = ['#', 'اسم القسم والتصنيف', 'كود التصنيف', 'الوصف', 'عدد الأنواع المسجلة'];
                $rows = $categories->map(fn ($c, $i) => [
                    $i + 1,
                    "<strong>{$c->name}</strong>",
                    $c->category_code ?? '—',
                    $c->description ?? '—',
                    "<span class=\"badge badge-info\">{$c->types_count} نوع</span>",
                ])->toArray();

                return ExportPdfService::exportTableReport(
                    title: 'كشف أقسام وتصنيفات المخالفات',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violation-categories-' . date('Ymd'),
                        'totals' => ['إجمالي التصنيفات' => count($rows) . ' تصنيف'],
                    ]
                );

            case 'types':
                $types = ViolationType::with('category')->orderBy('name')->get();
                $headers = ['#', 'مسمى ونوع المخالفة', 'القسم / التصنيف', 'كود النوع', 'قيمة الغرامة الافتراضية'];
                $rows = $types->map(fn ($t, $i) => [
                    $i + 1,
                    "<strong>{$t->name}</strong>",
                    $t->category?->name ?? '—',
                    $t->type_code ?? '—',
                    number_format((float)($t->default_fine_amount ?? 0), 2) . ' ر.س',
                ])->toArray();

                return ExportPdfService::exportTableReport(
                    title: 'كشف أنواع ومسميات المخالفات',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violation-types-' . date('Ymd'),
                        'totals' => ['إجمالي أنواع المخالفات' => count($rows) . ' نوع'],
                    ]
                );

            case 'regulations':
                $regs = ViolationRegulation::orderBy('sort_order')->orderBy('title')->get();
                $headers = ['#', 'اسم اللائحة / النظام', 'النص / المحتوى والمجال', 'الحالة'];
                $rows = $regs->map(fn ($r, $i) => [
                    $i + 1,
                    "<strong>{$r->title}</strong>",
                    $r->content ? \Illuminate\Support\Str::limit(strip_tags($r->content), 120) : '—',
                    $r->is_active ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">معطل</span>',
                ])->toArray();

                return ExportPdfService::exportTableReport(
                    title: 'كشف لوائح وأنظمة المخالفات المعتمدة',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violation-regulations-' . date('Ymd'),
                        'totals' => ['إجمالي اللوائح' => count($rows) . ' لائحة'],
                    ]
                );

            case 'violations':
            default:
                $query = Violation::with(['company', 'category', 'type', 'recorder']);
                if (!empty($companyId)) {
                    $query->where('company_id', $companyId);
                }
                $violations = $query->orderBy('violation_date', 'desc')->get();

                $headers = ['#', 'رقم المخالفة', 'التاريخ (م / هـ)', 'المنشأة', 'نوع المخالفة', 'الجهة المصدرة', 'المبلغ', 'حالة السداد'];
                $rows = $violations->map(fn ($v, $i) => [
                    $i + 1,
                    "<strong>{$v->violation_number}</strong>",
                    ($v->violation_date ? $v->violation_date->format('Y/m/d') : '—') . '<br><small style="color:#64748b;">' . HijriDateService::toHijriNumeric($v->violation_date) . '</small>',
                    $v->company?->trade_name ?? '—',
                    $v->type?->name ?? ($v->category?->name ?? '—'),
                    $v->issuing_authority ?? '—',
                    number_format((float)$v->violation_amount, 2) . ' ر.س',
                    $v->payment_status?->getLabel() ?? '—',
                ])->toArray();

                $totalAmount = $violations->sum('violation_amount');

                return ExportPdfService::exportTableReport(
                    title: 'سجل وكشف المخالفات المسجلة',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violations-log-' . date('Ymd'),
                        'totals' => [
                            'عدد المخالفات' => count($rows),
                            'إجمالي مبالغ الغرامات' => number_format((float)$totalAmount, 2) . ' ر.س',
                        ],
                    ]
                );
        }
    }

    /**
     * Export Statement for Violations Collection Box.
     */
    public function exportViolationStatement(Request $request)
    {
        $companyId = $request->query('company_id');
        $transType = $request->query('transaction_type', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = ViolationTransaction::with(['company', 'violation.type', 'violation.category', 'executor']);

        $companyTitle = 'كافة المؤسسات والشركات (الكل)';
        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "مؤسسة #{$companyId}";
        }

        $typeLabel = 'كشف شامل (قبض وصرف)';
        if ($transType === 'incoming') {
            $query->where('transaction_type', 'incoming');
            $typeLabel = 'كشف مقبوضات وتحصيل المخالفات (قبض فقط)';
        } elseif ($transType === 'outgoing') {
            $query->where('transaction_type', 'outgoing');
            $typeLabel = 'كشف منصرفات صندوق المخالفات (صرف فقط)';
        }

        $dateRangeLabel = null;
        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('transaction_date', [$fromDate, $toDate]);
            $dateRangeLabel = "الفترة من: {$fromDate} إلى: {$toDate}";
        } elseif (!empty($fromDate)) {
            $query->where('transaction_date', '>=', $fromDate);
            $dateRangeLabel = "من تاريخ: {$fromDate}";
        } elseif (!empty($toDate)) {
            $query->where('transaction_date', '<=', $toDate);
            $dateRangeLabel = "حتى تاريخ: {$toDate}";
        }

        $records = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get()->map(function ($t) {
            $violation = $t->violation;
            $desc = $t->description ?: ($violation ? "سداد غرامة مخالفة {$violation->violation_number}" : '—');
            if ($violation && $violation->violator_name) {
                $desc .= " (المخالف: {$violation->violator_name})";
            }

            return [
                'type'           => $t->transaction_type?->value ?? $t->transaction_type,
                'voucher_number' => $t->voucher_number ?? ('VT-' . $t->id),
                'date'           => $t->transaction_date ? $t->transaction_date->format('Y/m/d') : '—',
                'hijri_date'     => HijriDateService::toHijriNumeric($t->transaction_date),
                'company_name'   => $t->company?->trade_name ?? '—',
                'description'    => $desc,
                'amount'         => (float)$t->amount,
                'balance_after'  => $t->balance_after,
            ];
        })->toArray();

        return ExportPdfService::exportStatement(
            title: 'كشف حركات صندوق تحصيل المخالفات',
            records: $records,
            options: [
                'company_title'    => $companyTitle,
                'type_label'       => $typeLabel,
                'date_range_label' => $dateRangeLabel,
                'filename'         => 'violation-box-statement-' . date('Ymd'),
            ]
        );
    }

    /**
     * Print official voucher for a violation transaction.
     */
    public function printViolationVoucher(ViolationTransaction $transaction)
    {
        $transaction->load(['company', 'violation.type', 'violation.category', 'executor']);
        return ExportPdfService::exportVoucher($transaction);
    }

    /**
     * Export Statement for Treasury Transactions (المحاسبة والخزينة).
     */
    public function exportTreasuryStatement(Request $request)
    {
        $companyId = $request->query('company_id');
        $transType = $request->query('transaction_type', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = TreasuryTransaction::with(['company', 'paymentCollectionItem', 'executor.user']);

        $companyTitle = 'كافة المؤسسات والشركات (الكل)';
        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "مؤسسة #{$companyId}";
        }

        $typeLabel = 'كشف شامل بحركات الخزينة (قبض وصرف)';
        if ($transType === 'incoming') {
            $query->where('transaction_type', 'incoming');
            $typeLabel = 'كشف مقبوضات الخزينة (سندات قبض فقط)';
        } elseif ($transType === 'outgoing') {
            $query->where('transaction_type', 'outgoing');
            $typeLabel = 'كشف مدفوعات ومنصرفات الخزينة (سندات صرف فقط)';
        }

        $dateRangeLabel = null;
        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('transaction_date', [$fromDate, $toDate]);
            $dateRangeLabel = "الفترة من: {$fromDate} إلى: {$toDate}";
        } elseif (!empty($fromDate)) {
            $query->where('transaction_date', '>=', $fromDate);
            $dateRangeLabel = "من تاريخ: {$fromDate}";
        } elseif (!empty($toDate)) {
            $query->where('transaction_date', '<=', $toDate);
            $dateRangeLabel = "حتى تاريخ: {$toDate}";
        }

        $records = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get()->map(function ($t) {
            $desc = $t->description ?: ($t->paymentCollectionItem?->sub_item ?? '—');
            if ($t->recipient_or_payer) {
                $desc .= " (الطرف: {$t->recipient_or_payer})";
            }

            return [
                'type'           => $t->transaction_type,
                'voucher_number' => $t->voucher_number ?? $t->transaction_number,
                'date'           => $t->transaction_date ? $t->transaction_date->format('Y/m/d') : '—',
                'hijri_date'     => HijriDateService::toHijriNumeric($t->transaction_date),
                'company_name'   => $t->company?->trade_name ?? '—',
                'description'    => $desc,
                'amount'         => (float)$t->amount,
                'balance_after'  => $t->balance_after,
            ];
        })->toArray();

        return ExportPdfService::exportStatement(
            title: 'كشف حركة الخزينة والمقبوضات/المدفوعات',
            records: $records,
            options: [
                'company_title'    => $companyTitle,
                'type_label'       => $typeLabel,
                'date_range_label' => $dateRangeLabel,
                'filename'         => 'treasury-statement-' . date('Ymd'),
            ]
        );
    }

    /**
     * Print official voucher for a treasury transaction.
     */
    public function printTreasuryVoucher(TreasuryTransaction $transaction)
    {
        $transaction->load(['company', 'paymentCollectionItem', 'executor.user']);
        return ExportPdfService::exportVoucher($transaction);
    }

    /**
     * Export Statement for General Debts (سجل الديون العامة).
     */
    public function exportDebtsStatement(Request $request)
    {
        $companyId = $request->query('company_id');
        $debtorType = $request->query('debtor_type', 'all');
        $status = $request->query('status', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = \App\Models\Debt::with(['company', 'employee.user', 'guarantor']);

        $companyTitle = 'كافة المنشآت والشركات (الكل)';
        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "منشأة #{$companyId}";
        }

        if (!empty($debtorType) && $debtorType !== 'all') {
            $query->where('debtor_type', $debtorType);
        }

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('debt_date', [$fromDate, $toDate]);
        } elseif (!empty($fromDate)) {
            $query->where('debt_date', '>=', $fromDate);
        } elseif (!empty($toDate)) {
            $query->where('debt_date', '<=', $toDate);
        }

        $debts = $query->orderBy('debt_date', 'desc')->orderBy('id', 'desc')->get();

        $headers = [
            '#',
            'رقم الدين',
            'المدين (صاحب الدين)',
            'النوع',
            'المنشأة',
            'الضامن / الكفيل',
            'سبب الدين',
            'أصل المبلغ',
            'المسدد',
            'المتبقي',
            'تاريخ الدين',
            'تاريخ الاستحقاق',
            'الحالة',
        ];

        $rows = $debts->map(function ($d, $i) {
            $debtorName = $d->debtor_type === 'external'
                ? ($d->external_name ?? '—')
                : ($d->employee?->user?->name ?? 'موظف داخلي');

            if ($d->debtor_type === 'external' && $d->external_phone) {
                $debtorName .= '<br><small style="color:#64748b;">' . $d->external_phone . '</small>';
            }

            $debtorTypeLabel = $d->debtor_type === 'external'
                ? '<span style="color:#d97706; font-weight:bold;">خارجي</span>'
                : '<span style="color:#2563eb; font-weight:bold;">داخلي</span>';

            $statusBadge = match ($d->status) {
                'paid' => '<span style="color:#16a34a; font-weight:bold;">مسدد</span>',
                'partial' => '<span style="color:#0284c7; font-weight:bold;">جزئي</span>',
                'overdue' => '<span style="color:#dc2626; font-weight:bold;">متعثر</span>',
                default => '<span style="color:#ca8a04; font-weight:bold;">قيد الانتظار</span>',
            };

            return [
                $i + 1,
                "<strong>{$d->debt_number}</strong>",
                $debtorName,
                $debtorTypeLabel,
                $d->company?->trade_name ?? '—',
                $d->guarantor?->name ?? '—',
                $d->debt_reason ?? '—',
                number_format((float)$d->total_amount, 2) . ' ر.س',
                number_format((float)$d->paid_amount, 2) . ' ر.س',
                '<strong style="color:#dc2626;">' . number_format((float)$d->remaining_amount, 2) . ' ر.س</strong>',
                $d->debt_date ? $d->debt_date->format('Y/m/d') : '—',
                $d->due_date ? $d->due_date->format('Y/m/d') : '—',
                $statusBadge,
            ];
        })->toArray();

        $totalDebts = (float)$debts->sum('total_amount');
        $totalPaid = (float)$debts->sum('paid_amount');
        $totalRemaining = (float)$debts->sum('remaining_amount');

        return ExportPdfService::exportTableReport(
            title: 'كشف وسجل الديون العامة',
            headers: $headers,
            rows: $rows,
            options: [
                'orientation'  => 'L',
                'company_name' => $companyTitle,
                'subtitle'     => 'تقرير شامل بحركات وأرصدة الديون المسجلة',
                'totals'       => [
                    'عدد الديون'            => count($rows) . ' دين',
                    'إجمالي أصل الديون'    => number_format($totalDebts, 2) . ' ر.س',
                    'إجمالي المبالغ المسددة' => number_format($totalPaid, 2) . ' ر.س',
                    'إجمالي الأرصدة المتبقية' => number_format($totalRemaining, 2) . ' ر.س',
                ],
                'filename'     => 'debts-report-' . date('Ymd-His'),
            ]
        );
    }

    /**
     * Print official voucher for a debt.
     */
    public function printDebtVoucher(\App\Models\Debt $debt)
    {
        $debt->load(['company', 'employee.user', 'guarantor']);
        
        $amount = (float)$debt->total_amount;
        $amountWords = \App\Services\TafqeetService::inArabic($amount);
        $transDate = $debt->debt_date ?? now();
        $gregorianDate = $transDate->format('Y/m/d');
        $hijriDate = HijriDateService::toHijri($transDate);
        $companyName = $debt->company?->trade_name ?? 'مجموعة فواز';
        $crNumber = $debt->company?->commercial_registration_number ?? null;

        $debtorName = $debt->debtor_type === 'external'
            ? ($debt->external_name ?? '—')
            : ($debt->employee?->user?->name ?? 'موظف');

        $html = view('exports.pdf-voucher', [
            'voucher_title'    => 'سند إثبات وتفاصيل دين عام',
            'voucher_number'   => $debt->debt_number,
            'is_incoming'      => false,
            'company_name'     => $companyName,
            'cr_number'        => $crNumber,
            'department_label' => 'إدارة الحسابات — سجل الديون العامة',
            'gregorian_date'   => $gregorianDate,
            'hijri_date'       => $hijriDate,
            'amount'           => $amount,
            'amount_words'     => $amountWords,
            'party_name'       => $debtorName . ($debt->external_phone ? " ({$debt->external_phone})" : ''),
            'category_name'    => $debt->debt_reason ?? 'دين عام',
            'description'      => "أصل الدين: " . number_format($debt->total_amount, 2) . " ر.س | المسدد: " . number_format($debt->paid_amount, 2) . " ر.س | المتبقي: " . number_format($debt->remaining_amount, 2) . " ر.س" . ($debt->guarantor ? " | الضامن/الكفيل: {$debt->guarantor->name}" : '') . ($debt->description ? " | ملاحظات: {$debt->description}" : ''),
            'balance_after'    => $debt->remaining_amount,
            'executed_by'      => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = 'debt-voucher-' . $debt->debt_number . '.pdf';

        return ExportPdfService::renderHtml($html, $filename, false, ['margin_top' => 12, 'margin_bottom' => 12]);
    }

    /**
     * Export Statement for Invoices (كشف الفواتير وسندات الخدمات).
     */
    public function exportInvoicesStatement(Request $request)
    {
        $companyId = $request->query('company_id');
        $status = $request->query('payment_status');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = \App\Models\Invoice::with(['company', 'customer', 'employee.user', 'invoiceType']);

        $companyTitle = 'كافة المنشآت والشركات (الكل)';
        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "منشأة #{$companyId}";
        }

        if (!empty($status) && $status !== 'all') {
            $query->where('payment_status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('issue_date', [$fromDate, $toDate]);
        } elseif (!empty($fromDate)) {
            $query->where('issue_date', '>=', $fromDate);
        } elseif (!empty($toDate)) {
            $query->where('issue_date', '<=', $toDate);
        }

        $invoices = $query->orderBy('issue_date', 'desc')->orderBy('id', 'desc')->get();

        $headers = [
            '#',
            'رقم الفاتورة',
            'المنشأة المصدرة',
            'العميل / الطرف الثاني',
            'نوع الفاتورة',
            'تاريخ الإصدار',
            'إجمالي الفاتورة',
            'المبلغ المدفوع',
            'المبلغ المتبقي',
            'حالة السداد',
        ];

        $rows = $invoices->map(function ($inv, $i) {
            $client = $inv->customer?->name;
            if (!$client && $inv->employee) {
                $client = 'موظف: ' . ($inv->employee->user?->name ?? '#' . $inv->employee->id);
            }

            $st = $inv->payment_status?->value ?? (string)$inv->payment_status;
            $statusBadge = match ($st) {
                'paid' => '<span style="color:#16a34a; font-weight:bold;">مسددة بالكامل</span>',
                'partially_paid', 'partial' => '<span style="color:#0284c7; font-weight:bold;">مسددة جزئياً</span>',
                default => '<span style="color:#dc2626; font-weight:bold;">غير مسددة</span>',
            };

            return [
                $i + 1,
                "<strong>{$inv->invoice_number}</strong>",
                $inv->company?->trade_name ?? '—',
                $client ?? '—',
                $inv->invoiceType?->name ?? 'خدمات عامة',
                $inv->issue_date ? $inv->issue_date->format('Y/m/d') : '—',
                number_format((float)$inv->total_amount, 2) . ' ر.س',
                number_format((float)$inv->paid_amount, 2) . ' ر.س',
                '<strong style="color:#dc2626;">' . number_format((float)$inv->remaining_amount, 2) . ' ر.س</strong>',
                $statusBadge,
            ];
        })->toArray();

        $totalAmount = (float)$invoices->sum('total_amount');
        $totalPaid = (float)$invoices->sum('paid_amount');
        $totalRemaining = (float)$invoices->sum('remaining_amount');

        return ExportPdfService::exportTableReport(
            title: 'كشف وسجل الفواتير وسندات الخدمات',
            headers: $headers,
            rows: $rows,
            options: [
                'orientation'  => 'L',
                'company_name' => $companyTitle,
                'subtitle'     => 'تقرير شامل بحركات ومستحقات الفواتير الصادرة',
                'totals'       => [
                    'عدد الفواتير'            => count($rows) . ' فاتورة',
                    'إجمالي قيمة الفواتير'    => number_format($totalAmount, 2) . ' ر.س',
                    'إجمالي المبالغ المحصلة' => number_format($totalPaid, 2) . ' ر.س',
                    'إجمالي المبالغ المتبقية' => number_format($totalRemaining, 2) . ' ر.س',
                ],
                'filename'     => 'invoices-report-' . date('Ymd-His'),
            ]
        );
    }
}
