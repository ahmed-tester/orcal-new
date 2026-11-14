@props([
    'score' => null,
    'scopeLabel' => '',
    'companyId' => null,
    'departmentId' => null,
])

@php
    $available = $score !== null && $score->available;
    $value = $available ? (int) $score->score : null;
    $ringColor = $value !== null
        ? ($value >= 80 ? '#22c55e' : ($value >= \App\Services\PerformanceScoreService::REVIEW_THRESHOLD ? '#f59e0b' : '#ef4444'))
        : 'var(--bd, #e4e8f3)';
    $needsReview = $value !== null && $value < \App\Services\PerformanceScoreService::REVIEW_THRESHOLD;

    $arg = fn ($v) => $v === null ? 'null' : (is_int($v) ? (string) $v : "'".addslashes((string) $v)."'");
    $cid = $arg($companyId);
    $did = $arg($departmentId);

    $statementActions = [
        'overdue_tasks' => "openKashf('tasks', 'score_overdue', {$cid}, {$did})",
        'overdue_deficiencies' => "openKashf('deficiencies', 'score_overdue', {$cid}, {$did})",
    ];

    $deductionLabels = [
        'overdue_tasks' => __('performance.score.deduct_overdue_tasks'),
        'overdue_deficiencies' => __('performance.score.deduct_overdue_deficiencies'),
    ];
@endphp

<div class="perf-score-panel">
    <div class="perf-score-rules">
        <div class="perf-score-rules-header">
            <div class="ic">
                <x-heroicon-o-scale style="width: 18px; height: 18px;" />
            </div>
            <span>{{ __('performance.score.rules') }}</span>
        </div>
        <ul>
            <li>{{ __('performance.score.rules_start') }}</li>
            <li>{{ __('performance.score.rules_task') }}</li>
            <li>{{ __('performance.score.rules_deficiency') }}</li>
            <li>{{ __('performance.score.rules_timing') }}</li>
            <li>{{ __('performance.score.rules_recovery') }}</li>
        </ul>
        <div class="perf-score-partial-note">
            <x-filament::badge color="warning" size="xs">{{ __('performance.score.partial_badge') }}</x-filament::badge>
            <span>{{ __('performance.score.partial_note') }}</span>
        </div>
    </div>

    <div class="perf-score-main">
        <div class="perf-score-ring-side">
            <x-performance.rate-ring
                :rate="$value"
                :label="__('performance.score.title')"
                :sublabel="$scopeLabel"
                :color="$ringColor"
                size="l"
                :show-percent="false"
                no-data-text="—"
            />
            <span class="perf-score-of">{{ __('performance.score.of_100') }}</span>
            @if($available)
                <span class="perf-score-status {{ $needsReview ? 'review' : '' }}" style="--col: {{ $ringColor }};">
                    {{ $needsReview ? __('performance.score.review_status') : __('performance.score.current_status') }}
                </span>
            @else
                <span class="perf-score-status">{{ __('performance.score.unavailable') }}</span>
            @endif
        </div>

        <div class="perf-score-deductions">
            <div class="perf-score-deductions-title">{{ __('performance.score.deductions') }}</div>
            @if($available)
                @forelse($score->deductions as $d)
                    <button
                        type="button"
                        class="perf-score-deduction {{ $d->points > 0 ? 'active' : '' }}"
                        wire:click="{{ $statementActions[$d->key] }}"
                        title="{{ __('performance.columns.view_statement') }}"
                    >
                        <span class="perf-score-deduction-name">{{ $deductionLabels[$d->key] }}</span>
                        <span class="perf-score-deduction-math">
                            <bdi>{{ number_format($d->count) }}</bdi> × {{ $d->weight }}
                            <strong class="perf-score-deduction-points" style="--col: {{ $d->points > 0 ? '#e11d48' : '#22c55e' }};">
                                −{{ number_format($d->points) }}
                            </strong>
                        </span>
                    </button>
                @endforeach
                @if($score->deductions->every(fn ($d) => $d->points === 0))
                    <div class="perf-score-no-deductions">{{ __('performance.score.no_deductions') }}</div>
                @endif
                <div class="perf-score-total">
                    <span>{{ __('performance.score.total_deduction') }}</span>
                    <strong style="color: {{ $score->total_deduction > 0 ? '#e11d48' : '#22c55e' }};">
                        −{{ number_format($score->total_deduction) }}
                    </strong>
                </div>
            @endif
        </div>
    </div>
</div>
