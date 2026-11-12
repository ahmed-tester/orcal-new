<p><strong>{{ $details['debtor']->beneficiary_name }}</strong> · {{ $details['debtor']->company?->trade_name ?? __('debts.summary.no_company') }}</p>

@foreach($details['debts'] as $debt)
    <article class="debt-details-item">
        <header>
            <strong><bdi>{{ $debt->debt_number }}</bdi></strong>
            · {{ __('debts.badges.'.(\App\Services\DebtSummaryService::isOverdue($debt) ? 'overdue' : $debt->status)) }}
            @if(filled($debt->debt_reason)) · {{ $debt->debt_reason }} @endif
        </header>
        <dl class="debt-details-meta">
            @foreach(['debt_date', 'due_date'] as $key)
                <div><dt>{{ __('debts.fields.'.$key) }}</dt><dd><bdi>{{ $debt->{$key}?->format('Y-m-d') ?? '—' }}</bdi></dd></div>
            @endforeach
            @foreach(['total_amount', 'paid_amount', 'remaining_amount'] as $key)
                <div><dt>{{ __('debts.fields.'.$key) }}</dt><dd><bdi>{{ number_format((float) $debt->{$key}, 2) }}</bdi> {{ __('debts.summary.currency') }}</dd></div>
            @endforeach
        </dl>

        @if($details['showCollections'])
            <div class="debt-summary-scroll">
                <table class="debt-summary-table">
                    <thead><tr>
                        @foreach(['voucher_number', 'collection_date', 'amount', 'payment_method', 'status'] as $column)
                            <th scope="col">{{ __('debts.collections.fields.'.$column) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse($debt->collections as $collection)
                            <tr>
                                <td><bdi>{{ $collection->voucher_number }}</bdi></td>
                                <td><bdi>{{ $collection->collection_date?->format('Y-m-d') ?? '—' }}</bdi></td>
                                <td><bdi>{{ number_format((float) $collection->amount, 2) }}</bdi></td>
                                <td>{{ $collection->payment_method ? __('debts.collections.payment_methods.'.$collection->payment_method) : '—' }}</td>
                                <td>{{ $collection->status->getLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">{{ __('debts.summary.no_collections') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($interactive && $debt->remaining_amount > 0 && $canCollect)
            <div style="margin-top: .75rem">
                <x-filament::button tag="a" :href="\App\Filament\Resources\DebtCollections\DebtCollectionResource::getUrl('create', ['debt_id' => $debt->id])" size="sm" icon="heroicon-o-plus">
                    {{ __('debts.summary.collect') }}
                </x-filament::button>
            </div>
        @endif
    </article>
@endforeach
