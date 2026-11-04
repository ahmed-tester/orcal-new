<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'dejavusans', sans-serif; direction: rtl; color: #000; font-size: 8.5pt; }

    .header { text-align: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #082c50; }
    .header h1 { font-size: 13pt; font-weight: bold; color: #082c50; margin-bottom: 2px; }
    .header p { font-size: 8.5pt; color: #4a5568; }
    .header .purpose-badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt; margin-top: 3px; }

    .fund-info { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; }
    .fund-info table { width: 100%; border-collapse: collapse; }
    .fund-info td { font-size: 8pt; padding: 2px 6px; }
    .fund-info td.label { font-weight: bold; color: #082c50; width: 100px; }

    .section-title { font-size: 9.5pt; font-weight: bold; text-align: center; margin: 10px 0 6px 0; }
    .section-title.deposit { color: #15803d; }
    .section-title.withdrawal { color: #b91c1c; margin-top: 12px; }

    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.data-table th { background: #f1f5f9; color: #0f172a; border: 1px solid #94a3b8; padding: 4px 3px; font-size: 7pt; font-weight: bold; text-align: center; line-height: 1.2; }
    table.data-table td { border: 1px solid #94a3b8; padding: 4px 3px; font-size: 7.5pt; text-align: center; color: #000; }
    table.data-table tr.total-row td { background: #f8fafc; font-weight: bold; border-top: 2px solid #082c50; }

    .summary-box { margin-top: 10px; margin-bottom: 10px; }
    .summary-box table { width: 100%; border-collapse: collapse; }
    .summary-box td { padding: 5px 6px; font-size: 8pt; border: 1px solid #cbd5e1; text-align: center; }
    .summary-box td.label { font-weight: bold; background: #f1f5f9; color: #082c50; }

    .footer { margin-top: 14px; text-align: center; font-size: 7pt; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body>

<div class="header">
    <h1>كشف حركة صندوق الأصول</h1>
    <p>{{ $fund->fund_name }} ({{ $fund->fund_number }})</p>
    @if (! empty($filters['preset']) && $filters['preset'] !== 'all')
        <div class="purpose-badge">
            {{ __('asset_funds.statement.columns_panel.presets.' . $filters['preset']) }}
        </div>
    @endif
</div>

<div class="fund-info">
    <table>
        <tr>
            <td class="label">اسم الصندوق:</td>
            <td>{{ $fund->fund_name }}</td>
            <td class="label">رقم الصندوق:</td>
            <td>{{ $fund->fund_number }}</td>
            <td class="label">تاريخ الإنشاء:</td>
            <td>{{ $fund->established_date?->format('Y/m/d') }}</td>
        </tr>
        <tr>
            <td class="label">مدير الصندوق:</td>
            <td>{{ $fund->manager_name ?? '—' }}</td>
            <td class="label">محاسب الصندوق:</td>
            <td>{{ $fund->accountant_name ?? '—' }}</td>
            <td class="label">فترة الكشف:</td>
            <td>
                {{ $filters['date_from'] ? 'من ' . $filters['date_from'] : 'منذ البداية' }}
                {{ $filters['date_to'] ? ' إلى ' . $filters['date_to'] : ' حتى الآن' }}
            </td>
        </tr>
    </table>
</div>

@php
    $allDepCols  = \App\Services\AssetFundStatementService::depositColumns();
    $allWthCols  = \App\Services\AssetFundStatementService::withdrawalColumns();
    $allCardDefs = \App\Services\AssetFundStatementService::summaryCards();
    $showDeposits    = ($filters['filter_type'] === 'all' || $filters['filter_type'] === 'deposit') && count($depositColumns) > 0;
    $showWithdrawals = ($filters['filter_type'] === 'all' || $filters['filter_type'] === 'withdrawal') && count($withdrawalColumns) > 0;
@endphp

{{-- 1. Deposit vouchers table --}}
@if ($showDeposits)
    <div class="section-title deposit">سند مدخلات ( إيداع )</div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($depositColumns as $key)
                    @if (isset($allDepCols[$key]))
                        <th @if(!empty($allDepCols[$key]['width'])) style="width:{{ $allDepCols[$key]['width'] }}" @endif>
                            {{ $allDepCols[$key]['label'] }}
                        </th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($depositVouchers as $i => $row)
                <tr>
                    @foreach ($depositColumns as $key)
                        @if (isset($allDepCols[$key]))
                            <td>{!! $allDepCols[$key]['html']($row, $i) !!}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($depositColumns) }}" style="padding: 10px; color: #64748b;">
                        {{ __('asset_funds.statement.no_deposits') }}
                    </td>
                </tr>
            @endforelse

            @if ($depositVouchers->count() > 0)
                <tr class="total-row">
                    @foreach ($depositColumns as $key)
                        @if (isset($allDepCols[$key]))
                            <td>
                                @if ($loop->first)
                                    {{ __('asset_funds.statement.cols.totals') }}
                                    @if (! empty($allDepCols[$key]['total']))
                                        <br>{!! $allDepCols[$key]['total']($depositsSummary) !!}
                                    @endif
                                @elseif (! empty($allDepCols[$key]['total']))
                                    {!! $allDepCols[$key]['total']($depositsSummary) !!}
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
@endif

{{-- 2. Withdrawal vouchers table --}}
@if ($showWithdrawals)
    <div class="section-title withdrawal">سند سحب ( صرف )</div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($withdrawalColumns as $key)
                    @if (isset($allWthCols[$key]))
                        <th @if(!empty($allWthCols[$key]['width'])) style="width:{{ $allWthCols[$key]['width'] }}" @endif>
                            {{ $allWthCols[$key]['label'] }}
                        </th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($withdrawalVouchers as $i => $row)
                <tr>
                    @foreach ($withdrawalColumns as $key)
                        @if (isset($allWthCols[$key]))
                            <td>{!! $allWthCols[$key]['html']($row, $i) !!}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($withdrawalColumns) }}" style="padding: 10px; color: #64748b;">
                        {{ __('asset_funds.statement.no_withdrawals') }}
                    </td>
                </tr>
            @endforelse

            @if ($withdrawalVouchers->count() > 0)
                <tr class="total-row">
                    @foreach ($withdrawalColumns as $key)
                        @if (isset($allWthCols[$key]))
                            <td>
                                @if ($loop->first)
                                    {{ __('asset_funds.statement.cols.totals') }}
                                    @if (! empty($allWthCols[$key]['total']))
                                        <br>{!! $allWthCols[$key]['total']($withdrawalsSummary) !!}
                                    @endif
                                @elseif (! empty($allWthCols[$key]['total']))
                                    {!! $allWthCols[$key]['total']($withdrawalsSummary) !!}
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
@endif

{{-- Financial summary --}}
@if (count($cards) > 0)
    @php
        $cardColors = [
            'total_deposits'    => '#16a34a',
            'total_withdrawals' => '#dc2626',
            'total_tax'         => '#b45309',
            'total_retention'   => '#6d28d9',
            'net_balance'       => ($summary['net_balance'] >= 0 ? '#16a34a' : '#dc2626'),
        ];
    @endphp
    <div class="summary-box">
        <div style="font-size: 9pt; font-weight: bold; color: #082c50; margin-bottom: 4px; text-align: center;">
            {{ __('asset_funds.statement.summary_section') }}
        </div>
        <table>
            <tr>
                @foreach ($cards as $cardKey)
                    @if (isset($allCardDefs[$cardKey]))
                        <td class="label">{{ $allCardDefs[$cardKey] }}</td>
                    @endif
                @endforeach
            </tr>
            <tr>
                @foreach ($cards as $cardKey)
                    @if (isset($allCardDefs[$cardKey]))
                        <td style="font-weight: bold; font-size: 9pt; color: {{ $cardColors[$cardKey] ?? '#000' }}; background: #fff;">
                            {{ number_format($summary[$cardKey], 2) }} <small style="font-size: 6.5pt;">ر.س</small>
                        </td>
                    @endif
                @endforeach
            </tr>
        </table>
    </div>
@endif

<div class="footer">
    تم استخراج هذا الكشف آلياً بواسطة نظام Orcal الإداري والمالي — {{ now()->format('Y/m/d H:i') }}
</div>

</body>
</html>
