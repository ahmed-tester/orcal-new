@props([
    'rate' => null,
    'label' => '',
    'sublabel' => null,
    'size' => 'm',
    'color' => null,
    'action' => null,
    'noDataText' => '—',
    'showPercent' => true,
])

@php
    $val = is_numeric($rate) ? (float) $rate : null;
    $pct = $val !== null ? max(0, min(100, (int) round($val))) : 0;

    $ringColor = $color ?? ($val !== null
        ? ($val >= 80 ? '#22c55e' : ($val >= 50 ? '#f59e0b' : '#ef4444'))
        : 'var(--bd, #e4e8f3)');
@endphp

<div
    class="perf-ring-item {{ $action ? 'is-clickable' : '' }}"
    @if($action) wire:click="{{ $action }}" role="button" tabindex="0" @endif
>
    <div
        class="ring {{ $size }}"
        style="--col: {{ $ringColor }}; --p: {{ $pct }};"
        title="{{ $label ? "{$label}: " : '' }}{{ $val !== null ? "{$pct}%" : $noDataText }}"
    >
        <i>
            @if($val !== null)
                <span>{{ $pct }}{{ $showPercent ? '%' : '' }}</span>
            @else
                <span class="ring-null">{{ $noDataText }}</span>
            @endif
        </i>
    </div>
    @if($label)
        <span class="perf-ring-label">{{ $label }}</span>
    @endif
    @if($sublabel)
        <span class="perf-ring-sublabel">{{ $sublabel }}</span>
    @endif
</div>

