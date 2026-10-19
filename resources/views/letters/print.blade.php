<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>{{ $letter->subject ?: __('official_letters.single') }}</title>
<style>
    @page {
        margin-top: 10mm;
        margin-bottom: 14mm;
        margin-left: 12mm;
        margin-right: 12mm;
        footer: html_docFooter;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'dejavusans', sans-serif;
        font-size: 9.5pt;
        color: #111111;
        direction: rtl;
        text-align: right;
        line-height: 1.6;
    }

    /* --- Header --- */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }

    .header-table td {
        vertical-align: middle;
    }

    .header-right {
        width: 42%;
        text-align: right;
        direction: rtl;
    }

    .header-right .co-name-ar {
        color: #1a4478;
        font-size: 13pt;
        font-weight: bold;
        margin-bottom: 3px;
    }

    .header-right .meta-ar {
        color: #222222;
        font-size: 8.5pt;
        font-weight: bold;
        line-height: 1.4;
    }

    .header-center {
        width: 16%;
        text-align: center;
    }

    .header-left {
        width: 42%;
        text-align: left;
        direction: ltr;
    }

    .header-left .co-name-en {
        color: #1a4478;
        font-size: 11pt;
        font-weight: bold;
        margin-bottom: 3px;
        font-family: 'dejavusans', sans-serif;
    }

    .header-left .meta-en {
        color: #222222;
        font-size: 8.5pt;
        font-weight: bold;
        line-height: 1.4;
    }

    .header-divider {
        border-bottom: 2px solid #1a4478;
        margin-top: 6px;
        margin-bottom: 12px;
        width: 100%;
    }

    /* --- Title & Meta Bar --- */
    .doc-title-container {
        text-align: center;
        margin-bottom: 10px;
    }

    .doc-title {
        font-size: 14pt;
        font-weight: bold;
        color: #1a4478;
        border-bottom: 2px solid #1a4478;
        padding-bottom: 3px;
        display: inline-block;
    }

    .doc-meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        background-color: #f3f6f9;
        border: 1px solid #d0dbe5;
    }

    .doc-meta-table td {
        padding: 5px 12px;
        font-size: 9pt;
        vertical-align: middle;
    }

    .meta-label {
        font-weight: bold;
        color: #1a4478;
    }

    .meta-val {
        color: #111111;
        font-weight: bold;
    }

    /* --- Letter Content / Rich Text --- */
    .letter-body {
        margin-top: 10px;
        margin-bottom: 16px;
        line-height: 1.9;
        font-size: 9.5pt;
        text-align: justify;
    }

    .letter-body h1, .letter-body h2, .letter-body h3 {
        color: #1a4478;
        margin-top: 12px;
        margin-bottom: 6px;
    }

    .letter-body p {
        margin-bottom: 8px;
    }

    .letter-body ul, .letter-body ol {
        margin-right: 20px;
        margin-bottom: 8px;
    }

    .letter-body li {
        margin-bottom: 4px;
    }

    .letter-body table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        margin-top: 6px;
        border: 1px solid #d0dbe5;
    }

    .letter-body table td, .letter-body table th {
        border: 1px solid #d0dbe5;
        padding: 6px 10px;
        font-size: 9pt;
    }

    .letter-body table th {
        background-color: #f3f6f9;
        color: #1a4478;
        font-weight: bold;
    }

    /* --- Signatures Block --- */
    .signature-container {
        width: 100%;
        margin-top: 24px;
        margin-bottom: 10px;
        page-break-inside: avoid;
    }

    /* --- Footer --- */
    .page-footer {
        width: 100%;
        text-align: center;
        font-size: 8pt;
        color: #444444;
        line-height: 1.4;
        border-top: 1px solid #999999;
        padding-top: 4px;
    }
</style>
</head>
<body>

