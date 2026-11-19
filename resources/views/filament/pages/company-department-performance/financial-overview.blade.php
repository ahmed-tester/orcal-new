@php
    $overview = $this->financialOverview;
    $treasuryUrl = $this->treasurySummaryUrl;
@endphp

@if($treasury = $overview['treasury'])
<section class="perf-overview-group" aria-labelledby="perf-financial-heading">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <h2 class="perf-section-title" id="perf-financial-heading">{{ __('performance.financial.treasury_title') }}</h2>

        @if($treasuryUrl)
            <div>
                <x-filament::button tag="a" :href="$treasuryUrl" color="gray" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                    {{ __('performance.financial.open_treasury') }}
                </x-filament::button>
            </div>
        @endif
    </div>

    @if($treasury['branch_restricted'])
        <div class="perf-card" style="border-inline-start: 4px solid var(--c, #f59e0b); background: color-mix(in srgb, #f59e0b 8%, var(--card)); padding: 1.25rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <div style="color: #d97706; margin-top: 2px;">
                    <x-heroicon-o-exclamation-triangle style="width: 22px; height: 22px;" />
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <strong style="color: var(--tx); font-size: 0.95rem;">{{ __('performance.financial.branch_restricted_title') }}</strong>
                    <span class="perf-overview-note" style="color: var(--mu);">{{ __('performance.financial.branch_restricted_desc') }}</span>
                </div>
            </div>
        </div>
    @else
        @include('filament.pages.company-department-performance.financial-metric-grid', [
            'group' => 'treasury',
            'cards' => $this->financialCardGroups['treasury'],
            'labels' => [
                'current_balance' => 'performance.financial.current_balance',
                'incoming' => 'performance.financial.total_incoming',
                'outgoing' => 'performance.financial.total_outgoing',
                'net_flow' => 'performance.financial.net_flow',
            ],
            'hints' => [
                'current_balance' => 'performance.financial.current_balance_hint',
                'incoming' => 'performance.financial.total_incoming_hint',
                'outgoing' => 'performance.financial.total_outgoing_hint',
                'net_flow' => 'performance.financial.net_flow_hint',
            ],
            'icons' => [
                'incoming' => 'heroicon-o-arrow-down-left',
                'outgoing' => 'heroicon-o-arrow-up-right',
                'net_flow' => 'heroicon-o-arrows-right-left',
            ],
            'accents' => [
                'incoming' => '#059669',
                'outgoing' => '#e11d48',
                'net_flow' => $treasury['net_flow'] >= 0 ? '#059669' : '#e11d48',
            ],
        ])

        <p class="perf-overview-note">{{ __('performance.financial.period_note') }}</p>
    @endif
</section>
@endif

@foreach($overview['groups'] as $group => $cards)
    <section class="perf-overview-group" aria-labelledby="perf-financial-{{ $group }}">
        <h2 class="perf-section-title" id="perf-financial-{{ $group }}">{{ __('performance.financial.groups.'.$group) }}</h2>
        <p class="perf-overview-note">{{ __('performance.financial.group_notes.'.$group) }}</p>

        @include('filament.pages.company-department-performance.financial-metric-grid')
    </section>
@endforeach
