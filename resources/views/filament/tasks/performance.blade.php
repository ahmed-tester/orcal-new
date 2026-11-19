<x-filament-panels::page>
    @php
        $data = $this->performance;
        $employee = $data['employee'] ?? null;
        $serviceData = $this->servicePerformance;
        $employeeName = $employee?->user?->name ?? $employee?->employee_number ?? '—';
    @endphp

    <style>
        .task-performance-page {
            --tp-surface: #ffffff;
            --tp-surface-subtle: #f8fafc;
            --tp-border: #e2e8f0;
            --tp-border-soft: #f1f5f9;
            --tp-text: #0f172a;
            --tp-text-muted: #64748b;
            --tp-text-soft: #475569;
            --tp-shadow: 0 1px 3px rgba(15, 23, 42, 0.05), 0 1px 2px rgba(15, 23, 42, 0.03);
            --tp-shadow-hover: 0 8px 20px -6px rgba(15, 23, 42, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .dark .task-performance-page {
            --tp-surface: #1e293b;
            --tp-surface-subtle: #0f172a;
            --tp-border: #334155;
            --tp-border-soft: #273449;
            --tp-text: #f8fafc;
            --tp-text-muted: #94a3b8;
            --tp-text-soft: #cbd5e1;
            --tp-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
            --tp-shadow-hover: 0 10px 24px -6px rgba(0, 0, 0, 0.45);
        }

        /* Hero Header */
        .task-performance-page .tp-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 1.25rem 1.35rem;
            border: 1px solid var(--tp-border);
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--tp-surface) 0%, var(--tp-surface-subtle) 100%);
            box-shadow: var(--tp-shadow);
        }
        .task-performance-page .tp-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
            flex: 1 1 280px;
        }
        .task-performance-page .tp-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 0.9rem;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .task-performance-page .tp-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--tp-text);
            line-height: 1.3;
        }
        .task-performance-page .tp-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }
        .task-performance-page .tp-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid var(--tp-border);
            background: var(--tp-surface);
            font-size: 0.75rem;
            color: var(--tp-text-soft);
        }
        .task-performance-page .tp-meta-chip strong {
            color: var(--tp-text-muted);
            font-weight: 600;
        }
        .task-performance-page .tp-controls {
            display: flex;
            align-items: flex-end;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .task-performance-page .tp-period-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--tp-text-muted);
            margin-bottom: 0.3rem;
        }
        .task-performance-page .tp-nav-group {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem;
            border-radius: 0.65rem;
            border: 1px solid var(--tp-border);
            background: var(--tp-surface);
        }

        /* Section Containers */
        .task-performance-page .tp-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--tp-border);
        }
        .task-performance-page .tp-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .task-performance-page .tp-section-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--tp-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .task-performance-page .tp-section-subtitle {
            font-size: 0.8rem;
            color: var(--tp-text-muted);
            margin-top: 0.2rem;
        }

        /* KPI Cards */
        .task-performance-page .tp-task-metrics {
            container: task-metrics / inline-size;
            display: grid;
            gap: 0.85rem;
            width: 100%;
        }
        .task-performance-page .tp-task-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.8rem;
        }
        .task-performance-page .tp-service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.85rem;
        }
        .task-performance-page .tp-card {
            position: relative;
            border: 1px solid var(--tp-border);
            border-top: 3px solid var(--tp-metric-accent, var(--tp-border));
            border-radius: 0.9rem;
            padding: 0.95rem 1rem;
            background: var(--tp-surface);
            box-shadow: var(--tp-shadow);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 0;
            text-align: start;
        }
        .task-performance-page .tp-card.is-clickable {
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
        }
        .task-performance-page .tp-card.is-clickable:hover {
            transform: translateY(-2px);
            box-shadow: var(--tp-shadow-hover);
            border-color: color-mix(in srgb, var(--tp-metric-accent, #2563eb) 45%, var(--tp-border));
        }
        .task-performance-page .tp-card.is-clickable:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }
        .task-performance-page .tp-card.is-active {
            border-color: var(--tp-metric-accent, #2563eb);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--tp-metric-accent, #2563eb) 28%, transparent), var(--tp-shadow);
            background: color-mix(in srgb, var(--tp-metric-accent, #2563eb) 6%, var(--tp-surface));
        }
        .dark .task-performance-page .tp-card.is-active {
            background: color-mix(in srgb, var(--tp-metric-accent, #60a5fa) 14%, var(--tp-surface));
        }

        .task-performance-page .tp-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .task-performance-page .tp-card-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--tp-text-soft);
            display: flex;
            align-items: center;
            gap: 0.45rem;
            line-height: 1.35;
        }
        .task-performance-page .tp-card-icon-wrap {
            width: 2rem;
            height: 2rem;
            border-radius: 0.55rem;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--tp-metric-accent, #64748b) 12%, transparent);
            color: var(--tp-metric-accent, #64748b);
        }
        .dark .task-performance-page .tp-card-icon-wrap {
            background: color-mix(in srgb, var(--tp-metric-accent, #94a3b8) 20%, transparent);
            color: color-mix(in srgb, var(--tp-metric-accent, #94a3b8), white 45%);
        }
        .task-performance-page .tp-task-icon {
            width: 1.1rem;
            height: 1.1rem;
        }
        .task-performance-page .tp-task-filter-icon {
            width: 0.9rem;
            height: 0.9rem;
            color: var(--tp-text-muted);
            opacity: 0.6;
            flex-shrink: 0;
            transition: opacity 0.15s ease, color 0.15s ease;
        }
        .task-performance-page .tp-card.is-clickable:hover .tp-task-filter-icon,
        .task-performance-page .tp-card.is-active .tp-task-filter-icon {
            opacity: 1;
            color: var(--tp-metric-accent, #2563eb);
        }
        .task-performance-page .tp-card-value {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--tp-metric-accent, var(--tp-text));
            font-variant-numeric: tabular-nums;
        }
        .dark .task-performance-page .tp-card-value {
            color: color-mix(in srgb, var(--tp-metric-accent, #f8fafc), white 48%);
        }
        .task-performance-page .tp-card-hint {
            font-size: 0.72rem;
            color: var(--tp-text-muted);
            line-height: 1.45;
            margin-top: auto;
        }

        /* Rate Rings */
        .task-performance-page .tp-task-rates {
            --bd: #e2e8f0;
            --tp-ring-center: #ffffff;
        }
        .dark .task-performance-page .tp-task-rates {
            --bd: #334155;
            --tp-ring-center: #1e293b;
        }
        .task-performance-page .tp-task-rates .tp-card {
            border-top-width: 1px;
            padding: 1.05rem 1.1rem;
            justify-content: center;
        }
        .tp-task-rates .perf-ring-item {
            display: grid;
            grid-template-areas: 'ring label' 'ring hint';
            grid-template-columns: auto minmax(0, 1fr);
            align-content: center;
            align-items: center;
            gap: 0.25rem 0.95rem;
        }
        .tp-task-rates .ring {
            grid-area: ring;
            display: grid;
            place-items: center;
            width: 4.75rem;
            aspect-ratio: 1;
            border-radius: 50%;
            direction: ltr;
            background: conic-gradient(var(--col) calc(var(--p) * 1%), var(--bd) 0);
        }
        .tp-task-rates .ring i {
            display: grid;
            place-items: center;
            width: 3.75rem;
            aspect-ratio: 1;
            border-radius: 50%;
            background: var(--tp-ring-center);
            color: var(--tp-text);
            font-size: 0.82rem;
            font-weight: 800;
            font-style: normal;
            font-variant-numeric: tabular-nums;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
        }
        .tp-task-rates .perf-ring-label {
            grid-area: label;
            color: var(--tp-text);
            font-size: 0.84rem;
            font-weight: 700;
            align-self: end;
        }
        .tp-task-rates .perf-ring-sublabel {
            grid-area: hint;
            color: var(--tp-text-muted);
            font-size: 0.74rem;
            line-height: 1.4;
            align-self: start;
        }

        @container task-metrics (min-width: 19rem) {
            .task-performance-page .tp-task-counts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @container task-metrics (min-width: 38rem) {
            .task-performance-page .tp-task-counts {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @container task-metrics (min-width: 42rem) {
            .task-performance-page .tp-task-rates {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .tp-task-rates .perf-ring-item {
                grid-template-areas: 'ring' 'label' 'hint';
                grid-template-columns: minmax(0, 1fr);
                justify-items: center;
                align-content: start;
                gap: 0.45rem;
                text-align: center;
            }
        }
        @container task-metrics (min-width: 58rem) {
            .task-performance-page .tp-task-counts {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        /* Statement Toolbar */
        .task-performance-page .tp-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--tp-border);
            border-radius: 0.85rem;
            background: var(--tp-surface);
            box-shadow: var(--tp-shadow);
        }
        .task-performance-page .tp-toolbar-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--tp-text);
        }
        .task-performance-page .tp-toolbar-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.55rem;
            flex: 1 1 320px;
            justify-content: flex-end;
        }
        .task-performance-page .tp-search-box {
            position: relative;
            flex: 1 1 210px;
            max-width: 300px;
        }
        .task-performance-page .tp-search-icon {
            position: absolute;
            inset-inline-start: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: var(--tp-text-muted);
            pointer-events: none;
        }
        .task-performance-page .tp-search-input {
            width: 100%;
            height: 2.2rem;
            padding-inline: 2.1rem 0.75rem;
            border: 1px solid var(--tp-border);
            border-radius: 0.55rem;
            background: var(--tp-surface-subtle);
            color: var(--tp-text);
            font-size: 0.8rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .task-performance-page .tp-search-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18);
        }
        .task-performance-page .tp-pill-switch {
            display: inline-flex;
            padding: 0.18rem;
            border-radius: 0.55rem;
            border: 1px solid var(--tp-border);
            background: var(--tp-surface-subtle);
            gap: 0.18rem;
        }
        .task-performance-page .tp-pill-btn {
            padding: 0.3rem 0.65rem;
            border-radius: 0.42rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--tp-text-soft);
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .task-performance-page .tp-pill-btn.is-selected {
            background: var(--tp-surface);
            color: #1d4ed8;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }
        .dark .task-performance-page .tp-pill-btn.is-selected {
            color: #93c5fd;
        }

        /* Tables */
        .task-performance-page .tp-table-container {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
            border: 1px solid var(--tp-border);
            border-radius: 0.85rem;
            background: var(--tp-surface);
            box-shadow: var(--tp-shadow);
        }
        .task-performance-page .tp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            min-width: 680px;
        }
        .task-performance-page .tp-service-table { min-width: 820px; }
        .task-performance-page .tp-deficiency-table { min-width: 760px; }
        .task-performance-page .tp-table thead tr {
            background: var(--tp-surface-subtle);
            border-bottom: 1px solid var(--tp-border);
        }
        .task-performance-page .tp-table th {
            padding: 0.8rem 0.85rem;
            text-align: inherit;
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--tp-text-soft);
            white-space: nowrap;
        }
        .task-performance-page .tp-table tbody tr {
            border-bottom: 1px solid var(--tp-border-soft);
            transition: background-color 0.12s ease;
        }
        .task-performance-page .tp-table tbody tr:last-child {
            border-bottom: none;
        }
        .task-performance-page .tp-table tbody tr:hover {
            background: var(--tp-surface-subtle);
        }
        .task-performance-page .tp-table tbody tr.tp-row-overdue {
            background: rgba(239, 68, 68, 0.035);
            box-shadow: inset 3px 0 0 #dc2626;
        }
        [dir="rtl"] .task-performance-page .tp-table tbody tr.tp-row-overdue {
            box-shadow: inset -3px 0 0 #dc2626;
        }
        .task-performance-page .tp-table tbody tr.tp-row-returned {
            background: rgba(245, 158, 11, 0.045);
            box-shadow: inset 3px 0 0 #d97706;
        }
        [dir="rtl"] .task-performance-page .tp-table tbody tr.tp-row-returned {
            box-shadow: inset -3px 0 0 #d97706;
        }
        .task-performance-page .tp-table td {
            padding: 0.75rem 0.85rem;
            vertical-align: middle;
            color: var(--tp-text);
        }
        .task-performance-page .tp-task-name {
            font-weight: 700;
            color: var(--tp-text);
            min-width: 190px;
            line-height: 1.4;
        }
        .task-performance-page .tp-task-sub {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.35rem;
            font-size: 0.74rem;
            font-weight: 500;
            color: var(--tp-text-muted);
        }
        .task-performance-page .tp-status-cell {
            white-space: nowrap;
        }
        .task-performance-page .tp-eval-main {
            font-weight: 800;
            color: var(--tp-text);
            font-variant-numeric: tabular-nums;
        }
        .task-performance-page .tp-eval-sub {
            font-size: 0.72rem;
            color: var(--tp-text-muted);
            margin-top: 0.15rem;
            font-variant-numeric: tabular-nums;
        }
        .task-performance-page .tp-num {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .task-performance-page .tp-due-pill {
            display: inline-flex;
            align-items: center;
            margin-top: 0.25rem;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .task-performance-page .tp-due-pill.is-overdue {
            background: #fee2e2;
            color: #b91c1c;
        }
        .dark .task-performance-page .tp-due-pill.is-overdue {
            background: rgba(185, 28, 28, 0.25);
            color: #fca5a5;
        }
        .task-performance-page .tp-due-pill.is-today {
            background: #fef3c7;
            color: #b45309;
        }
        .dark .task-performance-page .tp-due-pill.is-today {
            background: rgba(180, 83, 9, 0.25);
            color: #fcd34d;
        }
        .task-performance-page .tp-due-pill.is-upcoming {
            background: #f1f5f9;
            color: #475569;
        }
        .dark .task-performance-page .tp-due-pill.is-upcoming {
            background: #334155;
            color: #cbd5e1;
        }

        .task-performance-page .tp-table td.tp-record-cell {
            padding-block: 0.35rem;
            vertical-align: middle;
            white-space: nowrap;
        }
        .task-performance-page .tp-record-link {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            min-height: 2rem;
            padding-inline: 0.55rem;
            border-radius: 0.45rem;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            white-space: nowrap;
            transition: background-color 0.15s ease;
        }
        .task-performance-page .tp-record-link svg { width: 0.9rem; height: 0.9rem; flex: none; }
        .task-performance-page .tp-record-link:hover { background: #dbeafe; }
        .task-performance-page .tp-record-link:hover bdi { text-decoration: underline; text-underline-offset: 0.18em; }
        .task-performance-page .tp-record-link:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
        .dark .task-performance-page .tp-record-link { background: #172554; color: #bfdbfe; }
        .dark .task-performance-page .tp-record-link:hover { background: #1e3a8a; }
        @media (pointer: coarse), (max-width: 640px) {
            .task-performance-page .tp-record-link { min-height: 2.75rem; }
        }
        .task-performance-page .tp-linked-list { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.75rem; }
        .task-performance-page .tp-muted { color: var(--tp-text-muted); }
        .task-performance-page .tp-empty-cell {
            padding: 2.25rem 1rem !important;
            text-align: center;
            color: var(--tp-text-muted);
        }
        .task-performance-page .tp-empty-inner {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
        }
        .task-performance-page .tp-empty-inner svg {
            width: 2rem;
            height: 2rem;
            opacity: 0.55;
        }

        /* Modal Inputs */
        .tp-modal-field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: #334155;
        }
        .dark .tp-modal-field-label { color: #cbd5e1; }
        .tp-modal-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.55rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.85rem;
            background: #ffffff;
            color: #0f172a;
        }
        .dark .tp-modal-input {
            border-color: #475569;
            background: #0f172a;
            color: #f8fafc;
        }
        .tp-modal-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    @if(! $employee)
        <x-filament::section>
            <p>{{ __('tasks.performance.no_employee') }}</p>
        </x-filament::section>
    @else
        <div class="task-performance-page">
            {{-- Header Card --}}
            <div class="tp-header">
                <div class="tp-identity">
                    <div class="tp-avatar" aria-hidden="true">
                        {{ mb_substr($employeeName, 0, 1) }}
                    </div>
                    <div style="min-width: 0;">
                        <div class="tp-name">{{ $employeeName }}</div>
                        <div class="tp-meta">
                            <span class="tp-meta-chip">
                                <strong>{{ __('tasks.performance.company') }}:</strong>
                                <span>{{ $employee->company?->trade_name ?? '—' }}</span>
                            </span>
                            <span class="tp-meta-chip">
                                <strong>{{ __('tasks.performance.branch') }}:</strong>
                                <span>{{ $employee->branch?->name ?? '—' }}</span>
                            </span>
                            <span class="tp-meta-chip">
                                <strong>{{ __('tasks.performance.department') }}:</strong>
                                <span>{{ $employee->department?->name ?? '—' }}</span>
                            </span>
                            <span class="tp-meta-chip">
                                <strong>{{ __('tasks.performance.job_title') }}:</strong>
                                <span>{{ $employee->jobTitleDisplay ?? '—' }}</span>
                            </span>
                            <span class="tp-meta-chip">
                                <strong>{{ __('tasks.performance.employee_number') }}:</strong>
                                <bdi>{{ $employee->employee_number ?? '—' }}</bdi>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="tp-controls">
                    <div>
                        <label class="tp-period-label">{{ __('tasks.performance.period') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="month">
                                @foreach($this->monthOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    <div class="tp-nav-group">
                        <x-filament::button color="gray" size="sm" wire:click="previousMonth" icon="heroicon-m-chevron-right">
                            {{ __('tasks.performance.previous') }}
                        </x-filament::button>
                        <x-filament::button color="gray" size="sm" wire:click="currentMonth">
                            {{ __('tasks.performance.current') }}
                        </x-filament::button>
                        <x-filament::button color="gray" size="sm" wire:click="nextMonth" icon="heroicon-m-chevron-left" icon-position="after">
                            {{ __('tasks.performance.next') }}
                        </x-filament::button>
                    </div>

                    @if($data['task_visible'])
                        <x-filament::button color="primary" size="sm" wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
                            {{ __('tasks.performance.export') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @if($data['task_visible'])
                <div class="tp-section">
                    <div class="tp-section-header">
                        <h2 class="tp-section-title">{{ __('tasks.performance.task_section_title') }}</h2>
                        @if(($data['is_closed_month'] ?? false) && (int) ($data['employee']->pending_review_count ?? 0) > 0)
                            <x-filament::badge color="info" icon="heroicon-m-eye">
                                {{ __('tasks.review.labels.closed_month_pending', ['count' => (int) $data['employee']->pending_review_count]) }}
                            </x-filament::badge>
                        @endif
                    </div>

                    <div class="tp-task-metrics">
                        @php
                            $countCards = [
                                ['key' => 'completed', 'title' => __('tasks.performance.completed_in_period'), 'value' => (int) $data['employee']->completed_in_period, 'hint' => __('tasks.performance.completed_in_period_hint'), 'icon' => 'heroicon-o-check-circle', 'color' => '#059669'],
                                ['key' => 'due', 'title' => __('tasks.performance.due_in_period'), 'value' => (int) $data['employee']->due_in_period, 'hint' => __('tasks.performance.due_in_period_hint'), 'icon' => 'heroicon-o-calendar-days', 'color' => '#1d4ed8'],
                                ['key' => 'pending_review', 'title' => __('tasks.performance.pending_review'), 'value' => (int) ($data['employee']->pending_review_count ?? 0), 'hint' => __('tasks.performance.pending_review_hint'), 'icon' => 'heroicon-o-eye', 'color' => '#0284c7'],
                                ['key' => 'overdue', 'title' => __('tasks.performance.overdue_now'), 'value' => (int) $data['employee']->overdue_count, 'hint' => __('tasks.performance.overdue_now_hint'), 'icon' => 'heroicon-o-exclamation-triangle', 'color' => '#b91c1c'],
                                ['key' => 'postponed', 'title' => __('tasks.performance.postponed'), 'value' => (int) $data['employee']->postponed_count, 'hint' => __('tasks.performance.postponed_hint'), 'icon' => 'heroicon-o-pause-circle', 'color' => '#b45309'],
                            ];
                            $approvedItemsCount = (int) ($data['employee']->approved_items_count ?? 0);
                            $totalItemsCount = (int) ($data['employee']->total_items ?? 0);
                            $rateCards = [
                                ['title' => 'weighted_score', 'rate' => $data['weighted_percentage'], 'color' => '#7e22ce', 'sublabel' => $data['weighted_percentage'] === null ? __('tasks.performance.no_data') : __('tasks.performance.weighted_score_hint', ['approved' => $approvedItemsCount, 'total' => $totalItemsCount])],
                                ['title' => 'completion_rate', 'rate' => $data['completion_rate'], 'color' => '#3762eb', 'sublabel' => $data['completion_rate'] === null ? __('tasks.performance.no_data') : __('tasks.performance.completion_rate_hint')],
                                ['title' => 'on_time_rate', 'rate' => $data['on_time_rate'], 'color' => '#16a34a', 'sublabel' => $data['on_time_rate'] === null ? __('tasks.performance.no_data') : __('tasks.performance.on_time_rate_hint')],
                            ];
                        @endphp

                        <div class="tp-task-grid tp-task-counts">
                            @foreach($countCards as $card)
                                <button
                                    type="button"
                                    @class(['tp-card', 'is-clickable', 'is-active' => $this->cardFilter === $card['key']])
                                    style="--tp-metric-accent: {{ $card['color'] }}"
                                    wire:click="filterCard('{{ $card['key'] }}')"
                                    aria-pressed="{{ $this->cardFilter === $card['key'] ? 'true' : 'false' }}"
                                >
                                    <div class="tp-card-top">
                                        <span class="tp-card-title">
                                            <span class="tp-card-icon-wrap" aria-hidden="true">
                                                <x-dynamic-component :component="$card['icon']" class="tp-task-icon" />
                                            </span>
                                            <span>{{ $card['title'] }}</span>
                                        </span>
                                        <x-heroicon-m-funnel class="tp-task-filter-icon" aria-hidden="true" />
                                    </div>
                                    <bdi class="tp-card-value">{{ $card['value'] }}</bdi>
                                    <span class="tp-card-hint">{{ $card['hint'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="tp-task-grid tp-task-rates">
                            @foreach($rateCards as $card)
                                <div class="tp-card">
                                    <x-performance.rate-ring
                                        :rate="$card['rate']"
                                        :label="__('tasks.performance.'.$card['title'])"
                                        :sublabel="$card['sublabel']"
                                        :color="$card['rate'] === null ? null : $card['color']"
                                        :precision="2"
                                        :fixed-precision="true"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Statement Toolbar --}}
                    <div class="tp-toolbar">
                        <div class="tp-toolbar-title">
                            <span>{{ __('tasks.performance.statement') }}</span>
                            <x-filament::badge color="gray">
                                {{ __('tasks.performance.items_count', ['count' => $this->performanceItems->count()]) }}
                            </x-filament::badge>
                            @if($this->cardFilter)
                                <x-filament::badge color="info">
                                    {{ __('tasks.performance.filter_'.$this->cardFilter) }}
                                </x-filament::badge>
                            @endif
                            @if($this->cardFilter || $this->statusQuickFilter !== 'all' || trim($this->search) !== '')
                                <x-filament::link tag="button" wire:click="clearTaskFilters" style="font-size:0.75rem;">
                                    {{ __('tasks.performance.clear_filter') }}
                                </x-filament::link>
                            @endif
                        </div>

                        <div class="tp-toolbar-actions">
                            <div class="tp-pill-switch" role="group">
                                <button
                                    type="button"
                                    wire:click="$set('statusQuickFilter', 'all')"
                                    @class(['tp-pill-btn', 'is-selected' => $this->statusQuickFilter === 'all'])
                                >
                                    {{ __('tasks.performance.status_filter_all') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('statusQuickFilter', 'action_needed')"
                                    @class(['tp-pill-btn', 'is-selected' => $this->statusQuickFilter === 'action_needed'])
                                >
                                    {{ __('tasks.performance.status_filter_action_needed') }}
                                </button>
                            </div>

                            <div class="tp-search-box">
                                <x-heroicon-m-magnifying-glass class="tp-search-icon" aria-hidden="true" />
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="search"
                                    class="tp-search-input"
                                    placeholder="{{ __('tasks.performance.search_placeholder') }}"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="tp-table-container">
                        <table class="tp-table">
                            <thead>
                                <tr>
                                    @foreach(['task', 'status', 'dates', 'evaluation', 'review_actions'] as $column)
                                        <th scope="col">{{ __('tasks.performance.columns.'.$column) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->performanceItems as $item)
                                    @php
                                        $isOpen = $item->status?->isEmployeeOpen() ?? false;
                                        $isReturned = $item->status === \App\Enums\TaskStatus::RETURNED_FOR_REVISION;
                                        $isOverdue = $isOpen && $item->due_date !== null && $item->due_date->lt(today());
                                        $daysDiff = ($isOpen && $item->due_date !== null) ? today()->diffInDays($item->due_date, false) : null;
                                        $assignmentNumber = $item->taskAssignment?->transaction_number ?? '—';
                                    @endphp
                                    <tr
                                        wire:key="task-item-{{ $item->id }}"
                                        @class([
                                            'tp-row-overdue' => $isOverdue && ! $isReturned,
                                            'tp-row-returned' => $isReturned,
                                        ])
                                    >
                                        <td class="tp-task-name">
                                            <div>{{ $item->task?->name ?? '—' }}</div>
                                            <div class="tp-task-sub">
                                                @if($url = $this->assignmentUrl($item))
                                                    <a class="tp-record-link" href="{{ $url }}" aria-label="{{ __('tasks.performance.open_assignment') }} {{ $assignmentNumber }}">
                                                        <bdi>{{ $assignmentNumber }}</bdi>
                                                        <x-heroicon-m-arrow-top-right-on-square aria-hidden="true" />
                                                    </a>
                                                @else
                                                    <bdi>{{ $assignmentNumber }}</bdi>
                                                @endif
                                                @if($item->taskAssignment?->assignment_date)
                                                    <span>· <bdi>{{ $item->taskAssignment->assignment_date->format('Y-m-d') }}</bdi></span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="tp-status-cell">
                                            <x-filament::badge :color="$item->status?->getColor() ?? 'gray'">
                                                {{ $item->status?->getLabel() ?? '—' }}
                                            </x-filament::badge>
                                            @if((int) ($item->returned_count ?? 0) > 0)
                                                <div style="font-size:0.7rem; color:#b45309; margin-top:0.25rem; font-weight:600; white-space:normal;">
                                                    {{ __('tasks.review.fields.returned_count') }}: {{ (int) $item->returned_count }}
                                                </div>
                                            @endif
                                            @if(filled($item->latest_return_reason) && $isReturned)
                                                <div style="font-size:0.72rem; color:#b91c1c; margin-top:0.25rem; max-width:220px; line-height:1.4; white-space:normal;">
                                                    {{ $item->latest_return_reason }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="tp-num">
                                            <div><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></div>
                                            @if($daysDiff !== null)
                                                @if($daysDiff < 0)
                                                    <span class="tp-due-pill is-overdue">{{ __('tasks.performance.overdue_by_days', ['days' => abs($daysDiff)]) }}</span>
                                                @elseif($daysDiff === 0)
                                                    <span class="tp-due-pill is-today">{{ __('tasks.performance.due_today') }}</span>
                                                @elseif($daysDiff <= 5)
                                                    <span class="tp-due-pill is-upcoming">{{ __('tasks.performance.due_in_days', ['days' => $daysDiff]) }}</span>
                                                @endif
                                            @endif
                                            @if($item->completed_at)
                                                <div class="tp-eval-sub">
                                                    {{ __('tasks.performance.columns.completed_label') }}: <bdi>{{ $item->completed_at->format('Y-m-d') }}</bdi>
                                                </div>
                                            @endif
                                            @if($item->original_due_date)
                                                <div style="font-size:0.7rem; color:#64748b; margin-top:0.2rem;">
                                                    {{ __('tasks.review.labels.extended_from', ['date' => $item->original_due_date->format('Y-m-d')]) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="tp-num">
                                            @if((float) $item->score > 0 || $item->status === \App\Enums\TaskStatus::COMPLETED)
                                                <div class="tp-eval-main"><bdi>{{ number_format((float) $item->score_percentage, 2) }}%</bdi></div>
                                                <div class="tp-eval-sub">
                                                    {{ __('tasks.performance.columns.score') }}: <bdi>{{ number_format((float) $item->score, 2) }}</bdi>
                                                    · {{ __('tasks.performance.columns.weight') }}: <bdi>{{ number_format((float) $item->weight, 2) }}%</bdi>
                                                </div>
                                            @else
                                                <div class="tp-eval-sub" style="margin-top:0;">
                                                    {{ __('tasks.performance.columns.weight') }}: <bdi>{{ number_format((float) $item->weight, 2) }}%</bdi>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="display:flex; flex-wrap:wrap; gap:0.35rem;">
                                                @if($this->canSubmitItem($item))
                                                    <x-filament::button size="xs" color="primary" wire:click="openSubmitModal({{ $item->id }})">
                                                        {{ $isReturned ? __('tasks.review.actions.resubmit') : __('tasks.review.actions.submit') }}
                                                    </x-filament::button>
                                                @endif
                                                @if($this->canReviewItem($item))
                                                    <x-filament::button size="xs" color="success" wire:click="openApproveModal({{ $item->id }})">
                                                        {{ __('tasks.review.actions.approve') }}
                                                    </x-filament::button>
                                                    <x-filament::button size="xs" color="warning" wire:click="openReturnModal({{ $item->id }})">
                                                        {{ __('tasks.review.actions.return') }}
                                                    </x-filament::button>
                                                @endif
                                                @if($this->canExtendItemDueDate($item))
                                                    <x-filament::button size="xs" color="info" wire:click="openExtendModal({{ $item->id }})">
                                                        {{ __('tasks.review.actions.extend_due_date') }}
                                                    </x-filament::button>
                                                @endif
                                                @if($item->reviews->isNotEmpty())
                                                    <x-filament::button size="xs" color="gray" wire:click="openHistoryModal({{ $item->id }})">
                                                        {{ __('tasks.review.actions.history') }}
                                                    </x-filament::button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="tp-empty-cell">
                                            <div class="tp-empty-inner">
                                                <x-heroicon-o-clipboard-document-check aria-hidden="true" />
                                                <span>{{ __('tasks.performance.empty') }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($data['service_visible'] && $serviceData)
                <div class="tp-section">
                    <div>
                        <div class="tp-section-title">{{ __('tasks.performance.service_section_title') }}</div>
                        <div class="tp-section-subtitle">{{ __('tasks.performance.services.subtitle') }}</div>
                    </div>

                    <div class="tp-service-grid">
                        @php
                            $serviceCards = [
                                ['key' => 'active', 'title' => __('tasks.performance.services.cards.active'), 'value' => (int) $serviceData['stats']->active, 'hint' => __('tasks.performance.services.cards.active_hint'), 'icon' => 'heroicon-o-play-circle', 'color' => '#1d4ed8'],
                                ['key' => 'completed', 'title' => __('tasks.performance.services.cards.completed'), 'value' => (int) $serviceData['stats']->completed, 'hint' => __('tasks.performance.services.cards.completed_hint'), 'icon' => 'heroicon-o-check-badge', 'color' => '#059669'],
                                ['key' => 'overdue', 'title' => __('tasks.performance.services.cards.overdue'), 'value' => (int) $serviceData['stats']->overdue, 'hint' => __('tasks.performance.services.cards.overdue_hint'), 'icon' => 'heroicon-o-exclamation-triangle', 'color' => '#b91c1c'],
                                ['key' => 'assigned_deficiencies', 'title' => __('tasks.performance.services.cards.assigned_deficiencies'), 'value' => (int) $serviceData['stats']->assigned_deficiencies, 'hint' => __('tasks.performance.services.cards.assigned_deficiencies_hint'), 'icon' => 'heroicon-o-clipboard-document-list', 'color' => '#b45309'],
                            ];
                        @endphp

                        @foreach($serviceCards as $card)
                            <div
                                class="tp-card is-clickable {{ $this->serviceCardFilter === $card['key'] ? 'is-active' : '' }}"
                                style="--tp-metric-accent: {{ $card['color'] }}"
                                wire:click="filterServiceCard('{{ $card['key'] }}')"
                            >
                                <div class="tp-card-top">
                                    <span class="tp-card-title">
                                        <span class="tp-card-icon-wrap" aria-hidden="true">
                                            <x-dynamic-component :component="$card['icon']" class="tp-task-icon" />
                                        </span>
                                        <span>{{ $card['title'] }}</span>
                                    </span>
                                    <x-heroicon-m-funnel class="tp-task-filter-icon" aria-hidden="true" />
                                </div>
                                <div class="tp-card-value">{{ $card['value'] }}</div>
                                <div class="tp-card-hint">{{ $card['hint'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="tp-toolbar">
                        <div class="tp-toolbar-title">
                            <span>{{ $serviceData['deficiencies_only'] ? __('tasks.performance.services.deficiencies_statement') : __('tasks.performance.services.transaction_statement') }}</span>
                            <x-filament::badge color="gray">
                                @if($serviceData['deficiencies_only'])
                                    {{ __('tasks.performance.services.deficiencies_count', ['count' => $serviceData['deficiencies']->count()]) }}
                                @else
                                    {{ __('tasks.performance.services.items_count', ['count' => $serviceData['transactions']->count()]) }}
                                @endif
                            </x-filament::badge>
                            @if($this->serviceCardFilter)
                                <x-filament::badge color="info">
                                    {{ __('tasks.performance.services.cards.'.$this->serviceCardFilter) }}
                                </x-filament::badge>
                                <x-filament::link tag="button" wire:click="clearServiceCardFilter" style="font-size:0.75rem;">
                                    {{ __('tasks.performance.services.clear_filter') }}
                                </x-filament::link>
                            @endif
                        </div>
                    </div>

                    @if(! $serviceData['deficiencies_only'])
                        <div class="tp-table-container">
                            <table class="tp-table tp-service-table">
                                <thead>
                                    <tr>
                                        @foreach(['transaction_number', 'service', 'service_status', 'transaction_date', 'due_date', 'completion_date', 'linked_deficiencies', 'pending_action'] as $column)
                                            <th scope="col">{{ __('tasks.performance.services.columns.'.$column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($serviceData['transactions'] as $transaction)
                                        @php
                                            $linked = \App\Services\ServicePerformanceSummaryService::linkedDeficiencies($transaction);
                                            $pending = \App\Services\ServicePerformanceSummaryService::pendingAction($transaction);
                                        @endphp
                                        <tr wire:key="service-transaction-{{ $transaction->id }}">
                                            <td class="tp-record-cell">
                                                @if(\App\Filament\Resources\ServiceTransactions\ServiceTransactionResource::canEdit($transaction))
                                                    <a class="tp-record-link" href="{{ \App\Filament\Resources\ServiceTransactions\ServiceTransactionResource::getUrl('edit', ['record' => $transaction]) }}" aria-label="{{ __('tasks.performance.services.open_service') }} {{ $transaction->transaction_number }}">
                                                        <bdi>{{ $transaction->transaction_number }}</bdi>
                                                        <x-heroicon-m-arrow-top-right-on-square aria-hidden="true" />
                                                    </a>
                                                @else
                                                    <bdi>{{ $transaction->transaction_number }}</bdi>
                                                @endif
                                            </td>
                                            <td class="tp-task-name">{{ $transaction->display_service_name }}</td>
                                            <td>
                                                <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::statusColor($transaction->service_status)">
                                                    {{ \App\Services\ServicePerformanceSummaryService::statusLabel($transaction->service_status) }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="tp-num"><bdi>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td class="tp-num"><bdi>{{ $transaction->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td class="tp-num"><bdi>{{ $transaction->completion_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            <td>
                                                @if($linked->isEmpty())
                                                    <span class="tp-muted">—</span>
                                                @else
                                                    <div class="tp-linked-list">
                                                        @foreach($linked as $deficiency)
                                                            <span>
                                                                <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($deficiency->status)">
                                                                    {{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($deficiency->deficiency_type) }}
                                                                </x-filament::badge>
                                                                {{ $deficiency->responsibleEmployee?->user?->name ?? __('tasks.performance.services.no_responsible') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <x-filament::badge :color="$pending['color']">{{ $pending['label'] }}</x-filament::badge>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="tp-empty-cell">
                                                <div class="tp-empty-inner">
                                                    <x-heroicon-o-inbox aria-hidden="true" />
                                                    <span>{{ __('tasks.performance.services.empty') }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="tp-toolbar-title" style="margin-top:0.25rem;">
                        {{ __('tasks.performance.services.deficiencies_statement') }}
                    </div>

                    <div class="tp-table-container">
                        <table class="tp-table tp-deficiency-table">
                            <thead>
                                <tr>
                                    @foreach(['transaction_number', 'type', 'title', 'status', 'due_date', 'responsible', 'pending_action'] as $column)
                                        <th scope="col">{{ __('tasks.performance.services.deficiency_columns.'.$column) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceData['deficiencies'] as $deficiency)
                                    @php
                                        $deficiencyTransaction = \App\Services\ServicePerformanceSummaryService::deficiencyTransaction($deficiency);
                                        $pending = $deficiencyTransaction
                                            ? \App\Services\ServicePerformanceSummaryService::pendingAction($deficiencyTransaction)
                                            : ['label' => __('tasks.performance.services.pending_actions.none'), 'color' => 'gray'];
                                    @endphp
                                    <tr wire:key="service-deficiency-{{ $deficiency->id }}">
                                        <td class="tp-num"><bdi>{{ $deficiencyTransaction?->transaction_number ?? '—' }}</bdi></td>
                                        <td>{{ \App\Services\ServicePerformanceSummaryService::deficiencyTypeLabel($deficiency->deficiency_type) }}</td>
                                        <td class="tp-task-name">{{ $deficiency->title }}</td>
                                        <td>
                                            <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::deficiencyStatusColor($deficiency->status)">
                                                {{ \App\Services\ServicePerformanceSummaryService::deficiencyStatusLabel($deficiency->status) }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="tp-num"><bdi>{{ $deficiency->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td>{{ $deficiency->responsibleEmployee?->user?->name ?? __('tasks.performance.services.no_responsible') }}</td>
                                        <td><x-filament::badge :color="$pending['color']">{{ $pending['label'] }}</x-filament::badge></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="tp-empty-cell">
                                            <div class="tp-empty-inner">
                                                <x-heroicon-o-check-circle aria-hidden="true" />
                                                <span>{{ __('tasks.performance.services.deficiencies_empty') }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <x-filament::modal id="task-item-submit-modal" width="lg">
            <x-slot name="heading">
                {{ ($this->activeReviewItem?->status === \App\Enums\TaskStatus::RETURNED_FOR_REVISION) ? __('tasks.review.actions.resubmit') : __('tasks.review.actions.submit') }}
            </x-slot>
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                @if($this->activeReviewItem?->latest_return_reason)
                    <div style="padding:0.75rem; border-radius:0.6rem; background:#fef3c7; color:#92400e; font-size:0.82rem; border:1px solid #fde68a;">
                        <strong>{{ __('tasks.review.fields.latest_return_reason') }}:</strong> {{ $this->activeReviewItem->latest_return_reason }}
                    </div>
                @endif
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.submission_note') }}</label>
                    <textarea wire:model="submissionNote" rows="3" class="tp-modal-input"></textarea>
                    @error('status') <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p> @enderror
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'task-item-submit-modal' })">{{ __('tasks.review.actions.cancel') }}</x-filament::button>
                    <x-filament::button color="primary" wire:click="submitItemForReview">{{ __('tasks.review.actions.submit') }}</x-filament::button>
                </div>
            </div>
        </x-filament::modal>

        <x-filament::modal id="task-item-approve-modal" width="lg">
            <x-slot name="heading">{{ __('tasks.review.actions.approve') }}</x-slot>
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.score') }}</label>
                    <input type="number" step="0.01" min="0" wire:model="approvalScore" class="tp-modal-input" />
                    @error('score') <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p> @enderror
                    @error('approvalScore') <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.approval_note') }}</label>
                    <textarea wire:model="approvalNote" rows="2" class="tp-modal-input"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'task-item-approve-modal' })">{{ __('tasks.review.actions.cancel') }}</x-filament::button>
                    <x-filament::button color="success" wire:click="approveItemReview">{{ __('tasks.review.actions.approve') }}</x-filament::button>
                </div>
            </div>
        </x-filament::modal>

        <x-filament::modal id="task-item-return-modal" width="lg">
            <x-slot name="heading">{{ __('tasks.review.actions.return') }}</x-slot>
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.return_category') }}</label>
                    <select wire:model="returnCategory" class="tp-modal-input">
                        <option value="{{ \App\Models\TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT }}">{{ __('tasks.review.return_categories.execution_defect') }}</option>
                        <option value="{{ \App\Models\TaskAssignmentItemReview::RETURN_SCOPE_CHANGE }}">{{ __('tasks.review.return_categories.scope_change') }}</option>
                    </select>
                </div>
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.return_reason') }}</label>
                    <textarea wire:model="returnReason" rows="3" class="tp-modal-input"></textarea>
                    @error('reason') <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p> @enderror
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'task-item-return-modal' })">{{ __('tasks.review.actions.cancel') }}</x-filament::button>
                    <x-filament::button color="warning" wire:click="returnItemForRevision">{{ __('tasks.review.actions.return') }}</x-filament::button>
                </div>
            </div>
        </x-filament::modal>

        <x-filament::modal id="task-item-extend-modal" width="lg">
            <x-slot name="heading">{{ __('tasks.review.actions.extend_due_date') }}</x-slot>
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.new_due_date') }}</label>
                    <input type="date" wire:model="extensionNewDueDate" class="tp-modal-input" />
                </div>
                <div>
                    <label class="tp-modal-field-label">{{ __('tasks.review.fields.extension_reason') }}</label>
                    <textarea wire:model="extensionReason" rows="3" class="tp-modal-input"></textarea>
                    @error('reason') <p style="color:#dc2626; font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p> @enderror
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'task-item-extend-modal' })">{{ __('tasks.review.actions.cancel') }}</x-filament::button>
                    <x-filament::button color="info" wire:click="extendItemDueDate">{{ __('tasks.review.actions.extend_due_date') }}</x-filament::button>
                </div>
            </div>
        </x-filament::modal>

        <x-filament::modal id="task-item-history-modal" width="2xl">
            <x-slot name="heading">{{ __('tasks.review.actions.history') }} — {{ $this->activeReviewItem?->task?->name }}</x-slot>
            <div style="display:flex; flex-direction:column; gap:0.65rem;">
                @forelse($this->activeReviewItem?->reviews ?? [] as $review)
                    <div style="border:1px solid #e2e8f0; border-radius:0.65rem; padding:0.8rem; font-size:0.82rem;">
                        <div style="display:flex; justify-content:space-between; gap:0.5rem; font-weight:700;">
                            <span>{{ __('tasks.review.labels.attempt', ['number' => $review->attempt_number]) }}</span>
                            <span>{{ $review->submitted_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        @if($review->submission_note)
                            <div style="margin-top:0.35rem;"><strong>{{ __('tasks.review.fields.submission_note') }}:</strong> {{ $review->submission_note }}</div>
                        @endif
                        @if($review->decided_at)
                            <div style="margin-top:0.35rem; color:#64748b;">
                                <strong>{{ $review->decision === \App\Models\TaskAssignmentItemReview::DECISION_APPROVED ? __('tasks.review.actions.approve') : __('tasks.review.actions.return') }}</strong>
                                — {{ $review->decider?->name }} ({{ $review->decided_at->format('Y-m-d H:i') }})
                                @if($review->awarded_score !== null)
                                    — {{ __('tasks.review.fields.score') }}: {{ number_format((float) $review->awarded_score, 2) }}
                                @endif
                            </div>
                        @endif
                        @if($review->decision_reason)
                            <div style="margin-top:0.25rem;">{{ $review->decision_reason }}</div>
                        @endif
                    </div>
                @empty
                    <p style="font-size:0.85rem; color:#64748b;">{{ __('tasks.review.labels.no_history') }}</p>
                @endforelse
            </div>
        </x-filament::modal>
    @endif
</x-filament-panels::page>
