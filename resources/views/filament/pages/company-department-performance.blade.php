<x-filament-panels::page>
    <style>
        .company-perf-page {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Top Page Header */
        .company-perf-page .perf-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1.15rem 1.35rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .dark .company-perf-page .perf-header-bar {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        .company-perf-page .perf-header-info {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        .company-perf-page .perf-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 0.75rem;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid #bfdbfe;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.1);
        }
        .dark .company-perf-page .perf-header-icon {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border-color: rgba(37, 99, 235, 0.35);
        }
        .company-perf-page .perf-header-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin: 0;
        }
        .dark .company-perf-page .perf-header-title {
            color: #f8fafc;
        }
        .company-perf-page .perf-header-sub {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.2rem;
            line-height: 1.4;
        }
        .dark .company-perf-page .perf-header-sub {
            color: #94a3b8;
        }
        .company-perf-page .perf-header-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .company-perf-page .perf-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid #e2e8f0;
        }
        .dark .company-perf-page .perf-meta-chip {
            background: #0f172a;
            color: #cbd5e1;
            border-color: #334155;
        }

        /* Top KPI Stats Grid */
        .company-perf-page .perf-stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.875rem;
        }
        .company-perf-page .perf-stat-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 1rem 1.15rem;
            min-width: 0;
            min-height: 118px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.65rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.18s ease;
        }
        .company-perf-page .perf-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            inset-inline-start: 0;
            inset-inline-end: 0;
            height: 3px;
            background: var(--card-accent, #2563eb);
        }
        .company-perf-page .perf-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px -4px rgba(0, 0, 0, 0.08), 0 3px 6px -2px rgba(0, 0, 0, 0.03);
            border-color: #cbd5e1;
        }
        .dark .company-perf-page .perf-stat-card {
            background-color: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        }
        .dark .company-perf-page .perf-stat-card:hover {
            border-color: #475569;
            box-shadow: 0 8px 18px -4px rgba(0, 0, 0, 0.4);
        }
        .company-perf-page .perf-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .company-perf-page .perf-stat-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #334155;
            line-height: 1.3;
        }
        .dark .company-perf-page .perf-stat-title {
            color: #e2e8f0;
        }
        .company-perf-page .perf-stat-icon-wrapper {
            width: 36px;
            height: 36px;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.15s ease;
        }
        .company-perf-page .perf-stat-card:hover .perf-stat-icon-wrapper {
            transform: scale(1.05);
        }
        .company-perf-page .perf-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .company-perf-page .perf-stat-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }
        .company-perf-page .perf-stat-hint {
            font-size: 0.715rem;
            font-weight: 500;
            color: #64748b;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .company-perf-page .perf-stat-hint {
            color: #94a3b8;
        }
        .company-perf-page .perf-stat-action-hint {
            font-size: 0.685rem;
            font-weight: 700;
            color: var(--card-accent, #2563eb);
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            flex-shrink: 0;
            opacity: 0.85;
            transition: opacity 0.15s;
        }
        .company-perf-page .perf-stat-card:hover .perf-stat-action-hint {
            opacity: 1;
        }

        /* Notice banner */
        .company-perf-page .perf-notice-banner {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1rem;
            font-size: 0.775rem;
            line-height: 1.4;
            border-radius: 0.75rem;
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fef3c7;
        }
        .dark .company-perf-page .perf-notice-banner {
            background: rgba(245, 158, 11, 0.1);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.25);
        }

        /* Stepper / Breadcrumbs Bar */
        .company-perf-page .breadcrumb-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.875rem;
            padding: 0.875rem 1.15rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .dark .company-perf-page .breadcrumb-stepper {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        .company-perf-page .stepper-trail {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .company-perf-page .stepper-step {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .company-perf-page .stepper-step.active {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #e2e8f0;
        }
        .dark .company-perf-page .stepper-step.active {
            background: #0f172a;
            color: #f8fafc;
            border-color: #334155;
        }
        .company-perf-page .stepper-step.link {
            color: #2563eb;
            cursor: pointer;
        }
        .company-perf-page .stepper-step.link:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .dark .company-perf-page .stepper-step.link {
            color: #60a5fa;
        }
        .dark .company-perf-page .stepper-step.link:hover {
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(37, 99, 235, 0.35);
        }
        .company-perf-page .stepper-divider {
            color: #94a3b8;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
        }
        .company-perf-page .level-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.725rem;
            font-weight: 800;
            border: 1px solid #bae6fd;
        }
        .dark .company-perf-page .level-pill-badge {
            background: rgba(3, 105, 161, 0.2);
            color: #7dd3fc;
            border-color: rgba(3, 105, 161, 0.35);
        }

        /* Modern Table Container */
        .company-perf-page .perf-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .dark .company-perf-page .perf-table-wrapper {
            border-color: #334155;
            background-color: #1e293b;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        .company-perf-page .perf-table {
            width: 100%;
            text-align: start;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            min-width: 1100px;
        }
        .company-perf-page .perf-table thead tr {
            background-color: #f8fafc;
        }
        .dark .company-perf-page .perf-table thead tr {
            background-color: #0f172a;
        }
        .company-perf-page .perf-table th {
            padding: 0.85rem 1rem;
            font-weight: 700;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            text-align: inherit;
            white-space: nowrap;
            border-bottom: 2px solid #e2e8f0;
        }
        .dark .company-perf-page .perf-table th {
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .company-perf-page .perf-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.12s ease;
        }
        .dark .company-perf-page .perf-table tbody tr {
            border-bottom-color: rgba(51, 65, 85, 0.6);
        }
        .company-perf-page .perf-table tbody tr:last-child {
            border-bottom: none;
        }
        .company-perf-page .perf-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .dark .company-perf-page .perf-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.025);
        }
        .company-perf-page .perf-table td {
            padding: 0.85rem 1rem;
            white-space: nowrap;
            color: #1e293b;
            vertical-align: middle;
        }
        .dark .company-perf-page .perf-table td {
            color: #f1f5f9;
        }

        /* Clickable KPI Badges / Pills */
        .company-perf-page .val-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.25rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.8125rem;
            font-variant-numeric: tabular-nums;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }
        .company-perf-page .val-pill:hover {
            transform: translateY(-1px);
        }
        .company-perf-page .val-pill.zero {
            color: #94a3b8;
            font-weight: 500;
            cursor: pointer;
            background: transparent;
        }
        .dark .company-perf-page .val-pill.zero {
            color: #64748b;
        }
        .company-perf-page .val-pill.zero:hover {
            color: #475569;
            background: #f1f5f9;
        }
        .dark .company-perf-page .val-pill.zero:hover {
            color: #cbd5e1;
            background: #334155;
        }

        .company-perf-page .val-pill.active {
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .dark .company-perf-page .val-pill.active {
            color: #93c5fd;
            background: rgba(29, 78, 216, 0.2);
            border-color: rgba(29, 78, 216, 0.4);
        }
        .company-perf-page .val-pill.completed {
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }
        .dark .company-perf-page .val-pill.completed {
            color: #6ee7b7;
            background: rgba(4, 120, 87, 0.2);
            border-color: rgba(4, 120, 87, 0.4);
        }
        .company-perf-page .val-pill.overdue {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
        .dark .company-perf-page .val-pill.overdue {
            color: #fca5a5;
            background: rgba(185, 28, 28, 0.2);
            border-color: rgba(185, 28, 28, 0.4);
        }
        .company-perf-page .val-pill.deficiencies {
            color: #c2410c;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }
        .dark .company-perf-page .val-pill.deficiencies {
            color: #fdba74;
            background: rgba(194, 65, 12, 0.2);
            border-color: rgba(194, 65, 12, 0.4);
        }
        .company-perf-page .val-pill.tasks {
            color: #0e7490;
            background: #ecfeff;
            border: 1px solid #a5f3fc;
        }
        .dark .company-perf-page .val-pill.tasks {
            color: #67e8f9;
            background: rgba(14, 116, 144, 0.2);
            border-color: rgba(14, 116, 144, 0.4);
        }

        /* Rate Badges */
        .company-perf-page .rate-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.225rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.775rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .company-perf-page .rate-badge:hover {
            transform: translateY(-1px);
        }
        .company-perf-page .rate-badge.high {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .dark .company-perf-page .rate-badge.high {
            background: rgba(4, 120, 87, 0.2);
            color: #34d399;
            border-color: rgba(4, 120, 87, 0.4);
        }
        .company-perf-page .rate-badge.med {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .dark .company-perf-page .rate-badge.med {
            background: rgba(180, 83, 9, 0.2);
            color: #fbbf24;
            border-color: rgba(180, 83, 9, 0.4);
        }
        .company-perf-page .rate-badge.low {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .dark .company-perf-page .rate-badge.low {
            background: rgba(185, 28, 28, 0.2);
            color: #f87171;
            border-color: rgba(185, 28, 28, 0.4);
        }

        /* Unassigned Row Style */
        .company-perf-page .unassigned-row {
            background-color: #fffdf5 !important;
        }
        .dark .company-perf-page .unassigned-row {
            background-color: rgba(245, 158, 11, 0.05) !important;
        }

        /* Empty State */
        .company-perf-page .perf-empty-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .company-perf-page .perf-empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .company-perf-page .perf-empty-icon {
            background: #0f172a;
            color: #64748b;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1280px) {
            .company-perf-page .perf-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .company-perf-page .perf-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 480px) {
            .company-perf-page .perf-stats-grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>

    <div class="company-perf-page">
        @php
            $stats = $this->overallStats;
            $selectedCompany = $this->selectedCompany;
            $selectedDepartment = $this->selectedDepartment;

            $cards = [
                [
                    'title' => __('performance.cards.active_services'),
                    'value' => number_format($stats->active_services),
                    'hint' => __('performance.cards.active_services_hint'),
                    'icon' => 'heroicon-o-play-circle',
                    'accent' => '#2563eb',
                    'icon_bg' => '#eff6ff',
                    'icon_color' => '#1d4ed8',
                    'dark_icon_bg' => 'rgba(37,99,235,0.2)',
                    'dark_icon_color' => '#60a5fa',
                    'action' => "openKashf('services', 'active', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
                [
                    'title' => __('performance.cards.completed_services'),
                    'value' => number_format($stats->completed_services),
                    'hint' => __('performance.cards.completed_services_hint'),
                    'icon' => 'heroicon-o-check-circle',
                    'accent' => '#059669',
                    'icon_bg' => '#ecfdf5',
                    'icon_color' => '#047857',
                    'dark_icon_bg' => 'rgba(5,150,105,0.2)',
                    'dark_icon_color' => '#34d399',
                    'action' => "openKashf('services', 'completed', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
                [
                    'title' => __('performance.cards.overdue_services'),
                    'value' => number_format($stats->overdue_services),
                    'hint' => __('performance.cards.overdue_services_hint'),
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'accent' => '#e11d48',
                    'icon_bg' => '#fff1f2',
                    'icon_color' => '#be123c',
                    'dark_icon_bg' => 'rgba(225,29,72,0.2)',
                    'dark_icon_color' => '#fb7185',
                    'action' => "openKashf('services', 'overdue', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
                [
                    'title' => __('performance.cards.on_time_rate'),
                    'value' => $stats->on_time_rate !== null ? "{$stats->on_time_rate}%" : __('performance.cards.no_data'),
                    'hint' => __('performance.cards.on_time_rate_hint'),
                    'icon' => 'heroicon-o-clock',
                    'accent' => '#7c3aed',
                    'icon_bg' => '#f5f3ff',
                    'icon_color' => '#6d28d9',
                    'dark_icon_bg' => 'rgba(124,58,237,0.2)',
                    'dark_icon_color' => '#a78bfa',
                    'action' => "openKashf('services', 'on_time', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
                [
                    'title' => __('performance.cards.open_deficiencies'),
                    'value' => number_format($stats->open_deficiencies),
                    'hint' => __('performance.cards.open_deficiencies_hint'),
                    'icon' => 'heroicon-o-exclamation-circle',
                    'accent' => '#ea580c',
                    'icon_bg' => '#fff7ed',
                    'icon_color' => '#c2410c',
                    'dark_icon_bg' => 'rgba(234,88,12,0.2)',
                    'dark_icon_color' => '#fb923c',
                    'action' => "openKashf('deficiencies', 'all', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
                [
                    'title' => __('performance.cards.tasks_completion_rate'),
                    'value' => $stats->task_completion_rate !== null ? "{$stats->task_completion_rate}%" : __('performance.cards.no_data'),
                    'hint' => number_format($stats->task_completed_in_period) . ' ' . __('performance.columns.tasks_completed') . ' / ' . number_format($stats->task_due_in_period),
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'accent' => '#0891b2',
                    'icon_bg' => '#ecfeff',
                    'icon_color' => '#0e7490',
                    'dark_icon_bg' => 'rgba(8,145,178,0.2)',
                    'dark_icon_color' => '#38bdf8',
                    'action' => "openKashf('tasks', 'all', " . ($this->selectedCompanyId ?? 'null') . ", " . ($this->selectedDepartmentId !== null ? "'{$this->selectedDepartmentId}'" : 'null') . ")",
                ],
            ];
        @endphp

        <!-- Top Page Header -->
        <div class="perf-header-bar">
            <div class="perf-header-info">
                <div class="perf-header-icon">
                    <x-heroicon-o-chart-bar-square style="width: 26px; height: 26px;" />
                </div>
                <div>
                    <h1 class="perf-header-title">{{ __('performance.title') }}</h1>
                    <p class="perf-header-sub">{{ __('performance.subtitle') }}</p>
                </div>
            </div>

            <div class="perf-header-meta">
                <div class="perf-meta-chip">
                    <x-heroicon-m-calendar style="width: 14px; height: 14px; color: #2563eb;" />
                    <span><bdi>{{ $this->dateFrom ?? '—' }}</bdi> &larr; <bdi>{{ $this->dateTo ?? '—' }}</bdi></span>
                </div>
                <div class="perf-meta-chip">
                    <x-heroicon-m-building-office-2 style="width: 14px; height: 14px; color: #059669;" />
                    <span>{{ count($this->companies) }} {{ __('performance.columns.company') }}</span>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="perf-stats-grid">
            @foreach($cards as $card)
                <div class="perf-stat-card" style="--card-accent: {{ $card['accent'] }};" wire:click="{{ $card['action'] }}">
                    <div class="perf-stat-top">
                        <span class="perf-stat-title">{{ $card['title'] }}</span>
                        <div class="perf-stat-icon-wrapper" style="background-color: {{ $card['icon_bg'] }}; color: {{ $card['icon_color'] }};">
                            <x-dynamic-component :component="$card['icon']" style="width: 20px; height: 20px;" />
                        </div>
                    </div>
                    <div>
                        <div class="perf-stat-value" style="color: {{ $card['accent'] }};">{{ $card['value'] }}</div>
                    </div>
                    <div class="perf-stat-bottom">
                        <span class="perf-stat-hint">{{ $card['hint'] }}</span>
                        <span class="perf-stat-action-hint">
                            <span>{{ __('performance.columns.view_statement') }}</span>
                            <x-heroicon-m-arrow-left style="width: 12px; height: 12px;" />
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Filter Bar -->
        <x-filter-bar
            :title="__('performance.filters.title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('performance.filters.company')"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('performance.filters.branch')"
            :show-dates="true"
            :date-from-label="__('performance.filters.date_from')"
            :date-to-label="__('performance.filters.date_to')"
            :show-search="true"
            :search-placeholder="__('performance.filters.search')"
            :has-active="$this->hasActiveFilterBar()"
        />

        <!-- Branch Context Notice -->
        <div class="perf-notice-banner">
            <x-heroicon-o-information-circle style="width: 18px; height: 18px; flex-shrink: 0;" />
            <span>{{ __('performance.filters.branch_note') }}</span>
        </div>

        <!-- Hierarchy Stepper / Breadcrumbs Bar -->
        <div class="breadcrumb-stepper">
            <div class="stepper-trail">
                @if($this->selectedCompanyId === null)
                    <div class="stepper-step active">
                        <x-heroicon-m-building-office-2 style="width: 18px; height: 18px; color: #2563eb;" />
                        <span>{{ __('performance.levels.companies') }}</span>
                    </div>
                @elseif($this->selectedDepartmentId === null)
                    <button class="stepper-step link" wire:click="backToCompanies">
                        <x-heroicon-m-building-office-2 style="width: 18px; height: 18px;" />
                        <span>{{ __('performance.levels.companies') }}</span>
                    </button>
                    <span class="stepper-divider">
                        <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
                    </span>
                    <div class="stepper-step active">
                        <x-heroicon-m-rectangle-group style="width: 18px; height: 18px; color: #059669;" />
                        <span>{{ $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—') }}</span>
                    </div>
                @else
                    <button class="stepper-step link" wire:click="backToCompanies">
                        <x-heroicon-m-building-office-2 style="width: 18px; height: 18px;" />
                        <span>{{ __('performance.levels.companies') }}</span>
                    </button>
                    <span class="stepper-divider">
                        <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
                    </span>
                    <button class="stepper-step link" wire:click="backToDepartments">
                        <x-heroicon-m-rectangle-group style="width: 18px; height: 18px;" />
                        <span>{{ $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—') }}</span>
                    </button>
                    <span class="stepper-divider">
                        <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
                    </span>
                    <div class="stepper-step active">
                        <x-heroicon-m-user-group style="width: 18px; height: 18px; color: #7c3aed;" />
                        <span>
                            @if($this->selectedDepartmentId === 'unassigned')
                                {{ __('performance.levels.unassigned_or_no_department') }}
                            @else
                                {{ $selectedDepartment?->parent ? "{$selectedDepartment->name} ({$selectedDepartment->parent->name})" : $selectedDepartment?->name }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 0.65rem;">
                @if($this->selectedCompanyId === null)
                    <span class="level-pill-badge">
                        <x-heroicon-m-numbered-list style="width: 14px; height: 14px;" />
                        <span>المستوى 1: المنشآت</span>
                    </span>
                @elseif($this->selectedDepartmentId === null)
                    <span class="level-pill-badge" style="background:#ecfdf5; color:#047857; border-color:#a7f3d0;">
                        <x-heroicon-m-numbered-list style="width: 14px; height: 14px;" />
                        <span>المستوى 2: الأقسام</span>
                    </span>
                    <x-filament::button color="gray" icon="heroicon-m-arrow-right" size="sm" wire:click="backToCompanies">
                        {{ __('performance.levels.back_to_companies') }}
                    </x-filament::button>
                @else
                    <span class="level-pill-badge" style="background:#f5f3ff; color:#6d28d9; border-color:#ddd6fe;">
                        <x-heroicon-m-numbered-list style="width: 14px; height: 14px;" />
                        <span>المستوى 3: الموظفون</span>
                    </span>
                    <x-filament::button color="gray" icon="heroicon-m-arrow-right" size="sm" wire:click="backToDepartments">
                        {{ __('performance.levels.back_to_departments') }}
                    </x-filament::button>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- LEVEL 1: Companies Table -->
        <!-- ========================================== -->
        @if($this->selectedCompanyId === null)
            @php
                $companies = $this->companiesSummary;
            @endphp
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.columns.company') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.active_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.completed_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.overdue_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.on_time_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.open_deficiencies') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.tasks_due') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.tasks_completed') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.task_completion_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.departments_count') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.employees_count') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $row)
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <a href="javascript:void(0)" wire:click="selectCompany({{ $row->company_id }})" style="font-weight: 700; color: #0f172a; text-decoration: none;" class="hover:text-primary-600 dark:text-gray-100">
                                            {{ $row->company_name }}
                                        </a>
                                        <span style="font-size: 0.725rem; color: #64748b;"><bdi>{{ $row->company_number ?? '—' }}</bdi></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->active_services > 0 ? 'active' : 'zero' }}" wire:click="openKashf('services', 'active', {{ $row->company_id }})">
                                        {{ number_format($row->active_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->completed_services > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('services', 'completed', {{ $row->company_id }})">
                                        {{ number_format($row->completed_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->overdue_services > 0 ? 'overdue' : 'zero' }}" wire:click="openKashf('services', 'overdue', {{ $row->company_id }})">
                                        {{ number_format($row->overdue_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->on_time_rate !== null)
                                        @php
                                            $rateClass = $row->on_time_rate >= 80 ? 'high' : ($row->on_time_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $rateClass }}" wire:click="openKashf('services', 'on_time', {{ $row->company_id }})">
                                            {{ $row->on_time_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->open_deficiencies > 0 ? 'deficiencies' : 'zero' }}" wire:click="openKashf('deficiencies', 'all', {{ $row->company_id }})">
                                        {{ number_format($row->open_deficiencies) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill zero" wire:click="openKashf('tasks', 'due', {{ $row->company_id }})">
                                        {{ number_format($row->task_due_in_period) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->task_completed_in_period > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('tasks', 'completed', {{ $row->company_id }})">
                                        {{ number_format($row->task_completed_in_period) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->task_completion_rate !== null)
                                        @php
                                            $taskRateClass = $row->task_completion_rate >= 80 ? 'high' : ($row->task_completion_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $taskRateClass }}" wire:click="openKashf('tasks', 'all', {{ $row->company_id }})">
                                            {{ $row->task_completion_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #475569;">
                                    {{ number_format($row->departments_count) }}
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #475569;">
                                    {{ number_format($row->employees_count) }}
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::button size="xs" color="primary" icon="heroicon-m-chevron-left" icon-position="after" wire:click="selectCompany({{ $row->company_id }})">
                                        {{ __('performance.levels.view_departments') }}
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">
                                    <div class="perf-empty-state">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-building-office-2 style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                        <div style="font-size: 0.775rem; color: #94a3b8;">جرّب تعديل معايير البحث أو اختيار فترة زمنية أخرى.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- ========================================== -->
        <!-- LEVEL 2: Departments Table -->
        <!-- ========================================== -->
        @elseif($this->selectedDepartmentId === null)
            @php
                $departments = $this->departmentsSummary;
            @endphp
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.columns.department') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.active_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.completed_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.overdue_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.on_time_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.open_deficiencies') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.tasks_due') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.tasks_completed') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.task_completion_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.employees_count') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $row)
                            <tr class="{{ $row->is_unassigned ? 'unassigned-row' : '' }}">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="javascript:void(0)" wire:click="selectDepartment('{{ $row->department_id }}')" style="font-weight: 700; color: #0f172a; text-decoration: none;" class="hover:text-primary-600 dark:text-gray-100">
                                            {{ $row->department_name }}
                                        </a>
                                        @if($row->is_team_scope)
                                            <x-filament::badge color="info" size="xs">
                                                {{ __('performance.levels.team_badge') }}
                                            </x-filament::badge>
                                        @endif
                                        @if($row->is_unassigned)
                                            <x-filament::badge color="warning" size="xs">
                                                {{ __('performance.levels.unassigned_or_no_department') }}
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->active_services > 0 ? 'active' : 'zero' }}" wire:click="openKashf('services', 'active', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->active_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->completed_services > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('services', 'completed', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->completed_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->overdue_services > 0 ? 'overdue' : 'zero' }}" wire:click="openKashf('services', 'overdue', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->overdue_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->on_time_rate !== null)
                                        @php
                                            $rateClass = $row->on_time_rate >= 80 ? 'high' : ($row->on_time_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $rateClass }}" wire:click="openKashf('services', 'on_time', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                            {{ $row->on_time_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->open_deficiencies > 0 ? 'deficiencies' : 'zero' }}" wire:click="openKashf('deficiencies', 'all', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->open_deficiencies) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill zero" wire:click="openKashf('tasks', 'due', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->task_due_in_period) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->task_completed_in_period > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('tasks', 'completed', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                        {{ number_format($row->task_completed_in_period) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->task_completion_rate !== null)
                                        @php
                                            $taskRateClass = $row->task_completion_rate >= 80 ? 'high' : ($row->task_completion_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $taskRateClass }}" wire:click="openKashf('tasks', 'all', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')">
                                            {{ $row->task_completion_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #475569;">
                                    {{ number_format($row->employees_count) }}
                                </td>
                                <td style="text-align: center;">
                                    <x-filament::button size="xs" color="primary" icon="heroicon-m-chevron-left" icon-position="after" wire:click="selectDepartment('{{ $row->department_id }}')">
                                        {{ __('performance.levels.view_employees') }}
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="perf-empty-state">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-rectangle-group style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                        <div style="font-size: 0.775rem; color: #94a3b8;">لا توجد أقسام مسجلة تحت هذه المنشأة وفق التصفية الحالية.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- ========================================== -->
        <!-- LEVEL 3: Employees Table -->
        <!-- ========================================== -->
        @else
            @php
                $employees = $this->employeesSummary;
            @endphp
            <div class="perf-table-wrapper">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ __('performance.columns.employee') }}</th>
                            <th>{{ __('performance.columns.job_title') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.nationality') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.active_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.completed_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.overdue_services') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.on_time_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.open_deficiencies') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.tasks_completed') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.task_completion_rate') }}</th>
                            <th style="text-align: center;">{{ __('performance.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $row)
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                        <strong style="color: #0f172a;" class="dark:text-gray-100">{{ $row->name }}</strong>
                                        <span style="font-size: 0.725rem; color: #64748b;"><bdi>{{ $row->employee_number ?? '—' }}</bdi></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.775rem; color: #475569;" class="dark:text-gray-300">{{ $row->job_title ?: '—' }}</span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->is_saudi)
                                        <x-filament::badge color="success" size="xs">
                                            {{ __('performance.columns.saudi') }}
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray" size="xs">
                                            {{ __('performance.columns.foreign') }}
                                        </x-filament::badge>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->active_services > 0 ? 'active' : 'zero' }}" wire:click="openKashf('services', 'active', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                        {{ number_format($row->active_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->completed_services > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('services', 'completed', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                        {{ number_format($row->completed_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->overdue_services > 0 ? 'overdue' : 'zero' }}" wire:click="openKashf('services', 'overdue', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                        {{ number_format($row->overdue_services) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->on_time_rate !== null)
                                        @php
                                            $rateClass = $row->on_time_rate >= 80 ? 'high' : ($row->on_time_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $rateClass }}" wire:click="openKashf('services', 'on_time', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                            {{ $row->on_time_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="val-pill {{ $row->open_deficiencies > 0 ? 'deficiencies' : 'zero' }}" wire:click="openKashf('deficiencies', 'all', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                        {{ number_format($row->open_deficiencies) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->task_completed_in_period !== null)
                                        <span class="val-pill {{ $row->task_completed_in_period > 0 ? 'completed' : 'zero' }}" wire:click="openKashf('tasks', 'completed', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                            {{ number_format($row->task_completed_in_period) }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($row->task_completion_rate !== null)
                                        @php
                                            $taskRateClass = $row->task_completion_rate >= 80 ? 'high' : ($row->task_completion_rate >= 50 ? 'med' : 'low');
                                        @endphp
                                        <span class="rate-badge {{ $taskRateClass }}" wire:click="openKashf('tasks', 'all', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})">
                                            {{ $row->task_completion_rate }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($row->performance_url)
                                        <x-filament::link :href="$row->performance_url" icon="heroicon-m-arrow-top-right-on-square" target="_blank" size="sm">
                                            {{ __('performance.columns.view_profile') }}
                                        </x-filament::link>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="perf-empty-state">
                                        <div class="perf-empty-icon">
                                            <x-heroicon-o-users style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                        <div style="font-size: 0.775rem; color: #94a3b8;">لا يوجد موظفون مسجلون تحت هذا القسم وفق معايير التصفية.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- LEVEL 4: Kashf Slide-over Modal -->
        <!-- ========================================== -->
        <x-filament::modal id="performance-kashf-modal" width="6xl" slide-over>
            <x-slot:heading>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-o-clipboard-document-list style="width: 22px; height: 22px; color: #2563eb;" />
                    <span>{{ $this->kashfTitle }}</span>
                </div>
            </x-slot:heading>

            @php
                $records = $this->kashfRecords;
            @endphp

            @if($records)
                @if($this->kashfDomain === 'services')
                    <div class="perf-table-wrapper">
                        <table class="perf-table">
                            <thead>
                                <tr>
                                    <th>{{ __('performance.kashf.transaction_number') }}</th>
                                    <th>{{ __('performance.kashf.service') }}</th>
                                    <th>{{ __('performance.kashf.beneficiary') }}</th>
                                    <th>{{ __('performance.kashf.executor') }}</th>
                                    <th>{{ __('performance.kashf.transaction_date') }}</th>
                                    <th>{{ __('performance.kashf.due_date') }}</th>
                                    <th>{{ __('performance.kashf.completion_date') }}</th>
                                    <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                                    <th style="text-align: center;">{{ __('performance.kashf.pending_action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $srv)
                                    @php
                                        $pending = \App\Services\ServicePerformanceSummaryService::pendingAction($srv);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong style="color: #2563eb; font-size: 0.85rem;"><bdi>{{ $srv->transaction_number }}</bdi></strong>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600;">{{ $srv->display_service_name }}</span>
                                        </td>
                                        <td>{{ $srv->display_beneficiary }}</td>
                                        <td>
                                            <span style="color: #475569;" class="dark:text-gray-300">{{ $srv->executor?->user?->name ?? '—' }}</span>
                                        </td>
                                        <td><bdi>{{ $srv->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ $srv->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ $srv->completion_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td style="text-align: center;">
                                            <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::statusColor($srv->service_status)" size="xs">
                                                {{ \App\Services\ServicePerformanceSummaryService::statusLabel($srv->service_status) }}
                                            </x-filament::badge>
                                        </td>
                                        <td style="text-align: center;">
                                            <x-filament::badge :color="$pending['color']" size="xs">
                                                {{ $pending['label'] }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="perf-empty-state">
                                                <div class="perf-empty-icon">
                                                    <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                @elseif($this->kashfDomain === 'deficiencies')
                    <div class="perf-table-wrapper">
                        <table class="perf-table">
                            <thead>
                                <tr>
                                    <th>{{ __('performance.kashf.transaction_number') }}</th>
                                    <th>{{ __('performance.kashf.deficiency_type') }}</th>
                                    <th>{{ __('performance.kashf.responsible') }}</th>
                                    <th>{{ __('performance.kashf.due_date') }}</th>
                                    <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $def)
                                    @php
                                        $tx = \App\Services\ServicePerformanceSummaryService::deficiencyTransaction($def);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong style="color: #2563eb; font-size: 0.85rem;"><bdi>{{ $tx?->transaction_number ?? ($def->task_code ?? "#{$def->id}") }}</bdi></strong>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                                <strong style="color: #0f172a;" class="dark:text-gray-100">{{ $def->title }}</strong>
                                                <span style="font-size: 0.725rem; color: #64748b;">
                                                    {{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($def->deficiency_type) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $def->responsibleEmployee?->user?->name ?? '—' }}</td>
                                        <td><bdi>{{ $def->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td style="text-align: center;">
                                            <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($def->status)" size="xs">
                                                {{ \App\Services\ServicePerformanceSummaryService::deficiencyStatusLabel($def->status) }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="perf-empty-state">
                                                <div class="perf-empty-icon">
                                                    <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                @elseif($this->kashfDomain === 'tasks')
                    <div class="perf-table-wrapper">
                        <table class="perf-table">
                            <thead>
                                <tr>
                                    <th>{{ __('performance.kashf.task_name') }}</th>
                                    <th>{{ __('performance.kashf.assignment_number') }}</th>
                                    <th>{{ __('performance.columns.employee') }}</th>
                                    <th>{{ __('performance.kashf.due_date') }}</th>
                                    <th>{{ __('performance.kashf.completion_date') }}</th>
                                    <th style="text-align: center;">{{ __('performance.kashf.status') }}</th>
                                    <th style="text-align: center;">{{ __('performance.kashf.weight') }} / {{ __('performance.kashf.score') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $item)
                                    <tr>
                                        <td><strong>{{ $item->task?->name ?? '—' }}</strong></td>
                                        <td><bdi>{{ $item->taskAssignment?->transaction_number ?? '—' }}</bdi></td>
                                        <td>{{ $item->taskAssignment?->employee?->user?->name ?? '—' }}</td>
                                        <td><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td style="text-align: center;">
                                            <x-filament::badge :color="$item->status?->getColor() ?? 'gray'" size="xs">
                                                {{ $item->status?->getLabel() ?? '—' }}
                                            </x-filament::badge>
                                        </td>
                                        <td style="text-align: center;">
                                            <bdi>{{ number_format((float) $item->weight, 1) }}%</bdi> /
                                            <bdi>{{ number_format((float) $item->score, 1) }}</bdi>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="perf-empty-state">
                                                <div class="perf-empty-icon">
                                                    <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">{{ __('performance.kashf.empty') }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($records->hasPages())
                    <div style="padding: 1rem 0; border-top: 1px solid #e2e8f0;">
                        <x-filament::pagination :paginator="$records" />
                    </div>
                @endif
            @endif

            <x-slot:footerActions>
                <x-filament::button color="gray" wire:click="closeKashf">
                    {{ __('performance.kashf.close') }}
                </x-filament::button>
            </x-slot:footerActions>
        </x-filament::modal>
    </div>
</x-filament-panels::page>
