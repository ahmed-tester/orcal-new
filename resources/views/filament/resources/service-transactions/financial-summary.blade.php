@php
    $record = $record ?? null;
@endphp

@if ($record)
    @php
        $summary = $record->financialSummary();
        $snapshot = \App\Services\ServiceFinancialWorkflowService::displaySnapshot($record);
        $money = fn ($value) => number_format((float) $value, 2).' '.__('services_tracking.currency_sar');
    @endphp

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'client_due' => $summary['client_due'],
            'received' => $summary['received'],
            'custody' => $summary['custody'],
            'treasury_received' => $summary['treasury_received'],
            'fee_expected' => $summary['fee_defined'] ? $summary['fee_expected'] : null,
            'fee_paid' => $summary['fee_paid'],
            'fee_remaining' => $summary['fee_remaining'],
            'fee_funded_remaining' => $summary['fee_funded_remaining'],
            'employee_due' => $summary['employee_due'],
            'surplus' => $summary['surplus'],
            'net_cash' => $summary['net_cash'],
            'client_remaining' => $summary['client_remaining'],
        ] as $key => $value)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('financial.summary.'.$key) }}
                </div>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                    @if ($value === null)
                        <span class="text-sm font-semibold text-danger-600">
                            {{ __('financial.summary.not_defined') }}
                        </span>
                    @else
                        {{ $money($value) }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-3">
        <div>
            <span class="font-semibold">{{ __('financial.summary.pending_receipts') }}:</span>
            {{ $money($summary['pending_receipts']) }}
            ({{ $summary['pending_receipts_count'] }})
        </div>
        <div>
            <span class="font-semibold">{{ __('services_tracking.fields.due_date') }}:</span>
            {{ $record->due_date?->format('Y-m-d') ?? '—' }}
        </div>
        <div>
            <span class="font-semibold">{{ __('financial.summary.description') }}</span>
        </div>
    </div>
@endif
