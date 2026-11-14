<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('finance.daily_journals.summary.'.($details ? 'statement' : 'title')) }}</title>
    <style>
        @page {
            size: A4 landscape;
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
            max-width: 1200px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .toolbar-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }

        .sheet-container {
            background: #ffffff;
            max-width: 1200px;
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
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .company-meta h2 {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .company-meta p {
            font-size: 11.5px;
            color: #64748b;
        }
        .sheet-title {
            text-align: center;
        }
        .sheet-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 2px;
        }
        .sheet-title .subtitle {
            font-size: 11.5px;
            font-weight: 600;
            color: #64748b;
        }
        .date-meta {
            text-align: left;
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.5;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .kpi-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            background: #f8fafc;
        }
        .kpi-box .val {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            font-variant-numeric: tabular-nums;
        }
        .kpi-box .lbl {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .table-wrap {
            overflow-x: auto;
            margin-bottom: 12px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: center;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.data-table tfoot tr {
            background-color: #f0fdf4;
            font-weight: 800;
        }
        table.data-table tfoot td, table.data-table tfoot th {
            color: #166534;
            font-weight: 800;
            border: 1px solid #cbd5e1;
            padding: 8px;
        }

        .numeric {
            font-variant-numeric: tabular-nums;
            text-align: center;
        }
        .empty {
            text-align: center;
            padding: 24px;
            color: #94a3b8;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 700;
        }
        .badge-detailed { background: #eff6ff; color: #1d4ed8; }
        .badge-summary { background: #faf5ff; color: #7e22ce; }

        .branch-card {
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
        .branch-cell label {
            display: block;
            color: #64748b;
            font-weight: 600;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .branch-cell span {
            font-weight: 800;
            color: #0f172a;
        }

        .section-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #1e3a8a;
            margin: 16px 0 8px 0;
        }

        @media print {
            body { padding: 0; background: #ffffff; }
            .print-toolbar { display: none !important; }
            .sheet-container { max-width: none; border: 0; border-radius: 0; padding: 0; box-shadow: none; }
            .table-wrap { overflow: visible; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $companyName = $details
            ? ($details['journal']->company?->trade_name ?? auth()->user()?->company?->trade_name)
            : ($rows?->first()?->company?->trade_name ?? auth()->user()?->company?->trade_name);
    @endphp

    <div class="print-toolbar">
        <div class="toolbar-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">{{ __('finance.daily_journals.summary.print') }}</button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">{{ __('finance.daily_journals.summary.close') }}</button>
        </div>
        <div class="date-meta">
            <span>{{ __('finance.daily_journals.summary.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
    </div>

    <main class="sheet-container">
        <div class="header">
            <div class="company-meta">
                <h2>{{ $companyName ?? __('finance.daily_journals.summary.company') }}</h2>
                <p>{{ __('finance.daily_journals.plural') }}</p>
            </div>
            <div class="sheet-title">
                <h1>{{ __('finance.daily_journals.summary.'.($details ? 'statement' : 'title')) }}</h1>
                @if($details)
                    <div class="subtitle">{{ $details['journal']->branch?->name ?? __('finance.daily_journals.summary.no_branch') }}</div>
                @endif
            </div>
            <div class="date-meta">
                <div><span>{{ __('finance.daily_journals.summary.print_date') }}:</span> <bdi>{{ now()->format('Y-m-d') }}</bdi></div>
                <div><span>{{ __('finance.daily_journals.summary.entries') }}:</span> <bdi>{{ $details ? count($details['entries']) : ($rows ? $rows->sum('entries_count') : 0) }}</bdi></div>
            </div>
        </div>

        @if($details)
            @php
                $journal = $details['journal'];
                $entries = $details['entries'];
                $totalSales = $entries->sum('sales');
                $totalPurchases = $entries->sum('purchases');
                $totalNet = $entries->sum('net_amount');
            @endphp

            <div class="branch-card">
                <div class="branch-cell">
                    <label>{{ __('finance.daily_journals.summary.branch') }}</label>
                    <span>{{ $journal->branch?->name ?? __('finance.daily_journals.summary.no_branch') }}</span>
                </div>
                <div class="branch-cell">
                    <label>{{ __('finance.daily_journals.summary.company') }}</label>
                    <span>{{ $journal->company?->trade_name ?? __('finance.daily_journals.summary.no_company') }}</span>
                </div>
                <div class="branch-cell">
                    <label>{{ __('finance.daily_journals.fields.recorded_by') }}</label>
                    <span>{{ $journal->recorder?->user?->name ?? '—' }}</span>
                </div>
                <div class="branch-cell">
                    <label>{{ __('finance.daily_journals.summary.entries') }}</label>
                    <span>{{ $entries->count() }}</span>
                </div>
            </div>

            <div class="kpi-strip">
                <div class="kpi-box">
                    <div class="val" style="color: #1d4ed8;"><bdi>{{ $entries->count() }}</bdi></div>
                    <div class="lbl">{{ __('finance.daily_journals.summary.entries') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) $totalSales, 2) }}</bdi></div>
                    <div class="lbl">{{ __('finance.daily_journals.summary.sales_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) $totalPurchases, 2) }}</bdi></div>
                    <div class="lbl">{{ __('finance.daily_journals.summary.purchases_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: {{ $totalNet >= 0 ? '#059669' : '#dc2626' }};"><bdi>{{ number_format((float) $totalNet, 2) }}</bdi></div>
                    <div class="lbl">{{ __('finance.daily_journals.summary.net_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #0f172a;">
                        {{ $totalNet > 0 ? __('finance.daily_journals.net_status.profit') : ($totalNet < 0 ? __('finance.daily_journals.net_status.loss') : __('finance.daily_journals.net_status.even')) }}
                    </div>
                    <div class="lbl">{{ __('finance.daily_journals.summary.net_status_label') }}</div>
                </div>
            </div>

            <div class="section-title">{{ __('finance.daily_journals.summary.entries_records') }}</div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th>{{ __('finance.daily_journals.fields.entry_number') }}</th>
                            <th>{{ __('finance.daily_journals.fields.entry_date') }}</th>
                            <th>{{ __('finance.daily_journals.fields.journal_type') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.sales_sum') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.purchases_sum') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.net_sum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td><strong><bdi>{{ $entry->entry_number }}</bdi></strong></td>
                                <td class="numeric"><bdi>{{ $entry->entry_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td>
                                    <span class="badge {{ $entry->is_detailed ? 'badge-detailed' : 'badge-summary' }}">
                                        {{ __('finance.daily_journals.types.'.($entry->is_detailed ? 'detailed' : 'summary')) }}
                                    </span>
                                </td>
                                <td class="numeric" style="color: #7e22ce;"><bdi>{{ number_format((float) $entry->sales, 2) }}</bdi></td>
                                <td class="numeric" style="color: #b45309;"><bdi>{{ number_format((float) $entry->purchases, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ $entry->net_amount >= 0 ? '#059669' : '#dc2626' }};"><bdi>{{ number_format((float) $entry->net_amount, 2) }}</bdi></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">{{ __('finance.daily_journals.summary.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: start;"><strong>{{ __('finance.daily_journals.summary.total') }}</strong></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalSales, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalPurchases, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalNet, 2) }}</bdi></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @else
            @if($stats)
                <div class="kpi-strip">
                    <div class="kpi-box">
                        <div class="val" style="color: #1d4ed8;"><bdi>{{ number_format((float) ($stats->entries_count ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('finance.daily_journals.summary.entries') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #15803d;"><bdi>{{ number_format((float) ($stats->branches ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('finance.daily_journals.summary.branches') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) ($stats->sales_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('finance.daily_journals.summary.sales_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) ($stats->purchases_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('finance.daily_journals.summary.purchases_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: {{ (float) ($stats->net_sum ?? 0) >= 0 ? '#059669' : '#dc2626' }};"><bdi>{{ number_format((float) ($stats->net_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('finance.daily_journals.summary.net_sum') }} ({{ __('finance.daily_journals.summary.currency') }})</div>
                    </div>
                </div>
            @endif

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th style="text-align: start;">{{ __('finance.daily_journals.summary.branch') }}</th>
                            <th>{{ __('finance.daily_journals.summary.company') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.entries_count') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.sales_sum') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.purchases_sum') }}</th>
                            <th class="numeric">{{ __('finance.daily_journals.summary.net_sum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td style="text-align: start;"><strong>{{ $row->branch?->name ?? __('finance.daily_journals.summary.no_branch') }}</strong></td>
                                <td>{{ $row->company?->trade_name ?? __('finance.daily_journals.summary.no_company') }}</td>
                                <td class="numeric"><strong>{{ $row->entries_count }}</strong></td>
                                <td class="numeric" style="color: #7e22ce;"><bdi>{{ number_format((float) $row->sales_sum, 2) }}</bdi></td>
                                <td class="numeric" style="color: #b45309;"><bdi>{{ number_format((float) $row->purchases_sum, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ $row->net_sum >= 0 ? '#059669' : '#dc2626' }};">
                                    <bdi>{{ number_format((float) $row->net_sum, 2) }}</bdi>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">{{ __('finance.daily_journals.summary.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($stats && $rows && $rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: start;">
                                    <strong>{{ __('finance.daily_journals.summary.total') }} ({{ __('finance.daily_journals.summary.branches') }}: {{ number_format((float) ($stats->branches ?? 0)) }})</strong>
                                </td>
                                <td class="numeric"><strong>{{ number_format((float) ($stats->entries_count ?? 0)) }}</strong></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->sales_sum ?? 0), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->purchases_sum ?? 0), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->net_sum ?? 0), 2) }}</bdi></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </main>
</body>
</html>
