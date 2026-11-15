@if($type === 'current_balance')
    <div class="perf-table-wrapper">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ __('performance.kashf.company') }}</th>
                    <th>{{ __('performance.financial.kashf_last_transaction_number') }}</th>
                    <th>{{ __('performance.financial.kashf_voucher_number') }}</th>
                    <th>{{ __('performance.financial.kashf_last_date') }}</th>
                    <th>{{ __('performance.financial.kashf_executor') }}</th>
                    <th style="text-align: end;">{{ __('performance.financial.kashf_current_balance_column') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $tx)
                    <tr>
                        <td><strong>{{ $tx->company?->trade_name ?? '—' }}</strong></td>
                        <td><strong class="perf-tx-code"><bdi>{{ $tx->transaction_number }}</bdi></strong></td>
                        <td><bdi>{{ $tx->voucher_number ?? '—' }}</bdi></td>
                        <td><bdi>{{ $tx->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                        <td><span style="color: var(--mu, #6b7490);">{{ $tx->executor?->user?->name ?? '—' }}</span></td>
                        <td style="text-align: end; font-weight: 700;">
                            <bdi>{{ number_format((float) $tx->balance_after, 2) }} {{ __('performance.financial.currency') }}</bdi>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                <div class="perf-empty-icon">
                                    <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                </div>
                                <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="perf-table-wrapper">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ __('performance.financial.kashf_transaction_number') }}</th>
                    <th>{{ __('performance.financial.kashf_voucher_number') }}</th>
                    <th>{{ __('performance.kashf.company') }}</th>
                    <th>{{ __('performance.financial.kashf_date') }}</th>
                    <th>{{ __('performance.financial.kashf_beneficiary') }}</th>
                    <th>{{ __('performance.financial.kashf_description') }}</th>
                    <th style="text-align: center;">{{ __('performance.financial.kashf_type') }}</th>
                    <th style="text-align: end;">{{ __('performance.financial.kashf_amount') }}</th>
                    <th style="text-align: end;">{{ __('performance.financial.kashf_balance_after') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $tx)
                    @php
                        $isIncoming = ($tx->transaction_type instanceof \App\Enums\TreasuryTransactionType)
                            ? $tx->transaction_type === \App\Enums\TreasuryTransactionType::INCOMING
                            : $tx->transaction_type === 'incoming';
                    @endphp
                    <tr>
                        <td><strong class="perf-tx-code"><bdi>{{ $tx->transaction_number }}</bdi></strong></td>
                        <td><bdi>{{ $tx->voucher_number ?? '—' }}</bdi></td>
                        <td>{{ $tx->company?->trade_name ?? '—' }}</td>
                        <td><bdi>{{ $tx->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                        <td>{{ $tx->recipient_or_payer ?? '—' }}</td>
                        <td style="max-width: 220px; white-space: normal;">
                            <span style="font-size: 0.8rem; color: var(--tx);">{{ $tx->description ?? '—' }}</span>
                        </td>
                        <td style="text-align: center;">
                            <x-filament::badge :color="$isIncoming ? 'success' : 'danger'" size="xs">
                                {{ $isIncoming ? __('performance.financial.type_incoming') : __('performance.financial.type_outgoing') }}
                            </x-filament::badge>
                        </td>
                        <td style="text-align: end; font-weight: 700; color: {{ $isIncoming ? '#059669' : '#e11d48' }};">
                            <bdi>{{ number_format((float) $tx->amount, 2) }} {{ __('performance.financial.currency') }}</bdi>
                        </td>
                        <td style="text-align: end; font-weight: 600;">
                            <bdi>{{ number_format((float) $tx->balance_after, 2) }} {{ __('performance.financial.currency') }}</bdi>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                                <div class="perf-empty-icon">
                                    <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                                </div>
                                <div class="perf-empty-title">{{ __('performance.kashf.empty') }}</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
