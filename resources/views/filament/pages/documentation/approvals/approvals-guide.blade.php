<!-- تصميم مطابق لنمط "كارنت | دليل لوحة التحكم" بخط تجوال (Tajawal) وبنفس الأحجام والتنسيق -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">

<style>
    /* ==========================================================================
       CarNet Design System Styles - Font: Tajawal
       ========================================================================== */
    .carnet-wrapper,
    .carnet-wrapper * {
        font-family: 'Tajawal', sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
        box-sizing: border-box;
    }

    .carnet-wrapper {
        direction: rtl;
        color: #1e293b;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .dark .carnet-wrapper {
        color: #f1f5f9;
    }

    /* 1. CarNet Top Toolbar */
    .carnet-top-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }
    .dark .carnet-top-bar {
        background: #1e293b;
        border-color: #334155;
    }

    .carnet-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .carnet-logo-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }
    .carnet-title-text h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
    }
    .dark .carnet-title-text h3 {
        color: #f8fafc;
    }
    .carnet-title-text p {
        margin: 2px 0 0 0;
        font-size: 12.5px;
        color: #64748b;
    }
    .dark .carnet-title-text p {
        color: #94a3b8;
    }

    /* Search Box */
    .carnet-search-box {
        flex: 1;
        max-width: 480px;
        position: relative;
    }
    .carnet-search-input {
        width: 100%;
        padding: 10px 40px 10px 16px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        font-size: 13.5px;
        color: #0f172a;
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }
    .dark .carnet-search-input {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }
    .carnet-search-input:focus {
        border-color: #0284c7;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }
    .carnet-search-icon {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 15px;
        pointer-events: none;
    }

    /* Action Buttons */
    .carnet-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .carnet-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 15px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid transparent;
        font-family: inherit;
        background: transparent;
    }
    .carnet-btn-primary {
        background: #0284c7;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
    }
    .carnet-btn-primary:hover {
        background: #0369a1;
        color: #ffffff;
    }
    .carnet-btn-outline {
        background: #ffffff;
        border-color: #e2e8f0;
        color: #475569;
    }
    .dark .carnet-btn-outline {
        background: #0f172a;
        border-color: #334155;
        color: #cbd5e1;
    }
    .carnet-btn-outline:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .dark .carnet-btn-outline:hover {
        background: #1e293b;
        color: #f8fafc;
    }

    /* 2. Secondary Tabs Row */
    .carnet-tabs-nav {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        border-bottom: 2px solid #e2e8f0;
    }
    .dark .carnet-tabs-nav {
        border-bottom-color: #334155;
    }
    .carnet-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 16px;
        font-size: 13.5px;
        font-weight: 700;
        color: #64748b;
        text-decoration: none;
        white-space: nowrap;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.15s ease;
    }
    .carnet-tab-link:hover {
        color: #0284c7;
    }
    .carnet-tab-link.active {
        color: #0284c7;
        border-bottom-color: #0284c7;
    }
    .dark .carnet-tab-link.active {
        color: #38bdf8;
        border-bottom-color: #38bdf8;
    }

    /* 3. Two-Column Layout (Content Left, Index Sidebar Right) */
    .carnet-content-grid {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 960px) {
        .carnet-content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Right Sticky In-Page Index (فهرس محتويات الصفحة) */
    .carnet-index-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 16px;
        position: sticky;
        top: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }
    .dark .carnet-index-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-index-heading {
        font-size: 13.5px;
        font-weight: 800;
        color: #64748b;
        margin: 0 0 14px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .carnet-index-heading {
        color: #94a3b8;
        border-bottom-color: #334155;
    }
    .carnet-index-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .carnet-index-item a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .dark .carnet-index-item a {
        color: #cbd5e1;
    }
    .carnet-index-item a:hover {
        background: #f1f5f9;
        color: #0284c7;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a:hover {
        background: #0f172a;
        color: #38bdf8;
    }
    .carnet-index-item a span.icon {
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Left Main Content Sections */
    .carnet-main-flow {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    /* Video Section Header (Matching CarNet style with red play icon) */
    .carnet-video-head {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    .carnet-play-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fee2e2;
        color: #ef4444;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .carnet-video-head h2 {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-video-head h2 {
        color: #f8fafc;
    }
    .carnet-video-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 0 0 16px 0;
        line-height: 1.8;
    }
    .dark .carnet-video-subtitle {
        color: #94a3b8;
    }

    /* Video Player Frame */
    .carnet-video-box {
        background: #0f172a;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        border: 1px solid #334155;
        position: relative;
    }
    .carnet-video-element {
        width: 100%;
        height: auto;
        max-height: 540px;
        display: block;
        background: #000000;
    }
    .carnet-video-caption {
        padding: 10px 16px;
        background: #0b1329;
        color: #94a3b8;
        font-size: 12.5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #1e293b;
    }

    /* Standard CarNet Section Card */
    .carnet-section-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    }
    .dark .carnet-section-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-section-title {
        font-size: 16.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 16px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark .carnet-section-title {
        color: #f8fafc;
        border-bottom-color: #334155;
    }
    .carnet-section-title .sec-icon {
        font-size: 18px;
    }

    /* Screen / Module Cards Grid */
    .carnet-screens-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }
    .carnet-screen-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 14px;
        transition: all 0.2s ease;
    }
    .dark .carnet-screen-box {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-screen-box:hover {
        background: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 4px 16px rgba(2, 132, 199, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-screen-box:hover {
        background: #1e293b;
        border-color: #38bdf8;
    }
    .carnet-screen-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .carnet-screen-name {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-screen-name {
        color: #f8fafc;
    }
    .carnet-screen-badge {
        font-size: 11.5px;
        font-weight: 700;
        background: #e0f2fe;
        color: #0369a1;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .dark .carnet-screen-badge {
        background: #075985;
        color: #e0f2fe;
    }
    .carnet-screen-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        line-height: 1.7;
    }
    .dark .carnet-screen-desc {
        color: #94a3b8;
    }
    .carnet-screen-link-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        background: #0284c7;
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        transition: background 0.15s ease;
    }
    .carnet-screen-link-btn:hover {
        background: #0369a1;
        color: #ffffff;
    }

    /* Steps List */
    .carnet-step-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .dark .carnet-step-item {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-step-num {
        min-width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .carnet-step-content h4 {
        margin: 0 0 4px 0;
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
    }
    .dark .carnet-step-content h4 {
        color: #f1f5f9;
    }
    .carnet-step-content p {
        margin: 0;
        font-size: 13.5px;
        color: #475569;
        line-height: 1.7;
    }
    .dark .carnet-step-content p {
        color: #94a3b8;
    }

    /* FAQ / Accordion Items */
    .carnet-faq-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }
    .dark .carnet-faq-item {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-faq-question {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .carnet-faq-question {
        color: #f8fafc;
    }
    .carnet-faq-answer {
        margin: 0;
        font-size: 13px;
        color: #475569;
        line-height: 1.7;
    }
    .dark .carnet-faq-answer {
        color: #94a3b8;
    }

    /* Toast Notification for Copy Link */
    #carnet-toast {
        display: none;
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: #0f172a;
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        z-index: 99999;
    }

    @media print {
        .carnet-top-bar,
        .carnet-tabs-nav,
        .carnet-index-card,
        .carnet-actions,
        aside.fi-sidebar,
        .doc-hub-top-bar,
        .doc-hub-nav-aside {
            display: none !important;
        }
        .carnet-content-grid {
            grid-template-columns: 1fr !important;
        }
        .carnet-video-box {
            display: none !important;
        }
    }
</style>

<div class="carnet-wrapper" id="carnet-approvals-hub">

    <!-- 1. شريط الأدوات العلوي (مطابق لكارنت: العنوان، حقل البحث، أزرار الطباعة والمشاركة والانتقال) -->
    <div class="carnet-top-bar">
        <div class="carnet-title-box">
            <div class="carnet-logo-icon">✓</div>
            <div class="carnet-title-text">
                <h3>دليل لوحة التحكم | نظام الاعتماد والموافقات</h3>
                <p>شرح طريقة مراجعة واعتماد السندات والمعاملات خطوة بخطوة</p>
            </div>
        </div>

        <!-- حقل البحث الحي المباشر -->
        <div class="carnet-search-box">
            <span class="carnet-search-icon">🔍</span>
            <input
                type="text"
                id="carnet-search-input"
                class="carnet-search-input"
                placeholder="ابحث في دليل الشرح (مثال: بحث، اعتماد، إلغاء، أقساط، ديون، خدمات)..."
                onkeyup="filterCarnetContent()"
            >
        </div>

        <!-- أزرار الإجراءات السريعة -->
        <div class="carnet-actions">
            <button type="button" onclick="copyCarnetShareLink()" class="carnet-btn carnet-btn-outline" title="نسخ رابط هذه الصفحة">
                <span>🔗</span>
                <span>مشاركة الرابط</span>
            </button>

            <button type="button" onclick="window.print()" class="carnet-btn carnet-btn-outline" title="طباعة الشرح أو حفظه كـ PDF">
                <span>🖨️</span>
                <span>طباعة الدليل</span>
            </button>

            <a href="/installment-payments" class="carnet-btn carnet-btn-primary">
                <span>الذهاب لشاشات السندات</span>
                <span>⇦</span>
            </a>
        </div>
    </div>

    <!-- 2. شريط التبويبات الأفقي (Horizontal Navigation Tabs) -->
    <nav class="carnet-tabs-nav">
        <a href="#sec-video" class="carnet-tab-link active">
            <span>🎬</span>
            <span>فيديو الشرح التوضيحي</span>
        </a>
        <a href="#sec-concept" class="carnet-tab-link">
            <span>💡</span>
            <span>الفكرة العامة للاعتماد</span>
        </a>
        <a href="#sec-screens" class="carnet-tab-link">
            <span>📑</span>
            <span>الشاشات المشمولة (3)</span>
        </a>
        <a href="#sec-workflow" class="carnet-tab-link">
            <span>📋</span>
            <span>خطوات الاعتماد خطوة بخطوة</span>
        </a>
        <a href="#sec-faq" class="carnet-tab-link">
            <span>❓</span>
            <span>الأسئلة الشائعة والإرشادات</span>
        </a>
    </nav>

    <!-- 3. المخطط الرئيسي (فهرس المحتويات على اليمين، والمحتوى والشرح على اليسار) -->
    <div class="carnet-content-grid">

        <!-- الفهرس الجانبي الثابت (فهرس محتويات الصفحة) -->
        <aside class="carnet-index-card">
            <div class="carnet-index-heading">
                <span>📑</span>
                <span>فهرس محتويات الصفحة</span>
            </div>
            <ul class="carnet-index-list">
                <li class="carnet-index-item">
                    <a href="#sec-video">
                        <span class="icon">🎬</span>
                        <span>فيديو الشرح التوضيحي</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-concept">
                        <span class="icon">💡</span>
                        <span>فكرة وهدف الاعتماد</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-screens">
                        <span class="icon">📑</span>
                        <span>الشاشات المشمولة</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-workflow">
                        <span class="icon">📋</span>
                        <span>طريقة الاعتماد (خطوة بخطوة)</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-unapprove">
                        <span class="icon">🔄</span>
                        <span>إلغاء الاعتماد والتعديل</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-tips">
                        <span class="icon">⭐</span>
                        <span>إرشادات ونصائح عملية</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-faq">
                        <span class="icon">❓</span>
                        <span>الأسئلة الشائعة</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- المحتوى الرئيسي المتدفق (يسار) -->
        <main class="carnet-main-flow">

            <!-- القسم 1: فيديو الشرح التوضيحي (مطابق لتصميم كارنت مع شارة التشغيل الحمراء) -->
            <section id="sec-video" class="carnet-searchable-section">
                <div class="carnet-video-head">
                    <span class="carnet-play-badge">▶</span>
                    <h2>{{ __('documentation.approvals.video_title') }}</h2>
                </div>
                <p class="carnet-video-subtitle">
                    {{ __('documentation.approvals.video_desc') }}
                </p>

                <div class="carnet-video-box">
                    <video controls preload="metadata" class="carnet-video-element">
                        <source src="{{ asset('vidoes/approved.mp4') }}" type="video/mp4">
                        <source src="/vidoes/approved.mp4" type="video/mp4">
                        <source src="/public/vidoes/approved.mp4" type="video/mp4">
                        <source src="{{ asset('vidoes/approved') }}" type="video/mp4">
                        <source src="/vidoes/approved" type="video/mp4">
                        <source src="{{ asset('videos/approved.mp4') }}" type="video/mp4">
                        <source src="/videos/approved.mp4" type="video/mp4">
                        متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                    </video>
                    <div class="carnet-video-caption">
                        <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                        <span>مسار الفيديو: <code>/vidoes/approved.mp4</code></span>
                    </div>
                </div>
            </section>

            <!-- القسم 2: الفكرة والهدف العام من الاعتماد -->
            <section id="sec-concept" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">💡</span>
                    <span>{{ __('documentation.approvals.concept_label') }}</span>
                </h3>
                <p style="margin: 0; font-size: 14.5px; color: #334155; line-height: 1.9;" class="dark:text-slate-300">
                    {{ __('documentation.approvals.concept_text') }}
                </p>
            </section>

            <!-- القسم 3: الشاشات التي تدعم الاعتماد حالياً -->
            <section id="sec-screens" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">📑</span>
                    <span>{{ __('documentation.approvals.screens_label') }}</span>
                </h3>
                <p style="margin: 0 0 16px 0; font-size: 13.5px; color: #64748b;" class="dark:text-slate-400">
                    {{ __('documentation.approvals.screens_desc') }}
                </p>

                <div class="carnet-screens-grid">
                    <!-- Screen 1: Installment Payments -->
                    <div class="carnet-screen-box">
                        <div>
                            <div class="carnet-screen-top">
                                <h4 class="carnet-screen-name">{{ __('documentation.approvals.screen_1_title') }}</h4>
                                <span class="carnet-screen-badge">سندات قبض</span>
                            </div>
                            <p class="carnet-screen-desc">{{ __('documentation.approvals.screen_1_desc') }}</p>
                        </div>
                        <a href="/installment-payments" class="carnet-screen-link-btn">
                            <span>فتح شاشة الأقساط</span>
                            <span>⇦</span>
                        </a>
                    </div>

                    <!-- Screen 2: Debt Collections -->
                    <div class="carnet-screen-box">
                        <div>
                            <div class="carnet-screen-top">
                                <h4 class="carnet-screen-name">{{ __('documentation.approvals.screen_2_title') }}</h4>
                                <span class="carnet-screen-badge">تحصيل مالي</span>
                            </div>
                            <p class="carnet-screen-desc">{{ __('documentation.approvals.screen_2_desc') }}</p>
                        </div>
                        <a href="/debt-collections" class="carnet-screen-link-btn">
                            <span>فتح شاشة تحصيل الديون</span>
                            <span>⇦</span>
                        </a>
                    </div>

                    <!-- Screen 3: Service Transactions -->
                    <div class="carnet-screen-box">
                        <div>
                            <div class="carnet-screen-top">
                                <h4 class="carnet-screen-name">{{ __('documentation.approvals.screen_3_title') }}</h4>
                                <span class="carnet-screen-badge">متابعة خدمات</span>
                            </div>
                            <p class="carnet-screen-desc">{{ __('documentation.approvals.screen_3_desc') }}</p>
                        </div>
                        <a href="/service-transactions" class="carnet-screen-link-btn">
                            <span>فتح شاشة الخدمات</span>
                            <span>⇦</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- القسم 4: خطوات الاعتماد خطوة بخطوة -->
            <section id="sec-workflow" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">📋</span>
                    <span>{{ __('documentation.approvals.workflow_label') }}</span>
                </h3>

                <!-- خطوة 1 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">{{ __('documentation.approvals.step_1_num') }}</div>
                    <div class="carnet-step-content">
                        <h4>{{ __('documentation.approvals.step_1_title') }}</h4>
                        <p>{{ __('documentation.approvals.step_1_desc') }}</p>
                    </div>
                </div>

                <!-- خطوة 2 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">{{ __('documentation.approvals.step_2_num') }}</div>
                    <div class="carnet-step-content">
                        <h4>{{ __('documentation.approvals.step_2_title') }}</h4>
                        <p>{{ __('documentation.approvals.step_2_desc') }}</p>
                    </div>
                </div>

                <!-- خطوة 3 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">{{ __('documentation.approvals.step_3_num') }}</div>
                    <div class="carnet-step-content">
                        <h4>{{ __('documentation.approvals.step_3_title') }}</h4>
                        <p>{{ __('documentation.approvals.step_3_desc') }}</p>
                    </div>
                </div>
            </section>

            <!-- القسم 5: إلغاء الاعتماد والتعديل -->
            <section id="sec-unapprove" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">🔄</span>
                    <span>إلغاء الاعتماد وتصحيح السندات</span>
                </h3>
                <div class="carnet-step-item" style="border-right: 4px solid #64748b;">
                    <div class="carnet-step-num" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                        {{ __('documentation.approvals.step_4_num') }}
                    </div>
                    <div class="carnet-step-content">
                        <h4>{{ __('documentation.approvals.step_4_title') }}</h4>
                        <p>{{ __('documentation.approvals.step_4_desc') }}</p>
                    </div>
                </div>
            </section>

            <!-- القسم 6: إرشادات ونصائح عملية -->
            <section id="sec-tips" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">⭐</span>
                    <span>{{ __('documentation.approvals.tips_label') }}</span>
                </h3>

                <div class="carnet-screens-grid">
                    <div class="carnet-screen-box" style="border-right: 4px solid #0284c7;">
                        <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #0284c7;">
                            🔍 {{ __('documentation.approvals.tip_1_title') }}
                        </h4>
                        <p class="carnet-screen-desc">{{ __('documentation.approvals.tip_1_desc') }}</p>
                    </div>

                    <div class="carnet-screen-box" style="border-right: 4px solid #10b981;">
                        <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #059669;">
                            🔒 {{ __('documentation.approvals.tip_2_title') }}
                        </h4>
                        <p class="carnet-screen-desc">{{ __('documentation.approvals.tip_2_desc') }}</p>
                    </div>

                    <div class="carnet-screen-box" style="border-right: 4px solid #f59e0b;">
                        <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #d97706;">
                            🎨 {{ __('documentation.approvals.tip_3_title') }}
                        </h4>
                        <p class="carnet-screen-desc">{{ __('documentation.approvals.tip_3_desc') }}</p>
                    </div>
                </div>
            </section>

            <!-- القسم 7: الأسئلة الشائعة (FAQ) -->
            <section id="sec-faq" class="carnet-section-card carnet-searchable-section">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">❓</span>
                    <span>الأسئلة الشائعة حول نظام الاعتماد</span>
                </h3>

                <div class="carnet-faq-item">
                    <h5 class="carnet-faq-question">
                        <span>❓</span>
                        <span>من يملك صلاحية اعتماد وإلغاء اعتماد السندات؟</span>
                    </h5>
                    <p class="carnet-faq-answer">
                        صلاحية الاعتماد مقتصرة فقط على المشرف العام (Super Admin)، ملاك المنشأة، والمحاسبين الممنوحين صلاحيات:
                        <code>approve_installment_payment</code> و <code>approve_debt_collection</code> و <code>approve_service_transaction</code>.
                    </p>
                </div>

                <div class="carnet-faq-item">
                    <h5 class="carnet-faq-question">
                        <span>❓</span>
                        <span>ماذا يحدث عند الضغط على زر "اعتماد"؟</span>
                    </h5>
                    <p class="carnet-faq-answer">
                        يتحول السند فوراً إلى حالة "معتمد" وتتغير شارته إلى اللون الأخضر، ويتم تأكيد صحة العملية في السجلات.
                    </p>
                </div>

                <div class="carnet-faq-item">
                    <h5 class="carnet-faq-question">
                        <span>❓</span>
                        <span>هل يمكن التعديل على سند معتمد؟</span>
                    </h5>
                    <p class="carnet-faq-answer">
                        لحماية البيانات، لا يجوز تعديل سند معتمد إلا بعد إلغاء اعتماده أولاً بالضغط على زر "إلغاء الاعتماد"، ثم إجراء التعديل وإعادة اعتماده مجدداً.
                    </p>
                </div>
            </section>

        </main>

    </div>

    <!-- إشعار نسخ الرابط -->
    <div id="carnet-toast">تم نسخ رابط الدليل إلى الحافظة بنجاح ✓</div>

</div>

<!-- سكريبت التفاعل والبحث الحي ومشاركة الرابط -->
<script>
    function filterCarnetContent() {
        const query = (document.getElementById('carnet-search-input').value || '').trim().toLowerCase();
        const sections = document.querySelectorAll('.carnet-searchable-section');

        sections.forEach(sec => {
            if (!query) {
                sec.style.display = '';
                return;
            }
            const text = sec.innerText.toLowerCase();
            if (text.includes(query)) {
                sec.style.display = '';
            } else {
                sec.style.display = 'none';
            }
        });
    }

    function copyCarnetShareLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const toast = document.getElementById('carnet-toast');
            if (toast) {
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 2500);
            }
        }).catch(() => {
            alert('تم نسخ الرابط: ' + url);
        });
    }

    // تفعيل التمرير السلس للروابط
    document.querySelectorAll('.carnet-index-item a, .carnet-tab-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // تحديث الحالة النشطة في التبويبات
                    document.querySelectorAll('.carnet-tab-link').forEach(t => t.classList.remove('active'));
                    const matchTab = document.querySelector(`.carnet-tab-link[href="${targetId}"]`);
                    if (matchTab) matchTab.classList.add('active');
                }
            }
        });
    });
</script>
