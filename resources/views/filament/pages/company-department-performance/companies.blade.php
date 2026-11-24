@php
    $companies = $this->companiesSummary;
@endphp

<div class="perf-entity-section">
    <div class="perf-section-header">
        <div class="perf-section-title-wrap">
            <h2 class="perf-section-title">{{ __('performance.levels.companies') }}</h2>
            <span class="perf-count-badge">{{ count($companies) }}</span>
        </div>
    </div>

    <div class="perf-cards-grid">
        @forelse($companies as $row)
            <div class="card inst" style="--i: {{ $loop->index }};" wire:key="company-card-{{ $row->company_id }}">
                <div class="inst-top">
                    <div class="inst-info">
                        <b class="inst-title" wire:click="selectCompany({{ $row->company_id }})" role="button" tabindex="0">
                            {{ $row->company_name }}
                        </b>
                        <div class="inst-subtitle">
                            <bdi>{{ $row->company_number ?: '—' }}</bdi>
                        </div>
                    </div>
                    <span class="inst-badge">#{{ $row->company_id }}</span>
                </div>

                <div
                    class="inst-kpi-banner {{ $row->active_services > 0 ? 'has-active' : '' }}"
                    wire:click="openStatement('services', 'active', {{ $row->company_id }})"
                    role="button"
                    tabindex="0"
                    title="{{ __('performance.cards.active_services_hint') }}"
                >
                    <div class="v">
                        <span>{{ number_format($row->active_services) }}</span>
                        <u>{{ __('performance.columns.active_services') }}</u>
                    </div>
                    <span class="inst-kpi-link">{{ __('performance.columns.view_statement') }} &larr;</span>
                </div>

                <div class="mini">
                    <span>
                        {{ __('performance.columns.departments_count') }}
                        <b class="n">{{ number_format($row->departments_count) }}</b>
                    </span>
                    <span>
                        {{ __('performance.columns.employees_count') }}
                        <b class="n">{{ number_format($row->employees_count) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openStatement('services', 'completed', {{ $row->company_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.completed_services') }}
                        <b>{{ number_format($row->completed_services) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openStatement('services', 'overdue', {{ $row->company_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.overdue_services') }}
                        <b style="color: {{ $row->overdue_services > 0 ? '#ef4444' : 'inherit' }}">{{ number_format($row->overdue_services) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openStatement('deficiencies', 'all', {{ $row->company_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.open_deficiencies') }}
                        <b style="color: {{ $row->open_deficiencies > 0 ? '#f59e0b' : 'inherit' }}">{{ number_format($row->open_deficiencies) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openStatement('tasks', 'completed', {{ $row->company_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.tasks_completed') }}
                        <b>{{ number_format($row->task_completed_in_period) }} / {{ number_format($row->task_due_in_period) }}</b>
                    </span>
                </div>

                <x-performance.operational-rates
                    :on-time="$row->on_time_rate"
                    :task-completion="$row->task_completion_rate"
                    :on-time-action="'openStatement(\'services\', \'on_time\', ' . $row->company_id . ')'"
                    :task-completion-action="'openStatement(\'tasks\', \'all\', ' . $row->company_id . ')'"
                />

                <div class="inst-footer" wire:click="selectCompany({{ $row->company_id }})" role="button" tabindex="0">
                    <span>{{ __('performance.levels.view_departments') }}</span>
                    <x-heroicon-m-arrow-left style="width: 14px; height: 14px;" />
                </div>
            </div>
        @empty
            <div class="perf-empty-state-card">
                <div class="perf-empty-icon">
                    <x-heroicon-o-building-office-2 style="width: 32px; height: 32px;" />
                </div>
                <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                <div class="perf-empty-sub">{{ __('performance.empty.hint') }}</div>
            </div>
        @endforelse
    </div>
</div>

