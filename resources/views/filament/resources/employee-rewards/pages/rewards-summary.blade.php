<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .rwd-rewards-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .rwd-rewards-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .rwd-rewards-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .rwd-rewards-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .rwd-rewards-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .rwd-rewards-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 1100px) {
            .rwd-rewards-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .rwd-rewards-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; }
        }
        @media (max-width: 480px) {
            .rwd-rewards-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .rwd-rewards-page .report-stat-card { padding: 0.75rem; min-height: 104px; }
        }

        .rwd-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); text-decoration: none; }
        .rwd-export-btn:hover { background: #1e293b; color: #ffffff; }
        .dark .rwd-export-btn { background: #2563eb; }
        .dark .rwd-export-btn:hover { background: #1d4ed8; }

        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .report-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 1050px; }
        .report-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .report-table thead tr { background-color: #0f172a; border-color: #334155; }
        .report-table th { padding: 0.875rem 0.875rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .report-table th { color: #cbd5e1; }
        .report-table th button { background: none; border: none; cursor: pointer; font-weight: inherit; font-size: inherit; color: inherit; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0; }
        .report-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .report-table tbody tr { border-bottom-color: #334155; }
        .report-table tbody tr:hover { background-color: #f8fafc; }
        .dark .report-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .report-table td { padding: 0.75rem 0.875rem; white-space: nowrap; color: #0f172a; }
        .dark .report-table td { color: #f1f5f9; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }
        .report-table-pagination svg { width: 1.25rem !important; height: 1.25rem !important; max-width: 1.25rem !important; }

        .rwd-details-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .rwd-details-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .dark .rwd-details-btn { background: rgba(37,99,235,0.15); border-color: #1e40af; color: #60a5fa; }
        .dark .rwd-details-btn:hover { background: rgba(37,99,235,0.25); }

        /* Modal Styles */
        .rwd-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .rwd-modal { background: #ffffff; border-radius: 1rem; width: 100%; max-width: 1100px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; }
        .dark .rwd-modal { background: #1e293b; }
        .rwd-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; }
        .dark .rwd-modal-header { background: #0f172a; border-bottom-color: #334155; }
        .rwd-modal-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; }
        .dark .rwd-modal-title { color: #f8fafc; }
        .rwd-modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; }
        .dark .rwd-modal-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .rwd-modal-close:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .rwd-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .rwd-modal-footer { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; flex-wrap: wrap; gap: 0.75rem; }
        .dark .rwd-modal-footer { background: #0f172a; border-top-color: #334155; }

        /* Employee Info Strip */
        .rwd-emp-strip { display: flex; flex-wrap: wrap; gap: 1rem; padding: 0.85rem 1rem; background: #f1f5f9; border-radius: 0.75rem; border: 1px solid #e2e8f0; font-size: 0.8125rem; }
        .dark .rwd-emp-strip { background: #0f172a; border-color: #334155; }
        .rwd-strip-item { display: flex; align-items: center; gap: 0.4rem; color: #475569; }
        .dark .rwd-strip-item { color: #94a3b8; }
        .rwd-strip-item strong { color: #0f172a; font-weight: 700; }
        .dark .rwd-strip-item strong { color: #f8fafc; }

        /* Balance / Stats grid in modal */
        .rwd-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
        .rwd-stat-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.625rem; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .dark .rwd-stat-cell { background: #0f172a; border-color: #334155; }
        .rwd-stat-label { font-size: 0.72rem; font-weight: 700; color: #64748b; }
        .dark .rwd-stat-label { color: #94a3b8; }
        .rwd-stat-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; }
        .dark .rwd-stat-val { color: #f1f5f9; }

        .report-icon-xs { width: 14px !important; height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        [x-cloak] { display: none !important; }
    </style>

    <div class="report-page-container rwd-rewards-page">
        {{-- KPI Cards (5 Cards) --}}
        @php
            $stats = $this->stats;
            $sar = __('finance.employee_rewards.summary.currency_sar');
            $cards = [
                [
                    'title' => __('finance.employee_rewards.summary.cards.total_employees'),
                    'value' => number_format($stats['employees_count']),
                    'subtitle' => __('finance.employee_rewards.summary.cards.employees_unit'),
                    'icon' => 'heroicon-o-users',
                    'bg' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'text' => '#1d4ed8',
                    'icon_bg' => '#dbeafe',
                ],
                [
                    'title' => __('finance.employee_rewards.summary.cards.total_rewards'),
                    'value' => number_format($stats['total_rewards']),
                    'subtitle' => __('finance.employee_rewards.summary.cards.rewards_unit'),
                    'icon' => 'heroicon-o-gift',
                    'bg' => '#f0fdf4',
                    'border' => '#bbf7d0',
                    'text' => '#15803d',
                    'icon_bg' => '#dcfce7',
                ],
                [
                    'title' => __('finance.employee_rewards.summary.cards.total_amount'),
                    'value' => number_format($stats['total_amount'], 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-banknotes',
                    'bg' => '#faf5ff',
                    'border' => '#e9d5ff',
                    'text' => '#7e22ce',
                    'icon_bg' => '#f3e8ff',
                ],
                [
                    'title' => __('finance.employee_rewards.summary.cards.paid_amount'),
                    'value' => number_format($stats['paid_amount'], 2),
                    'subtitle' => $sar,
                    'icon' => 'heroicon-o-check-circle',
                    'bg' => '#ecfdf5',
                    'border' => '#a7f3d0',
                    'text' => '#059669',
                    'icon_bg' => '#d1fae5',
                ],
                [
                    'title' => __('finance.employee_rewards.summary.cards.pending_count'),
                    'value' => number_format($stats['pending_count']),
                    'subtitle' => __('finance.employee_rewards.summary.cards.pending_unit'),
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
            :title="__('finance.employee_rewards.summary.filters.search')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('finance.employee_rewards.fields.company')"
            :multiple-company="true"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('finance.employee_rewards.summary.columns.branch')"
            :multiple-branch="true"
            :show-status="false"
            :show-dates="false"
            :show-search="true"
            :search-placeholder="__('finance.employee_rewards.summary.filters.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <button type="button" wire:click="exportCsv" class="rwd-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('finance.employee_rewards.summary.filters.export_excel') }}</span>
                </button>
            </x-slot>

            <x-slot name="append">
                {{-- Year selector --}}
                <x-filter-select
                    wire:model.live="year"
                    :label="__('finance.employee_rewards.summary.filters.year')"
                    :placeholder="__('finance.employee_rewards.summary.filters.all_years')"
                    :options="$this->yearOptions"
                    :multiple="false"
                    :searchable="false"
                />

                {{-- Reward filter selector --}}
                <x-filter-select
                    wire:model.live="rewardFilter"
                    :label="__('finance.employee_rewards.summary.filters.reward_filter')"
                    :placeholder="__('finance.employee_rewards.summary.filters.reward_filters.all')"
                    :options="[
                        'has_pending' => __('finance.employee_rewards.summary.filters.reward_filters.has_pending'),
                        'has_paid' => __('finance.employee_rewards.summary.filters.reward_filters.has_paid'),
                    ]"
                    :multiple="false"
                    :searchable="false"
                />

                {{-- Employment Status selector --}}
                <x-filter-select
                    wire:model.live="employmentStatus"
                    :label="__('finance.employee_rewards.summary.filters.employment_status')"
                    :placeholder="__('reports.filters.status_all')"
                    :options="[
                        'active' => __('finance.employee_rewards.summary.filters.employment_statuses.active'),
                        'inactive' => __('finance.employee_rewards.summary.filters.employment_statuses.inactive'),
                    ]"
                    :multiple="false"
                    :searchable="false"
                />
            </x-slot>
        </x-filter-bar>

        {{-- Summary Table (One Row Per Employee) --}}
        @php $summary = $this->summary; @endphp
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>
                            <button type="button" wire:click="sortBy('name')" style="{{ $sortColumn === 'name' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.employee_rewards.summary.columns.employee') }}</span>
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('finance.employee_rewards.summary.columns.national_id') }}</th>
                        <th>{{ __('finance.employee_rewards.summary.columns.branch') }}</th>
                        <th>{{ __('finance.employee_rewards.summary.columns.job_title') }}</th>
                        <th>
                            <button type="button" wire:click="sortBy('total_rewards')" style="{{ $sortColumn === 'total_rewards' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.employee_rewards.summary.columns.total_rewards') }}</span>
                                @if($sortColumn === 'total_rewards')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('finance.employee_rewards.summary.columns.total_hours') }}</th>
                        <th>
                            <button type="button" wire:click="sortBy('total_amount')" style="{{ $sortColumn === 'total_amount' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('finance.employee_rewards.summary.columns.total_amount') }}</span>
                                @if($sortColumn === 'total_amount')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('finance.employee_rewards.summary.columns.paid_amount') }}</th>
                        <th>{{ __('finance.employee_rewards.summary.columns.pending_count') }}</th>
                        <th>{{ __('finance.employee_rewards.summary.columns.last_date') }}</th>
                        <th style="text-align: center;">{{ __('finance.employee_rewards.summary.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $index => $row)
                        @php
                            $pendingCount = (int) ($row->pending_count ?? 0);
                        @endphp
                        <tr wire:key="reward-row-{{ $row->id }}">
                            <td style="color: #64748b; font-size: 0.75rem;">{{ $summary->firstItem() + $index }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 700; color: #0f172a;" class="dark:text-white">{{ $this->nameOf($row) }}</span>
                                    <span style="font-size: 0.75rem; color: #64748b;">🆔 {{ $row->employee_number ?? '—' }}</span>
                                </div>
                            </td>
                            <td style="font-size: 0.8125rem; color: #475569;" class="dark:text-slate-300">{{ $this->nationalIdOf($row) ?? '—' }}</td>
                            <td style="font-size: 0.8125rem;">
                                <div style="display: flex; flex-direction: column;">
                                    <span>{{ $this->branchOf($row) ?? '—' }}</span>
                                    <span style="font-size: 0.7rem; color: #94a3b8;">{{ $this->companyOf($row) }}</span>
                                </div>
                            </td>
                            <td style="font-size: 0.8125rem; color: #475569;" class="dark:text-slate-300">{{ $this->jobTitleOf($row) ?? '—' }}</td>
                            <td>
                                <span style="font-weight: 800; font-size: 0.9rem; color: #1e293b;" class="dark:text-slate-200">
                                    {{ (int) $row->total_rewards }}
                                </span>
                            </td>
                            <td style="font-size: 0.8125rem; color: #475569;">
                                {{ number_format((float) $row->total_hours, 1) }} {{ __('finance.employee_rewards.summary.hours_unit') }}
                            </td>
                            <td>
                                <span style="font-weight: 800; color: #059669; font-size: 0.9rem;">
                                    {{ number_format((float) $row->total_amount, 2) }} {{ $sar }}
                                </span>
                            </td>
                            <td style="font-weight: 700; color: #15803d; font-size: 0.85rem;">
                                {{ number_format((float) $row->paid_amount, 2) }} {{ $sar }}
                            </td>
                            <td>
                                @if($pendingCount > 0)
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 0.375rem; background: #fef3c7; color: #b45309; font-weight: 800; font-size: 0.75rem;">
                                        {{ $pendingCount }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="font-size: 0.8125rem; color: #64748b;">
                                {{ $row->last_reward_date ? \Carbon\Carbon::parse($row->last_reward_date)->format('Y-m-d') : '—' }}
                            </td>
                            <td style="text-align: center;">
                                <button type="button" wire:click="openDetails({{ $row->id }})" class="rwd-details-btn">
                                    <x-heroicon-o-eye class="report-icon-xs" />
                                    <span>{{ __('finance.employee_rewards.summary.columns.view_details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                                <x-heroicon-o-gift class="report-icon-lg" style="margin: 0 auto 0.5rem; display: block; opacity: 0.6;" />
                                <div>{{ __('finance.employee_rewards.summary.empty') }}</div>
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

        {{-- Details Modal --}}
        @if($details)
            <div class="rwd-overlay" wire:click.self="closeDetails">
                <div class="rwd-modal" role="dialog" aria-modal="true">
                    {{-- Modal Header --}}
                    <div class="rwd-modal-header">
                        <div class="rwd-modal-title">
                            <x-heroicon-o-gift class="report-icon-md" style="color: #2563eb;" />
                            <span>{{ __('finance.employee_rewards.summary.modal.title') }} — {{ $details['employee']->user?->name ?? '' }}</span>
                        </div>
                        <button type="button" wire:click="closeDetails" class="rwd-modal-close" aria-label="Close">
                            <x-heroicon-o-x-mark class="report-icon-sm" />
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="rwd-modal-body">
                        {{-- Employee Info Strip --}}
                        <div class="rwd-emp-strip">
                            <div class="rwd-strip-item">
                                <span>🆔 {{ __('finance.employee_rewards.summary.columns.employee_number') }}:</span>
                                <strong>{{ $details['employee']->employee_number ?? '—' }}</strong>
                            </div>
                            <div class="rwd-strip-item">
                                <span>🏢 {{ __('finance.employee_rewards.fields.company') }}:</span>
                                <strong>{{ $details['employee']->company?->trade_name ?? '—' }}</strong>
                            </div>
                            <div class="rwd-strip-item">
                                <span>📍 {{ __('finance.employee_rewards.summary.columns.branch') }}:</span>
                                <strong>{{ $details['employee']->branch?->name ?? '—' }}</strong>
                            </div>
                            <div class="rwd-strip-item">
                                <span>💼 {{ __('finance.employee_rewards.summary.columns.job_title') }}:</span>
                                <strong>{{ $details['employee']->jobTitle?->name ?? '—' }}</strong>
                            </div>
                        </div>

                        {{-- Stats Grid --}}
                        <div class="rwd-stats-grid">
                            <div class="rwd-stat-cell">
                                <span class="rwd-stat-label">{{ __('finance.employee_rewards.summary.modal.total_rewards') }}</span>
                                <span class="rwd-stat-val">{{ $details['stats']['total_rewards'] }}</span>
                            </div>
                            <div class="rwd-stat-cell" style="background: #f0fdf4; border-color: #bbf7d0;">
                                <span class="rwd-stat-label" style="color: #166534;">{{ __('finance.employee_rewards.summary.modal.total_amount') }}</span>
                                <span class="rwd-stat-val" style="color: #15803d;">{{ number_format($details['stats']['total_amount'], 2) }} {{ $sar }}</span>
                            </div>
                            <div class="rwd-stat-cell">
                                <span class="rwd-stat-label">{{ __('finance.employee_rewards.summary.modal.paid_amount') }}</span>
                                <span class="rwd-stat-val" style="color: #059669;">{{ number_format($details['stats']['paid_amount'], 2) }} {{ $sar }}</span>
                            </div>
                            <div class="rwd-stat-cell">
                                <span class="rwd-stat-label">{{ __('finance.employee_rewards.summary.modal.pending_count') }}</span>
                                <span class="rwd-stat-val" style="color: #b45309;">{{ $details['stats']['pending_count'] }}</span>
                            </div>
                            <div class="rwd-stat-cell">
                                <span class="rwd-stat-label">{{ __('finance.employee_rewards.summary.modal.total_hours') }}</span>
                                <span class="rwd-stat-val">{{ number_format($details['stats']['total_hours'], 1) }}</span>
                            </div>
                        </div>

                        {{-- Rewards History Table --}}
                        <div style="margin-top: 0.5rem;">
                            <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 0.5rem; color: #0f172a;" class="dark:text-white">
                                📋 {{ __('finance.employee_rewards.summary.modal.history_tab') }} ({{ $details['rewards']->count() }})
                            </div>
                            <div class="report-table-container" style="max-height: 340px; overflow-y: auto;">
                                <table class="report-table" style="min-width: 800px; font-size: 0.8125rem;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('finance.employee_rewards.fields.reward_number') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.reward_date') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.hours_count') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.minutes_count') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.net_amount') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.reason') }}</th>
                                            <th>{{ __('finance.employee_rewards.fields.status') }}</th>
                                            <th style="text-align: center;">{{ __('finance.employee_rewards.summary.columns.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($details['rewards'] as $reward)
                                            <tr>
                                                <td style="font-weight: 700; color: #2563eb;">{{ $reward->reward_number }}</td>
                                                <td>
                                                    <div>{{ $reward->reward_date ? \Carbon\Carbon::parse($reward->reward_date)->format('Y-m-d') : '—' }}</div>
                                                    <div style="font-size: 0.7rem; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($reward->reward_date) }}</div>
                                                </td>
                                                <td style="font-weight: 600;">{{ $reward->hours_count }}</td>
                                                <td style="font-weight: 600;">{{ $reward->minutes_count }}</td>
                                                <td style="font-weight: 800; color: #059669;">
                                                    {{ number_format((float) $reward->net_amount, 2) }} {{ $sar }}
                                                </td>
                                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $reward->reason }}">
                                                    {{ $reward->reason ?: '—' }}
                                                </td>
                                                <td>
                                                    <x-filament::badge :color="$reward->status?->getColor() ?? 'gray'">
                                                        {{ $reward->status?->getLabel() ?? $reward->status }}
                                                    </x-filament::badge>
                                                </td>
                                                <td style="text-align: center;">
                                                    <a href="{{ \App\Filament\Resources\EmployeeRewards\EmployeeRewardResource::getUrl('edit', ['record' => $reward->id]) }}" class="rwd-details-btn" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                                        <x-heroicon-o-pencil class="report-icon-xs" />
                                                        <span>{{ __('filament-actions::edit.single.label') }}</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" style="text-align: center; padding: 1.5rem; color: #94a3b8;">
                                                    {{ __('finance.employee_rewards.summary.modal.no_rewards') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="rwd-modal-footer">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ \App\Filament\Resources\EmployeeRewards\EmployeeRewardResource::getUrl('create') }}?employee_id={{ $details['employee']->id }}" class="rwd-export-btn" style="background: #2563eb;">
                                <x-heroicon-o-plus-circle class="report-icon-sm" />
                                <span>{{ __('finance.employee_rewards.summary.modal.new_reward_btn') }}</span>
                            </a>
                        </div>
                        <button type="button" wire:click="closeDetails" class="rwd-export-btn" style="background: #64748b;">
                            {{ __('finance.employee_rewards.summary.modal.close') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

