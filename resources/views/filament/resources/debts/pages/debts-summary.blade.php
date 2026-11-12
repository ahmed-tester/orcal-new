<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .debts-summary-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .debts-summary-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .debts-summary-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .debts-summary-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .debts-summary-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .debts-summary-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 1100px) {
            .debts-summary-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .debts-summary-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; }
        }
        @media (max-width: 480px) {
            .debts-summary-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .debts-summary-page .report-stat-card { padding: 0.75rem; min-height: 104px; }
        }

        .rwd-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); text-decoration: none; }
        .rwd-export-btn:hover { background: #1e293b; color: #ffffff; }
        .dark .rwd-export-btn { background: #2563eb; }
        .dark .rwd-export-btn:hover { background: #1d4ed8; }

        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .debt-summary-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 900px; }
        .debt-summary-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .debt-summary-table thead tr { background-color: #0f172a; border-color: #334155; }
        .debt-summary-table th { padding: 0.875rem 0.875rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .debt-summary-table th { color: #cbd5e1; }
        .debt-summary-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .debt-summary-table tbody tr { border-bottom-color: #334155; }
        .debt-summary-table tbody tr:hover { background-color: #f8fafc; }
        .dark .debt-summary-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .debt-summary-table td { padding: 0.75rem 0.875rem; white-space: nowrap; color: #0f172a; }
        .dark .debt-summary-table td { color: #f1f5f9; }
        .debt-summary-table bdi { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }

        .debt-details-item { margin-block: 1rem; padding: 0.85rem; border: 1px solid #e2e8f0; border-radius: 0.65rem; background: #f8fafc; }
        .dark .debt-details-item { background: #0f172a; border-color: #334155; }
        .debt-details-meta { display: flex; flex-wrap: wrap; gap: 0.6rem 1.2rem; margin-block: 0.65rem; }
        .debt-details-meta dt { font-size: 0.8rem; color: #64748b; }
        .dark .debt-details-meta dt { color: #94a3b8; }
        .debt-details-meta dd { font-weight: 700; color: #0f172a; }
        .dark .debt-details-meta dd { color: #f1f5f9; }

        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
    </style>

    <div class="report-page-container debts-summary-page">
        {{-- KPI Cards (5 Cards matching Rewards layout) --}}
        @php
            $stats = $this->stats;
            $rows = $this->summary;
            $details = $this->details;
            $sar = __('debts.summary.currency_sar');
            $cards = [
                [
                    'title' => __('debts.summary.debtors'),
                    'value' => number_format((float) ($stats->debtors ?? 0)),
                    'subtitle' => __('debts.summary.debtors_unit'),
                    'icon' => 'heroicon-o-users',
                    'bg' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'text' => '#1d4ed8',
                    'icon_bg' => '#dbeafe',
                ],
                [
                    'title' => __('debts.summary.debt_count'),
                    'value' => number_format((float) ($stats->debt_count ?? 0)),
                    'subtitle' => __('debts.summary.debts_unit'),
                    'icon' => 'heroicon-o-gift',
                    'bg' => '#f0fdf4',
                    'border' => '#bbf7d0',
                    'text' => '#15803d',
                    'icon_bg' => '#dcfce7',
                ],
                [
                    'title' => __('debts.summary.total_sum'),
                    'value' => number_format((float) ($stats->total_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-banknotes',
                    'bg' => '#faf5ff',
                    'border' => '#e9d5ff',
                    'text' => '#7e22ce',
                    'icon_bg' => '#f3e8ff',
                ],
                [
                    'title' => __('debts.summary.paid_sum'),
                    'value' => number_format((float) ($stats->paid_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-check-circle',
                    'bg' => '#ecfdf5',
                    'border' => '#a7f3d0',
                    'text' => '#059669',
                    'icon_bg' => '#d1fae5',
                ],
                [
                    'title' => __('debts.summary.remaining_sum'),
                    'value' => number_format((float) ($stats->remaining_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-clock',
                    'bg' => '#fffbeb',
                    'border' => '#fde68a',
                    'text' => '#b45309',
                    'icon_bg' => '#fef3c7',
                ],
            ];
        @endphp

        <div class="report-stats-grid">
            @foreach($cards as $card)
                <div class="report-stat-card" style="background-color: {{ $card['bg'] }}; border-color: {{ $card['border'] }};">
                    <div class="report-stat-top">
                        <span class="report-stat-title" style="color: {{ $card['text'] }};">{{ $card['title'] }}</span>
                        <div class="report-stat-icon-wrapper" style="background-color: {{ $card['icon_bg'] }}; color: {{ $card['text'] }};">
                            <x-dynamic-component :component="$card['icon']" class="report-icon-md" />
                        </div>
                    </div>
                    <div class="report-stat-metric">
                        <div class="report-stat-value" style="color: {{ $card['text'] }};">{{ $card['value'] }}</div>
                        @if(isset($card['subtitle']))
                            <div class="report-stat-subtitle" style="color: {{ $card['text'] }};">
                                <span>{{ $card['subtitle'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Unified Filter Bar with Multi-Select --}}
        <x-filter-bar
            :title="__('debts.summary.filter_title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('debts.summary.company')"
            :multiple-company="true"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('reports.filters.branch')"
            :multiple-branch="true"
            :show-status="true"
            :status-label="__('reports.filters.status')"
            :status-options="['open' => __('debts.summary.open'), 'paid' => __('debts.badges.paid'), 'overdue' => __('debts.badges.overdue')]"
            :multiple-status="true"
            :show-dates="true"
            :date-from-label="__('debts.summary.date_from')"
            :date-to-label="__('debts.summary.date_to')"
            :show-search="true"
            :search-placeholder="__('debts.summary.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <button type="button" wire:click="exportCsv" class="rwd-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('debts.summary.export_excel') }}</span>
                </button>
                <a href="{{ route('debts.summary.print', $this->filters()) }}" target="_blank" rel="noopener" class="rwd-export-btn" style="background: #475569;">
                    <x-heroicon-o-printer class="report-icon-sm" />
                    <span>{{ __('debts.summary.print_summary') }}</span>
                </a>
            </x-slot>

            <x-slot name="append">
                {{-- Debtor Type selector (Multi-select) --}}
                <x-filter-select
                    wire:model.live="debtorType"
                    :label="__('debts.summary.debtor_type')"
                    :placeholder="__('debts.summary.all_types')"
                    :options="['internal' => __('debts.badges.internal'), 'external' => __('debts.badges.external')]"
                    :multiple="true"
                    :searchable="false"
                />
            </x-slot>
        </x-filter-bar>

        {{-- Summary Table Container --}}
        <div class="report-table-container">
            @include('debts.partials.summary-table', ['rows' => $rows, 'interactive' => true])

            @if($rows->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$rows" />
                </div>
            @endif
        </div>

        {{-- Slide-over Details Modal --}}
        <x-filament::modal id="debt-details" width="4xl" slide-over>
            <x-slot:heading>{{ __('debts.summary.statement') }}</x-slot:heading>

            @if($details)
                @include('debts.partials.statement', [
                    'interactive' => true,
                    'canCollect' => $details['showCollections'] && \App\Filament\Resources\DebtCollections\DebtCollectionResource::canCreate(),
                ])

                <x-slot:footerActions>
                    <x-filament::button tag="a" :href="route('debts.summary.print', ['debt' => $details['debtor']->id, ...$this->filters()])" target="_blank" rel="noopener" icon="heroicon-o-printer">
                        {{ __('debts.summary.print_statement') }}
                    </x-filament::button>
                    <x-filament::button color="gray" x-on:click="close()" wire:click="closeDetails">
                        {{ __('debts.summary.close') }}
                    </x-filament::button>
                </x-slot:footerActions>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>
