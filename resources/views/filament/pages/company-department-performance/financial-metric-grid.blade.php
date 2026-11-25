@php
    ['type' => $selectedType, 'records' => $records] = $this->financialPreviews[$group];
    $title = __($labels[$selectedType] ?? 'performance.financial.metrics.'.$selectedType);
    $rates = \App\Services\CompanyFinancialPerformanceService::indicatorRates($group, $cards);
    $rateLabel = match ($group) {
        'treasury' => 'treasury_share',
        'receipts_and_custody' => 'receipts_share',
        'settlements' => 'fees_share',
        'payroll' => 'payroll_share',
        'collection' => 'collection_share',
        'debts' => 'debt_share',
        default => null,
    };
    $rateAccents = [
        'receipt_cash' => '#059669',
        'receipt_non_cash' => '#0284c7',
        'fee_direct' => '#0284c7',
        'fee_personal' => '#7c3aed',
        'payroll_paid' => '#059669',
        'payroll_remaining' => '#b45309',
        'collection_paid' => '#059669',
        'collection_remaining' => '#b45309',
        'debt_paid' => '#059669',
        'debt_remaining' => '#b45309',
        'debt_overdue' => '#e11d48',
    ];
@endphp

<div class="perf-financial-grid">
    @foreach($cards as $type => $amount)
        <x-performance.metric-card
            :title="__($labels[$type] ?? 'performance.financial.metrics.'.$type)"
            :value="number_format($amount, 2)"
            :unit="__('performance.financial.currency')"
            :rate="$rates[$type] ?? null"
            :rate-label="array_key_exists($type, $rates) ? __('performance.financial.'.$rateLabel) : null"
            :rate-precision="1"
            :hint="__($hints[$type] ?? 'performance.financial.metric_hints.'.$type)"
            :icon="$icons[$type] ?? 'heroicon-o-banknotes'"
            :accent="$accents[$type] ?? $rateAccents[$type] ?? '#4f46e5'"
            :action="'selectFinancialPreview(\''.$group.'\', \''.$type.'\')'"
            :expanded="$selectedType === $type"
            stacked
        />
    @endforeach
</div>

<section class="perf-financial-preview" wire:key="financial-preview-{{ $group }}" aria-labelledby="financial-preview-{{ $selectedType }}">
    <div class="perf-financial-preview-header">
        <div>
            <h3 id="financial-preview-{{ $selectedType }}">{{ __('performance.financial.preview_title', ['metric' => $title]) }}</h3>
            @if($records->isNotEmpty())
                <p class="perf-overview-note">{{ __('performance.financial.preview_count', ['count' => $records->count()]) }}</p>
            @endif
        </div>
        @if($records->isNotEmpty())
            <x-filament::button color="gray" size="sm" wire:click="openStatement('financial', '{{ $selectedType }}')">
                {{ __('performance.financial.full_statement') }}
            </x-filament::button>
        @endif
    </div>
    @include('filament.pages.company-department-performance.financial-records', ['type' => $selectedType, 'preview' => true])
</section>
