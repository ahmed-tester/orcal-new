<style>
    /* Custom Navy Blue Sidebar Theme */
    :root {
        --custom-sidebar-bg: #04386c;
        --custom-sidebar-active-bg: #ffffff;
        --custom-sidebar-active-text: #04386c;
        --custom-sidebar-text: #ffffff;
        --custom-sidebar-text-muted: rgba(255, 255, 255, 0.82);
        --custom-sidebar-hover-bg: rgba(255, 255, 255, 0.12);
    }

    /* Main Sidebar Container (Light & Dark mode) */
    #fi-main-sidebar,
    .fi-main-sidebar,
    aside.fi-sidebar,
    .dark #fi-main-sidebar,
    .dark .fi-main-sidebar,
    .dark aside.fi-sidebar {
        background-color: var(--custom-sidebar-bg) !important;
        background: linear-gradient(180deg, #054584 0%, #032d57 100%) !important;
        border-inline-end: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    /* Header, Footer & Navigation in Sidebar */
    .fi-main-sidebar .fi-sidebar-header,
    .fi-main-sidebar .fi-sidebar-header-ctn,
    .fi-main-sidebar .fi-sidebar-footer,
    .fi-main-sidebar .fi-sidebar-nav,
    .dark .fi-main-sidebar .fi-sidebar-header,
    .dark .fi-main-sidebar .fi-sidebar-header-ctn,
    .dark .fi-main-sidebar .fi-sidebar-footer,
    .dark .fi-main-sidebar .fi-sidebar-nav {
        background-color: transparent !important;
        background: transparent !important;
    }

    .fi-main-sidebar .fi-sidebar-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .fi-main-sidebar .fi-logo,
    .fi-main-sidebar .fi-sidebar-header span,
    .fi-main-sidebar .fi-sidebar-header a,
    .fi-main-sidebar .fi-sidebar-header .fi-icon,
    .fi-main-sidebar .fi-sidebar-header svg {
        color: #ffffff !important;
    }

    /* Group Labels & Group Toggle Buttons */
    .fi-main-sidebar .fi-sidebar-group-label,
    .fi-main-sidebar .fi-sidebar-group-btn span,
    .fi-main-sidebar .fi-sidebar-group-btn,
    .dark .fi-main-sidebar .fi-sidebar-group-label,
    .dark .fi-main-sidebar .fi-sidebar-group-btn span,
    .dark .fi-main-sidebar .fi-sidebar-group-btn {
        color: var(--custom-sidebar-text) !important;
        font-weight: 600 !important;
    }

    .fi-main-sidebar .fi-sidebar-group-btn:hover {
        background-color: var(--custom-sidebar-hover-bg) !important;
        border-radius: 0.5rem !important;
    }

    .fi-main-sidebar .fi-sidebar-group-btn .fi-icon,
    .fi-main-sidebar .fi-sidebar-group-btn svg,
    .fi-main-sidebar .fi-sidebar-group-collapse-btn,
    .dark .fi-main-sidebar .fi-sidebar-group-btn .fi-icon,
    .dark .fi-main-sidebar .fi-sidebar-group-btn svg,
    .dark .fi-main-sidebar .fi-sidebar-group-collapse-btn {
        color: var(--custom-sidebar-text-muted) !important;
    }

    /* Standard Inactive Items */
    .fi-main-sidebar .fi-sidebar-item-btn {
        color: var(--custom-sidebar-text) !important;
        transition: all 0.15s ease-in-out !important;
        border-radius: 0.5rem !important;
        margin-bottom: 2px !important;
    }

    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-btn:hover,
    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-btn:focus,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-btn:hover,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-btn:focus {
        background-color: var(--custom-sidebar-hover-bg) !important;
        color: #ffffff !important;
    }

    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label {
        color: var(--custom-sidebar-text) !important;
    }

    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-icon,
    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-icon,
    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) svg,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-icon,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-icon,
    .dark .fi-main-sidebar .fi-sidebar-item:not(.fi-active) svg {
        color: var(--custom-sidebar-text) !important;
    }

    /* Active Menu Item (White card style with blue text and icon) */
    .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
    .fi-main-sidebar .fi-sidebar-item-active > .fi-sidebar-item-btn,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
    .dark .fi-main-sidebar .fi-sidebar-item-active > .fi-sidebar-item-btn {
        background-color: var(--custom-sidebar-active-bg) !important;
        color: var(--custom-sidebar-active-text) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        border-radius: 0.5rem !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        color: var(--custom-sidebar-active-text) !important;
        font-weight: 700 !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-icon,
    .fi-main-sidebar .fi-sidebar-item.fi-active svg,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-icon,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active svg {
        color: var(--custom-sidebar-active-text) !important;
    }

    /* Badges in Sidebar (Numbers like 1, 9, 35) */
    .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge,
    .fi-main-sidebar .fi-badge,
    .dark .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge,
    .dark .fi-main-sidebar .fi-badge {
        background-color: #ffffff !important;
        background: #ffffff !important;
        border-radius: 9999px !important;
        padding: 0.15rem 0.55rem !important;
        font-size: 0.78rem !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25) !important;
        border: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .fi-main-sidebar .fi-badge,
    .fi-main-sidebar .fi-badge span,
    .fi-main-sidebar .fi-badge-label,
    .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge,
    .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge span,
    .dark .fi-main-sidebar .fi-badge,
    .dark .fi-main-sidebar .fi-badge span,
    .dark .fi-main-sidebar .fi-badge-label,
    .dark .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge,
    .dark .fi-main-sidebar .fi-sidebar-item-badge-ctn .fi-badge span {
        color: var(--custom-sidebar-bg) !important;
        font-weight: 800 !important;
    }

    /* Badges when item is active */
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge {
        background-color: var(--custom-sidebar-bg) !important;
        background: var(--custom-sidebar-bg) !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge,
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge span,
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge-label,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge span,
    .dark .fi-main-sidebar .fi-sidebar-item.fi-active .fi-badge-label {
        color: #ffffff !important;
    }

    /* Grouped items tree borders / dots */
    .fi-main-sidebar .fi-sidebar-item-grouped-border-part,
    .fi-main-sidebar .fi-sidebar-item-grouped-border-part-not-first,
    .fi-main-sidebar .fi-sidebar-item-grouped-border-part-not-last {
        background-color: rgba(255, 255, 255, 0.3) !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-grouped-border-part {
        background-color: var(--custom-sidebar-active-text) !important;
    }

    /* Clean Scrollbar inside Sidebar */
    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar {
        width: 6px;
    }
    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }
    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.25);
        border-radius: 4px;
    }
    /* Responsive Toggle Buttons & Button Groups (Flex & Prevent Overflow) */
    .fi-fo-toggle-buttons.fi-btn-group,
    .fi-btn-group {
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    .fi-fo-toggle-buttons.fi-btn-group > .fi-btn,
    .fi-btn-group > .fi-btn {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        white-space: normal !important;
        text-align: center !important;
        word-break: break-word !important;
        height: auto !important;
        padding-inline: 0.65rem !important;
        box-sizing: border-box !important;
    }

    .fi-fo-toggle-buttons-wrp {
        max-width: 100% !important;
        width: 100% !important;
        overflow: hidden !important;
    }

    @media (max-width: 640px) {
        .fi-fo-toggle-buttons.fi-btn-group {
            flex-direction: column !important;
        }
        .fi-fo-toggle-buttons.fi-btn-group > .fi-btn {
            border-radius: 0.5rem !important;
            margin-bottom: 0.25rem !important;
            width: 100% !important;
        }
    }
</style>

<script>
    // Disable native browser validation tooltips (e.g. English "Please fill out this field")
    // so Laravel & Livewire can render full Arabic inline error messages.
    function disableBrowserValidation() {
        document.querySelectorAll('form:not([novalidate])').forEach(form => {
            form.setAttribute('novalidate', '');
        });
    }

    document.addEventListener('DOMContentLoaded', disableBrowserValidation);
    document.addEventListener('livewire:navigated', disableBrowserValidation);
    document.addEventListener('livewire:init', disableBrowserValidation);

    // Watch for dynamically opened modals and Livewire components
    const formObserver = new MutationObserver(() => {
        disableBrowserValidation();
    });

    if (document.body) {
        formObserver.observe(document.body, { childList: true, subtree: true });
    }
</script>
