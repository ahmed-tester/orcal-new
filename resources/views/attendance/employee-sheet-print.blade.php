<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('attendance.summary.print.title') }} — {{ $employee->user?->name ?? 'موظف' }} — {{ $details['month_label'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            padding: 20px;
            direction: rtl;
        }
        .print-toolbar {
            max-width: 840px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }

        .sheet-container {
            background: #ffffff;
            max-width: 840px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-meta h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .company-meta p {
            font-size: 13px;
            color: #475569;
        }
        .sheet-title {
            text-align: center;
        }
        .sheet-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 4px;
        }
        .sheet-title .period {
            font-size: 13px;
            font-weight: 700;
            color: #0369a1;
            background: #e0f2fe;
            padding: 3px 10px;
            border-radius: 9999px;
            display: inline-block;
        }
        .employee-card {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .info-cell label {
            display: block;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-cell span {
            font-weight: 800;
            color: #0f172a;
        }
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .kpi-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            background: #ffffff;
        }
        .kpi-box .val {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .kpi-box .lbl {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: center;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
        }
        .data-table tbody tr.weekend {
            background-color: #f8fafc;
        }
        .data-table tfoot tr {
            background-color: #f0fdf4;
            font-weight: 800;
        }
        .data-table tfoot td {
            color: #166534;
        }

        .status-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        .status-present { background: #dcfce7; color: #166534; }
        .status-late { background: #fef3c7; color: #92400e; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        .status-leave { background: #ede9fe; color: #5b21b6; }
        .status-unrecorded { background: #f1f5f9; color: #64748b; }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
        }
        .sign-box {
            text-align: center;
        }
        .sign-box h4 {
            font-size: 13px;
            font-weight: 800;
            color: #334155;
            margin-bottom: 40px;
        }
        .sign-line {
            width: 180px;
            margin: 0 auto;
            border-top: 1px solid #94a3b8;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .print-toolbar { display: none !important; }
            .sheet-container { border: none; box-shadow: none; padding: 0; }
            .data-table th { background-color: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
            .status-badge { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()" class="btn btn-primary">
            🖨️ {{ __('attendance.summary.modal.print_sheet') }}
        </button>
        <button type="button" onclick="window.close()" class="btn btn-secondary">
            ✕ {{ __('attendance.summary.modal.close') }}
        </button>
    </div>

    <div class="sheet-container">
        {{-- Header --}}
        <div class="header">
            <div class="company-meta">
                <h2>{{ $employee->company?->trade_name ?? config('app.name') }}</h2>
                <p>{{ __('attendance.summary.columns.branch') }}: {{ $employee->branch?->name ?? '—' }}</p>
                <p>{{ __('attendance.summary.print.print_date') }}: {{ now()->format('Y/m/d H:i') }}</p>
            </div>
            <div class="sheet-title">
                <h1>{{ __('attendance.summary.print.title') }}</h1>
                <div class="period">{{ $details['month_label'] }} ({{ $details['month_hijri'] }})</div>
            </div>
            <div style="width: 100px; text-align: left;">
                @if($employee->company?->logo)
                    <img src="{{ asset('storage/' . $employee->company->logo) }}" alt="Logo" style="max-height: 50px; max-width: 100px;">
                @endif
            </div>
        </div>

        {{-- Employee Details Card --}}
        <div class="employee-card">
            <div class="info-cell">
                <label>{{ __('attendance.summary.print.employee_name') }}</label>
                <span>{{ $employee->user?->name ?? '—' }}</span>
            </div>
            <div class="info-cell">
                <label>{{ __('attendance.summary.print.employee_number') }}</label>
                <span style="font-family: monospace;">{{ $employee->employee_number ?? "ID: {$employee->id}" }}</span>
            </div>
            <div class="info-cell">
                <label>{{ __('attendance.summary.print.national_id') }}</label>
                <span style="font-family: monospace;">{{ $employee->details?->national_id ?: $employee->details?->iqama_number ?: '—' }}</span>
            </div>
            <div class="info-cell">
                <label>{{ __('attendance.summary.columns.job_title') }}</label>
                <span>{{ $employee->jobTitle?->name ?? '—' }}</span>
            </div>
        </div>

        {{-- KPI Strip --}}
        @php $kpis = $details['stats']; @endphp
        <div class="kpi-strip">
            <div class="kpi-box">
                <div class="val" style="color: #059669;">{{ (int) $kpis['present_days'] }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.present') }}</div>
            </div>
            <div class="kpi-box">
                <div class="val" style="color: #d97706;">{{ (int) $kpis['late_days'] }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.late') }}</div>
            </div>
            <div class="kpi-box">
                <div class="val" style="color: #dc2626;">{{ (int) $kpis['absent_days'] }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.absent') }}</div>
            </div>
            <div class="kpi-box">
                <div class="val" style="color: #7c3aed;">{{ (int) $kpis['leave_days'] }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.leave') }}</div>
            </div>
            <div class="kpi-box">
                <div class="val" style="color: #ea580c;">{{ (int) $kpis['late_minutes'] }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.late_total') }}</div>
            </div>
            <div class="kpi-box">
                <div class="val" style="color: #0f172a;">{{ number_format((float) $kpis['total_hours'], 1) }}</div>
                <div class="lbl">{{ __('attendance.summary.modal.stats.hours_total') }}</div>
            </div>
        </div>

        {{-- Attendance Days Table --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 70px;">{{ __('attendance.summary.modal.day') }}</th>
                    <th style="width: 80px;">{{ __('attendance.summary.modal.date') }}</th>
                    <th style="width: 80px;">{{ __('attendance.summary.modal.hijri') }}</th>
                    <th style="width: 60px;">{{ __('attendance.summary.modal.check_in') }}</th>
                    <th style="width: 60px;">{{ __('attendance.summary.modal.check_out') }}</th>
                    <th style="width: 50px;">{{ __('attendance.summary.modal.late_mins') }}</th>
                    <th style="width: 50px;">{{ __('attendance.summary.modal.overtime_mins') }}</th>
                    <th style="width: 50px;">{{ __('attendance.summary.modal.hours') }}</th>
                    <th style="width: 85px;">{{ __('attendance.summary.modal.status') }}</th>
                    <th>{{ __('attendance.summary.modal.notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details['days'] as $day)
                    <tr class="{{ $day['is_weekend'] ? 'weekend' : '' }}">
                        <td style="font-weight: 700;">{{ $day['day_number'] }}</td>
                        <td>{{ $day['day_name'] }}</td>
                        <td style="font-family: monospace;">{{ $day['date'] }}</td>
                        <td style="font-family: monospace; color: #64748b;">{{ $day['hijri_date'] }}</td>
                        <td style="font-family: monospace; font-weight: 700;">{{ $day['check_in'] ?? '—' }}</td>
                        <td style="font-family: monospace; font-weight: 700;">{{ $day['check_out'] ?? '—' }}</td>
                        <td style="font-family: monospace; {{ $day['late_minutes'] > 0 ? 'color: #ea580c; font-weight: 700;' : '' }}">
                            {{ $day['late_minutes'] > 0 ? $day['late_minutes'] : '0' }}
                        </td>
                        <td style="font-family: monospace; {{ $day['overtime_minutes'] > 0 ? 'color: #059669; font-weight: 700;' : '' }}">
                            {{ $day['overtime_minutes'] > 0 ? $day['overtime_minutes'] : '0' }}
                        </td>
                        <td style="font-family: monospace; font-weight: 700;">
                            {{ $day['total_hours'] > 0 ? number_format($day['total_hours'], 1) : '—' }}
                        </td>
                        <td>
                            @if($day['has_record'] && $day['status'])
                                @php
                                    $badgeClass = match($day['status']->value ?? (string) $day['status']) {
                                        'present' => 'status-present',
                                        'late' => 'status-late',
                                        'absent' => 'status-absent',
                                        'leave', 'excused' => 'status-leave',
                                        default => 'status-unrecorded',
                                    };
                                @endphp
                                <span class="status-badge {{ $badgeClass }}">{{ $day['status_label'] }}</span>
                            @else
                                <span class="status-badge status-unrecorded">{{ $day['status_label'] }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-size: 10px; color: #64748b;">{{ $day['notes'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: end;">{{ __('attendance.summary.columns.totals') }}:</td>
                    <td colspan="2"></td>
                    <td>{{ (int) $kpis['late_minutes'] }}</td>
                    <td>{{ (int) $kpis['overtime_minutes'] }}</td>
                    <td>{{ number_format((float) $kpis['total_hours'], 1) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sign-box">
                <h4>{{ __('attendance.summary.print.signature_employee') }}</h4>
                <div class="sign-line"></div>
            </div>
            <div class="sign-box">
                <h4>{{ __('attendance.summary.print.signature_manager') }}</h4>
                <div class="sign-line"></div>
            </div>
        </div>
    </div>
</body>
</html>

