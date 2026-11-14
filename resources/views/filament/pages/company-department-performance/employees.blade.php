@php
    $employees = $this->employeesSummary;
    $selectedCompany = $this->selectedCompany;
    $selectedDepartment = $this->selectedDepartment;
    $companyName = $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—');
    $deptName = $this->selectedDepartmentId === 'unassigned'
        ? __('performance.levels.unassigned_or_no_department')
        : ($selectedDepartment?->parent ? "{$selectedDepartment->name} ({$selectedDepartment->parent->name})" : ($selectedDepartment?->name ?? '—'));
@endphp

<div class="perf-entity-section">
    <div class="perf-section-header">
        <div class="perf-section-title-wrap">
            <h2 class="perf-section-title">{{ __('performance.levels.employees', ['department' => $deptName, 'company' => $companyName]) }}</h2>
            <span class="perf-count-badge">{{ count($employees) }}</span>
        </div>

        <button class="perf-back-btn" wire:click="backToDepartments" role="button">
            <x-heroicon-m-arrow-right style="width: 14px; height: 14px;" />
            <span>{{ __('performance.levels.back_to_departments') }}</span>
        </button>
    </div>

    <div class="perf-cards-grid">
        @forelse($employees as $row)
            <div class="card inst" style="--i: {{ $loop->index }};" wire:key="employee-card-{{ $row->employee_id }}">
                <div class="inst-top">
                    <div class="inst-info">
                        <b class="inst-title">
                            {{ $row->name }}
                        </b>
                        <div class="inst-subtitle">
                            <bdi>{{ $row->employee_number ?: '—' }}</bdi>
                            @if($row->job_title)
                                <span class="inst-subtitle-dot">&bull;</span>
                                <span>{{ $row->job_title }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($row->is_saudi)
                            <span class="inst-pill-badge is-saudi">
                                {{ __('performance.columns.saudi') }}
                            </span>
                        @else
                            <span class="inst-pill-badge is-foreign">
                                {{ __('performance.columns.foreign') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div
                    class="inst-kpi-banner {{ $row->active_services > 0 ? 'has-active' : '' }}"
                    wire:click="openKashf('services', 'active', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})"
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
                    <span
                        class="mini-clickable"
                        wire:click="openKashf('services', 'completed', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.completed_services') }}
                        <b>{{ number_format($row->completed_services) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openKashf('services', 'overdue', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.overdue_services') }}
                        <b style="color: {{ $row->overdue_services > 0 ? '#ef4444' : 'inherit' }}">{{ number_format($row->overdue_services) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openKashf('deficiencies', 'all', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.open_deficiencies') }}
                        <b style="color: {{ $row->open_deficiencies > 0 ? '#f59e0b' : 'inherit' }}">{{ number_format($row->open_deficiencies) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openKashf('tasks', 'completed', {{ $this->selectedCompanyId }}, '{{ $this->selectedDepartmentId }}', {{ $row->employee_id }})"
                        role="button"
                        tabindex="0"
                    >
                        {{ __('performance.columns.tasks_completed') }}
                        <b>{{ $row->task_completed_in_period !== null ? number_format($row->task_completed_in_period) : '—' }}</b>
                    </span>
                </div>

                <div class="rings-row">
                    <x-performance.rate-ring
                        :rate="$row->on_time_rate"
                        :label="__('performance.columns.on_time_rate')"
                        size="m"
                        :action="'openKashf(\'services\', \'on_time\', ' . $this->selectedCompanyId . ', \'' . $this->selectedDepartmentId . '\', ' . $row->employee_id . ')'"
                    />
                    <x-performance.rate-ring
                        :rate="$row->task_completion_rate"
                        :label="__('performance.columns.task_completion_rate')"
                        size="m"
                        :action="'openKashf(\'tasks\', \'all\', ' . $this->selectedCompanyId . ', \'' . $this->selectedDepartmentId . '\', ' . $row->employee_id . ')'"
                    />
                </div>

                @if($row->performance_url)
                    <a href="{{ $row->performance_url }}" target="_blank" class="inst-footer is-link">
                        <span>{{ __('performance.columns.view_profile') }}</span>
                        <x-heroicon-m-arrow-top-right-on-square style="width: 14px; height: 14px;" />
                    </a>
                @else
                    <div class="inst-footer is-disabled">
                        <span>{{ __('performance.columns.view_profile') }}</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="perf-empty-state-card">
                <div class="perf-empty-icon">
                    <x-heroicon-o-users style="width: 32px; height: 32px;" />
                </div>
                <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                <div class="perf-empty-sub">{{ __('performance.empty.no_employees') }}</div>
            </div>
        @endforelse
    </div>
</div>

