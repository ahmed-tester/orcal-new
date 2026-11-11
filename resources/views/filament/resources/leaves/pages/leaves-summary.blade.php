<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .lvs-leaves-page .report-stats-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.875rem; }
        .lvs-leaves-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .lvs-leaves-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .lvs-leaves-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .lvs-leaves-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .lvs-leaves-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 1100px) {
            .lvs-leaves-page .report-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .lvs-leaves-page .report-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; }
        }
        @media (max-width: 480px) {
            .lvs-leaves-page .report-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .lvs-leaves-page .report-stat-card { padding: 0.75rem; min-height: 104px; }
        }

        .lvs-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); text-decoration: none; }
        .lvs-export-btn:hover { background: #1e293b; color: #ffffff; }
        .dark .lvs-export-btn { background: #2563eb; }
        .dark .lvs-export-btn:hover { background: #1d4ed8; }

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

        .lvs-details-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .lvs-details-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .dark .lvs-details-btn { background: rgba(37,99,235,0.15); border-color: #1e40af; color: #60a5fa; }
        .dark .lvs-details-btn:hover { background: rgba(37,99,235,0.25); }

        /* Modal Styles */
        .lvs-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .lvs-modal { background: #ffffff; border-radius: 1rem; width: 100%; max-width: 1100px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; }
        .dark .lvs-modal { background: #1e293b; }
        .lvs-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; }
        .dark .lvs-modal-header { background: #0f172a; border-bottom-color: #334155; }
        .lvs-modal-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; }
        .dark .lvs-modal-title { color: #f8fafc; }
        .lvs-modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; }
        .dark .lvs-modal-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .lvs-modal-close:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .lvs-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .lvs-modal-footer { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; flex-wrap: wrap; gap: 0.75rem; }
        .dark .lvs-modal-footer { background: #0f172a; border-top-color: #334155; }

        /* Employee Info Strip */
        .lvs-emp-strip { display: flex; flex-wrap: wrap; gap: 1rem; padding: 0.85rem 1rem; background: #f1f5f9; border-radius: 0.75rem; border: 1px solid #e2e8f0; font-size: 0.8125rem; }
        .dark .lvs-emp-strip { background: #0f172a; border-color: #334155; }
        .lvs-strip-item { display: flex; align-items: center; gap: 0.4rem; color: #475569; }
        .dark .lvs-strip-item { color: #94a3b8; }
        .lvs-strip-item strong { color: #0f172a; font-weight: 700; }
        .dark .lvs-strip-item strong { color: #f8fafc; }

        /* Balance grid in modal */
        .lvs-balance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
        .lvs-balance-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.625rem; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .dark .lvs-balance-cell { background: #0f172a; border-color: #334155; }
        .lvs-balance-label { font-size: 0.72rem; font-weight: 700; color: #64748b; }
        .dark .lvs-balance-label { color: #94a3b8; }
        .lvs-balance-value { font-size: 1.25rem; font-weight: 800; color: #0f172a; }
        .dark .lvs-balance-value { color: #f1f5f9; }

        .report-icon-xs { width: 14px !important; height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        [x-cloak] { display: none !important; }
    </style>

    <div class="report-page-container lvs-leaves-page">
        @if(!$details)
            @error('status')
                <p role="alert" class="text-danger-600">{{ $message }}</p>
            @enderror
        @endif
        {{-- KPI Cards (5 Cards) --}}
        @php
            $stats = $this->stats;
            $cards = [
                [
                    'title' => __('leaves.summary.cards.total_employees'),
                    'value' => number_format($stats['employees_count']),
                    'subtitle' => __('leaves.summary.cards.employees_count_unit'),
                    'icon' => 'heroicon-o-users',
                    'bg' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'text' => '#1d4ed8',
                    'icon_bg' => '#dbeafe',
                ],
                [
                    'title' => __('leaves.summary.cards.on_leave_today'),
                    'value' => number_format($stats['on_leave_today_count']),
                    'subtitle' => __('leaves.summary.status.on_leave'),
                    'icon' => 'heroicon-o-calendar-days',
                    'bg' => '#f0fdf4',
                    'border' => '#bbf7d0',
                    'text' => '#15803d',
                    'icon_bg' => '#dcfce7',
                ],
                [
                    'title' => __('leaves.summary.cards.pending_requests'),
                    'value' => number_format($stats['pending_requests_count']),
                    'subtitle' => __('leaves.summary.cards.requests_unit'),
                    'icon' => 'heroicon-o-clock',
                    'bg' => '#fffbeb',
                    'border' => '#fde68a',
                    'text' => '#b45309',
                    'icon_bg' => '#fef3c7',
                ],
                [
                    'title' => __('leaves.summary.cards.total_used_days'),
                    'value' => number_format($stats['total_approved_days']),
                    'subtitle' => __('leaves.summary.cards.days_unit'),
                    'icon' => 'heroicon-o-check-circle',
                    'bg' => '#eef2ff',
                    'border' => '#c7d2fe',
                    'text' => '#4338ca',
                    'icon_bg' => '#e0e7ff',
                ],
                [
                    'title' => __('leaves.summary.cards.total_available_days'),
                    'value' => number_format($stats['total_available_annual_days']),
                    'subtitle' => __('leaves.summary.cards.days_unit'),
                    'icon' => 'heroicon-o-sparkles',
                    'bg' => '#faf5ff',
                    'border' => '#e9d5ff',
                    'text' => '#7e22ce',
                    'icon_bg' => '#f3e8ff',
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
            :title="__('leaves.summary.filters.search')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('leaves.fields.company')"
            :multiple-company="true"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('leaves.fields.branch')"
            :multiple-branch="true"
            :show-status="false"
            :show-dates="false"
            :show-search="true"
            :search-placeholder="__('leaves.summary.filters.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <a href="{{ route('leaves.summary.print', $this->filterState()) }}" target="_blank" rel="noopener" class="lvs-export-btn">
                    <x-heroicon-o-printer class="report-icon-sm" />
                    <span>{{ __('leaves.summary.print.summary_action') }}</span>
                </a>
                <button type="button" wire:click="exportCsv" class="lvs-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('leaves.summary.filters.export_excel') }}</span>
                </button>
            </x-slot>

            <x-slot name="append">
                {{-- Year selector --}}
                <x-filter-select
                    wire:model.live="year"
                    :label="__('leaves.summary.filters.year')"
                    :placeholder="__('leaves.summary.filters.all_years')"
                    :options="$this->yearOptions"
                    :multiple="false"
                    :searchable="false"
                />

                {{-- Leave filter selector --}}
                <x-filter-select
                    wire:model.live="leaveFilter"
                    :label="__('leaves.summary.filters.leave_filter')"
                    :placeholder="__('leaves.summary.filters.leave_filters.all')"
                    :options="[
                        'on_leave' => __('leaves.summary.filters.leave_filters.on_leave'),
                        'has_pending' => __('leaves.summary.filters.leave_filters.has_pending'),
                    ]"
                    :multiple="false"
                    :searchable="false"
                />

                {{-- Employment Status selector --}}
                <x-filter-select
                    wire:model.live="employmentStatus"
                    :label="__('leaves.summary.filters.employment_status')"
                    :placeholder="__('reports.filters.status_all')"
                    :options="[
                        'active' => __('leaves.summary.filters.employment_statuses.active'),
                        'inactive' => __('leaves.summary.filters.employment_statuses.inactive'),
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
                                <span>{{ __('leaves.summary.columns.employee') }}</span>
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('leaves.summary.columns.national_id') }}</th>
                        <th>{{ __('leaves.summary.columns.branch') }}</th>
                        <th>{{ __('leaves.summary.columns.job_title') }}</th>
                        <th>{{ __('leaves.summary.columns.annual_rate') }}</th>
                        <th>{{ __('leaves.summary.columns.accrued_days') }}</th>
                        <th>{{ __('leaves.summary.columns.historical_used_days') }}</th>
                        <th>
                            <span style="color: #15803d; font-weight: 800;">{{ __('leaves.summary.columns.available_days') }}</span>
                        </th>
                        <th>{{ __('leaves.summary.columns.year_used_days') }} ({{ $this->year ?? __('leaves.summary.filters.all_years') }})</th>
                        <th>{{ __('leaves.summary.columns.pending_leaves') }}</th>
                        <th>{{ __('leaves.summary.columns.status_today') }}</th>
                        <th style="text-align: center;">{{ __('leaves.summary.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $index => $row)
                        @php
                            $balance = $this->balanceFor($row);
                            $isOnLeave = (int) ($row->is_on_leave_today ?? 0) === 1;
                            $pendingCount = (int) ($row->pending_leaves_count ?? 0);
                        @endphp
                        <tr wire:key="leave-row-{{ $row->id }}">
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
                            <td style="font-weight: 600;">{{ $balance['annual_rate'] }} {{ __('leaves.units.day') }}</td>
                            <td style="font-weight: 600;">{{ $balance['accrued_days'] }}</td>
                            <td style="color: #dc2626; font-weight: 600;">{{ $balance['used_days'] }}</td>
                            <td>
                                @if($balance['available_days'] > 0)
                                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 0.375rem; background: #dcfce7; color: #15803d; font-weight: 800; font-size: 0.85rem;">
                                        {{ $balance['available_days'] }} {{ __('leaves.units.day') }}
                                    </span>
                                @else
                                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 0.375rem; background: #fee2e2; color: #991b1b; font-weight: 800; font-size: 0.85rem;">
                                        0 {{ __('leaves.units.day') }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-weight: 700; color: #475569;">{{ (int) ($row->total_approved_days ?? 0) }} {{ __('leaves.units.day') }}</td>
                            <td>
                                @if($pendingCount > 0)
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 0.375rem; background: #fef3c7; color: #b45309; font-weight: 800; font-size: 0.75rem;">
                                        {{ $pendingCount }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td>
                                @if($isOnLeave)
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; border-radius: 0.375rem; background: #fef3c7; color: #b45309; font-weight: 700; font-size: 0.75rem;">
                                        <x-heroicon-s-sun class="report-icon-xs" />
                                        <span>{{ __('leaves.summary.status.on_leave') }}</span>
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; border-radius: 0.375rem; background: #f0fdf4; color: #166534; font-weight: 700; font-size: 0.75rem;">
                                        <x-heroicon-s-check class="report-icon-xs" />
                                        <span>{{ __('leaves.summary.status.working') }}</span>
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <button type="button" wire:click="openDetails({{ $row->id }})" class="lvs-details-btn">
                                    <x-heroicon-o-eye class="report-icon-xs" />
                                    <span>{{ __('leaves.summary.columns.view_details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                                <x-heroicon-o-inbox class="report-icon-lg" style="margin: 0 auto 0.5rem; display: block; opacity: 0.6;" />
                                <div>{{ __('leaves.table.no_records') }}</div>
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
            <div class="lvs-overlay" wire:click.self="closeDetails">
                <div class="lvs-modal" role="dialog" aria-modal="true">
                    {{-- Modal Header --}}
                    <div class="lvs-modal-header">
                        <div class="lvs-modal-title">
                            <x-heroicon-o-document-text class="report-icon-md" style="color: #2563eb;" />
                            <span>{{ __('leaves.summary.modal.title') }} — {{ $details['employee']->user?->name ?? '' }}</span>
                        </div>
                        <button type="button" wire:click="closeDetails" class="lvs-modal-close" aria-label="Close">
                            <x-heroicon-o-x-mark class="report-icon-sm" />
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="lvs-modal-body">
                        @error('status')
                            <p role="alert" class="text-danger-600">{{ $message }}</p>
                        @enderror
                        {{-- Employee Info Strip --}}
                        <div class="lvs-emp-strip">
                            <div class="lvs-strip-item">
                                <span>🆔 {{ __('leaves.fields.record_number') }}:</span>
                                <strong>{{ $details['employee']->employee_number ?? '—' }}</strong>
                            </div>
                            <div class="lvs-strip-item">
                                <span>🏢 {{ __('leaves.fields.company') }}:</span>
                                <strong>{{ $details['employee']->company?->trade_name ?? '—' }}</strong>
                            </div>
                            <div class="lvs-strip-item">
                                <span>📍 {{ __('leaves.fields.branch') }}:</span>
                                <strong>{{ $details['employee']->branch?->name ?? '—' }}</strong>
                            </div>
                            <div class="lvs-strip-item">
                                <span>💼 {{ __('leaves.fields.job_title') }}:</span>
                                <strong>{{ $details['employee']->jobTitle?->name ?? '—' }}</strong>
                            </div>
                            <div class="lvs-strip-item">
                                <span>📅 {{ __('leaves.summary.modal.hire_date') }}</span>
                                <strong>{{ $details['balance']['hire_date'] ?? '—' }}</strong>
                            </div>
                            <div class="lvs-strip-item">
                                <span>⏳ {{ __('leaves.summary.modal.service_duration') }}</span>
                                <strong>{{ $details['balance']['service_months'] ?? 0 }} {{ __('leaves.summary.modal.months') }}</strong>
                            </div>
                        </div>

                        {{-- Active Leave Alert Banner --}}
                        @if($details['active_leave'])
                            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.625rem; padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: #b45309; font-weight: 700; font-size: 0.85rem;">
                                    <x-heroicon-s-information-circle class="report-icon-md" />
                                    <span>{{ __('leaves.summary.modal.current_leave', ['date' => $details['active_leave']->end_date?->format('Y-m-d')]) }} ({{ $details['active_leave']->leaveType?->name ?? '' }})</span>
                                </div>
                                <span style="font-size: 0.75rem; background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 0.375rem; font-weight: 800;">
                                    {{ $details['active_leave']->days }} {{ __('leaves.units.day') }}
                                </span>
                            </div>
                        @endif

                        {{-- Annual Balance Cards Grid --}}
                        <div class="lvs-balance-grid">
                            <div class="lvs-balance-cell">
                                <span class="lvs-balance-label">{{ __('leaves.summary.modal.annual_rate_label') }}</span>
                                <span class="lvs-balance-value">{{ $details['balance']['annual_rate'] ?? 21 }} {{ __('leaves.units.day') }}</span>
                            </div>
                            <div class="lvs-balance-cell">
                                <span class="lvs-balance-label">{{ __('leaves.summary.modal.accrued_label') }}</span>
                                <span class="lvs-balance-value" style="color: #2563eb;">{{ $details['balance']['accrued_days'] ?? 0 }} {{ __('leaves.units.day') }}</span>
                            </div>
                            <div class="lvs-balance-cell">
                                <span class="lvs-balance-label">{{ __('leaves.summary.modal.used_label') }}</span>
                                <span class="lvs-balance-value" style="color: #dc2626;">{{ $details['balance']['used_days'] ?? 0 }} {{ __('leaves.units.day') }}</span>
                            </div>
                            <div class="lvs-balance-cell" style="background: #f0fdf4; border-color: #bbf7d0;">
                                <span class="lvs-balance-label" style="color: #166534;">{{ __('leaves.summary.modal.available_label') }}</span>
                                <span class="lvs-balance-value" style="color: #15803d;">{{ $details['balance']['available_days'] ?? 0 }} {{ __('leaves.units.day') }}</span>
                            </div>
                            <div class="lvs-balance-cell">
                                <span class="lvs-balance-label">{{ __('leaves.summary.columns.year_used_days') }} ({{ $details['year'] ?? __('leaves.summary.filters.all_years') }})</span>
                                <span class="lvs-balance-value">{{ $details['year_stats']['year_approved_days'] ?? 0 }} {{ __('leaves.units.day') }}</span>
                            </div>
                            <div class="lvs-balance-cell">
                                <span class="lvs-balance-label">{{ __('leaves.summary.cards.pending_requests') }}</span>
                                <span class="lvs-balance-value" style="color: #b45309;">{{ $details['year_stats']['year_pending_count'] ?? 0 }}</span>
                            </div>
                        </div>

                        {{-- Leaves History Table --}}
                        <div style="margin-top: 0.5rem;">
                            <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 0.5rem; color: #0f172a;" class="dark:text-white">
                                📋 {{ __('leaves.summary.modal.history_tab') }} ({{ $details['leaves']->count() }})
                            </div>
                            <div class="report-table-container" style="max-height: 340px; overflow-y: auto;">
                                <table class="report-table" style="min-width: 800px; font-size: 0.8125rem;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('leaves.fields.leave_type') }}</th>
                                            <th>{{ __('leaves.fields.start_date') }}</th>
                                            <th>{{ __('leaves.fields.end_date') }}</th>
                                            <th>{{ __('leaves.fields.days') }}</th>
                                            <th>{{ __('leaves.fields.status') }}</th>
                                            <th>{{ __('leaves.fields.return_status') }}</th>
                                            <th>{{ __('leaves.fields.reason') }}</th>
                                            <th style="text-align: center;">{{ __('leaves.summary.columns.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($details['leaves'] as $leave)
                                            <tr>
                                                <td style="font-weight: 700;">{{ $leave->leaveType?->name ?? '—' }}</td>
                                                <td>
                                                    <div>{{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') : '—' }}</div>
                                                    <div style="font-size: 0.7rem; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($leave->start_date) }}</div>
                                                </td>
                                                <td>
                                                    <div>{{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') : '—' }}</div>
                                                    <div style="font-size: 0.7rem; color: #64748b;">{{ \App\Services\HijriDateService::toHijriNumeric($leave->end_date) }}</div>
                                                </td>
                                                <td style="font-weight: 700;">{{ $leave->days }} {{ __('leaves.units.day') }}</td>
                                                <td>
                                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700;
                                                        @if($leave->status === \App\Enums\LeaveStatus::APPROVED) background: #dcfce7; color: #15803d;
                                                        @elseif($leave->status === \App\Enums\LeaveStatus::PENDING) background: #fef3c7; color: #b45309;
                                                        @elseif($leave->status === \App\Enums\LeaveStatus::REJECTED) background: #fee2e2; color: #991b1b;
                                                        @else background: #f1f5f9; color: #475569; @endif">
                                                        {{ $leave->status?->getLabel() ?? $leave->status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="font-size: 0.75rem;">{{ $leave->return_status_label }}</span>
                                                </td>
                                                <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $leave->reason }}">
                                                    {{ $leave->reason ?: '—' }}
                                                </td>
                                                <td style="text-align: center;">
                                                    <div style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                                        @if($leave->status === \App\Enums\LeaveStatus::PENDING && \App\Services\LeaveSummaryService::canChangeStatus($leave))
                                                            <button type="button" wire:click="approveLeave({{ $leave->id }})" class="lvs-action-btn" style="color: #15803d; background: #dcfce7; border: 1px solid #bbf7d0; padding: 0.2rem 0.4rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                                                                ✓ {{ __('leaves.actions.approve') }}
                                                            </button>
                                                            <button type="button" wire:click="startRejection({{ $leave->id }})" class="lvs-action-btn" style="color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: 0.2rem 0.4rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                                                                ✕ {{ __('leaves.actions.reject') }}
                                                            </button>
                                                        @endif
                                                        <a href="{{ route('leaves.print', $leave) }}" target="_blank" class="lvs-action-btn" style="color: #475569; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.2rem 0.4rem; border-radius: 0.375rem; font-size: 0.75rem; text-decoration: none;">
                                                            🖨️
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @if($rejectingLeaveId === $leave->id)
                                                <tr wire:key="reject-leave-{{ $leave->id }}">
                                                    <td colspan="8">
                                                        <form wire:submit="rejectLeave({{ $leave->id }})" class="flex flex-wrap items-center gap-2">
                                                            <label for="leave-rejection-reason">{{ __('leaves.fields.rejection_reason') }}</label>
                                                            <input id="leave-rejection-reason" type="text" wire:model="rejectionReason" required maxlength="1000" placeholder="{{ __('leaves.fields.rejection_reason_placeholder') }}" class="rounded-lg dark:bg-gray-900">
                                                            <button type="submit" class="lvs-details-btn">{{ __('leaves.summary.modal.confirm_rejection') }}</button>
                                                            <button type="button" wire:click="cancelRejection" class="lvs-details-btn">{{ __('leaves.summary.modal.cancel_rejection') }}</button>
                                                            @error('rejectionReason') <span role="alert" class="text-danger-600">{{ $message }}</span> @enderror
                                                            @error('rejection_reason') <span role="alert" class="text-danger-600">{{ $message }}</span> @enderror
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="8" style="text-align: center; padding: 1.5rem; color: #94a3b8;">
                                                    {{ __('leaves.summary.modal.no_leaves') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="lvs-modal-footer">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ \App\Filament\Resources\Leaves\LeaveResource::getUrl('create') }}?employee_id={{ $details['employee']->id }}" class="lvs-export-btn" style="background: #2563eb;">
                                <x-heroicon-o-plus-circle class="report-icon-sm" />
                                <span>{{ __('leaves.summary.modal.new_request_btn') }}</span>
                            </a>
                            <a href="{{ route('leaves.statement.print', ['employee' => $details['employee']->id, 'year' => $detailsYear ?? 'all']) }}" target="_blank" rel="noopener" class="lvs-export-btn" style="background: #0f172a;">
                                <x-heroicon-o-printer class="report-icon-sm" />
                                <span>{{ __('leaves.summary.modal.print_statement_btn') }}</span>
                            </a>
                        </div>
                        <button type="button" wire:click="closeDetails" class="lvs-export-btn" style="background: #64748b;">
                            {{ __('leaves.summary.modal.close') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

