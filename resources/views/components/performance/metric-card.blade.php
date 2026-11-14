@props([
    'title' => '',
    'value' => '0',
    'hint' => null,
    'icon' => null,
    'accent' => '#5b5bf0',
    'action' => null,
    'unit' => null,
    'isHero' => false,
    'index' => 0,
    'rate' => null,
])

<div
    class="perf-card {{ $isHero ? 'hero' : '' }} {{ $action ? 'is-clickable' : '' }}"
    style="--i: {{ $index }}; --c1: {{ $accent }};"
    @if($action) wire:click="{{ $action }}" role="button" tabindex="0" @endif
>
    <div class="perf-card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
            @if($icon)
                <div class="ic" style="background: color-mix(in srgb, {{ $accent }} 16%, transparent); color: {{ $accent }}; margin-bottom: 0;">
                    <x-dynamic-component :component="$icon" style="width: 18px; height: 18px;" />
                </div>
            @endif
            <div class="k">{{ $title }}</div>
        </div>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 6px;">
        <div class="v" style="margin-top: 0;">
            <span>{{ $value }}</span>
            @if($unit)
                <u>{{ $unit }}</u>
            @endif
        </div>
        @if($rate !== null)
            <x-performance.rate-ring :rate="$rate" size="s" />
        @endif
    </div>

    @if($hint)
        <div class="perf-card-hint">
            <span>{{ $hint }}</span>
            @if($action)
                <span class="perf-card-hint-arrow">
                    <x-heroicon-m-arrow-left style="width: 12px; height: 12px;" />
                </span>
            @endif
        </div>
    @endif
</div>
