@php
    $isDashboard = request()->routeIs('filament.admin.pages.dashboard')
        || request()->routeIs('filament.admin.pages.home')
        || url()->current() === rtrim(filament()->getUrl(), '/');

    if ($isDashboard) {
        return;
    }

    $backUrl = null;
    $livewire = $this ?? null;

    if ($livewire && method_exists($livewire, 'getResource')) {
        try {
            $resource = $livewire::getResource();
            // On Create, Edit, or View page, return directly to the resource index
            if (! ($livewire instanceof \Filament\Resources\Pages\ListRecords)) {
                $backUrl = $resource::getUrl('index');
            }
        } catch (\Throwable $e) {
            $backUrl = null;
        }
    }
@endphp

<div class="fi-header-back-button-wrap no-print" style="display: inline-flex; align-items: center; margin-inline-end: 0.5rem;">
    @if($backUrl)
        <a
            href="{{ $backUrl }}"
            class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-btn-outlined"
            style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 700; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; text-decoration: none; transition: all 0.15s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
            onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8'; this.style.color='#0f172a';"
            onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'; this.style.color='#475569';"
            title="{{ __('admin.back') }}"
        >
            <svg style="width: 1.05rem; height: 1.05rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
            <span>{{ __('admin.back') }}</span>
        </a>
    @else
        <button
            type="button"
            onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ filament()->getUrl() }}'; }"
            class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-btn-outlined"
            style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; border-radius: 0.5rem; font-size: 0.825rem; font-weight: 700; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
            onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8'; this.style.color='#0f172a';"
            onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'; this.style.color='#475569';"
            title="{{ __('admin.back') }}"
        >
            <svg style="width: 1.05rem; height: 1.05rem;" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
            <span>{{ __('admin.back') }}</span>
        </button>
    @endif
</div>

