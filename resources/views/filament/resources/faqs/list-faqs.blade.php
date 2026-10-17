<x-filament-panels::page>

<style>
/* ═══════════════════════════════════════════════════
   FAQ PAGE  —  Full-width, static, searchable
═══════════════════════════════════════════════════ */

/* ── Search bar ─────────────────────────────────── */
.faq-search-wrap {
    position: sticky;
    top: 0;
    z-index: 20;
    padding: .6rem 0 .8rem;
    background: var(--body-bg, #f9fafb);
}
.dark .faq-search-wrap {
    background: var(--gray-950, #0c111d);
}
.faq-search-inner {
    position: relative;
    max-width: 100%;
}
.faq-search-icon {
    position: absolute;
    top: 50%;
    right: .85rem;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--gray-400);
    pointer-events: none;
}
[dir="ltr"] .faq-search-icon {
    right: auto;
    left: .85rem;
}
.faq-search-input {
    width: 100%;
    padding: .55rem 1rem .55rem 2.6rem;
    padding-right: 2.6rem;
    padding-left: 1rem;
    border-radius: .6rem;
    border: 1.5px solid var(--gray-200);
    background: #fff;
    font-size: .875rem;
    color: var(--gray-800);
    outline: none;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    transition: border-color .2s, box-shadow .2s;
    direction: rtl;
}
[dir="ltr"] .faq-search-input {
    padding-right: 1rem;
    padding-left: 2.6rem;
    direction: ltr;
}
.dark .faq-search-input {
    background: var(--gray-800);
    border-color: var(--gray-700);
    color: var(--gray-100);
}
.faq-search-input:focus {
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(var(--primary-500-rgb,8,44,80),.15);
}
.faq-search-input::placeholder { color: var(--gray-400); }

/* ── Results counter ────────────────────────────── */
.faq-results-bar {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .82rem;
    color: var(--gray-500);
    margin-bottom: .75rem;
}
.faq-results-bar strong { color: var(--primary-600); font-weight: 700; }
.dark .faq-results-bar strong { color: var(--primary-400); }

/* ── FAQ grid ───────────────────────────────────── */
.faq-grid {
    display: flex;
    flex-direction: column;
    gap: .9rem;
    width: 100%;
}

/* ── FAQ card ───────────────────────────────────── */
.faq-card {
    width: 100%;
    background: #fff;
    border: 1.5px solid var(--gray-200);
    border-radius: 1rem;
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
}
.dark .faq-card {
    background: var(--gray-800);
    border-color: var(--gray-700);
}
.faq-card:hover {
    border-color: var(--primary-400);
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
}
.dark .faq-card:hover { border-color: var(--primary-500); }

/* Card header — question row */
.faq-card-header {
    display: flex;
    align-items: flex-start;
    gap: .9rem;
    padding: 1.1rem 1.5rem;
}
.faq-num {
    flex-shrink: 0;
    min-width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
    box-shadow: 0 2px 8px rgba(var(--primary-500-rgb,8,44,80),.35);
}
.faq-question {
    flex: 1;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1.6;
}
.dark .faq-question { color: var(--gray-50); }

/* Inactive badge */
.faq-inactive {
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    font-size: .7rem;
    font-weight: 600;
    color: #d97706;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 9999px;
    padding: .1rem .5rem;
    margin-inline-start: .5rem;
    vertical-align: middle;
}
.dark .faq-inactive {
    background: rgba(217,119,6,.12);
    color: #fbbf24;
    border-color: rgba(217,119,6,.3);
}

/* Divider */
.faq-divider {
    height: 1px;
    background: var(--gray-100);
    margin: 0 1.5rem;
}
.dark .faq-divider { background: var(--gray-700); }

/* Answer body */
.faq-answer {
    padding: 1rem 1.5rem 1rem 1.5rem;
    padding-inline-start: 4.1rem; /* align under question text */
    color: var(--gray-600);
    font-size: .95rem;
    line-height: 1.8;
}
.dark .faq-answer { color: var(--gray-300); }
.faq-answer p  { margin: .5rem 0; }
.faq-answer ul,
.faq-answer ol { padding-inline-start: 1.4rem; margin: .4rem 0; }
.faq-answer li { margin: .3rem 0; }
.faq-answer strong { color: var(--gray-800); }
.dark .faq-answer strong { color: var(--gray-100); }
.faq-answer a  { color: var(--primary-600); text-decoration: underline; }

/* Highlight search term */
.faq-highlight {
    background: #fef08a;
    color: #713f12;
    border-radius: 2px;
    padding: 0 2px;
}
.dark .faq-highlight {
    background: rgba(234,179,8,.25);
    color: #fde047;
}

/* ── Footer: actions row ────────────────────────── */
.faq-card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .55rem;
    padding: .7rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
}
.dark .faq-card-footer {
    background: var(--gray-800);
    border-top-color: var(--gray-700);
}

.faq-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem 1rem;
    border-radius: .5rem;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all .18s;
    background: none;
}
.faq-btn svg { width: 15px; height: 15px; flex-shrink: 0; }

