@php
    $comp = $this->complianceOverview;
@endphp

<p class="perf-overview-note">{{ __('performance.compliance.period_note') }}</p>

{{-- 1. Iqama & Employment Documents Expiries --}}
<section class="perf-overview-group" aria-labelledby="perf-iqama-heading">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-iqama-heading">{{ __('performance.compliance.iqama_title') }}</h2>
        <span class="perf-section-subtitle">
            {{ __('performance.compliance.total_iqamas') }}: <strong>{{ number_format($comp['iqama']['total_tracked']) }}</strong>
        </span>
    </div>

    <div class="perf-kpi-grid">
        <x-performance.metric-card
            :title="__('performance.compliance.iqama_expired')"
            :value="number_format($comp['iqama']['expired'])"
            accent="#e11d48"
            clickable
            wire:click="openKashf('compliance', 'iqama_expired')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.iqama_within_3m')"
            :value="number_format($comp['iqama']['within_3m'])"
            accent="#ea580c"
            clickable
            wire:click="openKashf('compliance', 'iqama_within_3m')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.iqama_within_6m')"
            :value="number_format($comp['iqama']['within_6m'])"
            accent="#d97706"
            clickable
            wire:click="openKashf('compliance', 'iqama_within_6m')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.iqama_within_9m')"
            :value="number_format($comp['iqama']['within_9m'])"
            accent="#0284c7"
            clickable
            wire:click="openKashf('compliance', 'iqama_within_9m')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.iqama_valid_over_9m')"
            :value="number_format($comp['iqama']['valid_over_9m'])"
            accent="#059669"
            clickable
            wire:click="openKashf('compliance', 'iqama_valid_over_9m')"
        />
    </div>

    <div class="perf-kpi-grid" style="margin-top: 0.75rem;">
        <x-performance.metric-card
            :title="__('performance.compliance.work_permit_expired')"
            :value="number_format($comp['work_permits']['expired'])"
            accent="#e11d48"
            clickable
            wire:click="openKashf('compliance', 'work_permit_expired')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.work_permit_expiring_soon')"
            :value="number_format($comp['work_permits']['expiring_soon'])"
            accent="#ea580c"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.insurance_expired')"
            :value="number_format($comp['insurance']['expired'])"
            accent="#e11d48"
            clickable
            wire:click="openKashf('compliance', 'insurance_expired')"
        />
        <x-performance.metric-card
            :title="__('performance.compliance.insurance_expiring_soon')"
            :value="number_format($comp['insurance']['expiring_soon'])"
            accent="#ea580c"
        />
    </div>
</section>

