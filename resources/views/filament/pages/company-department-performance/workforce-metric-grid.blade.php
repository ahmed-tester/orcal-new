@php
    ['type' => $selectedType, 'records' => $records] = $this->workforcePreviews[$group];
    $title = __($labels[$selectedType] ?? 'performance.overview.'.$selectedType);
@endphp

<div class="perf-workforce-grid perf-financial-grid">
    @foreach($cards as $type => $count)
        <x-performance.metric-card
            :title="__($labels[$type] ?? 'performance.overview.'.$type)"
            :value="number_format($count)"
            :unit="$units[$type] ?? null"
            :hint="__($hints[$type] ?? null)"
            :icon="$icons[$type] ?? 'heroicon-o-calendar'"
            :accent="$accents[$type] ?? '#4f46e5'"
            :action="'selectWorkforcePreview(\''.$group.'\', \''.$type.'\')'"
            :expanded="$selectedType === $type"
            stacked
        />
    @endforeach
</div>

<section class="perf-financial-preview" wire:key="workforce-preview-{{ $group }}" aria-labelledby="workforce-preview-{{ $selectedType }}">
    <div class="perf-financial-preview-header">
        <div>
            <h3 id="workforce-preview-{{ $selectedType }}">{{ __('performance.workforce.preview_title', ['metric' => $title]) }}</h3>
            @if($records->isNotEmpty())
                <p class="perf-overview-note">{{ __('performance.workforce.preview_count', ['count' => $records->count()]) }}</p>
            @endif
        </div>
        @if($records->isNotEmpty())
            <x-filament::button color="gray" size="sm" wire:click="openKashf('{{ $group }}', '{{ $selectedType }}')">
                {{ __('performance.workforce.full_statement') }}
            </x-filament::button>
        @endif
    </div>
    @include('filament.pages.company-department-performance.workforce-records', ['group' => $group, 'type' => $selectedType, 'records' => $records, 'preview' => true])
</section>
