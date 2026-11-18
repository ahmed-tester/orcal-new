@php
    $report = $this->report;
    $totals = $report['totals'];
    $evaluation = $report['evaluation'];
    $employeeTab = $this->employeeTab;
@endphp

<x-filament-panels::page>
    <div class="sup-perf-page">
        <style>
            .sup-perf-page { display: flex; flex-direction: column; gap: 1.15rem; }
            .sup-perf-page .sup-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.875rem;
                padding: 1.25rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
            }
            .dark .sup-perf-page .sup-card { background: #1e293b; border-color: #334155; }

            .sup-perf-page .sup-unavailable {
                display: flex;
                align-items: flex-start;
                gap: 0.55rem;
                padding: 0.85rem 1rem;
                border-radius: 0.75rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
                font-size: 0.82rem;
                font-weight: 600;
            }
            .dark .sup-perf-page .sup-unavailable { background: rgba(154, 52, 18, 0.18); border-color: rgba(251, 146, 60, 0.4); color: #fdba74; }

            .sup-perf-page .sup-tabs { display: flex; gap: 0.4rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.55rem; flex-wrap: wrap; }
            .dark .sup-perf-page .sup-tabs { border-color: #334155; }
            .sup-perf-page .sup-tabs button {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                border: 1px solid transparent;
                background: transparent;
                color: #475569;
                font-size: 0.85rem;
                font-weight: 800;
                padding: 0.55rem 1rem;
                border-radius: 0.6rem;
                cursor: pointer;
                transition: all 0.15s ease-in-out;
            }
            .sup-perf-page .sup-tabs button:hover { background: #f1f5f9; color: #1d4ed8; }
            .dark .sup-perf-page .sup-tabs button { color: #cbd5e1; }
            .dark .sup-perf-page .sup-tabs button:hover { background: #0f172a; color: #93c5fd; }
            .sup-perf-page .sup-tabs button[aria-pressed="true"] { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
            .dark .sup-perf-page .sup-tabs button[aria-pressed="true"] { background: rgba(37, 99, 235, 0.2); border-color: rgba(37, 99, 235, 0.45); color: #93c5fd; }

            .sup-perf-page .sup-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.7rem; margin-bottom: 1rem; }
            .sup-perf-page .sup-kpi {
                border: 1px solid #e2e8f0;
                border-radius: 0.7rem;
                padding: 0.7rem 0.85rem;
                background: #f8fafc;
                display: flex;
                flex-direction: column;
                gap: 0.2rem;
            }
            .dark .sup-perf-page .sup-kpi { background: #0f172a; border-color: #334155; }
            .sup-perf-page .sup-kpi-label { font-size: 0.72rem; font-weight: 700; color: #64748b; }
            .sup-perf-page .sup-kpi-value { font-size: 1.25rem; font-weight: 900; color: #0f172a; }
            .dark .sup-perf-page .sup-kpi-value { color: #f8fafc; }
            .sup-perf-page .sup-kpi--rate .sup-kpi-value { color: #047857; }
            .dark .sup-perf-page .sup-kpi--rate .sup-kpi-value { color: #34d399; }

            .sup-perf-page .sup-evaluation-rings {
                --ring-track: #e2e8f0;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 1rem;
            }
            .dark .sup-perf-page .sup-evaluation-rings { --ring-track: #475569; }
            .sup-evaluation-rings .perf-ring-item { display: grid; justify-items: center; align-content: start; gap: 0.35rem; text-align: center; }
            .sup-evaluation-rings .ring {
                --ring-size: 5.25rem;
                --ring-width: 0.55rem;
                width: var(--ring-size);
                aspect-ratio: 1;
                border-radius: 50%;
                background: conic-gradient(var(--col) calc(var(--p) * 1%), var(--ring-track) 0);
                display: grid;
                place-items: center;
                margin-block: 0.625rem;
                direction: ltr;
            }
            .sup-evaluation-rings .ring.l { --ring-size: 6.5rem; --ring-width: 0.625rem; margin-block: 0; }
            .sup-evaluation-rings .ring i {
                width: calc(var(--ring-size) - 2 * var(--ring-width));
                aspect-ratio: 1;
                border-radius: 50%;
                display: grid;
                place-items: center;
                background: #ffffff;
                color: #0f172a;
                font-size: 0.9rem;
                font-style: normal;
                font-weight: 800;
                font-variant-numeric: tabular-nums;
            }
            .sup-evaluation-rings .ring.l i { font-size: 1.2rem; }
            .dark .sup-evaluation-rings .ring i { background: #1e293b; color: #f8fafc; }
            .sup-evaluation-rings .perf-ring-label { font-size: 0.8rem; color: #334155; font-weight: 700; }
            .sup-evaluation-rings .perf-ring-sublabel { font-size: 0.75rem; color: #475569; }
            .dark .sup-evaluation-rings .perf-ring-label { color: #f1f5f9; }
            .dark .sup-evaluation-rings .perf-ring-sublabel { color: #cbd5e1; }
            @media (max-width: 600px) {
                .sup-perf-page .sup-evaluation-rings { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .sup-evaluation-rings .perf-ring-item:first-child { grid-column: 1 / -1; }
                .sup-evaluation-rings .ring { margin-block: 0; }
            }

            .sup-perf-page .sup-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 0.75rem; }
            .dark .sup-perf-page .sup-table-wrap { border-color: #334155; }
            .sup-perf-page table.sup-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; min-width: 920px; }
            .sup-perf-page table.sup-table thead th {
                background: #f8fafc;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 800;
                text-align: start;
                padding: 0.65rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                white-space: nowrap;
            }
            .dark .sup-perf-page table.sup-table thead th { background: #0f172a; color: #cbd5e1; border-color: #334155; }
            .sup-perf-page table.sup-table tbody td { padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
            .dark .sup-perf-page table.sup-table tbody td { color: #e2e8f0; border-color: #1e293b; }
            .sup-perf-page table.sup-table tbody tr:last-child td { border-bottom: none; }
            .sup-perf-page table.sup-table tbody tr.ts-selected { background: #eff6ff; }
            .dark .sup-perf-page table.sup-table tbody tr.ts-selected { background: rgba(37, 99, 235, 0.16); }
            .sup-perf-page .sup-strong { font-weight: 800; color: #0f172a; }
            .dark .sup-perf-page .sup-strong { color: #f8fafc; }
            .sup-perf-page .sup-muted { font-size: 0.72rem; color: #64748b; }
            .sup-perf-page .sup-num-btn {
                border: 1px solid #dbeafe;
                background: #eff6ff;
                color: #1d4ed8;
                border-radius: 0.45rem;
                font-weight: 800;
                font-size: 0.78rem;
                padding: 0.2rem 0.55rem;
                cursor: pointer;
                min-width: 2rem;
                transition: all 0.12s ease-in-out;
            }
            .sup-perf-page .sup-num-btn:hover { background: #dbeafe; border-color: #93c5fd; }
            .sup-perf-page .sup-num-btn--plain { border-color: transparent; background: transparent; color: #475569; cursor: default; }
            .sup-perf-page .sup-rate { display: inline-flex; flex-direction: column; gap: 0.15rem; }
            .sup-perf-page .sup-rate-value { font-weight: 900; color: #047857; }
            .sup-perf-page .sup-rate-value--na { color: #b45309; }
            .sup-perf-page .sup-empty {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.35rem;
                padding: 2.5rem 1rem;
                color: #64748b;
                font-size: 0.85rem;
                font-weight: 600;
            }
            .sup-perf-page .sup-section-title { font-size: 0.9rem; font-weight: 800; color: #0f172a; margin: 0 0 0.75rem; }
            .dark .sup-perf-page .sup-section-title { color: #f8fafc; }
            .sup-perf-page .sup-employee-head { display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap; margin-bottom: 1rem; }
            .sup-perf-page .sup-avatar {
                width: 44px; height: 44px; border-radius: 9999px;
                background: #082c50; color: #ffffff; display: inline-flex;
                align-items: center; justify-content: center; font-weight: 900; font-size: 1rem;
            }
            .sup-perf-page .sup-modal-note { font-size: 0.8rem; color: #475569; margin-bottom: 0.75rem; }
            .dark .sup-perf-page .sup-modal-note { color: #cbd5e1; }
        </style>

        <div wire:key="supervisor-performance-filters-{{ $report['month'] }}">
            <x-filter-bar
                :title="__('supervisor_performance.filters.title')"
                :show-company="true"
                :companies="$this->companyFilterOptions"
                :company-label="__('supervisor_performance.filters.company')"
                :show-search="false"
                :show-dates="false"
                :has-active="$this->hasActiveFilterBar()"
                reset-method="resetFilterBar"
            >
                <div class="fb-col">
                    <label class="fb-label" for="sup-supervisor">{{ __('supervisor_performance.filters.supervisor') }}</label>
                    <select id="sup-supervisor" wire:model.live="supervisorId" class="fb-input">
                        <option value="">{{ __('supervisor_performance.filters.supervisor_placeholder') }}</option>
                        @foreach($this->supervisorOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="fb-col">
                    <label class="fb-label" for="sup-month">{{ __('supervisor_performance.filters.month') }}</label>
                    <input id="sup-month" type="month" wire:model.live="month" class="fb-input">
                </div>

                <div class="fb-col">
                    <label class="fb-label" for="sup-type">{{ __('supervisor_performance.filters.type') }}</label>
                    <select id="sup-type" wire:model.live="type" class="fb-input">
                        @foreach($this->typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-bar>
        </div>

        <section class="sup-card" aria-labelledby="sup-evaluation-title">
            <h2 id="sup-evaluation-title" class="sup-section-title">
                {{ $report['current_month'] ? __('supervisor_performance.evaluation.current_title', ['date' => $report['evaluation_through']]) : __('supervisor_performance.evaluation.title') }}
            </h2>
            @if($evaluation['reason'] === null)
                <div class="sup-evaluation-rings">
                    <x-performance.rate-ring
                        :rate="$evaluation['total']"
                        :label="__($report['current_month'] ? 'supervisor_performance.evaluation.current_total' : 'supervisor_performance.evaluation.total')"
                        :sublabel="$report['current_month'] ? null : __('performance.score.of_100')"
                        size="l"
                        :precision="2"
                        :fixed-precision="true"
                        :show-percent="false"
                    />
                    @foreach(['team' => '#3762eb', 'personal' => '#16a34a'] as $metric => $color)
                        <x-performance.rate-ring
                            :rate="$evaluation[$metric]"
                            :label="__('supervisor_performance.evaluation.'.$metric).' (50%)'"
                            :sublabel="__('supervisor_performance.evaluation.contribution', ['points' => number_format($evaluation[$metric] / 2, 2)])"
                            :color="$color"
                            :precision="2"
                            :fixed-precision="true"
                        />
                    @endforeach
                </div>
            @else
                <p class="sup-muted">{{ __('supervisor_performance.evaluation.reasons.'.$evaluation['reason']) }}</p>
            @endif
        </section>

        {{-- Tabs --}}
        <nav class="sup-tabs" aria-label="{{ __('supervisor_performance.tabs.navigation') }}">
            <button type="button" wire:click="showSupervisionTab" aria-pressed="{{ $this->activeTab === 'supervision' ? 'true' : 'false' }}">
                <x-heroicon-o-user-group style="width: 16px; height: 16px;" />
                {{ __('supervisor_performance.tabs.supervision') }}
            </button>
            <button type="button" wire:click="showEmployeeTab" aria-pressed="{{ $this->activeTab === 'employee' ? 'true' : 'false' }}">
                <x-heroicon-o-identification style="width: 16px; height: 16px;" />
                {{ __('supervisor_performance.tabs.employee') }}
            </button>
        </nav>

        @if($this->activeTab === 'supervision')
            <div class="sup-card">
                @if($report['team_tasks_visible'])
                <div class="sup-kpis">
                    <div class="sup-kpi">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.due') }}</span>
                        <span class="sup-kpi-value"><bdi>{{ $totals['due'] }}</bdi></span>
                    </div>
                    <div class="sup-kpi">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.on_time') }}</span>
                        <span class="sup-kpi-value"><bdi>{{ $totals['on_time'] }}</bdi></span>
                    </div>
                    <div class="sup-kpi">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.late') }}</span>
                        <span class="sup-kpi-value"><bdi>{{ $totals['late'] }}</bdi></span>
                    </div>
                    <div class="sup-kpi">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.open') }}</span>
                        <span class="sup-kpi-value"><bdi>{{ $totals['open'] }}</bdi></span>
                    </div>
                    <div class="sup-kpi">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.excluded') }}</span>
                        <span class="sup-kpi-value"><bdi>{{ $totals['excluded'] }}</bdi></span>
                    </div>
                    <div class="sup-kpi sup-kpi--rate">
                        <span class="sup-kpi-label">{{ __('supervisor_performance.summary.rate') }}</span>
                        <span class="sup-kpi-value">
                            @if($totals['rate'] === null)
                                {{ __('supervisor_performance.not_measurable') }}
                            @else
                                <bdi>{{ number_format((float) $totals['rate'], 2) }}%</bdi>
                            @endif
                        </span>
                    </div>
                </div>
                @elseif($report['rows'] !== [])
                    <div class="sup-unavailable">{{ __('supervisor_performance.reasons.no_task_permission') }}</div>
                @endif

                @if($report['rows'] === [])
                    <div class="sup-empty">
                        <x-heroicon-o-calendar-days style="width: 30px; height: 30px;" />
                        <span>{{ __('supervisor_performance.table.empty') }}</span>
                    </div>
                @else
                    <div class="sup-table-wrap">
                        <table class="sup-table">
                            <thead>
                                <tr>
                                    <th>{{ __('supervisor_performance.table.supervisor') }}</th>
                                    <th>{{ __('supervisor_performance.table.type') }}</th>
                                    <th>{{ __('supervisor_performance.table.period') }}</th>
                                    <th>{{ __('supervisor_performance.table.status') }}</th>
                                    <th>{{ __('supervisor_performance.table.team') }}</th>
                                    <th>{{ __('supervisor_performance.table.due') }}</th>
                                    <th>{{ __('supervisor_performance.table.on_time') }}</th>
                                    <th>{{ __('supervisor_performance.table.late') }}</th>
                                    <th>{{ __('supervisor_performance.table.open') }}</th>
                                    <th>{{ __('supervisor_performance.table.excluded') }}</th>
                                    <th>{{ __('supervisor_performance.table.rate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['rows'] as $row)
                                    @php
                                        $shift = $row['shift'];
                                        $isSelected = $this->isSelectedShift($row['shift_id']);
                                    @endphp
                                    <tr wire:key="sup-row-{{ $row['shift_id'] }}" @class(['ts-selected' => $isSelected])>
                                        <td>
                                            <div class="sup-strong">{{ $shift->supervisor?->user?->name ?? '—' }}</div>
                                            <div class="sup-muted">
                                                {{ $shift->supervisor?->employee_number ?? '' }}
                                                @if($shift->supervisor?->jobTitle?->name)
                                                    · {{ $shift->supervisor->jobTitle->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <x-filament::badge :color="$shift->type === 'shift' ? 'warning' : 'info'" size="xs">
                                                {{ __('supervisor_performance.filters.types.'.($shift->type === 'shift' ? 'shift' : 'supervision')) }}
                                            </x-filament::badge>
                                        </td>
                                        <td>
                                            <div><bdi>{{ $shift->start_date?->format('Y-m-d') ?? '—' }}</bdi></div>
                                            <div class="sup-muted">
                                                <bdi>{{ $shift->end_date?->format('Y-m-d') ?? '—' }}</bdi>
                                                @if($shift->start_time && $shift->end_time)
                                                    <span> · <bdi>{{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}</bdi></span>
                                                @endif
                                            </div>
                                            @if($row['window'])
                                                <div class="sup-muted">
                                                    {{ __('supervisor_performance.table.measured_window') }}:
                                                    <bdi>{{ $row['window']['start'] }} → {{ $row['window']['end'] }}</bdi>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                                <x-filament::badge :color="match ($shift->status) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'suspended' => 'warning',
                                                    'cancelled' => 'danger',
                                                    default => 'gray',
                                                }" size="xs">
                                                    {{ __('supervisor_performance.statuses.'.($shift->status ?? 'active')) }}
                                                </x-filament::badge>
                                                @if($row['provisional'])
                                                    <x-filament::badge color="warning" size="xs">
                                                        {{ __('supervisor_performance.provisional') }}
                                                    </x-filament::badge>
                                                @endif
                                            </div>
                                        </td>
                                        <td><bdi>{{ $row['team_count'] }}</bdi></td>

                                        @if($row['measurable'])
                                            <td>
                                                <button type="button" class="sup-num-btn"
                                                    wire:click="openStatement({{ $row['shift_id'] }}, 'due')"
                                                    title="{{ __('supervisor_performance.table.view_hint') }}">
                                                    <bdi>{{ $row['due'] }}</bdi>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="sup-num-btn"
                                                    wire:click="openStatement({{ $row['shift_id'] }}, 'on_time')"
                                                    title="{{ __('supervisor_performance.table.view_hint') }}">
                                                    <bdi>{{ $row['on_time'] }}</bdi>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="sup-num-btn"
                                                    wire:click="openStatement({{ $row['shift_id'] }}, 'late')"
                                                    title="{{ __('supervisor_performance.table.view_hint') }}">
                                                    <bdi>{{ $row['late'] }}</bdi>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="sup-num-btn"
                                                    wire:click="openStatement({{ $row['shift_id'] }}, 'open')"
                                                    title="{{ __('supervisor_performance.table.view_hint') }}">
                                                    <bdi>{{ $row['open'] }}</bdi>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="sup-num-btn"
                                                    wire:click="openStatement({{ $row['shift_id'] }}, 'excluded')"
                                                    title="{{ __('supervisor_performance.table.view_hint') }}">
                                                    <bdi>{{ $row['missing_date'] + $row['ambiguous'] }}</bdi>
                                                </button>
                                            </td>
                                            <td>
                                                @if($row['rate'] === null)
                                                    <span class="sup-rate">
                                                        <span class="sup-rate-value sup-rate-value--na">{{ __('supervisor_performance.not_measurable') }}</span>
                                                        @if($row['reason'])
                                                            <span class="sup-muted">{{ __('supervisor_performance.reasons.'.$row['reason']) }}</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="sup-rate">
                                                        <span class="sup-rate-value"><bdi>{{ number_format((float) $row['rate'], 2) }}%</bdi></span>
                                                        <span class="sup-muted">{{ $row['on_time'] }} / {{ $row['due'] }}</span>
                                                        @if($shift->task_id === null)
                                                            <span class="sup-muted">{{ __('supervisor_performance.table.estimated_rate') }}</span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="6">
                                                <span class="sup-rate">
                                                    <span class="sup-rate-value sup-rate-value--na">{{ __('supervisor_performance.not_measurable') }}</span>
                                                    <span class="sup-muted">
                                                        {{ $row['reason'] ? __('supervisor_performance.reasons.'.$row['reason']) : '' }}
                                                    </span>
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            {{-- Employee tab --}}
            <div class="sup-card">
                @if(! $employeeTab['available'])
                    <div class="sup-empty">
                        <x-heroicon-o-identification style="width: 30px; height: 30px;" />
                        <span>{{ __('supervisor_performance.employee.'.($employeeTab['reason'] ?? 'out_of_scope')) }}</span>
                    </div>
                @else
                    @php $employee = $employeeTab['employee']; @endphp
                    <div class="sup-employee-head">
                        <span class="sup-avatar">{{ mb_substr($employee->user?->name ?? '?', 0, 1) }}</span>
                        <div>
                            <div class="sup-strong" style="font-size: 1rem;">{{ $employee->user?->name ?? '—' }}</div>
                            <div class="sup-muted">
                                {{ __('supervisor_performance.employee.employee_number') }}:
                                <bdi>{{ $employee->employee_number ?? '—' }}</bdi>
                                @if($employee->company?->trade_name)
                                    · {{ $employee->company->trade_name }}
                                @endif
                                @if($employee->jobTitle?->name)
                                    · {{ $employee->jobTitle->name }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="sup-kpis">
                        <div class="sup-kpi">
                            <span class="sup-kpi-label">{{ __('supervisor_performance.employee.completion_rate') }}</span>
                            <span class="sup-kpi-value">
                                {{ $employeeTab['completion_rate'] === null ? '—' : number_format((float) $employeeTab['completion_rate'], 2).'%' }}
                            </span>
                        </div>
                        <div class="sup-kpi">
                            <span class="sup-kpi-label">{{ __('supervisor_performance.employee.on_time_rate') }}</span>
                            <span class="sup-kpi-value">
                                {{ $employeeTab['on_time_rate'] === null ? '—' : number_format((float) $employeeTab['on_time_rate'], 2).'%' }}
                            </span>
                        </div>
                        <div class="sup-kpi">
                            <span class="sup-kpi-label">{{ __('supervisor_performance.employee.weighted') }}</span>
                            <span class="sup-kpi-value">
                                {{ $employeeTab['weighted_percentage'] === null ? '—' : number_format((float) $employeeTab['weighted_percentage'], 2).'%' }}
                            </span>
                        </div>
                    </div>

                    @if(! $employeeTab['task_visible'])
                        <div class="sup-unavailable">
                            <x-heroicon-o-lock-closed style="width: 18px; height: 18px; flex-shrink: 0;" />
                            <span>{{ __('supervisor_performance.employee.no_task_data') }}</span>
                        </div>
                    @else
                        <h3 class="sup-section-title" style="margin-top: 1rem;">{{ __('supervisor_performance.employee.tasks') }}</h3>

                        @if($employeeTab['items']->isEmpty())
                            <div class="sup-empty">{{ __('supervisor_performance.employee.no_items') }}</div>
                        @else
                            <div class="sup-table-wrap">
                                <table class="sup-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('supervisor_performance.employee.task') }}</th>
                                            <th>{{ __('supervisor_performance.employee.assignment') }}</th>
                                            <th>{{ __('supervisor_performance.employee.status') }}</th>
                                            <th>{{ __('supervisor_performance.employee.due_date') }}</th>
                                            <th>{{ __('supervisor_performance.employee.completed_at') }}</th>
                                            <th>{{ __('supervisor_performance.employee.weight_score') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employeeTab['items'] as $item)
                                            <tr wire:key="sup-emp-item-{{ $item->id }}">
                                                <td class="sup-strong">{{ $item->task?->name ?? '—' }}</td>
                                                <td><bdi>{{ $item->taskAssignment?->transaction_number ?? '—' }}</bdi></td>
                                                <td>
                                                    <x-filament::badge :color="$item->status?->getColor() ?? 'gray'" size="xs">
                                                        {{ $item->status?->getLabel() ?? '—' }}
                                                    </x-filament::badge>
                                                </td>
                                                <td><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                                <td><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                                                <td>
                                                    <bdi>{{ number_format((float) $item->weight, 2) }}</bdi> /
                                                    <bdi>{{ number_format((float) $item->score, 2) }}</bdi>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif

                    @if(! $employeeTab['service_visible'])
                        <div class="sup-unavailable" style="margin-top: 1rem;">
                            <x-heroicon-o-lock-closed style="width: 18px; height: 18px; flex-shrink: 0;" />
                            <span>{{ __('supervisor_performance.employee.no_service_data') }}</span>
                        </div>
                    @elseif($employeeTab['services'] !== null)
                        <h3 class="sup-section-title" style="margin-top: 1rem;">{{ __('supervisor_performance.employee.services') }}</h3>

                        <div class="sup-kpis">
                            <div class="sup-kpi">
                                <span class="sup-kpi-label">{{ __('supervisor_performance.employee.active_services') }}</span>
                                <span class="sup-kpi-value"><bdi>{{ $employeeTab['services']['stats']->active }}</bdi></span>
                            </div>
                            <div class="sup-kpi">
                                <span class="sup-kpi-label">{{ __('supervisor_performance.employee.completed_services') }}</span>
                                <span class="sup-kpi-value"><bdi>{{ $employeeTab['services']['stats']->completed }}</bdi></span>
                            </div>
                            <div class="sup-kpi">
                                <span class="sup-kpi-label">{{ __('supervisor_performance.employee.overdue_services') }}</span>
                                <span class="sup-kpi-value"><bdi>{{ $employeeTab['services']['stats']->overdue }}</bdi></span>
                            </div>
                            <div class="sup-kpi">
                                <span class="sup-kpi-label">{{ __('supervisor_performance.employee.deficiencies') }}</span>
                                <span class="sup-kpi-value"><bdi>{{ $employeeTab['services']['stats']->assigned_deficiencies }}</bdi></span>
                            </div>
                        </div>

                        @if($employeeTab['services']['transactions']->isEmpty())
                            <div class="sup-empty">{{ __('supervisor_performance.employee.no_services') }}</div>
                        @else
                            <div class="sup-table-wrap">
                                <table class="sup-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('supervisor_performance.employee.transaction') }}</th>
                                            <th>{{ __('supervisor_performance.employee.service') }}</th>
                                            <th>{{ __('supervisor_performance.employee.status') }}</th>
                                            <th>{{ __('supervisor_performance.employee.due_date') }}</th>
                                            <th>{{ __('supervisor_performance.employee.completed_at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employeeTab['services']['transactions'] as $transaction)
                                            <tr wire:key="sup-emp-service-{{ $transaction->id }}">
                                                <td><bdi>{{ $transaction->transaction_number }}</bdi></td>
                                                <td>{{ $transaction->display_service_name }}</td>
                                                <td>
                                                    <x-filament::badge :color="\App\Services\ServicePerformanceSummaryService::statusColor($transaction->service_status)" size="xs">
                                                        {{ \App\Services\ServicePerformanceSummaryService::statusLabel($transaction->service_status) }}
                                                    </x-filament::badge>
                                                </td>
                                                <td><bdi>{{ $transaction->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                                <td><bdi>{{ $transaction->completion_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        @endif

        @if($this->statementRow)
            <x-filament::modal id="supervisor-performance-statement" width="7xl">
                <x-slot:heading>
                    <span>{{ $this->statementTitle }}</span>
                </x-slot:heading>

                @php $statementRow = $this->statementRow; @endphp

                @if(! $statementRow['measurable'])
                    <div class="sup-unavailable">
                        <x-heroicon-o-exclamation-triangle style="width: 18px; height: 18px; flex-shrink: 0;" />
                        <span>
                            {{ __('supervisor_performance.statement.not_measurable') }} —
                            {{ $statementRow['reason'] ? __('supervisor_performance.reasons.'.$statementRow['reason']) : '' }}
                        </span>
                    </div>
                @elseif($this->statementItems->isEmpty())
                    <div class="sup-empty">{{ __('supervisor_performance.statement.empty') }}</div>
                @else
                    <p class="sup-modal-note">
                        {{ __('supervisor_performance.statement.window') }}:
                        <bdi>{{ $statementRow['window']['start'] }} → {{ $statementRow['window']['end'] }}</bdi>
                    </p>

                    <div class="sup-table-wrap">
                        <table class="sup-table">
                            <thead>
                                <tr>
                                    <th>{{ __('supervisor_performance.statement.task') }}</th>
                                    <th>{{ __('supervisor_performance.statement.assignment') }}</th>
                                    <th>{{ __('supervisor_performance.statement.employee') }}</th>
                                    <th>{{ __('supervisor_performance.statement.due_date') }}</th>
                                    <th>{{ __('supervisor_performance.statement.completed_at') }}</th>
                                    <th>{{ __('supervisor_performance.statement.state') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->statementPaginator as $entry)
                                    @php
                                        $item = $entry['item'];
                                        $state = $entry['state'];
                                    @endphp
                                    <tr wire:key="sup-statement-{{ $item->id }}">
                                        <td class="sup-strong">{{ $item->task?->name ?? '—' }}</td>
                                        <td><bdi>{{ $item->taskAssignment?->transaction_number ?? '—' }}</bdi></td>
                                        <td>{{ $item->taskAssignment?->employee?->user?->name ?? '—' }}</td>
                                        <td><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></td>
                                        <td>
                                            <x-filament::badge :color="match ($state) {
                                                \App\Services\SupervisorPerformanceService::ITEM_ON_TIME => 'success',
                                                \App\Services\SupervisorPerformanceService::ITEM_LATE => 'warning',
                                                \App\Services\SupervisorPerformanceService::ITEM_OPEN => 'danger',
                                                \App\Services\SupervisorPerformanceService::ITEM_AMBIGUOUS => 'gray',
                                                default => 'info',
                                            }" size="xs">
                                                {{ __('supervisor_performance.statement.item_states.'.$state) }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($this->statementPaginator->hasPages())
                        <div style="padding-top: 1rem;">
                            <x-filament::pagination :paginator="$this->statementPaginator" />
                        </div>
                    @endif
                @endif

                <x-slot:footerActions>
                    <x-filament::button color="gray" wire:click="closeStatement">
                        {{ __('supervisor_performance.statement.close') }}
                    </x-filament::button>
                </x-slot:footerActions>
            </x-filament::modal>
        @endif
    </div>
</x-filament-panels::page>
