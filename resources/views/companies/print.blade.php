<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('organization.companies.print.title') }} - {{ $company->trade_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
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
            padding: 20px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
        }
        .action-bar {
            max-width: 860px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .action-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #082c50;
        }
        .action-title-code {
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .action-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease-in-out;
            line-height: 1.4;
            white-space: nowrap;
        }
        .btn-icon {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
        }
        .btn-print {
            background-color: #082c50;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #0c3d6e;
        }
        .btn-close {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-close:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        .print-card {
            background: #ffffff;
            max-width: 860px;
            margin: 0 auto;
            border: 2px solid #082c50;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .header-top {
            width: 100%;
            border-bottom: 2px solid #082c50;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .company-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 4px;
            background: #ffffff;
            margin-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 14px;
        }
        .brand-details h1 {
            color: #082c50;
            font-size: 20px;
            margin-bottom: 4px;
            font-weight: 800;
        }
        .brand-details h2 {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }
        .header-meta {
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }
        .doc-badge {
            display: inline-block;
            background: #082c50;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
        }
        .status-badge-active {
            background: #dcfce7;
            color: #166534;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        .status-badge-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        .section-title {
            background-color: #f1f5f9;
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #082c50;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: bold;
            color: #082c50;
            margin-top: 16px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .field-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 4px;
        }
        .field-table td {
            vertical-align: top;
            padding: 0;
            border: none;
        }
        .field-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            height: 100%;
        }
        .field-label {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .field-value {
            font-size: 12px;
            color: #0f172a;
            font-weight: 700;
            word-break: break-word;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .table-data th, .table-data td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }
        .table-data th {
            background-color: #f8fafc;
            color: #082c50;
            font-weight: 700;
        }
        .table-data tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .footer-signatures {
            margin-top: 24px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 16px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 16px;
        }
        .footer-signatures td {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .sig-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            min-height: 100px;
            background: #fcfcfc;
        }
        .sig-title {
            font-size: 12px;
            font-weight: bold;
            color: #082c50;
            margin-bottom: 8px;
        }
        .stamp-preview {
            max-height: 75px;
            max-width: 120px;
            object-fit: contain;
            margin-top: 4px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .print-card {
                box-shadow: none;
                border: 1.5px solid #082c50;
                padding: 16px;
                max-width: 100%;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
    <div class="action-bar">
        <div class="action-title">
            <span class="action-title-name">{{ $company->trade_name }}</span>
            <span class="action-title-code">{{ $company->company_number ?? ('#' . $company->id) }}</span>
        </div>
        <div class="action-group">
            <button type="button" class="action-btn btn-print" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="btn-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V3.75a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75V7.5m-10.5 0h12m-12 0A2.25 2.25 0 0 0 4.5 9.75v6a2.25 2.25 0 0 0 2.25 2.25h.75m10.5-8.25h.75a2.25 2.25 0 0 1 2.25 2.25v6a2.25 2.25 0 0 1-2.25 2.25h-.75m-12 0h12m-12 0v2.25a.75.75 0 0 0 .75.75h10.5a.75.75 0 0 0 .75-.75V18m-12 0h12" />
                </svg>
                <span>{{ __('organization.companies.actions.print') }}</span>
            </button>
            <button type="button" class="action-btn btn-close" onclick="window.close()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="btn-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                <span>{{ __('organization.companies.actions.close') }}</span>
            </button>
        </div>
    </div>
    @endif

    <div class="print-card">
        <div class="header-top">
            <table class="header-table">
                <tr>
                    <td style="width: 65%;">
                        <div style="display: flex; align-items: center;">
                            @if($company->logo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo) }}" alt="{{ $company->trade_name }}" class="company-logo">
                            @endif
                            <div class="brand-details">
                                <h1>{{ $company->trade_name }}</h1>
                                @if($company->trade_name_en)
                                    <h2>{{ $company->trade_name_en }}</h2>
                                @endif
                                <div style="margin-top: 6px; display: flex; gap: 8px; align-items: center;">
                                    <span class="doc-badge">{{ $company->company_number ?? ('#' . $company->id) }}</span>
                                    @php
                                        $statusVal = $company->status instanceof \BackedEnum ? $company->status->value : (string) ($company->status ?? 'active');
                                    @endphp
                                    @if($statusVal === 'active')
                                        <span class="status-badge-active">{{ __('organization.companies.print.active') }}</span>
                                    @else
                                        <span class="status-badge-inactive">{{ __('organization.companies.print.inactive') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="width: 35%;" class="header-meta">
                        <div><strong>{{ __('organization.companies.print.holding_group') }}</strong></div>
                        <div>{{ __('organization.companies.print.system_name') }}</div>
                        <div style="margin-top: 6px;">{{ __('organization.companies.print.printed_at') }}: {{ $printed_at }}</div>
                        <div>{{ $hijri_date }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">{{ __('organization.companies.print.company_info') }}</div>
        <table class="field-table">
            <tr>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.company_number') }}</div>
                        <div class="field-value"><bdi>{{ $company->company_number ?? ('#' . $company->id) }}</bdi></div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.phone') }}</div>
                        <div class="field-value"><bdi>{{ $company->phone ?? '—' }}</bdi></div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.email') }}</div>
                        <div class="field-value">{{ $company->email ?? '—' }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.address') }}</div>
                        <div class="field-value">{{ $company->address ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.address_en') }}</div>
                        <div class="field-value">{{ $company->address_en ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.postal_code') }}</div>
                        <div class="field-value">{{ $company->postal_code ?? '—' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">{{ __('organization.companies.print.legal_info') }}</div>
        <table class="field-table">
            <tr>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.cr_number') }}</div>
                        <div class="field-value">{{ $company->commercial_registration_number ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.cr_issue_date') }}</div>
                        <div class="field-value">{{ $company->commercial_registration_issue_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.cr_expiry_date') }}</div>
                        <div class="field-value">
                            {{ $company->commercial_registration_expiry?->format('Y-m-d') ?? '—' }}
                            @if($company->commercial_registration_expiry)
                                @php
                                    $days = (int) now()->diffInDays(\Carbon\Carbon::parse($company->commercial_registration_expiry), false);
                                @endphp
                                <span style="font-size: 10px; font-weight: normal; margin-right: 4px; color: {{ $days <= 30 ? '#dc2626' : '#16a34a' }};">
                                    @if($days < 0)
                                        ({{ __('organization.companies.print.days_expired', ['days' => abs($days)]) }})
                                    @elseif($days == 0)
                                        ({{ __('organization.companies.print.expires_today') }})
                                    @else
                                        ({{ __('organization.companies.print.days_remaining', ['days' => $days]) }})
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 50%;" colspan="1">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.municipality_number') }}</div>
                        <div class="field-value">
                            {{ $company->municipality_license_number ?? '—' }}
                            @if($company->municipality_license_expiry)
                                <small style="color: #64748b; font-weight: normal;">({{ $company->municipality_license_expiry->format('Y-m-d') }})</small>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width: 50%;" colspan="2">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.civil_defense_number') }}</div>
                        <div class="field-value">
                            {{ $company->civil_defense_license_number ?? '—' }}
                            @if($company->civil_defense_license_expiry)
                                <small style="color: #64748b; font-weight: normal;">({{ $company->civil_defense_license_expiry->format('Y-m-d') }})</small>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">{{ __('organization.companies.print.gov_subscriptions') }}</div>
        <table class="field-table">
            <tr>
                <td style="width: 25%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.interior_ministry_number') }}</div>
                        <div class="field-value">{{ $company->interior_ministry_number ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.labor_ministry_number') }}</div>
                        <div class="field-value">{{ $company->labor_ministry_number ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.social_insurance_number') }}</div>
                        <div class="field-value">{{ $company->social_insurance_number ?? '—' }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="field-box">
                        <div class="field-label">{{ __('organization.companies.fields.zakat_number') }}</div>
                        <div class="field-value">{{ $company->zakat_number ?? '—' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        @if($company->branches->isNotEmpty())
        <div class="section-title">{{ __('organization.companies.print.branches') }} ({{ $company->branches->count() }})</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 35px;">#</th>
                    <th>{{ __('organization.branches.fields.name') }}</th>
                    <th>{{ __('organization.branches.fields.branch_code') }}</th>
                    <th>{{ __('organization.branches.fields.phone') }}</th>
                    <th>{{ __('organization.branches.fields.address') }}</th>
                    <th>{{ __('organization.branches.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($company->branches as $idx => $branch)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $branch->name }}</strong></td>
                    <td>{{ $branch->branch_code ?? '—' }}</td>
                    <td><bdi>{{ $branch->phone ?? '—' }}</bdi></td>
                    <td>{{ $branch->address ?? '—' }}</td>
                    <td>{{ $branch->status ?? 'active' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($company->owners->isNotEmpty())
        <div class="section-title">{{ __('organization.companies.print.owners') }} ({{ $company->owners->count() }})</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 35px;">#</th>
                    <th>{{ __('owners.fields.name') }}</th>
                    <th>{{ __('owners.fields.national_id') }}</th>
                    <th>{{ __('owners.fields.phone') }}</th>
                    <th>{{ __('owners.fields.ownership_percentage') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($company->owners as $idx => $owner)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $owner->user?->name ?? '—' }}</strong></td>
                    <td>{{ $owner->national_id ?? '—' }}</td>
                    <td><bdi>{{ $owner->user?->phone ?? '—' }}</bdi></td>
                    <td>{{ $owner->ownership_percentage ? $owner->ownership_percentage . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table class="footer-signatures">
            <tr>
                <td>
                    <div class="sig-box">
                        <div class="sig-title">{{ __('organization.companies.print.manager_signature') }}</div>
                        <div style="margin-top: 40px; font-size: 11px; color: #64748b;">
                            {{ __('organization.companies.print.signatures') }}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="sig-box">
                        <div class="sig-title">{{ __('organization.companies.print.official_stamp') }}</div>
                        @if($company->stamp_url)
                            <img src="{{ $company->stamp_url }}" alt="{{ __('organization.companies.print.official_stamp') }}" class="stamp-preview">
                        @else
                            <div style="margin-top: 40px; font-size: 11px; color: #94a3b8;">
                                —
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
