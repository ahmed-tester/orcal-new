<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .violations-summary-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .violations-summary-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .violations-summary-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .violations-summary-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .violations-summary-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .violations-summary-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 1100px) {
            .violations-summary-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .violations-summary-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; }
        }
        @media (max-width: 480px) {
            .violations-summary-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .violations-summary-page .report-stat-card { padding: 0.75rem; min-height: 104px; }
        }

        .rwd-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); text-decoration: none; }
        .rwd-export-btn:hover { background: #1e293b; color: #ffffff; }
        .dark .rwd-export-btn { background: #2563eb; }
        .dark .rwd-export-btn:hover { background: #1d4ed8; }

        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .violation-summary-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 900px; }
        .violation-summary-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .violation-summary-table thead tr { background-color: #0f172a; border-color: #334155; }
        .violation-summary-table th { padding: 0.875rem 0.875rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .violation-summary-table th { color: #cbd5e1; }
        .violation-summary-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .violation-summary-table tbody tr { border-bottom-color: #334155; }
        .violation-summary-table tbody tr:hover { background-color: #f8fafc; }
        .dark .violation-summary-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .violation-summary-table td { padding: 0.75rem 0.875rem; white-space: nowrap; color: #0f172a; }
        .dark .violation-summary-table td { color: #f1f5f9; }
        .violation-summary-table bdi { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }

        .violation-details-item { margin-block: 1rem; padding: 0.85rem; border: 1px solid #e2e8f0; border-radius: 0.65rem; background: #f8fafc; }
        .dark .violation-details-item { background: #0f172a; border-color: #334155; }
        .violation-details-meta { display: flex; flex-wrap: wrap; gap: 0.6rem 1.2rem; margin-block: 0.65rem; }
        .violation-details-meta dt { font-size: 0.8rem; color: #64748b; }
        .dark .violation-details-meta dt { color: #94a3b8; }
        .violation-details-meta dd { font-weight: 700; color: #0f172a; }
        .dark .violation-details-meta dd { color: #f1f5f9; }

        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
    </style>

    <div class="report-page-container violations-summary-page">
        @php
            $stats = $this->stats;
            $rows = $this->summary;
            $details = $this->details;
            $sar = __('violations.summary.currency_sar');
            $cards = [
                [
                    'title' => __('violations.summary.companies'),
                    'value' => number_format((float) ($stats->companies ?? 0)),
                    'subtitle' => __('violations.summary.companies_unit'),
                    'icon' => 'heroicon-o-building-office-2',
                    'bg' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'text' => '#1d4ed8',
                    'icon_bg' => '#dbeafe',
                ],
                [
                    'title' => __('violations.summary.violation_count'),
                    'value' => number_format((float) ($stats->violation_count ?? 0)),
                    'subtitle' => __('violations.summary.violations_unit'),
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'bg' => '#f0fdf4',
                    'border' => '#bbf7d0',
                    'text' => '#15803d',
                    'icon_bg' => '#dcfce7',
                ],
                [
                    'title' => __('violations.summary.total_sum'),
                    'value' => number_format((float) ($stats->total_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-banknotes',
                    'bg' => '#faf5ff',
                    'border' => '#e9d5ff',
                    'text' => '#7e22ce',
                    'icon_bg' => '#f3e8ff',
                ],
                [
                    'title' => __('violations.summary.paid_sum'),
                    'value' => number_format((float) ($stats->paid_sum ?? 0), 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-check-circle',
                    'bg' => '#ecfdf5',
                    'border' => '#a7f3d0',
                    'text' => '#059669',
                    'icon_bg' => '#d1fae5',
                ],
                [
                    'title' => __('violations.summary.remaining_sum'),
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

        {{-- Unified Filter Bar --}}
        <x-filter-bar
            :title="__('violations.summary.filter_title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('violations.summary.company')"
            :multiple-company="true"
            :show-branch="false"
            :show-status="true"
            :status-label="__('reports.filters.status')"
            :status-options="[
                'open' => __('violations.summary.open'),
                'paid' => __('violations.payment_statuses.paid'),
                'partial' => __('violations.payment_statuses.partial'),
                'pending' => __('violations.payment_statuses.pending'),
                'overdue' => __('violations.summary.overdue_sum'),
            ]"
            :multiple-status="true"
            :show-dates="true"
            :date-from-label="__('violations.summary.date_from')"
            :date-to-label="__('violations.summary.date_to')"
            :show-search="true"
            :search-placeholder="__('violations.summary.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <button type="button" wire:click="exportCsv" class="rwd-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('violations.summary.export_excel') }}</span>
                </button>
                <a href="{{ route('violations.summary.print', $this->filters()) }}" target="_blank" rel="noopener" class="rwd-export-btn" style="background: #475569;">
                    <x-heroicon-o-printer class="report-icon-sm" />
                    <span>{{ __('violations.summary.print_summary') }}</span>
                </a>
            </x-slot>

            <x-slot name="append">
                <x-filter-select
                    wire:model.live="categoryId"
                    :label="__('violations.summary.category')"
                    :placeholder="__('violations.summary.all_categories')"
                    :options="$this->categories"
                    :multiple="true"
                    :searchable="true"
                />

                <x-filter-select
                    wire:model.live="typeId"
                    :label="__('violations.summary.type')"
                    :placeholder="__('violations.summary.all_types')"
                    :options="$this->violationTypes"
                    :multiple="true"
                    :searchable="true"
                />

                <x-filter-select
                    wire:model.live="stage"
                    :label="__('violations.violations.fields.stage')"
                    :placeholder="__('violations.summary.all_stages')"
                    :options="collect(\App\Enums\ViolationStage::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])->toArray()"
                    :multiple="true"
                    :searchable="false"
                />
            </x-slot>
        </x-filter-bar>

        {{-- Summary Table Container --}}
        <div class="report-table-container">
            @include('violations.partials.summary-table', ['rows' => $rows, 'interactive' => true])

            @if($rows->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$rows" />
                </div>
            @endif
        </div>

        {{-- Slide-over Details Modal --}}
        <x-filament::modal id="violation-details" width="4xl" slide-over>
            <x-slot:heading>{{ __('violations.summary.statement') }}</x-slot:heading>

            @if($details)
                @include('violations.partials.statement', [
                    'interactive' => true,
                    'canPay' => $details['showTransactions'] && \App\Filament\Resources\ViolationTransactions\ViolationTransactionResource::canCreate(),
                ])

                <x-slot:footerActions>
                    <x-filament::button tag="a" :href="route('violations.summary.print', ['company' => $details['representative']->id, ...$this->filters()])" target="_blank" rel="noopener" icon="heroicon-o-printer">
                        {{ __('violations.summary.print_statement') }}
                    </x-filament::button>
                    <x-filament::button color="gray" x-on:click="close()" wire:click="closeDetails">
                        {{ __('violations.summary.close') }}
                    </x-filament::button>
                </x-slot:footerActions>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>

