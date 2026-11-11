<p style="margin-bottom: 0.75rem;">
    <strong>{{ $details['company']?->trade_name ?? __('violations.summary.no_company') }}</strong>
    · {{ __('violations.summary.commercial_registration') }}: <bdi>{{ $details['company']?->commercial_registration_number ?? '—' }}</bdi>
</p>

@foreach($details['violations'] as $violation)
    <article class="violation-details-item">
        <header style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <strong><bdi>{{ $violation->violation_number ?? "#{$violation->id}" }}</bdi></strong>
            @if(filled($violation->external_violation_number))
                <span style="color: #64748b;">(<bdi>{{ $violation->external_violation_number }}</bdi>)</span>
            @endif
            · <span style="font-weight: 600;">{{ $violation->issuing_authority }}</span>
            · <span class="badge">{{ $violation->payment_status?->getLabel() }}</span>
            · <span class="badge" style="color: #2563eb;">{{ $violation->stage?->getLabel() }}</span>
            @if(\App\Services\ViolationSummaryService::isOverdue($violation))
                · <span style="color: #dc2626; font-weight: 700;">{{ __('violations.summary.overdue_sum') }}</span>
            @endif
        </header>

        <dl class="violation-details-meta">
            <div>
                <dt>{{ __('violations.violations.fields.violation_date') }}</dt>
                <dd><bdi>{{ $violation->violation_date?->format('Y-m-d') ?? '—' }}</bdi></dd>
            </div>
            <div>
                <dt>{{ __('violations.violations.fields.category') }}</dt>
                <dd>{{ $violation->category?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('violations.violations.fields.type') }}</dt>
                <dd>{{ $violation->type?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('violations.violations.fields.violation_amount') }}</dt>
                <dd><bdi>{{ number_format((float) $violation->violation_amount, 2) }}</bdi> {{ __('violations.summary.currency') }}</dd>
            </div>
            <div>
                <dt>{{ __('violations.violations.fields.paid_amount') }}</dt>
                <dd style="color: #16a34a;"><bdi>{{ number_format((float) $violation->paid_amount, 2) }}</bdi> {{ __('violations.summary.currency') }}</dd>
            </div>
            <div>
                <dt>{{ __('violations.violations.fields.remaining_amount') }}</dt>
                <dd style="color: {{ (float) $violation->remaining_amount > 0 ? '#dc2626' : '#16a34a' }};"><bdi>{{ number_format((float) $violation->remaining_amount, 2) }}</bdi> {{ __('violations.summary.currency') }}</dd>
            </div>
        </dl>

        @if(filled($violation->description))
            <p style="font-size: 0.85rem; color: #475569; margin-block: 0.5rem;">{{ $violation->description }}</p>
        @endif

        @if($details['showTransactions'])
            <div style="overflow-x: auto; margin-top: 0.75rem;">
                <table class="violation-summary-table" style="font-size: 0.825rem;">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('violations.transactions.fields.voucher_number') }}</th>
                            <th scope="col">{{ __('violations.transactions.fields.transaction_date') }}</th>
                            <th scope="col">{{ __('violations.transactions.fields.transaction_type') }}</th>
                            <th scope="col">{{ __('violations.transactions.fields.amount') }}</th>
                            <th scope="col">{{ __('violations.transactions.fields.description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($violation->transactions as $transaction)
                            <tr>
                                <td><bdi>{{ $transaction->voucher_number }}</bdi></td>
                                <td><bdi>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td>{{ $transaction->transaction_type?->getLabel() }}</td>
                                <td><bdi>{{ number_format((float) $transaction->amount, 2) }}</bdi></td>
                                <td>{{ $transaction->description ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">{{ __('violations.summary.no_transactions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($interactive && (float) $violation->remaining_amount > 0 && $canPay)
            <div style="margin-top: 0.75rem;">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\ViolationTransactions\ViolationTransactionResource::getUrl('create', ['violation_id' => $violation->id])"
                    size="sm"
                    icon="heroicon-o-plus"
                >
                    {{ __('violations.summary.pay_violation') }}
                </x-filament::button>
            </div>
        @endif
    </article>
@endforeach

