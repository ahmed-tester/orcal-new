@php
    $overview = $this->workforceOverview;
@endphp

<p class="perf-overview-note">{{ __('performance.overview.period_note') }}</p>

@if($overview['attendance'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-attendance-heading">
        <div class="perf-section-header">
            <h2 class="perf-section-title" id="perf-attendance-heading">{{ __('performance.overview.attendance_month', ['month' => $overview['month']]) }}</h2>
            @if($overview['attendance_url'])
                <x-filament::button tag="a" :href="$overview['attendance_url']" color="gray">
                    {{ __('performance.overview.attendance_details') }}
                </x-filament::button>
            @endif
        </div>

        <div class="perf-kpi-grid">
            <x-performance.metric-card :title="__('attendance.summary.stats.present_days')" :value="number_format($overview['attendance']['present_days'])" accent="#059669" />
            <x-performance.metric-card :title="__('attendance.summary.stats.late_days')" :value="number_format($overview['attendance']['late_days'])" accent="#ea580c" />
            <x-performance.metric-card :title="__('attendance.summary.stats.absent_days')" :value="number_format($overview['attendance']['absent_days'])" accent="#e11d48" />
        </div>
    </section>
@endif

@if($overview['leaves'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-leaves-heading">
        <div class="perf-section-header">
            <h2 class="perf-section-title" id="perf-leaves-heading">{{ __('performance.overview.leaves_year', ['year' => $overview['year']]) }}</h2>
            @if($overview['leaves_url'])
                <x-filament::button tag="a" :href="$overview['leaves_url']" color="gray">
                    {{ __('performance.overview.leaves_details') }}
                </x-filament::button>
            @endif
        </div>

        <div class="perf-kpi-grid">
            <x-performance.metric-card :title="__('leaves.summary.cards.on_leave_today')" :value="number_format($overview['leaves']['on_leave_today_count'])" accent="#0891b2" />
            <x-performance.metric-card :title="__('leaves.summary.cards.pending_requests')" :value="number_format($overview['leaves']['pending_requests_count'])" accent="#ea580c" />
            <x-performance.metric-card :title="__('performance.overview.approved_leave_days')" :value="number_format($overview['leaves']['total_approved_days'])" accent="#059669" />
        </div>
    </section>
@endif
