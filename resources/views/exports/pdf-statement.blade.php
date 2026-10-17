<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 34mm;
            margin-bottom: 22mm;
            margin-left: 10mm;
            margin-right: 10mm;
        }
        body {
            font-family: 'dejavusans', 'sans-serif';
            direction: rtl;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #04386c;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 17px;
            font-weight: bold;
            color: #04386c;
            text-align: center;
        }
        .meta-badge {
            display: inline-block;
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 12px;
        }
        .summary-box {
            padding: 8px;
            text-align: center;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }
        .summary-box-in {
            background-color: #f0fdf4;
            border-color: #86efac;
        }
        .summary-box-out {
            background-color: #fef2f2;
            border-color: #fca5a5;
        }
        .summary-box-net {
            background-color: #eff6ff;
            border-color: #93c5fd;
        }
        .summary-label {
            font-size: 9px;
            color: #475569;
            margin-bottom: 2px;
        }
        .summary-val {
            font-size: 13px;
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background-color: #04386c;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            padding: 7px 5px;
            text-align: center;
            border: 1px solid #04386c;
        }
        .data-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            text-align: center;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .incoming { color: #16a34a; font-weight: bold; }
        .outgoing { color: #dc2626; font-weight: bold; }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            font-size: 8.5px;
            color: #64748b;
        }
        .signatures-table {
            width: 100%;
            margin-top: 25px;
            padding-top: 10px;
        }
        .signature-box {
            text-align: center;
            font-size: 10px;
            padding: 10px;
        }
    </style>
</head>
<body>

    <htmlpageheader name="page-header">
        <table class="header-table">
            <tr>
                <td style="width: 32%; text-align: right;">
                    <div style="font-weight: bold; font-size: 12px; color: #04386c;">{{ $company_title ?? 'كافة المؤسسات والشركات' }}</div>
                    <div style="font-size: 9px; color: #475569; margin-top: 2px;">نظام إدارة الحسابات والعمليات</div>
                </td>
                <td style="width: 36%; text-align: center;">
                    <div class="header-title">{{ $title }}</div>
                    <div style="margin-top: 3px;">
                        <span class="meta-badge">{{ $type_label ?? 'كشف شامل (قبض وصرف)' }}</span>
                    </div>
                </td>
                <td style="width: 32%; text-align: left; font-size: 9px; color: #475569;">
                    <div>📅 {{ $dual_date ?? \App\Services\HijriDateService::formatDual() }}</div>
                    <div>👤 المنفذ: {{ $printed_by ?? (auth()->user()?->name ?? 'المسؤول') }}</div>
                    @if(!empty($date_range_label))
                        <div style="color: #04386c; font-weight: bold; margin-top: 2px;">{{ $date_range_label }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: right; width: 50%;">
                    كشف حساب رسمي معتمد — رقم التقرير: ST-{{ date('YmdHis') }}
                </td>
                <td style="text-align: left; width: 50%;">
                    صفحة {PAGENO} من {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    {{-- Summary KPIs Cards --}}
    <table class="summary-boxes">
        <tr>
            <td class="summary-box summary-box-in" style="width: 25%;">
                <div class="summary-label">إجمالي المقبوضات (الوارد)</div>
                <div class="summary-val" style="color: #166534;">{{ number_format($total_incoming ?? 0, 2) }} <span style="font-size: 9px;">ر.س</span></div>
            </td>
            <td class="summary-box summary-box-out" style="width: 25%;">
                <div class="summary-label">إجمالي المدفوعات (المنصرف)</div>
                <div class="summary-val" style="color: #991b1b;">{{ number_format($total_outgoing ?? 0, 2) }} <span style="font-size: 9px;">ر.س</span></div>
            </td>
            <td class="summary-box summary-box-net" style="width: 25%;">
                <div class="summary-label">صافي الحركات / الرصيد</div>
                <div class="summary-val" style="color: #1e40af;">{{ number_format(($total_incoming ?? 0) - ($total_outgoing ?? 0), 2) }} <span style="font-size: 9px;">ر.س</span></div>
            </td>
            <td class="summary-box" style="background: #f8fafc; width: 25%;">
                <div class="summary-label">إجمالي عدد الحركات</div>
                <div class="summary-val" style="color: #334155;">{{ count($records) }} <span style="font-size: 9px;">حركة</span></div>
            </td>
        </tr>
    </table>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 65px;">رقم السند</th>
                <th style="width: 100px;">التاريخ (ميلادي / هجري)</th>
                <th style="width: 80px;">المنشأة</th>
                <th style="width: 55px;">النوع</th>
                <th>البيان / المستفيد / التفاصيل</th>
                <th style="width: 65px;">وارد (+)</th>
                <th style="width: 65px;">منصرف (-)</th>
                <th style="width: 70px;">الرصيد بعد الحركة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $item)
                @php
                    $isIncoming = in_array(strtolower($item['type'] ?? ''), ['incoming', 'وارد', 'قبض']);
                    $amount = (float)($item['amount'] ?? 0);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold; font-family: monospace;">{{ $item['voucher_number'] ?? '—' }}</td>
                    <td style="font-size: 8.5px;">
                        <div>{{ $item['date'] ?? '—' }}</div>
                        <div style="color: #64748b; font-size: 8px;">{{ $item['hijri_date'] ?? '' }}</div>
                    </td>
                    <td>{{ $item['company_name'] ?? '—' }}</td>
                    <td>
                        @if($isIncoming)
                            <span style="color: #166534; font-weight: bold;">قبض</span>
                        @else
                            <span style="color: #991b1b; font-weight: bold;">صرف</span>
                        @endif
                    </td>
                    <td style="text-align: right; padding-right: 6px;">{{ $item['description'] ?? '—' }}</td>
                    <td class="incoming">{{ $isIncoming ? number_format($amount, 2) : '—' }}</td>
                    <td class="outgoing">{{ !$isIncoming ? number_format($amount, 2) : '—' }}</td>
                    <td style="font-weight: bold;">{{ isset($item['balance_after']) ? number_format((float)$item['balance_after'], 2) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding: 20px; color: #64748b;">
                        لا توجد حركات مسجلة تطابق محددات البحث المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Official Signatures --}}
    <table class="signatures-table">
        <tr>
            <td class="signature-box" style="width: 33%;">
                <strong>المحاسب المسؤول</strong><br><br><br>
                <span>....................................</span>
            </td>
            <td class="signature-box" style="width: 33%;">
                <strong>المراجع المالي</strong><br><br><br>
                <span>....................................</span>
            </td>
            <td class="signature-box" style="width: 33%;">
                <strong>المدير المالي / الاعتماد</strong><br><br><br>
                <span>....................................</span>
            </td>
        </tr>
    </table>

</body>
</html>