@php
    $logoFile = null;
    if ($company?->logo) {
        $possiblePath = storage_path('app/public/' . $company->logo);
        if (file_exists($possiblePath)) {
            $logoFile = $possiblePath;
        }
    }

    $stampFile = null;
    if ($company?->stamp) {
        $possibleStamp = storage_path('app/public/' . $company->stamp);
        if (file_exists($possibleStamp)) {
            $stampFile = $possibleStamp;
        }
    }

    $toArabicDigits = function ($str) {
        if ($str === null || $str === '') return '';
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($western, $eastern, (string)$str);
    };

    // --- Print / Display Options ---
    $printOptions  = $options ?? [];
    $showLogo      = $printOptions['show_logo'] ?? true;
    $showSignature = $printOptions['show_signature'] ?? true;
    $showStamp     = $printOptions['show_stamp'] ?? true;
@endphp

<!-- --- Header --- -->
<table class="header-table">
    <tr>
        <!-- 1st Cell in RTL: Appears on the RIGHT (Arabic) -->
        <td class="header-right">
            <div class="co-name-ar">
                {{ $company?->trade_name ?? '' }}
            </div>
            <div class="meta-ar">
                @if($company?->commercial_registration_number)
                س - ت / <b>{{ $toArabicDigits($company->commercial_registration_number) }}</b><br>
                @endif
                @if($company?->zakat_number)
                الرقم الضريبي / <b>{{ $toArabicDigits($company->zakat_number) }}</b>
                @endif
            </div>
        </td>

        <!-- 2nd Cell in RTL: Appears in the CENTER (Logo) -->
        <td class="header-center">
            @if($showLogo)
                @if($logoFile)
                    <img src="{{ $logoFile }}" style="max-height: 65px; max-width: 140px;">
                @else
                    <svg width="55" height="55" viewBox="0 0 100 100" fill="#1a4478" xmlns="http://www.w3.org/2000/svg">
                        <path d="M 10 85 A 45 45 0 0 1 90 85 Z" fill="none" stroke="#1a4478" stroke-width="3"/>
                        <rect x="5" y="87" width="90" height="3" fill="#1a4478" />
                        <rect x="46" y="20" width="8" height="65" rx="3" fill="#1a4478"/>
                        <rect x="34" y="32" width="7" height="53" rx="3" fill="#1a4478"/>
                        <rect x="59" y="32" width="7" height="53" rx="3" fill="#1a4478"/>
                        <rect x="23" y="46" width="7" height="39" rx="3" fill="#1a4478"/>
                        <rect x="70" y="46" width="7" height="39" rx="3" fill="#1a4478"/>
                    </svg>
                @endif
            @endif
        </td>

        <!-- 3rd Cell in RTL: Appears on the LEFT (English) -->
        <td class="header-left">
            <div class="co-name-en">
                {{ $company?->trade_name_en ?: ($company?->additional_info ?: $company?->trade_name) }}
            </div>
            <div class="meta-en">
                @if($company?->commercial_registration_number)
                C.R. <b>{{ $company->commercial_registration_number }}</b><br>
                @endif
                @if($company?->zakat_number)
                Tax number / <b>{{ $company->zakat_number }}</b>
                @endif
            </div>
        </td>
    </tr>
</table>

<div class="header-divider"></div>

<!-- --- Document Title --- -->
<div class="doc-title-container">
    <span class="doc-title">{{ $letter->subject ?: 'خطاب رسمي' }}</span>
</div>

<!-- --- Document Meta Bar (Date only) --- -->
<table class="doc-meta-table">
    <tr>
        <td style="text-align: right;">
            <span class="meta-label">التاريخ:</span>
            <span class="meta-val">{{ $letter->letter_date?->format('Y/m/d') ?? date('Y/m/d') }}م</span>
        </td>
    </tr>
</table>

<!-- --- Bismillah --- -->
<div style="text-align: center; margin-bottom: 12px; font-weight: bold; font-size: 11pt; color: #1a4478;">
    بسم الله الرحمن الرحيم
