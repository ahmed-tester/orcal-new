<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('debts.summary.'.($details ? 'statement' : 'title')) }}</title>
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
        .badge-internal { background: #eff6ff; color: #1d4ed8; }
        .badge-external { background: #faf5ff; color: #7e22ce; }

        .debtor-card {
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
        .debtor-cell label {
            display: block;
            color: #64748b;
            font-weight: 600;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .debtor-cell span {
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
            ? ($details['debtor']->company?->trade_name ?? auth()->user()?->company?->trade_name)
            : ($rows?->first()?->company?->trade_name ?? auth()->user()?->company?->trade_name);
    @endphp

    <div class="print-toolbar">
        <div class="toolbar-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">{{ __('debts.summary.print') }}</button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">{{ __('debts.summary.close') }}</button>
        </div>
        <div class="date-meta">
            <span>{{ __('debts.summary.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
    </div>

    <main class="sheet-container">
        <div class="header">
            <div class="company-meta">
                <h2>{{ $companyName ?? __('debts.summary.company') }}</h2>
                <p>{{ __('debts.plural') }}</p>
            </div>
            <div class="sheet-title">
                <h1>{{ __('debts.summary.'.($details ? 'statement' : 'title')) }}</h1>
                @if($details)
                    <div class="subtitle">{{ $details['debtor']->beneficiary_name }}</div>
                @endif
            </div>
            <div class="date-meta">
                <div><span>{{ __('debts.summary.print_date') }}:</span> <bdi>{{ now()->format('Y-m-d') }}</bdi></div>
                <div><span>{{ __('debts.summary.debt_count') }}:</span> <bdi>{{ $details ? count($details['debts']) : ($rows ? $rows->count() : 0) }}</bdi></div>
            </div>
        </div>

        @if($details)
            @php
                $debtor = $details['debtor'];
                $debtorDebts = $details['debts'];
                $totalPrincipal = $debtorDebts->sum('total_amount');
                $totalPaid = $debtorDebts->sum('paid_amount');
                $totalRemaining = $debtorDebts->sum('remaining_amount');
                $totalOverdue = $debtorDebts->filter(fn($d) => \App\Services\DebtSummaryService::isOverdue($d))->sum('remaining_amount');
            @endphp

            <div class="debtor-card">
                <div class="debtor-cell">
                    <label>{{ __('debts.summary.debtor') }}</label>
                    <span>{{ $debtor->beneficiary_name }}</span>
                </div>
                <div class="debtor-cell">
                    <label>{{ __('debts.summary.debtor_type') }}</label>
                    <span>
                        <span class="badge {{ $debtor->debtor_type === 'internal' ? 'badge-internal' : 'badge-external' }}">
                            {{ __('debts.badges.'.$debtor->debtor_type) }}
                        </span>
                    </span>
                </div>
                <div class="debtor-cell">
                    <label>{{ __('debts.summary.company') }}</label>
                    <span>{{ $debtor->company?->trade_name ?? __('debts.summary.no_company') }}</span>
                </div>
                @if($debtor->isExternal() && filled($debtor->external_national_id))
                    <div class="debtor-cell">
                        <label>{{ __('debts.fields.external_national_id') }}</label>
                        <span><bdi>{{ $debtor->external_national_id }}</bdi></span>
                    </div>
                @elseif($debtor->employee)
                    <div class="debtor-cell">
                        <label>{{ __('debts.fields.employee_number') }}</label>
                        <span><bdi>{{ $debtor->employee->employee_number }}</bdi></span>
                    </div>
                @endif
            </div>

            <div class="kpi-strip">
                <div class="kpi-box">
                    <div class="val" style="color: #1d4ed8;"><bdi>{{ $debtorDebts->count() }}</bdi></div>
                    <div class="lbl">{{ __('debts.summary.debt_count') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) $totalPrincipal, 2) }}</bdi></div>
                    <div class="lbl">{{ __('debts.summary.total_sum') }} ({{ __('debts.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #059669;"><bdi>{{ number_format((float) $totalPaid, 2) }}</bdi></div>
                    <div class="lbl">{{ __('debts.summary.paid_sum') }} ({{ __('debts.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) $totalRemaining, 2) }}</bdi></div>
                    <div class="lbl">{{ __('debts.summary.remaining_sum') }} ({{ __('debts.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #dc2626;"><bdi>{{ number_format((float) $totalOverdue, 2) }}</bdi></div>
                    <div class="lbl">{{ __('debts.summary.overdue_sum') }} ({{ __('debts.summary.currency') }})</div>
                </div>
            </div>

            <div class="section-title">{{ __('debts.summary.debts_records') }}</div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th>{{ __('debts.fields.debt_number') }}</th>
                            <th>{{ __('debts.fields.debt_date') }}</th>
                            <th>{{ __('debts.fields.due_date') }}</th>
                            <th>{{ __('debts.fields.debt_reason') }}</th>
                            <th class="numeric">{{ __('debts.fields.total_amount') }}</th>
                            <th class="numeric">{{ __('debts.fields.paid_amount') }}</th>
                            <th class="numeric">{{ __('debts.fields.remaining_amount') }}</th>
                            <th>{{ __('debts.fields.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debtorDebts as $debtRecord)
                            @php $isOverdue = \App\Services\DebtSummaryService::isOverdue($debtRecord); @endphp
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td><strong><bdi>{{ $debtRecord->debt_number }}</bdi></strong></td>
                                <td class="numeric"><bdi>{{ $debtRecord->debt_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td class="numeric"><bdi>{{ $debtRecord->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td style="text-align: start;">{{ $debtRecord->debt_reason ?: '—' }}</td>
                                <td class="numeric"><bdi>{{ number_format((float) $debtRecord->total_amount, 2) }}</bdi></td>
                                <td class="numeric" style="color: #059669;"><bdi>{{ number_format((float) $debtRecord->paid_amount, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ $debtRecord->remaining_amount > 0 ? '#b45309' : '#059669' }};"><bdi>{{ number_format((float) $debtRecord->remaining_amount, 2) }}</bdi></td>
                                <td>{{ __('debts.badges.'.($isOverdue ? 'overdue' : $debtRecord->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty">{{ __('debts.summary.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: start;"><strong>{{ __('debts.summary.total') }}</strong></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalPrincipal, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalPaid, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalRemaining, 2) }}</bdi></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($details['showCollections'])
                @php
                    $collections = $debtorDebts->flatMap->collections->sortByDesc('collection_date');
                @endphp
                <div class="section-title">{{ __('debts.summary.collections_records') }}</div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th>{{ __('debts.collections.fields.voucher_number') }}</th>
                                <th>{{ __('debts.fields.debt_number') }}</th>
                                <th>{{ __('debts.collections.fields.collection_date') }}</th>
                                <th>{{ __('debts.collections.fields.payment_method') }}</th>
                                <th class="numeric">{{ __('debts.collections.fields.amount') }}</th>
                                <th>{{ __('debts.collections.fields.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($collections as $col)
                                <tr>
                                    <td class="numeric">{{ $loop->iteration }}</td>
                                    <td><strong><bdi>{{ $col->voucher_number }}</bdi></strong></td>
                                    <td><bdi>{{ $col->debt?->debt_number ?? '—' }}</bdi></td>
                                    <td class="numeric"><bdi>{{ $col->collection_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                    <td>{{ $col->payment_method ? __('debts.collections.payment_methods.'.$col->payment_method) : '—' }}</td>
                                    <td class="numeric" style="color: #059669; font-weight: 700;"><bdi>{{ number_format((float) $col->amount, 2) }}</bdi></td>
                                    <td>{{ $col->status?->getLabel() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">{{ __('debts.summary.no_collections') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if($collections->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align: start;"><strong>{{ __('debts.summary.total') }}</strong></td>
                                    <td class="numeric"><bdi>{{ number_format((float) $collections->sum('amount'), 2) }}</bdi></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif

        @else
            @if($stats)
                <div class="kpi-strip">
                    <div class="kpi-box">
                        <div class="val" style="color: #1d4ed8;"><bdi>{{ number_format((float) ($stats->debtors ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('debts.summary.debtors') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #15803d;"><bdi>{{ number_format((float) ($stats->debt_count ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('debts.summary.debt_count') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) ($stats->total_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('debts.summary.total_sum') }} ({{ __('debts.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #059669;"><bdi>{{ number_format((float) ($stats->paid_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('debts.summary.paid_sum') }} ({{ __('debts.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) ($stats->remaining_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('debts.summary.remaining_sum') }} ({{ __('debts.summary.currency') }})</div>
                    </div>
                </div>
            @endif

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th style="text-align: start;">{{ __('debts.summary.debtor') }}</th>
                            <th>{{ __('debts.summary.debtor_type') }}</th>
                            <th>{{ __('debts.summary.company') }}</th>
                            <th class="numeric">{{ __('debts.summary.debt_count') }}</th>
                            <th class="numeric">{{ __('debts.summary.total_sum') }}</th>
                            <th class="numeric">{{ __('debts.summary.paid_sum') }}</th>
                            <th class="numeric">{{ __('debts.summary.remaining_sum') }}</th>
                            <th class="numeric">{{ __('debts.summary.overdue_sum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td style="text-align: start;"><strong>{{ $row->beneficiary_name }}</strong></td>
                                <td>
                                    <span class="badge {{ $row->debtor_type === 'internal' ? 'badge-internal' : 'badge-external' }}">
                                        {{ __('debts.badges.'.$row->debtor_type) }}
                                    </span>
                                </td>
                                <td>{{ $row->company?->trade_name ?? __('debts.summary.no_company') }}</td>
                                <td class="numeric"><strong>{{ $row->debt_count }}</strong></td>
                                <td class="numeric"><bdi>{{ number_format((float) $row->total_sum, 2) }}</bdi></td>
                                <td class="numeric" style="color: #059669;"><bdi>{{ number_format((float) $row->paid_sum, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ $row->remaining_sum > 0 ? '#b45309' : '#059669' }};">
                                    <bdi>{{ number_format((float) $row->remaining_sum, 2) }}</bdi>
                                </td>
                                <td class="numeric" style="color: {{ $row->overdue_sum > 0 ? '#dc2626' : '#64748b' }}; font-weight: {{ $row->overdue_sum > 0 ? '700' : '400' }};">
                                    <bdi>{{ number_format((float) $row->overdue_sum, 2) }}</bdi>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="empty">{{ __('debts.summary.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($stats && $rows && $rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: start;">
                                    <strong>{{ __('debts.summary.total') }} ({{ __('debts.summary.debtors') }}: {{ number_format((float) ($stats->debtors ?? 0)) }})</strong>
                                </td>
                                <td class="numeric"><strong>{{ number_format((float) ($stats->debt_count ?? 0)) }}</strong></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->total_sum ?? 0), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->paid_sum ?? 0), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->remaining_sum ?? 0), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) ($stats->overdue_sum ?? 0), 2) }}</bdi></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </main>
</body>
</html>
