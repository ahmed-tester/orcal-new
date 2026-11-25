<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('attendance.period_report.title') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .toolbar, .sheet { max-width: 1200px; margin: 0 auto; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 16px; }
        button { border: 0; border-radius: 6px; padding: 10px 18px; background: #2563eb; color: #fff; font: inherit; cursor: pointer; }
        .sheet { padding: 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        h1 { margin: 0 0 12px; font-size: 20px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-bottom: 12px; color: #475569; font-size: 12px; }
        .notice { margin-bottom: 16px; padding: 10px 12px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; font-size: 11px; color: #78350f; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 6px; text-align: start; overflow-wrap: anywhere; }
        th { background: #e2e8f0; font-weight: 700; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .numeric { text-align: center; font-variant-numeric: tabular-nums; }
        .provisional { font-size: 10px; color: #92400e; display: block; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .sheet { max-width: none; border: 0; border-radius: 0; padding: 0; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $rows = $report['rows'];
        $totals = $report['totals'];
        $canViewFinancial = $report['can_view_financial'];
        $hasActivePolicy = (bool) ($report['has_active_policy'] ?? false);
    @endphp
    <div class="toolbar">
        <button type="button" onclick="window.print()">{{ __('attendance.summary.print.print_action') }}</button>
        <button type="button" onclick="window.close()">{{ __('attendance.summary.modal.close') }}</button>
    </div>
    <main class="sheet">
        <h1>{{ __('attendance.period_report.title') }}</h1>
        <div class="meta">
            <span>{{ __('attendance.period_report.filters.date_from') }}: <bdi>{{ $report['date_from'] }}</bdi></span>
            <span>{{ __('attendance.period_report.filters.date_to') }}: <bdi>{{ $report['date_to'] }}</bdi></span>
            <span>{{ __('attendance.summary.stats.employees') }}: {{ $totals['employees_count'] }}</span>
            <span>{{ __('attendance.summary.print.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
        <div class="notice">
            <div>{{ $hasActivePolicy ? __('attendance.period_report.active_policy_notice') : __('attendance.period_report.pending_net_notice') }}</div>
            <div>{{ __('attendance.period_report.overtime_source_note') }}</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('attendance.period_report.columns.employee_number') }}</th>
                    <th>{{ __('attendance.period_report.columns.employee_name') }}</th>
                    <th>{{ __('attendance.period_report.columns.company') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.recorded_days') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.present_days') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.late_days') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.absent_days') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.late_minutes') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.overtime_minutes') }}</th>
                    <th class="numeric">{{ __('attendance.period_report.columns.total_hours') }}</th>
                    @if ($canViewFinancial)
                        <th class="numeric">{{ __('attendance.period_report.columns.reference_total_salary') }}</th>
                        <th class="numeric">{{ __('attendance.period_report.columns.overtime_hourly_rate') }}</th>
                        <th class="numeric">{{ __('attendance.period_report.columns.overtime_cost_estimate') }}</th>
                        @if ($hasActivePolicy)
                            <th class="numeric">{{ __('attendance.period_report.columns.late_deduction') }}</th>
                            <th class="numeric">{{ __('attendance.period_report.columns.compensation_minutes_used') }}</th>
                            <th class="numeric">{{ __('attendance.period_report.columns.compensation_amount') }}</th>
                            <th class="numeric">{{ __('attendance.period_report.columns.remaining_late_deduction') }}</th>
                        @endif
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['employee_number'] }}</td>
                        <td>{{ $row['employee_name'] }}</td>
                        <td>{{ $row['company_name'] }} / {{ $row['branch_name'] }}</td>
                        <td class="numeric">{{ $row['recorded_days'] }}</td>
                        <td class="numeric">{{ $row['present_days'] }}</td>
                        <td class="numeric">{{ $row['late_days'] }}</td>
                        <td class="numeric">{{ $row['absent_days'] }}</td>
                        <td class="numeric">{{ $row['late_minutes'] }}</td>
                        <td class="numeric">{{ $row['overtime_minutes'] }}</td>
                        <td class="numeric">{{ number_format((float) $row['total_hours'], 2) }}</td>
                        @if ($canViewFinancial)
                            @php $ref = $row['salary_reference']; @endphp
                            <td class="numeric">{{ $ref ? number_format((float) $ref['total_salary'], 2) : '—' }}</td>
                            <td class="numeric">
                                {{ ($ref && $ref['overtime_hourly_rate'] !== null) ? number_format((float) $ref['overtime_hourly_rate'], 2) : __('employees.salary_profile.placeholders.unspecified') }}
                            </td>
                            <td class="numeric">
                                @if ($row['overtime_cost_estimate'] !== null)
                                    {{ number_format((float) $row['overtime_cost_estimate'], 2) }}
                                    <span class="provisional">{{ __('attendance.period_report.provisional_estimate_badge') }}</span>
                                @else
                                    {{ __('attendance.period_report.pending_calculation') }}
                                @endif
                            </td>
                            @if ($hasActivePolicy)
                                @php $pol = $row['policy_summary'] ?? null; @endphp
                                <td class="numeric">{{ ($pol && $pol['active']) ? number_format((float) $pol['late_deduction_amount'], 2) : '—' }}</td>
                                <td class="numeric">{{ ($pol && $pol['active']) ? number_format((float) $pol['compensation_minutes_used'], 2) : '—' }}</td>
                                <td class="numeric">{{ ($pol && $pol['active']) ? number_format((float) $pol['compensation_amount'], 2) : '—' }}</td>
                                <td class="numeric">{{ ($pol && $pol['active']) ? number_format((float) $pol['remaining_late_deduction'], 2) : '—' }}</td>
                            @endif
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canViewFinancial ? ($hasActivePolicy ? 17 : 13) : 10 }}" class="numeric">{{ __('attendance.table.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <th colspan="3">{{ __('attendance.summary.stats.employees') }}: {{ $totals['employees_count'] }}</th>
                        <th class="numeric">{{ $totals['recorded_days'] }}</th>
                        <th class="numeric">{{ $totals['present_days'] }}</th>
                        <th class="numeric">{{ $totals['late_days'] }}</th>
                        <th class="numeric">{{ $totals['absent_days'] }}</th>
                        <th class="numeric">{{ $totals['late_minutes'] }}</th>
                        <th class="numeric">{{ $totals['overtime_minutes'] }}</th>
                        <th class="numeric">{{ number_format((float) $totals['total_hours'], 2) }}</th>
                        @if ($canViewFinancial)
                            <th class="numeric">—</th>
                            <th class="numeric">—</th>
                            <th class="numeric">
                                @if ($totals['provisional_overtime_cost_total'] !== null)
                                    {{ number_format((float) $totals['provisional_overtime_cost_total'], 2) }}
                                    <span class="provisional">{{ __('attendance.period_report.provisional_estimate_badge') }}</span>
                                @else
                                    {{ __('attendance.period_report.pending_calculation') }}
                                @endif
                            </th>
                            @if ($hasActivePolicy)
                                <th class="numeric">{{ number_format((float) ($totals['late_deduction_amount'] ?? 0), 2) }}</th>
                                <th class="numeric">{{ number_format((float) ($totals['compensation_minutes_used'] ?? 0), 2) }}</th>
                                <th class="numeric">{{ number_format((float) ($totals['compensation_amount'] ?? 0), 2) }}</th>
                                <th class="numeric">{{ number_format((float) ($totals['remaining_late_deduction'] ?? 0), 2) }}</th>
                            @endif
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    </main>
</body>
</html>
