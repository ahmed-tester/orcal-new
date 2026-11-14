@php
    $journal = $details['journal'];
    $branchLabel = $journal->branch?->name ?? __('finance.daily_journals.summary.no_branch');
@endphp

<p><strong>{{ $branchLabel }}</strong> · {{ $journal->company?->trade_name ?? __('finance.daily_journals.summary.no_company') }}</p>

@foreach($details['entries'] as $entry)
    <article class="journal-details-item">
        <header>
            <strong><bdi>{{ $entry->entry_number }}</bdi></strong>
            · {{ __('finance.daily_journals.types.'.($entry->is_detailed ? 'detailed' : 'summary')) }}
            @if(filled($entry->notes)) · {{ $entry->notes }} @endif
        </header>
        <dl class="journal-details-meta">
            <div><dt>{{ __('finance.daily_journals.fields.entry_date') }}</dt><dd><bdi>{{ $entry->entry_date?->format('Y-m-d') ?? '—' }}</bdi></dd></div>
            @foreach(['sales', 'purchases', 'net_amount'] as $key)
                <div><dt>{{ __('finance.daily_journals.fields.'.$key) }}</dt><dd><bdi>{{ number_format((float) $entry->{$key}, 2) }}</bdi> {{ __('finance.daily_journals.summary.currency') }}</dd></div>
            @endforeach
        </dl>

        @if($entry->is_detailed)
            <div class="journal-summary-scroll">
                <table class="journal-summary-table">
                    <thead><tr>
                        @foreach(['sales_cash', 'sales_network', 'sales_deferred', 'sales_other'] as $column)
                            <th scope="col">{{ __('finance.daily_journals.fields.'.$column) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        <tr>
                            @foreach(['sales_cash', 'sales_network', 'sales_deferred', 'sales_other'] as $column)
                                <td><bdi>{{ number_format((float) $entry->{$column}, 2) }}</bdi></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
                <table class="journal-summary-table" style="margin-top: .5rem">
                    <thead><tr>
                        @foreach(['purchases_cash', 'purchases_network', 'purchases_credit', 'purchases_debit', 'expenses_other'] as $column)
                            <th scope="col">{{ __('finance.daily_journals.fields.'.$column) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        <tr>
                            @foreach(['purchases_cash', 'purchases_network', 'purchases_credit', 'purchases_debit', 'expenses_other'] as $column)
                                <td><bdi>{{ number_format((float) ($entry->{$column} ?? 0), 2) }}</bdi></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </article>
@endforeach
