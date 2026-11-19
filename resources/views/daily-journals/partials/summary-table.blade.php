<table class="journal-summary-table">
    <thead>
        <tr>
            @foreach(['branch', 'company', 'entries_count', ...\App\Services\DailyJournalSummaryService::TOTALS] as $column)
                <th scope="col">{{ __('finance.daily_journals.summary.'.$column) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr @if($interactive) wire:key="journal-{{ $row->id }}" @endif>
                <td>
                    @if($interactive)
                        <x-filament::link tag="button" wire:click="openDetails({{ $row->id }})">
                            {{ $row->branch?->name ?? __('finance.daily_journals.summary.no_branch') }}
                        </x-filament::link>
                    @else
                        {{ $row->branch?->name ?? __('finance.daily_journals.summary.no_branch') }}
                    @endif
                </td>
                <td>{{ $row->company?->trade_name ?? __('finance.daily_journals.summary.no_company') }}</td>
                <td>{{ $row->entries_count }}</td>
                @foreach(\App\Services\DailyJournalSummaryService::TOTALS as $column)
                    <td><bdi>{{ number_format((float) $row->{$column}, 2) }}</bdi></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="6">{{ __('finance.daily_journals.summary.empty') }}</td></tr>
        @endforelse
    </tbody>
</table>
