@php
    $overview = $this->workforceOverview;
@endphp

@if($overview['attendance'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-attendance-heading">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <h2 class="perf-section-title" id="perf-attendance-heading">{{ __('performance.workforce.attendance_title') }}</h2>

            @if($overview['attendance_url'])
                <div>
                    <x-filament::button tag="a" :href="$overview['attendance_url']" color="gray" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                        {{ __('performance.workforce.open_attendance') }}
                    </x-filament::button>
                </div>
            @endif
        </div>

        <p class="perf-overview-note">{{ __('performance.workforce.attendance_note', ['month' => $overview['month']]) }}</p>

        @include('filament.pages.company-department-performance.workforce-metric-grid', [
            'group' => 'attendance',
            'cards' => $this->workforceCardGroups['attendance'],
            'rateTotal' => $overview['attendance']['recorded_days'],
            'labels' => [
                'present_days' => 'performance.overview.present_days',
                'late_days' => 'performance.overview.late_days',
                'absent_days' => 'performance.overview.absent_days',
            ],
            'hints' => [
                'present_days' => 'performance.workforce.metric_hints.present_days',
                'late_days' => 'performance.workforce.metric_hints.late_days',
                'absent_days' => 'performance.workforce.metric_hints.absent_days',
            ],
            'icons' => [
                'present_days' => 'heroicon-o-check-circle',
                'late_days' => 'heroicon-o-clock',
                'absent_days' => 'heroicon-o-x-circle',
            ],
            'accents' => [
                'present_days' => '#059669',
                'late_days' => '#b45309',
                'absent_days' => '#e11d48',
            ],
            'units' => [
                'present_days' => __('performance.workforce.days_unit'),
                'late_days' => __('performance.workforce.days_unit'),
                'absent_days' => __('performance.workforce.days_unit'),
            ],
        ])
    </section>
@endif

@if($overview['leaves'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-leaves-heading">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <h2 class="perf-section-title" id="perf-leaves-heading">{{ __('performance.workforce.leaves_title') }}</h2>

            @if($overview['leaves_url'])
                <div>
                    <x-filament::button tag="a" :href="$overview['leaves_url']" color="gray" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                        {{ __('performance.workforce.open_leaves') }}
                    </x-filament::button>
                </div>
            @endif
        </div>

        <p class="perf-overview-note">{{ __('performance.workforce.leaves_note', ['year' => $overview['year']]) }}</p>

        @include('filament.pages.company-department-performance.workforce-metric-grid', [
            'group' => 'leaves',
            'cards' => $this->workforceCardGroups['leaves'],
            'labels' => [
                'on_leave_today_count' => 'performance.overview.on_leave_today_count',
                'pending_requests_count' => 'performance.overview.pending_requests_count',
                'total_approved_days' => 'performance.overview.total_approved_days',
            ],
            'hints' => [
                'on_leave_today_count' => 'performance.workforce.metric_hints.on_leave_today_count',
                'pending_requests_count' => 'performance.workforce.metric_hints.pending_requests_count',
                'total_approved_days' => 'performance.workforce.metric_hints.total_approved_days',
            ],
            'icons' => [
                'on_leave_today_count' => 'heroicon-o-calendar-days',
                'pending_requests_count' => 'heroicon-o-clipboard-document-list',
                'total_approved_days' => 'heroicon-o-calendar',
            ],
            'accents' => [
                'on_leave_today_count' => '#0891b2',
                'pending_requests_count' => '#7c3aed',
                'total_approved_days' => '#059669',
            ],
            'units' => [
                'on_leave_today_count' => __('performance.workforce.employees_unit'),
                'pending_requests_count' => __('performance.workforce.requests_unit'),
                'total_approved_days' => __('performance.workforce.days_unit'),
            ],
        ])
    </section>
@endif
