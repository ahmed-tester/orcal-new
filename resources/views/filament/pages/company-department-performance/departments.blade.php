@php
    $departments = $this->departmentsSummary;
    $selectedCompany = $this->selectedCompany;
    $companyName = $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—');

    $validOnTime = $departments->whereNotNull('on_time_rate');
    $avgOnTime = $validOnTime->isNotEmpty() ? round($validOnTime->avg('on_time_rate')) : null;

    $validTasks = $departments->whereNotNull('task_completion_rate');
    $avgTasks = $validTasks->isNotEmpty() ? round($validTasks->avg('task_completion_rate')) : null;

    $totalCompleted = $departments->sum('completed_services');
    $totalActive = $departments->sum('active_services');
    $totalActiveOrCompleted = $totalActive + $totalCompleted;
    $completedServicesShare = $totalActiveOrCompleted > 0 ? round(($totalCompleted / $totalActiveOrCompleted) * 100) : null;

    // Resolve Department Supervisor/Lead names
    $deptIds = $departments->pluck('department_id')->all();
    $leadsMap = \App\Services\CompanyDepartmentPerformanceService::getDepartmentLeads((int) $this->selectedCompanyId, $deptIds);
@endphp

<div class="perf-entity-section">
    <!-- Header with Back Button -->
    <div class="perf-section-header">
        <div class="perf-section-title-wrap">
            <h2 class="perf-section-title">{{ __('performance.supervisors.title') }}</h2>
            <span class="perf-count-badge">{{ count($departments) }} {{ __('performance.columns.departments_count') }}</span>
        </div>

        <button class="perf-back-btn" wire:click="backToCompanies" role="button">
            <x-heroicon-m-arrow-right style="width: 14px; height: 14px;" />
            <span>{{ __('performance.levels.back_to_companies') }}</span>
        </button>
    </div>

    <!-- Summary for the departments shown -->
    @if(count($departments) > 0)
        <div class="perf-avg-banner">
            <div class="perf-avg-header">
                <span class="perf-avg-title">
                    <span>{{ __('performance.supervisors.departments_summary') }}</span>
                </span>
                <button type="button" class="perf-avg-chevron" wire:click="backToCompanies" title="{{ __('performance.levels.back_to_companies') }}">
                    <x-heroicon-m-chevron-left style="width: 20px; height: 20px;" />
                </button>
            </div>

            <div class="perf-avg-rings">
                <x-performance.rate-ring
                    :rate="$avgOnTime"
                    :label="__('performance.supervisors.average_on_time_rate')"
                    size="l"
                />
                <x-performance.rate-ring
                    :rate="$avgTasks"
                    :label="__('performance.supervisors.average_task_completion_rate')"
                    size="l"
                />
                <x-performance.rate-ring
                    :rate="$completedServicesShare"
                    :label="__('performance.supervisors.completed_services_share')"
                    :sublabel="__('performance.supervisors.completed_services_share_hint')"
                    size="l"
                />
            </div>
        </div>
    @endif

    <!-- Department cards -->
    <div class="perf-cards-grid dept-grid-style">
        @forelse($departments as $row)
            @php
                $lead = is_numeric($row->department_id) ? ($leadsMap[$row->department_id] ?? null) : null;
                $leadName = $lead ? $lead->name : ($row->is_unassigned ? '—' : __('performance.supervisors.department_lead'));
                $leadTitle = $lead ? $lead->title : null;

                $activeOrCompleted = $row->completed_services + $row->active_services;
                $departmentCompletedShare = $activeOrCompleted > 0 ? round(($row->completed_services / $activeOrCompleted) * 100) : null;
            @endphp

            <div
                class="card dept-card {{ $row->is_unassigned ? 'is-unassigned' : '' }}"
                style="--i: {{ $loop->index }};"
                wire:key="department-card-{{ $row->department_id }}"
            >
                <!-- Card Header: Department Name + Supervisor/Lead Name -->
                <div class="dept-card-header">
                    <div
                        class="dept-card-title"
                        wire:click="selectDepartment('{{ $row->department_id }}')"
                        role="button"
                    >
                        {{ $row->department_name }}
                    </div>
                    <div class="dept-card-lead">
                        <span>{{ $leadName }}</span>
                        @if($leadTitle)
                            <span class="dept-lead-role">({{ $leadTitle }})</span>
                        @endif
                    </div>

                    @if($row->is_team_scope || $row->is_unassigned)
                        <div class="inst-badges-row" style="justify-content: center; margin-top: 2px;">
                            @if($row->is_team_scope)
                                <span class="inst-pill-badge is-team">
                                    {{ __('performance.levels.team_badge') }}
                                </span>
                            @endif
                            @if($row->is_unassigned)
                                <span class="inst-pill-badge is-unassigned">
                                    {{ __('performance.levels.unassigned_or_no_department') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Department metrics -->
                <div class="dept-card-rings">
                    <x-performance.rate-ring
                        :rate="$row->on_time_rate"
                        :label="__('performance.cards.on_time_rate')"
                        size="dept"
                        :action="'openKashf(\'services\', \'on_time\', ' . $this->selectedCompanyId . ', \'' . $row->department_id . '\')'"
                    />
                    <x-performance.rate-ring
                        :rate="$row->task_completion_rate"
                        :label="__('performance.cards.tasks_completion_rate')"
                        size="dept"
                        :action="'openKashf(\'tasks\', \'all\', ' . $this->selectedCompanyId . ', \'' . $row->department_id . '\')'"
                    />
                    <x-performance.rate-ring
                        :rate="$departmentCompletedShare"
                        :label="__('performance.supervisors.completed_services_share')"
                        :sublabel="__('performance.supervisors.completed_services_share_hint')"
                        size="dept"
                        :action="'openKashf(\'services\', \'completed\', ' . $this->selectedCompanyId . ', \'' . $row->department_id . '\')'"
                    />
                </div>

                <!-- Mini Details Summary -->
                <div class="mini" style="margin: 2px 0 6px;">
                    <span>
                        {{ __('performance.columns.employees_count') }}
                        <b class="n">{{ number_format($row->employees_count) }}</b>
                    </span>
                    <span>
                        {{ __('performance.columns.active_services') }}
                        <b class="n">{{ number_format($row->active_services) }}</b>
                    </span>
                    <span
                        class="mini-clickable"
                        wire:click="openKashf('deficiencies', 'all', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')"
                        role="button"
                    >
                        {{ __('performance.columns.open_deficiencies') }}
                        <b style="color: {{ $row->open_deficiencies > 0 ? '#ea580c' : 'inherit' }}">{{ number_format($row->open_deficiencies) }}</b>
                    </span>
                </div>

                <!-- Department actions -->
                <div class="dept-card-actions">
                    <button
                        type="button"
                        class="btn-action-purple"
                        wire:click="selectDepartment('{{ $row->department_id }}')"
                    >
                        <span>{{ __('performance.supervisors.enter_department') }}</span>
                    </button>

                    <button
                        type="button"
                        class="btn-action-teal"
                        wire:click="openKashf('services', 'all', {{ $this->selectedCompanyId }}, '{{ $row->department_id }}')"
                    >
                        <span>{{ __('performance.supervisors.view_details') }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="perf-empty-state-card">
                <div class="perf-empty-icon">
                    <x-heroicon-o-rectangle-group style="width: 32px; height: 32px;" />
                </div>
                <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                <div class="perf-empty-sub">{{ __('performance.empty.no_departments') }}</div>
            </div>
        @endforelse
    </div>
</div>
