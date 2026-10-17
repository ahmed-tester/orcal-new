<div
    x-data="sidebarSearchComponent()"
    x-init="initSearch()"
    x-show="$store.sidebar.isOpen"
    x-cloak
    class="fi-sidebar-search-wrapper"
>
    <!-- Search Input Box -->
    <div class="fi-sidebar-search-field">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="fi-sidebar-search-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>

        <input
            type="text"
            x-model="query"
            x-on:input="filterNavigation()"
            x-on:keydown.escape="clearSearch()"
            placeholder="{{ __('reports.sidebar.search_placeholder') }}"
            class="fi-sidebar-search-input"
            aria-label="{{ __('reports.sidebar.search_label') }}"
            dir="auto"
        />

        <button
            type="button"
            x-show="query.length > 0"
            x-on:click="clearSearch()"
            class="fi-sidebar-search-clear"
            title="{{ __('reports.sidebar.clear_search') }}"
            style="display: none;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
        </button>
    </div>

    <!-- Collapse / Expand All Buttons (Left side) -->
    <div class="fi-sidebar-search-btn-group">
        <button
            type="button"
            x-on:click="collapseAll()"
            title="{{ __('reports.sidebar.collapse_all') }}"
            aria-label="{{ __('reports.sidebar.collapse_all') }}"
            class="fi-sidebar-search-btn"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        <button
            type="button"
            x-on:click="expandAll()"
            title="{{ __('reports.sidebar.expand_all') }}"
            aria-label="{{ __('reports.sidebar.expand_all') }}"
            class="fi-sidebar-search-btn"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</div>

<!-- No Results Notice -->
<div
    id="fi-sidebar-search-no-results"
    style="display: none;"
    class="fi-sidebar-search-empty"
>
    <span>{{ __('reports.sidebar.no_results') }}</span>
</div>

<style>
    .fi-sidebar-search-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem 0.85rem 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 0.5rem;
    }

    .fi-sidebar-search-btn-group {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-shrink: 0;
        direction: ltr;
    }

    .fi-sidebar-search-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        padding: 0;
    }

    .fi-sidebar-search-btn:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.3);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .fi-sidebar-search-btn:active {
        transform: translateY(0);
        background: rgba(255, 255, 255, 0.12);
    }

    .fi-sidebar-search-field {
        position: relative;
        flex: 1;
        display: flex;
        align-items: center;
        min-width: 0;
    }

    .fi-sidebar-search-icon {
        position: absolute;
        inset-inline-start: 10px;
        width: 15px;
        height: 15px;
        color: rgba(255, 255, 255, 0.5);
        pointer-events: none;
        transition: color 0.15s ease-in-out;
    }

    .fi-sidebar-search-field:focus-within .fi-sidebar-search-icon {
        color: #ffffff;
    }

    .fi-sidebar-search-input {
        width: 100%;
        height: 32px;
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid rgba(255, 255, 255, 0.16) !important;
        border-radius: 8px !important;
        color: #ffffff !important;
        font-size: 0.815rem !important;
        padding-inline-start: 32px !important;
        padding-inline-end: 28px !important;
        outline: none !important;
        transition: all 0.15s ease-in-out !important;
        box-sizing: border-box !important;
    }

    .fi-sidebar-search-input::placeholder {
        color: rgba(255, 255, 255, 0.45) !important;
        font-size: 0.815rem;
    }

    .fi-sidebar-search-input:hover {
        border-color: rgba(255, 255, 255, 0.28) !important;
        background: rgba(255, 255, 255, 0.11) !important;
    }

    .fi-sidebar-search-input:focus {
        border-color: rgba(255, 255, 255, 0.45) !important;
        background: rgba(255, 255, 255, 0.14) !important;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1) !important;
    }

    .fi-sidebar-search-clear {
        position: absolute;
        inset-inline-end: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        color: #ffffff;
        cursor: pointer;
        padding: 0;
        transition: background 0.15s ease-in-out;
    }

    .fi-sidebar-search-clear:hover {
        background: rgba(255, 255, 255, 0.35);
    }

    .fi-sidebar-search-empty {
        text-align: center;
        padding: 1.25rem 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.825rem;
        font-weight: 500;
    }
</style>

