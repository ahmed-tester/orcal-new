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

        /* Columns & cards selector */
        .af-cols { margin-top: 1rem; border: 1px solid #e2e8f0; border-radius: .875rem; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden; }
        .dark .af-cols { background: #1e293b; border-color: #334155; }
        .af-cols > summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .85rem 1.15rem; font-size: .875rem; font-weight: 800; color: #082c50; background: #f8fafc; border-bottom: 1px solid #e2e8f0; transition: background .15s; }
        .dark .af-cols > summary { color: #f8fafc; background: #0f172a; border-bottom-color: #334155; }
        .af-cols > summary::-webkit-details-marker { display: none; }
        .af-cols > summary:hover { background: #f1f5f9; }
        .dark .af-cols > summary:hover { background: #1e293b; }
        .af-cols > summary::after { content: '▾'; color: #64748b; font-size: 1rem; transition: transform .2s ease; }
        .af-cols[open] > summary::after { transform: rotate(180deg); }
        .af-preset-badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: .725rem; font-weight: 700; }
        .dark .af-preset-badge { background: rgba(3,105,161,.2); color: #7dd3fc; }
        .af-cols-count { display: inline-flex; align-items: center; justify-content: center; min-width: 1.5rem; padding: 0 .45rem; height: 1.35rem; border-radius: 999px; background: #082c50; color: #fff; font-size: .7rem; font-weight: 800; }
        .dark .af-cols-count { background: #3b82f6; }
        .af-cols-body { padding: 1rem 1.15rem 1.25rem; }

        .af-permanent-notice { display: flex; align-items: center; gap: .6rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: .5rem; padding: .55rem .85rem; margin-bottom: 1rem; font-size: .775rem; color: #1e40af; line-height: 1.4; }
        .dark .af-permanent-notice { background: rgba(30,58,138,.25); border-color: rgba(59,130,246,.3); color: #93c5fd; }
        .af-perm-badge { display: inline-flex; align-items: center; padding: .15rem .45rem; border-radius: .35rem; background: #2563eb; color: #fff; font-weight: 800; font-size: .68rem; white-space: nowrap; }

        .af-presets-wrap { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-bottom: 1rem; padding-bottom: .85rem; border-bottom: 1px dashed #e2e8f0; }
        .dark .af-presets-wrap { border-bottom-color: #334155; }
        .af-presets-title { font-size: .775rem; font-weight: 800; color: #475569; }
        .dark .af-presets-title { color: #94a3b8; }
        .af-presets-list { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .af-preset-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .85rem; border-radius: 9999px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .775rem; font-weight: 700; cursor: pointer; transition: all .15s ease-in-out; }
        .af-preset-btn:hover { border-color: #082c50; color: #082c50; background: #f8fafc; }
        .af-preset-btn.is-active { background: #082c50 !important; color: #ffffff !important; border-color: #082c50 !important; box-shadow: 0 2px 4px rgba(8,44,80,.18); }
        .dark .af-preset-btn { background: #1e293b; border-color: #475569; color: #e2e8f0; }
        .dark .af-preset-btn.is-active { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }

        .af-cols-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        @media (max-width: 1024px) { .af-cols-grid { grid-template-columns: 1fr; } }
        .af-cols-group { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: .85rem; display: flex; flex-direction: column; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
        .dark .af-cols-group { background: #1e293b; border-color: #334155; }
        .af-cols-group.group-deposit { border-top: 3px solid #16a34a; }
        .af-cols-group.group-withdrawal { border-top: 3px solid #dc2626; }
        .af-cols-group.group-cards { border-top: 3px solid #2563eb; }

        .af-cols-group-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; padding-bottom: .5rem; border-bottom: 1px solid #f1f5f9; font-size: .825rem; font-weight: 800; }
        .dark .af-cols-group-head { border-bottom-color: #334155; }
        .group-deposit .af-cols-group-head { color: #15803d; }
        .group-withdrawal .af-cols-group-head { color: #b91c1c; }
        .group-cards .af-cols-group-head { color: #1d4ed8; }

        .af-cols-group-actions { display: flex; gap: .35rem; }
        .af-group-btn { font-size: .7rem; font-weight: 700; padding: .2rem .55rem; border-radius: .35rem; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: all .12s; }
        .af-group-btn:hover { background: #e2e8f0; color: #0f172a; }
        .dark .af-group-btn { background: #0f172a; border-color: #475569; color: #cbd5e1; }
        .dark .af-group-btn:hover { background: #334155; }

        .af-cols-list { display: flex; flex-direction: column; gap: .4rem; }
        .af-tile { display: flex; align-items: center; justify-content: space-between; padding: .5rem .75rem; border-radius: .5rem; border: 1px solid #e2e8f0; background: #f8fafc; cursor: pointer; transition: all .15s ease-in-out; user-select: none; }
        .dark .af-tile { background: #0f172a; border-color: #334155; }
        .af-tile:hover { border-color: #cbd5e1; background: #f1f5f9; }
        .dark .af-tile:hover { border-color: #475569; background: #1e293b; }
        .af-tile.is-checked { border-color: #082c50; background: #f0f7ff; }
        .dark .af-tile.is-checked { border-color: #3b82f6; background: rgba(59,130,246,.15); }
        .group-deposit .af-tile.is-checked { border-color: #86efac; background: #f0fdf4; }
        .dark .group-deposit .af-tile.is-checked { border-color: rgba(34,197,94,.4); background: rgba(34,197,94,.12); }
        .group-withdrawal .af-tile.is-checked { border-color: #fca5a5; background: #fef2f2; }
        .dark .group-withdrawal .af-tile.is-checked { border-color: rgba(239,68,68,.4); background: rgba(239,68,68,.12); }
        .group-cards .af-tile.is-checked { border-color: #93c5fd; background: #eff6ff; }
        .dark .group-cards .af-tile.is-checked { border-color: rgba(59,130,246,.4); background: rgba(59,130,246,.12); }

        .af-tile-label { font-size: .8rem; font-weight: 600; color: #334155; }
        .dark .af-tile-label { color: #e2e8f0; }
        .af-tile.is-checked .af-tile-label { font-weight: 700; color: #082c50; }
        .dark .af-tile.is-checked .af-tile-label { color: #f8fafc; }
        .group-deposit .af-tile.is-checked .af-tile-label { color: #15803d; }
        .dark .group-deposit .af-tile.is-checked .af-tile-label { color: #4ade80; }
        .group-withdrawal .af-tile.is-checked .af-tile-label { color: #b91c1c; }
        .dark .group-withdrawal .af-tile.is-checked .af-tile-label { color: #f87171; }
        .group-cards .af-tile.is-checked .af-tile-label { color: #1d4ed8; }
        .dark .group-cards .af-tile.is-checked .af-tile-label { color: #60a5fa; }

        .af-tile-check { width: 1.05rem; height: 1.05rem; accent-color: #082c50; cursor: pointer; }
        .group-deposit .af-tile-check { accent-color: #16a34a; }
        .group-withdrawal .af-tile-check { accent-color: #dc2626; }
        .group-cards .af-tile-check { accent-color: #2563eb; }

        .af-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .af-sum-card { border-radius: .75rem; padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .25rem; }
        .af-sum-card.deposits { background: #dcfce7; border: 1px solid #86efac; }
        .dark .af-sum-card.deposits { background: rgba(22,163,74,.2); border-color: rgba(22,163,74,.4); }
        .af-sum-card.withdrawals { background: #fee2e2; border: 1px solid #fca5a5; }
        .dark .af-sum-card.withdrawals { background: rgba(220,38,38,.2); border-color: rgba(220,38,38,.4); }
        .af-sum-card.tax { background: #fef3c7; border: 1px solid #fcd34d; }
        .dark .af-sum-card.tax { background: rgba(217,119,6,.2); border-color: rgba(217,119,6,.4); }
        .af-sum-card.retention { background: #ede9fe; border: 1px solid #c4b5fd; }
        .dark .af-sum-card.retention { background: rgba(124,58,237,.2); border-color: rgba(124,58,237,.4); }
        .af-sum-card.fund-share { background: #cffafe; border: 1px solid #67e8f9; }
        .dark .af-sum-card.fund-share { background: rgba(14,116,144,.2); border-color: rgba(14,116,144,.4); }
        .af-sum-card.balance { background: #dbeafe; border: 1px solid #93c5fd; }
        .dark .af-sum-card.balance { background: rgba(37,99,235,.2); border-color: rgba(37,99,235,.4); }
        .af-sum-card.gross-balance { background: #e0e7ff; border: 1px solid #a5b4fc; }
        .dark .af-sum-card.gross-balance { background: rgba(99,102,241,.2); border-color: rgba(99,102,241,.4); }
        .af-balances-grid { display: grid; gap: 1rem; margin-bottom: 1.25rem; width: 100%; }
        @media (max-width: 768px) {
            .af-balances-grid { grid-template-columns: 1fr !important; }
        }
        .af-sum-card-balance { padding: 1.15rem 1.35rem; justify-content: space-between; min-height: 88px; }
        .af-sum-card-balance .af-sum-label { font-size: .85rem; }
        .af-sum-card-balance .af-sum-value { font-size: 1.65rem; }
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
            {{-- Fund selection --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.fund') }}</label>
                <select class="af-filter-input" wire:model.live="fund_id">
                    <option value="">{{ __('asset_funds.statement.select_fund_placeholder') }}</option>
                    @foreach ($this->funds as $fund)
                        <option value="{{ $fund->id }}">{{ $fund->fund_name }} ({{ $fund->fund_number }})</option>
                    @endforeach
                </select>
            </div>

            {{-- From date --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.date_from') }}</label>
                <input type="date" class="af-filter-input" wire:model.live="date_from">
            </div>

            {{-- To date --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.date_to') }}</label>
                <input type="date" class="af-filter-input" wire:model.live="date_to">
            </div>

            {{-- Operation type --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.filter_type') }}</label>
                <select class="af-filter-input" wire:model.live="filter_type">
                    <option value="all">{{ __('asset_funds.statement.filter_types.all') }}</option>
                    <option value="deposit">{{ __('asset_funds.statement.filter_types.deposit') }}</option>
                    <option value="withdrawal">{{ __('asset_funds.statement.filter_types.withdrawal') }}</option>
                </select>
            </div>

            {{-- Statement purpose --}}
            <div>
                <label class="af-filter-label">{{ __('asset_funds.statement.fields.purpose') }}</label>
                <select class="af-filter-input" wire:model.live="preset" style="font-weight:700;">
                    @foreach ($this->preset_options as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Columns & cards selector --}}
        <details class="af-cols" @if($this->preset === 'custom') open @endif>
            <summary>
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1.25rem;height:1.25rem;color:#082c50">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>{{ __('asset_funds.statement.columns_panel.title') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;margin-inline-start:auto;">
                    <span class="af-preset-badge">
                        {{ $this->preset_options[$this->preset] ?? $this->preset }}
                    </span>
                    <span class="af-cols-count" title="{{ __('asset_funds.statement.columns_panel.selected_count') }}">
                        {{ count($this->deposit_columns) + count($this->withdrawal_columns) + count($this->summary_cards) }}
                    </span>
                </div>
            </summary>

            <div class="af-cols-body">
                {{-- Permanent columns notice --}}
                <div class="af-permanent-notice">
                    <span class="af-perm-badge">ثابتة دائماً</span>
                    <span>{{ __('asset_funds.statement.columns_panel.permanent_notice') }}</span>
                </div>

                {{-- Quick presets --}}
                <div class="af-presets-wrap">
                    <span class="af-presets-title">{{ __('asset_funds.statement.columns_panel.presets_title') }}:</span>
                    <div class="af-presets-list">
                        @php
                            $presetIcons = [
                                'all'        => '📋',
                                'tax'        => '🧾',
                                'deductions' => '📊',
                                'custom'     => '⚙️',
                            ];
                        @endphp
                        @foreach ($this->preset_options as $key => $label)
                            <button type="button" 
                                class="af-preset-btn {{ $this->preset === $key ? 'is-active' : '' }}" 
                                wire:click="applyPreset('{{ $key }}')">
                                <span>{{ $presetIcons[$key] ?? '•' }}</span>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="af-cols-grid">
                    {{-- Deposit columns --}}
                    <div class="af-cols-group group-deposit">
                        <div class="af-cols-group-head">
                            <div style="display:flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width:1.05rem;height:1.05rem"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" /></svg>
                                <span>{{ __('asset_funds.statement.columns_panel.deposit_group') }}</span>
                                <span style="font-size:.7rem;font-weight:700;color:#64748b;">({{ count($this->deposit_columns) }}/{{ count($this->deposit_column_options) }})</span>
                            </div>
                            <div class="af-cols-group-actions">
                                <button type="button" class="af-group-btn" wire:click="selectAllColumns('deposit')">{{ __('asset_funds.statement.columns_panel.select_all') }}</button>
                                <button type="button" class="af-group-btn" wire:click="clearColumns('deposit')">{{ __('asset_funds.statement.columns_panel.clear') }}</button>
                            </div>
                        </div>
                        <div class="af-cols-list">
                            @foreach ($this->deposit_column_options as $key => $label)
                                @php $checked = in_array($key, $this->deposit_columns, true); @endphp
                                <label class="af-tile {{ $checked ? 'is-checked' : '' }}">
                                    <span class="af-tile-label">{{ $label }}</span>
                                    <input type="checkbox" value="{{ $key }}" wire:model.live="deposit_columns" class="af-tile-check">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Withdrawal columns --}}
                    <div class="af-cols-group group-withdrawal">
                        <div class="af-cols-group-head">
                            <div style="display:flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width:1.05rem;height:1.05rem"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                                <span>{{ __('asset_funds.statement.columns_panel.withdrawal_group') }}</span>
                                <span style="font-size:.7rem;font-weight:700;color:#64748b;">({{ count($this->withdrawal_columns) }}/{{ count($this->withdrawal_column_options) }})</span>
                            </div>
                            <div class="af-cols-group-actions">
                                <button type="button" class="af-group-btn" wire:click="selectAllColumns('withdrawal')">{{ __('asset_funds.statement.columns_panel.select_all') }}</button>
                                <button type="button" class="af-group-btn" wire:click="clearColumns('withdrawal')">{{ __('asset_funds.statement.columns_panel.clear') }}</button>
                            </div>
                        </div>
                        <div class="af-cols-list">
                            @foreach ($this->withdrawal_column_options as $key => $label)
                                @php $checked = in_array($key, $this->withdrawal_columns, true); @endphp
                                <label class="af-tile {{ $checked ? 'is-checked' : '' }}">
                                    <span class="af-tile-label">{{ $label }}</span>
                                    <input type="checkbox" value="{{ $key }}" wire:model.live="withdrawal_columns" class="af-tile-check">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Summary cards --}}
                    <div class="af-cols-group group-cards">
                        <div class="af-cols-group-head">
                            <div style="display:flex;align-items:center;gap:.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width:1.05rem;height:1.05rem"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" /></svg>
                                <span>{{ __('asset_funds.statement.columns_panel.cards_group') }}</span>
                                <span style="font-size:.7rem;font-weight:700;color:#64748b;">({{ count($this->summary_cards) }}/{{ count($this->summary_card_options) }})</span>
                            </div>
                            <div class="af-cols-group-actions">
                                <button type="button" class="af-group-btn" wire:click="selectAllColumns('cards')">{{ __('asset_funds.statement.columns_panel.select_all') }}</button>
                                <button type="button" class="af-group-btn" wire:click="clearColumns('cards')">{{ __('asset_funds.statement.columns_panel.clear') }}</button>
                            </div>
                        </div>
                        <div class="af-cols-list">
                            @foreach ($this->summary_card_options as $key => $label)
                                @php $checked = in_array($key, $this->summary_cards, true); @endphp
                                <label class="af-tile {{ $checked ? 'is-checked' : '' }}">
                                    <span class="af-tile-label">{{ $label }}</span>
                                    <input type="checkbox" value="{{ $key }}" wire:model.live="summary_cards" class="af-tile-check">
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </details>

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
        @php
            $summary    = $this->summary;
            $depCols    = \App\Services\AssetFundStatementService::depositColumns();
            $wthCols    = \App\Services\AssetFundStatementService::withdrawalColumns();
            $cardDefs   = \App\Services\AssetFundStatementService::summaryCards();
            $depKeys    = $this->selected_deposit_columns;
            $wthKeys    = $this->selected_withdrawal_columns;
            $cardKeys   = $this->selected_cards;
            $cardStyles = [
                'total_deposits'    => ['class' => 'deposits', 'color' => '#16a34a'],
                'total_withdrawals' => ['class' => 'withdrawals', 'color' => '#dc2626'],
                'total_tax'         => ['class' => 'tax', 'color' => '#b45309'],
                'total_retention'   => ['class' => 'retention', 'color' => '#6d28d9'],
                'total_fund_share'  => ['class' => 'fund-share', 'color' => '#0891b2'],
                'net_balance'       => ['class' => 'balance', 'color' => $summary['net_balance'] >= 0 ? '#2563eb' : '#dc2626'],
                'gross_balance'     => ['class' => 'gross-balance', 'color' => ($summary['gross_balance'] ?? 0) >= 0 ? '#4338ca' : '#dc2626'],
            ];

            $isWithdrawalFilter = $this->filter_type === 'withdrawal';
            $balanceKeys        = ['net_balance', 'gross_balance'];
            $withdrawalCardKeys = $isWithdrawalFilter ? $cardKeys : [];
            $mainCardKeys       = $isWithdrawalFilter ? [] : array_values(array_filter($cardKeys, fn ($k) => ! in_array($k, $balanceKeys, true)));
            $balanceCardKeys    = $isWithdrawalFilter ? [] : array_values(array_filter($cardKeys, fn ($k) => in_array($k, $balanceKeys, true)));
        @endphp

        {{-- Withdrawal statement: all summary cards in a single row --}}
        @if (count($withdrawalCardKeys) > 0)
            <div class="af-balances-grid" style="grid-template-columns: repeat({{ count($withdrawalCardKeys) }}, minmax(0, 1fr));">
                @foreach ($withdrawalCardKeys as $cardKey)
                    @php
                        $isNetInWithdrawal = $cardKey === 'net_balance';
                        $cardLabel = $isNetInWithdrawal ? __('asset_funds.statement.summary.total_withdrawn_amount') : $cardDefs[$cardKey];
                        $cardValue = $isNetInWithdrawal ? $summary['total_withdrawals'] : $summary[$cardKey];
                        $cardClass = $isNetInWithdrawal ? 'withdrawals' : $cardStyles[$cardKey]['class'];
                        $cardColor = $isNetInWithdrawal ? '#dc2626' : $cardStyles[$cardKey]['color'];
                    @endphp
                    <div class="af-sum-card af-sum-card-balance {{ $cardClass }}">
                        <span class="af-sum-label">{{ $cardLabel }}</span>
                        <span class="af-sum-value" style="color:{{ $cardColor }}">
                            {{ number_format($cardValue, 2) }} <small style="font-size:.85rem">{{ __('asset_funds.statement.currency') }}</small>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Main operational summary cards (deposit / all) --}}
        @if (count($mainCardKeys) > 0)
            <div class="af-summary-grid">
                @foreach ($mainCardKeys as $cardKey)
                    <div class="af-sum-card {{ $cardStyles[$cardKey]['class'] }}">
                        <span class="af-sum-label">{{ $cardDefs[$cardKey] }}</span>
                        <span class="af-sum-value" style="color:{{ $cardStyles[$cardKey]['color'] }}">
                            {{ number_format($summary[$cardKey], 2) }} <small style="font-size:.75rem">{{ __('asset_funds.statement.currency') }}</small>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Full-width Balance cards (deposit / all) --}}
        @if (count($balanceCardKeys) > 0)
            <div class="af-balances-grid" style="grid-template-columns: repeat({{ count($balanceCardKeys) }}, minmax(0, 1fr));">
                @foreach ($balanceCardKeys as $cardKey)
                    <div class="af-sum-card af-sum-card-balance {{ $cardStyles[$cardKey]['class'] }}">
                        <span class="af-sum-label">{{ $cardDefs[$cardKey] }}</span>
                        <span class="af-sum-value" style="color:{{ $cardStyles[$cardKey]['color'] }}">
                            {{ number_format($summary[$cardKey], 2) }} <small style="font-size:.85rem">{{ __('asset_funds.statement.currency') }}</small>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($depKeys) === 0 && count($wthKeys) === 0 && count($cardKeys) === 0)
            <div class="af-stmt-card">
                <p class="no-data">{{ __('asset_funds.statement.columns_panel.none_selected') }}</p>
            </div>
        @endif

        {{-- 1. Deposit vouchers table --}}
        @if (($this->filter_type === 'all' || $this->filter_type === 'deposit') && count($depKeys) > 0)
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
                                    @foreach ($depKeys as $key)
                                        <th @if (! empty($depCols[$key]['width'])) style="width:{{ $depCols[$key]['width'] }}" @endif>{{ $depCols[$key]['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->deposit_vouchers as $i => $row)
                                    <tr>
                                        @foreach ($depKeys as $key)
                                            <td>{!! $depCols[$key]['html']($row, $i) !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    @foreach ($depKeys as $key)
                                        <td>
                                            @if ($loop->first)
                                                {{ __('asset_funds.statement.cols.totals') }}
                                                @if (! empty($depCols[$key]['total']))
                                                    <br>{!! $depCols[$key]['total']($depSum) !!}
                                                @endif
                                            @elseif (! empty($depCols[$key]['total']))
                                                {!! $depCols[$key]['total']($depSum) !!}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="no-data">{{ __('asset_funds.statement.no_deposits') }}</p>
                @endif
            </div>
        @endif

        {{-- 2. Withdrawal vouchers table --}}
        @if (($this->filter_type === 'all' || $this->filter_type === 'withdrawal') && count($wthKeys) > 0)
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
                                    @foreach ($wthKeys as $key)
                                        <th @if (! empty($wthCols[$key]['width'])) style="width:{{ $wthCols[$key]['width'] }}" @endif>{{ $wthCols[$key]['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->withdrawal_vouchers as $i => $row)
                                    <tr>
                                        @foreach ($wthKeys as $key)
                                            <td>{!! $wthCols[$key]['html']($row, $i) !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    @foreach ($wthKeys as $key)
                                        <td>
                                            @if ($loop->first)
                                                {{ __('asset_funds.statement.cols.totals') }}
                                                @if (! empty($wthCols[$key]['total']))
                                                    <br>{!! $wthCols[$key]['total']($wthSum) !!}
                                                @endif
                                            @elseif (! empty($wthCols[$key]['total']))
                                                {!! $wthCols[$key]['total']($wthSum) !!}
                                            @endif
                                        </td>
                                    @endforeach
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
