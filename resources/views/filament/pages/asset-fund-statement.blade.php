<x-filament-panels::page>
    <style>
        .af-stmt-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 1.25rem; }
        .dark .af-stmt-card { background: #1e293b; border-color: #334155; }
        .af-stmt-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem; }
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
        .af-table { width: 100%; border-collapse: collapse; }
        .af-table th { background: #f1f5f9; padding: .6rem 1rem; font-size: .8rem; font-weight: 700; color: #334155; border-bottom: 2px solid #e2e8f0; text-align: right; }
        .dark .af-table th { background: #0f172a; color: #cbd5e1; border-color: #334155; }
        .af-table td { padding: .65rem 1rem; font-size: .875rem; border-bottom: 1px solid #f1f5f9; color: #0f172a; }
        .dark .af-table td { border-color: #1e293b; color: #e2e8f0; }
        .af-table tr:hover td { background: #f8fafc; }
        .dark .af-table tr:hover td { background: #1e293b; }
        .badge { display: inline-flex; align-items: center; gap: .25rem; padding: .2rem .65rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .amount-in { color: #16a34a; font-weight: 700; }
        .amount-out { color: #dc2626; font-weight: 700; }
        .no-data { text-align: center; padding: 2.5rem; color: #94a3b8; font-size: .9rem; }
    </style>

    {{-- Header --}}
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

    @if ($this->fund_id && $this->vouchers->count() > 0)
        {{-- ملخص --}}
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

        {{-- جدول الحركات --}}
        <div class="af-stmt-card" style="overflow-x:auto;">
            <table class="af-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.voucher_number') }}</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.voucher_date') }}</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.type') }}</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.party_name') }}</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.notes') }}</th>
                        <th>{{ __('asset_funds.asset_fund_vouchers.fields.total_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->vouchers as $i => $voucher)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span style="font-weight:700;color:#082c50">{{ $voucher->voucher_number }}</span></td>
                            <td>{{ $voucher->voucher_date?->format('Y/m/d') }}</td>
                            <td>
                                @if ($voucher->type?->value === 'deposit')
                                    <span class="badge badge-green">⬇ {{ $voucher->type?->getLabel() }}</span>
                                @else
                                    <span class="badge badge-red">⬆ {{ $voucher->type?->getLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $voucher->party_name ?? $voucher->company?->name ?? '—' }}
                            </td>
                            <td>{{ Str::limit($voucher->notes, 60) ?? '—' }}</td>
                            <td class="{{ $voucher->type?->value === 'deposit' ? 'amount-in' : 'amount-out' }}">
                                {{ $voucher->type?->value === 'deposit' ? '+' : '-' }}
                                {{ number_format($voucher->total_amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @elseif ($this->fund_id)
        <div class="af-stmt-card">
            <p class="no-data">{{ __('asset_funds.statement.no_results') }}</p>
        </div>
    @else
        <div class="af-stmt-card">
            <p class="no-data">{{ __('asset_funds.statement.please_select_fund') }}</p>
        </div>
    @endif
</x-filament-panels::page>

