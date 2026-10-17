<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>{{ $offer->offer_type?->getLabel() ?? 'عرض سعر' }} - {{ $offer->offer_number }}</title>
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

    /* ─── Header ──────────────────────────────────────────── */
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

    /* ─── Title & Meta Bar ────────────────────────────────── */
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
        margin-bottom: 12px;
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

    /* ─── Contract Preamble ───────────────────────────────── */
    .contract-preamble {
        background-color: #fbfcfe;
        border: 1px solid #d9e2ec;
        border-right: 4px solid #1a4478;
        padding: 10px 14px;
        margin-bottom: 12px;
        font-size: 9.5pt;
        line-height: 1.8;
    }

    .party-box {
        margin-top: 6px;
        padding: 4px 0;
        border-top: 1px dashed #e2e8f0;
    }

    .party-title {
        font-weight: bold;
        color: #1a4478;
        font-size: 10pt;
    }

    /* ─── Intro Text (For Quotations) ─────────────────────── */
    .intro-section {
        margin-bottom: 12px;
        font-size: 9.5pt;
        line-height: 1.8;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
    }

    .intro-line {
        margin-bottom: 3px;
    }

    /* ─── Contract Clauses (Rich Text Content) ────────────── */
    .contract-clauses {
        margin-top: 10px;
        margin-bottom: 16px;
        line-height: 1.8;
        font-size: 9.5pt;
    }

    .contract-clauses h2, .contract-clauses h3 {
        color: #1a4478;
        font-size: 11pt;
        margin-top: 12px;
        margin-bottom: 5px;
        border-bottom: 1px solid #d9e2ec;
        padding-bottom: 3px;
    }

    .contract-clauses p {
        margin-bottom: 6px;
    }

    .contract-clauses ul, .contract-clauses ol {
        margin-right: 20px;
        margin-bottom: 8px;
    }

    .contract-clauses li {
        margin-bottom: 3px;
    }

    .contract-clauses table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        margin-top: 6px;
        border: 1.5px solid #000000;
    }

    .contract-clauses table td, .contract-clauses table th {
        border: 1px solid #000000;
        padding: 5px 8px;
        font-size: 9pt;
    }

    .contract-clauses table th {
        background-color: #eef3f8;
        color: #1a4478;
        font-weight: bold;
        text-align: center;
    }

    .notes-box {
        background-color: #fffbeb;
        border: 1px solid #fef3c7;
        border-right: 4px solid #f59e0b;
        padding: 6px 12px;
        margin-bottom: 12px;
        font-size: 9pt;
        line-height: 1.6;
    }

    /* ─── Signatures Block ────────────────────────────────── */
    .signature-container {
        width: 100%;
        margin-top: 16px;
        margin-bottom: 10px;
        page-break-inside: avoid;
    }

    .sig-party-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sig-party-table td {
        vertical-align: top;
        padding: 8px 12px;
    }

    /* ─── Footer ──────────────────────────────────────────── */
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
    $typeVal = $offer->offer_type instanceof \App\Enums\OfferType 
        ? $offer->offer_type->value 
        : (string)($offer->offer_type ?? 'quotation');
    $isContract = in_array($typeVal, ['unified_contract', 'agreement_contract', 'sale_agreement']);

    $toArabicDigits = function ($str) {
        if ($str === null || $str === '') return '';
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($western, $eastern, (string)$str);
    };
@endphp

<!-- ─── Header ──────────────────────────────────────────── -->
<table class="header-table">
    <tr>
        <!-- 1st Cell in RTL: Appears on the RIGHT (Arabic) -->
        <td class="header-right">
            <div class="co-name-ar">
                {{ $offer->company?->trade_name ?? '' }}
            </div>
            <div class="meta-ar">
                @if($offer->company?->commercial_registration_number)
                س - ت / <b>{{ $toArabicDigits($offer->company->commercial_registration_number) }}</b><br>
                @endif
                @if($offer->company?->zakat_number)
                الرقم الضريبي / <b>{{ $toArabicDigits($offer->company->zakat_number) }}</b>
                @endif
            </div>
        </td>

        <!-- 2nd Cell in RTL: Appears in the CENTER (Logo) -->
        <td class="header-center">
            @php
                $logoFile = null;
                if ($offer->company?->logo) {
                    $possiblePath = storage_path('app/public/' . $offer->company->logo);
                    if (file_exists($possiblePath)) {
                        $logoFile = $possiblePath;
                    }
                }
            @endphp

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
        </td>

        <!-- 3rd Cell in RTL: Appears on the LEFT (English) -->
        <td class="header-left">
            <div class="co-name-en">
                {{ $offer->company?->trade_name_en ?: ($offer->company?->additional_info ?: $offer->company?->trade_name) }}
            </div>
            <div class="meta-en">
                @if($offer->company?->commercial_registration_number)
                C.R. <b>{{ $offer->company->commercial_registration_number }}</b><br>
                @endif
                @if($offer->company?->zakat_number)
                Tax number / <b>{{ $offer->company->zakat_number }}</b>
                @endif
            </div>
        </td>
    </tr>
