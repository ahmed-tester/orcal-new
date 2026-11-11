<table class="debt-summary-table">
    <thead>
        <tr>
            @foreach(['debtor', 'debtor_type', 'company', 'debt_count', ...\App\Services\DebtSummaryService::TOTALS] as $column)
                <th scope="col">{{ __('debts.summary.'.$column) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr @if($interactive) wire:key="debtor-{{ $row->id }}" @endif>
                <td>
                    @if($interactive)
                        <x-filament::link tag="button" wire:click="openDetails({{ $row->id }})">{{ $row->beneficiary_name }}</x-filament::link>
                    @else
                        {{ $row->beneficiary_name }}
                    @endif
                </td>
                <td>{{ __('debts.badges.'.$row->debtor_type) }}</td>
                <td>{{ $row->company?->trade_name ?? __('debts.summary.no_company') }}</td>
                <td>{{ $row->debt_count }}</td>
                @foreach(\App\Services\DebtSummaryService::TOTALS as $column)
                    <td><bdi>{{ number_format((float) $row->{$column}, 2) }}</bdi></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="8">{{ __('debts.summary.empty') }}</td></tr>
        @endforelse
    </tbody>
</table>
