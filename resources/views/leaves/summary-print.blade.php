<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('leaves.summary.print.summary_title') }} ({{ $year ?? __('leaves.summary.filters.all_years') }})</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .toolbar, .sheet { max-width: 1200px; margin: 0 auto; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 16px; }
        button { border: 0; border-radius: 6px; padding: 10px 18px; background: #2563eb; color: #fff; font: inherit; cursor: pointer; }
        .sheet { padding: 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        h1 { margin: 0 0 12px; font-size: 22px; color: #0f172a; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-bottom: 20px; color: #475569; font-size: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 6px; text-align: start; overflow-wrap: anywhere; }
        th { background: #e2e8f0; font-weight: 700; color: #1e293b; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .numeric { text-align: center; font-variant-numeric: tabular-nums; }
        .empty { text-align: center; padding: 24px; color: #94a3b8; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .sheet { max-width: none; border: 0; border-radius: 0; padding: 0; }
            .table-wrap { overflow: visible; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">{{ __('leaves.summary.print.summary_action') }}</button>
        <button type="button" onclick="window.close()">{{ __('leaves.summary.modal.close') }}</button>
    </div>
    <main class="sheet">
        <h1>{{ __('leaves.summary.print.summary_title') }} — {{ $year ?? __('leaves.summary.filters.all_years') }}</h1>
        <div class="meta">
            <span>{{ __('leaves.summary.cards.total_employees') }}: {{ number_format($stats['employees_count']) }}</span>
            <span>{{ __('leaves.summary.cards.on_leave_today') }}: {{ number_format($stats['on_leave_today_count']) }}</span>
            <span>{{ __('leaves.summary.cards.pending_requests') }}: {{ number_format($stats['pending_requests_count']) }}</span>
            <span>{{ __('leaves.summary.cards.total_used_days') }}: {{ number_format($stats['total_approved_days']) }} {{ __('leaves.units.day') }}</span>
            <span>{{ __('leaves.summary.cards.total_available_days') }}: {{ number_format($stats['total_available_annual_days']) }} {{ __('leaves.units.day') }}</span>
            <span>{{ __('leaves.summary.print.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col" style="width: 35px;">#</th>
                        <th scope="col">{{ __('leaves.summary.columns.employee') }}</th>
                        <th scope="col">{{ __('leaves.summary.columns.company') }}</th>
                        <th scope="col">{{ __('leaves.summary.columns.branch') }}</th>
                        <th scope="col">{{ __('leaves.summary.columns.job_title') }}</th>
                        <th scope="col" class="numeric">{{ __('leaves.summary.columns.annual_rate') }}</th>
                        <th scope="col" class="numeric">{{ __('leaves.summary.columns.accrued_days') }}</th>
                        <th scope="col" class="numeric">{{ __('leaves.summary.columns.historical_used_days') }}</th>
                        <th scope="col" class="numeric" style="background: #dcfce7; color: #166534;">{{ __('leaves.summary.columns.available_days') }}</th>
                        <th scope="col" class="numeric">{{ __('leaves.summary.columns.year_used_days') }}</th>
                        <th scope="col" class="numeric">{{ __('leaves.summary.columns.pending_leaves') }}</th>
                        <th scope="col">{{ __('leaves.summary.columns.status_today') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $balance = \App\Services\LeaveSummaryService::computeEmployeeBalance($row, (float) ($row->historical_annual_used_days ?? 0));
                            $isOnLeave = (int) ($row->is_on_leave_today ?? 0) === 1;
                        @endphp
                        <tr>
                            <td class="numeric">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $row->user?->name ?? '—' }}</strong><br>
                                <span style="font-size: 10px; color: #64748b;">{{ $row->employee_number ?? '' }}</span>
                            </td>
                            <td>{{ $row->company?->trade_name ?? '—' }}</td>
                            <td>{{ $row->branch?->name ?? '—' }}</td>
                            <td>{{ $row->jobTitle?->name ?? '—' }}</td>
                            <td class="numeric">{{ $balance['annual_rate'] }}</td>
                            <td class="numeric">{{ $balance['accrued_days'] }}</td>
                            <td class="numeric" style="color: #dc2626;">{{ $balance['used_days'] }}</td>
                            <td class="numeric" style="font-weight: 800; color: {{ $balance['available_days'] > 0 ? '#15803d' : '#991b1b' }};">
                                {{ $balance['available_days'] }}
                            </td>
                            <td class="numeric">{{ (int) ($row->total_approved_days ?? 0) }}</td>
                            <td class="numeric">{{ (int) ($row->pending_leaves_count ?? 0) }}</td>
                            <td>
                                @if($isOnLeave)
                                    <span style="color: #b45309; font-weight: 700;">{{ __('leaves.summary.status.on_leave') }}</span>
                                @else
                                    <span style="color: #166534;">{{ __('leaves.summary.status.working') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="empty">{{ __('leaves.table.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

