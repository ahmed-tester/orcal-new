@php
    $summary = $this->summaryData;
    $user = auth()->user();
    $selectedDept = $this->selectedSummaryDepartment;
    $score = $summary['score'] ?? null;
    $hasActivity = (bool) ($summary['has_operational_activity'] ?? false);
    $scoreValue = ($score && $score->available && $hasActivity) ? (int) $score->score : null;
    $scoreColor = $scoreValue !== null
        ? ($scoreValue >= 80 ? '#22c55e' : ($scoreValue >= \App\Services\PerformanceScoreService::REVIEW_THRESHOLD ? '#f59e0b' : '#ef4444'))
        : 'var(--bd, #e4e8f3)';
    $needsReview = $scoreValue !== null && $scoreValue < \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;
    $cards = $summary['cards'] ?? [];
    $departments = ! ($summary['is_department_view'] ?? false) ? $this->departmentsOverview : collect();
    $companyLevel = $summary['company_level'] ?? null;
    $hasCompanyLevel = is_array($companyLevel) && (($companyLevel['debts'] ?? null) !== null || ($companyLevel['violations'] ?? null) !== null || ($companyLevel['compliance'] ?? null) !== null);
@endphp

<div class="perf-summary-shell">
    {{-- Hero Card --}}
    <section class="perf-summary-hero" aria-label="{{ __('performance.summary.hero_aria') }}">
        <div class="perf-summary-hero-side">
            @if($selectedDept)
                <button type="button" class="perf-summary-back" wire:click="backToSummaryOverview">
                    <x-heroicon-m-arrow-right style="width: 16px; height: 16px;" />
                    <span>{{ __('performance.summary.back_to_general') }}</span>
                </button>
            @endif
            <div class="perf-summary-greeting">
                {{ __('performance.summary.greeting', ['name' => $user?->name ?? __('performance.summary.default_user')]) }}
            </div>
            <div class="perf-summary-scope">
                @if($selectedDept)
                    <span class="perf-summary-scope-badge is-dept">{{ $selectedDept->department_name }}</span>
                    @if($selectedDept->companies->isNotEmpty())
                        <span class="perf-summary-scope-companies">
                            {{ $selectedDept->companies->pluck('company_name')->implode(' • ') }}
                        </span>
                    @endif
                @elseif($summary['is_supervisor'] ?? false)
                    <span class="perf-summary-scope-badge">{{ __('performance.summary.supervisor_scope') }}</span>
                @else
                    <span class="perf-summary-scope-badge">{{ __('performance.summary.general_scope') }}</span>
                @endif
            </div>
            <div class="perf-summary-period">
                <x-heroicon-o-calendar-days style="width: 15px; height: 15px;" />
                <bdi>{{ $this->dateFrom }} — {{ $this->dateTo }}</bdi>
            </div>
        </div>

        <div class="perf-summary-hero-center">
            <x-performance.rate-ring
                :rate="$scoreValue"
                :label="__('performance.summary.operational_score')"
                :sublabel="$scoreValue !== null ? __('performance.score.of_100') : __('performance.cards.no_data')"
                :color="$scoreColor"
                size="l"
                :show-percent="false"
                no-data-text="—"
            />
            @if($scoreValue !== null)
                <span class="perf-summary-status {{ $needsReview ? 'is-review' : 'is-ok' }}" style="--col: {{ $scoreColor }};">
                    {{ $needsReview ? __('performance.score.review_status') : __('performance.score.current_status') }}
                </span>
            @else
                <span class="perf-summary-status">{{ __('performance.cards.no_data') }}</span>
            @endif
            <p class="perf-summary-score-note">{{ __('performance.summary.score_note') }}</p>
        </div>

        <div class="perf-summary-hero-meta">
            <div class="perf-summary-meta-pill">
                <span>{{ __('performance.summary.employees_in_scope') }}</span>
                <strong><bdi>{{ number_format((int) ($summary['stats']->employees_count ?? 0)) }}</bdi></strong>
            </div>
            @if(! ($summary['is_department_view'] ?? false))
                <div class="perf-summary-meta-pill">
                    <span>{{ __('performance.summary.departments_in_scope') }}</span>
                    <strong><bdi>{{ number_format($departments->count()) }}</bdi></strong>
                </div>
            @endif
            <div class="perf-summary-legend">
                {{ __('performance.summary.snapshot_vs_period_note') }}
            </div>
        </div>
    </section>

    {{-- 6-Card Executive Grid --}}
    <section class="perf-summary-grid" aria-label="{{ __('performance.summary.cards_aria') }}">
        {{-- Card 1: Tasks & Deficiencies --}}
        @if(! empty($cards['tasks_deficiencies']))
            @php $c = $cards['tasks_deficiencies']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #3b82f6;">
                            <x-heroicon-o-clipboard-document-check style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.tasks_deficiencies') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.period_and_snapshot') }}</span>
                        </div>
                    </div>
                    @if($c['can_tasks'] ?? true)
                        <x-performance.rate-ring
                            :rate="$c['rate']"
                            :label="__('performance.cards.tasks_completion_rate')"
                            :sublabel="$c['rate'] === null ? __('performance.cards.no_data') : null"
                            :action="$c['action']"
                            no-data-text="—"
                        />
                    @endif
                </div>
                <div class="perf-summary-metrics">
                    @if($c['can_tasks'] ?? true)
                        <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['actions']['due'] }}">
                            <span>{{ __('performance.columns.tasks_due') }}</span>
                            <strong><bdi>{{ number_format($c['task_due']) }}</bdi></strong>
                        </button>
                        <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['actions']['completed'] }}">
                            <span>{{ __('performance.columns.tasks_completed') }}</span>
                            <strong><bdi>{{ number_format($c['task_completed']) }}</bdi></strong>
                        </button>
                    @endif
                    @if($c['can_deficiencies'] ?? true)
                        <button type="button" class="perf-summary-metric-btn is-warn" wire:click="{{ $c['actions']['deficiencies_open'] }}">
                            <span>{{ __('performance.cards.open_deficiencies') }}</span>
                            <strong><bdi>{{ number_format($c['open_deficiencies']) }}</bdi></strong>
                        </button>
                        <button type="button" class="perf-summary-metric-btn is-danger" wire:click="{{ $c['actions']['deficiencies_overdue'] }}">
                            <span>{{ __('performance.score.deduct_overdue_deficiencies') }}</span>
                            <strong><bdi>{{ number_format($c['overdue_deficiencies']) }}</bdi></strong>
                        </button>
                    @endif
                </div>
            </article>
        @endif

        {{-- Card 2: Payroll Collections & Debts --}}
        @if(! empty($cards['collection_debts']))
            @php $c = $cards['collection_debts']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #10b981;">
                            <x-heroicon-o-banknotes style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.collection_debts') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.period_indicator') }}</span>
                        </div>
                    </div>
                </div>
                <div class="perf-summary-split">
                    @if(! empty($c['collection']))
                        @php $col = $c['collection']; @endphp
                        <div class="perf-summary-subblock">
                            <x-performance.rate-ring
                                :rate="$col['rate']"
                                :label="__('performance.summary.payroll_collection')"
                                :sublabel="$col['rate'] === null ? __('performance.cards.no_data') : null"
                                :action="$col['action']"
                                no-data-text="—"
                            />
                            <button type="button" class="perf-summary-metric-btn" wire:click="{{ $col['action'] }}">
                                <span>{{ __('performance.summary.remaining_amount') }}</span>
                                <strong><bdi>{{ number_format($col['remaining'], 2) }}</bdi></strong>
                            </button>
                        </div>
                    @endif
                    @if(! empty($c['debts']))
                        @php $dbt = $c['debts']; @endphp
                        <div class="perf-summary-subblock">
                            <x-performance.rate-ring
                                :rate="$dbt['rate']"
                                :label="__('performance.summary.debts_paid_rate')"
                                :sublabel="$dbt['rate'] === null ? __('performance.cards.no_data') : null"
                                :action="$dbt['action']"
                                no-data-text="—"
                            />
                            <button type="button" class="perf-summary-metric-btn is-warn" wire:click="{{ $dbt['action'] }}">
                                <span>{{ __('performance.summary.remaining_amount') }}</span>
                                <strong><bdi>{{ number_format($dbt['remaining'], 2) }}</bdi></strong>
                            </button>
                        </div>
                    @endif
                </div>
            </article>
        @endif

        {{-- Card 3: Violations (General level only) --}}
        @if(! empty($cards['violations']))
            @php $c = $cards['violations']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #ef4444;">
                            <x-heroicon-o-shield-exclamation style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.violations') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.company_level_period') }}</span>
                        </div>
                    </div>
                    <x-performance.rate-ring
                        :rate="$c['rate']"
                        :label="__('performance.summary.violations_paid_rate')"
                        :sublabel="$c['rate'] === null ? __('performance.cards.no_data') : null"
                        :action="$c['action']"
                        no-data-text="—"
                    />
                </div>
                <div class="perf-summary-metrics">
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['action'] }}">
                        <span>{{ __('performance.summary.total_count') }}</span>
                        <strong><bdi>{{ number_format($c['count']) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['paid_action'] }}">
                        <span>{{ __('performance.summary.paid_count') }}</span>
                        <strong><bdi>{{ number_format($c['fully_paid_count']) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn is-danger" wire:click="{{ $c['remaining_action'] }}">
                        <span>{{ __('performance.summary.remaining_amount') }}</span>
                        <strong><bdi>{{ number_format($c['remaining'], 2) }}</bdi></strong>
                    </button>
                </div>
            </article>
        @endif

        {{-- Card 4: Payroll --}}
        @if(! empty($cards['payroll']))
            @php $c = $cards['payroll']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #6366f1;">
                            <x-heroicon-o-wallet style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.payroll') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.period_indicator') }}</span>
                        </div>
                    </div>
                    <x-performance.rate-ring
                        :rate="$c['rate']"
                        :label="__('performance.summary.payroll_paid_rate')"
                        :sublabel="$c['rate'] === null ? __('performance.cards.no_data') : null"
                        :action="$c['action']"
                        no-data-text="—"
                    />
                </div>
                <div class="perf-summary-metrics is-stacked">
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['paid_action'] }}">
                        <span>{{ __('performance.summary.net_amount') }}</span>
                        <strong><bdi>{{ number_format($c['net'], 2) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['paid_action'] }}">
                        <span>{{ __('performance.summary.paid_amount') }}</span>
                        <strong><bdi>{{ number_format($c['paid'], 2) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn is-warn" wire:click="{{ $c['action'] }}">
                        <span>{{ __('performance.summary.remaining_amount') }}</span>
                        <strong><bdi>{{ number_format($c['remaining'], 2) }}</bdi></strong>
                    </button>
                </div>
            </article>
        @endif

        {{-- Card 5: Compliance (Residencies, Licenses & Subscriptions) --}}
        @if(! empty($cards['compliance']))
            @php $c = $cards['compliance']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #0ea5e9;">
                            <x-heroicon-o-identification style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.compliance') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.snapshot_indicator') }}</span>
                        </div>
                    </div>
                </div>
                @if(! empty($c['documents']))
                    <div class="perf-summary-compliance-docs">
                        @foreach($c['documents'] as $docKey => $doc)
                            <div class="perf-summary-doc-box">
                                <x-performance.rate-ring
                                    :rate="$doc['rate']"
                                    :label="__('performance.summary.compliance_'.$docKey)"
                                    :sublabel="$doc['rate'] === null ? __('performance.cards.no_data') : null"
                                    :action="$doc['action']"
                                    no-data-text="—"
                                />
                                <div class="perf-summary-doc-actions">
                                    <button type="button" class="perf-summary-chip-btn is-danger" wire:click="{{ $doc['action'] }}">
                                        <span>{{ __('performance.summary.expired_count') }}</span>
                                        <strong><bdi>{{ number_format($doc['expired']) }}</bdi></strong>
                                    </button>
                                    <button type="button" class="perf-summary-chip-btn is-warn" wire:click="{{ $doc['soon_action'] }}">
                                        <span>{{ __('performance.summary.soon_count') }}</span>
                                        <strong><bdi>{{ number_format($doc['expiring_soon']) }}</bdi></strong>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if(! empty($c['company']['licenses']))
                    <div class="perf-summary-subs">
                        <div class="perf-summary-subs-title">{{ __('performance.summary.licenses_title') }}</div>
                        <div class="perf-summary-license-list">
                            @foreach($c['company']['licenses'] as $licenseKey => $license)
                                <div class="perf-summary-license-row">
                                    <span class="perf-summary-license-name">{{ __('performance.compliance.'.$licenseKey) }}</span>
                                    <span class="perf-summary-license-rate">
                                        <bdi>{{ $license['rate'] !== null ? round($license['rate'], 1).'%' : '—' }}</bdi>
                                    </span>
                                    <div class="perf-summary-doc-actions" style="width: auto;">
                                        <button type="button" class="perf-summary-chip-btn is-danger" wire:click="{{ $license['action'] }}">
                                            <span>{{ __('performance.summary.expired_count') }}</span>
                                            <strong><bdi>{{ number_format($license['expired']) }}</bdi></strong>
                                        </button>
                                        <button type="button" class="perf-summary-chip-btn is-warn" wire:click="{{ $license['soon_action'] }}">
                                            <span>{{ __('performance.summary.soon_count') }}</span>
                                            <strong><bdi>{{ number_format($license['expiring_soon']) }}</bdi></strong>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if(! empty($c['company']['subscriptions']))
                    <div class="perf-summary-subs">
                        <div class="perf-summary-subs-title">{{ __('performance.summary.subscriptions_status') }}</div>
                        <div class="perf-summary-subs-row">
                            @foreach($c['company']['subscriptions'] as $platform => $counts)
                                <span class="perf-summary-sub-chip">
                                    <strong>{{ __('performance.compliance.'.$platform) }}</strong>:
                                    {{ __('performance.summary.sub_active') }} <bdi>{{ $counts['active'] }}</bdi>
                                    @if($counts['expired'] > 0)
                                        • <span class="is-expired">{{ __('performance.summary.sub_expired') }} <bdi>{{ $counts['expired'] }}</bdi></span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @endif

        {{-- Card 6: On-Time Service Completion --}}
        @if(! empty($cards['on_time']))
            @php $c = $cards['on_time']; @endphp
            <article class="perf-summary-card">
                <div class="perf-summary-card-head">
                    <div class="perf-summary-card-title-wrap">
                        <span class="perf-summary-icon" style="--ic: #f59e0b;">
                            <x-heroicon-o-clock style="width: 20px; height: 20px;" />
                        </span>
                        <div>
                            <h3>{{ __('performance.summary.cards.on_time') }}</h3>
                            <span class="perf-summary-tag">{{ __('performance.summary.period_indicator') }}</span>
                        </div>
                    </div>
                    <x-performance.rate-ring
                        :rate="$c['rate']"
                        :label="__('performance.columns.on_time_rate')"
                        :sublabel="$c['rate'] === null ? __('performance.cards.no_data') : null"
                        :action="$c['action']"
                        no-data-text="—"
                    />
                </div>
                <div class="perf-summary-metrics is-stacked">
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['active_action'] }}">
                        <span>{{ __('performance.cards.active_services') }}</span>
                        <strong><bdi>{{ number_format($c['active_services']) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn" wire:click="{{ $c['completed_action'] }}">
                        <span>{{ __('performance.cards.completed_services') }}</span>
                        <strong><bdi>{{ number_format($c['completed_services']) }}</bdi></strong>
                    </button>
                    <button type="button" class="perf-summary-metric-btn is-danger" wire:click="{{ $c['overdue_action'] }}">
                        <span>{{ __('performance.cards.overdue_services') }}</span>
                        <strong><bdi>{{ number_format($c['overdue_services']) }}</bdi></strong>
                    </button>
                </div>
            </article>
        @endif
    </section>

    {{-- Authorized Departments List (General Level) --}}
    @if(! ($summary['is_department_view'] ?? false))
        <section class="perf-summary-departments" aria-label="{{ __('performance.summary.departments_list_title') }}">
            <div class="perf-summary-section-head">
                <h2>{{ __('performance.summary.departments_list_title') }}</h2>
                <span>{{ __('performance.summary.departments_list_hint') }}</span>
            </div>

            @if($departments->isEmpty())
                <div class="perf-empty-state-card">
                    <div class="perf-empty-title">{{ __('performance.summary.no_departments') }}</div>
                </div>
            @else
                <div class="perf-summary-dept-grid">
                    @foreach($departments as $dept)
                        <button
                            type="button"
                            class="perf-summary-dept-card"
                            wire:click="selectSummaryDepartment({{ $dept->department_id }})"
                        >
                            <div class="perf-summary-dept-top">
                                <strong>{{ $dept->department_name }}</strong>
                                <span class="perf-summary-dept-score">
                                    {{ $dept->score->available ? $dept->score->score : '—' }}
                                </span>
                            </div>
                            <div class="perf-summary-dept-meta">
                                <span>{{ __('performance.summary.employees_in_scope') }}: <bdi>{{ number_format($dept->employees_count) }}</bdi></span>
                                <span>{{ __('performance.columns.on_time_rate') }}: <bdi>{{ $dept->on_time_rate !== null ? round($dept->on_time_rate, 1).'%' : '—' }}</bdi></span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Company-level Data Section inside Department View --}}
    @if($hasCompanyLevel)
        <section class="perf-summary-company-level" aria-label="{{ __('performance.summary.company_level_section') }}">
            <div class="perf-summary-section-head">
                <h2>{{ __('performance.summary.company_level_section') }}</h2>
                <span>{{ __('performance.summary.company_level_note') }}</span>
            </div>
            <div class="perf-summary-grid">
                @if(! empty($companyLevel['debts']))
                    @php $dbt = $companyLevel['debts']; @endphp
                    <article class="perf-summary-card">
                        <div class="perf-summary-card-head">
                            <div class="perf-summary-card-title-wrap">
                                <span class="perf-summary-icon" style="--ic: #10b981;">
                                    <x-heroicon-o-banknotes style="width: 20px; height: 20px;" />
                                </span>
                                <div>
                                    <h3>{{ __('performance.summary.debts_paid_rate') }}</h3>
                                    <span class="perf-summary-tag">{{ __('performance.summary.company_level_period') }}</span>
                                </div>
                            </div>
                            <x-performance.rate-ring
                                :rate="$dbt['rate']"
                                :label="__('performance.summary.debts_paid_rate')"
                                :sublabel="$dbt['rate'] === null ? __('performance.cards.no_data') : null"
                                :action="$dbt['action']"
                                no-data-text="—"
                            />
                        </div>
                        <div class="perf-summary-metrics">
                            <button type="button" class="perf-summary-metric-btn is-warn" wire:click="{{ $dbt['action'] }}">
                                <span>{{ __('performance.summary.remaining_amount') }}</span>
                                <strong><bdi>{{ number_format($dbt['remaining'], 2) }}</bdi></strong>
                            </button>
                        </div>
                    </article>
                @endif

                @if(! empty($companyLevel['violations']))
                    @php $v = $companyLevel['violations']; @endphp
                    <article class="perf-summary-card">
                        <div class="perf-summary-card-head">
                            <div class="perf-summary-card-title-wrap">
                                <span class="perf-summary-icon" style="--ic: #ef4444;">
                                    <x-heroicon-o-shield-exclamation style="width: 20px; height: 20px;" />
                                </span>
                                <div>
                                    <h3>{{ __('performance.summary.cards.violations') }}</h3>
                                    <span class="perf-summary-tag">{{ __('performance.summary.company_level_period') }}</span>
                                </div>
                            </div>
                            <x-performance.rate-ring
                                :rate="$v['rate']"
                                :label="__('performance.summary.violations_paid_rate')"
                                :sublabel="$v['rate'] === null ? __('performance.cards.no_data') : null"
                                :action="$v['action']"
                                no-data-text="—"
                            />
                        </div>
                        <div class="perf-summary-metrics">
                            <button type="button" class="perf-summary-metric-btn" wire:click="{{ $v['action'] }}">
                                <span>{{ __('performance.summary.total_count') }}</span>
                                <strong><bdi>{{ number_format($v['count']) }}</bdi></strong>
                            </button>
                            <button type="button" class="perf-summary-metric-btn is-danger" wire:click="{{ $v['remaining_action'] }}">
                                <span>{{ __('performance.summary.remaining_amount') }}</span>
                                <strong><bdi>{{ number_format($v['remaining'], 2) }}</bdi></strong>
                            </button>
                        </div>
                    </article>
                @endif

                @if(! empty($companyLevel['compliance']['licenses']) || ! empty($companyLevel['compliance']['subscriptions']))
                    <article class="perf-summary-card">
                        <div class="perf-summary-card-head">
                            <div class="perf-summary-card-title-wrap">
                                <span class="perf-summary-icon" style="--ic: #0ea5e9;">
                                    <x-heroicon-o-building-office style="width: 20px; height: 20px;" />
                                </span>
                                <div>
                                    <h3>{{ __('performance.summary.licenses_title') }}</h3>
                                    <span class="perf-summary-tag">{{ __('performance.summary.snapshot_indicator') }}</span>
                                </div>
                            </div>
                        </div>
                        @if(! empty($companyLevel['compliance']['licenses']))
                            <div class="perf-summary-license-list">
                                @foreach($companyLevel['compliance']['licenses'] as $licenseKey => $license)
                                    <div class="perf-summary-license-row">
                                        <span class="perf-summary-license-name">{{ __('performance.compliance.'.$licenseKey) }}</span>
                                        <span class="perf-summary-license-rate">
                                            <bdi>{{ $license['rate'] !== null ? round($license['rate'], 1).'%' : '—' }}</bdi>
                                        </span>
                                        <div class="perf-summary-doc-actions" style="width: auto;">
                                            <button type="button" class="perf-summary-chip-btn is-danger" wire:click="{{ $license['action'] }}">
                                                <span>{{ __('performance.summary.expired_count') }}</span>
                                                <strong><bdi>{{ number_format($license['expired']) }}</bdi></strong>
                                            </button>
                                            <button type="button" class="perf-summary-chip-btn is-warn" wire:click="{{ $license['soon_action'] }}">
                                                <span>{{ __('performance.summary.soon_count') }}</span>
                                                <strong><bdi>{{ number_format($license['expiring_soon']) }}</bdi></strong>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(! empty($companyLevel['compliance']['subscriptions']))
                            <div class="perf-summary-subs">
                                <div class="perf-summary-subs-title">{{ __('performance.summary.subscriptions_status') }}</div>
                                <div class="perf-summary-subs-row">
                                    @foreach($companyLevel['compliance']['subscriptions'] as $platform => $counts)
                                        <span class="perf-summary-sub-chip">
                                            <strong>{{ __('performance.compliance.'.$platform) }}</strong>:
                                            {{ __('performance.summary.sub_active') }} <bdi>{{ $counts['active'] }}</bdi>
                                            @if($counts['expired'] > 0)
                                                • <span class="is-expired">{{ __('performance.summary.sub_expired') }} <bdi>{{ $counts['expired'] }}</bdi></span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </article>
                @endif
            </div>
        </section>
    @endif
</div>