</div>

<!-- --- Letter Content --- -->
<div class="letter-body">
    {!! $letter->body ?? '<p style="color:#888;font-style:italic;">لم يتم إدخال نص الخطاب بعد.</p>' !!}
</div>

<!-- --- Closing --- -->
<div style="margin-top: 16px; margin-bottom: 20px; font-weight: bold; font-size: 9.5pt; color: #1a4478;">
    وتقبلوا خالص التحية والتقدير،،،
</div>

@if($showSignature || $showStamp)
<!-- --- Signature & Stamp --- -->
<div class="signature-container">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            @if($showSignature)
            <td style="width: {{ $showStamp ? '55%' : '100%' }}; vertical-align: top; text-align: right;">
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 16px; color: #1a4478;">
                    {{ $company?->trade_name ?? '' }}
                </div>
                <div style="font-size: 9.5pt; font-weight: bold; margin-bottom: 16px; color: #111;">
                    التوقيع: ....................................
                </div>
                @if($company?->phone)
                <div style="font-size: 9pt; color: #555;">
                    هاتف: {{ $company->phone }}
                </div>
                @endif
            </td>
            @endif
            @if($showStamp)
            <td style="width: {{ $showSignature ? '45%' : '100%' }}; vertical-align: middle; text-align: center;">
                @if($stampFile)
                    <img src="{{ $stampFile }}" style="max-height: 95px; max-width: 160px; margin: 0 auto; display: block;">
                @elseif($company)
                    <table style="margin: 0 auto; border: 2px solid #2377c3; border-collapse: collapse; padding: 4px 10px; text-align: center; color: #2377c3; font-weight: bold; width: 140px;">
                        <tr><td style="font-size: 8.5pt; text-align: center; color: #2377c3; font-weight: bold; padding: 2px 4px; border-bottom: 1px dashed #2377c3;">{{ $company->trade_name }}</td></tr>
                        @if($company->commercial_registration_number)
                        <tr><td style="font-size: 8pt; text-align: center; color: #2377c3; font-weight: bold; padding: 2px 4px;">س . ت {{ $company->commercial_registration_number }}</td></tr>
                        @endif
                        <tr><td style="font-size: 7.5pt; text-align: center; color: #2377c3; font-weight: bold; padding: 2px 4px; border-top: 1px dashed #2377c3;">{{ $company->address ?? 'المملكة العربية السعودية' }}</td></tr>
                    </table>
                @endif
            </td>
            @endif
        </tr>
    </table>
</div>
@endif

<!-- --- Page Footer --- -->
<htmlpagefooter name="docFooter">
    <div class="page-footer">
        @php
            $co = $company;
            $enParts = array_filter([
                $co?->address_en ?: $co?->address,
                $co?->postal_code ? 'P. Code ' . $co->postal_code : null,
                $co?->phone ? 'Mob.: ' . $co->phone : null,
                $co?->email ? 'Email: ' . $co->email : null,
            ]);
            $arParts = array_filter([
                $co?->address,
                $co?->postal_code ? 'الرمز البريدي: ' . $toArabicDigits($co->postal_code) : null,
                $co?->phone ? 'جوال: ' . $toArabicDigits($co->phone) : null,
                $co?->email ? 'بريد: ' . $co->email : null,
            ]);
        @endphp

        @if(count($arParts) > 0)
            <div style="direction: rtl; text-align: center; font-weight: bold; font-size: 8.5pt; color: #1a4478; margin-bottom: 3px; line-height: 1.4;">
                {{ implode(' - ', $arParts) }}
            </div>
        @endif

        @if(count($enParts) > 0)
            <div style="direction: ltr; text-align: center; font-weight: bold; font-size: 8.5pt; color: #222222; line-height: 1.4;">
                {{ implode(' - ', $enParts) }}
            </div>
        @endif
    </div>
</htmlpagefooter>

</body>
</html>
