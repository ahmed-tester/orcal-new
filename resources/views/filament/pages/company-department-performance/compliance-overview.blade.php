@php
    $comp = $this->complianceOverview;
@endphp

<p class="perf-overview-note">{{ __('performance.compliance.period_note') }}</p>

@if($comp['iqama'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-iqama-heading">
        <div class="perf-section-header">
            <h2 class="perf-section-title" id="perf-iqama-heading">{{ __('performance.compliance.iqama_title') }}</h2>
            <span class="perf-section-subtitle">
                {{ __('performance.compliance.total_iqamas') }} <strong>{{ number_format($comp['iqama']['total_tracked']) }}</strong>
            </span>
        </div>

        @include('filament.pages.company-department-performance.compliance-metric-grid', [
            'group' => 'iqama',
            'cards' => $this->complianceCardGroups['iqama'],
            'hints' => [
                'iqama_expired' => 'performance.compliance.metric_hints.iqama_expired',
                'iqama_within_3m' => 'performance.compliance.metric_hints.iqama_within_3m',
                'iqama_within_6m' => 'performance.compliance.metric_hints.iqama_within_6m',
                'iqama_within_9m' => 'performance.compliance.metric_hints.iqama_within_9m',
                'iqama_valid_over_9m' => 'performance.compliance.metric_hints.iqama_valid_over_9m',
            ],
            'icons' => [
                'iqama_expired' => 'heroicon-o-x-circle',
                'iqama_within_3m' => 'heroicon-o-clock',
                'iqama_within_6m' => 'heroicon-o-clock',
                'iqama_within_9m' => 'heroicon-o-clock',
                'iqama_valid_over_9m' => 'heroicon-o-check-circle',
            ],
            'accents' => [
                'iqama_expired' => '#e11d48',
                'iqama_within_3m' => '#ea580c',
                'iqama_within_6m' => '#d97706',
                'iqama_within_9m' => '#0284c7',
                'iqama_valid_over_9m' => '#059669',
            ],
            'units' => [
                'iqama_expired' => __('performance.compliance.employees_unit'),
                'iqama_within_3m' => __('performance.compliance.employees_unit'),
                'iqama_within_6m' => __('performance.compliance.employees_unit'),
                'iqama_within_9m' => __('performance.compliance.employees_unit'),
                'iqama_valid_over_9m' => __('performance.compliance.employees_unit'),
            ],
        ])
    </section>
@endif

@if($comp['work_permits'] !== null)
    <section class="perf-overview-group" aria-labelledby="perf-work-documents-heading">
        <h2 class="perf-section-title" id="perf-work-documents-heading">{{ __('performance.compliance.work_permits_title') }}</h2>

        @include('filament.pages.company-department-performance.compliance-metric-grid', [
            'group' => 'work_documents',
            'cards' => $this->complianceCardGroups['work_documents'],
            'hints' => [
                'work_permit_expired' => 'performance.compliance.metric_hints.work_permit_expired',
                'work_permit_expiring_soon' => 'performance.compliance.metric_hints.work_permit_expiring_soon',
                'work_permit_valid' => 'performance.compliance.metric_hints.work_permit_valid',
                'insurance_expired' => 'performance.compliance.metric_hints.insurance_expired',
                'insurance_expiring_soon' => 'performance.compliance.metric_hints.insurance_expiring_soon',
                'insurance_valid' => 'performance.compliance.metric_hints.insurance_valid',
            ],
            'icons' => [
                'work_permit_expired' => 'heroicon-o-x-circle',
                'work_permit_expiring_soon' => 'heroicon-o-clock',
                'work_permit_valid' => 'heroicon-o-check-circle',
                'insurance_expired' => 'heroicon-o-shield-exclamation',
                'insurance_expiring_soon' => 'heroicon-o-shield-check',
                'insurance_valid' => 'heroicon-o-shield-check',
            ],
            'accents' => [
                'work_permit_expired' => '#e11d48',
                'work_permit_expiring_soon' => '#ea580c',
                'work_permit_valid' => '#059669',
                'insurance_expired' => '#e11d48',
                'insurance_expiring_soon' => '#ea580c',
                'insurance_valid' => '#059669',
            ],
            'units' => [
                'work_permit_expired' => __('performance.compliance.employees_unit'),
                'work_permit_expiring_soon' => __('performance.compliance.employees_unit'),
                'work_permit_valid' => __('performance.compliance.employees_unit'),
                'insurance_expired' => __('performance.compliance.employees_unit'),
                'insurance_expiring_soon' => __('performance.compliance.employees_unit'),
                'insurance_valid' => __('performance.compliance.employees_unit'),
            ],
        ])
    </section>
@endif

@if($comp['licenses'] !== null)
    @php
        $licenseIcons = [
            'municipality' => 'heroicon-o-building-office-2',
            'civil_defense' => 'heroicon-o-shield-check',
            'commercial_reg' => 'heroicon-o-document-text',
        ];
        $licenseStatusPills = ['valid' => '#059669', 'expiring_soon' => '#d97706', 'expired' => '#e11d48', 'unspecified' => '#6b7490'];
    @endphp
    <section class="perf-overview-group" aria-labelledby="perf-licenses-heading">
        <h2 class="perf-section-title" id="perf-licenses-heading">{{ __('performance.compliance.licenses_title') }}</h2>
        <p class="perf-overview-note">{{ __('performance.compliance.licenses_scope_note') }}</p>
        <div class="perf-table-wrapper perf-compliance-table-scroll" role="region" aria-labelledby="perf-licenses-heading" tabindex="0">
            <table class="perf-table perf-compliance-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('performance.compliance.license_type') }}</th>
                        @foreach(array_keys($licenseStatusPills) as $status)
                            <th scope="col">{{ __('performance.compliance.'.$status) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($comp['licenses'] as $license => $counts)
                        <tr>
                            <th scope="row">
                                <span class="perf-row-label">
                                    <x-dynamic-component :component="$licenseIcons[$license] ?? 'heroicon-o-document-text'" style="width: 16px; height: 16px;" />
                                    {{ __('performance.compliance.'.$license) }}
                                </span>
                            </th>
                            @foreach($licenseStatusPills as $status => $pillColor)
                                <td>
                                    @if($counts[$status] > 0)
                                        <button
                                            type="button"
                                            class="perf-status-pill"
                                            style="--pill: {{ $pillColor }};"
                                            aria-haspopup="dialog"
                                            aria-label="{{ __('performance.compliance.open_license_statement', [
                                                'license' => __('performance.compliance.'.$license),
                                                'status' => __('performance.compliance.'.$status),
                                                'count' => number_format($counts[$status]),
                                            ]) }}"
                                            wire:click="openKashf('compliance', '{{ $license }}_{{ $status }}')"
                                        >
                                            <span class="dot" aria-hidden="true"></span>
                                            <bdi>{{ number_format($counts[$status]) }}</bdi>
                                            <x-heroicon-m-chevron-left class="perf-status-chevron" aria-hidden="true" />
                                        </button>
                                    @else
                                        <span class="perf-status-pill is-zero">{{ number_format($counts[$status]) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @php
        $subscriptionIcons = ['qiwa' => 'heroicon-o-briefcase', 'absher' => 'heroicon-o-identification', 'spl' => 'heroicon-o-map-pin'];
        $subscriptionStatusPills = ['active' => '#059669', 'expired' => '#e11d48', 'unspecified' => '#6b7490'];
    @endphp
    <section class="perf-overview-group" aria-labelledby="perf-subs-heading">
        <h2 class="perf-section-title" id="perf-subs-heading">{{ __('performance.compliance.subscriptions_title') }}</h2>
        @if(collect($comp['subscriptions'])->contains(fn ($counts) => $counts['active'] + $counts['expired'] > 0))
            <div class="perf-table-wrapper perf-compliance-table-scroll" role="region" aria-labelledby="perf-subs-heading" tabindex="0">
                <table class="perf-table perf-compliance-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('performance.compliance.platform') }}</th>
                            @foreach(array_keys($subscriptionStatusPills) as $status)
                                <th scope="col">{{ __('performance.compliance.'.$status) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comp['subscriptions'] as $platform => $counts)
                            <tr>
                                <th scope="row">
                                    <span class="perf-row-label">
                                        <x-dynamic-component :component="$subscriptionIcons[$platform] ?? 'heroicon-o-globe-alt'" style="width: 16px; height: 16px;" />
                                        {{ __('performance.compliance.'.$platform) }}
                                    </span>
                                </th>
                                @foreach($subscriptionStatusPills as $status => $pillColor)
                                    <td>
                                        <span
                                            class="perf-status-pill {{ $counts[$status] > 0 ? '' : 'is-zero' }}"
                                            @if($counts[$status] > 0) style="--pill: {{ $pillColor }};" @endif
                                        >
                                            @if($counts[$status] > 0)
                                                <span class="dot" aria-hidden="true"></span>
                                            @endif
                                            <bdi>{{ number_format($counts[$status]) }}</bdi>
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="perf-overview-note">{{ __('performance.compliance.subscriptions_unavailable') }}</p>
        @endif
    </section>
@endif
