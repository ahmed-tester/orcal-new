<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
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
            padding: 16px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
        }
        .print-toolbar {
            max-width: 1200px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary { background-color: #082c50; color: #ffffff; }
        .btn-primary:hover { background-color: #0b3d6f; }
        .btn-pdf { background-color: #0284c7; color: #ffffff; }
        .btn-pdf:hover { background-color: #0369a1; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }
        .tab-btn {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .tab-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .tab-btn.active {
            background: #082c50;
            color: #ffffff;
            border-color: #082c50;
        }
        .report-card {
            background: #ffffff;
            max-width: 1200px;
            margin: 0 auto;
            border: 1.5px solid #082c50;
            border-radius: 8px;
            padding: 20px 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #082c50;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 800;
            color: #082c50;
            text-align: center;
        }
        .header-subtitle {
            font-size: 11px;
            color: #64748b;
            text-align: center;
            margin-top: 4px;
        }
        .meta-text {
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }
        .stat-val {
            font-size: 16px;
            font-weight: 800;
            color: #082c50;
        }
        .stat-label {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 8px;
        }
        .table-data th, .table-data td {
            border: 1px solid #cbd5e1;
            padding: 7px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .table-data th {
            background-color: #082c50;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
        }
        .table-data tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .text-start { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }} !important; }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            margin-top: 16px;
            font-size: 10px;
            color: #64748b;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-toolbar {
                display: none !important;
            }
            .report-card {
                box-shadow: none;
                border: 1px solid #082c50;
                padding: 8px;
                max-width: 100%;
            }
            thead {
                display: table-header-group;
            }
            tr {
                break-inside: avoid;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div class="toolbar-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 17px; height: 17px; flex-shrink: 0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V3.75a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75V7.5m-10.5 0h12m-12 0A2.25 2.25 0 0 0 4.5 9.75v6a2.25 2.25 0 0 0 2.25 2.25h.75m10.5-8.25h.75a2.25 2.25 0 0 1 2.25 2.25v6a2.25 2.25 0 0 1-2.25 2.25h-.75m-12 0h12m-12 0v2.25a.75.75 0 0 0 .75.75h10.5a.75.75 0 0 0 .75-.75V18m-12 0h12" />
                </svg>
                <span>{{ __('organization.companies.actions.print') }}</span>
            </button>
            <a href="{{ request()->fullUrlWithQuery(['export_mode' => 'pdf']) }}" class="btn btn-pdf">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 17px; height: 17px; flex-shrink: 0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>{{ __('organization.companies.actions.download_pdf') }}</span>
            </a>
            <div style="height: 20px; width: 1px; background: #cbd5e1; margin: 0 4px;"></div>
            <a href="{{ route('exports.companies-statement') }}" class="tab-btn {{ ($status === 'all' && !($isExpiringSoon ?? false)) ? 'active' : '' }}">
                {{ __('organization.companies.print.filter_all') }}
            </a>
            <a href="{{ route('exports.companies-statement', ['status' => 'active']) }}" class="tab-btn {{ $status === 'active' ? 'active' : '' }}">
                {{ __('organization.companies.print.filter_active') }}
            </a>
            <a href="{{ route('exports.companies-statement', ['status' => 'inactive']) }}" class="tab-btn {{ $status === 'inactive' ? 'active' : '' }}">
                {{ __('organization.companies.print.inactive_companies') }}
            </a>
            <a href="{{ route('exports.companies-statement', ['expiring_soon' => 1]) }}" class="tab-btn {{ ($isExpiringSoon ?? false) ? 'active' : '' }}">
                {{ __('organization.companies.print.filter_expiring') }}
            </a>
        </div>
        <button type="button" class="btn btn-secondary" onclick="window.close()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 16px; height: 16px; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            <span>{{ __('organization.companies.actions.close') }}</span>
        </button>
    </div>

    <div class="report-card">
        <table class="header-table">
            <tr>
                <td style="width: 30%; vertical-align: top;" class="meta-text text-start">
                    <strong>{{ __('organization.companies.print.holding_group') }}</strong><br>
                    <span>{{ __('organization.companies.print.system_name') }}</span>
                </td>
                <td style="width: 40%; text-align: center; vertical-align: middle;">
                    <div class="header-title">{{ $title }}</div>
                    <div class="header-subtitle">{{ __('organization.companies.print.statement_subtitle') }}</div>
                </td>
                <td style="width: 30%; vertical-align: top; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};" class="meta-text">
                    <div>{{ __('organization.companies.print.printed_at') }}: {{ $date_label }}</div>
                    <div>{{ $hijri_date }}</div>
                    <div>{{ __('organization.companies.print.printed_by') }}: {{ $printed_by }}</div>
                </td>
            </tr>
        </table>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val">{{ number_format($stats['total']) }}</div>
                <div class="stat-label">{{ __('organization.companies.print.total_companies') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #0284c7;">{{ number_format($stats['branches']) }}</div>
                <div class="stat-label">{{ __('organization.companies.print.total_branches') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #475569;">{{ number_format($stats['employees']) }}</div>
                <div class="stat-label">{{ __('organization.companies.print.total_employees') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #16a34a;">{{ number_format($stats['active']) }}</div>
                <div class="stat-label">{{ __('organization.companies.print.active_companies') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: {{ $stats['expiring'] > 0 ? '#dc2626' : '#64748b' }};">{{ number_format($stats['expiring']) }}</div>
                <div class="stat-label">{{ __('organization.companies.print.expiring_crs') }}</div>
            </div>
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 35px;">{{ __('organization.companies.print.col_index') }}</th>
                    <th class="text-start" style="width: 220px;">{{ __('organization.companies.print.col_company') }}</th>
                    <th style="width: 110px;">{{ __('organization.companies.print.col_code') }}</th>
                    <th>{{ __('organization.companies.print.col_cr') }}</th>
                    <th>{{ __('organization.companies.print.col_contact') }}</th>
                    <th style="width: 70px;">{{ __('organization.companies.print.col_branches') }}</th>
                    <th style="width: 70px;">{{ __('organization.companies.print.col_employees') }}</th>
                    <th style="width: 80px;">{{ __('organization.companies.print.col_status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $idx => $company)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="text-start">
                        <strong>{{ $company->trade_name }}</strong>
                        @if($company->trade_name_en)
                            <br><small style="color: #64748b;">{{ $company->trade_name_en }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-gray">{{ $company->company_number ?? ('#' . $company->id) }}</span>
                    </td>
                    <td>
                        {{ $company->commercial_registration_number ?? '—' }}
                        @if($company->commercial_registration_expiry)
                            @php
                                $days = (int) now()->diffInDays(\Carbon\Carbon::parse($company->commercial_registration_expiry), false);
                                $badgeClass = match(true) {
                                    $days < 0 || $days <= 30 => 'badge-danger',
                                    $days <= 90 => 'badge-warning',
                                    default => 'badge-success',
                                };
                            @endphp
                            <br><span class="badge {{ $badgeClass }}" style="font-size: 8.5px;">{{ $company->commercial_registration_expiry->format('Y-m-d') }}</span>
                        @endif
                    </td>
                    <td style="font-size: 10px;">
                        @if($company->phone)
                            <div><bdi>{{ $company->phone }}</bdi></div>
                        @endif
                        @if($company->email)
                            <div style="color: #64748b;">{{ $company->email }}</div>
                        @endif
                        @if(!$company->phone && !$company->email)
                            —
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $company->branches_count ?? 0 }}</span>
                    </td>
                    <td>
                        <span class="badge badge-gray">{{ $company->employees_count ?? 0 }}</span>
                    </td>
                    <td>
                        @php
                            $statusVal = $company->status instanceof \BackedEnum ? $company->status->value : (string) ($company->status ?? 'active');
                        @endphp
                        @if($statusVal === 'active')
                            <span class="badge badge-success">{{ __('organization.companies.print.active') }}</span>
                        @else
                            <span class="badge badge-danger">{{ __('organization.companies.print.inactive') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding: 24px; color: #94a3b8;">
                        {{ __('organization.companies.print.empty_records') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <table class="footer-table">
            <tr>
                <td class="text-start">
                    {{ __('organization.companies.print.total_companies') }}: {{ number_format($companies->count()) }}
                </td>
                <td style="text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">
                    {{ __('organization.companies.print.system_name') }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
