@php
    $allDepartments = $this->departmentsSummary;
    $departments = $this->displayedDepartments;
    $topRanked = $this->topRankedDepartments;
    $companyScore = $this->performanceScore;
    $selectedCompany = $this->selectedCompany;
    $companyName = $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—');

    $validOnTime = $allDepartments->whereNotNull('on_time_rate');
    $avgOnTime = $validOnTime->isNotEmpty() ? round($validOnTime->avg('on_time_rate')) : null;

    $validTasks = $allDepartments->whereNotNull('task_completion_rate');
    $avgTasks = $validTasks->isNotEmpty() ? round($validTasks->avg('task_completion_rate')) : null;

    $totalCompleted = $allDepartments->sum('completed_services');
    $totalActive = $allDepartments->sum('active_services');
    $totalActiveOrCompleted = $totalActive + $totalCompleted;
    $completedServicesShare = $totalActiveOrCompleted > 0 ? round(($totalCompleted / $totalActiveOrCompleted) * 100) : null;

    // Resolve Department Supervisor/Lead names
    $deptIds = $departments->pluck('department_id')->all();
    $leadsMap = \App\Services\CompanyDepartmentPerformanceService::getDepartmentLeads((int) $this->selectedCompanyId, $deptIds);

    // Score calculations for the Company Hero Card
    $available = $companyScore !== null && $companyScore->available;
    $scoreValue = $available ? (int) $companyScore->score : null;
    $needsReview = $scoreValue !== null && $scoreValue < \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;
    $scoreColor = $scoreValue !== null
        ? ($scoreValue >= 80 ? '#22c55e' : ($scoreValue >= \App\Services\PerformanceScoreService::REVIEW_THRESHOLD ? '#f59e0b' : '#ef4444'))
        : '#ffffff66';
    $cid = (int) $this->selectedCompanyId;

    $statementActions = [
        'overdue_tasks' => "openKashf('tasks', 'score_overdue', {$cid}, null)",
        'overdue_deficiencies' => "openKashf('deficiencies', 'score_overdue', {$cid}, null)",
    ];

    $deductionLabels = [
        'overdue_tasks' => __('performance.score.deduct_overdue_tasks'),
        'overdue_deficiencies' => __('performance.score.deduct_overdue_deficiencies'),
    ];
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

    <!-- Company Score & Operations Hero Card -->
    <div class="perf-company-hero-card">
        <div class="perf-hero-top">
            <div class="perf-hero-identity">
                <div class="perf-hero-badge-wrap">
                    <span class="perf-hero-pill">{{ __('performance.hero.company_score_title') }}</span>
                    <x-filament::badge color="warning" size="xs">{{ __('performance.score.partial_badge') }}</x-filament::badge>
                </div>
                <h3 class="perf-hero-company-name">{{ $companyName }}</h3>
                <p class="perf-hero-subtitle">{{ __('performance.hero.company_score_subtitle') }}</p>
            </div>
            <button type="button" class="perf-hero-back-link" wire:click="backToCompanies" title="{{ __('performance.levels.back_to_companies') }}">
                <x-heroicon-m-arrow-right style="width: 16px; height: 16px;" />
                <span>{{ __('performance.levels.back_to_companies') }}</span>
            </button>
        </div>

        <div class="perf-hero-body">
            <!-- Score Ring Side -->
            <div class="perf-hero-score-side">
                <x-performance.rate-ring
                    :rate="$scoreValue"
                    :label="__('performance.score.title')"
                    :sublabel="$companyName"
                    :color="$scoreColor"
                    size="l"
                    :show-percent="false"
                    no-data-text="—"
                />
                <span class="perf-hero-score-of">{{ __('performance.score.of_100') }}</span>
                @if($available)
                    <span @class(['perf-hero-score-status', 'review' => $needsReview, 'good' => $scoreValue >= 80, 'caution' => ! $needsReview && $scoreValue < 80])>
                        {{ $needsReview ? __('performance.score.review_status') : __('performance.score.current_status') }}
                    </span>
                @else
                    <span class="perf-hero-score-status">{{ __('performance.score.unavailable') }}</span>
                @endif
            </div>

            <!-- Deductions Side -->
            <div class="perf-hero-deductions">
                <div class="perf-hero-deductions-title">{{ __('performance.score.deductions') }}</div>
                @if($available && isset($companyScore->deductions))
                    <div class="perf-hero-deductions-list">
                        @foreach($companyScore->deductions as $d)
                            <button
                                type="button"
                                class="perf-hero-deduction {{ $d->points > 0 ? 'active' : '' }}"
                                wire:click="{{ $statementActions[$d->key] ?? '' }}"
                                title="{{ __('performance.columns.view_statement') }}"
                            >
                                <span class="perf-hero-deduction-name">{{ $deductionLabels[$d->key] ?? $d->key }}</span>
                                <span class="perf-hero-deduction-math">
                                    <bdi>{{ number_format($d->count) }}</bdi> × {{ $d->weight }}
                                    <strong @class(['perf-hero-deduction-points', 'active' => $d->points > 0])>
                                        −{{ number_format($d->points) }}
                                    </strong>
                                </span>
                            </button>
                        @endforeach
                    </div>
                    @if($companyScore->deductions->every(fn ($d) => $d->points === 0))
                        <div class="perf-hero-no-deductions">{{ __('performance.score.no_deductions') }}</div>
                    @endif
                    <div class="perf-hero-total-deduction">
                        <span>{{ __('performance.score.total_deduction') }}</span>
                        <strong @class(['perf-hero-deduction-points', 'active' => $companyScore->total_deduction > 0])>
                            −{{ number_format($companyScore->total_deduction) }}
                        </strong>
                    </div>
                @endif
                <div class="perf-hero-note">
                    <x-heroicon-o-information-circle style="width: 14px; height: 14px; flex-shrink: 0;" />
                    <span>{{ __('performance.score.partial_note') }}</span>
                </div>
                <details class="perf-hero-score-rules">
                    <summary>{{ __('performance.hero.score_rules_toggle') }}</summary>
                    <ul>
                        <li>{{ __('performance.score.rules_start') }}</li>
                        <li>{{ __('performance.score.rules_task') }}</li>
                        <li>{{ __('performance.score.rules_deficiency') }}</li>
                        <li>{{ __('performance.score.rules_timing') }}</li>
                        <li>{{ __('performance.score.rules_recovery') }}</li>
                    </ul>
                </details>
            </div>
        </div>

        <!-- Metrics Summary Section inside Hero Card -->
        <div class="perf-hero-metrics-section">
            <div class="perf-hero-metrics-title">{{ __('performance.hero.departments_metrics_title') }}</div>
            <div class="perf-hero-rings">
                <x-performance.rate-ring
                    :rate="$avgOnTime"
                    :label="__('performance.supervisors.average_on_time_rate')"
                    size="m"
                />
                <x-performance.rate-ring
                    :rate="$avgTasks"
                    :label="__('performance.supervisors.average_task_completion_rate')"
                    size="m"
                />
                <x-performance.rate-ring
                    :rate="$completedServicesShare"
                    :label="__('performance.supervisors.completed_services_share')"
                    :sublabel="__('performance.supervisors.completed_services_share_hint')"
                    size="m"
                />
            </div>
        </div>
    </div>

    <!-- Company Departments Leadership Sprint Track -->
    @if($topRanked->isNotEmpty())
        <div class="perf-sprint-card" aria-label="{{ __('performance.sprint.title') }}">
            <div class="perf-sprint-header">
                <div class="perf-sprint-title-wrap">
                    <span class="perf-sprint-icon">🏁</span>
                    <div>
                        <h3 class="perf-sprint-title">{{ __('performance.sprint.title') }}</h3>
                        <p class="perf-sprint-subtitle">{{ __('performance.sprint.subtitle') }}</p>
                    </div>
                </div>
            </div>

            <div class="perf-sprint-lanes">
                @foreach($topRanked as $ranked)
                    @php
                        $scoreVal = $ranked->score;
                        $trackClass = $scoreVal >= 90 ? 'is-excellent' : ($scoreVal >= 65 ? 'is-good' : 'is-review');
                    @endphp
                    <button
                        type="button"
                        class="perf-sprint-row {{ $trackClass }}"
                        wire:click="selectDepartment('{{ $ranked->department_id }}')"
                        aria-label="{{ $ranked->department_name }} — {{ __('performance.sprint.score_of_100', ['score' => $scoreVal]) }}"
                        title="{{ __('performance.sprint.view_department') }}"
                    >
                        <div class="perf-sprint-rank-badge">
                            @if($ranked->is_first)
                                <span class="perf-sprint-crown">👑</span>
                            @else
                                <span class="perf-sprint-rank-num">#{{ $ranked->rank }}</span>
                            @endif
                        </div>

                        <div class="perf-sprint-dept-info">
                            <span class="perf-sprint-dept-name">{{ $ranked->department_name }}</span>
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
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Display mode: all / best first / worst department -->
    <div class="perf-rank-modes" role="group" aria-label="{{ __('performance.department_ranking.label') }}">
        <span class="perf-rank-modes-title">{{ __('performance.department_ranking.label') }}</span>
        @foreach(['all', 'best_first', 'worst'] as $rankMode)
            @php $rankAction = "selectDepartmentRankMode('{$rankMode}')"; @endphp
            <button
                type="button"
                class="perf-rank-mode {{ $this->departmentRankMode === $rankMode ? 'is-active' : '' }}"
                wire:click="{{ $rankAction }}"
                aria-pressed="{{ $this->departmentRankMode === $rankMode ? 'true' : 'false' }}"
            >
                {{ __('performance.department_ranking.'.$rankMode) }}
            </button>
        @endforeach
    </div>

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

                    @if(! $row->is_unassigned)
                        <div class="dept-card-lead" title="{{ __('performance.supervisors.department_lead') }}">
                            <x-heroicon-m-user-circle style="width: 14px; height: 14px; color: var(--mu);" />
                            <span>{{ $leadName }}</span>
                            @if($leadTitle)
                                <small class="lead-title-badge">({{ $leadTitle }})</small>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- 3 Performance Rate Rings -->
                <x-performance.operational-rates
                    :on-time="$row->on_time_rate"
                    :task-completion="$row->task_completion_rate"
                />
                <div class="perf-department-service-share">
                    <div>
                        <span>{{ __('performance.supervisors.completed_services_share') }}</span>
                        <small>{{ __('performance.supervisors.completed_services_share_hint') }}</small>
                    </div>
                    <strong>{{ $departmentCompletedShare === null ? __('performance.cards.no_data') : "{$departmentCompletedShare}%" }}</strong>
                </div>

                <!-- Department Partial Performance Score & Rank Badge -->
                <div class="dept-card-score-row">
                    @if($row->score !== null && $row->score->available)
                        @php
                            $deptScoreVal = (int) $row->score->score;
                            $deptNeedsReview = $deptScoreVal < \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;
                            $deptScoreCol = $deptScoreVal >= 80 ? '#22c55e' : ($deptNeedsReview ? '#ef4444' : '#f59e0b');
                        @endphp
                        <div class="dept-card-score-badge {{ $deptNeedsReview ? 'needs-review' : '' }}">
                            <span class="dept-card-score-label">{{ __('performance.department_ranking.score') }}:</span>
                            <strong class="dept-card-score-val" style="color: {{ $deptScoreCol }};">
                                {{ $deptScoreVal }} / 100
                            </strong>
                            @if($deptNeedsReview)
                                <span class="dept-card-score-warn" title="{{ __('performance.score.review_status') }}">⚠️</span>
                            @endif
                        </div>
                    @else
                        <span class="dept-card-score-empty">{{ __('performance.cards.no_data') }}</span>
                    @endif
                </div>

                <!-- Card Actions -->
                <div class="dept-card-actions">
                    <button
                        type="button"
                        class="dept-btn primary"
                        wire:click="selectDepartment('{{ $row->department_id }}')"
                    >
                        <x-heroicon-o-user-group aria-hidden="true" />
                        <span>{{ __('performance.supervisors.enter_department') }}</span>
                    </button>

                    <button
                        type="button"
                        class="dept-btn secondary"
                        wire:click="openKashf('services', 'all', {{ (int) $this->selectedCompanyId }}, '{{ $row->department_id }}')"
                    >
                        <x-heroicon-o-document-text aria-hidden="true" />
                        <span>{{ __('performance.supervisors.view_details') }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="card is-empty-state">
                <div class="empty-state-wrap">
                    @if($this->departmentRankMode === 'worst')
                        <x-heroicon-o-check-circle style="width: 32px; height: 32px; color: var(--g, #059669); margin-bottom: 0.5rem;" />
                        <strong>{{ __('performance.department_ranking.worst_empty_title') }}</strong>
                        <p>{{ __('performance.department_ranking.no_rankable') }}</p>
                    @else
                        <x-heroicon-o-information-circle style="width: 32px; height: 32px; color: var(--mu, #6b7490); margin-bottom: 0.5rem;" />
                        <strong>{{ __('performance.cards.no_data') }}</strong>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>
