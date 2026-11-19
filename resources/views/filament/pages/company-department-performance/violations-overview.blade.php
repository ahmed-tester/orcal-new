@php
    $vio = $this->violationsOverview;
    $totals = $vio['totals'];
    $violationShare = fn (int $count) => $totals['count'] > 0 ? $count * 100 / $totals['count'] : null;
    $amountShare = fn (float $amount) => $totals['amount'] > 0 && $amount >= 0 && $amount <= $totals['amount']
        ? $amount * 100 / $totals['amount']
        : null;
    $currency = __('performance.violations.currency');
@endphp

<section class="perf-overview-group perf-violations-overview" aria-labelledby="perf-vio-heading">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-vio-heading">{{ __('performance.violations.summary_title') }}</h2>
        <x-filament::button tag="a" :href="$this->violationsRegisterUrl" color="gray" size="sm">
            {{ __('performance.violations.open_register') }}
        </x-filament::button>
    </div>
    <p class="perf-overview-note">{{ __('performance.violations.scope_note') }}</p>

    <div class="perf-violation-status-grid">
        <x-performance.metric-card
            :title="__('performance.violations.objection')"
            :value="number_format($vio['stages']['objection']['count'])"
            :rate="$violationShare($vio['stages']['objection']['count'])"
            :rate-label="__('performance.violations.registered_share')"
            :hint="number_format($vio['stages']['objection']['amount'], 2) . ' ' . $currency"
            accent="#ea580c"
            action="openKashf('violations', 'objection')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.execution')"
            :value="number_format($vio['stages']['execution']['count'])"
            :rate="$violationShare($vio['stages']['execution']['count'])"
            :rate-label="__('performance.violations.registered_share')"
            :hint="number_format($vio['stages']['execution']['amount'], 2) . ' ' . $currency"
            accent="#e11d48"
            action="openKashf('violations', 'execution')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.grace')"
            :value="number_format($vio['stages']['grace']['count'])"
            :rate="$violationShare($vio['stages']['grace']['count'])"
            :rate-label="__('performance.violations.registered_share')"
            :hint="number_format($vio['stages']['grace']['amount'], 2) . ' ' . $currency"
            accent="#0284c7"
            action="openKashf('violations', 'grace')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.paid')"
            :value="number_format($totals['fully_paid_count'])"
            :rate="$violationShare($totals['fully_paid_count'])"
            :rate-label="__('performance.violations.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#059669"
            action="openKashf('violations', 'paid')"
        />
    </div>

    <div class="perf-violation-totals-grid">
        <x-performance.metric-card
            :title="__('performance.violations.total_violations')"
            :value="number_format($totals['count'])"
            :hint="__('performance.columns.view_statement')"
            accent="#5b5bf0"
            action="openKashf('violations', 'all')"
        >
            <div class="perf-violation-total-amount">
                <span>{{ __('performance.violations.total_amount') }}</span>
                <bdi>{{ number_format($totals['amount'], 2) }} {{ $currency }}</bdi>
            </div>
        </x-performance.metric-card>
        <x-performance.metric-card
            :title="__('performance.violations.total_paid')"
            :value="number_format($totals['paid'], 2)"
            :unit="$currency"
            :rate="$amountShare($totals['paid'])"
            :rate-label="__('performance.violations.amount_share')"
            :hint="__('performance.violations.payment_count', ['count' => number_format($totals['payment_count'])])"
            accent="#059669"
            action="openKashf('violations', 'payments')"
        />
        <x-performance.metric-card
            :title="__('performance.violations.total_remaining')"
            :value="number_format($totals['remaining'], 2)"
            :unit="$currency"
            :rate="$amountShare($totals['remaining'])"
            :rate-label="__('performance.violations.amount_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#e11d48"
            action="openKashf('violations', 'unpaid')"
        />
    </div>
</section>

<section class="perf-overview-group" aria-labelledby="perf-authorities-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-authorities-heading">{{ __('performance.violations.authorities_title') }}</h2>
    </div>

    @if($vio['authorities']->isNotEmpty())
        <div class="perf-table-wrapper">
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
                            <td><strong>{{ filled($auth->issuing_authority) ? $auth->issuing_authority : __('performance.violations.unknown_authority') }}</strong></td>
                            <td style="text-align: center;">
                                <x-filament::badge color="gray" size="sm">{{ number_format($auth->count) }}</x-filament::badge>
                            </td>
                            <td style="text-align: left;"><bdi>{{ number_format($auth->total_amount, 2) }} {{ $currency }}</bdi></td>
                            <td style="text-align: left; color: #059669;"><bdi>{{ number_format($auth->paid_amount, 2) }} {{ $currency }}</bdi></td>
                            <td style="text-align: left; color: #e11d48;"><bdi>{{ number_format($auth->remaining_amount, 2) }} {{ $currency }}</bdi></td>
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
