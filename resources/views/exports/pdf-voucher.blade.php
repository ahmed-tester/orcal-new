<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $voucher_title }} - {{ $voucher_number }}</title>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 32mm;
            margin-bottom: 20mm;
            margin-left: 10mm;
            margin-right: 10mm;
        }
        body {
            font-family: 'dejavusans', 'sans-serif';
            direction: rtl;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #04386c;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #04386c;
            text-align: center;
        }
        .header-meta {
            font-size: 10px;
            color: #475569;
        }
        .voucher-number {
            font-family: 'dejavusansmono', 'monospace';
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            margin-top: 4px;
        }
        .amount-card {
            margin-top: 15px;
            padding: 12px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: center;
        }
        .amount-num {
            font-size: 18px;
            font-weight: bold;
            color: #04386c;
        }
        .amount-words {
            font-size: 11px;
            color: #334155;
            margin-top: 5px;
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th {
            background-color: #04386c;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #04386c;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }
        .data-table td.label-cell {
            width: 28%;
            font-weight: bold;
            color: #334155;
            background: #f8fafc;
            text-align: right;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <htmlpageheader name="page-header">
        <table class="header-table">
            <tr>
                <td style="width: 30%; text-align: right;" class="header-meta">
                    <strong>{{ $company_name ?? 'مجموعة فواز القابضة' }}</strong><br>
                    <span>{{ $department_label ?? 'نظام إدارة العمليات والموارد' }}</span>
                    @if(!empty($cr_number))
                        <br><span>س.ت: {{ $cr_number }}</span>
                    @endif
                </td>
                <td style="width: 40%; text-align: center;">
                    <div class="header-title">{{ $voucher_title }}</div>
                    <div class="voucher-number">رقم السند: {{ $voucher_number }}</div>
                </td>
                <td style="width: 30%; text-align: left;" class="header-meta">
                    <div>التاريخ الميلادي: {{ $gregorian_date }}</div>
                    <div>التاريخ الهجري: {{ $hijri_date }}</div>
                    <div>طُبع بواسطة: {{ $executed_by }}</div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: right; width: 50%;">
                    سند رسمي صادر من النظام الآلي — {{ $company_name ?? 'مجموعة فواز' }}
                </td>
                <td style="text-align: left; width: 50%;">
                    صفحة {PAGENO} من {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    {{-- Amount Box --}}
    <div class="amount-card">
        <span class="amount-num">{{ number_format($amount, 2) }}</span>
        <span style="font-size: 13px; color: #04386c;">ريال سعودي</span>
        @if(!empty($amount_words))
            <div class="amount-words">فقط وقدره: {{ $amount_words }} لا غير.</div>
        @endif
    </div>

    {{-- Details Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 28%;">البيان</th>
                <th style="width: 72%;">التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-cell">{{ $is_incoming ? 'استلمنا من السيد / الجهة:' : 'يُصرف إلى السيد / الجهة:' }}</td>
                <td style="text-align: right;"><strong>{{ $party_name }}</strong></td>
            </tr>
            @if(!empty($category_name))
                <tr>
                    <td class="label-cell">بند المعاملة / التصنيف:</td>
                    <td style="text-align: right;">{{ $category_name }}</td>
                </tr>
            @endif
            <tr>
                <td class="label-cell">وذلك عن (البيان):</td>
                <td style="text-align: right;">{{ $description }}</td>
            </tr>
            @if(!empty($balance_after))
                <tr>
                    <td class="label-cell">الرصيد بعد السند:</td>
                    <td style="text-align: right;"><strong style="color: #04386c;">{{ number_format((float)$balance_after, 2) }} ر.س</strong></td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
