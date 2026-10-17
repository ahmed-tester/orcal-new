<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transaction->transaction_type === 'outgoing' ? 'سند صرف' : 'سند قبض' }} - {{ $transaction->voucher_number ?? $transaction->transaction_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            padding: 20px;
            direction: rtl;
        }
        .voucher-card {
            background: #ffffff;
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #1e3a8a;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .company-info h2 {
            color: #1e3a8a;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #64748b;
            font-size: 14px;
        }
        .voucher-title-box {
            text-align: center;
            border: 2px solid {{ $transaction->transaction_type === 'outgoing' ? '#dc2626' : '#16a34a' }};
            background-color: {{ $transaction->transaction_type === 'outgoing' ? '#fef2f2' : '#f0fdf4' }};
            padding: 10px 25px;
            border-radius: 8px;
        }
        .voucher-title-box h1 {
            color: {{ $transaction->transaction_type === 'outgoing' ? '#dc2626' : '#16a34a' }};
            font-size: 20px;
            margin-bottom: 5px;
        }
        .voucher-meta {
            text-align: left;
            font-size: 14px;
            color: #334155;
        }
        .voucher-meta div {
            margin-bottom: 4px;
        }
        .voucher-meta strong {
            color: #0f172a;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }
        .details-table td.label {
            width: 25%;
            font-weight: bold;
            color: #475569;
            background-color: #f8fafc;
            border-left: 2px solid #e2e8f0;
        }
        .amount-box {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            color: #0f172a;
            font-size: 16px;
        }
        .tafqeet-text {
            color: #1e3a8a;
            font-weight: 600;
            font-style: italic;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #cbd5e1;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-box p {
            font-weight: bold;
            color: #334155;
            margin-bottom: 40px;
        }
        .signature-line {
            border-bottom: 1px solid #94a3b8;
            width: 80%;
            margin: 0 auto;
        }
        .footer-note {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .print-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .voucher-card {
                box-shadow: none;
                border: 2px solid #000;
                padding: 20px;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">🖨️ طباعة السند</button>

    <div class="voucher-card">
        <div class="header">
            <div class="company-info">
                <h2>{{ $transaction->company?->trade_name ?? 'مجموعة فواز القابضة' }}</h2>
                <p>نظام الموارد البشرية والمالية وإدارة المنشآت</p>
            </div>
            
            <div class="voucher-title-box">
                <h1>{{ $transaction->transaction_type === 'outgoing' ? 'سند صرف رسمي' : 'سند قبض رسمي' }}</h1>
                <span>{{ $transaction->transaction_type === 'outgoing' ? 'Payment Voucher' : 'Receipt Voucher' }}</span>
            </div>

            <div class="voucher-meta">
                <div><strong>رقم السند:</strong> {{ $transaction->voucher_number ?? $transaction->transaction_number }}</div>
                <div><strong>التاريخ:</strong> {{ $transaction->transaction_date?->format('Y-m-d') }}</div>
                <div><strong>رقم الحركة:</strong> {{ $transaction->transaction_number }}</div>
            </div>
        </div>

        <table class="details-table">
            <tr>
                <td class="label">{{ $transaction->transaction_type === 'outgoing' ? 'صرفنا إلى المكرم:' : 'استلمنا من المكرم:' }}</td>
                <td><strong>{{ $transaction->recipient_or_payer ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="label">المبلغ المالي:</td>
                <td>
                    <span class="amount-box">{{ number_format($transaction->amount, 2) }} ر.س</span>
                </td>
            </tr>
            <tr>
                <td class="label">المبلغ كتابةً:</td>
                <td class="tafqeet-text">
                    {{ \App\Services\TafqeetService::inArabic((float)$transaction->amount) }}
                </td>
            </tr>
            <tr>
                <td class="label">البند المحاسبي:</td>
                <td>
                    <strong>{{ $transaction->paymentCollectionItem?->main_item }}</strong>
                    @if($transaction->paymentCollectionItem?->sub_item)
                        — {{ $transaction->paymentCollectionItem?->sub_item }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">وذلك عن (البيان):</td>
                <td>{{ $transaction->description ?? 'لا يوجد بيان' }}</td>
            </tr>
            <tr>
                <td class="label">المؤسسة التابعة:</td>
                <td>{{ $transaction->company?->trade_name }}</td>
            </tr>
            <tr>
                <td class="label">منفذ السند:</td>
                <td>{{ $transaction->executor?->name ?? 'مدير النظام' }}</td>
            </tr>
        </table>

        <div class="signatures">
            <div class="signature-box">
                <p>{{ $transaction->transaction_type === 'outgoing' ? 'توقيع المستلم' : 'توقيع الدافع' }}</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>المحاسب المسؤول</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>المدير المالي / المعتمد</p>
                <div class="signature-line"></div>
            </div>
        </div>

        <div class="footer-note">
            وثيقة رسمية صادرة من نظام مجموعة فواز القابضة — يُعتمد بختم المؤسسة وتوقيع المفوضين
        </div>
    </div>

</body>
</html>
