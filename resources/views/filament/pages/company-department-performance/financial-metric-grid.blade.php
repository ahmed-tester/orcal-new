@php
    ['type' => $selectedType, 'records' => $records] = $this->financialPreviews[$group];
    $title = __($labels[$selectedType] ?? 'performance.financial.metrics.'.$selectedType);
@endphp

<div class="perf-financial-grid">
    @foreach($cards as $type => $amount)
        <x-performance.metric-card
            :title="__($labels[$type] ?? 'performance.financial.metrics.'.$type)"
            :value="number_format($amount, 2)"
            :unit="__('performance.financial.currency')"
            :hint="__($hints[$type] ?? 'performance.financial.metric_hints.'.$type)"
            :icon="$icons[$type] ?? 'heroicon-o-banknotes'"
            :accent="$accents[$type] ?? '#4f46e5'"
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
            <x-filament::button color="gray" size="sm" wire:click="openKashf('financial', '{{ $selectedType }}')">
                {{ __('performance.financial.full_statement') }}
            </x-filament::button>
        @endif
    </div>
    @include('filament.pages.company-department-performance.financial-records', ['type' => $selectedType, 'preview' => true])
</section>
