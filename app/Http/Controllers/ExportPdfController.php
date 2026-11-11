<?php

namespace App\Http\Controllers;

use App\Enums\TreasuryTransactionType;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\TreasuryTransaction;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationRegulation;
use App\Models\ViolationTransaction;
use App\Models\ViolationType;
use App\Services\CompanyFormService;
use App\Services\ExportPdfService;
use App\Services\HijriDateService;
use App\Services\TafqeetService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
                        'filename' => 'violation-categories-'.date('Ymd'),
                        'totals' => ['إجمالي التصنيفات' => count($rows).' تصنيف'],
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
                    number_format((float) ($t->default_fine_amount ?? 0), 2).' ر.س',
                ])->toArray();

                return ExportPdfService::exportTableReport(
                    title: 'كشف أنواع ومسميات المخالفات',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violation-types-'.date('Ymd'),
                        'totals' => ['إجمالي أنواع المخالفات' => count($rows).' نوع'],
                    ]
                );

            case 'regulations':
                $regs = ViolationRegulation::orderBy('sort_order')->orderBy('title')->get();
                $headers = ['#', 'اسم اللائحة / النظام', 'النص / المحتوى والمجال', 'الحالة'];
                $rows = $regs->map(fn ($r, $i) => [
                    $i + 1,
                    "<strong>{$r->title}</strong>",
                    $r->content ? Str::limit(strip_tags($r->content), 120) : '—',
                    $r->is_active ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">معطل</span>',
                ])->toArray();

                return ExportPdfService::exportTableReport(
                    title: 'كشف لوائح وأنظمة المخالفات المعتمدة',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violation-regulations-'.date('Ymd'),
                        'totals' => ['إجمالي اللوائح' => count($rows).' لائحة'],
                    ]
                );

            case 'violations':
            default:
                $query = Violation::with(['company', 'category', 'type', 'recorder']);
                if (! empty($companyId)) {
                    $query->where('company_id', $companyId);
                }
                $violations = $query->orderBy('violation_date', 'desc')->get();

                $headers = ['#', 'رقم المخالفة', 'التاريخ (م / هـ)', 'المنشأة', 'نوع المخالفة', 'الجهة المصدرة', 'المبلغ', 'حالة السداد'];
                $rows = $violations->map(fn ($v, $i) => [
                    $i + 1,
                    "<strong>{$v->violation_number}</strong>",
                    ($v->violation_date ? $v->violation_date->format('Y/m/d') : '—').'<br><small style="color:#64748b;">'.HijriDateService::toHijriNumeric($v->violation_date).'</small>',
                    $v->company?->trade_name ?? '—',
                    $v->type?->name ?? ($v->category?->name ?? '—'),
                    $v->issuing_authority ?? '—',
                    number_format((float) $v->violation_amount, 2).' ر.س',
                    $v->payment_status?->getLabel() ?? '—',
                ])->toArray();

                $totalAmount = $violations->sum('violation_amount');

                return ExportPdfService::exportTableReport(
                    title: 'سجل وكشف المخالفات المسجلة',
                    headers: $headers,
                    rows: $rows,
                    options: [
                        'filename' => 'violations-log-'.date('Ymd'),
                        'totals' => [
                            'عدد المخالفات' => count($rows),
                            'إجمالي مبالغ الغرامات' => number_format((float) $totalAmount, 2).' ر.س',
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
        if (! empty($companyId) && $companyId !== 'all') {
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
        if (! empty($fromDate) && ! empty($toDate)) {
            $query->whereBetween('transaction_date', [$fromDate, $toDate]);
            $dateRangeLabel = "الفترة من: {$fromDate} إلى: {$toDate}";
        } elseif (! empty($fromDate)) {
            $query->where('transaction_date', '>=', $fromDate);
            $dateRangeLabel = "من تاريخ: {$fromDate}";
        } elseif (! empty($toDate)) {
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
                'type' => $t->transaction_type?->value ?? $t->transaction_type,
                'voucher_number' => $t->voucher_number ?? ('VT-'.$t->id),
                'date' => $t->transaction_date ? $t->transaction_date->format('Y/m/d') : '—',
                'hijri_date' => HijriDateService::toHijriNumeric($t->transaction_date),
                'company_name' => $t->company?->trade_name ?? '—',
                'description' => $desc,
                'amount' => (float) $t->amount,
                'balance_after' => $t->balance_after,
            ];
        })->toArray();

        return ExportPdfService::exportStatement(
            title: 'كشف حركات صندوق تحصيل المخالفات',
            records: $records,
            options: [
                'company_title' => $companyTitle,
                'type_label' => $typeLabel,
                'date_range_label' => $dateRangeLabel,
                'filename' => 'violation-box-statement-'.date('Ymd'),
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
        if (! empty($companyId) && $companyId !== 'all') {
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
        if (! empty($fromDate) && ! empty($toDate)) {
            $query->whereBetween('transaction_date', [$fromDate, $toDate]);
            $dateRangeLabel = "الفترة من: {$fromDate} إلى: {$toDate}";
        } elseif (! empty($fromDate)) {
            $query->where('transaction_date', '>=', $fromDate);
            $dateRangeLabel = "من تاريخ: {$fromDate}";
        } elseif (! empty($toDate)) {
            $query->where('transaction_date', '<=', $toDate);
            $dateRangeLabel = "حتى تاريخ: {$toDate}";
        }

        $records = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get()->map(function ($t) {
            $desc = $t->description ?: ($t->paymentCollectionItem?->sub_item ?? '—');
            $desc = str_replace(
                ['Salary Payment', 'Salary Collection', 'salary_payment', 'salary_collection'],
                ['تسليم رواتب', 'تحصيل رواتب', 'تسليم رواتب', 'تحصيل رواتب'],
                $desc
            );
            if ($t->recipient_or_payer && ! str_contains($desc, $t->recipient_or_payer)) {
                $desc .= " (الطرف: {$t->recipient_or_payer})";
            }

            return [
                'type' => $t->transaction_type instanceof TreasuryTransactionType ? $t->transaction_type->value : (string) $t->transaction_type,
                'voucher_number' => $t->voucher_number ?? $t->transaction_number,
                'date' => $t->transaction_date ? $t->transaction_date->format('Y/m/d') : '—',
                'hijri_date' => HijriDateService::toHijriNumeric($t->transaction_date),
                'company_name' => $t->company?->trade_name ?? '—',
                'description' => $desc,
                'amount' => (float) $t->amount,
                'balance_after' => $t->balance_after,
            ];
        })->toArray();

        return ExportPdfService::exportStatement(
            title: 'كشف حركة الخزينة والمقبوضات/المدفوعات',
            records: $records,
            options: [
                'company_title' => $companyTitle,
                'type_label' => $typeLabel,
                'date_range_label' => $dateRangeLabel,
                'filename' => 'treasury-statement-'.date('Ymd'),
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

        $query = Debt::with(['company', 'employee.user', 'guarantor']);

        $companyTitle = 'كافة المنشآت والشركات (الكل)';
        if (! empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "منشأة #{$companyId}";
        }

        if (! empty($debtorType) && $debtorType !== 'all') {
            $query->where('debtor_type', $debtorType);
        }

        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($fromDate) && ! empty($toDate)) {
            $query->whereBetween('debt_date', [$fromDate, $toDate]);
        } elseif (! empty($fromDate)) {
            $query->where('debt_date', '>=', $fromDate);
        } elseif (! empty($toDate)) {
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
                $debtorName .= '<br><small style="color:#64748b;">'.$d->external_phone.'</small>';
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
                number_format((float) $d->total_amount, 2).' ر.س',
                number_format((float) $d->paid_amount, 2).' ر.س',
                '<strong style="color:#dc2626;">'.number_format((float) $d->remaining_amount, 2).' ر.س</strong>',
                $d->debt_date ? $d->debt_date->format('Y/m/d') : '—',
                $d->due_date ? $d->due_date->format('Y/m/d') : '—',
                $statusBadge,
            ];
        })->toArray();

        $totalDebts = (float) $debts->sum('total_amount');
        $totalPaid = (float) $debts->sum('paid_amount');
        $totalRemaining = (float) $debts->sum('remaining_amount');

        return ExportPdfService::exportTableReport(
            title: 'كشف وسجل الديون العامة',
            headers: $headers,
            rows: $rows,
            options: [
                'orientation' => 'L',
                'company_name' => $companyTitle,
                'subtitle' => 'تقرير شامل بحركات وأرصدة الديون المسجلة',
                'totals' => [
                    'عدد الديون' => count($rows).' دين',
                    'إجمالي أصل الديون' => number_format($totalDebts, 2).' ر.س',
                    'إجمالي المبالغ المسددة' => number_format($totalPaid, 2).' ر.س',
                    'إجمالي الأرصدة المتبقية' => number_format($totalRemaining, 2).' ر.س',
                ],
                'filename' => 'debts-report-'.date('Ymd-His'),
            ]
        );
    }

    /**
     * Print official voucher for a debt.
     */
    public function printDebtVoucher(Debt $debt)
    {
        $debt->load(['company', 'employee.user', 'guarantor']);

        $amount = (float) $debt->total_amount;
        $amountWords = TafqeetService::inArabic($amount);
        $transDate = $debt->debt_date ?? now();
        $gregorianDate = $transDate->format('Y/m/d');
        $hijriDate = HijriDateService::toHijri($transDate);
        $companyName = $debt->company?->trade_name ?? 'مجموعة فواز';
        $crNumber = $debt->company?->commercial_registration_number ?? null;

        $debtorName = $debt->debtor_type === 'external'
            ? ($debt->external_name ?? '—')
            : ($debt->employee?->user?->name ?? 'موظف');

        $html = view('exports.pdf-voucher', [
            'voucher_title' => 'سند إثبات وتفاصيل دين عام',
            'voucher_number' => $debt->debt_number,
            'is_incoming' => false,
            'company_name' => $companyName,
            'cr_number' => $crNumber,
            'department_label' => 'إدارة الحسابات — سجل الديون العامة',
            'gregorian_date' => $gregorianDate,
            'hijri_date' => $hijriDate,
            'amount' => $amount,
            'amount_words' => $amountWords,
            'party_name' => $debtorName.($debt->external_phone ? " ({$debt->external_phone})" : ''),
            'category_name' => $debt->debt_reason ?? 'دين عام',
            'description' => 'أصل الدين: '.number_format($debt->total_amount, 2).' ر.س | المسدد: '.number_format($debt->paid_amount, 2).' ر.س | المتبقي: '.number_format($debt->remaining_amount, 2).' ر.س'.($debt->guarantor ? " | الضامن/الكفيل: {$debt->guarantor->name}" : '').($debt->description ? " | ملاحظات: {$debt->description}" : ''),
            'balance_after' => $debt->remaining_amount,
            'executed_by' => auth()->user()?->name ?? 'المسؤول',
        ])->render();

        $filename = 'debt-voucher-'.$debt->debt_number.'.pdf';

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

        $query = Invoice::with(['company', 'customer', 'employee.user', 'invoiceType']);

        $companyTitle = 'كافة المنشآت والشركات (الكل)';
        if (! empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
            $comp = Company::find($companyId);
            $companyTitle = $comp?->trade_name ?? "منشأة #{$companyId}";
        }

        if (! empty($status) && $status !== 'all') {
            $query->where('payment_status', $status);
        }

        if (! empty($fromDate) && ! empty($toDate)) {
            $query->whereBetween('issue_date', [$fromDate, $toDate]);
        } elseif (! empty($fromDate)) {
            $query->where('issue_date', '>=', $fromDate);
        } elseif (! empty($toDate)) {
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
            if (! $client && $inv->employee) {
                $client = 'موظف: '.($inv->employee->user?->name ?? '#'.$inv->employee->id);
            }

            $st = $inv->payment_status?->value ?? (string) $inv->payment_status;
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
                number_format((float) $inv->total_amount, 2).' ر.س',
                number_format((float) $inv->paid_amount, 2).' ر.س',
                '<strong style="color:#dc2626;">'.number_format((float) $inv->remaining_amount, 2).' ر.س</strong>',
                $statusBadge,
            ];
        })->toArray();

        $totalAmount = (float) $invoices->sum('total_amount');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $totalRemaining = (float) $invoices->sum('remaining_amount');

        return ExportPdfService::exportTableReport(
            title: 'كشف وسجل الفواتير وسندات الخدمات',
            headers: $headers,
            rows: $rows,
            options: [
                'orientation' => 'L',
                'company_name' => $companyTitle,
                'subtitle' => 'تقرير شامل بحركات ومستحقات الفواتير الصادرة',
                'totals' => [
                    'عدد الفواتير' => count($rows).' فاتورة',
                    'إجمالي قيمة الفواتير' => number_format($totalAmount, 2).' ر.س',
                    'إجمالي المبالغ المحصلة' => number_format($totalPaid, 2).' ر.س',
                    'إجمالي المبالغ المتبقية' => number_format($totalRemaining, 2).' ر.س',
                ],
                'filename' => 'invoices-report-'.date('Ymd-His'),
            ]
        );
    }

    /**
     * Export / Print Statement for Employees and Owners.
     * Options:
     * - statement_type: 'all' | 'employees_only' | 'owners_only'
     * - company_id: ID | 'all'
     * - status: 'all' | 'active'
     * - nationality: 'all' | 'saudi' | 'foreign'
     * - export_mode: 'print' | 'pdf'
     */
    public function exportEmployeesStatement(Request $request)
    {
        $statementType = $request->query('statement_type', 'all');
        $companyId = $request->query('company_id', 'all');
        $status = $request->query('status', 'all');
        $nationality = $request->query('nationality', 'all');
        $exportMode = $request->query('export_mode', 'print');

        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $authorizedCompanyIds = $user ? array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user)) : [];

        // Security check: non-superadmins must have authorized companies and cannot query unauthorized companies
        if (! $isSuperAdmin) {
            if (empty($authorizedCompanyIds)) {
                abort(403, 'لا تملك صلاحية الوصول إلى أي منشأة.');
            }

            if (! empty($companyId) && $companyId !== 'all') {
                if (! in_array((int) $companyId, $authorizedCompanyIds, true)) {
                    abort(403, 'غير مصرح لك بالوصول لبيانات هذه المنشأة.');
                }
            }
        }

        $companyTitle = 'كافة المنشآت المصرح بها';
        if (! empty($companyId) && $companyId !== 'all') {
            $company = Company::find($companyId);
            $companyTitle = $company?->trade_name ?? "منشأة #{$companyId}";
        }

        // Query employees if needed
        $employees = collect();
        if ($statementType === 'all' || $statementType === 'employees_only' || $statementType === 'dual_only') {
            $empQuery = Employee::with(['user.owners', 'company', 'branch', 'department', 'jobTitle', 'details']);

            if ($statementType === 'dual_only') {
                $empQuery->whereHas('user.owners');
            }

            if (! empty($companyId) && $companyId !== 'all') {
                $empQuery->where('company_id', (int) $companyId);
            } elseif (! $isSuperAdmin) {
                $empQuery->whereIn('company_id', $authorizedCompanyIds);
            }

            if (! empty($status) && $status !== 'all') {
                $empQuery->where('status', $status);
            }

            if ($nationality === 'saudi') {
                $empQuery->saudi();
            } elseif ($nationality === 'foreign') {
                $empQuery->foreign();
            }

            $employees = $empQuery->orderBy('id', 'desc')->get();
        }

        // Query owners if needed
        $owners = collect();
        if ($statementType === 'all' || $statementType === 'owners_only' || $statementType === 'dual_only') {
            $ownerQuery = Owner::with(['user.employee', 'company']);

            if ($statementType === 'dual_only') {
                $ownerQuery->whereHas('user.employee');
            }

            if (! empty($companyId) && $companyId !== 'all') {
                $ownerQuery->where('company_id', (int) $companyId);
            } elseif (! $isSuperAdmin) {
                $ownerQuery->whereIn('company_id', $authorizedCompanyIds);
            }

            if (! empty($status) && $status !== 'all') {
                $ownerQuery->where('status', $status);
            }

            $owners = $ownerQuery->orderBy('id', 'desc')->get();
        }

        $records = [];
        $stats = [
            'employees_only_count' => 0,
            'owners_only_count' => 0,
            'dual_count' => 0,
        ];

        if ($statementType === 'employees_only') {
            foreach ($employees as $emp) {
                $isOwner = $emp->user?->owners?->isNotEmpty() ?? false;
                if ($isOwner) {
                    $stats['dual_count']++;
                } else {
                    $stats['employees_only_count']++;
                }

                $records[] = [
                    'name' => $emp->user?->name ?? '—',
                    'capacity' => $isOwner ? __('employees.fields.capacity_employee_and_owner') : __('employees.fields.capacity_employee'),
                    'capacity_code' => $isOwner ? 'employee_and_owner' : 'employee',
                    'national_id' => $emp->details?->national_id ?? $emp->details?->iqama_number ?? '—',
                    'company_name' => $emp->company?->trade_name ?? '—',
                    'branch_name' => $emp->branch?->name ?? '—',
                    'department_name' => $emp->department?->name ?? '—',
                    'job_title' => $emp->metadata['job_title'] ?? $emp->jobTitle?->name ?? $emp->details?->profession ?? '—',
                    'phone' => $emp->user?->phone ?? '—',
                    'status' => $emp->status instanceof \BackedEnum ? $emp->status->value : (string) ($emp->status ?? 'active'),
                ];
            }
        } elseif ($statementType === 'owners_only') {
            foreach ($owners as $owner) {
                $isEmployee = (bool) $owner->user?->employee;
                if ($isEmployee) {
                    $stats['dual_count']++;
                } else {
                    $stats['owners_only_count']++;
                }

                $records[] = [
                    'name' => $owner->user?->name ?? '—',
                    'capacity' => $isEmployee ? __('owners.fields.capacity_employee_and_owner') : __('owners.fields.capacity_owner'),
                    'capacity_code' => $isEmployee ? 'employee_and_owner' : 'owner',
                    'national_id' => $owner->national_id ?? '—',
                    'company_name' => $owner->company?->trade_name ?? '—',
                    'branch_name' => '—',
                    'department_name' => 'الإدارة العليا / المُلّاك',
                    'job_title' => $owner->ownership_percentage ? "نسبة الملكية: {$owner->ownership_percentage}%" : 'مالك',
                    'phone' => $owner->user?->phone ?? '—',
                    'status' => $owner->status instanceof \BackedEnum ? $owner->status->value : (string) ($owner->status ?? 'active'),
                ];
            }
        } elseif ($statementType === 'dual_only') {
            // 'dual_only': Persons holding both Employee and Owner capacities
            $seenUserIds = [];

            foreach ($employees as $emp) {
                if ($emp->user?->owners?->isNotEmpty()) {
                    $stats['dual_count']++;
                    if ($emp->user_id) {
                        $seenUserIds[$emp->user_id] = true;
                    }

                    $records[] = [
                        'name' => $emp->user?->name ?? '—',
                        'capacity' => __('employees.fields.capacity_employee_and_owner'),
                        'capacity_code' => 'employee_and_owner',
                        'national_id' => $emp->details?->national_id ?? $emp->details?->iqama_number ?? '—',
                        'company_name' => $emp->company?->trade_name ?? '—',
                        'branch_name' => $emp->branch?->name ?? '—',
                        'department_name' => $emp->department?->name ?? '—',
                        'job_title' => $emp->metadata['job_title'] ?? $emp->jobTitle?->name ?? $emp->details?->profession ?? '—',
                        'phone' => $emp->user?->phone ?? '—',
                        'status' => $emp->status instanceof \BackedEnum ? $emp->status->value : (string) ($emp->status ?? 'active'),
                    ];
                }
            }

            foreach ($owners as $owner) {
                if ($owner->user_id && isset($seenUserIds[$owner->user_id])) {
                    continue;
                }

                if ((bool) $owner->user?->employee) {
                    $stats['dual_count']++;
                    if ($owner->user_id) {
                        $seenUserIds[$owner->user_id] = true;
                    }

                    $records[] = [
                        'name' => $owner->user?->name ?? '—',
                        'capacity' => __('owners.fields.capacity_employee_and_owner'),
                        'capacity_code' => 'employee_and_owner',
                        'national_id' => $owner->national_id ?? '—',
                        'company_name' => $owner->company?->trade_name ?? '—',
                        'branch_name' => '—',
                        'department_name' => 'الإدارة العليا / المُلّاك',
                        'job_title' => $owner->ownership_percentage ? "نسبة الملكية: {$owner->ownership_percentage}%" : 'مالك',
                        'phone' => $owner->user?->phone ?? '—',
                        'status' => $owner->status instanceof \BackedEnum ? $owner->status->value : (string) ($owner->status ?? 'active'),
                    ];
                }
            }
        } else {
            // 'all': Combined records of employees and owners
            $seenUserIds = [];

            foreach ($employees as $emp) {
                $isOwner = $emp->user?->owners?->isNotEmpty() ?? false;
                if ($isOwner) {
                    $stats['dual_count']++;
                } else {
                    $stats['employees_only_count']++;
                }

                if ($emp->user_id) {
                    $seenUserIds[$emp->user_id] = true;
                }

                $records[] = [
                    'name' => $emp->user?->name ?? '—',
                    'capacity' => $isOwner ? __('employees.fields.capacity_employee_and_owner') : __('employees.fields.capacity_employee'),
                    'capacity_code' => $isOwner ? 'employee_and_owner' : 'employee',
                    'national_id' => $emp->details?->national_id ?? $emp->details?->iqama_number ?? '—',
                    'company_name' => $emp->company?->trade_name ?? '—',
                    'branch_name' => $emp->branch?->name ?? '—',
                    'department_name' => $emp->department?->name ?? '—',
                    'job_title' => $emp->metadata['job_title'] ?? $emp->jobTitle?->name ?? $emp->details?->profession ?? '—',
                    'phone' => $emp->user?->phone ?? '—',
                    'status' => $emp->status instanceof \BackedEnum ? $emp->status->value : (string) ($emp->status ?? 'active'),
                ];
            }

            foreach ($owners as $owner) {
                if ($owner->user_id && isset($seenUserIds[$owner->user_id])) {
                    // Already included from employees list as dual (employee & owner)
                    continue;
                }

                $isEmployee = (bool) $owner->user?->employee;
                if ($isEmployee) {
                    $stats['dual_count']++;
                } else {
                    $stats['owners_only_count']++;
                }

                $records[] = [
                    'name' => $owner->user?->name ?? '—',
                    'capacity' => $isEmployee ? __('owners.fields.capacity_employee_and_owner') : __('owners.fields.capacity_owner'),
                    'capacity_code' => $isEmployee ? 'employee_and_owner' : 'owner',
                    'national_id' => $owner->national_id ?? '—',
                    'company_name' => $owner->company?->trade_name ?? '—',
                    'branch_name' => '—',
                    'department_name' => 'الإدارة العليا / المُلّاك',
                    'job_title' => $owner->ownership_percentage ? "نسبة الملكية: {$owner->ownership_percentage}%" : 'مالك',
                    'phone' => $owner->user?->phone ?? '—',
                    'status' => $owner->status instanceof \BackedEnum ? $owner->status->value : (string) ($owner->status ?? 'active'),
                ];
            }
        }

        $nationalitySuffix = match ($nationality) {
            'saudi' => ' (الموظفون السعوديون)',
            'foreign' => ' (الموظفون المقيمون)',
            default => '',
        };

        $title = match ($statementType) {
            'owners_only' => 'كشف وبيان مُلّاك المنشأة',
            'employees_only' => 'كشف وبيان موظفي المنشأة'.$nationalitySuffix,
            'dual_only' => 'كشف من يجمع بين صفتي موظف ومالك منشأة'.$nationalitySuffix,
            default => 'كشف وبيان الموظفين والمُلّاك'.$nationalitySuffix,
        };

        $typeLabel = match ($statementType) {
            'owners_only' => 'كشف المُلّاك',
            'employees_only' => 'كشف الموظفين'.$nationalitySuffix,
            'dual_only' => 'كشف من يجمع بين الصفتين (موظف ومالك منشأة)'.$nationalitySuffix,
            default => 'كشف شامل للموظفين والمُلّاك معاً (مع إظهار الصفة)'.$nationalitySuffix,
        };

        if ($exportMode === 'pdf') {
            $headers = ['#', 'الاسم الكامل', 'الصفة', 'الهوية / الإقامة', 'المنشأة / الشركة', 'الفرع / القسم', 'المسمى الوظيفي', 'رقم الجوال', 'الحالة'];
            $rows = [];
            foreach ($records as $i => $row) {
                $rows[] = [
                    $i + 1,
                    "<strong>{$row['name']}</strong>",
                    $row['capacity'],
                    $row['national_id'],
                    $row['company_name'],
                    ($row['branch_name'] !== '—' ? $row['branch_name'].' / ' : '').$row['department_name'],
                    $row['job_title'],
                    $row['phone'],
                    $row['status'] === 'active' ? 'نشط' : $row['status'],
                ];
            }

            return ExportPdfService::exportTableReport(
                title: $title,
                headers: $headers,
                rows: $rows,
                options: [
                    'orientation' => 'L',
                    'company_name' => $companyTitle,
                    'subtitle' => $typeLabel,
                    'totals' => [
                        'إجمالي السجلات' => count($rows).' سجل',
                        'الموظفون فقط' => $stats['employees_only_count'],
                        'المُلّاك فقط' => $stats['owners_only_count'],
                        'موظف ومالك منشأة' => $stats['dual_count'],
                    ],
                    'filename' => 'employees-statement-'.date('Ymd-His'),
                ]
            );
        }

        return response()->view('exports.employees-statement-print', [
            'title' => $title,
            'type_label' => $typeLabel,
            'statement_type' => $statementType,
            'company_title' => $companyTitle,
            'status' => $status,
            'records' => $records,
            'stats' => $stats,
            'date_label' => now()->format('Y/m/d'),
            'hijri_date' => HijriDateService::formatDual(now(), true),
            'printed_by' => auth()->user()?->name ?? 'المسؤول',
        ]);
    }
}
