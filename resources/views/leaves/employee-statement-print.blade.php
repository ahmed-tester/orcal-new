<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('leaves.summary.print.statement_title') }} — {{ $employee->user?->name ?? '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding: 20px; direction: rtl; }
        .print-toolbar { max-width: 840px; margin: 0 auto 16px auto; display: flex; justify-content: space-between; align-items: center; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 6px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }

        .sheet-container { background: #ffffff; max-width: 840px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
        .company-info h2 { font-size: 20px; color: #0f172a; margin-bottom: 4px; font-weight: 800; }
        .company-info p { font-size: 13px; color: #64748b; }
        .report-title { text-align: left; }
        .report-title h1 { font-size: 20px; color: #2563eb; font-weight: 800; margin-bottom: 4px; }
        .report-title p { font-size: 12px; color: #64748b; }

        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px 16px; }
        .info-item { display: flex; flex-direction: column; gap: 2px; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 600; }
        .info-value { font-size: 13px; color: #0f172a; font-weight: 700; }

        .balance-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .balance-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
        .balance-box.highlight { background: #f0fdf4; border-color: #86efac; }
        .balance-box-label { font-size: 11px; color: #64748b; margin-bottom: 6px; font-weight: 700; }
        .balance-box-val { font-size: 22px; font-weight: 800; color: #0f172a; }
        .balance-box.highlight .balance-box-val { color: #15803d; }

        .section-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 24px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: start; }
        th { background: #f1f5f9; color: #334155; font-weight: 700; }
        tr:nth-child(even) { background: #fafafa; }
        .text-center { text-align: center; }

        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
        .sig-block { text-align: center; }
        .sig-title { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 45px; }
        .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto 6px auto; }
        .sig-name { font-size: 11px; color: #64748b; }

        @media print {
            body { padding: 0; background: #ffffff; }
            .print-toolbar { display: none; }
            .sheet-container { border: none; padding: 0; box-shadow: none; max-width: 100%; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ {{ __('leaves.actions.print') }}</button>
        <button type="button" onclick="window.close()" class="btn btn-secondary">{{ __('leaves.summary.modal.close') }}</button>
    </div>

    <div class="sheet-container">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <h2>{{ $employee->company?->trade_name ?? config('app.name') }}</h2>
                <p>{{ $employee->branch?->name ? __('leaves.fields.branch') . ': ' . $employee->branch->name : '' }}</p>
            </div>
            <div class="report-title">
                <h1>{{ __('leaves.summary.print.statement_title') }}</h1>
                <p>{{ __('leaves.summary.print.print_date') }} {{ now()->format('Y/m/d H:i') }}</p>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.summary.columns.employee') }}</span>
                    <span class="info-value">{{ $employee->user?->name ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.fields.record_number') }}</span>
                    <span class="info-value">{{ $employee->employee_number ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.summary.columns.national_id') }}</span>
                    <span class="info-value">{{ $employee->details?->national_id ?: $employee->details?->iqama_number ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.summary.columns.job_title') }}</span>
                    <span class="info-value">{{ $employee->jobTitle?->name ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.summary.modal.hire_date') }}</span>
                    <span class="info-value">{{ $details['balance']['hire_date'] ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('leaves.summary.modal.service_duration') }}</span>
                    <span class="info-value">{{ $details['balance']['service_months'] ?? 0 }} {{ __('leaves.summary.modal.months') }}</span>
                </div>
            </div>
        </div>

        {{-- Balance Breakdown --}}
        <div class="section-title">📊 {{ __('leaves.summary.print.balance_breakdown') }}</div>
        <div class="balance-summary">
            <div class="balance-box">
                <div class="balance-box-label">{{ __('leaves.summary.modal.annual_rate_label') }}</div>
                <div class="balance-box-val">{{ $details['balance']['annual_rate'] ?? 21 }} <span style="font-size: 13px;">{{ __('leaves.units.day') }}</span></div>
            </div>
            <div class="balance-box">
                <div class="balance-box-label">{{ __('leaves.summary.modal.accrued_label') }}</div>
                <div class="balance-box-val" style="color: #2563eb;">{{ $details['balance']['accrued_days'] ?? 0 }} <span style="font-size: 13px;">{{ __('leaves.units.day') }}</span></div>
            </div>
            <div class="balance-box">
                <div class="balance-box-label">{{ __('leaves.summary.modal.used_label') }}</div>
                <div class="balance-box-val" style="color: #dc2626;">{{ $details['balance']['used_days'] ?? 0 }} <span style="font-size: 13px;">{{ __('leaves.units.day') }}</span></div>
            </div>
            <div class="balance-box highlight">
                <div class="balance-box-label" style="color: #166534;">{{ __('leaves.summary.modal.available_label') }}</div>
                <div class="balance-box-val">{{ $details['balance']['available_days'] ?? 0 }} <span style="font-size: 13px;">{{ __('leaves.units.day') }}</span></div>
            </div>
        </div>

        {{-- Leaves History Table --}}
        <div class="section-title">📋 {{ __('leaves.summary.print.leaves_history') }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th>{{ __('leaves.fields.leave_type') }}</th>
                    <th class="text-center">{{ __('leaves.fields.start_date') }}</th>
                    <th class="text-center">{{ __('leaves.fields.end_date') }}</th>
                    <th class="text-center">{{ __('leaves.fields.days') }}</th>
                    <th class="text-center">{{ __('leaves.fields.status') }}</th>
                    <th>{{ __('leaves.fields.return_status') }}</th>
                    <th>{{ __('leaves.fields.reason') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details['leaves'] as $leave)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td><strong>{{ $leave->leaveType?->name ?? '—' }}</strong></td>
                        <td class="text-center">
                            <div>{{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('Y/m/d') : '—' }}</div>
                            <div style="font-size: 10px; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($leave->start_date) }}</div>
                        </td>
                        <td class="text-center">
                            <div>{{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('Y/m/d') : '—' }}</div>
                            <div style="font-size: 10px; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($leave->end_date) }}</div>
                        </td>
                        <td class="text-center"><strong>{{ $leave->days }}</strong></td>
                        <td class="text-center">
                            {{ $leave->status?->getLabel() ?? $leave->status }}
                        </td>
                        <td>{{ $leave->return_status_label }}</td>
                        <td>{{ $leave->reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">
                            {{ __('leaves.summary.modal.no_leaves') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-title">{{ __('leaves.summary.print.signatures.employee') }}</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $employee->user?->name ?? '' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-title">{{ __('leaves.summary.print.signatures.hr_manager') }}</div>
                <div class="sig-line"></div>
                <div class="sig-name">................................................</div>
            </div>
            <div class="sig-block">
                <div class="sig-title">{{ __('leaves.summary.print.signatures.general_manager') }}</div>
                <div class="sig-line"></div>
                <div class="sig-name">................................................</div>
            </div>
        </div>
    </div>
</body>
</html>

