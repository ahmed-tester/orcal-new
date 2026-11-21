<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .daily-journals-summary-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .daily-journals-summary-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .daily-journals-summary-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .daily-journals-summary-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .daily-journals-summary-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .daily-journals-summary-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 1100px) {
            .daily-journals-summary-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .daily-journals-summary-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; }
        }
        @media (max-width: 480px) {
            .daily-journals-summary-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .daily-journals-summary-page .report-stat-card { padding: 0.75rem; min-height: 104px; }
        }

        .rwd-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); text-decoration: none; }
        .rwd-export-btn:hover { background: #1e293b; color: #ffffff; }
        .dark .rwd-export-btn { background: #2563eb; }
        .dark .rwd-export-btn:hover { background: #1d4ed8; }

        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .journal-summary-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 700px; }
        .journal-summary-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .journal-summary-table thead tr { background-color: #0f172a; border-color: #334155; }
        .journal-summary-table th { padding: 0.875rem 0.875rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .journal-summary-table th { color: #cbd5e1; }
        .journal-summary-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .journal-summary-table tbody tr { border-bottom-color: #334155; }
        .journal-summary-table tbody tr:hover { background-color: #f8fafc; }
        .dark .journal-summary-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .journal-summary-table td { padding: 0.75rem 0.875rem; white-space: nowrap; color: #0f172a; }
        .dark .journal-summary-table td { color: #f1f5f9; }
        .journal-summary-table bdi { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }

        .journal-details-item { margin-block: 1rem; padding: 0.85rem; border: 1px solid #e2e8f0; border-radius: 0.65rem; background: #f8fafc; }
        .dark .journal-details-item { background: #0f172a; border-color: #334155; }
        .journal-details-meta { display: flex; flex-wrap: wrap; gap: 0.6rem 1.2rem; margin-block: 0.65rem; }
        .journal-details-meta dt { font-size: 0.8rem; color: #64748b; }
        .dark .journal-details-meta dt { color: #94a3b8; }
        .journal-details-meta dd { font-weight: 700; color: #0f172a; }
        .dark .journal-details-meta dd { color: #f1f5f9; }

        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
    </style>

    <div class="report-page-container daily-journals-summary-page">
        @php
            $stats = $this->stats;
            $rows = $this->summary;
            $details = $this->details;
            $sar = __('finance.daily_journals.summary.currency_sar');
            $net = (float) ($stats->net_sum ?? 0);
            $netStatus = $net > 0
                ? __('finance.daily_journals.net_status.profit')
                : ($net < 0 ? __('finance.daily_journals.net_status.loss') : __('finance.daily_journals.net_status.even'));
            $cards = [
                [
                    'title' => __('finance.daily_journals.summary.entries'),
                    'value' => number_format((float) ($stats->entries_count ?? 0)),
                    'subtitle' => __('finance.daily_journals.summary.entries_unit'),
                    'icon' => 'heroicon-o-book-open',
                    'bg' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'text' => '#1d4ed8',
                    'icon_bg' => '#dbeafe',
                ],
                [
                    'title' => __('finance.daily_journals.summary.branches'),
                    'value' => number_format((float) ($stats->branches ?? 0)),
                    'subtitle' => __('finance.daily_journals.summary.branches_unit'),
                    'icon' => 'heroicon-o-building-office-2',
                    'bg' => '#f0fdf4',
                    'border' => '#bbf7d0',
                    'text' => '#15803d',
                    'icon_bg' => '#dcfce7',
                ],
                [
                    'title' => __('finance.daily_journals.summary.sales_sum'),
                    'value' => number_format((float) ($stats->sales_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'bg' => '#faf5ff',
                    'border' => '#e9d5ff',
                    'text' => '#7e22ce',
                    'icon_bg' => '#f3e8ff',
                ],
                [
                    'title' => __('finance.daily_journals.summary.purchases_sum'),
                    'value' => number_format((float) ($stats->purchases_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'bg' => '#fffbeb',
                    'border' => '#fde68a',
                    'text' => '#b45309',
                    'icon_bg' => '#fef3c7',
                ],
                [
                    'title' => __('finance.daily_journals.summary.net_sum'),
                    'value' => number_format($net, 2),
                    'subtitle' => $netStatus,
                    'icon' => 'heroicon-o-scale',
                    'bg' => $net >= 0 ? '#ecfdf5' : '#fef2f2',
                    'border' => $net >= 0 ? '#a7f3d0' : '#fecaca',
                    'text' => $net >= 0 ? '#059669' : '#dc2626',
                    'icon_bg' => $net >= 0 ? '#d1fae5' : '#fee2e2',
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

        <x-filter-bar
            :title="__('finance.daily_journals.summary.filter_title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('finance.daily_journals.summary.company')"
            :multiple-company="true"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('reports.filters.branch')"
            :multiple-branch="true"
            :show-dates="true"
            :date-from-label="__('finance.daily_journals.summary.date_from')"
            :date-to-label="__('finance.daily_journals.summary.date_to')"
            :show-search="true"
            :search-placeholder="__('finance.daily_journals.summary.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <button type="button" wire:click="exportCsv" class="rwd-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('finance.daily_journals.summary.export_excel') }}</span>
                </button>
                <a href="{{ route('daily-journals.summary.print', $this->filters()) }}" target="_blank" rel="noopener" class="rwd-export-btn" style="background: #475569;">
                    <x-heroicon-o-printer class="report-icon-sm" />
                    <span>{{ __('finance.daily_journals.summary.print_summary') }}</span>
                </a>
            </x-slot>

            <x-slot name="append">
                {{-- Journal type selector (Multi-select) --}}
                <x-filter-select
                    wire:model.live="isDetailed"
                    :label="__('finance.daily_journals.fields.journal_type')"
                    :placeholder="__('finance.daily_journals.summary.all_types')"
                    :options="['1' => __('finance.daily_journals.types.detailed'), '0' => __('finance.daily_journals.types.summary')]"
                    :multiple="true"
                    :searchable="false"
                />
            </x-slot>
        </x-filter-bar>

        <div class="report-table-container">
            @include('daily-journals.partials.summary-table', ['rows' => $rows, 'interactive' => true])

            @if($rows->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$rows" />
                </div>
            @endif
        </div>

        <x-filament::modal id="daily-journal-details" width="4xl" slide-over>
            <x-slot:heading>{{ __('finance.daily_journals.summary.statement') }}</x-slot:heading>

            @if($details)
                @include('daily-journals.partials.statement', ['interactive' => true])

                <x-slot:footerActions>
                    <x-filament::button tag="a" :href="route('daily-journals.summary.print', ['journal' => $details['journal']->id, ...$this->filters()])" target="_blank" rel="noopener" icon="heroicon-o-printer">
                        {{ __('finance.daily_journals.summary.print_statement') }}
                    </x-filament::button>
                    <x-filament::button color="gray" x-on:click="close()" wire:click="closeDetails">
                        {{ __('finance.daily_journals.summary.close') }}
                    </x-filament::button>
                </x-slot:footerActions>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>
