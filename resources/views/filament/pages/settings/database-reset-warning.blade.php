<style>
    .danger-zone-card {
        border: 1px solid #fecaca;
        background: linear-gradient(180deg, #fffafa 0%, #ffffff 100%);
        border-radius: 1rem;
        padding: 1.35rem 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.05), 0 2px 4px -2px rgba(239, 68, 68, 0.05);
        margin-bottom: 0.75rem;
        box-sizing: border-box;
    }
    .dark .danger-zone-card {
        border-color: rgba(239, 68, 68, 0.25);
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.08) 0%, rgba(17, 24, 39, 0.5) 100%);
        box-shadow: none;
    }

    .danger-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #fee2e2;
        flex-wrap: wrap;
    }
    .dark .danger-header {
        border-bottom-color: rgba(239, 68, 68, 0.18);
    }

    .danger-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .danger-icon-box {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 0.65rem;
        background-color: #fee2e2;
        color: #dc2626;
        font-size: 1.35rem;
        flex-shrink: 0;
        box-shadow: 0 1px 2px 0 rgba(220, 38, 38, 0.15);
    }
    .dark .danger-icon-box {
        background-color: rgba(239, 68, 68, 0.22);
        color: #f87171;
    }

    .danger-title-text {
        font-size: 0.95rem;
        font-weight: 800;
        color: #991b1b;
        letter-spacing: -0.2px;
    }
    .dark .danger-title-text {
        color: #fca5a5;
    }

    .danger-subtitle-text {
        font-size: 0.78rem;
        color: #b91c1c;
        margin-top: 0.15rem;
        line-height: 1.4;
    }
    .dark .danger-subtitle-text {
        color: #f87171;
    }

    .danger-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        white-space: nowrap;
    }
    .dark .danger-badge {
        background-color: rgba(239, 68, 68, 0.18);
        color: #fca5a5;
        border-color: rgba(239, 68, 68, 0.35);
    }

    .danger-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
        gap: 1rem;
        margin-top: 1.15rem;
    }

    .danger-box {
        border-radius: 0.75rem;
        padding: 1rem 1.15rem;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        box-sizing: border-box;
    }

    .danger-box-wiped {
        border: 1px solid #fecaca;
        background-color: rgba(254, 242, 242, 0.7);
    }
    .dark .danger-box-wiped {
        border-color: rgba(239, 68, 68, 0.22);
        background-color: rgba(239, 68, 68, 0.05);
    }

    .danger-box-preserved {
        border: 1px solid #bbf7d0;
        background-color: rgba(240, 253, 244, 0.7);
    }
    .dark .danger-box-preserved {
        border-color: rgba(16, 185, 129, 0.22);
        background-color: rgba(16, 185, 129, 0.05);
    }

    .box-header {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.84rem;
        font-weight: 800;
        padding-bottom: 0.4rem;
        border-bottom: 1px dashed;
    }
    .box-header-wiped {
        color: #991b1b;
        border-bottom-color: rgba(239, 68, 68, 0.25);
    }
    .dark .box-header-wiped {
        color: #f87171;
        border-bottom-color: rgba(239, 68, 68, 0.2);
    }
    .box-header-preserved {
        color: #065f46;
        border-bottom-color: rgba(16, 185, 129, 0.25);
    }
    .dark .box-header-preserved {
        color: #34d399;
        border-bottom-color: rgba(16, 185, 129, 0.2);
    }

    .box-content-list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        font-size: 0.78rem;
        line-height: 1.55;
    }
    .box-content-list-wiped {
        color: #7f1d1d;
    }
    .dark .box-content-list-wiped {
        color: #fca5a5;
    }
    .box-content-list-preserved {
        color: #064e3b;
    }
    .dark .box-content-list-preserved {
        color: #a7f3d0;
    }

    .box-content-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.45rem;
    }

    .danger-footer-banner {
        margin-top: 1.15rem;
        padding: 0.65rem 0.95rem;
        border-radius: 0.55rem;
        background-color: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 0.77rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    .dark .danger-footer-banner {
        background-color: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.25);
        color: #fde68a;
    }
</style>

<div class="danger-zone-card" dir="rtl">
    <!-- 1. Header with icon, title, subtitle & pill badge -->
    <div class="danger-header">
        <div class="danger-title-wrap">
            <div class="danger-icon-box">
                ⚠️
            </div>
            <div>
                <div class="danger-title-text">
                    {{ __('settings.manage.database_reset.warning_title') }}
                </div>
                <div class="danger-subtitle-text">
                    {{ __('settings.manage.database_reset.warning_subtitle') }}
                </div>
            </div>
        </div>
        <div class="danger-badge">
            <span>⛔</span>
            <span>{{ __('settings.manage.database_reset.warning_badge') }}</span>
        </div>
    </div>

    <!-- 2. Dual Comparison Grid: Wiped vs Preserved -->
    <div class="danger-grid">
        <!-- Wiped Items Card -->
        <div class="danger-box danger-box-wiped">
            <div class="box-header box-header-wiped">
                <span>🗑️</span>
                <span>{{ __('settings.manage.database_reset.wiped_items_title') }}</span>
            </div>
            <ul class="box-content-list box-content-list-wiped">
                @php $wiped = __('settings.manage.database_reset.wiped_items'); @endphp
                @if (is_array($wiped))
                    @foreach ($wiped as $item)
                        <li>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                @else
                    <li>{{ $wiped }}</li>
                @endif
            </ul>
        </div>

        <!-- Preserved Items Card -->
        <div class="danger-box danger-box-preserved">
            <div class="box-header box-header-preserved">
                <span>🛡️</span>
                <span>{{ __('settings.manage.database_reset.preserved_items_title') }}</span>
            </div>
            <ul class="box-content-list box-content-list-preserved">
                @php $preserved = __('settings.manage.database_reset.preserved_items'); @endphp
                @if (is_array($preserved))
                    @foreach ($preserved as $item)
                        <li>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                @else
                    <li>{{ $preserved }}</li>
                @endif
            </ul>
        </div>
    </div>

    <!-- 3. Bottom Guidance Note -->
    <div class="danger-footer-banner">
        <span>ℹ️</span>
        <span>{{ __('settings.manage.database_reset.footer_notice') }}</span>
    </div>
</div>
