@php
    $user = auth()->user();
    if (! $user) {
        return;
    }

    $favorites = $user->favorites()
        ->get(['id', 'title', 'url', 'icon', 'sort_order'])
        ->map(fn ($fav) => [
            'id' => $fav->id,
            'title' => $fav->title,
            'url' => $fav->url,
            'icon' => $fav->icon,
        ])
        ->values()
        ->all();

    $currentNormalizedUrl = \App\Models\UserFavorite::normalizeUrl(url()->current());
    $sidebarCounts = \App\Services\SidebarBadgeService::getCountsByUrl();
@endphp

<div
    id="fi-sidebar-favorites-root"
    x-data="{ open: localStorage.getItem('fi_favorites_open') !== '0' }"
    x-show="$store.sidebar.isOpen"
    class="fi-sidebar-favorites-wrapper"
    style="{{ count($favorites) === 0 ? 'display: none;' : '' }}"
    data-toggle-url="{{ route('favorites.toggle') }}"
    data-destroy-base="{{ url('/favorites') }}"
    data-csrf="{{ csrf_token() }}"
    data-current-url="{{ $currentNormalizedUrl }}"
    data-remove-title="{{ __('admin.remove_from_favorites') }}"
>
    <button
        type="button"
        x-on:click="open = !open; localStorage.setItem('fi_favorites_open', open ? '1' : '0')"
        class="fi-sidebar-favorites-header"
    >
        <span class="fi-sidebar-favorites-heading">
            <svg class="fi-sidebar-favorites-heading-star" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
            </svg>
            <span>{{ __('admin.favorites') }}</span>
            <span id="fi-sidebar-favorites-count" class="fi-sidebar-favorites-count">{{ count($favorites) }}</span>
        </span>
        <svg
            class="fi-sidebar-favorites-chevron"
            :class="{ 'is-collapsed': !open }"
            viewBox="0 0 20 20"
            fill="currentColor"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <ul id="fi-sidebar-favorites-list" x-show="open" class="fi-sidebar-favorites-list">
        @foreach($favorites as $fav)
            @php
                $isActiveFav = $fav['url'] === $currentNormalizedUrl;
                $favCount = $sidebarCounts[rtrim(url($fav['url']), '/')] ?? null;
            @endphp
            <li class="fi-sidebar-fav-item {{ $isActiveFav ? 'is-active' : '' }}" data-fav-id="{{ $fav['id'] }}" data-fav-url="{{ $fav['url'] }}">
                <a href="{{ url($fav['url']) }}" class="fi-sidebar-fav-link" title="{{ $fav['title'] }}">
                    <svg class="fi-sidebar-fav-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                    </svg>
                    <span class="fi-sidebar-fav-label">{{ $fav['title'] }}</span>
                    @if($favCount !== null && $favCount > 0)
                        <span class="fi-sidebar-fav-badge">{{ $favCount }}</span>
                    @endif
                </a>
                <button
                    type="button"
                    class="fi-sidebar-fav-remove"
                    title="{{ __('admin.remove_from_favorites') }}"
                    onclick="window.removeUserFavorite && window.removeUserFavorite({{ $fav['id'] }}, this)"
                >
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px;">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </li>
        @endforeach
    </ul>
</div>

