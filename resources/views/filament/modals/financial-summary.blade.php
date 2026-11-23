@php
    $groups = $groups ?? [];
    $notice = $notice ?? null;
    $progress = $progress ?? null;
    $footerNote = $footerNote ?? null;
@endphp

<style>
    .fs-modal {
        display: flex;
        flex-direction: column;
        gap: 16px;
        font-family: inherit;
    }

    .fs-modal-notice {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.55;
        font-weight: 500;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e40af;
    }
    .fs-modal-notice.is-warning {
        border-color: #fde68a;
        background: #fffbeb;
        color: #92400e;
    }
    .dark .fs-modal-notice {
        border-color: rgba(59, 130, 246, 0.35);
        background: rgba(30, 58, 138, 0.25);
        color: #bfdbfe;
    }
    .dark .fs-modal-notice.is-warning {
        border-color: rgba(245, 158, 11, 0.35);
        background: rgba(120, 53, 15, 0.25);
        color: #fde68a;
    }

    .fs-modal-progress-card {
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .dark .fs-modal-progress-card {
        border-color: rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.03);
    }

    .fs-modal-progress-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fs-modal-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
    }
    .fs-modal-badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: currentColor;
    }
    .fs-modal-badge.tone-success { background: #dcfce7; color: #166534; }
    .fs-modal-badge.tone-warning { background: #fef3c7; color: #92400e; }
    .fs-modal-badge.tone-neutral { background: #e2e8f0; color: #334155; }
    .dark .fs-modal-badge.tone-success { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
    .dark .fs-modal-badge.tone-warning { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }
    .dark .fs-modal-badge.tone-neutral { background: rgba(148, 163, 184, 0.18); color: #cbd5e1; }

    .fs-modal-percent {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        font-variant-numeric: tabular-nums;
    }
    .dark .fs-modal-percent {
        color: #cbd5e1;
    }

    .fs-modal-bar-track {
        width: 100%;
        height: 8px;
        border-radius: 9999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .dark .fs-modal-bar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .fs-modal-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }
    .fs-modal-bar-fill.tone-success { background: #10b981; }
    .fs-modal-bar-fill.tone-warning { background: #f59e0b; }
    .fs-modal-bar-fill.tone-neutral { background: #94a3b8; }

    .fs-modal-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .fs-modal-group-title {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        padding-inline: 2px;
    }
    .dark .fs-modal-group-title {
        color: #94a3b8;
    }

    .fs-modal-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .fs-modal-grid.cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    @media (max-width: 640px) {
        .fs-modal-grid,
        .fs-modal-grid.cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 420px) {
        .fs-modal-grid,
        .fs-modal-grid.cols-2 {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .fs-modal-card {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 6px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .dark .fs-modal-card {
        border-color: rgba(255, 255, 255, 0.09);
        background: rgba(255, 255, 255, 0.03);
    }

    .fs-modal-card.tone-primary { border-color: #bfdbfe; background: #f8fbff; }
    .fs-modal-card.tone-success { border-color: #bbf7d0; background: #f0fdf4; }
    .fs-modal-card.tone-warning { border-color: #fde68a; background: #fffbeb; }
    .fs-modal-card.tone-danger  { border-color: #fecaca; background: #fef2f2; }
    .fs-modal-card.tone-info    { border-color: #bae6fd; background: #f0f9ff; }
    .fs-modal-card.tone-neutral { border-color: #e2e8f0; background: #f8fafc; }

    .dark .fs-modal-card.tone-primary { border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.08); }
    .dark .fs-modal-card.tone-success { border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.08); }
    .dark .fs-modal-card.tone-warning { border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.08); }
    .dark .fs-modal-card.tone-danger  { border-color: rgba(239, 68, 68, 0.3);  background: rgba(239, 68, 68, 0.08); }
    .dark .fs-modal-card.tone-info    { border-color: rgba(14, 165, 233, 0.3); background: rgba(14, 165, 233, 0.08); }
    .dark .fs-modal-card.tone-neutral { border-color: rgba(255, 255, 255, 0.09); background: rgba(255, 255, 255, 0.03); }

    .fs-modal-card-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        line-height: 1.35;
    }
    .dark .fs-modal-card-label {
        color: #94a3b8;
    }

    .fs-modal-card-value-row {
        display: flex;
        align-items: baseline;
        gap: 5px;
        flex-wrap: wrap;
    }

    .fs-modal-card-value {
        font-size: 18px;
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        direction: ltr;
        unicode-bidi: isolate;
        color: #0f172a;
    }
    .dark .fs-modal-card-value {
        color: #f8fafc;
    }

    .fs-modal-card.tone-primary .fs-modal-card-value { color: #1d4ed8; }
    .fs-modal-card.tone-success .fs-modal-card-value { color: #15803d; }
    .fs-modal-card.tone-warning .fs-modal-card-value { color: #b45309; }
    .fs-modal-card.tone-danger  .fs-modal-card-value { color: #b91c1c; }
    .fs-modal-card.tone-info    .fs-modal-card-value { color: #0369a1; }

    .dark .fs-modal-card.tone-primary .fs-modal-card-value { color: #93c5fd; }
    .dark .fs-modal-card.tone-success .fs-modal-card-value { color: #6ee7b7; }
    .dark .fs-modal-card.tone-warning .fs-modal-card-value { color: #fcd34d; }
    .dark .fs-modal-card.tone-danger  .fs-modal-card-value { color: #fca5a5; }
    .dark .fs-modal-card.tone-info    .fs-modal-card-value { color: #7dd3fc; }

    .fs-modal-card-currency {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }
    .dark .fs-modal-card-currency {
        color: #94a3b8;
    }

    .fs-modal-footer {
        font-size: 11.5px;
        color: #64748b;
        padding-top: 4px;
        border-top: 1px dashed #e2e8f0;
    }
    .dark .fs-modal-footer {
        color: #94a3b8;
        border-top-color: rgba(255, 255, 255, 0.1);
    }
</style>

<div class="fs-modal">
    @if ($notice)
        <div class="fs-modal-notice {{ ($notice['tone'] ?? 'info') === 'warning' ? 'is-warning' : '' }}">
            <span>{{ $notice['text'] }}</span>
        </div>
    @endif

    @if ($progress)
        @php
            $progressTone = $progress['tone'] ?? 'neutral';
            $percent = (float) ($progress['percent'] ?? 0);
        @endphp
        <div class="fs-modal-progress-card">
            <div class="fs-modal-progress-top">
                <span class="fs-modal-badge tone-{{ $progressTone }}">
                    <span class="fs-modal-badge-dot"></span>
                    {{ $progress['status'] }}
                </span>
                <span class="fs-modal-percent">{{ number_format($percent, $percent == floor($percent) ? 0 : 1) }}%</span>
            </div>
            <div class="fs-modal-bar-track">
                <div class="fs-modal-bar-fill tone-{{ $progressTone }}" style="width: {{ max(0, min(100, $percent)) }}%;"></div>
            </div>
        </div>
    @endif

    @foreach ($groups as $group)
        @php
            $items = $group['items'] ?? [];
            $cols = (int) ($group['columns'] ?? 3);
        @endphp
        @if (! empty($items))
            <div class="fs-modal-group">
                @if (! empty($group['title']))
                    <div class="fs-modal-group-title">{{ $group['title'] }}</div>
                @endif

                <div class="fs-modal-grid {{ $cols === 2 ? 'cols-2' : '' }}">
                    @foreach ($items as $item)
                        @php
                            $tone = $item['tone'] ?? 'neutral';
                        @endphp
                        <div class="fs-modal-card tone-{{ $tone }}">
                            <div class="fs-modal-card-label">{{ $item['label'] }}</div>
                            <div class="fs-modal-card-value-row">
                                <span class="fs-modal-card-value">{{ $item['value'] }}</span>
                                @if (! empty($item['currency']))
                                    <span class="fs-modal-card-currency">{{ $item['currency'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @if ($footerNote)
        <div class="fs-modal-footer">
            {{ $footerNote }}
        </div>
    @endif
</div>
