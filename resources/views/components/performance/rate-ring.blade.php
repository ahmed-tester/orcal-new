@props([
    'rate' => null,
    'label' => '',
    'sublabel' => null,
    'size' => 'm',
    'color' => null,
    'action' => null,
    'noDataText' => '—',
    'showPercent' => true,
    'precision' => 0,
    'fixedPrecision' => false,
])

@php
    $val = is_numeric($rate) ? (float) $rate : null;
    $pct = $val !== null ? max(0, min(100, round($val, $precision))) : 0;
    $displayValue = $fixedPrecision && $val !== null ? number_format($val, $precision) : $pct;
    $suffix = $showPercent ? '%' : '';

    $ringColor = $color ?? ($val !== null
        ? ($val >= 80 ? '#22c55e' : ($val >= 50 ? '#f59e0b' : '#ef4444'))
        : 'var(--bd, #e4e8f3)');
    $tag = $action ? 'button' : 'div';
@endphp

<{{ $tag }}
    class="perf-ring-item {{ $action ? 'is-clickable' : '' }}"
    @if($action) type="button" wire:click="{{ $action }}" @endif
>
    <span
        class="ring {{ $size }}"
        style="--col: {{ $ringColor }}; --p: {{ $pct }};"
        title="{{ $label ? "{$label}: " : '' }}{{ $val !== null ? "{$displayValue}{$suffix}" : $noDataText }}"
    >
        <i>
            @if($val !== null)
                <span>{{ $displayValue }}{{ $suffix }}</span>
            @else
                <span class="ring-null">{{ $noDataText }}</span>
            @endif
        </i>
    </span>
    @if($label)
        <span class="perf-ring-label">{{ $label }}</span>
    @endif
    @if($sublabel)
        <span class="perf-ring-sublabel">{{ $sublabel }}</span>
    @endif
</{{ $tag }}>

