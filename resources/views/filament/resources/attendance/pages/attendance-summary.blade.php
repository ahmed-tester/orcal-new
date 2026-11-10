<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .ats-attendance-page .report-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.875rem; }
        .ats-attendance-page .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; min-width: 0; min-height: 116px; box-sizing: border-box; box-shadow: 0 1px 2px rgba(15,23,42,0.025); display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .ats-attendance-page .report-stat-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15,23,42,0.045); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ats-attendance-page .report-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ats-attendance-page .report-stat-metric { display: flex; align-items: baseline; gap: 0.4rem; min-height: 32px; }
        .ats-attendance-page .report-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #0f172a; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }

        .ats-attendance-page .fb-card { padding: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,0.025); }
        .ats-attendance-page .fb-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .ats-attendance-page .fb-select-trigger { height: 42px; border-radius: 0.625rem; }
        .ats-attendance-page .fb-search-wrap { grid-column: 1 / -1; margin-top: 0; }
        .ats-attendance-page .fb-search-input { height: 44px; border-radius: 0.625rem; }
        .ats-attendance-page .fb-header-actions > :is(button, a) { min-height: 38px; box-sizing: border-box; }

        @media (max-width: 900px) {
            .ats-attendance-page .report-stats-grid,
            .ats-attendance-page .fb-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 480px) {
            .ats-attendance-page .report-stats-grid { gap: 0.625rem; }
            .ats-attendance-page .report-stat-card { padding: 0.75rem; min-height: 108px; }
            .ats-attendance-page .report-stat-title { font-size: 0.75rem; }
            .ats-attendance-page .report-stat-icon-wrapper { width: 30px; height: 30px; }
            .ats-attendance-page .report-stat-value { font-size: 1.5rem; }
            .ats-attendance-page .fb-grid { grid-template-columns: minmax(0, 1fr); }
            .ats-attendance-page .fb-header-actions { width: 100%; }
            .ats-attendance-page .fb-header-actions > :is(button, a) { flex: 1; justify-content: center; }
        }

        .ats-export-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background-color 0.15s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); }
        .ats-export-btn:hover { background: #1e293b; }
        .dark .ats-export-btn { background: #2563eb; }
        .dark .ats-export-btn:hover { background: #1d4ed8; }

        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .report-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 900px; }
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
        .report-table tfoot tr { background-color: #f0fdf4; border-top: 2px solid #bbf7d0; font-weight: 800; }
        .dark .report-table tfoot tr { background-color: rgba(22, 101, 52, 0.2); border-top-color: #15803d; }
        .report-table tfoot td { padding: 0.875rem; color: #166534; font-weight: 800; }
        .dark .report-table tfoot td { color: #86efac; }

        .ats-details-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .ats-details-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .dark .ats-details-btn { background: rgba(37,99,235,0.15); border-color: #1e40af; color: #60a5fa; }
        .dark .ats-details-btn:hover { background: rgba(37,99,235,0.25); }

        /* Modal Styles */
        .ats-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .ats-modal { background: #ffffff; border-radius: 1rem; width: 100%; max-width: 1180px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; }
        .dark .ats-modal { background: #1e293b; }
        .ats-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; }
        .dark .ats-modal-header { background: #0f172a; border-bottom-color: #334155; }
        .ats-modal-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; }
        .dark .ats-modal-title { color: #f8fafc; }
        .ats-modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; }
        .dark .ats-modal-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .ats-modal-close:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .ats-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .ats-modal-footer { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; flex-shrink: 0; background: #f8fafc; }
        .dark .ats-modal-footer { background: #0f172a; border-top-color: #334155; }

        /* Month Navigation Strip */
        .ats-month-nav { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; padding: 0.75rem 1rem; background: #f1f5f9; border-radius: 0.75rem; border: 1px solid #e2e8f0; }
        .dark .ats-month-nav { background: #0f172a; border-color: #334155; }
        .ats-nav-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.85rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .ats-nav-btn:hover { background: #f8fafc; border-color: #94a3b8; }
        .dark .ats-nav-btn { background: #1e293b; border-color: #475569; color: #f8fafc; }
        .dark .ats-nav-btn:hover { background: #334155; }
        .ats-nav-current { font-size: 1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; }
        .dark .ats-nav-current { color: #f8fafc; }

        /* Mini stats in modal */
        .ats-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.65rem; }
        .ats-summary-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.625rem; padding: 0.6rem 0.75rem; display: flex; flex-direction: column; gap: 0.2rem; }
        .dark .ats-summary-cell { background: #0f172a; border-color: #334155; }
        .ats-summary-label { font-size: 0.72rem; font-weight: 700; color: #64748b; }
        .dark .ats-summary-label { color: #94a3b8; }
        .ats-summary-value { font-size: 1rem; font-weight: 800; color: #0f172a; }
        .dark .ats-summary-value { color: #f1f5f9; }

        /* Action buttons */
        .ats-action-add { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px dashed #cbd5e1; background: #ffffff; color: #2563eb; font-size: 0.75rem; font-weight: 700; text-decoration: none; cursor: pointer; }
        .ats-action-add:hover { background: #eff6ff; border-color: #93c5fd; }
        .dark .ats-action-add { background: #0f172a; border-color: #475569; color: #60a5fa; }
        .ats-action-edit { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; background: #ffffff; color: #475569; font-size: 0.75rem; font-weight: 700; text-decoration: none; cursor: pointer; }
        .ats-action-edit:hover { background: #f8fafc; color: #0f172a; }
        .dark .ats-action-edit { background: #0f172a; border-color: #334155; color: #cbd5e1; }

        /* Loading overlay */
        .ats-loading-overlay { position: fixed; inset: 0; z-index: 120; background: rgba(255, 255, 255, 0.55); display: none; backdrop-filter: blur(1px); }
        .ats-loading-overlay.ats-loading-active { display: flex; align-items: center; justify-content: center; }
        .dark .ats-loading-overlay { background: rgba(15, 23, 42, 0.55); }
        .ats-spinner { width: 42px; height: 42px; border: 4px solid #bfdbfe; border-top-color: #2563eb; border-radius: 9999px; animation: ats-spin 0.8s linear infinite; }
        @keyframes ats-spin { to { transform: rotate(360deg); } }

        .report-icon-xs { width: 14px !important; height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        [x-cloak] { display: none !important; }
    </style>

    <div class="report-page-container ats-attendance-page">
        {{-- KPI Cards (8 Cards) --}}
        @php
            $stats = $this->stats;
            $minsUnit = __('attendance.units.minute');
            $hoursUnit = __('attendance.units.hour');
            $cards = [
                ['title' => __('attendance.summary.stats.employees'), 'value' => number_format($stats['employees_count'] ?? 0), 'icon' => 'heroicon-o-users', 'bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
                ['title' => __('attendance.summary.stats.recorded_days'), 'value' => number_format($stats['recorded_days'] ?? 0), 'icon' => 'heroicon-o-calendar', 'bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1'],
                ['title' => __('attendance.summary.stats.present_days'), 'value' => number_format($stats['present_days'] ?? 0), 'icon' => 'heroicon-o-check-circle', 'bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0'],
                ['title' => __('attendance.summary.stats.late_days'), 'value' => number_format($stats['late_days'] ?? 0), 'icon' => 'heroicon-o-clock', 'bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a'],
                ['title' => __('attendance.summary.stats.absent_days'), 'value' => number_format($stats['absent_days'] ?? 0), 'icon' => 'heroicon-o-x-circle', 'bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
                ['title' => __('attendance.summary.stats.leave_days'), 'value' => number_format($stats['leave_days'] ?? 0), 'icon' => 'heroicon-o-calendar-days', 'bg' => '#f5f3ff', 'text' => '#7c3aed', 'border' => '#ddd6fe'],
                ['title' => __('attendance.summary.stats.late_minutes'), 'value' => number_format($stats['late_minutes'] ?? 0), 'subtitle' => $minsUnit, 'icon' => 'heroicon-o-exclamation-triangle', 'bg' => '#fff7ed', 'text' => '#ea580c', 'border' => '#ffedd5'],
                ['title' => __('attendance.summary.stats.total_hours'), 'value' => number_format($stats['total_hours'] ?? 0, 2), 'subtitle' => $hoursUnit, 'icon' => 'heroicon-o-chart-bar', 'bg' => '#f0fdfa', 'text' => '#0d9488', 'border' => '#ccfbf1'],
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
                    <div class="report-stat-metric">
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

        {{-- Unified Filter Bar --}}
        <x-filter-bar
            :title="__('attendance.summary.filters.search')"
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
            :search-placeholder="__('attendance.summary.filters.search_placeholder')"
            :has-active="$this->hasActiveFilters()"
            reset-method="resetFilters"
        >
            <x-slot name="actions">
                <a href="{{ route('attendance.summary.print', $this->filterState()) }}" target="_blank" rel="noopener" class="ats-export-btn">
                    <x-heroicon-o-printer class="report-icon-sm" />
                    <span>{{ __('attendance.summary.print.summary_action') }}</span>
                </a>
                <button type="button" wire:click="exportCsv" class="ats-export-btn">
                    <x-heroicon-o-arrow-down-tray class="report-icon-sm" />
                    <span>{{ __('attendance.summary.filters.export_excel') }}</span>
                </button>
            </x-slot>

            <x-slot name="append">
                {{-- Month selector --}}
                <x-filter-select
                    wire:model.live="month"
                    :label="__('attendance.summary.filters.month')"
                    :placeholder="__('attendance.summary.filters.all_months')"
                    :options="$this->monthOptions"
                    :multiple="false"
                    :searchable="true"
                />

                {{-- Employment Status selector --}}
                <x-filter-select
                    wire:model.live="employmentStatus"
                    :label="__('attendance.summary.filters.employment_status')"
                    :placeholder="__('reports.filters.status_all')"
                    :options="[
                        'active' => __('attendance.summary.filters.employment_statuses.active'),
                        'inactive' => __('attendance.summary.filters.employment_statuses.inactive'),
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
                                <span>{{ __('attendance.summary.columns.employee') }}</span>
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('attendance.summary.columns.national_id') }}</th>
                        <th>{{ __('attendance.summary.columns.branch') }}</th>
                        <th>{{ __('attendance.summary.columns.job_title') }}</th>
                        <th>
                            <button type="button" wire:click="sortBy('recorded_days')" style="{{ $sortColumn === 'recorded_days' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.recorded_days') }}</span>
                                @if($sortColumn === 'recorded_days')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('present_days')" style="{{ $sortColumn === 'present_days' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.present_days') }}</span>
                                @if($sortColumn === 'present_days')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('late_days')" style="{{ $sortColumn === 'late_days' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.late_days') }}</span>
                                @if($sortColumn === 'late_days')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('absent_days')" style="{{ $sortColumn === 'absent_days' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.absent_days') }}</span>
                                @if($sortColumn === 'absent_days')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('late_minutes')" style="{{ $sortColumn === 'late_minutes' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.late_minutes') }}</span>
                                @if($sortColumn === 'late_minutes')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" wire:click="sortBy('total_hours')" style="{{ $sortColumn === 'total_hours' ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                <span>{{ __('attendance.summary.columns.total_hours') }}</span>
                                @if($sortColumn === 'total_hours')
                                    @if($sortDirection === 'asc') <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                    @else <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" /> @endif
                                @else
                                    <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                @endif
                            </button>
                        </th>
                        <th>{{ __('attendance.summary.columns.last_date') }}</th>
                        <th style="text-align: center;">{{ __('attendance.summary.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr>
                            <td style="font-weight: 700;">{{ ($summary->firstItem() ?? 0) + $loop->index }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem; align-items: flex-start;">
                                    <span style="font-weight: 800; color: #0f172a;" class="dark:text-white">{{ $this->nameOf($row) }}</span>
                                    <span style="font-size: 0.75rem; font-family: monospace; color: #64748b;">
                                        {{ $row->employee_number ?? "ID: {$row->id}" }}
                                    </span>
                                </div>
                            </td>
                            <td style="font-family: monospace;">{{ $this->nationalIdOf($row) ?? '—' }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                    <span style="font-weight: 600;">{{ $this->branchOf($row) ?? '—' }}</span>
                                    <span style="font-size: 0.72rem; color: #64748b;">{{ $this->companyOf($row) ?? '—' }}</span>
                                </div>
                            </td>
                            <td>{{ $this->jobTitleOf($row) ?? '—' }}</td>
                            <td><span style="font-weight: 700;">{{ (int) $row->recorded_days }}</span></td>
                            <td><span style="font-weight: 700; color: #059669;">{{ (int) $row->present_days }}</span></td>
                            <td>
                                <span style="font-weight: 700; {{ (int) $row->late_days > 0 ? 'color: #d97706;' : 'color: #94a3b8;' }}">
                                    {{ (int) $row->late_days }}
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 800; {{ (int) $row->absent_days > 0 ? 'color: #dc2626;' : 'color: #94a3b8;' }}">
                                    {{ (int) $row->absent_days }}
                                </span>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; {{ (float) $row->late_minutes > 0 ? 'color: #ea580c;' : 'color: #94a3b8;' }}">
                                    {{ (int) round((float) $row->late_minutes) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; color: #0f172a;" class="dark:text-white">
                                    {{ number_format((float) $row->total_hours, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($row->last_date)
                                    <span style="font-size: 0.8125rem; font-family: monospace;">{{ \Carbon\Carbon::parse($row->last_date)->format('Y-m-d') }}</span>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <button type="button" wire:click="openDetails({{ $row->id }})" class="ats-details-btn">
                                    <x-heroicon-m-eye class="report-icon-sm" />
                                    <span>{{ __('attendance.summary.view_details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" style="padding: 3.5rem 1.5rem; text-align: center; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div style="width: 52px; height: 52px; border-radius: 9999px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin-bottom: 0.875rem;">
                                        <x-heroicon-o-users class="report-icon-lg" />
                                    </div>
                                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ __('attendance.summary.empty') }}</h3>
                                    <p style="font-size: 0.8125rem; color: #64748b; max-width: 24rem;">{{ __('attendance.summary.empty_hint') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: end; padding-inline-end: 1rem;">{{ __('attendance.summary.columns.totals') }}:</td>
                        <td>{{ number_format($stats['recorded_days']) }}</td>
                        <td style="color: #059669;">{{ number_format($stats['present_days']) }}</td>
                        <td style="color: #d97706;">{{ number_format($stats['late_days']) }}</td>
                        <td style="color: #dc2626;">{{ number_format($stats['absent_days']) }}</td>
                        <td style="color: #ea580c;">{{ number_format($stats['late_minutes']) }}</td>
                        <td>{{ number_format($stats['total_hours'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            @if($summary->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$summary" :page-options="[10, 25, 50, 100]" current-page-option-property="perPage" />
                </div>
            @endif
        </div>
    </div>

    {{-- Loading Overlay for Modal and Navigation Actions --}}
    <div class="ats-loading-overlay" wire:loading.class="ats-loading-active" wire:target="openDetails, setDetailsMonth, previousDetailsMonth, nextDetailsMonth, currentDetailsMonth">
        <div class="ats-spinner" aria-hidden="true"></div>
    </div>

    {{-- Employee Monthly Attendance Modal --}}
    @if($this->details)
        @php
            $emp = $this->details['employee'];
            $modalStats = $this->details['stats'];
        @endphp
        <div class="ats-overlay" x-data @keydown.escape.window="$wire.closeDetails()" @click.self="$wire.closeDetails()">
            <div class="ats-modal" role="dialog" aria-modal="true" aria-label="{{ __('attendance.summary.modal.title') }}">
                {{-- Modal Header --}}
                <div class="ats-modal-header">
                    <div class="ats-modal-title">
                        <x-heroicon-o-calendar-days class="report-icon-md" style="color: #2563eb;" />
                        <span>{{ __('attendance.summary.modal.title') }}</span>
                        <span style="font-weight: 500; color: #64748b; font-size: 0.9rem;">— {{ $this->nameOf($emp) }} ({{ $emp->employee_number ?? "ID: {$emp->id}" }})</span>
                    </div>
                    <button type="button" wire:click="closeDetails" class="ats-modal-close" aria-label="{{ __('attendance.summary.modal.close') }}">
                        <x-heroicon-m-x-mark class="report-icon-sm" />
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="ats-modal-body">
                    {{-- Employee Mini Info Bar --}}
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 0.625rem; border: 1px solid #e2e8f0; font-size: 0.8125rem;">
                        <div><strong style="color: #64748b;">{{ __('attendance.summary.columns.company') }}:</strong> <span style="font-weight: 700;">{{ $this->companyOf($emp) }}</span></div>
                        <div><strong style="color: #64748b;">{{ __('attendance.summary.columns.branch') }}:</strong> <span style="font-weight: 700;">{{ $this->branchOf($emp) }}</span></div>
                        <div><strong style="color: #64748b;">{{ __('attendance.summary.columns.job_title') }}:</strong> <span style="font-weight: 700;">{{ $this->jobTitleOf($emp) }}</span></div>
                        <div><strong style="color: #64748b;">{{ __('attendance.summary.columns.national_id') }}:</strong> <span style="font-family: monospace; font-weight: 700;">{{ $this->nationalIdOf($emp) ?? '—' }}</span></div>
                    </div>

                    {{-- Month Navigator Strip --}}
                    <div class="ats-month-nav">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <button type="button" wire:click="previousDetailsMonth" class="ats-nav-btn" title="{{ __('attendance.summary.modal.previous_month') }}">
                                <x-heroicon-m-chevron-right class="report-icon-xs" />
                                <span>{{ __('attendance.summary.modal.previous_month') }}</span>
                            </button>
                            <button type="button" wire:click="currentDetailsMonth" class="ats-nav-btn">
                                <span>{{ __('attendance.summary.modal.current_month') }}</span>
                            </button>
                            <button type="button" wire:click="nextDetailsMonth" class="ats-nav-btn" title="{{ __('attendance.summary.modal.next_month') }}">
                                <span>{{ __('attendance.summary.modal.next_month') }}</span>
                                <x-heroicon-m-chevron-left class="report-icon-xs" />
                            </button>
                        </div>
                        <div class="ats-nav-current">
                            <span>{{ $this->details['month_label'] }}</span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">({{ $this->details['month_hijri'] }})</span>
                        </div>
                    </div>

                    {{-- Monthly Mini KPIs Strip --}}
                    <div class="ats-summary-grid">
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.present') }}</span>
                            <span class="ats-summary-value" style="color: #059669;">{{ (int) $modalStats['present_days'] }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.late') }}</span>
                            <span class="ats-summary-value" style="color: #d97706;">{{ (int) $modalStats['late_days'] }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.absent') }}</span>
                            <span class="ats-summary-value" style="color: #dc2626;">{{ (int) $modalStats['absent_days'] }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.leave') }}</span>
                            <span class="ats-summary-value" style="color: #7c3aed;">{{ (int) $modalStats['leave_days'] }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.unrecorded') }}</span>
                            <span class="ats-summary-value" style="color: #64748b;">{{ (int) $modalStats['unrecorded_days'] }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.late_total') }}</span>
                            <span class="ats-summary-value" style="color: #ea580c;">{{ (int) $modalStats['late_minutes'] }} {{ __('attendance.units.minute') }}</span>
                        </div>
                        <div class="ats-summary-cell">
                            <span class="ats-summary-label">{{ __('attendance.summary.modal.stats.hours_total') }}</span>
                            <span class="ats-summary-value" style="color: #0d9488;">{{ number_format((float) $modalStats['total_hours'], 2) }} {{ __('attendance.units.hour') }}</span>
                        </div>
                    </div>

                    {{-- Day-by-Day Attendance Table --}}
                    <div class="report-table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>{{ __('attendance.summary.modal.day') }}</th>
                                    <th>{{ __('attendance.summary.modal.date') }}</th>
                                    <th>{{ __('attendance.summary.modal.hijri') }}</th>
                                    <th>{{ __('attendance.summary.modal.check_in') }}</th>
                                    <th>{{ __('attendance.summary.modal.check_out') }}</th>
                                    <th>{{ __('attendance.summary.modal.late_mins') }}</th>
                                    <th>{{ __('attendance.summary.modal.overtime_mins') }}</th>
                                    <th>{{ __('attendance.summary.modal.hours') }}</th>
                                    <th>{{ __('attendance.summary.modal.status') }}</th>
                                    <th>{{ __('attendance.summary.modal.source') }}</th>
                                    <th>{{ __('attendance.summary.modal.notes') }}</th>
                                    <th style="text-align: center;">{{ __('attendance.summary.columns.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->details['days'] as $day)
                                    <tr style="{{ $day['is_weekend'] ? 'background-color: #fafafa;' : '' }}">
                                        <td style="font-weight: 700;">{{ $day['day_number'] }}</td>
                                        <td style="font-weight: 700;">
                                            <span>{{ $day['day_name'] }}</span>
                                            @if($day['is_weekend'])
                                                <span style="font-size: 0.68rem; color: #94a3b8; font-weight: normal; margin-inline-start: 0.25rem;">({{ __('attendance.summary.modal.weekend') }})</span>
                                            @endif
                                        </td>
                                        <td style="font-family: monospace;">{{ $day['date'] }}</td>
                                        <td style="font-family: monospace; color: #64748b; font-size: 0.8125rem;">{{ $day['hijri_date'] }}</td>
                                        <td>
                                            @if($day['check_in'])
                                                <span style="font-family: monospace; font-weight: 700; color: #0f172a;" class="dark:text-white">{{ $day['check_in'] }}</span>
                                            @else
                                                <span style="color: #94a3b8;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($day['check_out'])
                                                <span style="font-family: monospace; font-weight: 700; color: #0f172a;" class="dark:text-white">{{ $day['check_out'] }}</span>
                                            @else
                                                <span style="color: #94a3b8;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($day['late_minutes'] > 0)
                                                <span style="font-family: monospace; font-weight: 700; color: #ea580c;">{{ $day['late_minutes'] }}</span>
                                            @else
                                                <span style="color: #94a3b8;">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($day['overtime_minutes'] > 0)
                                                <span style="font-family: monospace; font-weight: 700; color: #059669;">{{ $day['overtime_minutes'] }}</span>
                                            @else
                                                <span style="color: #94a3b8;">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($day['total_hours'] > 0)
                                                <span style="font-family: monospace; font-weight: 700;">{{ number_format($day['total_hours'], 2) }}</span>
                                            @else
                                                <span style="color: #94a3b8;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($day['has_record'] && $day['status'])
                                                <x-filament::badge :color="$day['status_color']">
                                                    {{ $day['status_label'] }}
                                                </x-filament::badge>
                                            @else
                                                <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 9999px; background: #f1f5f9; color: #64748b; font-size: 0.72rem; font-weight: 700;">
                                                    {{ $day['status_label'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                @if($day['source_label'])
                                                    <span>{{ $day['source_label'] }}</span>
                                                @endif
                                                @if($day['device_name'])
                                                    <span style="display: block; font-size: 0.7rem; color: #94a3b8;">{{ $day['device_name'] }}</span>
                                                @endif
                                                @if(! $day['source_label'] && ! $day['device_name'])
                                                    <span>—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.78rem; color: #64748b; max-width: 140px; display: inline-block; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $day['notes'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            @if($day['has_record'])
                                                <a href="{{ \App\Filament\Resources\Attendance\AttendanceResource::getUrl('edit', ['record' => $day['id']]) }}" target="_blank" class="ats-action-edit">
                                                    <x-heroicon-m-pencil-square class="report-icon-xs" />
                                                    <span>{{ __('attendance.summary.modal.edit_record') }}</span>
                                                </a>
                                            @else
                                                <a href="{{ \App\Filament\Resources\Attendance\AttendanceResource::getUrl('create', ['employee_id' => $emp->id, 'date' => $day['date']]) }}" target="_blank" class="ats-action-add">
                                                    <x-heroicon-m-plus class="report-icon-xs" />
                                                    <span>{{ __('attendance.summary.modal.add_record') }}</span>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="ats-modal-footer">
                    <button type="button" wire:click="closeDetails" class="ats-nav-btn">
                        <span>{{ __('attendance.summary.modal.close') }}</span>
                    </button>
                    <a href="{{ route('attendance.sheet.print', ['employee' => $emp->id, 'month' => $this->details['month']]) }}" target="_blank" class="ats-export-btn" style="background: #2563eb;">
                        <x-heroicon-o-printer class="report-icon-sm" />
                        <span>{{ __('attendance.summary.modal.print_sheet') }}</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

