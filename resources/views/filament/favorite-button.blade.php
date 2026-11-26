@php
    $isDashboard = request()->routeIs('filament.admin.pages.dashboard')
        || request()->routeIs('filament.admin.pages.home')
        || url()->current() === rtrim(filament()->getUrl(), '/');

    if ($isDashboard || ! auth()->check()) {
        return;
    }

    $routeController = rescue(fn () => request()->route()?->getController(), null, false);
    $livewire = isset($this) ? $this : (is_object($routeController) ? $routeController : null);

    // During Livewire AJAX updates (/livewire/update), url()->current() points to Livewire's endpoint;
    // fall back to the Referer header so the button keeps the actual page URL.
    $rawUrl = url()->current();
    $rawPath = parse_url($rawUrl, PHP_URL_PATH) ?? '';
    if (request()->hasHeader('X-Livewire') || str_starts_with($rawPath, '/livewire')) {
        $rawUrl = request()->header('Referer') ?: $rawUrl;
    }
    $currentUrl = \App\Models\UserFavorite::normalizeUrl($rawUrl);

    $pageTitle = '';
    if ($livewire && method_exists($livewire, 'getTitle')) {
        $pageTitle = trim(strip_tags((string) rescue(fn () => $livewire->getTitle(), '', false)));
    }
    if ($pageTitle === '' && $livewire && method_exists($livewire, 'getHeading')) {
        $pageTitle = trim(strip_tags((string) rescue(fn () => $livewire->getHeading(), '', false)));
    }
    if (
        $pageTitle !== ''
        && $livewire
        && ($livewire instanceof \Filament\Resources\Pages\EditRecord || $livewire instanceof \Filament\Resources\Pages\ViewRecord)
        && method_exists($livewire, 'getRecordTitle')
    ) {
        $recordTitle = rescue(fn () => trim(strip_tags((string) $livewire->getRecordTitle())), '', false);
        if ($recordTitle !== '') {
            $pageTitle .= ' - ' . $recordTitle;
        }
    }
    $hasServerTitle = $pageTitle !== '';
    if ($pageTitle === '') {
        $pageTitle = __('admin.favorites');
    }
    $pageTitle = \Illuminate\Support\Str::limit($pageTitle, 145, '...');

    $pageIcon = null;
    if ($livewire && method_exists($livewire, 'getResource')) {
        $pageIcon = rescue(fn () => $livewire::getResource()::getNavigationIcon(), null, false);
    } elseif ($livewire && method_exists($livewire, 'getNavigationIcon')) {
        $pageIcon = rescue(fn () => $livewire::getNavigationIcon(), null, false);
    }
    if ($pageIcon instanceof \BackedEnum) {
        $pageIcon = $pageIcon->value;
    }
    $pageIcon = is_string($pageIcon) && $pageIcon !== '' ? $pageIcon : 'heroicon-o-star';

    $isFavorited = auth()->user()
        ->favorites()
        ->where('url', $currentUrl)
        ->exists();
@endphp

<div class="fi-header-favorite-button-wrap no-print" style="display: inline-flex; align-items: center; margin-inline-end: 0.5rem;">
    <button
        type="button"
        id="fi-page-favorite-toggle-btn"
        data-favorite-toggle="1"
        data-url="{{ $currentUrl }}"
        data-title="{{ $pageTitle }}"
        data-has-server-title="{{ $hasServerTitle ? '1' : '0' }}"
        data-icon="{{ $pageIcon }}"
        data-favorited="{{ $isFavorited ? '1' : '0' }}"
        data-label-add="{{ __('admin.add_to_favorites') }}"
        data-label-added="{{ __('admin.in_favorites') }}"
        data-title-add="{{ __('admin.add_to_favorites') }}"
        data-title-remove="{{ __('admin.remove_from_favorites') }}"
        onclick="window.toggleUserFavorite && window.toggleUserFavorite(this)"
        class="fi-btn fi-btn-size-md fi-favorite-header-btn {{ $isFavorited ? 'is-favorited' : '' }}"
        title="{{ $isFavorited ? __('admin.remove_from_favorites') : __('admin.add_to_favorites') }}"
    >
        <svg class="fi-favorite-star-outline" style="width: 1.05rem; height: 1.05rem; {{ $isFavorited ? 'display:none;' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
        <svg class="fi-favorite-star-solid" style="width: 1.05rem; height: 1.05rem; {{ $isFavorited ? '' : 'display:none;' }}" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
        </svg>
        <span class="fi-favorite-btn-label">{{ $isFavorited ? __('admin.in_favorites') : __('admin.add_to_favorites') }}</span>
    </button>
</div>

<style>
    .fi-favorite-header-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 0.5rem;
        font-size: 0.825rem;
        font-weight: 700;
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .fi-favorite-header-btn:hover {
        background: #fffbeb;
        border-color: #f59e0b;
        color: #b45309;
    }

    .fi-favorite-header-btn.is-favorited {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #b45309;
    }

    .fi-favorite-header-btn.is-favorited:hover {
        background: #fde68a;
        border-color: #d97706;
        color: #92400e;
    }

    .fi-favorite-header-btn.is-loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .dark .fi-favorite-header-btn {
        background: #1e293b;
        color: #e2e8f0;
        border-color: #334155;
    }

    .dark .fi-favorite-header-btn:hover {
        background: rgba(245, 158, 11, 0.15);
        border-color: #f59e0b;
        color: #fbbf24;
    }

    .dark .fi-favorite-header-btn.is-favorited {
        background: rgba(245, 158, 11, 0.22);
        border-color: #f59e0b;
        color: #fcd34d;
    }
</style>