</table>

<div class="header-divider"></div>

<!-- ─── Document Title ──────────────────────────────────── -->
<div class="doc-title-container">
    <span class="doc-title">{{ $offer->offer_type?->getLabel() ?? 'عرض سعر' }}</span>
</div>

<!-- ─── Document Meta Bar (رقم المستند، التاريخ، الصلاحية) ─── -->
<table class="doc-meta-table">
    <tr>
        <td style="width: 40%;">
            <span class="meta-label">{{ $isContract ? 'رقم العقد:' : 'رقم العرض:' }}</span>
            <span class="meta-val">{{ $offer->offer_number }}</span>
        </td>
        <td style="width: {{ (!$isContract && $offer->valid_until) ? '30%' : '60%' }}; text-align: {{ (!$isContract && $offer->valid_until) ? 'center' : 'left' }};">
            <span class="meta-label">التاريخ:</span>
            <span class="meta-val">{{ $offer->offer_date?->format('Y/m/d') ?? date('Y/m/d') }}م</span>
        </td>
        @if(!$isContract && $offer->valid_until)
        <td style="width: 30%; text-align: left;">
            <span class="meta-label">صالح حتى:</span>
            <span class="meta-val">{{ $offer->valid_until->format('Y/m/d') }}م</span>
        </td>
        @endif
    </tr>
</table>

@if($isContract)
<!-- ─── Contract Preamble & Parties (التمهيد وأطراف العقد) ──── -->
<div class="contract-preamble">
    <div style="text-align: center; margin-bottom: 8px; font-weight: bold; font-size: 11pt; color: #1a4478;">بسم الله الرحمن الرحيم</div>
    <div style="margin-bottom: 6px;">
        إنه في يوم <b>{{ $offer->offer_date?->translatedFormat('l') ?? '' }}</b> 
        الموافق <b>{{ $offer->offer_date?->format('Y/m/d') ?? date('Y/m/d') }}م</b>، 
        تم الاتفاق والتراضي بين كل من:
    </div>

    <!-- الطرف الأول: المقاول / المنفذ -->
    <div class="party-box">
        <span class="party-title">1. الطرف الأول (المقاول / المنفذ):</span><br>
        <b>{{ $offer->company?->trade_name }}</b>
        @if($offer->company?->commercial_registration_number)
            - سجل تجاري رقم: <b>{{ $offer->company->commercial_registration_number }}</b>
        @endif
        @if($offer->company?->zakat_number)
            - الرقم الضريبي: <b>{{ $offer->company->zakat_number }}</b>
        @endif
        @if($offer->company?->address)
            - ومقرها: <b>{{ $offer->company->address }}</b>
        @endif
        <br>
        @if($offer->manager?->user?->name)
            ويمثلها في هذا العقد السيد: <b>{{ $offer->manager->user->name }}</b>
            (بصفته: <b>{{ $offer->manager->jobTitle?->name ?? 'المدير التنفيذي' }}</b>)
            @if($offer->manager->user->phone) - جوال: <b>{{ $offer->manager->user->phone }}</b> @endif
        @elseif($offer->company?->phone)
            - جوال / هاتف: <b>{{ $offer->company->phone }}</b>
        @endif
    </div>

    <!-- الطرف الثاني: العميل / المالك -->
    <div class="party-box">
        <span class="party-title">2. الطرف الثاني (المالك / العميل):</span><br>
        <b>{{ $offer->customer?->name }}</b>
        @if($offer->customer?->commercial_registration_number)
            - سجل تجاري رقم: <b>{{ $offer->customer->commercial_registration_number }}</b>
        @elseif($offer->customer?->national_id)
            - بطاقة هوية / إقامة رقم: <b>{{ $offer->customer->national_id }}</b>
        @endif
        @if($offer->customer?->tax_number)
            - الرقم الضريبي: <b>{{ $offer->customer->tax_number }}</b>
        @endif
        @if($offer->customer?->address || $offer->customer?->city)
            - المقر / العنوان: <b>{{ implode(' - ', array_filter([$offer->customer?->city, $offer->customer?->address])) }}</b>
        @endif
        @if($offer->customer?->phone)
            - جوال: <b>{{ $offer->customer->phone }}</b>
        @endif
        @if($offer->customer?->email)
            - بريد إلكتروني: <b>{{ $offer->customer->email }}</b>
        @endif
    </div>

    <!-- موضوع العقد والتمهيد -->
    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #d9e2ec;">
        <b>موضوع العقد:</b> {{ $offer->subject }}<br>
        @if(!empty($offer->description))
            <b>تمهيد العقد:</b> {!! nl2br(e($offer->description)) !!}
        @else
            اتفق الطرفان بكامل أهليتهما المعتبرة شرعاً ونظاماً على أن يقوم الطرف الأول بتنفيذ وتوريد الأعمال المبينة في هذا العقد لصالح الطرف الثاني، طبقاً للشروط والمواصفات وجدول الأسعار الموضح أدناه:
        @endif
    </div>