<style>
    .fi-sidebar-favorites-wrapper {
        padding: 0.45rem 0.75rem 0.65rem 0.75rem;
        margin-bottom: 0.45rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .fi-sidebar-favorites-header {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.35rem 0.45rem;
        border: none;
        background: transparent;
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: background 0.15s ease;
    }

    .fi-sidebar-favorites-header:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .fi-sidebar-favorites-heading {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .fi-sidebar-favorites-heading-star {
        width: 15px;
        height: 15px;
        color: #fbbf24;
        flex-shrink: 0;
    }

    .fi-sidebar-favorites-count {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border-radius: 9999px;
        padding: 0.05rem 0.45rem;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .fi-sidebar-favorites-chevron {
        width: 16px;
        height: 16px;
        color: rgba(255, 255, 255, 0.75);
        transition: transform 0.15s ease;
    }

    .fi-sidebar-favorites-chevron.is-collapsed {
        transform: rotate(90deg);
    }

    .fi-sidebar-favorites-list {
        list-style: none;
        margin: 0.35rem 0 0 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        max-height: 240px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
    }

    .fi-sidebar-fav-item {
        display: flex;
        align-items: center;
        border-radius: 0.5rem;
        transition: background 0.15s ease;
    }

    .fi-sidebar-fav-item:not(.is-active):hover {
        background: rgba(255, 255, 255, 0.12);
    }

    .fi-sidebar-fav-item.is-active {
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .fi-sidebar-fav-link {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.45rem 0.6rem;
        text-decoration: none;
        color: #ffffff;
        font-size: 0.84rem;
        font-weight: 500;
    }

    .fi-sidebar-fav-item.is-active .fi-sidebar-fav-link {
        color: #04386c;
        font-weight: 700;
    }

    .fi-sidebar-fav-icon {
        width: 15px;
        height: 15px;
        color: #fbbf24;
        flex-shrink: 0;
    }

    .fi-sidebar-fav-item.is-active .fi-sidebar-fav-icon {
        color: #d97706;
    }

    .fi-sidebar-fav-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .fi-sidebar-fav-badge {
        margin-inline-start: auto;
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border-radius: 9999px;
        padding: 0.05rem 0.45rem;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1.35;
        flex-shrink: 0;
    }

    .fi-sidebar-fav-item.is-active .fi-sidebar-fav-badge {
        background: rgba(4, 56, 108, 0.12);
        color: #04386c;
    }

    .fi-sidebar-fav-remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        margin-inline-end: 0.3rem;
        border: none;
        border-radius: 0.375rem;
        background: transparent;
        color: rgba(255, 255, 255, 0.55);
        cursor: pointer;
        opacity: 0;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }

    .fi-sidebar-fav-item:hover .fi-sidebar-fav-remove,
    .fi-sidebar-fav-item:focus-within .fi-sidebar-fav-remove {
        opacity: 1;
    }

    .fi-sidebar-fav-remove:hover {
        background: rgba(239, 68, 68, 0.22);
        color: #fecaca;
    }

    .fi-sidebar-fav-item.is-active .fi-sidebar-fav-remove {
        color: #64748b;
    }

    .fi-sidebar-fav-item.is-active .fi-sidebar-fav-remove:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    @media (hover: none) {
        .fi-sidebar-fav-remove {
            opacity: 0.85;
        }
    }
</style>

<script>
    (function () {
        if (window.__userFavoritesInitialized) {
            return;
        }
        window.__userFavoritesInitialized = true;

        function getRoot() {
            return document.getElementById('fi-sidebar-favorites-root');
        }

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || getRoot()?.dataset.csrf
                || '';
        }

        function showFavoriteToast(message, isAdded) {
            if (window.FilamentNotification) {
                const notif = new window.FilamentNotification().title(message);
                if (isAdded) {
                    notif.success();
                } else {
                    notif.warning();
                }
                notif.send();
                return;
            }

            const existing = document.getElementById('fi-favorite-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'fi-favorite-toast';
            toast.textContent = message;
            toast.style.cssText = 'position:fixed;bottom:1.25rem;inset-inline-end:1.25rem;z-index:9999;padding:0.65rem 1rem;border-radius:0.5rem;font-size:0.85rem;font-weight:700;color:#fff;box-shadow:0 10px 25px rgba(0,0,0,0.2);background:' + (isAdded ? '#059669' : '#d97706') + ';';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        function syncHeaderButtonState(favoritedUrls) {
            const btn = document.getElementById('fi-page-favorite-toggle-btn');
            if (!btn) return;

            const isFav = favoritedUrls.includes(btn.dataset.url);
            btn.dataset.favorited = isFav ? '1' : '0';
            btn.classList.toggle('is-favorited', isFav);
            btn.title = isFav ? btn.dataset.titleRemove : btn.dataset.titleAdd;

            const outline = btn.querySelector('.fi-favorite-star-outline');
            const solid = btn.querySelector('.fi-favorite-star-solid');
            const label = btn.querySelector('.fi-favorite-btn-label');

            if (outline) outline.style.display = isFav ? 'none' : '';
            if (solid) solid.style.display = isFav ? '' : 'none';
            if (label) label.textContent = isFav ? btn.dataset.labelAdded : btn.dataset.labelAdd;
        }

        function renderSidebarFavorites(favorites) {
            const root = getRoot();
            if (!root) return;

            const list = document.getElementById('fi-sidebar-favorites-list');
            const countEl = document.getElementById('fi-sidebar-favorites-count');
            const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
            const currentUrl = currentPath.startsWith('/livewire')
                ? (root.dataset.currentUrl || currentPath)
                : currentPath;
            const removeTitle = root.dataset.removeTitle || '';

            if (countEl) {
                countEl.textContent = String(favorites.length);
            }

            root.style.display = favorites.length > 0 ? '' : 'none';

            if (!list) return;
            list.innerHTML = '';

            favorites.forEach((fav) => {
                const li = document.createElement('li');
                li.className = 'fi-sidebar-fav-item' + (fav.url === currentUrl ? ' is-active' : '');
                li.dataset.favId = String(fav.id);
                li.dataset.favUrl = fav.url;

                const a = document.createElement('a');
                a.href = fav.url;
                a.className = 'fi-sidebar-fav-link';
                a.title = fav.title;

                a.innerHTML = '<svg class="fi-sidebar-fav-icon" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" /></svg>';

                const span = document.createElement('span');
                span.className = 'fi-sidebar-fav-label';
                span.textContent = fav.title;
                a.appendChild(span);

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'fi-sidebar-fav-remove';
                delBtn.title = removeTitle;
                delBtn.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>';
                delBtn.addEventListener('click', () => window.removeUserFavorite(fav.id, delBtn));

                li.appendChild(a);
                li.appendChild(delBtn);
                list.appendChild(li);
            });

            syncHeaderButtonState(favorites.map(f => f.url));
        }

        window.toggleUserFavorite = async function (btn) {
            const root = getRoot();
            const toggleUrl = root?.dataset.toggleUrl || '/favorites/toggle';
            btn.classList.add('is-loading');
            btn.disabled = true;

            let resolvedUrl = btn.dataset.url || '';
            if (!resolvedUrl || resolvedUrl.startsWith('/livewire')) {
                resolvedUrl = window.location.pathname.replace(/\/+$/, '') || '/';
            }

            let resolvedTitle = btn.dataset.title || '';
            if (btn.dataset.hasServerTitle !== '1' || !resolvedTitle) {
                const headingText = document.querySelector('.fi-header-heading')?.textContent?.trim();
                const docTitle = document.title ? document.title.split(' - ')[0].trim() : '';
                resolvedTitle = headingText || docTitle || resolvedTitle;
            }

            try {
                const response = await fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        url: resolvedUrl,
                        title: resolvedTitle,
                        icon: btn.dataset.icon,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const data = await response.json();
                renderSidebarFavorites(data.favorites || []);
                showFavoriteToast(data.message || '', Boolean(data.favorited));
            } catch (err) {
                console.error(err);
            } finally {
                btn.classList.remove('is-loading');
                btn.disabled = false;
            }
        };

        window.removeUserFavorite = async function (id, triggerBtn) {
            const root = getRoot();
            const base = root?.dataset.destroyBase || '/favorites';
            if (triggerBtn) triggerBtn.disabled = true;

            try {
                const response = await fetch(base + '/' + id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });

                if (!response.ok) {
                    throw new Error('Delete failed');
                }

                const data = await response.json();
                renderSidebarFavorites(data.favorites || []);
                showFavoriteToast(data.message || '', false);
            } catch (err) {
                console.error(err);
                if (triggerBtn) triggerBtn.disabled = false;
            }
        };
    })();
</script>