{{-- 2. Official Company Licenses --}}
<section class="perf-overview-group" aria-labelledby="perf-licenses-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-licenses-heading">{{ __('performance.compliance.licenses_title') }}</h2>
    </div>

    <div class="perf-kpi-grid">
        {{-- Municipality License --}}
        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.municipality') }}</div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                <x-filament::badge color="success" size="sm">
                    {{ __('performance.compliance.valid') }}: {{ $comp['licenses']['municipality']['valid'] }}
                </x-filament::badge>
                @if($comp['licenses']['municipality']['expiring_soon'] > 0)
                    <x-filament::badge color="warning" size="sm">
                        {{ __('performance.compliance.expiring_soon') }}: {{ $comp['licenses']['municipality']['expiring_soon'] }}
                    </x-filament::badge>
                @endif
                @if($comp['licenses']['municipality']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">
                        {{ __('performance.compliance.expired') }}: {{ $comp['licenses']['municipality']['expired'] }}
                    </x-filament::badge>
                @endif
            </div>
        </div>

        {{-- Civil Defense --}}
        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.civil_defense') }}</div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                <x-filament::badge color="success" size="sm">
                    {{ __('performance.compliance.valid') }}: {{ $comp['licenses']['civil_defense']['valid'] }}
                </x-filament::badge>
                @if($comp['licenses']['civil_defense']['expiring_soon'] > 0)
                    <x-filament::badge color="warning" size="sm">
                        {{ __('performance.compliance.expiring_soon') }}: {{ $comp['licenses']['civil_defense']['expiring_soon'] }}
                    </x-filament::badge>
                @endif
                @if($comp['licenses']['civil_defense']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">
                        {{ __('performance.compliance.expired') }}: {{ $comp['licenses']['civil_defense']['expired'] }}
                    </x-filament::badge>
                @endif
            </div>
        </div>

        {{-- Commercial Registration --}}
        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.commercial_reg') }}</div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                <x-filament::badge color="success" size="sm">
                    {{ __('performance.compliance.valid') }}: {{ $comp['licenses']['commercial_reg']['valid'] }}
                </x-filament::badge>
                @if($comp['licenses']['commercial_reg']['expiring_soon'] > 0)
                    <x-filament::badge color="warning" size="sm">
                        {{ __('performance.compliance.expiring_soon') }}: {{ $comp['licenses']['commercial_reg']['expiring_soon'] }}
                    </x-filament::badge>
                @endif
                @if($comp['licenses']['commercial_reg']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">
                        {{ __('performance.compliance.expired') }}: {{ $comp['licenses']['commercial_reg']['expired'] }}
                    </x-filament::badge>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- 3. Government Platform Subscriptions --}}
<section class="perf-overview-group" aria-labelledby="perf-subs-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-subs-heading">{{ __('performance.compliance.subscriptions_title') }}</h2>
    </div>

    <div class="perf-kpi-grid">
        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.qiwa') }}</div>
            <div style="margin-top: 0.5rem;">
                @if($comp['subscriptions']['qiwa']['active'] > 0)
                    <x-filament::badge color="success" size="sm">{{ __('performance.compliance.active') }} ({{ $comp['subscriptions']['qiwa']['active'] }})</x-filament::badge>
                @elseif($comp['subscriptions']['qiwa']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">{{ __('performance.compliance.expired') }} ({{ $comp['subscriptions']['qiwa']['expired'] }})</x-filament::badge>
                @else
                    <span style="color: var(--mu, #6b7490); font-size: 0.875rem;">{{ __('performance.compliance.unspecified') }}</span>
                @endif
            </div>
        </div>

        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.absher') }}</div>
            <div style="margin-top: 0.5rem;">
                @if($comp['subscriptions']['absher']['active'] > 0)
                    <x-filament::badge color="success" size="sm">{{ __('performance.compliance.active') }} ({{ $comp['subscriptions']['absher']['active'] }})</x-filament::badge>
                @elseif($comp['subscriptions']['absher']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">{{ __('performance.compliance.expired') }} ({{ $comp['subscriptions']['absher']['expired'] }})</x-filament::badge>
                @else
                    <span style="color: var(--mu, #6b7490); font-size: 0.875rem;">{{ __('performance.compliance.unspecified') }}</span>
                @endif
            </div>
        </div>

        <div class="perf-kpi-card">
            <div class="perf-kpi-title">{{ __('performance.compliance.spl') }}</div>
            <div style="margin-top: 0.5rem;">
                @if($comp['subscriptions']['spl']['active'] > 0)
                    <x-filament::badge color="success" size="sm">{{ __('performance.compliance.active') }} ({{ $comp['subscriptions']['spl']['active'] }})</x-filament::badge>
                @elseif($comp['subscriptions']['spl']['expired'] > 0)
                    <x-filament::badge color="danger" size="sm">{{ __('performance.compliance.expired') }} ({{ $comp['subscriptions']['spl']['expired'] }})</x-filament::badge>
                @else
                    <span style="color: var(--mu, #6b7490); font-size: 0.875rem;">{{ __('performance.compliance.unspecified') }}</span>
                @endif
            </div>
        </div>
    </div>
</section>
