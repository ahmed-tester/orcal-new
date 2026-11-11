<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('violations.summary.'.($details ? 'statement' : 'title')) }}</title>
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

        .company-card {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .company-cell label {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }
        .company-cell span {
            font-weight: 800;
            color: #0f172a;
            font-size: 13px;
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
            ? ($details['company']?->trade_name ?? auth()->user()?->company?->trade_name)
            : ($rows?->first()?->company?->trade_name ?? auth()->user()?->company?->trade_name);
    @endphp

    <div class="print-toolbar">
        <div class="toolbar-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">{{ __('violations.summary.print') }}</button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">{{ __('violations.summary.close') }}</button>
        </div>
        <div class="date-meta">
            <span>{{ __('violations.summary.print_date') }}: <bdi>{{ now()->format('Y-m-d H:i') }}</bdi></span>
        </div>
    </div>

    <main class="sheet-container">
        <div class="header">
            <div class="company-meta">
                <h2>{{ $companyName ?? __('violations.summary.company') }}</h2>
                <p>{{ __('violations.violations.plural') }}</p>
            </div>
            <div class="sheet-title">
                <h1>{{ __('violations.summary.'.($details ? 'statement' : 'title')) }}</h1>
                @if($details)
                    <div class="subtitle">{{ $details['company']?->trade_name ?? __('violations.summary.no_company') }}</div>
                @endif
            </div>
            <div class="date-meta">
                <div><span>{{ __('violations.summary.print_date') }}:</span> <bdi>{{ now()->format('Y-m-d') }}</bdi></div>
                <div><span>{{ __('violations.summary.violation_count') }}:</span> <bdi>{{ $details ? count($details['violations']) : ($rows ? $rows->count() : 0) }}</bdi></div>
            </div>
        </div>

        @if($details)
            @php
                $company = $details['company'];
                $violations = $details['violations'];
                $totalPrincipal = $violations->sum('violation_amount');
                $totalPaid = $violations->sum('paid_amount');
                $totalRemaining = $violations->sum('remaining_amount');
                $totalOverdue = $violations->filter(fn($v) => \App\Services\ViolationSummaryService::isOverdue($v))->sum('remaining_amount');
            @endphp

            <div class="company-card">
                <div class="company-cell">
                    <label>{{ __('violations.summary.company') }}</label>
                    <span>{{ $company?->trade_name ?? __('violations.summary.no_company') }}</span>
                </div>
                <div class="company-cell">
                    <label>{{ __('violations.summary.commercial_registration') }}</label>
                    <span><bdi>{{ $company?->commercial_registration_number ?? '—' }}</bdi></span>
                </div>
                <div class="company-cell">
                    <label>{{ __('violations.violations.fields.company_phone') }}</label>
                    <span><bdi>{{ $company?->phone ?? '—' }}</bdi></span>
                </div>
            </div>

            <div class="kpi-strip">
                <div class="kpi-box">
                    <div class="val" style="color: #1d4ed8;"><bdi>{{ $violations->count() }}</bdi></div>
                    <div class="lbl">{{ __('violations.summary.violation_count') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) $totalPrincipal, 2) }}</bdi></div>
                    <div class="lbl">{{ __('violations.summary.total_sum') }} ({{ __('violations.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #059669;"><bdi>{{ number_format((float) $totalPaid, 2) }}</bdi></div>
                    <div class="lbl">{{ __('violations.summary.paid_sum') }} ({{ __('violations.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) $totalRemaining, 2) }}</bdi></div>
                    <div class="lbl">{{ __('violations.summary.remaining_sum') }} ({{ __('violations.summary.currency') }})</div>
                </div>
                <div class="kpi-box">
                    <div class="val" style="color: #dc2626;"><bdi>{{ number_format((float) $totalOverdue, 2) }}</bdi></div>
                    <div class="lbl">{{ __('violations.summary.overdue_sum') }} ({{ __('violations.summary.currency') }})</div>
                </div>
            </div>

            <div class="section-title">{{ __('violations.summary.violations_records') }}</div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th>{{ __('violations.violations.fields.violation_number') }}</th>
                            <th>{{ __('violations.violations.fields.external_violation_number') }}</th>
                            <th>{{ __('violations.violations.fields.violation_date') }}</th>
                            <th>{{ __('violations.violations.fields.issuing_authority') }}</th>
                            <th>{{ __('violations.summary.category') }}</th>
                            <th class="numeric">{{ __('violations.violations.fields.violation_amount') }}</th>
                            <th class="numeric">{{ __('violations.violations.fields.paid_amount') }}</th>
                            <th class="numeric">{{ __('violations.violations.fields.remaining_amount') }}</th>
                            <th>{{ __('violations.violations.fields.stage') }}</th>
                            <th>{{ __('violations.violations.fields.payment_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($violations as $vRecord)
                            @php $isOverdue = \App\Services\ViolationSummaryService::isOverdue($vRecord); @endphp
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td><strong><bdi>{{ $vRecord->violation_number ?? "#{$vRecord->id}" }}</bdi></strong></td>
                                <td><bdi>{{ $vRecord->external_violation_number ?: '—' }}</bdi></td>
                                <td class="numeric"><bdi>{{ $vRecord->violation_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td>{{ $vRecord->issuing_authority }}</td>
                                <td>{{ $vRecord->category?->name ?? '—' }}</td>
                                <td class="numeric"><bdi>{{ number_format((float) $vRecord->violation_amount, 2) }}</bdi></td>
                                <td class="numeric" style="color: #059669;"><bdi>{{ number_format((float) $vRecord->paid_amount, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ (float) $vRecord->remaining_amount > 0 ? '#b45309' : '#059669' }};"><bdi>{{ number_format((float) $vRecord->remaining_amount, 2) }}</bdi></td>
                                <td>
                                    {{ $vRecord->stage?->getLabel() }}
                                    @if($isOverdue)
                                        <span style="color: #dc2626; font-weight: 700;">({{ __('violations.summary.overdue_sum') }})</span>
                                    @endif
                                </td>
                                <td>{{ $vRecord->payment_status?->getLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="empty">{{ __('violations.summary.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align: start;"><strong>{{ __('violations.summary.total') }}</strong></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalPrincipal, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalPaid, 2) }}</bdi></td>
                            <td class="numeric"><bdi>{{ number_format((float) $totalRemaining, 2) }}</bdi></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($details['showTransactions'])
                @php
                    $allTransactions = $violations->flatMap->transactions->sortByDesc('transaction_date');
                @endphp
                <div class="section-title">{{ __('violations.summary.transactions_records') }}</div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th>{{ __('violations.transactions.fields.voucher_number') }}</th>
                                <th>{{ __('violations.violations.fields.violation_number') }}</th>
                                <th>{{ __('violations.transactions.fields.transaction_date') }}</th>
                                <th>{{ __('violations.transactions.fields.transaction_type') }}</th>
                                <th class="numeric">{{ __('violations.transactions.fields.amount') }}</th>
                                <th>{{ __('violations.transactions.fields.description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allTransactions as $trans)
                                <tr>
                                    <td class="numeric">{{ $loop->iteration }}</td>
                                    <td><strong><bdi>{{ $trans->voucher_number }}</bdi></strong></td>
                                    <td><bdi>{{ $trans->violation?->violation_number ?? "#{$trans->violation_id}" }}</bdi></td>
                                    <td class="numeric"><bdi>{{ $trans->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                    <td>{{ $trans->transaction_type?->getLabel() }}</td>
                                    <td class="numeric" style="color: #059669; font-weight: 700;"><bdi>{{ number_format((float) $trans->amount, 2) }}</bdi></td>
                                    <td>{{ $trans->description ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">{{ __('violations.summary.no_transactions') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if($allTransactions->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align: start;"><strong>{{ __('violations.summary.total') }}</strong></td>
                                    <td class="numeric"><bdi>{{ number_format((float) $allTransactions->sum('amount'), 2) }}</bdi></td>
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
                        <div class="val" style="color: #1d4ed8;"><bdi>{{ number_format((float) ($stats->companies ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('violations.summary.companies') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #15803d;"><bdi>{{ number_format((float) ($stats->violation_count ?? 0)) }}</bdi></div>
                        <div class="lbl">{{ __('violations.summary.violation_count') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #7e22ce;"><bdi>{{ number_format((float) ($stats->total_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('violations.summary.total_sum') }} ({{ __('violations.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #059669;"><bdi>{{ number_format((float) ($stats->paid_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('violations.summary.paid_sum') }} ({{ __('violations.summary.currency') }})</div>
                    </div>
                    <div class="kpi-box">
                        <div class="val" style="color: #b45309;"><bdi>{{ number_format((float) ($stats->remaining_sum ?? 0), 2) }}</bdi></div>
                        <div class="lbl">{{ __('violations.summary.remaining_sum') }} ({{ __('violations.summary.currency') }})</div>
                    </div>
                </div>
            @endif

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th style="text-align: start;">{{ __('violations.summary.company') }}</th>
                            <th>{{ __('violations.summary.commercial_registration') }}</th>
                            <th class="numeric">{{ __('violations.summary.violation_count') }}</th>
                            <th class="numeric">{{ __('violations.summary.total_sum') }}</th>
                            <th class="numeric">{{ __('violations.summary.paid_sum') }}</th>
                            <th class="numeric">{{ __('violations.summary.remaining_sum') }}</th>
                            <th class="numeric">{{ __('violations.summary.overdue_sum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="numeric">{{ $loop->iteration }}</td>
                                <td style="text-align: start;"><strong>{{ $row->company?->trade_name ?? __('violations.summary.no_company') }}</strong></td>
                                <td><bdi>{{ $row->company?->commercial_registration_number ?? '—' }}</bdi></td>
                                <td class="numeric">{{ $row->violation_count }}</td>
                                <td class="numeric"><bdi>{{ number_format((float) $row->total_sum, 2) }}</bdi></td>
                                <td class="numeric" style="color: #059669;"><bdi>{{ number_format((float) $row->paid_sum, 2) }}</bdi></td>
                                <td class="numeric" style="font-weight: 700; color: {{ (float) $row->remaining_sum > 0 ? '#b45309' : '#059669' }};"><bdi>{{ number_format((float) $row->remaining_sum, 2) }}</bdi></td>
                                <td class="numeric" style="color: #dc2626;"><bdi>{{ number_format((float) $row->overdue_sum, 2) }}</bdi></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty">{{ __('violations.summary.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows && $rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: start;"><strong>{{ __('violations.summary.total') }}</strong></td>
                                <td class="numeric"><bdi>{{ $rows->sum('violation_count') }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) $rows->sum('total_sum'), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) $rows->sum('paid_sum'), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) $rows->sum('remaining_sum'), 2) }}</bdi></td>
                                <td class="numeric"><bdi>{{ number_format((float) $rows->sum('overdue_sum'), 2) }}</bdi></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </main>
</body>
</html>

