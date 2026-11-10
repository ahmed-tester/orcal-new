<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('attendance.summary.title') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .toolbar, .sheet { max-width: 1200px; margin: 0 auto; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 16px; }
        button { border: 0; border-radius: 6px; padding: 10px 18px; background: #2563eb; color: #fff; font: inherit; cursor: pointer; }
        .sheet { padding: 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        h1 { margin: 0 0 12px; font-size: 22px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-bottom: 20px; color: #475569; font-size: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 6px; text-align: start; overflow-wrap: anywhere; }
        th { background: #e2e8f0; font-weight: 700; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .numeric { text-align: center; font-variant-numeric: tabular-nums; }
        .empty { text-align: center; padding: 24px; }
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
        <button type="button" onclick="window.print()">{{ __('attendance.summary.print.print_action') }}</button>
        <button type="button" onclick="window.close()">{{ __('attendance.summary.modal.close') }}</button>
    </div>
    <main class="sheet">
        <h1>{{ __('attendance.summary.title') }}</h1>
        <div class="meta">
            <span>{{ __('attendance.summary.filters.month') }}: <bdi>{{ $dateRange[0] }} — {{ $dateRange[1] }}</bdi></span>
            <span>{{ __('attendance.summary.stats.employees') }}: {{ $rows->count() }}</span>
            <span>{{ __('attendance.summary.print.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        @foreach(['number', 'employee', 'company', 'branch', 'recorded_days', 'present_days', 'late_days', 'absent_days', 'leave_days', 'late_minutes', 'total_hours', 'last_date'] as $column)
                            <th scope="col">{{ __('attendance.summary.columns.'.$column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="numeric">{{ $loop->iteration }}</td>
                            <td>{{ $row->user?->name ?? '—' }}<br><bdi>{{ $row->employee_number }}</bdi></td>
                            <td>{{ $row->company?->trade_name ?? '—' }}</td>
                            <td>{{ $row->branch?->name ?? '—' }}</td>
                            @foreach(['recorded_days', 'present_days', 'late_days', 'absent_days', 'leave_days', 'late_minutes'] as $metric)
                                <td class="numeric">{{ number_format((float) $row->{$metric}) }}</td>
                            @endforeach
                            <td class="numeric">{{ number_format((float) $row->total_hours, 2) }}</td>
                            <td><bdi>{{ $row->last_date ? \Carbon\Carbon::parse($row->last_date)->format('Y-m-d') : '—' }}</bdi></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="empty">{{ __('attendance.summary.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
