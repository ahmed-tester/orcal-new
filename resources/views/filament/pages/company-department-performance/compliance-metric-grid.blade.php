@php
    ['type' => $selectedType, 'records' => $records] = $this->compliancePreviews[$group];
    $title = __('performance.compliance.'.$selectedType);
@endphp

<div class="perf-workforce-grid perf-financial-grid">
    @foreach($cards as $type => $count)
        @php
            $document = $group === 'iqama' ? 'iqama' : (str_starts_with($type, 'insurance_') ? 'insurance' : 'work_permit');
            $total = $rateTotals[$document];
        @endphp
        <x-performance.metric-card
            :title="__('performance.compliance.'.$type)"
            :value="number_format($count)"
            :unit="$units[$type] ?? null"
            :rate="$total > 0 ? $count * 100 / $total : null"
            :rate-label="__('performance.compliance.recorded_documents_share')"
            :hint="__($hints[$type] ?? 'performance.compliance.metric_hints.'.$type)"
            :icon="$icons[$type] ?? 'heroicon-o-identification'"
            :accent="$accents[$type] ?? '#4f46e5'"
            :action="'selectCompliancePreview(\''.$group.'\', \''.$type.'\')'"
            :expanded="$selectedType === $type"
            stacked
        />
    @endforeach
</div>

<section class="perf-financial-preview" wire:key="compliance-preview-{{ $group }}" aria-labelledby="compliance-preview-{{ $selectedType }}">
    <div class="perf-financial-preview-header">
        <div>
            <h3 id="compliance-preview-{{ $selectedType }}">{{ __('performance.compliance.preview_title', ['metric' => $title]) }}</h3>
            @if($records->isNotEmpty())
                <p class="perf-overview-note">{{ __('performance.compliance.preview_count', ['count' => $records->count()]) }}</p>
            @endif
        </div>
        @if($records->isNotEmpty())
            <x-filament::button color="gray" size="sm" wire:click="openStatement('compliance', '{{ $selectedType }}')">
                {{ __('performance.compliance.full_statement') }}
            </x-filament::button>
        @endif
    </div>
    @include('filament.pages.company-department-performance.compliance-records', ['type' => $selectedType, 'records' => $records])
</section>
