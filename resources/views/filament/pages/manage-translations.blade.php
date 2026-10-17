<x-filament-panels::page>
    <style>
        .trans-wrapper {
            direction: rtl;
            font-family: inherit;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Top Control Header Card */
        .trans-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .dark .trans-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .trans-top-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .trans-top-bar {
            border-color: #1f2937;
        }

        .trans-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .trans-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 0.65rem;
            background: linear-gradient(135deg, #2299dd 0%, #1570a6 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(34, 153, 221, 0.3);
        }

        /* Language Toggle Tabs */
        .trans-lang-tabs {
            display: inline-flex;
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.25rem;
            border: 1px solid #e2e8f0;
        }
        .dark .trans-lang-tabs {
            background: #0f172a;
            border-color: #334155;
        }
        .trans-lang-btn {
            padding: 0.4rem 1.1rem;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 0.55rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .dark .trans-lang-btn { color: #94a3b8; }
        .trans-lang-btn.active {
            background: #2299dd;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(34, 153, 221, 0.3);
        }

        /* Controls Row: Search + File + PerPage */
        .trans-controls-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.85rem;
        }

        .trans-search-box {
            flex: 1;
            min-width: 240px;
            position: relative;
        }
        .trans-search-icon {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.85rem;
        }
        .trans-clear-btn {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: #e2e8f0;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #64748b;
            cursor: pointer;
        }
        .dark .trans-clear-btn {
            background: #334155;
            color: #cbd5e1;
        }

        .trans-input {
            width: 100%;
            padding: 0.55rem 0.85rem;
            border-radius: 0.65rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.15s;
            box-sizing: border-box;
        }
        .trans-input:focus {
            background: #ffffff;
            border-color: #2299dd;
            box-shadow: 0 0 0 3px rgba(34, 153, 221, 0.15);
        }
        .dark .trans-input {
            background: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .dark .trans-input:focus {
            background: #1e293b;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .trans-search-input {
            padding-right: 2.4rem;
            padding-left: 2rem;
        }

        /* Modern Table Card */
        .trans-table-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dark .trans-table-card {
            background: #111827;
            border-color: #1f2937;
        }
        .trans-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 0.875rem;
        }
        .trans-table th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            font-weight: 700;
            color: #475569;
            font-size: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .trans-table th {
            background: #0f172a;
            color: #94a3b8;
            border-color: #1f2937;
        }
        .trans-table td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dark .trans-table td {
            border-color: #1f2937;
        }
        .trans-table tr:hover td {
            background: #f8fafc;
        }
        .dark .trans-table tr:hover td {
            background: #1e293b/50;
        }

        .trans-badge-file {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .dark .trans-badge-file {
            background: #082f49;
            color: #7dd3fc;
            border-color: #0369a1;
        }

        .trans-key-pill {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8rem;
            font-weight: 600;
            color: #0f172a;
            direction: ltr;
            text-align: left;
            background: #f1f5f9;
            padding: 0.25rem 0.55rem;
            border-radius: 0.45rem;
            border: 1px solid #e2e8f0;
            display: inline-block;
            word-break: break-all;
            user-select: all;
        }
        .dark .trans-key-pill {
            background: #0f172a;
            color: #e2e8f0;
            border-color: #334155;
        }

        /* Footer Bar with Pagination & Save Button */
        .trans-footer-bar {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 0.85rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .dark .trans-footer-bar {
            background: #111827;
            border-color: #1f2937;
        }

        .trans-pg-btn {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s;
            min-width: 32px;
            text-align: center;
        }
        .trans-pg-btn:hover:not(:disabled) {
            background: #f8fafc;
            border-color: #2299dd;
            color: #2299dd;
        }
        .trans-pg-btn.active {
            background: #2299dd;
            color: #ffffff;
            border-color: #2299dd;
        }
        .dark .trans-pg-btn {
            background: #1f2937;
            border-color: #374151;
            color: #9ca3af;
        }
        .dark .trans-pg-btn.active {
            background: #2299dd;
            color: #ffffff;
            border-color: #2299dd;
        }

        .trans-save-btn {
            background: #2299dd;
            color: #ffffff;
            border: none;
            padding: 0.65rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 0.65rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(34, 153, 221, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s;
        }
        .trans-save-btn:hover {
            background: #1a7bb2;
            transform: translateY(-1px);
        }
        .trans-save-btn:active {
            transform: translateY(0);
        }
    </style>

    <div class="trans-wrapper">
        <!-- 1. Header & Controls Card -->
        <div class="trans-card">
            <div class="trans-top-bar">
                <div class="trans-title-wrap">
                    <div class="trans-icon-badge">
                        🌐
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;" class="dark:text-white">
                            {{ __('translations.page_heading') }}
                        </h3>
                        <span style="font-size: 0.8rem; color: #64748b;" class="dark:text-slate-400">
                            {{ __('translations.total') }}: <strong class="text-primary-600">{{ number_format($this->stats['total']) }}</strong> {{ __('translations.keys_in') }} <strong>{{ $this->stats['files_count'] }}</strong> {{ __('translations.files') }}
                        </span>
                    </div>
                </div>

                <!-- Language Toggle -->
                <div class="trans-lang-tabs">
                    <button
                        type="button"
                        wire:click="$set('selectedLocale', 'ar')"
                        class="trans-lang-btn {{ $selectedLocale === 'ar' ? 'active' : '' }}"
                    >
                        <span>🇸🇦</span>
                        <span>{{ __('translations.arabic') }}</span>
                    </button>
                    {{--
                    <button
                        type="button"
                        wire:click="$set('selectedLocale', 'en')"
                        class="trans-lang-btn {{ $selectedLocale === 'en' ? 'active' : '' }}"
                    >
                        <span>🇬🇧</span>
                        <span>{{ __('translations.english') }}</span>
                    </button>
                    --}}
                </div>
            </div>

            <!-- Filters and Search Row -->
            <div class="trans-controls-row">
                <!-- Search Input -->
                <div class="trans-search-box">
                    <span class="trans-search-icon">🔍</span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('translations.search_placeholder') }}"
                        class="trans-input trans-search-input"
                    />
                    @if($search)
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="trans-clear-btn"
                            title="{{ __('translations.clear_search') }}"
                        >
                            ✕
                        </button>
                    @endif
                </div>

                <!-- File Filter -->
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <label style="font-size: 0.8rem; color: #64748b; font-weight: 700; white-space: nowrap;">{{ __('translations.file_label') }}:</label>
                    <select
                        wire:model.live="fileFilter"
                        class="trans-input"
                        style="width: auto; padding: 0.45rem 0.75rem; font-size: 0.85rem; min-width: 140px; font-weight: 700;"
                    >
                        <option value="all">📁 {{ __('translations.all_files') }}</option>
                        @foreach($this->availableFiles as $file)
                            <option value="{{ $file }}">📄 {{ $file }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page -->
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <label style="font-size: 0.8rem; color: #64748b; font-weight: 700; white-space: nowrap;">{{ __('translations.per_page_label') }}:</label>
                    <select
                        wire:model.live="perPage"
                        class="trans-input"
                        style="width: auto; padding: 0.45rem 0.75rem; font-size: 0.85rem; min-width: 70px; font-weight: 700;"
                    >
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Table Card with Integrated Footer -->
        <div class="trans-table-card">
            <div style="overflow-x: auto;">
                <table class="trans-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">{{ __('translations.th_number') }}</th>
                            <th style="width: 150px;">{{ __('translations.th_file') }}</th>
                            <th style="width: 300px;">{{ __('translations.th_key') }}</th>
                            <th>{{ __('translations.th_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $items = $this->filteredTranslations;
                            $pg = $this->paginationData;
                        @endphp

                        @forelse($items as $i => $item)
                            <tr wire:key="trans-row-{{ $item['unique_key'] }}">
                                <td style="text-align: center; color: #94a3b8; font-size: 0.75rem; font-weight: 700;">
                                    {{ ($pg['from'] + $i) }}
                                </td>

                                <td>
                                    <span class="trans-badge-file">
                                        📄 {{ $item['source'] }}
                                    </span>
                                </td>

                                <td>
                                    <span class="trans-key-pill">
                                        {{ $item['key'] }}
                                    </span>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        wire:key="trans-input-{{ $item['unique_key'] }}"
                                        wire:model="editedValues.{{ $item['unique_key'] }}"
                                        placeholder="{{ __('translations.input_placeholder') }}"
                                        class="trans-input"
                                        style="padding: 0.45rem 0.75rem; font-size: 0.875rem;"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                                    {{ __('translations.no_translations_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Pagination & Save Button -->
            @php $pgData = $this->paginationData; @endphp
            <div class="trans-footer-bar">
                {{-- Pagination Controls --}}
                @if($pgData['total'] > 0)
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">
                            {{ __('translations.showing') }} {{ $pgData['from'] }} - {{ $pgData['to'] }} {{ __('translations.of') }} {{ $pgData['total'] }}
                        </span>

                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                            <button
                                type="button"
                                wire:click="previousPage"
                                @if($pgData['currentPage'] <= 1) disabled @endif
                                class="trans-pg-btn"
                                style="@if($pgData['currentPage'] <= 1) opacity: 0.35; cursor: not-allowed; @endif"
                            >
                                ‹
                            </button>

                            @for($p = 1; $p <= $pgData['lastPage']; $p++)
                                @if($pgData['lastPage'] <= 5 || $p <= 2 || $p >= $pgData['lastPage'] - 1 || abs($p - $pgData['currentPage']) <= 1)
                                    <button
                                        type="button"
                                        wire:click="goToPage({{ $p }})"
                                        class="trans-pg-btn {{ $p === $pgData['currentPage'] ? 'active' : '' }}"
                                    >
                                        {{ $p }}
                                    </button>
                                @elseif($p === 3 && $pgData['currentPage'] > 3)
                                    <span style="color: #94a3b8; padding: 0 0.15rem;">…</span>
                                @elseif($p === $pgData['lastPage'] - 2 && $pgData['currentPage'] < $pgData['lastPage'] - 2)
                                    <span style="color: #94a3b8; padding: 0 0.15rem;">…</span>
                                @endif
                            @endfor

                            <button
                                type="button"
                                wire:click="nextPage"
                                @if($pgData['currentPage'] >= $pgData['lastPage']) disabled @endif
                                class="trans-pg-btn"
                                style="@if($pgData['currentPage'] >= $pgData['lastPage']) opacity: 0.35; cursor: not-allowed; @endif"
                            >
                                ›
                            </button>
                        </div>
                    </div>
                @else
                    <div></div>
                @endif

                {{-- Save Button --}}
                <div>
                    <button
                        type="button"
                        wire:click="saveAll"
                        wire:loading.attr="disabled"
                        class="trans-save-btn"
                    >
                        <span wire:loading.remove wire:target="saveAll">
                            ✓ {{ __('translations.save_changes') }}
                        </span>
                        <span wire:loading wire:target="saveAll">
                            ⏳ {{ __('translations.saving') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
