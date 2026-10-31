<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .report-header-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .report-header-info { display: flex; align-items: center; gap: 0.875rem; }
        .report-header-icon { width: 48px; height: 48px; border-radius: 0.75rem; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bfdbfe; box-shadow: 0 1px 2px rgba(37,99,235,0.1); }
        .dark .report-header-icon { background: rgba(37, 99, 235, 0.2); color: #60a5fa; border-color: rgba(37, 99, 235, 0.4); }
        .report-header-title { font-size: 1.5rem; font-weight: 800; line-height: 1.2; color: #0f172a; }
        .dark .report-header-title { color: #f8fafc; }
        .report-header-sub { font-size: 0.875rem; color: #475569; margin-top: 0.25rem; }
        .dark .report-header-sub { color: #94a3b8; }

        /* Stats Grid */
        .report-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
        .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; }
        .report-stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .report-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 38px; height: 38px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .report-stat-value { font-size: 1.875rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem; color: #0f172a; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.8125rem; font-weight: 600; display: flex; align-items: center; gap: 0.375rem; }

        /* Filters */
        .report-filter-label { font-size: 0.8125rem; font-weight: 700; color: #1e293b; }
        .dark .report-filter-label { color: #e2e8f0; }
        .report-filter-input { width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.875rem; outline: none; box-sizing: border-box; transition: border-color 0.15s; }
        .dark .report-filter-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-filter-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .report-search-inner { position: relative; width: 100%; }
        .report-search-input { width: 100%; padding: 0.5rem 2.25rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.875rem; outline: none; box-sizing: border-box; }
        .dark .report-search-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-search-icon { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 0.75rem; color: #64748b; pointer-events: none; }
        .report-search-clear { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-end: 0.75rem; color: #64748b; cursor: pointer; background: none; border: none; }
        .ism-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); }
        .ism-export-btn:hover { background: #1e293b; }
        .dark .ism-export-btn { background: #2563eb; }
        .dark .ism-export-btn:hover { background: #1d4ed8; }

        /* Table */
        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .report-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 850px; }
        .report-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .report-table thead tr { background-color: #0f172a; border-color: #334155; }
        .report-table th { padding: 0.875rem 1rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .report-table th { color: #cbd5e1; }
        .report-table th button { background: none; border: none; cursor: pointer; font-weight: inherit; font-size: inherit; color: inherit; display: inline-flex; align-items: center; gap: 0.375rem; padding: 0; }
        .report-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .report-table tbody tr { border-bottom-color: #334155; }
        .report-table tbody tr:hover { background-color: #f8fafc; }
        .dark .report-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .report-table td { padding: 0.75rem 1rem; white-space: nowrap; color: #0f172a; }
        .dark .report-table td { color: #f1f5f9; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }
        .report-table tfoot tr { background-color: #ecfdf5; border-top: 2px solid #a7f3d0; }
        .dark .report-table tfoot tr { background-color: rgba(5, 150, 105, 0.18); border-top-color: #065f46; }
        .report-table tfoot td { font-weight: 800; color: #065f46; }
        .dark .report-table tfoot td { color: #6ee7b7; }
        .ism-total-paid { color: #059669; }
        .dark .ism-total-paid { color: #34d399; }
        .ism-cell-muted { color: #475569; }
        .dark .ism-cell-muted { color: #94a3b8; }
        .ism-cell-text { color: #334155; }
        .dark .ism-cell-text { color: #e2e8f0; }

        /* Icon strict sizing */
        svg { max-width: 100%; }
        .report-icon-xs { width: 14px !important; height: 14px !important; min-width: 14px !important; max-height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; min-width: 16px !important; max-height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; min-width: 20px !important; max-height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; min-width: 24px !important; max-height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }

        /* Details button inside summary rows */
        .ism-details-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .ism-details-btn:hover { background: #dbeafe; border-color: #93c5fd; }

        /* Details modal */
        .ism-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .ism-modal { background: #ffffff; border-radius: 1rem; width: 100%; max-width: 1100px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; }
        .dark .ism-modal { background: #1e293b; }
        .ism-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
        .dark .ism-modal-header { border-bottom-color: #334155; }
        .ism-modal-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
        .dark .ism-modal-title { color: #f8fafc; }
        .ism-modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; }
        .dark .ism-modal-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .ism-modal-close:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .ism-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .ism-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; }
        .ism-summary-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.625rem; padding: 0.65rem 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .dark .ism-summary-cell { background: #0f172a; border-color: #334155; }
        .ism-summary-label { font-size: 0.72rem; font-weight: 700; color: #64748b; }
        .dark .ism-summary-label { color: #94a3b8; }
        .ism-summary-value { font-size: 0.9rem; font-weight: 800; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dark .ism-summary-value { color: #f1f5f9; }
        .ism-modal-footer { display: flex; justify-content: flex-start; padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; flex-shrink: 0; }
        .dark .ism-modal-footer { border-top-color: #334155; }
        .ism-tabs { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
        .dark .ism-tabs { border-bottom-color: #334155; }
        .ism-tab { padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; }
        .dark .ism-tab { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .ism-tab:hover { border-color: #bfdbfe; color: #2563eb; }
        .dark .ism-tab:hover { border-color: #1e40af; color: #60a5fa; }
        .ism-tab.active { color: #ffffff; }
        .ism-tab.active:hover { color: #ffffff; }
        .ism-tab--all.active { background: #2563eb; border-color: #2563eb; }
        .ism-tab--installments.active { background: #4f46e5; border-color: #4f46e5; }
        .ism-tab--payments.active { background: #059669; border-color: #059669; }

        /* Loading overlay — visibility driven by wire:loading.class so Livewire's
           inline display toggle can't override the flex centering. */
        .ism-loading-overlay { position: fixed; top: 0; right: 0; bottom: 0; left: 0; width: 100%; height: 100%; z-index: 110; background: rgba(255, 255, 255, 0.55); display: none; backdrop-filter: blur(1px); }
        .ism-loading-overlay.ism-loading-active { display: flex; align-items: center; justify-content: center; }
        .dark .ism-loading-overlay { background: rgba(15, 23, 42, 0.55); }
        .ism-spinner { width: 42px; height: 42px; border: 4px solid #bfdbfe; border-top-color: #2563eb; border-radius: 9999px; animation: ism-spin 0.8s linear infinite; margin: auto; flex-shrink: 0; }
        @keyframes ism-spin { to { transform: rotate(360deg); } }

        [x-cloak] { display: none !important; }
    </style>

    <div class="report-page-container">
        {{-- Stats Cards (5 cards matching mockup) --}}
        @php
            $stats = $this->stats;
            $currency = __('finance.installments.summary.stats.currency');
            $cards = [
                ['title' => __('finance.installments.summary.stats.total'), 'value' => number_format($stats['total'] ?? 0, 2), 'subtitle' => $currency, 'icon' => 'heroicon-o-banknotes', 'bg' => '#fef3c7', 'text' => '#d97706', 'border' => '#fde68a'],
                ['title' => __('finance.installments.summary.stats.paid'), 'value' => number_format($stats['paid'], 2), 'subtitle' => $currency, 'icon' => 'heroicon-o-check-badge', 'bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0'],
                ['title' => __('finance.installments.summary.stats.remaining'), 'value' => number_format($stats['remaining'], 2), 'subtitle' => $currency, 'icon' => 'heroicon-o-currency-dollar', 'bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fcd34d'],
                ['title' => __('finance.installments.summary.stats.installments'), 'value' => number_format($stats['installments']), 'icon' => 'heroicon-o-credit-card', 'bg' => '#eef2ff', 'text' => '#4f46e5', 'border' => '#c7d2fe'],
                ['title' => __('finance.installments.summary.stats.beneficiaries'), 'value' => number_format($stats['beneficiaries']), 'icon' => 'heroicon-o-users', 'bg' => '#fff1f2', 'text' => '#e11d48', 'border' => '#fecdd3'],
            ];
        @endphp
        <div class="report-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            @foreach($cards as $card)
                <div class="report-stat-card">
                    <div class="report-stat-top">
                        <span class="report-stat-title">{{ $card['title'] }}</span>
                        <div class="report-stat-icon-wrapper" style="background-color: {{ $card['bg'] }}; color: {{ $card['text'] }}; border: 1px solid {{ $card['border'] }};">
                            <x-dynamic-component :component="$card['icon']" class="report-icon-md" />
                        </div>
                    </div>
                    <div>
                        <div class="report-stat-value">{{ $card['value'] }}</div>
                        @if(isset($card['subtitle']))
                            <div class="report-stat-subtitle" style="color: {{ $card['text'] }};">
                                <span>{{ $card['subtitle'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Filters (Clean row layout matching mockup) --}}
        <x-filament::section icon="heroicon-o-funnel">
            <x-slot name="heading">
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>{{ __('finance.installments.summary.filters.search') }}</span>
                    @if($this->hasActiveFilters())
                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                            {{ __('finance.installments.summary.filters.active') }}
                        </span>
                    @endif
                </div>
            </x-slot>

            <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem;">
                {{-- Search --}}
                <div style="flex: 2; min-width: 240px;">
                    <label class="report-filter-label">{{ __('finance.installments.summary.filters.search') }}</label>
                    <div class="report-search-inner">
                        <div class="report-search-icon"><x-heroicon-m-magnifying-glass class="report-icon-sm" /></div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('finance.installments.summary.filters.search_placeholder') }}" class="report-search-input">
                        @if($search)
                            <button type="button" wire:click="$set('search', '')" class="report-search-clear"><x-heroicon-m-x-mark class="report-icon-sm" /></button>
                        @endif
                    </div>
                </div>

                {{-- Company --}}
                <div style="flex: 1.2; min-width: 170px;">
                    <label class="report-filter-label">{{ __('finance.installments.fields.company') }}</label>
                    <select wire:model.live="companyId" class="report-filter-input">
                        <option value="">{{ __('finance.installments.summary.filters.all_companies') }}</option>
                        @foreach($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Installment / Transaction Type --}}
                <div style="flex: 1; min-width: 140px;">
                    <label class="report-filter-label">{{ __('finance.installments.summary.filters.installment_type') }}</label>
                    <select wire:model.live="transactionType" class="report-filter-input">
                        <option value="">{{ __('finance.installments.summary.filters.all_types') }}</option>
                        <option value="installment">{{ __('finance.installments.toggle_options.installment') }}</option>
                        <option value="advance">{{ __('finance.installments.toggle_options.advance') }}</option>
                    </select>
                </div>

                {{-- Status --}}
                <div style="flex: 1; min-width: 140px;">
                    <label class="report-filter-label">{{ __('finance.installments.fields.status') }}</label>
                    <select wire:model.live="status" class="report-filter-input">
                        <option value="">{{ __('finance.installments.summary.filters.all_statuses') }}</option>
                        @foreach($this->statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset Filters button (if active) --}}
                @if($this->hasActiveFilters())
                    <div>
                        <x-filament::button wire:click="resetFilters" color="gray" size="sm" icon="heroicon-m-arrow-path">
                            {{ __('finance.installments.summary.filters.reset') }}
                        </x-filament::button>
                    </div>
                @endif

                {{-- Export Excel Button --}}
                <div>
                    <button type="button" wire:click="exportCsv" class="ism-export-btn">
                        <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                        <span>{{ __('finance.installments.summary.filters.export_excel') }}</span>
                    </button>
                </div>
            </div>
        </x-filament::section>

        {{-- Summary Table --}}
        @php $summary = $this->summary; @endphp
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>{{ __('finance.installments.summary.columns.name') }}</th>
                        <th>{{ __('finance.installments.summary.columns.national_id') }}</th>
                        <th>{{ __('finance.installments.summary.columns.company') }}</th>
                        <th>
                            <button type="button" wire:click="sortBy('installments_count')" style="{{ $sortColumn === 'installments_count' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.installments.summary.columns.count') }}</span>
                                @if($sortColumn === 'installments_count')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('total_sum')" style="{{ $sortColumn === 'total_sum' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.installments.summary.columns.total') }}</span>
                                @if($sortColumn === 'total_sum')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('paid_sum')" style="{{ $sortColumn === 'paid_sum' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.installments.summary.columns.paid') }}</span>
                                @if($sortColumn === 'paid_sum')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('remaining_sum')" style="{{ $sortColumn === 'remaining_sum' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.installments.summary.columns.remaining') }}</span>
                                @if($sortColumn === 'remaining_sum')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('finance.installments.summary.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr>
                            <td style="font-weight: 700;">{{ ($summary->firstItem() ?? 0) + $loop->index }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: flex-start;">
                                    <span style="font-weight: 800;">{{ $this->nameOf($row) }}</span>
                                    <x-filament::badge :color="$row->isExternal() ? 'warning' : 'info'">
                                        {{ $row->isExternal() ? __('finance.installments.summary.beneficiary_types.external') : __('finance.installments.summary.beneficiary_types.internal') }}
                                    </x-filament::badge>
                                </div>
                            </td>
                            <td style="font-family: monospace;">{{ $this->nationalIdOf($row) ?? '—' }}</td>
                            <td>{{ $this->companyOf($row) ?? '—' }}</td>
                            <td>{{ (int) $row->installments_count }}</td>
                            <td><span style="font-family: monospace; font-weight: 600;">{{ number_format((float) $row->total_sum, 2) }}</span></td>
                            <td><span style="font-family: monospace; font-weight: 600; color: #059669;">{{ number_format((float) $row->paid_sum, 2) }}</span></td>
                            <td><span style="font-family: monospace; font-weight: 800; color: #d97706;">{{ number_format((float) $row->remaining_sum, 2) }}</span></td>
                            <td>
                                <button type="button" wire:click="openDetails({{ $row->row_id }})" class="ism-details-btn">
                                    <x-heroicon-m-eye class="report-icon-sm" />
                                    <span>{{ __('finance.installments.summary.view_details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 3.5rem 1.5rem; text-align: center; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div style="width: 52px; height: 52px; border-radius: 9999px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin-bottom: 0.875rem;">
                                        <x-heroicon-o-document-magnifying-glass class="report-icon-lg" />
                                    </div>
                                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ __('finance.installments.summary.empty') }}</h3>
                                    <p style="font-size: 0.8125rem; color: #64748b; max-width: 24rem;">{{ __('finance.installments.summary.empty_hint') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($summary->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$summary" :page-options="[10, 25, 50, 100]" current-page-option-property="perPage" />
                </div>
            @endif
        </div>
    </div>

    {{-- Loading overlay while opening the details modal --}}
    <div class="ism-loading-overlay" wire:loading.class="ism-loading-active" wire:target="openDetails">
        <div class="ism-spinner" aria-hidden="true"></div>
    </div>

    {{-- Beneficiary Installments Details Modal --}}
    @if($this->details)
        <div class="ism-overlay" x-data @keydown.escape.window="$wire.closeDetails()" @click.self="$wire.closeDetails()">
            <div class="ism-modal" role="dialog" aria-modal="true" aria-label="{{ __('finance.installments.summary.modal.title') }}">
                <div class="ism-modal-header">
                    <h3 class="ism-modal-title">{{ __('finance.installments.summary.modal.title') }}</h3>
                    <button type="button" wire:click="closeDetails" class="ism-modal-close" aria-label="{{ __('finance.installments.summary.modal.close') }}">
                        <x-heroicon-m-x-mark class="report-icon-sm" />
                    </button>
                </div>

                <div class="ism-modal-body">
                    {{-- Beneficiary summary strip --}}
                    <div class="ism-summary-grid">
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.name') }}</span>
                            <span class="ism-summary-value">{{ $this->details['name'] ?? '—' }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.national_id') }}</span>
                            <span class="ism-summary-value">{{ $this->details['national_id'] ?? '—' }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.company') }}</span>
                            <span class="ism-summary-value">{{ $this->details['company'] ?? '—' }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.total') }}</span>
                            <span class="ism-summary-value">{{ number_format($this->details['total'], 2) }} {{ $currency }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.paid') }}</span>
                            <span class="ism-summary-value" style="color: #059669;">{{ number_format($this->details['paid'], 2) }} {{ $currency }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.remaining') }}</span>
                            <span class="ism-summary-value" style="color: #d97706;">{{ number_format($this->details['remaining'], 2) }} {{ $currency }}</span>
                        </div>
                        <div class="ism-summary-cell">
                            <span class="ism-summary-label">{{ __('finance.installments.summary.columns.count') }}</span>
                            <span class="ism-summary-value">{{ (int) $this->details['count'] }}</span>
                        </div>
                    </div>

                    {{-- Tabs: All transactions / Installments / Payments --}}
                    @php
                        $allTransactions = $this->details['transactions'] ?? collect();
                        $installmentsCount = $this->details['count'] ?? 0;
                        $paymentsCount = $this->details['payments_count'] ?? 0;
                        $totalTxCount = $allTransactions->count();

                        $displayedItems = match($this->detailsTab) {
                            'installments' => $allTransactions->where('type', 'installment')->values(),
                            'payments' => $allTransactions->where('type', 'payment')->values(),
                            default => $allTransactions,
                        };
                    @endphp

                    <div class="ism-tabs">
                        <button type="button"
                                wire:click="setDetailsTab('all')"
                                class="ism-tab ism-tab--all {{ $detailsTab === 'all' ? 'active' : '' }}">
                            <span>{{ __('finance.installments.summary.modal.tab_all') }} ({{ $totalTxCount }})</span>
                        </button>
                        <button type="button"
                                wire:click="setDetailsTab('installments')"
                                class="ism-tab ism-tab--installments {{ $detailsTab === 'installments' ? 'active' : '' }}">
                            <span>{{ __('finance.installments.summary.modal.tab_installments') }} ({{ $installmentsCount }})</span>
                        </button>
                        <button type="button"
                                wire:click="setDetailsTab('payments')"
                                class="ism-tab ism-tab--payments {{ $detailsTab === 'payments' ? 'active' : '' }}">
                            <span>{{ __('finance.installments.summary.modal.tab_payments') }} ({{ $paymentsCount }})</span>
                        </button>
                    </div>

                    {{-- Ledger table mirroring the source statement:
                         # | Date | Movement type | Operator | Charges | Paid amount | Description --}}
                    <div class="report-table-container">
                        <table class="report-table" style="min-width: 950px;">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>{{ __('finance.installments.summary.modal.date') }}</th>
                                    <th>{{ __('finance.installments.summary.modal.type') }}</th>
                                    <th>{{ __('finance.installments.summary.modal.operator') }}</th>
                                    <th>{{ __('finance.installments.summary.modal.charges_amount') }}</th>
                                    <th>{{ __('finance.installments.summary.modal.paid_amount') }}</th>
                                    <th>{{ __('finance.installments.summary.modal.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($displayedItems as $index => $item)
                                    @php
                                        $isPay = ($item['type'] ?? '') === 'payment';
                                        $settled = ($item['status'] ?? '') === 'settled';
                                        $isApproved = (bool) ($item['is_approved'] ?? true);
                                    @endphp
                                    <tr style="{{ $isPay ? 'background-color: rgba(16, 185, 129, 0.04);' : '' }}">
                                        <td style="font-weight: 700;">{{ $index + 1 }}</td>
                                        <td>{{ $item['date'] ? (\Carbon\Carbon::parse($item['date'])->format('Y-m-d')) : '—' }}</td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                                                <x-filament::badge :color="$isPay ? 'success' : 'primary'">
                                                    {{ $item['item_type'] ?? ($isPay ? __('finance.installments.summary.modal.type_payment') : __('finance.installments.types.installment')) }}
                                                </x-filament::badge>
                                                @if($isPay && ! $isApproved)
                                                    <x-filament::badge color="warning">
                                                        {{ __('finance.installments.summary.modal.unapproved_payment') }}
                                                    </x-filament::badge>
                                                @elseif(! $isPay)
                                                    <x-filament::badge :color="$settled ? 'success' : 'danger'">
                                                        {{ $settled ? __('finance.installments.summary.modal.settled') : __('finance.installments.summary.modal.pending') }}
                                                    </x-filament::badge>
                                                @endif
                                            </div>
                                            @if(! $isPay && ! empty($item['due_date']))
                                                <div class="ism-cell-muted" style="font-size: 0.7rem; margin-top: 0.2rem;">
                                                    {{ __('finance.installments.summary.modal.due_date') }}: {{ \Carbon\Carbon::parse($item['due_date'])->format('Y-m-d') }}
                                                </div>
                                            @endif
                                            <div class="ism-cell-muted" style="font-size: 0.7rem; margin-top: 0.15rem;">
                                                {{ $item['number'] }}
                                            </div>
                                        </td>
                                        <td class="ism-cell-muted">{{ $item['operator_name'] ?: '—' }}</td>
                                        <td>
                                            <span style="font-family: monospace; font-weight: 600;">
                                                {{ number_format((float) ($item['charges_amount'] ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="ism-total-paid" style="font-family: monospace; font-weight: 600; {{ $isPay ? '' : 'color: #64748b;' }}">
                                                {{ number_format((float) ($item['paid_amount'] ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="ism-cell-text">{{ $item['description'] ?? '—' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                                            {{ __('finance.installments.summary.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">{{ __('finance.installments.summary.modal.totals') }}</td>
                                    <td>
                                        <span style="font-family: monospace; font-weight: 800;">
                                            {{ number_format((float) $displayedItems->sum('charges_amount'), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ism-total-paid" style="font-family: monospace; font-weight: 800;">
                                            {{ number_format((float) $displayedItems->sum('paid_amount'), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ism-cell-muted" style="font-weight: 700;">
                                            {{ __('finance.installments.summary.modal.total_remaining') }}:
                                            {{ number_format((float) $this->details['remaining'], 2) }} {{ $currency }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="ism-modal-footer">
                    <x-filament::button wire:click="closeDetails" color="gray" size="sm">
                        {{ __('finance.installments.summary.modal.close') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>