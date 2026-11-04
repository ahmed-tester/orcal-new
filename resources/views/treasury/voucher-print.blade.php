<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isOutgoing = ($transaction->transaction_type instanceof \App\Enums\TreasuryTransactionType)
            ? $transaction->transaction_type === \App\Enums\TreasuryTransactionType::OUTGOING
            : ($transaction->transaction_type === 'outgoing');

        $voucherTitle = $isOutgoing ? 'سند صرف رسمي' : 'سند قبض رسمي';
        $themeColor = $isOutgoing ? '#dc2626' : '#16a34a';
        $themeBg = $isOutgoing ? '#fef2f2' : '#f0fdf4';
        $themeBorder = $isOutgoing ? '#fecaca' : '#bbf7d0';

        $companyName = $transaction->company?->trade_name ?? 'الشركة / المؤسسة';
        $partyName = $transaction->recipient_or_payer ?: '—';

        $mainItem = $transaction->paymentCollectionItem?->main_item;
        $mainItemLabel = $mainItem instanceof \App\Enums\PaymentItemCategory
            ? $mainItem->getLabel()
            : (is_string($mainItem) ? (__('finance.payment_collection_items.categories.' . $mainItem) ?: $mainItem) : '');
        $subItem = $transaction->paymentCollectionItem?->sub_item;
        $itemDisplay = ($subItem && $mainItemLabel && $subItem !== $mainItemLabel)
            ? "{$subItem} ({$mainItemLabel})"
            : ($subItem ?: ($mainItemLabel ?: '—'));

        $cleanDesc = $transaction->description ?? 'لا يوجد بيان';
        $cleanDesc = str_replace(
            ['Salary Payment', 'Salary Collection', 'salary_payment', 'salary_collection'],
            ['تسليم رواتب', 'تحصيل رواتب', 'تسليم رواتب', 'تحصيل رواتب'],
            $cleanDesc
        );

        $executorName = $transaction->executor?->user?->name ?? ($transaction->executor?->name ?? 'مدير النظام');
        $hijriDate = \App\Services\HijriDateService::toHijriNumeric($transaction->transaction_date);
    @endphp
    <title>{{ $voucherTitle }} - {{ $transaction->voucher_number ?? $transaction->transaction_number }}</title>
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
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 24px;
            direction: rtl;
        }
        .voucher-card {
            background: #ffffff;
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 22px;
        }
        .company-info h2 {
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .company-info p {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }
        .voucher-title-badge {
            text-align: center;
            border: 2px solid {{ $themeColor }};
            background-color: {{ $themeBg }};
            color: {{ $themeColor }};
            padding: 8px 24px;
            border-radius: 10px;
        }
        .voucher-title-badge h1 {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .voucher-meta {
            text-align: left;
            font-size: 13px;
            color: #475569;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .voucher-meta strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* Hero Amount Card */
        .hero-amount-card {
            background: {{ $themeBg }};
            border: 1.5px solid {{ $themeBorder }};
            border-radius: 12px;
            padding: 16px 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .hero-amount-label {
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 4px;
        }
        .hero-amount-number {
            font-size: 32px;
            font-weight: 900;
            color: {{ $themeColor }};
            font-family: monospace, sans-serif;
            margin-bottom: 4px;
        }
        .hero-tafqeet {
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
        }

        /* Parties Flow Cards */
        .parties-box {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .parties-flow-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .party-card {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .party-role-tag {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
        }
        .party-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .party-flow-arrow {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            font-weight: 900;
            font-size: 18px;
            flex-shrink: 0;
        }
        .parties-summary-text {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .details-table td {
            padding: 12px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .details-table td.label {
            width: 25%;
            font-weight: 800;
            color: #475569;
            background-color: #f8fafc;
            border-left: 1px solid #e2e8f0;
        }
        .details-table td.value {
            color: #0f172a;
            font-weight: 600;
        }

        .print-btn {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-btn:hover {
            background-color: #1e293b;
            transform: translateY(-1px);
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .voucher-card {
                box-shadow: none;
                border: 1.5px solid #cbd5e1;
                padding: 24px;
                max-width: 100%;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">
        <span>🖨️</span>
        <span>طباعة السند</span>
    </button>

    <div class="voucher-card">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <h2>{{ $companyName }}</h2>
                <p>سند مالي رسمي — الخزينة والمحاسبة</p>
            </div>
            
            <div class="voucher-title-badge">
                <h1>{{ $voucherTitle }}</h1>
            </div>

            <div class="voucher-meta">
                <div><strong>رقم السند:</strong> {{ $transaction->voucher_number ?? $transaction->transaction_number }}</div>
                <div><strong>رقم الحركة:</strong> {{ $transaction->transaction_number }}</div>
                <div><strong>التاريخ:</strong> {{ $transaction->transaction_date?->format('Y-m-d') }} م @if($hijriDate) • {{ $hijriDate }} هـ @endif</div>
            </div>
        </div>

        {{-- Hero Amount Card --}}
        <div class="hero-amount-card">
            <div class="hero-amount-label">المبلغ المالي الصافي</div>
            <div class="hero-amount-number">{{ number_format($transaction->amount, 2) }} ر.س</div>
            <div class="hero-tafqeet">{{ \App\Services\TafqeetService::inArabic((float)$transaction->amount) }}</div>
        </div>

        {{-- Parties Flow (من استلم من من بوضوح تام) --}}
        <div class="parties-box">
            <div class="parties-flow-grid">
                @if($isOutgoing)
                    {{-- Outgoing: Company PAYS -> Beneficiary RECEIVES --}}
                    <div class="party-card" style="border-right: 4px solid #dc2626;">
                        <span class="party-role-tag" style="color: #dc2626;">الطرف الدافع (صُرف من خزينة)</span>
                        <span class="party-name">{{ $companyName }}</span>
                    </div>

                    <div class="party-flow-arrow" title="تم التحويل والصرف إلى">←</div>

                    <div class="party-card" style="border-right: 4px solid #16a34a;">
                        <span class="party-role-tag" style="color: #16a34a;">المستفيد المستلم (صُرف إلى المكرم)</span>
                        <span class="party-name">{{ $partyName }}</span>
                    </div>
                @else
                    {{-- Incoming: Beneficiary/Client PAYS -> Company RECEIVES --}}
                    <div class="party-card" style="border-right: 4px solid #2563eb;">
                        <span class="party-role-tag" style="color: #2563eb;">الطرف الدافع (استلمنا من المكرم)</span>
                        <span class="party-name">{{ $partyName }}</span>
                    </div>

                    <div class="party-flow-arrow" title="تم الإيداع والاستلام في">←</div>

                    <div class="party-card" style="border-right: 4px solid #16a34a;">
                        <span class="party-role-tag" style="color: #16a34a;">الجهة المستلمة (أودع في خزينة)</span>
                        <span class="party-name">{{ $companyName }}</span>
                    </div>
                @endif
            </div>

            <div class="parties-summary-text">
                @if($isOutgoing)
                    <span>📌</span>
                    <span><strong>الإقرار المحاسبي:</strong> صُرف المبلغ الموضح أعلاه من خزينة <strong>{{ $companyName }}</strong> إلى المكرم <strong>{{ $partyName }}</strong>.</span>
                @else
                    <span>📌</span>
                    <span><strong>الإقرار المحاسبي:</strong> استلمت خزينة <strong>{{ $companyName }}</strong> المبلغ الموضح أعلاه من المكرم <strong>{{ $partyName }}</strong>.</span>
                @endif
            </div>
        </div>

        {{-- Details Table --}}
        <table class="details-table">
            <tr>
                <td class="label">البند المحاسبي:</td>
                <td class="value"><strong>{{ $itemDisplay }}</strong></td>
            </tr>
            <tr>
                <td class="label">وذلك عن (البيان):</td>
                <td class="value">{{ $cleanDesc }}</td>
            </tr>
            <tr>
                <td class="label">المؤسسة التابعة:</td>
                <td class="value">{{ $companyName }}</td>
            </tr>
            <tr>
                <td class="label">المسؤول / منفذ السند:</td>
                <td class="value">{{ $executorName }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
