<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 20px;
            direction: rtl;
        }
        .action-bar {
            max-width: 860px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #082c50;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #0b3d6f;
        }
        .btn-pdf {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background-color: #0369a1;
        }
        .btn-close {
            background-color: #e2e8f0;
            color: #475569;
        }
        .btn-close:hover {
            background-color: #cbd5e1;
        }
        .invoice-card {
            background: #ffffff;
            max-width: 860px;
            margin: 0 auto;
            border: 2px solid #082c50;
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
            margin-bottom: 20px;
        }
        .company-info h2 {
            color: #082c50;
            font-size: 22px;
            margin-bottom: 4px;
        }
        .company-info p {
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }
        .invoice-title-box {
            text-align: center;
            border: 2px solid #082c50;
            background-color: #f8fafc;
            padding: 10px 24px;
            border-radius: 8px;
        }
        .invoice-title-box h1 {
            color: #082c50;
            font-size: 20px;
            margin-bottom: 4px;
        }
        .invoice-meta {
            text-align: left;
            font-size: 12px;
            color: #334155;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .badge-partial {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #7dd3fc;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .client-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .client-info h3 {
            font-size: 14px;
            color: #082c50;
            margin-bottom: 6px;
        }
        .client-info p {
            font-size: 12px;
            color: #475569;
            margin-bottom: 3px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #082c50;
            color: #ffffff;
            font-size: 12px;
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #082c50;
        }
        .items-table td {
            padding: 9px 8px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            text-align: center;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            gap: 20px;
        }
        .tafqeet-box {
            flex: 1;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            font-size: 13px;
            color: #1e293b;
        }
        .tafqeet-box strong {
            color: #082c50;
        }
        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-table .label {
            color: #64748b;
            text-align: right;
        }
        .totals-table .value {
            text-align: left;
            font-weight: bold;
            color: #1e293b;
        }
        .totals-table .grand-total {
            background-color: #082c50;
            color: #ffffff !important;
            font-size: 14px;
            border-radius: 4px;
        }
        .totals-table .grand-total td {
            color: #ffffff !important;
            border-bottom: none;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .sig-block {
            text-align: center;
            width: 30%;
        }
        .sig-block p {
            font-size: 13px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 40px;
        }
        .sig-line {
            border-bottom: 1px dashed #94a3b8;
            width: 80%;
            margin: 0 auto;
        }
        .footer-note {
            margin-top: 25px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .invoice-card {
                border: 1px solid #082c50;
                box-shadow: none;
                max-width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- شريط الإجراءات والطباعة العلوي -->
    <div class="action-bar">
        <div style="font-weight: bold; color: #082c50;">
            فاتورة رقم: {{ $invoice->invoice_number }}
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="action-btn btn-print">
                🖨️ طباعة الفاتورة
            </button>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="action-btn btn-pdf">
                📥 حفظ الفاتورة PDF
            </a>
            <button onclick="window.close()" class="action-btn btn-close">
                ✖ إغلاق
            </button>
        </div>
    </div>

    <!-- بطاقة الفاتورة الرسمية -->
    <div class="invoice-card">
        <!-- الهيدر الرسمي -->
        <div class="header">
            <div class="company-info">
                <h2>{{ $invoice->company?->trade_name ?? config('app.name', 'مجموعة فواز القابضة') }}</h2>
                @if($invoice->company?->commercial_registration_number)
                    <p>س.ت: {{ $invoice->company->commercial_registration_number }}</p>
                @endif
                @if($invoice->company?->zakat_number)
                    <p>الرقم الضريبي: {{ $invoice->company->zakat_number }}</p>
                @endif
                @if($invoice->company?->phone)
                    <p>هاتف: {{ $invoice->company->phone }}</p>
                @endif
            </div>

            <div class="invoice-title-box">
                <h1>{{ $invoice->invoiceType?->name ?? 'فاتورة وسند خدمة' }}</h1>
                <div style="margin-top: 6px;">
                    @php
                        $status = $invoice->payment_status?->value ?? (string)$invoice->payment_status;
                    @endphp
                    @if($status === 'paid')
                        <span class="badge badge-paid">مسددة بالكامل</span>
                    @elseif($status === 'partially_paid' || $status === 'partial')
                        <span class="badge badge-partial">مسددة جزئياً</span>
                    @else
                        <span class="badge badge-unpaid">غير مسددة</span>
                    @endif
                </div>
            </div>

            <div class="invoice-meta">
                <div><strong>رقم الفاتورة:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>تاريخ الإصدار:</strong> {{ $invoice->issue_date ? $invoice->issue_date->format('Y/m/d') : now()->format('Y/m/d') }}</div>
                <div><strong>التاريخ الهجري:</strong> {{ \App\Services\HijriDateService::toHijri($invoice->issue_date ?? now()) }}</div>
                @if($invoice->branch)
                    <div><strong>الفرع:</strong> {{ $invoice->branch->name }}</div>
                @endif
            </div>
        </div>

        <!-- بيانات العميل / المستفيد -->
        <div class="client-section">
            <div class="client-info">
                <h3>بيانات العميل / طالب الخدمة:</h3>
                @php
                    $customerName = $invoice->customer?->name;
                    if (!$customerName && $invoice->employee) {
                        $customerName = 'الموظف: ' . ($invoice->employee->user?->name ?? '#' . $invoice->employee->id);
                    }
                @endphp
                <p><strong>الاسم:</strong> {{ $customerName ?? 'عميل عام' }}</p>
                @if($invoice->customer?->phone || $invoice->employee?->user?->phone)
                    <p><strong>الهاتف:</strong> {{ $invoice->customer?->phone ?? $invoice->employee?->user?->phone }}</p>
                @endif
                @if($invoice->customer?->tax_number)
                    <p><strong>الرقم الضريبي للعميل:</strong> {{ $invoice->customer->tax_number }}</p>
                @endif
            </div>
            @if($invoice->is_paid_by_applicant)
                <div>
                    <span class="badge badge-paid" style="font-size: 12px; padding: 6px 12px;">تم السداد بواسطة طالب الخدمة</span>
                </div>
            @endif
        </div>

        <!-- جدول بنود الفاتورة والخدمات -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>البيان / تفاصيل الخدمة</th>
                    <th style="width: 70px;">الكمية</th>
                    <th style="width: 100px;">سعر الوحدة</th>
                    <th style="width: 80px;">الضريبة</th>
                    <th style="width: 110px;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ $item->item_name ?? ($item->service?->name ?? 'خدمة عامة') }}
                            @if($item->notes)
                                <div style="font-size: 10px; color: #64748b; font-weight: normal;">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float)$item->quantity, 1) }}</td>
                        <td>{{ number_format((float)$item->unit_price, 2) }} ر.س</td>
                        <td>{{ number_format((float)$item->tax_amount, 2) }} ر.س</td>
                        <td style="font-weight: bold;">{{ number_format((float)$item->total_amount, 2) }} ر.س</td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ $invoice->invoiceType?->name ?? 'خدمات عامة واستشارات' }}
                            @if($invoice->notes)
                                <div style="font-size: 10px; color: #64748b; font-weight: normal;">{{ $invoice->notes }}</div>
                            @endif
                        </td>
                        <td>1.0</td>
                        <td>{{ number_format((float)$invoice->total_amount, 2) }} ر.س</td>
                        <td>0.00 ر.س</td>
                        <td style="font-weight: bold;">{{ number_format((float)$invoice->total_amount, 2) }} ر.س</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- الإجماليات والتفقيط -->
        <div class="totals-container">
            <div class="tafqeet-box">
                <p><strong>المبلغ كتابةً:</strong></p>
                <p style="margin-top: 6px; font-weight: bold; color: #082c50;">
                    {{ \App\Services\TafqeetService::inArabic((float)$invoice->total_amount) }}
                </p>
                @if($invoice->notes)
                    <p style="margin-top: 10px; font-size: 11px; color: #64748b;">
                        <strong>ملاحظات:</strong> {{ $invoice->notes }}
                    </p>
                @endif
            </div>

            <table class="totals-table">
                <tr>
                    <td class="label">إجمالي الفاتورة:</td>
                    <td class="value">{{ number_format((float)$invoice->total_amount, 2) }} ر.س</td>
                </tr>
                <tr>
                    <td class="label">المبلغ المدفوع:</td>
                    <td class="value" style="color: #16a34a;">{{ number_format((float)$invoice->paid_amount, 2) }} ر.س</td>
                </tr>
                <tr>
                    <td class="label">المبلغ المتبقي:</td>
                    <td class="value" style="color: {{ (float)$invoice->remaining_amount > 0 ? '#dc2626' : '#64748b' }};">
                        {{ number_format((float)$invoice->remaining_amount, 2) }} ر.س
                    </td>
                </tr>
                <tr class="grand-total">
                    <td class="label">الصافي المطلوب:</td>
                    <td class="value">{{ number_format((float)$invoice->total_amount, 2) }} ر.س</td>
                </tr>
            </table>
        </div>

        <!-- التوقيعات الرسمية -->
        <div class="signatures">
            <div class="sig-block">
                <p>المستلم / طالب الخدمة</p>
                <div class="sig-line"></div>
            </div>
            <div class="sig-block">
                <p>المحاسب المسؤول</p>
                <div class="sig-line"></div>
            </div>
            <div class="sig-block">
                <p>اعتماد الإدارة / الختم</p>
                <div class="sig-line"></div>
            </div>
        </div>

        <div class="footer-note">
            وثيقة مالية رسمية صادرة من نظام الفواتير وسندات الخدمات — {{ $invoice->company?->trade_name ?? config('app.name', 'مجموعة فواز') }}
        </div>
    </div>

</body>
</html>
