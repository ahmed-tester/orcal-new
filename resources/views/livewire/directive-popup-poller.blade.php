<div wire:poll.30s="loadPendingDirective">
    @if($directiveData)
        @php
            $colorMap = [
                'danger'  => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'border' => '#fca5a5', 'accent' => '#ef4444'],
                'warning' => ['bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fcd34d', 'accent' => '#f59e0b'],
                'success' => ['bg' => '#dcfce7', 'text' => '#15803d', 'border' => '#86efac', 'accent' => '#10b981'],
                'info'    => ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'border' => '#93c5fd', 'accent' => '#3b82f6'],
                'primary' => ['bg' => '#e0e7ff', 'text' => '#3730a3', 'border' => '#a5b4fc', 'accent' => '#054584'],
                'gray'    => ['bg' => '#f1f5f9', 'text' => '#334155', 'border' => '#cbd5e1', 'accent' => '#64748b'],
            ];
            $badgePalette = $colorMap[$directiveData['category_color'] ?? 'info'] ?? $colorMap['info'];
            $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
        @endphp

        <div
            x-data
            x-on:keydown.escape.window.prevent.stop=""
            role="dialog"
            aria-modal="true"
            aria-labelledby="directive-popup-title"
            class="fi-directive-backdrop"
        >
            <div class="fi-directive-modal" dir="{{ $dir }}">
                {{-- Top Accent Strip --}}
                <div class="fi-directive-accent-bar" style="background: {{ $badgePalette['accent'] }};"></div>

                {{-- Header --}}
                <div class="fi-directive-modal-header">
                    <div class="fi-directive-modal-header-main">
                        <span class="fi-directive-modal-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                            </svg>
                        </span>
                        <div class="fi-directive-header-text">
                            <div class="fi-directive-header-kicker">{{ __('directives.popup.header_title') }}</div>
                            <h2 id="directive-popup-title" class="fi-directive-header-title">
                                {{ $directiveData['title'] }}
                            </h2>
                        </div>
                    </div>

                    @if($pendingCount > 1)
                        <span class="fi-directive-counter">
                            {{ __('directives.popup.counter', ['current' => 1, 'total' => $pendingCount]) }}
                        </span>
                    @endif
                </div>

                {{-- Meta Bar --}}
                <div class="fi-directive-meta">
                    @if(! empty($directiveData['category_name']))
                        <span
                            class="fi-directive-category-badge"
                            style="background: {{ $badgePalette['bg'] }}; color: {{ $badgePalette['text'] }}; border: 1px solid {{ $badgePalette['border'] }};"
                        >
                            <span class="fi-directive-dot" style="background: {{ $badgePalette['accent'] }};"></span>
                            {{ $directiveData['category_name'] }}
                        </span>
                    @endif

                    @if(! empty($directiveData['sender']))
                        <span class="fi-directive-meta-chip">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                            </svg>
                            <span><strong>{{ __('directives.popup.sender') }}:</strong> {{ $directiveData['sender'] }}</span>
                        </span>
                    @endif

                    @if(! empty($directiveData['sent_at']))
                        <span class="fi-directive-meta-chip">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                            </svg>
                            <span><strong>{{ __('directives.popup.sent_at') }}:</strong> {{ $directiveData['sent_at'] }}</span>
                        </span>
                    @endif
                </div>

                {{-- Body --}}
                <div class="fi-directive-body">
                    <div class="fi-directive-message-card" style="border-inline-start-color: {{ $badgePalette['accent'] }};">
                        <div class="fi-directive-description">{{ $directiveData['description'] }}</div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="fi-directive-footer">
                    <div class="fi-directive-notice">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ __('directives.popup.mandatory_notice') }}</span>
                    </div>

                    <button
                        type="button"
                        wire:click="acknowledge"
                        wire:loading.attr="disabled"
                        class="fi-directive-ack-btn"
                    >
                        <svg wire:loading.remove wire:target="acknowledge" viewBox="0 0 20 20" fill="currentColor" style="width: 18px; height: 18px;">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                        <svg wire:loading wire:target="acknowledge" class="fi-directive-spinner" viewBox="0 0 24 24" fill="none" style="width: 18px; height: 18px;">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.25" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        <span>{{ __('directives.actions.acknowledge') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <style>
            @keyframes fiDirectiveFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes fiDirectivePopIn {
                from {
                    opacity: 0;
                    transform: translateY(10px) scale(0.97);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @keyframes fiDirectiveSpin {
                to { transform: rotate(360deg); }
            }

            .fi-directive-backdrop {
                position: fixed;
                inset: 0;
                z-index: 99999;
                background: rgba(15, 23, 42, 0.72);
                backdrop-filter: blur(6px);
                -webkit-backdrop-filter: blur(6px);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                animation: fiDirectiveFadeIn 0.2s ease-out;
            }

            .fi-directive-modal {
                width: 100%;
                max-width: 640px;
                background: #ffffff;
                color: #0f172a;
                border-radius: 1.15rem;
                overflow: hidden;
                box-shadow:
                    0 25px 50px -12px rgba(15, 23, 42, 0.45),
                    0 0 0 1px rgba(255, 255, 255, 0.08);
                display: flex;
                flex-direction: column;
                max-height: 88vh;
                animation: fiDirectivePopIn 0.24s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .dark .fi-directive-modal {
                background: #0f172a;
                color: #f8fafc;
                box-shadow:
                    0 25px 50px -12px rgba(0, 0, 0, 0.7),
                    0 0 0 1px #334155;
            }

            .fi-directive-accent-bar {
                height: 4px;
                width: 100%;
                flex-shrink: 0;
            }

            .fi-directive-modal-header {
                position: relative;
                background: linear-gradient(135deg, #054584 0%, #032d57 100%);
                color: #ffffff;
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                overflow: hidden;
            }

            .fi-directive-modal-header::after {
                content: '';
                position: absolute;
                inset-inline-end: -40px;
                top: -40px;
                width: 150px;
                height: 150px;
                border-radius: 9999px;
                background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
                pointer-events: none;
            }

            .fi-directive-modal-header-main {
                display: flex;
                align-items: center;
                gap: 0.95rem;
                min-width: 0;
                position: relative;
                z-index: 1;
            }

            .fi-directive-modal-icon {
                width: 46px;
                height: 46px;
                border-radius: 0.85rem;
                background: rgba(255, 255, 255, 0.14);
                border: 1px solid rgba(255, 255, 255, 0.24);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .fi-directive-modal-icon svg {
                width: 24px;
                height: 24px;
                color: #fde68a;
            }

            .fi-directive-header-text {
                min-width: 0;
            }

            .fi-directive-header-kicker {
                font-size: 0.78rem;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.78);
                margin-bottom: 0.2rem;
                letter-spacing: 0.01em;
            }

            .fi-directive-header-title {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 800;
                line-height: 1.35;
                color: #ffffff;
                word-break: break-word;
            }

            .fi-directive-counter {
                position: relative;
                z-index: 1;
                background: rgba(255, 255, 255, 0.16);
                color: #ffffff;
                border: 1px solid rgba(255, 255, 255, 0.28);
                padding: 0.3rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.76rem;
                font-weight: 700;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .fi-directive-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.65rem;
                padding: 0.85rem 1.5rem;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                font-size: 0.82rem;
                color: #475569;
            }

            .dark .fi-directive-meta {
                background: #1e293b;
                border-bottom-color: #334155;
                color: #cbd5e1;
            }

            .fi-directive-category-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.78rem;
                font-weight: 700;
            }

            .fi-directive-dot {
                width: 7px;
                height: 7px;
                border-radius: 9999px;
                flex-shrink: 0;
            }

            .fi-directive-meta-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.24rem 0.65rem;
                border-radius: 0.5rem;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.8rem;
            }

            .dark .fi-directive-meta-chip {
                background: #0f172a;
                border-color: #334155;
                color: #cbd5e1;
            }

            .fi-directive-meta-chip svg {
                width: 14px;
                height: 14px;
                color: #64748b;
                flex-shrink: 0;
            }

            .fi-directive-body {
                padding: 1.5rem;
                overflow-y: auto;
                flex: 1;
            }

            .fi-directive-message-card {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-inline-start-width: 4px;
                border-radius: 0.75rem;
                padding: 1.15rem 1.25rem;
                min-height: 84px;
            }

            .dark .fi-directive-message-card {
                background: #1e293b;
                border-color: #334155;
            }

            .fi-directive-description {
                white-space: pre-line;
                font-size: 1rem;
                line-height: 1.85;
                color: #1e293b;
                word-break: break-word;
            }

            .dark .fi-directive-description {
                color: #e2e8f0;
            }

            .fi-directive-footer {
                padding: 1rem 1.5rem;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.9rem;
            }

            .dark .fi-directive-footer {
                background: #1e293b;
                border-top-color: #334155;
            }

            .fi-directive-notice {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                margin: 0;
                font-size: 0.82rem;
                font-weight: 600;
                color: #b45309;
            }

            .fi-directive-notice svg {
                width: 17px;
                height: 17px;
                flex-shrink: 0;
            }

            .dark .fi-directive-notice {
                color: #fbbf24;
            }

            .fi-directive-ack-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.7rem 1.75rem;
                border-radius: 0.65rem;
                border: none;
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: #ffffff;
                font-size: 0.95rem;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
                transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
                margin-inline-start: auto;
            }

            .fi-directive-ack-btn:hover:not(:disabled) {
                filter: brightness(1.05);
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(5, 150, 105, 0.42);
            }

            .fi-directive-ack-btn:active:not(:disabled) {
                transform: translateY(0);
            }

            .fi-directive-ack-btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }

            .fi-directive-spinner {
                animation: fiDirectiveSpin 0.75s linear infinite;
            }
        </style>
    @endif
</div>
