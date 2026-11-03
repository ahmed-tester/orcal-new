<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'dejavusans', sans-serif; direction: rtl; color: #1a202c; font-size: 11pt; }

    .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #082c50; }
    .header h1 { font-size: 16pt; font-weight: bold; color: #082c50; margin-bottom: 4px; }
    .header p { font-size: 10pt; color: #4a5568; }

    .fund-info { background: #edf2f7; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
    .fund-info table { width: 100%; }
    .fund-info td { font-size: 10pt; padding: 3px 8px; }
    .fund-info td.label { font-weight: bold; color: #082c50; width: 140px; }

    .filter-bar { background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 6px; padding: 8px 14px; margin-bottom: 14px; font-size: 9.5pt; }

    table.stmt { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.stmt th { background: #082c50; color: #fff; padding: 7px 10px; font-size: 9.5pt; text-align: center; }
    table.stmt td { padding: 6px 10px; font-size: 9.5pt; border-bottom: 1px solid #e2e8f0; text-align: center; }
    table.stmt tr:nth-child(even) td { background: #f7fafc; }
    .type-d { color: #276749; font-weight: bold; }
    .type-w { color: #9b2c2c; font-weight: bold; }
    .amount-d { color: #276749; font-weight: bold; }
    .amount-w { color: #9b2c2c; font-weight: bold; }

    .summary { margin-top: 10px; }
    .summary table { width: 100%; border-collapse: collapse; }
    .summary td { padding: 7px 14px; font-size: 10.5pt; border: 1px solid #e2e8f0; }
    .summary td.label { font-weight: bold; color: #2d3748; background: #f7fafc; width: 50%; }
    .summary td.val-d { color: #276749; font-weight: bold; font-size: 12pt; }
    .summary td.val-w { color: #9b2c2c; font-weight: bold; font-size: 12pt; }
    .summary td.val-b { font-weight: bold; font-size: 12pt; }

    .footer { margin-top: 20px; text-align: center; font-size: 8.5pt; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>كشف حركة صندوق الأصول</h1>
    <p>{{ $fund->fund_name }} — {{ $fund->fund_number }}</p>
</div>

{{-- بيانات الصندوق --}}
<div class="fund-info">
    <table>
        <tr>
            <td class="label">اسم الصندوق:</td>
            <td>{{ $fund->fund_name }}</td>
            <td class="label">رقم الصندوق:</td>
            <td>{{ $fund->fund_number }}</td>
        </tr>
        <tr>
            <td class="label">تاريخ الإنشاء:</td>
            <td>{{ $fund->established_date?->format('Y/m/d') }}</td>
            <td class="label">مدير الصندوق:</td>
            <td>{{ $fund->manager_name ?? '—' }}</td>
        </tr>
        @if ($fund->accountant_name)
        <tr>
            <td class="label">محاسب الصندوق:</td>
            <td colspan="3">{{ $fund->accountant_name }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- فلاتر الكشف --}}
<div class="filter-bar">
    <strong>فترة الكشف:</strong>
    {{ $filters['date_from'] ? 'من ' . $filters['date_from'] : 'منذ البداية' }}
    {{ $filters['date_to']   ? ' إلى ' . $filters['date_to'] : ' حتى الآن' }}
    &nbsp;|&nbsp;
    <strong>نوع العمليات:</strong>
    @if ($filters['filter_type'] === 'all') الكل (واردات + مصروفات)
    @elseif ($filters['filter_type'] === 'deposit') الواردات فقط
    @else المصروفات فقط
    @endif
    &nbsp;|&nbsp;
    <strong>تاريخ استخراج الكشف:</strong> {{ now()->format('Y/m/d H:i') }}
</div>

{{-- جدول الحركات --}}
@if ($vouchers->count() > 0)
<table class="stmt">
    <thead>
        <tr>
            <th>#</th>
            <th>رقم السند</th>
            <th>التاريخ</th>
            <th>النوع</th>
            <th>الطرف / الجهة</th>
            <th>الملاحظات</th>
            <th>المبلغ (ر.س)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vouchers as $i => $voucher)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $voucher->voucher_number }}</td>
            <td>{{ $voucher->voucher_date?->format('Y/m/d') }}</td>
            <td class="{{ $voucher->type?->value === 'deposit' ? 'type-d' : 'type-w' }}">
                {{ $voucher->type?->getLabel() }}
            </td>
            <td>{{ $voucher->party_name ?? $voucher->company?->name ?? '—' }}</td>
            <td>{{ Str::limit($voucher->notes, 50) ?? '—' }}</td>
            <td class="{{ $voucher->type?->value === 'deposit' ? 'amount-d' : 'amount-w' }}">
                {{ $voucher->type?->value === 'deposit' ? '+' : '-' }}{{ number_format($voucher->total_amount, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- الملخص --}}
<div class="summary">
    <table>
        <tr>
            <td class="label">إجمالي الواردات (إيداع)</td>
            <td class="val-d">{{ number_format($summary['total_deposits'], 2) }} ر.س</td>
        </tr>
        <tr>
            <td class="label">إجمالي المصروفات (سحب)</td>
            <td class="val-w">{{ number_format($summary['total_withdrawals'], 2) }} ر.س</td>
        </tr>
        <tr>
            <td class="label">الرصيد الصافي</td>
            <td class="val-b" style="color: {{ $summary['net_balance'] >= 0 ? '#276749' : '#9b2c2c' }}">
                {{ number_format($summary['net_balance'], 2) }} ر.س
            </td>
        </tr>
    </table>
</div>

@else
<p style="text-align:center;color:#718096;padding:20px;">لا توجد حركات مطابقة للفترة المحددة.</p>
@endif

<div class="footer">
    تم استخراج هذا الكشف بواسطة نظام Orcal — {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>

