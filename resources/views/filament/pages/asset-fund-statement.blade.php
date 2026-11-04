<x-filament-panels::page>
    <style>
        .af-stmt-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 1.25rem; }
        .dark .af-stmt-card { background: #1e293b; border-color: #334155; }
        .af-stmt-title { font-size: 1.25rem; font-weight: 800; color: #082c50; display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem; }
        .dark .af-stmt-title { color: #f8fafc; }
        .af-stmt-subtitle { font-size: .85rem; color: #64748b; margin-bottom: 1.25rem; }
        .af-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .af-filter-label { font-size: .8125rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; display: block; }
        .dark .af-filter-label { color: #e2e8f0; }
        .af-filter-input { width: 100%; padding: .5rem .75rem; border-radius: .5rem; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; font-size: .875rem; outline: none; box-sizing: border-box; }
        .dark .af-filter-input { background: #0f172a; border-color: #334155; color: #f8fafc; }
        .af-filter-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        .af-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1.25rem; border-radius: .5rem; font-size: .875rem; font-weight: 700; cursor: pointer; border: none; transition: all .15s; }
        .af-btn-primary { background: #082c50; color: #fff; }
        .af-btn-primary:hover { background: #0a3d6e; }
        .af-btn-success { background: #16a34a; color: #fff; }
        .af-btn-success:hover { background: #15803d; }
        .af-summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
        @media (max-width: 640px) { .af-summary-grid { grid-template-columns: 1fr; } }
        .af-sum-card { border-radius: .75rem; padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .25rem; }
        .af-sum-card.deposits { background: #dcfce7; border: 1px solid #86efac; }
        .dark .af-sum-card.deposits { background: rgba(22,163,74,.2); border-color: rgba(22,163,74,.4); }
        .af-sum-card.withdrawals { background: #fee2e2; border: 1px solid #fca5a5; }
        .dark .af-sum-card.withdrawals { background: rgba(220,38,38,.2); border-color: rgba(220,38,38,.4); }
        .af-sum-card.balance { background: #dbeafe; border: 1px solid #93c5fd; }
        .dark .af-sum-card.balance { background: rgba(37,99,235,.2); border-color: rgba(37,99,235,.4); }
        .af-sum-label { font-size: .75rem; font-weight: 700; color: #475569; }
        .dark .af-sum-label { color: #94a3b8; }
        .af-sum-value { font-size: 1.4rem; font-weight: 900; }
        .af-table-wrapper { overflow-x: auto; margin-bottom: 1.5rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; }
        .af-table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        .af-table th { background: #e2e8f0; padding: .6rem .65rem; font-size: .75rem; font-weight: 800; color: #1e293b; border: 1px solid #cbd5e1; text-align: center; white-space: normal; }
        .dark .af-table th { background: #334155; color: #f8fafc; border-color: #475569; }
        .af-table td { padding: .55rem .65rem; border: 1px solid #e2e8f0; text-align: center; color: #0f172a; }
        .dark .af-table td { border-color: #334155; color: #e2e8f0; }
        .af-table tr:hover td { background: #f8fafc; }
        .dark .af-table tr:hover td { background: #1e293b; }
        .af-table tfoot td { font-weight: 800; background: #f1f5f9; border-top: 2px solid #94a3b8; }
        .dark .af-table tfoot td { background: #1e293b; }
        .table-section-title { font-size: 1.05rem; font-weight: 800; margin-bottom: .6rem; display: flex; align-items: center; gap: .5rem; }
        .title-deposits { color: #15803d; }
        .title-withdrawals { color: #b91c1c; }
        .no-data { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: .875rem; }
    </style>

    {{-- Filter Header --}}
    <div class="af-stmt-card">
        <div class="af-stmt-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.5rem;height:1.5rem;color:#082c50">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            {{ __('asset_funds.statement.title') }}
        </div>
        <p class="af-stmt-subtitle">{{ __('asset_funds.statement.subtitle') }}</p>

        {{-- Filters --}}
        <div class="af-filter-grid">
            {{-- اختيار الصندوق --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.fund') }}</label>
                <select class="af-filter-input" wire:model.live="fund_id">
                    <option value="">{{ __('asset_funds.statement.select_fund_placeholder') }}</option>
                    @foreach ($this->funds as $fund)
                        <option value="{{ $fund->id }}">{{ $fund->fund_name }} ({{ $fund->fund_number }})</option>
                    @endforeach
                </select>
            </div>

            {{-- من تاريخ --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.date_from') }}</label>
                <input type="date" class="af-filter-input" wire:model.live="date_from">
            </div>

            {{-- إلى تاريخ --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.date_to') }}</label>
                <input type="date" class="af-filter-input" wire:model.live="date_to">
            </div>

            {{-- نوع العمليات --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.filter_type') }}</label>
                <select class="af-filter-input" wire:model.live="filter_type">
                    <option value="all">{{ __('asset_funds.statement.filter_types.all') }}</option>
                    <option value="deposit">{{ __('asset_funds.statement.filter_types.deposit') }}</option>
                    <option value="withdrawal">{{ __('asset_funds.statement.filter_types.withdrawal') }}</option>
                </select>
            </div>
        </div>

        @if ($this->fund_id)
            <div style="margin-top:1rem;display:flex;gap:.75rem;flex-wrap:wrap;">
                <button class="af-btn af-btn-success" wire:click="exportPdf">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-4.5c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                    {{ __('asset_funds.statement.actions.export_pdf') }}
                </button>
            </div>
        @endif
    </div>

    @if ($this->fund_id)
        {{-- بطاقات الملخص المالي --}}
        @php $summary = $this->summary; @endphp
        <div class="af-summary-grid">
            <div class="af-sum-card deposits">
                <span class="af-sum-label">{{ __('asset_funds.statement.summary.total_deposits') }}</span>
                <span class="af-sum-value" style="color:#16a34a">{{ number_format($summary['total_deposits'], 2) }} <small style="font-size:.75rem">{{ __('asset_funds.statement.currency') }}</small></span>
            </div>
            <div class="af-sum-card withdrawals">
                <span class="af-sum-label">{{ __('asset_funds.statement.summary.total_withdrawals') }}</span>
                <span class="af-sum-value" style="color:#dc2626">{{ number_format($summary['total_withdrawals'], 2) }} <small style="font-size:.75rem">{{ __('asset_funds.statement.currency') }}</small></span>
            </div>
            <div class="af-sum-card balance">
                <span class="af-sum-label">{{ __('asset_funds.statement.summary.net_balance') }}</span>
                <span class="af-sum-value" style="color:{{ $summary['net_balance'] >= 0 ? '#2563eb' : '#dc2626' }}">
                    {{ number_format($summary['net_balance'], 2) }} <small style="font-size:.75rem">{{ __('asset_funds.statement.currency') }}</small>
                </span>
            </div>
        </div>

        {{-- 1. جدول سند مدخلات ( إيداع ) --}}
        @if ($this->filter_type === 'all' || $this->filter_type === 'deposit')
            <div class="af-stmt-card">
                <div class="table-section-title title-deposits">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.25rem;height:1.25rem"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" /></svg>
                    {{ __('asset_funds.statement.deposits_table_title') }}
                </div>

                @if ($this->deposit_vouchers->count() > 0)
                    @php $depSum = $this->deposits_summary; @endphp
                    <div class="af-table-wrapper">
                        <table class="af-table">
                            <thead>
                                <tr>
                                    <th style="width:30px">{{ __('asset_funds.statement.cols.serial') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.contractor') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.company') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.gross_amount') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.tax_amount') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.net_after_tax') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.retention_pct') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.fund_share_pct') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.net_after_deductions') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.date') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.invoice_no') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->deposit_vouchers as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $row->contractor_name ?: '—' }}</td>
                                        <td>{{ $row->party_name ?: ($row->company?->trade_name ?? $row->company?->name ?? '—') }}</td>
                                        <td style="font-weight:700">{{ $row->gross_amount !== null ? number_format($row->gross_amount, 2) : '—' }}</td>
                                        <td>{{ $row->tax_amount !== null ? number_format($row->tax_amount, 2) : '0.00' }}</td>
                                        <td style="font-weight:700">{{ $row->net_after_tax !== null ? number_format($row->net_after_tax, 2) : '—' }}</td>
                                        <td>
                                            @if ($row->retention_amount > 0)
                                                {{ number_format($row->retention_amount, 2) }}
                                                @if ($row->retention_percentage)
                                                    <small style="color:#64748b">({{ (float) $row->retention_percentage }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->fund_share_amount > 0)
                                                {{ number_format($row->fund_share_amount, 2) }}
                                                @if ($row->fund_share_percentage)
                                                    <small style="color:#64748b">({{ (float) $row->fund_share_percentage }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td style="font-weight:800;color:#16a34a">{{ number_format($row->total_amount, 2) }}</td>
                                        <td>{{ $row->voucher_date?->format('Y/m/d') }}</td>
                                        <td><span style="font-weight:700;color:#082c50">{{ $row->tax_invoice_number ?: '—' }}</span></td>
                                        <td>{{ $row->notes ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">{{ __('asset_funds.statement.cols.totals') }}</td>
                                    <td>{{ number_format($depSum['gross_amount'], 2) }}</td>
                                    <td>{{ number_format($depSum['tax_amount'], 2) }}</td>
                                    <td>{{ number_format($depSum['net_after_tax'], 2) }}</td>
                                    <td>{{ number_format($depSum['retention_amount'], 2) }}</td>
                                    <td>{{ number_format($depSum['fund_share_amount'], 2) }}</td>
                                    <td style="color:#16a34a">{{ number_format($depSum['net_after_deductions'], 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="no-data">{{ __('asset_funds.statement.no_deposits') }}</p>
                @endif
            </div>
        @endif

        {{-- 2. جدول سند سحب ( صرف ) --}}
        @if ($this->filter_type === 'all' || $this->filter_type === 'withdrawal')
            <div class="af-stmt-card">
                <div class="table-section-title title-withdrawals">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.25rem;height:1.25rem"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                    {{ __('asset_funds.statement.withdrawals_table_title') }}
                </div>

                @if ($this->withdrawal_vouchers->count() > 0)
                    @php $wthSum = $this->withdrawals_summary; @endphp
                    <div class="af-table-wrapper">
                        <table class="af-table">
                            <thead>
                                <tr>
                                    <th style="width:30px">{{ __('asset_funds.statement.cols.serial') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.payee') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.reason') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.amount_without_tax') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.tax_amount_opt') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.gross_amount') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.date') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.voucher_no') }}</th>
                                    <th>{{ __('asset_funds.statement.cols.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->withdrawal_vouchers as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td style="font-weight:700">{{ $row->party_name ?: '—' }}</td>
                                        <td>{{ $row->expense_reason ?: '—' }}</td>
                                        <td style="font-weight:700">{{ $row->amount_without_tax !== null ? number_format($row->amount_without_tax, 2) : '—' }}</td>
                                        <td>{{ $row->tax_amount !== null ? number_format($row->tax_amount, 2) : '0.00' }}</td>
                                        <td style="font-weight:800;color:#dc2626">{{ number_format($row->total_amount, 2) }}</td>
                                        <td>{{ $row->voucher_date?->format('Y/m/d') }}</td>
                                        <td><span style="font-weight:700;color:#082c50">{{ $row->voucher_number }}</span></td>
                                        <td>{{ $row->notes ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">{{ __('asset_funds.statement.cols.totals') }}</td>
                                    <td>{{ number_format($wthSum['amount_without_tax'], 2) }}</td>
                                    <td>{{ number_format($wthSum['tax_amount'], 2) }}</td>
                                    <td style="color:#dc2626">{{ number_format($wthSum['total_amount'], 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="no-data">{{ __('asset_funds.statement.no_withdrawals') }}</p>
                @endif
            </div>
        @endif

    @else
        <div class="af-stmt-card">
            <p class="no-data">{{ __('asset_funds.statement.please_select_fund') }}</p>
        </div>
    @endif
</x-filament-panels::page>