.faq-btn-edit {
    color: var(--primary-700);
    background: var(--primary-50);
    border-color: var(--primary-200);
}
.faq-btn-edit:hover {
    background: var(--primary-100);
    border-color: var(--primary-400);
}
.dark .faq-btn-edit {
    background: rgba(var(--primary-500-rgb,8,44,80),.15);
    color: var(--primary-300);
    border-color: rgba(var(--primary-500-rgb,8,44,80),.3);
}
.dark .faq-btn-edit:hover {
    background: rgba(var(--primary-500-rgb,8,44,80),.28);
}

.faq-btn-delete {
    color: #e11d48;
    background: #fff1f2;
    border-color: #fecdd3;
}
.faq-btn-delete:hover {
    background: #ffe4e6;
    border-color: #fb7185;
}
.dark .faq-btn-delete {
    background: rgba(225,29,72,.12);
    color: #fb7185;
    border-color: rgba(225,29,72,.25);
}
.dark .faq-btn-delete:hover {
    background: rgba(225,29,72,.22);
}

/* ── Empty state ─────────────────────────────────── */
.faq-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray-400);
}
.faq-empty svg {
    width: 56px;
    height: 56px;
    margin: 0 auto 1rem;
    opacity: .45;
}
.faq-empty-title  { font-size: 1rem; font-weight: 600; color: var(--gray-500); }
.faq-empty-sub    { font-size: .875rem; margin-top: .3rem; }
</style>

{{-- ══════════════════════════════════════════════════
     SEARCH BAR  (Livewire reactive)
══════════════════════════════════════════════════ --}}
<div class="faq-search-wrap">
    <div class="faq-search-inner">
        {{-- magnifier icon --}}
        <svg class="faq-search-icon" xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input
            type="text"
            class="faq-search-input"
            placeholder="{{ __('faq.search_placeholder') }}"
            wire:model.live.debounce.300ms="search"
            autocomplete="off"
        >
    </div>
</div>

@php
    $faqs  = $this->getFaqs();
    $total = \App\Models\Faq::count();
    $found = $faqs->count();
    $term  = trim($this->search);
@endphp

{{-- ── Results counter ─────────────────────────── --}}
@if($term !== '')
    <div class="faq-results-bar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width:15px;height:15px;flex-shrink:0">
            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
        </svg>
        {{ __('faq.search_results', ['found' => $found, 'total' => $total, 'term' => $term]) }}
    </div>
@endif

{{-- ══════════════════════════════════════════════════
     FAQ CARDS  (always expanded, no toggle)
══════════════════════════════════════════════════ --}}
@if($faqs->isEmpty())
    <div class="faq-empty">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>
        </svg>
        @if($term !== '')
            <p class="faq-empty-title">{{ __('faq.no_results_title') }}</p>
            <p class="faq-empty-sub">{{ __('faq.no_results_sub', ['term' => $term]) }}</p>
        @else
            <p class="faq-empty-title">{{ __('faq.empty_title') }}</p>
            <p class="faq-empty-sub">{{ __('faq.empty_subtitle') }}</p>
        @endif
    </div>
@else

<div class="faq-grid">
    @foreach($faqs as $index => $faq)
    <div class="faq-card">

        {{-- ── Question ─────────────────────────── --}}
        <div class="faq-card-header">
            <span class="faq-num">{{ $index + 1 }}</span>
            <span class="faq-question">
                @if($term !== '')
                    {!! preg_replace('/(' . preg_quote(e($term), '/') . ')/iu',
                        '<mark class="faq-highlight">$1</mark>',
                        e($faq->question)) !!}
                @else
                    {{ $faq->question }}
                @endif
                @if(!$faq->is_active)
                    <span class="faq-inactive">{{ __('faq.inactive_label') }}</span>
                @endif
            </span>
        </div>

        <div class="faq-divider"></div>

        {{-- ── Answer (always visible) ─────────── --}}
        <div class="faq-answer">
            @if($term !== '')
                {!! preg_replace('/(' . preg_quote($term, '/') . ')/iu',
                    '<mark class="faq-highlight">$1</mark>',
                    $faq->answer) !!}
            @else
                {!! $faq->answer !!}
            @endif
        </div>

        {{-- ── Footer: Edit / Delete ───────────── --}}
        @if(auth()->user()?->can('update_faq') || auth()->user()?->can('delete_faq'))
        <div class="faq-card-footer">

            {{-- Edit --}}
            @can('update_faq')
            <a href="{{ \App\Filament\Resources\Faqs\FaqResource::getUrl('edit', ['record' => $faq]) }}"
               class="faq-btn faq-btn-edit">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                    <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                </svg>
                {{ __('faq.actions.edit') }}
            </a>
            @endcan

            {{-- Delete --}}
            @can('delete_faq')
            <button
                type="button"
                class="faq-btn faq-btn-delete"
                wire:click="deleteFaq({{ $faq->id }})"
                wire:confirm="{{ __('faq.confirm_delete') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                </svg>
                {{ __('faq.actions.delete') }}
            </button>
            @endcan

        </div>
        @endif
    </div>
    @endforeach
</div>

@endif

</x-filament-panels::page>
