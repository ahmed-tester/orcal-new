<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .report-header-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }

        /* Stats Grid */
        .report-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; }
        .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; }
        .report-stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .report-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 38px; height: 38px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .report-stat-value { font-size: 1.65rem; font-weight: 800; line-height: 1.1; margin-bottom: 0.35rem; color: #0f172a; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.8125rem; font-weight: 600; display: flex; align-items: center; gap: 0.375rem; }

        /* Filters */
        .report-filter-label { font-size: 0.8125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; display: block; }
        .dark .report-filter-label { color: #e2e8f0; }
        .report-filter-input { width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.85rem; outline: none; box-sizing: border-box; transition: border-color 0.15s; }
        .dark .report-filter-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-filter-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .report-search-inner { position: relative; width: 100%; }
        .report-search-input { width: 100%; padding: 0.5rem 2.25rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.85rem; outline: none; box-sizing: border-box; }
        .dark .report-search-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-search-icon { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 0.75rem; color: #64748b; pointer-events: none; }
        .report-search-clear { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-end: 0.75rem; color: #64748b; cursor: pointer; background: none; border: none; }
        .ism-export-btn { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.45rem 0.95rem; border-radius: 0.5rem; background: #082c50; color: #ffffff; font-weight: 700; font-size: 0.8rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08); white-space: nowrap; flex-shrink: 0; line-height: 1; }
        .ism-export-btn:hover { background: #0c4277; }
        .dark .ism-export-btn { background: #2563eb; }
        .dark .ism-export-btn:hover { background: #1d4ed8; }

        /* Table */
        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .report-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; }
        .report-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .report-table thead tr { background-color: #0f172a; border-color: #334155; }
        .report-table th { padding: 0.875rem 0.875rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .report-table th { color: #cbd5e1; }
        .report-table th button { background: none; border: none; cursor: pointer; font-weight: inherit; font-size: inherit; color: inherit; display: inline-flex; align-items: center; gap: 0.35rem; padding: 0; }
        .report-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .report-table tbody tr { border-bottom-color: #334155; }
        .report-table tbody tr:hover { background-color: #f8fafc; }
        .dark .report-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .report-table td { padding: 0.75rem 0.875rem; color: #0f172a; vertical-align: middle; }
        .dark .report-table td { color: #f1f5f9; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }
        .report-table-pagination svg { width: 1.25rem !important; height: 1.25rem !important; }

        /* Icons */
        .report-icon-xs { width: 14px !important; height: 14px !important; min-width: 14px !important; max-height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; min-width: 16px !important; max-height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; min-width: 20px !important; max-height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; min-width: 24px !important; max-height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }

        /* Action buttons */
        .ism-details-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
        .ism-details-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .dark .ism-details-btn { background: rgba(37,99,235,0.2); border-color: rgba(37,99,235,0.4); color: #93c5fd; }

        .ism-print-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.6rem; border-radius: 0.4rem; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap; }
        .ism-print-btn:hover { background: #ffedd5; border-color: #fdba74; }

        .ism-edit-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.3rem 0.5rem; border-radius: 0.4rem; border: 1px solid #e2e8f0; background: #ffffff; color: #475569; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: all 0.15s; }
        .ism-edit-btn:hover { background: #f1f5f9; color: #0f172a; }

        /* Modal */
        .ism-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1.25rem; backdrop-filter: blur(2px); }
        .ism-modal { background: #ffffff; border-radius: 1rem; width: 100%; max-width: 1050px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; }
        .dark .ism-modal { background: #1e293b; }
        .ism-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.15rem 1.25rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
        .dark .ism-modal-header { border-bottom-color: #334155; }
        .ism-modal-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; }
        .dark .ism-modal-title { color: #f8fafc; }
        .ism-modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; }
        .dark .ism-modal-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .ism-modal-close:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .ism-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .ism-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; }
        .ism-summary-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.625rem; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .dark .ism-summary-cell { background: #0f172a; border-color: #334155; }
        .ism-summary-label { font-size: 0.75rem; font-weight: 700; color: #64748b; }
        .dark .ism-summary-label { color: #94a3b8; }
        .ism-summary-value { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .dark .ism-summary-value { color: #f1f5f9; }
        .ism-modal-footer { display: flex; align-items: center; justify-content: flex-end; padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; flex-shrink: 0; }
        .dark .ism-modal-footer { border-top-color: #334155; }

        [x-cloak] { display: none !important; }
    </style>

    <div class="report-page-container">
        {{-- Stats Cards (5 Cards for Treasury Overview) --}}
        @php
            $stats = $this->stats;
            $currency = __('finance.treasury_transactions.summary.stats.currency');
            $cards = [
                [
                    'title' => __('finance.treasury_transactions.summary.stats.current_balance'),
                    'value' => number_format($stats['current_balance'] ?? 0, 2),
                    'subtitle' => $currency,
                    'icon' => 'heroicon-o-banknotes',
                    'bg' => '#eff6ff',
                    'text' => '#1d4ed8',
                    'border' => '#bfdbfe',
                ],
                [
                    'title' => __('finance.treasury_transactions.summary.stats.total_outgoing'),
                    'value' => number_format($stats['total_outgoing'] ?? 0, 2),
                    'subtitle' => $currency,
                    'icon' => 'heroicon-o-arrow-up-right',
                    'bg' => '#fef2f2',
                    'text' => '#dc2626',
                    'border' => '#fecaca',
                ],
                [
                    'title' => __('finance.treasury_transactions.summary.stats.total_incoming'),
                    'value' => number_format($stats['total_incoming'] ?? 0, 2),
                    'subtitle' => $currency,
                    'icon' => 'heroicon-o-arrow-down-left',
                    'bg' => '#ecfdf5',
                    'text' => '#059669',
                    'border' => '#a7f3d0',
                ],
                [
                    'title' => __('finance.treasury_transactions.summary.stats.beneficiaries'),
                    'value' => number_format($stats['beneficiaries_count'] ?? 0),
                    'icon' => 'heroicon-o-users',
                    'bg' => '#fff1f2',
                    'text' => '#e11d48',
                    'border' => '#fecdd3',
                ],
                [
                    'title' => __('finance.treasury_transactions.summary.stats.total_count'),
                    'value' => number_format($stats['total_count'] ?? 0),
                    'icon' => 'heroicon-o-document-text',
                    'bg' => '#f5f3ff',
                    'text' => '#7c3aed',
                    'border' => '#ddd6fe',
                ],
            ];
        @endphp
        <div class="report-stats-grid">
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

        {{-- Unified Reusable Filter Bar --}}
        <x-filter-bar
            :title="__('finance.treasury_transactions.summary.filters.search')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('finance.treasury_transactions.fields.company')"
            :multiple-company="true"
            :show-dates="true"
            :date-from-label="__('finance.treasury_transactions.summary.filters.date_from')"
            :date-to-label="__('finance.treasury_transactions.summary.filters.date_to')"
            :show-search="true"
            :search-placeholder="__('finance.treasury_transactions.summary.filters.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <button type="button" wire:click="exportCsv" class="ism-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('finance.treasury_transactions.summary.filters.export_excel') }}</span>
                </button>
            </x-slot>

            <x-slot name="append">
                {{-- Transaction Type --}}
                <x-filter-select
                    wire:model.live="transactionType"
                    :label="__('finance.treasury_transactions.fields.transaction_type')"
                    :placeholder="__('finance.treasury_transactions.summary.filters.all_types')"
                    :options="[
                        'outgoing' => __('finance.treasury_transactions.types.outgoing'),
                        'incoming' => __('finance.treasury_transactions.types.incoming'),
                    ]"
                    :multiple="true"
                    :searchable="true"
                />

                {{-- Payment Collection Item --}}
                <x-filter-select
                    wire:model.live="paymentItem"
                    :label="__('finance.treasury_transactions.summary.filters.item')"
                    :placeholder="__('finance.treasury_transactions.summary.filters.all_items')"
                    :options="$this->paymentItems"
                    :multiple="true"
                    :searchable="true"
                />
            </x-slot>
        </x-filter-bar>

        {{-- Grouped Beneficiaries Table: ONE ROW PER EMPLOYEE/BENEFICIARY --}}
        @php $summary = $this->summary; @endphp
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th>{{ __('finance.treasury_transactions.summary.columns.beneficiary') }}</th>
                        @if(count($this->companies) > 1)
                            <th>{{ __('finance.treasury_transactions.summary.columns.company') }}</th>
                        @endif
                        <th>
                            <button type="button" wire:click="sortBy('transactions_count')" style="{{ $sortColumn === 'transactions_count' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.treasury_transactions.summary.columns.vouchers_count') }}</span>
                                @if($sortColumn === 'transactions_count')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('total_outgoing')" style="{{ $sortColumn === 'total_outgoing' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.treasury_transactions.summary.columns.total_outgoing') }}</span>
                                @if($sortColumn === 'total_outgoing')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('total_incoming')" style="{{ $sortColumn === 'total_incoming' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.treasury_transactions.summary.columns.total_incoming') }}</span>
                                @if($sortColumn === 'total_incoming')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('net_amount')" style="{{ $sortColumn === 'net_amount' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.treasury_transactions.summary.columns.net_amount') }}</span>
                                @if($sortColumn === 'net_amount')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('last_transaction_date')" style="{{ $sortColumn === 'last_transaction_date' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.treasury_transactions.summary.columns.last_date') }}</span>
                                @if($sortColumn === 'last_transaction_date')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('finance.treasury_transactions.summary.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        @php
                            $outgoing = (float) $row->total_outgoing;
                            $incoming = (float) $row->total_incoming;
                            $net = (float) $row->net_amount;
                        @endphp
                        <tr>
                            <td style="font-weight: 700; color: #64748b;">
                                {{ ($summary->firstItem() ?? 0) + $loop->index }}
                            </td>

                            {{-- Beneficiary Name --}}
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                    <span style="font-weight: 800; font-size: 0.95rem; color: #0f172a;">
                                        {{ $row->beneficiary_name }}
                                    </span>
                                    @if(count($this->companies) <= 1 && $row->company)
                                        <span style="font-size: 0.75rem; color: #64748b;">
                                            {{ $row->company->trade_name }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Company (multi) --}}
                            @if(count($this->companies) > 1)
                                <td>
                                    <span style="font-weight: 600; font-size: 0.82rem; color: #334155;">
                                        {{ $row->company?->trade_name ?? '—' }}
                                    </span>
                                </td>
                            @endif

                            {{-- Vouchers Count --}}
                            <td>
                                <x-filament::badge color="primary">
                                    {{ $row->transactions_count }} {{ __('finance.treasury_transactions.summary.columns.vouchers_count') }}
                                </x-filament::badge>
                            </td>

                            {{-- Total Outgoing (صرف) --}}
                            <td>
                                @if($outgoing > 0)
                                    <span style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: #dc2626;">
                                        − {{ number_format($outgoing, 2) }} {{ $currency }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-family: monospace;">0.00</span>
                                @endif
                            </td>

                            {{-- Total Incoming (قبض) --}}
                            <td>
                                @if($incoming > 0)
                                    <span style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: #059669;">
                                        + {{ number_format($incoming, 2) }} {{ $currency }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-family: monospace;">0.00</span>
                                @endif
                            </td>

                            {{-- Net Amount --}}
                            <td>
                                <span style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: {{ $net >= 0 ? '#059669' : '#d97706' }};">
                                    {{ $net > 0 ? '+' : '' }}{{ number_format($net, 2) }} {{ $currency }}
                                </span>
                            </td>

                            {{-- Last Transaction Date --}}
                            <td>
                                <span style="font-size: 0.82rem; color: #475569;">
                                    {{ $row->last_transaction_date ? \Carbon\Carbon::parse($row->last_transaction_date)->format('Y/m/d') : '—' }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td>
                                <button type="button" wire:click="openDetails({{ $row->row_id }})" class="ism-details-btn">
                                    <x-heroicon-m-eye class="report-icon-sm" />
                                    <span>{{ __('finance.treasury_transactions.summary.view_details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->companies) > 1 ? 9 : 8 }}" style="padding: 3.5rem 1.5rem; text-align: center; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div style="width: 52px; height: 52px; border-radius: 9999px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin-bottom: 0.875rem;">
                                        <x-heroicon-o-users class="report-icon-lg" />
                                    </div>
                                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">لا يوجد مستفيدون أو حركات مسجلة</h3>
                                    <p style="font-size: 0.8125rem; color: #64748b; max-width: 24rem;">جرّب تعديل الفلاتر أو تاريخ الفترة، أو سجّل حركة جديدة.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Styled Filament Pagination --}}
            @if($summary->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$summary" :page-options="[10, 15, 25, 50, 100]" current-page-option-property="perPage" />
                </div>
            @endif
        </div>

        {{-- Beneficiary Details Modal: All Vouchers and Transactions for this Person --}}
        @if($beneficiaryDetails)
            <div class="ism-overlay" wire:click.self="closeDetails">
                <div class="ism-modal">
                    {{-- Modal Header --}}
                    <div class="ism-modal-header">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 42px; height: 42px; border-radius: 0.6rem; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-heroicon-o-user class="report-icon-md" />
                            </div>
                            <div>
                                <h3 class="ism-modal-title">
                                    {{ __('finance.treasury_transactions.summary.modal.title') }}: {{ $beneficiaryDetails['beneficiary_name'] }}
                                </h3>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.2rem;">
                                    {{ $beneficiaryDetails['company_name'] }} • {{ $beneficiaryDetails['transactions_count'] }} {{ __('finance.treasury_transactions.summary.columns.vouchers_count') }}
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDetails" class="ism-modal-close">
                            <x-heroicon-m-x-mark class="report-icon-sm" />
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="ism-modal-body">
                        {{-- 4 Stat Cells for this Beneficiary --}}
                        <div class="ism-summary-grid">
                            <div class="ism-summary-cell">
                                <span class="ism-summary-label">{{ __('finance.treasury_transactions.summary.modal.total_outgoing') }}</span>
                                <span class="ism-summary-value" style="color: #dc2626;">
                                    − {{ number_format($beneficiaryDetails['total_outgoing'], 2) }} {{ $currency }}
                                </span>
                            </div>
                            <div class="ism-summary-cell">
                                <span class="ism-summary-label">{{ __('finance.treasury_transactions.summary.modal.total_incoming') }}</span>
                                <span class="ism-summary-value" style="color: #059669;">
                                    + {{ number_format($beneficiaryDetails['total_incoming'], 2) }} {{ $currency }}
                                </span>
                            </div>
                            <div class="ism-summary-cell">
                                <span class="ism-summary-label">{{ __('finance.treasury_transactions.summary.modal.net_amount') }}</span>
                                <span class="ism-summary-value" style="color: {{ $beneficiaryDetails['net_amount'] >= 0 ? '#059669' : '#d97706' }};">
                                    {{ $beneficiaryDetails['net_amount'] > 0 ? '+' : '' }}{{ number_format($beneficiaryDetails['net_amount'], 2) }} {{ $currency }}
                                </span>
                            </div>
                            <div class="ism-summary-cell">
                                <span class="ism-summary-label">{{ __('finance.treasury_transactions.summary.modal.vouchers_count') }}</span>
                                <span class="ism-summary-value" style="color: #2563eb;">
                                    {{ $beneficiaryDetails['transactions_count'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Ledger of all Vouchers for this Beneficiary --}}
                        <div class="report-table-container">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th style="width: 35px;">#</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.trans_no') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.voucher_no') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.date') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.type') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.item') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.description') }}</th>
                                        <th>{{ __('finance.treasury_transactions.summary.modal.amount') }}</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($beneficiaryDetails['transactions'] as $tx)
                                        @php
                                            $isInc = ($tx->transaction_type instanceof \App\Enums\TreasuryTransactionType)
                                                ? $tx->transaction_type === \App\Enums\TreasuryTransactionType::INCOMING
                                                : ($tx->transaction_type === 'incoming');
                                            $txDesc = $tx->description ?: ($tx->paymentCollectionItem?->sub_item ?? '—');
                                            $txDesc = str_replace(
                                                ['Salary Payment', 'Salary Collection', 'salary_payment', 'salary_collection'],
                                                ['تسليم رواتب', 'تحصيل رواتب', 'تسليم رواتب', 'تحصيل رواتب'],
                                                $txDesc
                                            );
                                        @endphp
                                        <tr>
                                            <td style="font-weight: 700; color: #64748b;">{{ $loop->iteration }}</td>
                                            <td>
                                                <x-filament::badge color="primary">
                                                    {{ $tx->transaction_number }}
                                                </x-filament::badge>
                                            </td>
                                            <td>
                                                @if($tx->voucher_number)
                                                    <x-filament::badge color="gray">
                                                        {{ $tx->voucher_number }}
                                                    </x-filament::badge>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="font-size: 0.8rem;">
                                                <div>{{ $tx->transaction_date ? $tx->transaction_date->format('Y-m-d') : '—' }} م</div>
                                                <div style="font-size: 0.72rem; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($tx->transaction_date) }} هـ</div>
                                            </td>
                                            <td>
                                                <x-filament::badge :color="$isInc ? 'success' : 'danger'">
                                                    {{ $isInc ? 'وارد (قبض)' : 'منصرف (صرف)' }}
                                                </x-filament::badge>
                                            </td>
                                            <td style="font-size: 0.82rem; font-weight: 600;">
                                                {{ $tx->paymentCollectionItem?->sub_item ?? '—' }}
                                            </td>
                                            <td style="font-size: 0.82rem; color: #334155; max-width: 250px;">
                                                {{ $txDesc }}
                                            </td>
                                            <td>
                                                <span style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: {{ $isInc ? '#059669' : '#dc2626' }};">
                                                    {{ $isInc ? '+' : '−' }} {{ number_format((float)$tx->amount, 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                    <a href="{{ route('treasury.voucher.print', $tx) }}" target="_blank" class="ism-print-btn" title="{{ __('finance.treasury_transactions.actions.print_voucher') }}">
                                                        <x-heroicon-o-printer class="report-icon-sm" />
                                                        <span>طباعة</span>
                                                    </a>
                                                    <a href="{{ \App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource::getUrl('edit', ['record' => $tx]) }}" class="ism-edit-btn" title="تعديل">
                                                        <x-heroicon-m-pencil-square class="report-icon-sm" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="ism-modal-footer">
                        <button type="button" wire:click="closeDetails" class="ism-modal-close" style="width: auto; padding: 0.5rem 1.25rem; font-weight: 700; font-size: 0.85rem;">
                            {{ __('finance.treasury_transactions.summary.modal.close') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
