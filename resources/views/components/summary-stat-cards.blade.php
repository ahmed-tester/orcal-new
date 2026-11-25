@props([
    'cards' => [],
    'active' => null,
    'method' => 'applyQuickFilter',
    'columns' => 5,
])

<style>
    .summary-stats-grid {
        display: grid;
        grid-template-columns: repeat(var(--summary-cols, 5), minmax(0, 1fr));
        gap: 0.875rem;
    }
    .summary-stat-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1rem;
        min-width: 0;
        min-height: 116px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        text-align: start;
        font: inherit;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .summary-stat-card.is-clickable {
        cursor: pointer;
    }
    .summary-stat-card.is-clickable:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px -3px rgba(15, 23, 42, 0.08);
    }
    .summary-stat-card.is-active {
        border-width: 2px;
        border-color: var(--stat-accent, #2563eb);
        box-shadow: 0 6px 16px -3px rgba(15, 23, 42, 0.12);
    }
    .dark .summary-stat-card {
        background-color: #1e293b !important;
        border-color: #334155;
    }
    .dark .summary-stat-card.is-active {
        border-color: var(--stat-accent, #60a5fa);
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    .summary-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        width: 100%;
    }
    .summary-stat-title {
        font-size: 0.8125rem;
        font-weight: 700;
        color: #475569;
    }
    .dark .summary-stat-title {
        color: #cbd5e1 !important;
    }
    .summary-stat-icon-wrapper {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .summary-stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .summary-stat-subtitle {
        font-size: 0.75rem;
        font-weight: 600;
    }
    .summary-stat-icon {
        width: 20px !important;
        height: 20px !important;
        flex-shrink: 0;
    }

    @media (max-width: 1100px) {
        .summary-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .summary-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 480px) {
        .summary-stats-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="summary-stats-grid" style="--summary-cols: {{ (int) $columns }};">
    @foreach($cards as $card)
        @php
            $key = $card['key'] ?? null;
            $clickable = $card['clickable'] ?? ($key !== null);
            $isActive = $clickable && $active !== null && $key !== null && $active === $key;
            $accent = $card['color'] ?? $card['text'] ?? '#2563eb';
            $hasIconBg = isset($card['icon_bg']);
            $cardBg = $card['card_bg'] ?? ($hasIconBg ? ($card['bg'] ?? '#ffffff') : '#ffffff');
            $iconBg = $card['icon_bg'] ?? ($card['bg'] ?? '#eff6ff');
            $cardBorder = $hasIconBg ? ($card['border'] ?? '#e2e8f0') : '#e2e8f0';
            $iconBorder = $hasIconBg ? 'none' : ('1px solid ' . ($card['border'] ?? '#e2e8f0'));
            $wireArg = $key !== null ? "'" . e($key) . "'" : 'null';
        @endphp

        @if($clickable)
            <button
                type="button"
                wire:click="{{ $method }}({{ $wireArg }})"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                style="background-color: {{ $cardBg }}; border-color: {{ $isActive ? $accent : $cardBorder }}; --stat-accent: {{ $accent }};"
                class="summary-stat-card is-clickable {{ $isActive ? 'is-active' : '' }}"
            >
                <div class="summary-stat-top">
                    <span class="summary-stat-title" style="color: {{ $accent }};">{{ $card['title'] }}</span>
                    @if(! empty($card['icon']))
                        <div class="summary-stat-icon-wrapper" style="background-color: {{ $iconBg }}; color: {{ $accent }}; border: {{ $iconBorder }};">
                            <x-dynamic-component :component="$card['icon']" class="summary-stat-icon" />
                        </div>
                    @endif
                </div>
                <div class="summary-stat-value" style="color: {{ $accent }};">{{ $card['value'] }}</div>
                @if(! empty($card['subtitle']))
                    <div class="summary-stat-subtitle" style="color: {{ $accent }};">{{ $card['subtitle'] }}</div>
                @endif
            </button>
        @else
            <div class="summary-stat-card" style="background-color: {{ $cardBg }}; border-color: {{ $cardBorder }};">
                <div class="summary-stat-top">
                    <span class="summary-stat-title" style="color: {{ $accent }};">{{ $card['title'] }}</span>
                    @if(! empty($card['icon']))
                        <div class="summary-stat-icon-wrapper" style="background-color: {{ $iconBg }}; color: {{ $accent }}; border: {{ $iconBorder }};">
                            <x-dynamic-component :component="$card['icon']" class="summary-stat-icon" />
                        </div>
                    @endif
                </div>
                <div class="summary-stat-value" style="color: {{ $accent }};">{{ $card['value'] }}</div>
                @if(! empty($card['subtitle']))
                    <div class="summary-stat-subtitle" style="color: {{ $accent }};">{{ $card['subtitle'] }}</div>
                @endif
            </div>
        @endif
    @endforeach
</div>
