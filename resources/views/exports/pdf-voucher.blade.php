<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $voucher_title }} - {{ $voucher_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        * {
            box-sizing: border-box;
            font-family: 'dejavusans', 'sans-serif';
            direction: rtl;
        }
        body {
            color: #0f172a;
            font-size: 11px;
            line-height: 1.5;
            padding: 0;
            margin: 0;
        }
        .voucher-border {
            border: 2.5px solid #04386c;
            border-radius: 12px;
            padding: 20px;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #04386c;
        }
        .company-sub {
            font-size: 9.5px;
            color: #475569;
        }
        .voucher-badge {
            display: inline-block;
            padding: 8px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            text-align: center;
        }
        .badge-incoming {
            background: #ecfdf5;
            color: #166534;
            border: 2px solid #16a34a;
        }
        .badge-outgoing {
            background: #fef2f2;
            color: #991b1b;
            border: 2px solid #dc2626;
        }
        .amount-box {
            background: #f8fafc;
            border: 2px solid #04386c;
            border-radius: 8px;
            padding: 10px;
            margin: 15px 0;
            text-align: center;
        }
        .amount-num {
            font-size: 20px;
            font-weight: bold;
            color: #04386c;
        }
        .amount-words {
            font-size: 11px;
            color: #334155;
            margin-top: 4px;
            font-weight: bold;
        }
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-bottom: 20px;
        }
        .info-label {
            width: 25%;
            font-weight: bold;
            color: #475569;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 0 6px 6px 0;
            border: 1px solid #e2e8f0;
            border-left: none;
        }
        .info-value {
            width: 75%;
            padding: 8px 12px;
            background: #ffffff;
            border-radius: 6px 0 0 6px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            color: #0f172a;
        }
        .signatures-table {
            width: 100%;
            border-top: 2px dashed #cbd5e1;
            padding-top: 25px;
            margin-top: 25px;
        }
        .sig-col {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }
        .sig-line {
            margin-top: 35px;
            border-bottom: 1px dotted #94a3b8;
            width: 75%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>

    <div class="voucher-border">
        {{-- Header --}}
        <table class="header-table">
            <tr>
                <td style="width: 35%; text-align: right;">
                    <div class="company-title">{{ $company_name ?? 'مجموعة فواز' }}</div>
                    <div class="company-sub">{{ $department_label ?? 'إدارة العمليات والمالية' }}</div>
                    @if(!empty($cr_number))
                        <div class="company-sub">س.ت: {{ $cr_number }}</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: center;">
                    <div class="voucher-badge {{ $is_incoming ? 'badge-incoming' : 'badge-outgoing' }}">
                        {{ $voucher_title }}
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px; font-weight: bold; font-family: monospace;">
                        رقم السند: {{ $voucher_number }}
                    </div>
                </td>
                <td style="width: 35%; text-align: left; font-size: 10px; color: #475569;">
                    <div>📅 الميلادي: <strong>{{ $gregorian_date }}</strong></div>
                    <div>🌙 الهجري: <strong>{{ $hijri_date }}</strong></div>
                    <div>👤 المحاسب/المنفذ: {{ $executed_by }}</div>
                </td>
            </tr>
        </table>

        {{-- Amount Box --}}
        <div class="amount-box">
            <div class="amount-num">{{ number_format($amount, 2) }} <span style="font-size: 13px;">ريال سعودي</span></div>
            @if(!empty($amount_words))
                <div class="amount-words">فقط وقدره: {{ $amount_words }} لا غير.</div>
            @endif
        </div>

        {{-- Details Table --}}
        <table class="info-table">
            <tr>
                <td class="info-label">{{ $is_incoming ? 'استلمنا من السيد / الجهة:' : 'يُصرف إلى السيد / الجهة:' }}</td>
                <td class="info-value"><strong>{{ $party_name }}</strong></td>
            </tr>
            @if(!empty($category_name))
                <tr>
                    <td class="info-label">بند المعاملة / التصنيف:</td>
                    <td class="info-value">{{ $category_name }}</td>
                </tr>
            @endif
            <tr>
                <td class="info-label">وذلك عن (البيان):</td>
                <td class="info-value">{{ $description }}</td>
            </tr>
            @if(!empty($balance_after))
                <tr>
                    <td class="info-label">الرصيد بعد السند:</td>
                    <td class="info-value"><strong>{{ number_format((float)$balance_after, 2) }} ر.س</strong></td>
                </tr>
            @endif
        </table>

        {{-- Signatures --}}
        <table class="signatures-table">
            <tr>
                <td class="sig-col">
                    <strong>المستلم / الدافع</strong>
                    <div class="sig-line"></div>
                </td>
                <td class="sig-col">
                    <strong>أمين الصندوق / المحاسب</strong>
                    <div class="sig-line"></div>
                </td>
                <td class="sig-col">
                    <strong>الاعتماد والمدير المالي</strong>
                    <div class="sig-line"></div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
