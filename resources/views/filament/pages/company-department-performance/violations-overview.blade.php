@php
    $vio = $this->violationsOverview;
@endphp

{{-- 1. Violations Summary & Regulatory Stages --}}
<section class="perf-overview-group" aria-labelledby="perf-vio-heading">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-vio-heading">{{ __('performance.violations.summary_title') }}</h2>
        <span class="perf-section-subtitle">
            {{ __('performance.violations.total_violations') }}: <strong>{{ number_format($vio['totals']['count']) }}</strong>
            ({{ number_format($vio['totals']['amount'], 2) }} ر.س)
        </span>
    </div>

    <div class="perf-kpi-grid">
        <x-performance.metric-card
            :title="__('performance.violations.objection')"
            :value="number_format($vio['stages']['objection']['count'])"
            :hint="number_format($vio['stages']['objection']['amount'], 2) . ' ر.س'"
            accent="#ea580c"
            clickable
            wire:click="openKashf('violations', 'objection')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.execution')"
            :value="number_format($vio['stages']['execution']['count'])"
            :hint="number_format($vio['stages']['execution']['amount'], 2) . ' ر.س'"
            accent="#e11d48"
            clickable
            wire:click="openKashf('violations', 'execution')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.grace')"
            :value="number_format($vio['stages']['grace']['count'])"
            :hint="number_format($vio['stages']['grace']['amount'], 2) . ' ر.س'"
            accent="#0284c7"
            clickable
            wire:click="openKashf('violations', 'grace')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.total_paid')"
            :value="number_format($vio['totals']['paid'], 2) . ' ر.س'"
            :hint="number_format($vio['stages']['paid']['count']) . ' مخالفة'"
            accent="#059669"
            clickable
            wire:click="openKashf('violations', 'paid')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.total_remaining')"
            :value="number_format($vio['totals']['remaining'], 2) . ' ر.س'"
            accent="#e11d48"
            clickable
            wire:click="openKashf('violations', 'unpaid')"
        />
    </div>
</section>

{{-- 2. Violations Distribution & Efaa Alignment --}}
<section class="perf-overview-group" aria-labelledby="perf-authorities-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-authorities-heading">{{ __('performance.violations.authorities_title') }}</h2>
    </div>

    @if($vio['authorities']->isNotEmpty())
        <div class="perf-table-wrapper" style="background: var(--card); border: 1px solid var(--bd); border-radius: 0.75rem; overflow: hidden;">
            <table class="perf-table">
                <thead>
                    <tr>
                        <th>{{ __('performance.violations.authority_name') }}</th>
                        <th style="text-align: center;">{{ __('performance.violations.authority_count') }}</th>
                        <th style="text-align: left;">{{ __('performance.violations.total_amount') }}</th>
                        <th style="text-align: left;">{{ __('performance.violations.total_paid') }}</th>
                        <th style="text-align: left;">{{ __('performance.violations.total_remaining') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vio['authorities'] as $auth)
                        <tr>
                            <td><strong>{{ $auth->issuing_authority }}</strong></td>
                            <td style="text-align: center;">
                                <x-filament::badge color="gray" size="sm">{{ number_format($auth->count) }}</x-filament::badge>
                            </td>
                            <td style="text-align: left;"><bdi>{{ number_format($auth->total_amount, 2) }} ر.س</bdi></td>
                            <td style="text-align: left; color: #059669;"><bdi>{{ number_format($auth->paid_amount, 2) }} ر.س</bdi></td>
                            <td style="text-align: left; color: #e11d48;"><bdi>{{ number_format($auth->remaining_amount, 2) }} ر.س</bdi></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="perf-empty-state-card" style="padding: 1.5rem; text-align: center;">
            <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
        </div>
    @endif
</section>

{{-- 3. Vehicles, Authorizations & Transport Violations --}}
<section class="perf-overview-group" aria-labelledby="perf-vehicles-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-vehicles-heading">{{ __('performance.violations.vehicles_title') }}</h2>
    </div>

    <div class="perf-kpi-grid">
        <x-performance.metric-card
            :title="__('performance.violations.total_vehicles')"
            :value="number_format($vio['vehicles']['total_vehicles'])"
            accent="#4f46e5"
        />
        <x-performance.metric-card
            :title="__('performance.violations.authorized_vehicles')"
            :value="number_format($vio['vehicles']['authorized_vehicles'])"
            accent="#059669"
        />
        <x-performance.metric-card
            :title="__('performance.violations.unauthorized_vehicles')"
            :value="number_format($vio['vehicles']['unauthorized_vehicles'])"
            accent="#ea580c"
        />
        <x-performance.metric-card
            :title="__('performance.violations.traffic_violations')"
            :value="number_format($vio['vehicles']['traffic_violations_count'])"
            accent="#e11d48"
        />
        <x-performance.metric-card
            :title="__('performance.violations.transport_violations')"
            :value="number_format($vio['vehicles']['transport_violations_count'])"
            accent="#d97706"
        />
    </div>
    <p class="perf-overview-note" style="margin-top: 0.5rem;">{{ __('performance.violations.vehicles_note') }}</p>
</section>