<script>
    function sidebarSearchComponent() {
        return {
            query: '',

            initSearch() {
                document.addEventListener('livewire:navigated', () => {
                    if (this.query) {
                        this.filterNavigation();
                    }
                });
            },

            normalize(text) {
                if (!text) return '';
                return text
                    .toString()
                    .toLowerCase()
                    .replace(/[\u064B-\u065F\u0670]/g, '') // Remove Arabic diacritics / tashkeel
                    .replace(/[إأآا]/g, 'ا')
                    .replace(/ة/g, 'ه')
                    .replace(/ى/g, 'ي')
                    .replace(/[\s\-_]+/g, ' ')
                    .trim();
            },

            getAllGroups() {
                return Array.from(document.querySelectorAll('.fi-main-sidebar .fi-sidebar-group'));
            },

            collapseAll() {
                const groups = this.getAllGroups();
                const allLabels = groups
                    .map(g => g.dataset.groupLabel)
                    .filter(Boolean);

                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').collapsedGroups = allLabels;
                }

                localStorage.setItem('collapsedGroups', JSON.stringify(allLabels));

                groups.forEach(g => {
                    if (g.dataset.groupLabel) {
                        g.classList.add('fi-collapsed');
                        const items = g.querySelector('.fi-sidebar-group-items');
                        if (items) {
                            items.style.display = 'none';
                        }
                    }
                });
            },

            expandAll() {
                const groups = this.getAllGroups();

                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').collapsedGroups = [];
                }

                localStorage.setItem('collapsedGroups', JSON.stringify([]));

                groups.forEach(g => {
                    g.classList.remove('fi-collapsed');
                    const items = g.querySelector('.fi-sidebar-group-items');
                    if (items) {
                        items.style.display = '';
                    }
                });
            },

            filterNavigation() {
                const q = this.normalize(this.query);
                const groups = this.getAllGroups();
                const emptyNotice = document.getElementById('fi-sidebar-search-no-results');
                let totalMatches = 0;

                if (!q) {
                    // Reset everything to normal state
                    groups.forEach(group => {
                        group.style.display = '';
                        const itemsContainer = group.querySelector('.fi-sidebar-group-items');
                        if (itemsContainer) {
                            itemsContainer.style.display = '';
                        }
                        group.querySelectorAll('.fi-sidebar-item').forEach(item => {
                            item.style.display = '';
                        });
                    });

                    if (emptyNotice) emptyNotice.style.display = 'none';

                    // Re-apply collapsed state from storage
                    const collapsed = (window.Alpine && window.Alpine.store('sidebar')?.collapsedGroups) || [];
                    groups.forEach(group => {
                        const label = group.dataset.groupLabel;
                        if (label && collapsed.includes(label)) {
                            group.classList.add('fi-collapsed');
                            const items = group.querySelector('.fi-sidebar-group-items');
                            if (items) items.style.display = 'none';
                        } else {
                            group.classList.remove('fi-collapsed');
                        }
                    });

                    return;
                }

                // Filter groups and items
                groups.forEach(group => {
                    const groupLabelEl = group.querySelector('.fi-sidebar-group-label');
                    const groupLabel = this.normalize(group.dataset.groupLabel || (groupLabelEl ? groupLabelEl.textContent : ''));
                    const groupMatches = groupLabel.includes(q);

                    const items = group.querySelectorAll('.fi-sidebar-item');
                    let groupHasMatchingItem = false;

                    items.forEach(item => {
                        const itemLabelEl = item.querySelector('.fi-sidebar-item-label');
                        const itemLabel = this.normalize(itemLabelEl ? itemLabelEl.textContent : item.textContent);

                        if (groupMatches || itemLabel.includes(q)) {
                            item.style.display = '';
                            groupHasMatchingItem = true;
                            totalMatches++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    const itemsContainer = group.querySelector('.fi-sidebar-group-items');

                    if (groupMatches || groupHasMatchingItem) {
                        group.style.display = '';
                        if (itemsContainer) {
                            itemsContainer.style.display = 'block';
                        }
                        group.classList.remove('fi-collapsed');
                    } else {
                        group.style.display = 'none';
                    }
                });

                if (emptyNotice) {
                    emptyNotice.style.display = totalMatches === 0 ? 'block' : 'none';
                }
            },

            clearSearch() {
                this.query = '';
                this.filterNavigation();
            }
        };
    }
</script>
