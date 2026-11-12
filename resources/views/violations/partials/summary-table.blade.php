<table class="violation-summary-table">
    <thead>
        <tr>
            @foreach(['company', 'commercial_registration', 'violation_count', ...\App\Services\ViolationSummaryService::TOTALS] as $column)
                <th scope="col">{{ __('violations.summary.'.$column) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr @if($interactive) wire:key="violation-company-{{ $row->id }}" @endif>
                <td>
                    @if($interactive)
                        <x-filament::link tag="button" wire:click="openDetails({{ $row->id }})">
                            {{ $row->company?->trade_name ?? __('violations.summary.no_company') }}
                        </x-filament::link>
                    @else
                        {{ $row->company?->trade_name ?? __('violations.summary.no_company') }}
                    @endif
                </td>
                <td><bdi>{{ $row->company?->commercial_registration_number ?? '—' }}</bdi></td>
                <td>{{ $row->violation_count }}</td>
                @foreach(\App\Services\ViolationSummaryService::TOTALS as $column)
                    <td><bdi>{{ number_format((float) $row->{$column}, 2) }}</bdi></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="7">{{ __('violations.summary.empty') }}</td></tr>
        @endforelse
    </tbody>
</table>

