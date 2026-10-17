@php
    $counts = $this->getCounts();
    $total = $counts['total'] ?? 0;
    $urgent = $counts['urgent'] ?? 0;
    $reportUrl = \App\Filament\Pages\NotificationsReport::getUrl();
@endphp

<style>
    .notif-banner-card {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid #bfdbfe;
        background: linear-gradient(135deg, #f0f7ff 0%, #e0effe 40%, #ffffff 100%);
        padding: 1rem 1.35rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 1px 3px rgba(37,99,235,0.08);
        transition: box-shadow 0.2s;
    }
    .notif-banner-card:hover {
        box-shadow: 0 4px 12px -2px rgba(37,99,235,0.15);
    }
    .dark .notif-banner-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-color: #334155;
    }
    .notif-banner-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }
    .notif-banner-icon {
        width: 44px;
        height: 44px;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(245,158,11,0.3);
    }
    .notif-banner-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .notif-banner-title { color: #f8fafc; }
    .notif-banner-sub {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #475569;
        margin: 0.2rem 0 0 0;
    }
    .dark .notif-banner-sub { color: #94a3b8; }
    .notif-banner-right {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
    }
    .badge-dark-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #0f172a;
        color: #ffffff;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .dark .badge-dark-pill {
        background: #ffffff;
        color: #0f172a;
    }
    .badge-rose-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #ffe4e6;
        color: #be123c;
        border: 1px solid #fecdd3;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 800;
    }
    .btn-open-report {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        border-radius: 0.65rem;
        padding: 0.45rem 0.95rem;
        font-size: 0.8rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.15s;
    }
    .btn-open-report:hover {
        background: #f8fafc;
        border-color: #2563eb;
        color: #2563eb;
        transform: translateY(-1px);
    }
    .dark .btn-open-report {
        background: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }
</style>

<div class="notif-banner-card" dir="rtl">
    <div class="notif-banner-left">
        <div class="notif-banner-icon">🔔</div>
        <div>
            <h3 class="notif-banner-title">تقرير الإشعارات</h3>
            <p class="notif-banner-sub">جميع الإشعارات والتنبيهات النشطة: انتهاء وثائق، سلف مستحقة، مواعيد متأخرة</p>
        </div>
    </div>

    <div class="notif-banner-right">
        <div class="badge-dark-pill">
            <span style="width: 7px; height: 7px; border-radius: 50%; background: #34d399; display: inline-block;"></span>
            <span>{{ $total }} إشعارًا نشطًا</span>
        </div>

        @if ($urgent > 0)
            <div class="badge-rose-pill">
                <span>{{ $urgent }} عاجلة</span>
            </div>
        @endif

        <a href="{{ $reportUrl }}" class="btn-open-report">
            <span>فتح التقرير</span>
            <span>←</span>
        </a>
    </div>
</div>
