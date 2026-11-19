@php
    $overviewDepartments = $this->departmentsOverview;
    $details = $this->detailsDepartment;
    $reviewThreshold = \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;
    $scoreColor = fn (int $value): string => $value >= 80 ? '#22c55e' : ($value >= $reviewThreshold ? '#f59e0b' : '#ef4444');
@endphp

<div class="perf-entity-section">
    <div class="perf-section-header">
        <div class="perf-section-title-wrap">
            <h2 class="perf-section-title">{{ __('performance.department_view.title') }}</h2>
            <span class="perf-count-badge">{{ $overviewDepartments->count() }} {{ __('performance.columns.departments_count') }}</span>
        </div>
    </div>

    <div class="perf-cards-grid dept-grid-style--3">
        @forelse($overviewDepartments as $row)
            @php
                $lowest = $row->lowest_company;
                $lowestValue = (int) $lowest->score->score;
                $aggregateValue = (int) $row->score->score;
                $needsReview = $aggregateValue < $reviewThreshold;
            @endphp
            <div class="card dept-card dept-overview-card" style="--i: {{ $loop->index }};" wire:key="department-overview-card-{{ $row->department_id }}">
                <div class="dept-card-header">
                    <div class="dept-card-title">{{ $row->department_name }}</div>
                    <div class="dept-overview-scope dept-overview-scope--{{ $row->scope }}">
                        <x-heroicon-m-building-office-2 aria-hidden="true" />
                        <span>{{ __('performance.department_view.scope_'.$row->scope) }}</span>
                    </div>
                    @if($row->affiliated_company_names->isNotEmpty())
                        <div class="dept-overview-affiliation">
                            <strong>{{ __('performance.department_view.affiliation_'.$row->scope) }}</strong>
                            <span>{{ $row->affiliated_company_names->take(2)->implode(' · ') }}</span>
                            @if($row->affiliated_company_names->count() > 2)
                                <button type="button" wire:click="openDepartmentDetails({{ $row->department_id }})">
                                    {{ __('performance.department_view.show_all_companies') }}
                                </button>
                            @endif
                        </div>
                    @endif
                    <span class="dept-overview-company-count">
                        {{ trans_choice('performance.department_view.companies_count', $row->companies->count()) }}
                    </span>
                </div>

                <div class="dept-card-score-row">
                    <div class="dept-card-score-badge {{ $needsReview ? 'needs-review' : '' }}">
                        <span class="dept-card-score-label">{{ __('performance.department_view.aggregate_score') }}:</span>
                        <strong class="dept-card-score-val" style="color: {{ $scoreColor($aggregateValue) }};">
                            {{ $aggregateValue }} / 100
                        </strong>
                        @if($needsReview)
                            <span class="dept-card-score-warn" title="{{ __('performance.score.review_status') }}">⚠️</span>
                        @endif
                    </div>
                </div>

                <div class="dept-card-score-row">
                    <div class="dept-card-score-badge dept-overview-lowest-badge {{ $lowestValue < $reviewThreshold ? 'needs-review' : '' }}">
                        <span class="dept-card-score-label">{{ __('performance.department_view.lowest_company', ['company' => $lowest->company_name]) }}:</span>
                        <strong class="dept-card-score-val" style="color: {{ $scoreColor($lowestValue) }};">
                            {{ $lowestValue }} / 100
                        </strong>
                    </div>
                </div>

                <x-performance.operational-rates
                    :on-time="$row->on_time_rate"
                    :task-completion="$row->task_completion_rate"
                    :precision="1"
                />

                <div class="dept-overview-stats">
                    <div class="perf-department-service-share">
                        <div><span>{{ __('performance.score.deduct_overdue_tasks') }}</span></div>
                        <strong class="dept-overview-stat-val {{ $row->overdue_tasks > 0 ? 'has-overdue' : '' }}">{{ number_format($row->overdue_tasks) }}</strong>
                    </div>
                    <div class="perf-department-service-share">
                        <div><span>{{ __('performance.score.deduct_overdue_deficiencies') }}</span></div>
                        <strong class="dept-overview-stat-val {{ $row->overdue_deficiencies > 0 ? 'has-overdue' : '' }}">{{ number_format($row->overdue_deficiencies) }}</strong>
                    </div>
                </div>

                <div class="dept-card-actions">
                    <button type="button" class="dept-btn primary" wire:click="openDepartmentDetails({{ $row->department_id }})">
                        <x-heroicon-o-document-text aria-hidden="true" />
                        <span>{{ __('performance.department_view.details') }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="card is-empty-state">
                <div class="empty-state-wrap">
                    <x-heroicon-o-information-circle style="width: 32px; height: 32px; color: var(--mu, #6b7490); margin-bottom: 0.5rem;" />
                    <strong>{{ __('performance.cards.no_data') }}</strong>
                </div>
            </div>
        @endforelse
    </div>
</div>

<x-filament::modal id="performance-department-details-modal" width="4xl" :close-by-clicking-away="true">
    <x-slot:heading>{{ $details?->department_name }}</x-slot:heading>

    @if($details)
        @if($details->affiliated_company_names->isNotEmpty())
            <div class="dept-overview-affiliation">
                <strong>{{ __('performance.department_view.affiliation_'.$details->scope) }}</strong>
                <span>{{ $details->affiliated_company_names->implode(' · ') }}</span>
            </div>
        @endif
        <div class="perf-table-wrapper">
            <table class="perf-table">
                <thead>
                    <tr>
                        <th>{{ __('performance.department_view.evaluated_companies') }}</th>
                        <th style="text-align: center;">{{ __('performance.department_view.employees') }}</th>
                        <th style="text-align: center;">{{ __('performance.score.deduct_overdue_tasks') }}</th>
                        <th style="text-align: center;">{{ __('performance.score.deduct_overdue_deficiencies') }}</th>
                        <th style="text-align: center;">{{ __('performance.score.total_deduction') }}</th>
                        <th style="text-align: center;">{{ __('performance.department_ranking.score') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details->companies as $company)
                        <tr wire:key="department-detail-{{ $details->department_id }}-{{ $company->company_id }}">
                            <td>{{ $company->company_name }}</td>
                            <td style="text-align: center;">{{ number_format($company->employees_count) }}</td>
                            <td style="text-align: center;">
                                @if($company->overdue_tasks > 0)
                                    <button type="button" wire:click="openDepartmentScoreKashf('tasks', {{ $company->company_id }}, {{ $details->department_id }})" title="{{ __('performance.columns.view_statement') }}">
                                        {{ number_format($company->overdue_tasks) }} × {{ \App\Services\PerformanceScoreService::OVERDUE_TASK_WEIGHT }}
                                    </button>
                                @else
                                    0
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($company->overdue_deficiencies > 0)
                                    <button type="button" wire:click="openDepartmentScoreKashf('deficiencies', {{ $company->company_id }}, {{ $details->department_id }})" title="{{ __('performance.columns.view_statement') }}">
                                        {{ number_format($company->overdue_deficiencies) }} × {{ \App\Services\PerformanceScoreService::OVERDUE_DEFICIENCY_WEIGHT }}
                                    </button>
                                @else
                                    0
                                @endif
                            </td>
                            <td style="text-align: center;">−{{ number_format($company->score->total_deduction) }}</td>
                            <td style="text-align: center;">
                                <strong style="color: {{ $scoreColor((int) $company->score->score) }};">{{ (int) $company->score->score }} / 100</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="perf-hero-subtitle">{{ __('performance.department_view.scope_note') }}</p>
    @endif
</x-filament::modal>
