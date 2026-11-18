@php
    $allEmployees = $this->employeesSummary;
    $employees = $this->displayedEmployees;
    $topRanked = $this->topRankedEmployees;
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

    <!-- Department Employees Leadership Sprint Track -->
    @if($topRanked->isNotEmpty())
        <div class="perf-sprint-card" aria-label="{{ __('performance.employee_sprint.title') }}">
            <div class="perf-sprint-header">
                <div class="perf-sprint-title-wrap">
                    <span class="perf-sprint-icon">🏁</span>
                    <div>
                        <h3 class="perf-sprint-title">{{ __('performance.employee_sprint.title') }}</h3>
                        <p class="perf-sprint-subtitle">{{ __('performance.employee_sprint.subtitle') }}</p>
                    </div>
                </div>
            </div>

            <div class="perf-sprint-lanes">
                @foreach($topRanked as $ranked)
                    @php
                        $scoreVal = $ranked->score;
                        $trackClass = $scoreVal >= 90 ? 'is-excellent' : ($scoreVal >= 65 ? 'is-good' : 'is-review');
                    @endphp
                    <div
                        class="perf-sprint-row {{ $trackClass }}"
                        aria-label="{{ $ranked->employee_name }} — {{ __('performance.sprint.score_of_100', ['score' => $scoreVal]) }}"
                    >
                        <div class="perf-sprint-rank-badge">
                            @if($ranked->is_first)
                                <span class="perf-sprint-crown">👑</span>
                            @else
                                <span class="perf-sprint-rank-num">#{{ $ranked->rank }}</span>
                            @endif
                        </div>

                        <div class="perf-sprint-dept-info">
                            <span class="perf-sprint-dept-name">{{ $ranked->employee_name }}</span>
                            <span class="perf-sprint-tag">
                                @if($ranked->is_first)
                                    <span class="perf-sprint-leader-label">
                                        {{ $ranked->is_shared_lead ? __('performance.sprint.shared_lead') : __('performance.sprint.leader') }}
                                    </span>
                                @elseif($ranked->is_tied_with_prev)
                                    <span class="perf-sprint-diff-label is-tied">{{ __('performance.sprint.tied_score') }}</span>
                                @else
                                    <span class="perf-sprint-diff-label">
                                        {{ $ranked->points_behind === 1 ? __('performance.sprint.points_behind_single') : __('performance.sprint.points_behind', ['points' => $ranked->points_behind]) }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div class="perf-sprint-track-bar">
                            <div class="perf-sprint-track-fill" style="width: {{ $scoreVal }}%;"></div>
                        </div>

                        <div class="perf-sprint-score-badge">
                            <strong>{{ $scoreVal }}</strong>
                            <small>/ 100</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Display mode: all / best first / worst employee -->
    <div class="perf-rank-modes" role="group" aria-label="{{ __('performance.employee_ranking.label') }}">
        <span class="perf-rank-modes-title">{{ __('performance.employee_ranking.label') }}</span>
        @foreach(['all', 'best_first', 'worst'] as $rankMode)
            @php $rankAction = "selectEmployeeRankMode('{$rankMode}')"; @endphp
            <button
                type="button"
                class="perf-rank-mode {{ $this->employeeRankMode === $rankMode ? 'is-active' : '' }}"
                wire:click="{{ $rankAction }}"
                aria-pressed="{{ $this->employeeRankMode === $rankMode ? 'true' : 'false' }}"
            >
                {{ __('performance.employee_ranking.'.$rankMode) }}
            </button>
        @endforeach
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
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
                        @if($row->is_saudi)
                            <span class="inst-pill-badge is-saudi">
                                {{ __('performance.columns.saudi') }}
                            </span>
                        @else
                            <span class="inst-pill-badge is-foreign">
                                {{ __('performance.columns.foreign') }}
                            </span>
                        @endif

                        @if($row->is_rankable && ($row->score?->available ?? false) && $row->score?->score !== null)
                            @php
                                $empScoreVal = (int) $row->score->score;
                                $empNeedsReview = $empScoreVal < \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;
                                $empScoreCol = $empScoreVal >= 80 ? '#22c55e' : ($empNeedsReview ? '#ef4444' : '#f59e0b');
                            @endphp
                            <div class="dept-card-score-badge {{ $empNeedsReview ? 'needs-review' : '' }}" style="margin-top: 0;">
                                <span class="dept-card-score-label">{{ __('performance.employee_ranking.score') }}:</span>
                                <strong class="dept-card-score-val" style="color: {{ $empScoreCol }};">
                                    {{ $empScoreVal }} / 100
                                </strong>
                                @if($empNeedsReview)
                                    <span class="dept-card-score-warn" title="{{ __('performance.score.review_status') }}">⚠️</span>
                                @endif
                            </div>
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

                <x-performance.operational-rates
                    :on-time="$row->on_time_rate"
                    :task-completion="$row->task_completion_rate"
                    :on-time-action="'openKashf(\'services\', \'on_time\', ' . $this->selectedCompanyId . ', \'' . $this->selectedDepartmentId . '\', ' . $row->employee_id . ')'"
                    :task-completion-action="'openKashf(\'tasks\', \'all\', ' . $this->selectedCompanyId . ', \'' . $this->selectedDepartmentId . '\', ' . $row->employee_id . ')'"
                />

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
            <div class="card is-empty-state">
                <div class="empty-state-wrap">
                    @if($this->employeeRankMode === 'worst')
                        <x-heroicon-o-check-circle style="width: 32px; height: 32px; color: var(--g, #059669); margin-bottom: 0.5rem;" />
                        <strong>{{ __('performance.employee_ranking.worst_empty_title') }}</strong>
                        <p>{{ __('performance.employee_ranking.no_rankable') }}</p>
                    @else
                        <x-heroicon-o-users style="width: 32px; height: 32px; color: var(--mu, #6b7490); margin-bottom: 0.5rem;" />
                        <strong>{{ __('performance.kashf.empty') }}</strong>
                        <p>{{ __('performance.empty.no_employees') }}</p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>

