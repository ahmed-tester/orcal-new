<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'dejavusans', sans-serif; direction: rtl; color: #000; font-size: 8.5pt; }

    .header { text-align: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #082c50; }
    .header h1 { font-size: 14pt; font-weight: bold; color: #082c50; margin-bottom: 2px; }
    .header p { font-size: 9pt; color: #4a5568; }

    .fund-info { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; }
    .fund-info table { width: 100%; border-collapse: collapse; }
    .fund-info td { font-size: 8.5pt; padding: 2px 6px; }
    .fund-info td.label { font-weight: bold; color: #082c50; width: 110px; }

    .section-title { font-size: 10pt; font-weight: bold; text-align: center; margin: 10px 0 6px 0; color: #b91c1c; }
    .section-title.deposit { color: #b91c1c; }
    .section-title.withdrawal { color: #b91c1c; margin-top: 14px; }

    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.data-table th { background: #f1f5f9; color: #0f172a; border: 1px solid #94a3b8; padding: 4px 3px; font-size: 7.5pt; font-weight: bold; text-align: center; line-height: 1.2; }
    table.data-table td { border: 1px solid #94a3b8; padding: 4px 3px; font-size: 8pt; text-align: center; color: #000; }
    table.data-table tr.total-row td { background: #f8fafc; font-weight: bold; border-top: 2px solid #082c50; }

    .summary-box { margin-top: 10px; width: 60%; margin-right: auto; margin-left: auto; }
    .summary-box table { width: 100%; border-collapse: collapse; }
    .summary-box td { padding: 4px 8px; font-size: 8.5pt; border: 1px solid #cbd5e1; text-align: center; }
    .summary-box td.label { font-weight: bold; background: #f1f5f9; color: #082c50; }
    .summary-box td.val-dep { font-weight: bold; color: #16a34a; }
    .summary-box td.val-wth { font-weight: bold; color: #dc2626; }
    .summary-box td.val-bal { font-weight: bold; font-size: 9.5pt; }

    .footer { margin-top: 14px; text-align: center; font-size: 7.5pt; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body>

<div class="header">
    <h1>كشف حركة صندوق الأصول</h1>
    <p>{{ $fund->fund_name }} ({{ $fund->fund_number }})</p>
</div>

<div class="fund-info">
    <table>
        <tr>
            <td class="label">اسم الصندوق:</td>
            <td>{{ $fund->fund_name }}</td>
            <td class="label">رقم الصندوق:</td>
            <td>{{ $fund->fund_number }}</td>
            <td class="label">تاريخ الإنشاء:</td>
            <td>{{ $fund->established_date?->format('Y/m/d') }}</td>
        </tr>
        <tr>
            <td class="label">مدير الصندوق:</td>
            <td>{{ $fund->manager_name ?? '—' }}</td>
            <td class="label">محاسب الصندوق:</td>
            <td>{{ $fund->accountant_name ?? '—' }}</td>
            <td class="label">فترة الكشف:</td>
            <td>
                {{ $filters['date_from'] ? 'من ' . $filters['date_from'] : 'منذ البداية' }}
                {{ $filters['date_to'] ? ' إلى ' . $filters['date_to'] : ' حتى الآن' }}
            </td>
        </tr>
    </table>
</div>

{{-- 1. جدول سند مدخلات ( إيداع ) --}}
@if ($filters['filter_type'] === 'all' || $filters['filter_type'] === 'deposit')
    <div class="section-title deposit">سند مدخلات ( إيداع )</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">م</th>
                <th style="width: 75px;">المقاول<br><span style="font-size:6.5pt;font-weight:normal">تسجيل يدوي حر</span></th>
                <th style="width: 85px;">المنشأة<br><span style="font-size:6.5pt;font-weight:normal">خيارين ربط بالمنشأة<br>او تسجيل يدوي حر</span></th>
                <th style="width: 65px;">المبلغ<br>الإجمالي<br>شامل<br>الضريبة</th>
                <th style="width: 55px;">مبلغ<br>الضريبة</th>
                <th style="width: 60px;">المبلغ<br>الصافي<br>بعد<br>الضريبة</th>
                <th style="width: 65px;">نسبة<br>الاستقطاع<br>%<br><span style="font-size:6pt;font-weight:normal">من الصافي</span></th>
                <th style="width: 60px;">نصيب<br>الصندوق<br>ان وجد<br>نسبة %</th>
                <th style="width: 70px;">المبلغ الصافي<br>بعد جميع<br>الخصومات</th>
                <th style="width: 65px;">التاريخ</th>
                <th style="width: 80px;">رقم الفاتورة<br><span style="font-size:6pt;font-weight:normal">في نظام المحاسبة<br>المربوط بالزكاة</span></th>
                <th>الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($depositVouchers as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->contractor_name ?: '—' }}</td>
                    <td>{{ $row->party_name ?: ($row->company?->trade_name ?? $row->company?->name ?? '—') }}</td>
                    <td>{{ $row->gross_amount !== null ? number_format($row->gross_amount, 2) : '—' }}</td>
                    <td>{{ $row->tax_amount !== null ? number_format($row->tax_amount, 2) : '0.00' }}</td>
                    <td>{{ $row->net_after_tax !== null ? number_format($row->net_after_tax, 2) : '—' }}</td>
                    <td>
                        @if ($row->retention_amount > 0)
                            {{ number_format($row->retention_amount, 2) }}
                            @if ($row->retention_percentage)
                                <br><small>({{ (float) $row->retention_percentage }}%)</small>
                            @endif
                        @else
                            0
                        @endif
                    </td>
                    <td>
                        @if ($row->fund_share_amount > 0)
                            {{ number_format($row->fund_share_amount, 2) }}
                            @if ($row->fund_share_percentage)
                                <br><small>({{ (float) $row->fund_share_percentage }}%)</small>
                            @endif
                        @else
                            0
                        @endif
                    </td>
                    <td style="font-weight: bold;">{{ number_format($row->total_amount, 2) }}</td>
                    <td>{{ $row->voucher_date?->format('Y/m/d') }}</td>
                    <td>{{ $row->tax_invoice_number ?: '—' }}</td>
                    <td>{{ $row->notes ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="padding: 10px; color: #64748b;">لا توجد سندات مدخلات (إيداع) في هذه الفترة.</td>
                </tr>
            @endforelse

            @if ($depositVouchers->count() > 0)
                <tr class="total-row">
                    <td colspan="3">المجموع</td>
                    <td>{{ number_format($depositsSummary['gross_amount'], 2) }}</td>
                    <td>{{ number_format($depositsSummary['tax_amount'], 2) }}</td>
                    <td>{{ number_format($depositsSummary['net_after_tax'], 2) }}</td>
                    <td>{{ number_format($depositsSummary['retention_amount'], 2) }}</td>
                    <td>{{ number_format($depositsSummary['fund_share_amount'], 2) }}</td>
                    <td>{{ number_format($depositsSummary['net_after_deductions'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            @endif
        </tbody>
    </table>
@endif

{{-- 2. جدول سند سحب ( صرف ) --}}
@if ($filters['filter_type'] === 'all' || $filters['filter_type'] === 'withdrawal')
    <div class="section-title withdrawal">سند سحب ( صرف )</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">م</th>
                <th style="width: 120px;">اسم الجهة مصروف لها<br><span style="font-size:6.5pt;font-weight:normal">تسجيل يدوي حر</span></th>
                <th style="width: 130px;">سبب الصرف<br><span style="font-size:6.5pt;font-weight:normal">تسجيل يدوي حر</span></th>
                <th style="width: 90px;">المبلغ المصروف<br>بدون ضرائب</th>
                <th style="width: 80px;">مبلغ الضريبة<br>ان وجد</th>
                <th style="width: 95px;">المبلغ الإجمالي<br>شامل الضريبة</th>
                <th style="width: 75px;">التاريخ</th>
                <th style="width: 85px;">رقم سند<br>الصرف آلي</th>
                <th>الملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($withdrawalVouchers as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->party_name ?: '—' }}</td>
                    <td>{{ $row->expense_reason ?: '—' }}</td>
                    <td>{{ $row->amount_without_tax !== null ? number_format($row->amount_without_tax, 2) : '—' }}</td>
                    <td>{{ $row->tax_amount !== null ? number_format($row->tax_amount, 2) : '0.00' }}</td>
                    <td style="font-weight: bold;">{{ number_format($row->total_amount, 2) }}</td>
                    <td>{{ $row->voucher_date?->format('Y/m/d') }}</td>
                    <td>{{ $row->voucher_number }}</td>
                    <td>{{ $row->notes ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding: 10px; color: #64748b;">لا توجد سندات سحب (صرف) في هذه الفترة.</td>
                </tr>
            @endforelse

            @if ($withdrawalVouchers->count() > 0)
                <tr class="total-row">
                    <td colspan="3">المجموع</td>
                    <td>{{ number_format($withdrawalsSummary['amount_without_tax'], 2) }}</td>
                    <td>{{ number_format($withdrawalsSummary['tax_amount'], 2) }}</td>
                    <td>{{ number_format($withdrawalsSummary['total_amount'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            @endif
        </tbody>
    </table>
@endif

{{-- الملخص المالي --}}
<div class="summary-box">
    <table>
        <tr>
            <td class="label">إجمالي الواردات الصافية (إيداع):</td>
            <td class="val-dep">{{ number_format($summary['total_deposits'], 2) }} ر.س</td>
            <td class="label">إجمالي المصروفات (سحب):</td>
            <td class="val-wth">{{ number_format($summary['total_withdrawals'], 2) }} ر.س</td>
            <td class="label">الرصيد الصافي المتاح:</td>
            <td class="val-bal" style="color: {{ $summary['net_balance'] >= 0 ? '#16a34a' : '#dc2626' }};">
                {{ number_format($summary['net_balance'], 2) }} ر.س
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    تم استخراج هذا الكشف آلياً بواسطة نظام Orcal الإداري والمالي — {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>
