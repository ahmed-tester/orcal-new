@php
    $tkt = $this->ticketsOverview;
    $totals = $tkt['totals'];
    $ticketShare = fn (int $count) => $totals['all'] > 0 ? $count * 100 / $totals['all'] : null;
@endphp

<section class="perf-overview-group" aria-labelledby="perf-tkt-heading">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-tkt-heading">{{ __('performance.tickets.summary_title') }}</h2>
        @if($this->ticketsRegisterUrl)
            <x-filament::button tag="a" :href="$this->ticketsRegisterUrl" color="gray" size="sm">
                {{ __('performance.tickets.open_register') }}
            </x-filament::button>
        @endif
    </div>
    <p class="perf-overview-note">{{ __('performance.tickets.scope_note') }}</p>

    <div class="perf-tickets-grid">
        <x-performance.metric-card
            :title="__('performance.tickets.total')"
            :value="number_format($totals['all'])"
            :hint="__('performance.columns.view_statement')"
            accent="#5b5bf0"
            action="openStatement('tickets', 'all')"
        />
        <x-performance.metric-card
            :title="__('performance.tickets.open')"
            :value="number_format($totals['open'])"
            :rate="$ticketShare($totals['open'])"
            :rate-label="__('performance.tickets.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#0284c7"
            action="openStatement('tickets', 'open')"
        />
        <x-performance.metric-card
            :title="__('performance.tickets.in_progress')"
            :value="number_format($totals['in_progress'])"
            :rate="$ticketShare($totals['in_progress'])"
            :rate-label="__('performance.tickets.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#d97706"
            action="openStatement('tickets', 'in_progress')"
        />
        <x-performance.metric-card
            :title="__('performance.tickets.waiting_user_reply')"
            :value="number_format($totals['waiting_user_reply'])"
            :rate="$ticketShare($totals['waiting_user_reply'])"
            :rate-label="__('performance.tickets.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#7c3aed"
            action="openStatement('tickets', 'waiting_user_reply')"
        />
        <x-performance.metric-card
            :title="__('performance.tickets.resolved_closed')"
            :value="number_format($totals['resolved_closed'])"
            :rate="$ticketShare($totals['resolved_closed'])"
            :rate-label="__('performance.tickets.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#059669"
            action="openStatement('tickets', 'resolved_closed')"
        />
        <x-performance.metric-card
            :title="__('performance.tickets.urgent_high')"
            :value="number_format($totals['urgent_high'])"
            :rate="$ticketShare($totals['urgent_high'])"
            :rate-label="__('performance.tickets.registered_share')"
            :hint="__('performance.columns.view_statement')"
            accent="#e11d48"
            action="openStatement('tickets', 'urgent_high')"
        />
    </div>
</section>

<section class="perf-overview-group" aria-labelledby="perf-tkt-categories-heading" style="margin-top: 1.5rem;">
    <div class="perf-section-header">
        <h2 class="perf-section-title" id="perf-tkt-categories-heading">{{ __('performance.tickets.categories_title') }}</h2>
    </div>

    @if(! empty($tkt['categories']))
        <div class="perf-table-wrapper">
            <table class="perf-table">
                <thead>
                    <tr>
                        <th>{{ __('performance.tickets.category_name') }}</th>
                        <th style="text-align: center;">{{ __('performance.tickets.total_count') }}</th>
                        <th style="text-align: center;">{{ __('performance.tickets.active_count') }}</th>
                        <th style="text-align: center;">{{ __('performance.tickets.resolved_closed') }}</th>
                        <th style="text-align: center;">{{ __('performance.tickets.urgent_high') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tkt['categories'] as $cat)
                        <tr>
                            <td>
                                <x-filament::badge :color="$cat['color']" size="sm">
                                    {{ $cat['label'] }}
                                </x-filament::badge>
                            </td>
                            <td style="text-align: center; font-weight: 700;">{{ number_format($cat['total']) }}</td>
                            <td style="text-align: center; color: #0284c7; font-weight: 700;">{{ number_format($cat['active']) }}</td>
                            <td style="text-align: center; color: #059669; font-weight: 700;">{{ number_format($cat['resolved_closed']) }}</td>
                            <td style="text-align: center; color: #e11d48; font-weight: 700;">{{ number_format($cat['urgent_high']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="perf-empty-state-card">
            <div class="perf-empty-icon">
                <x-heroicon-o-ticket style="width: 28px; height: 28px;" />
            </div>
            <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
            <div class="perf-empty-subtitle">{{ __('performance.empty.hint') }}</div>
        </div>
    @endif
</section>
