<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($voucher->voucher_type === 'request')
            {{ __('contract_vouchers.types.request') }}
        @elseif($voucher->voucher_type === 'payment')
            {{ __('contract_vouchers.types.payment') }}
        @else
            {{ __('contract_vouchers.types.receipt') }}
        @endif
        - {{ $voucher->voucher_number }}
    </title>
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
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #1a4478;
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
            color: #1a4478;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #64748b;
            font-size: 13px;
        }
        .voucher-title-box {
            text-align: center;
            border: 2px solid {{ $voucher->voucher_type === 'payment' ? '#dc2626' : ($voucher->voucher_type === 'request' ? '#d97706' : '#16a34a') }};
            background-color: {{ $voucher->voucher_type === 'payment' ? '#fef2f2' : ($voucher->voucher_type === 'request' ? '#fffbeb' : '#f0fdf4') }};
            padding: 10px 25px;
            border-radius: 8px;
        }
        .voucher-title-box h1 {
            color: {{ $voucher->voucher_type === 'payment' ? '#dc2626' : ($voucher->voucher_type === 'request' ? '#d97706' : '#16a34a') }};
            font-size: 20px;
            margin-bottom: 5px;
        }
        .voucher-meta {
            text-align: left;
            font-size: 13px;
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
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
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
            color: #1a4478;
            font-weight: 600;
            font-style: italic;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
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
            margin-top: 25px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .print-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #1a4478;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
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
                padding: 15px;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">🖨️ {{ __('contract_vouchers.actions.print') }}</button>

    <div class="voucher-card">
        <div class="header">
            <div class="company-info">
                <h2>{{ $voucher->company?->trade_name ?? config('app.name') }}</h2>
                <p>{{ __('navigation.contracts_group') }}</p>
                @if($voucher->company?->commercial_registration_number)
                    <p>{{ __('customers.fields.commercial_registration_number') }}: {{ $voucher->company->commercial_registration_number }}</p>
                @endif
            </div>

            <div class="voucher-title-box">
                <h1>
                    @if($voucher->voucher_type === 'request')
                        {{ __('contract_vouchers.types.request') }}
                    @elseif($voucher->voucher_type === 'payment')
                        {{ __('contract_vouchers.types.payment') }}
                    @else
                        {{ __('contract_vouchers.types.receipt') }}
                    @endif
                </h1>
                <span style="font-size: 12px; color: #64748b;">
                    @if($voucher->voucher_type === 'request')
                        Payment Request
                    @elseif($voucher->voucher_type === 'payment')
                        Payment Voucher
                    @else
                        Receipt Voucher
                    @endif
                </span>
            </div>

            <div class="voucher-meta">
                <div><strong>{{ __('contract_vouchers.fields.voucher_number') }}:</strong> {{ $voucher->voucher_number }}</div>
                <div><strong>{{ __('contract_vouchers.fields.voucher_date') }}:</strong> {{ $voucher->voucher_date?->format('Y-m-d') }}</div>
                <div><strong>{{ __('contract_vouchers.fields.status') }}:</strong> {{ __('contract_vouchers.statuses.' . $voucher->status) }}</div>
            </div>
        </div>

        <table class="details-table">
            @if($voucher->voucher_type === 'request')
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.first_party_name') }}:</td>
                    <td><strong>{{ $voucher->first_party_name ?: ($voucher->company?->trade_name ?: '—') }}</strong></td>
                </tr>
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.second_party_name') }}:</td>
                    <td><strong>{{ $voucher->second_party_name ?: ($voucher->customer?->name ?: '—') }}</strong></td>
                </tr>
                @if($voucher->contract_number)
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.contract_number') }}:</td>
                    <td><strong>{{ $voucher->contract_number }}</strong> @if($voucher->contract_date) (تاريخ العقد: {{ $voucher->contract_date?->format('Y-m-d') }}) @endif</td>
                </tr>
                @endif
                @if($voucher->contract_subject)
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.contract_subject') }}:</td>
                    <td>{{ $voucher->contract_subject }}</td>
                </tr>
                @endif
                @if($voucher->payment_reason)
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.payment_reason') }}:</td>
                    <td><strong>{{ $voucher->payment_reason }}</strong></td>
                </tr>
                @endif
            @else
                <tr>
                    <td class="label">
                        {{ $voucher->voucher_type === 'payment' ? 'صرفنا إلى المكرم / الجهة:' : 'استلمنا من المكرم / الجهة:' }}
                    </td>
                    <td>
                        <strong>
                            @if($voucher->voucher_type === 'payment')
                                {{ $voucher->received_by ?: ($voucher->customer?->name ?: ($voucher->second_party_name ?: '—')) }}
                            @else
                                {{ $voucher->delivered_by ?: ($voucher->customer?->name ?: ($voucher->first_party_name ?: '—')) }}
                            @endif
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.payment_method') }}:</td>
                    <td>
                        <strong>{{ __('contract_vouchers.payment_methods.' . ($voucher->payment_method ?? 'cash')) }}</strong>
                        @if($voucher->payment_method === 'check')
                            (رقم الشيك: <b>{{ $voucher->check_number }}</b> - بنك: <b>{{ $voucher->bank_name }}</b> - استحقاق: <b>{{ $voucher->due_date?->format('Y-m-d') }}</b>)
                        @elseif($voucher->payment_method === 'bank_transfer')
                            (بنك: <b>{{ $voucher->transfer_bank_name }}</b> - حساب: <b>{{ $voucher->account_number }}</b> - مرجع: <b>{{ $voucher->reference_number }}</b>)
                        @endif
                    </td>
                </tr>
                @if($voucher->contract_number || $voucher->offer_id)
                <tr>
                    <td class="label">{{ __('contract_vouchers.fields.offer') }}:</td>
                    <td>{{ $voucher->contract_number ?: $voucher->offer?->offer_number }} {{ $voucher->contract_subject ? ' - ' . $voucher->contract_subject : '' }}</td>
                </tr>
                @endif
            @endif

            <tr>
                <td class="label">{{ __('contract_vouchers.fields.amount') }}:</td>
                <td>
                    <span class="amount-box">{{ number_format($voucher->amount, 2) }} {{ __('offers.currency_symbol') }}</span>
                </td>
            </tr>
            <tr>
                <td class="label">المبلغ كتابةً:</td>
                <td class="tafqeet-text">
                    {{ \App\Services\TafqeetService::inArabic((float)$voucher->amount) }}
                </td>
            </tr>

            @if($voucher->notes)
            <tr>
                <td class="label">{{ __('contract_vouchers.fields.notes') }}:</td>
                <td>{{ $voucher->notes }}</td>
            </tr>
            @endif
        </table>

        <div class="signatures">
            <div class="signature-box">
                <p>
                    @if($voucher->voucher_type === 'request')
                        المسؤول عن الطلب
                    @elseif($voucher->voucher_type === 'payment')
                        توقيع المستلم
                    @else
                        توقيع المسلّم
                    @endif
                </p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>المحاسب المسؤول</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>المدير العام / الاعتماد</p>
                <div class="signature-line"></div>
            </div>
        </div>

        <div class="footer-note">
            وثيقة رسمية صادرة من نظام المقاولات والتوريد — {{ $voucher->company?->trade_name ?? config('app.name') }}
        </div>
    </div>

</body>
</html>