</div>
@else
<!-- ─── Quotation Intro (ديباجة عروض الأسعار والتوريد) ──────── -->
<div class="intro-section">
    <div class="intro-line">
        <b>السادة الكرام /</b> {{ $offer->customer?->name ?? '────────────────' }}
        @if($offer->customer?->commercial_registration_number)
            (س.ت: {{ $offer->customer->commercial_registration_number }})
        @elseif($offer->customer?->national_id)
            (هوية: {{ $offer->customer->national_id }})
        @endif
        @if($offer->customer?->phone)
            - جوال: {{ $offer->customer->phone }}
        @endif
    </div>

    <div class="intro-line" style="margin-top: 4px;">
        <b>بخصوص /</b> {{ $offer->subject }}
    </div>

    <div class="intro-line" style="margin-top: 4px; font-weight: normal;">
        @if(!empty($offer->description))
            {!! nl2br(e($offer->description)) !!}
        @else
            بناءً على طلبكم الكريم وبعد دراسة متطلبات العمل ومطابقة المواصفات، يسرنا أن نقدم لكم عرض السعر والمواصفات الفنية وفقاً للتالي:
        @endif
    </div>
</div>
@endif

<!-- ─── Contract Clauses / Terms & Pricing Table (من محرر النصوص RichEditor) ─── -->
@if(!empty($offer->terms_and_conditions))
<div class="contract-clauses">
    {!! str_contains($offer->terms_and_conditions, '<') ? $offer->terms_and_conditions : nl2br(e($offer->terms_and_conditions)) !!}
</div>
@endif

<!-- ─── Notes (الملاحظات الإضافية) ─────────────────────────────── -->
@if(!empty($offer->notes))
<div class="notes-box">
    <b>ملاحظات إضافية:</b> {!! nl2br(e($offer->notes)) !!}
</div>
@endif

<!-- ─── Signatures Block (التواقيع والأختام) ─────────────────────── -->
<div class="signature-container">
    @if($isContract)
    <!-- Dual Signatures for Contracts (توقيع وختم الطرفين) -->
    <table class="sig-party-table">
        <tr>
            <!-- الطرف الأول (المقاول / المنفذ) على اليمين -->
            <td style="width: 50%; text-align: center; border-left: 1px solid #d9e2ec; padding-left: 10px;">
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 5px; color: #1a4478;">الطرف الأول (المقاول / المنفذ)</div>
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 4px;">{{ $offer->company?->trade_name }}</div>
                @if($offer->manager?->user?->name)
                <div style="font-size: 9.5pt; color: #333; margin-bottom: 4px;">يمثلها: <b>{{ $offer->manager->user->name }}</b></div>
                <div style="font-size: 9pt; color: #555; margin-bottom: 4px;">({{ $offer->manager->jobTitle?->name ?? 'المدير التنفيذي' }})</div>
                @endif
                <div style="font-size: 9.5pt; font-weight: bold; margin-top: 6px; margin-bottom: 6px;">التوقيع: ....................................</div>
                @if($offer->manager?->user?->phone)
                <div style="font-size: 9pt; margin-bottom: 8px;">الجوال: {{ $offer->manager->user->phone }}</div>
                @endif

                @if($offer->show_stamp)
                <div style="margin-top: 6px;">
                    <table style="margin: 0 auto; border: 2px solid #2377c3; border-radius: 28px; border-collapse: separate; padding: 4px 12px; text-align: center; color: #2377c3; font-weight: bold;">
                        <tr><td style="font-size: 8.5pt; text-align: center; color: #2377c3; padding: 1px 4px; border-bottom: 1px dashed #2377c3;">★ {{ $offer->company?->trade_name }} ★</td></tr>
                        @if($offer->company?->commercial_registration_number)
                        <tr><td style="font-size: 8pt; text-align: center; color: #2377c3; padding: 2px 4px;">س . ت {{ $offer->company->commercial_registration_number }}</td></tr>
                        @endif
                        <tr><td style="font-size: 7.5pt; text-align: center; color: #2377c3; padding: 1px 4px; border-top: 1px dashed #2377c3;">{{ $offer->company?->address ?? 'المملكة العربية السعودية' }}</td></tr>
                    </table>
                </div>
                @endif
            </td>

            <!-- الطرف الثاني (المالك / العميل) على اليسار -->
            <td style="width: 50%; text-align: center; padding-right: 10px;">
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 5px; color: #1a4478;">الطرف الثاني (المالك / العميل)</div>
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 4px;">{{ $offer->customer?->name }}</div>
                <div style="font-size: 9.5pt; color: #333; margin-bottom: 4px;">الاسم / الممثل: ..................................</div>
                <div style="font-size: 9.5pt; font-weight: bold; margin-top: 6px; margin-bottom: 6px;">التوقيع: ....................................</div>
                <div style="font-size: 9pt; margin-bottom: 8px;">الجوال: {{ $offer->customer?->phone ?? '....................................' }}</div>
                <div style="font-size: 9pt; color: #666; margin-top: 12px;">الختم: ....................................</div>
            </td>
        </tr>
    </table>
    @else
    <!-- Single Signature Block for Quotations (توقيع المؤسسة للعروض) -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 55%; vertical-align: top; text-align: right; padding-top: 4px;">
                <div style="font-size: 11.5pt; font-weight: bold; margin-bottom: 4px; color: #1a4478;">
                    {{ $offer->company?->trade_name }}
                </div>
                
                @if($offer->manager)
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 3px; color: #222;">
                    {{ $offer->manager->jobTitle?->name ?? 'المدير التنفيذي' }}: {{ $offer->manager->user?->name }}
                </div>
                @endif
                
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 5px; color: #111;">
                    التوقيع: ....................................
                </div>
                
                @if($offer->manager?->user?->phone)
                <div style="font-size: 9.5pt; font-weight: bold; margin-bottom: 8px; color: #333;">
                    الجوال: {{ $offer->manager->user->phone }}
                </div>
                @endif

                @if($offer->supervisor)
                <div style="margin-top: 6px; margin-bottom: 10px;">
                    <div style="font-size: 10pt; font-weight: bold; margin-bottom: 3px; color: #222;">
                        {{ $offer->supervisor->jobTitle?->name ?? 'مشرف المواقع' }}: {{ $offer->supervisor->user?->name }}
                    </div>
                    @if($offer->supervisor->user?->phone)
                    <div style="font-size: 9pt; color: #444;">
                        جوال: {{ $offer->supervisor->user->phone }}
                    </div>
                    @endif
                </div>
                @endif

                @if($offer->show_stamp)
                <div style="margin-top: 8px;">
                    <table style="border: 2px solid #2377c3; border-radius: 28px; border-collapse: separate; padding: 4px 14px; text-align: center; color: #2377c3; font-weight: bold;">
                        <tr><td style="font-size: 8.5pt; text-align: center; color: #2377c3; font-weight: bold; padding: 1px 4px; border-bottom: 1px dashed #2377c3;">★ {{ $offer->company?->trade_name }} ★</td></tr>
                        @if($offer->company?->commercial_registration_number)
                        <tr><td style="font-size: 8pt; text-align: center; color: #2377c3; font-weight: bold; padding: 2px 4px;">س . ت {{ $offer->company->commercial_registration_number }}</td></tr>
                        @endif
                        <tr><td style="font-size: 7.5pt; text-align: center; color: #2377c3; font-weight: bold; padding: 1px 4px; border-top: 1px dashed #2377c3;">{{ $offer->company?->address ?? 'المملكة العربية السعودية' }}</td></tr>
                    </table>
                </div>
                @endif
            </td>
            <td style="width: 45%;"></td>
        </tr>
    </table>
    @endif
</div>

<!-- ─── Page Footer ─────────────────────────────────────── -->
<htmlpagefooter name="docFooter">
    <div class="page-footer">
        @php
            $co = $offer->company;
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