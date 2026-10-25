<!-- تصميم مطابق لنمط "كارنت | دليل لوحة التحكم" بخط تجوال (Tajawal) مع فهرس المحتويات الجانبي والبحث الحي والطباعة -->
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
    .carnet-btn-secondary {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .dark .carnet-btn-secondary {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }
    .carnet-btn-secondary:hover {
        background: #e2e8f0;
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

    /* Tip Box */
    .carnet-tip-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-inline-start: 4px solid #16a34a;
        border-radius: 10px;
        padding: 12px 16px;
        color: #166534;
        font-size: 13.5px;
        font-weight: 600;
        margin-top: 10px;
    }
    .dark .carnet-tip-box {
        background: #052e16;
        border-color: #14532d;
        color: #86efac;
    }

    /* Categories Grid */
    .carnet-categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .carnet-cat-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dark .carnet-cat-card {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-cat-card strong {
        font-size: 14px;
        color: #0284c7;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dark .carnet-cat-card strong {
        color: #38bdf8;
    }
    .carnet-cat-card span {
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.6;
    }
    .dark .carnet-cat-card span {
        color: #94a3b8;
    }

    /* Simple Table */
    .carnet-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        text-align: right;
        margin-top: 10px;
    }
    .carnet-table th {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 800;
        padding: 12px 14px;
        border-bottom: 2px solid #cbd5e1;
    }
    .dark .carnet-table th {
        background: #0f172a;
        color: #f1f5f9;
        border-bottom-color: #334155;
    }
    .carnet-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
        line-height: 1.7;
    }
    .dark .carnet-table td {
        border-bottom-color: #334155;
        color: #cbd5e1;
    }
    .carnet-table tr:hover td {
        background: #f8fafc;
    }
    .dark .carnet-table tr:hover td {
        background: #1e293b;
    }

    /* Color Badges */
    .carnet-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 800;
    }
    .badge-red-dark {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .dark .badge-red-dark {
        background: #450a0a;
        color: #fca5a5;
        border-color: #7f1d1d;
    }
    .badge-red {
        background: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
    }
    .dark .badge-red {
        background: #4c0519;
        color: #fda4af;
        border-color: #881337;
    }
    .badge-orange {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .dark .badge-orange {
        background: #451a03;
        color: #fcd34d;
        border-color: #78350f;
    }
    .badge-green {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .dark .badge-green {
        background: #052e16;
        color: #86efac;
        border-color: #14532d;
    }

    #carnet-reports-toast {
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
</style>

<div class="carnet-wrapper" id="carnet-reports-hub">

    <!-- 1. شريط الأدوات العلوي (مطابق لكارنت: العنوان، حقل البحث، أزرار الطباعة والمشاركة والانتقال) -->
    <div class="carnet-top-bar">
        <div class="carnet-title-box">
            <div class="carnet-logo-icon">📊</div>
            <div class="carnet-title-text">
                <h3>دليل لوحة التحكم | التقارير العامة وشاشة التنبيهات</h3>
                <p>شرح شامل لاستخدام 38 تقريراً ومراقبة تنبيهات وثائق الموظفين والتراخيص</p>
            </div>
        </div>

        <!-- حقل البحث الحي المباشر -->
        <div class="carnet-search-box">
            <span class="carnet-search-icon">🔍</span>
            <input
                type="text"
                id="carnet-reports-search"
                class="carnet-search-input"
                placeholder="ابحث في دليل التقارير (مثال: بحث، إكسل، إقامات، رخص، تنبيهات، ألوان)..."
                onkeyup="filterReportsContent()"
            >
        </div>

        <!-- أزرار الإجراءات السريعة -->
        <div class="carnet-actions">
            <button type="button" onclick="copyReportsShareLink()" class="carnet-btn carnet-btn-outline" title="نسخ رابط هذه الصفحة">
                <span>🔗</span>
                <span>مشاركة الرابط</span>
            </button>

            <button type="button" onclick="window.print()" class="carnet-btn carnet-btn-outline" title="طباعة الشرح أو حفظه كـ PDF">
                <span>🖨️</span>
                <span>طباعة الدليل</span>
            </button>

            <a href="/general-reports" class="carnet-btn carnet-btn-primary">
                <span>فتح التقارير العامة</span>
                <span>⇦</span>
            </a>

            <a href="/notifications-report" class="carnet-btn carnet-btn-secondary">
                <span>فتح التنبيهات</span>
                <span>⇦</span>
            </a>
        </div>
    </div>

    <!-- 2. شريط التبويبات الأفقي -->
    <nav class="carnet-tabs-nav">
        <a href="#sec-rep-concept" class="carnet-tab-link active">
            <span>💡</span>
            <span>الهدف من القسم</span>
        </a>
        <a href="#sec-rep-guide" class="carnet-tab-link">
            <span>📊</span>
            <span>دليل التقارير العامة (4 خطوات)</span>
        </a>
        <a href="#sec-rep-categories" class="carnet-tab-link">
            <span>📂</span>
            <span>تصنيفات التقارير (38)</span>
        </a>
        <a href="#sec-rep-alerts" class="carnet-tab-link">
            <span>🔔</span>
            <span>شاشة التنبيهات المسبقة</span>
        </a>
        <a href="#sec-rep-colors" class="carnet-tab-link">
            <span>🎨</span>
            <span>دليل ألوان التنبيهات</span>
        </a>
        <a href="#sec-rep-matrices" class="carnet-tab-link">
            <span>📑</span>
            <span>مصفوفة الوثائق</span>
        </a>
        <a href="#sec-rep-privacy" class="carnet-tab-link">
            <span>🛡️</span>
            <span>الخصوصية والأمان</span>
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
                    <a href="#sec-rep-concept">
                        <span class="icon">💡</span>
                        <span>فكرة وهدف القسم</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-guide">
                        <span class="icon">📊</span>
                        <span>شاشة التقارير العامة</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-categories">
                        <span class="icon">📂</span>
                        <span>تصنيفات الـ 38 تقريراً</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-alerts">
                        <span class="icon">🔔</span>
                        <span>شاشة التنبيهات المسبقة</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-colors">
                        <span class="icon">🎨</span>
                        <span>دليل ألوان التنبيهات</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-matrices">
                        <span class="icon">📑</span>
                        <span>مصفوفة الوثائق والتراخيص</span>
                    </a>
                </li>
                <li class="carnet-index-item">
                    <a href="#sec-rep-privacy">
                        <span class="icon">🛡️</span>
                        <span>الخصوصية والأمان</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- المحتوى الرئيسي المتدفق (يسار) -->
        <main class="carnet-main-flow">

            <!-- 1. الفكرة العامة والهدف -->
            <section id="sec-rep-concept" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">💡</span>
                    <span>ما هو الهدف من هذا القسم وكيف يساعدك في عملك؟</span>
                </h3>
                <p style="margin: 0; font-size: 14.5px; color: #334155; line-height: 1.9;" class="dark:text-slate-300">
                    هذا القسم صُمم ليوفر وقتك ومجهودك الإداري بالكامل: بدلاً من التنقل بين شاشات متعددة لجمع البيانات، وضعنا لك <strong>جميع تقارير المؤسسة (38 تقريراً) في شاشة واحدة</strong> سهلة وبسيطة، كما وضعنا لك <strong>نظام مراقبة وتنبيه ذكي</strong> يرصد لك تواريخ انتهاء إقامات الموظفين، ورخص العمل، والتأمين، وتراخيص الشركات والسجلات التجارية قبل موعدها بـ <strong>3 أشهر كاملة</strong> حتى لا تتفاجأ بأي غرامات مالية أو إيقاف في الخدمات.
                </p>
            </section>

            <!-- 2. خطوات استخدام شاشة التقارير العامة -->
            <section id="sec-rep-guide" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">📊</span>
                    <span>أولاً: دليل استخدام شاشة «التقارير العامة» (خطوة بخطوة)</span>
                </h3>

                <!-- خطوة 1 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">1</div>
                    <div class="carnet-step-content">
                        <h4>اختر التقرير الذي تريده</h4>
                        <p>في أعلى الصفحة، ستجد قائمة منسدلة تضم جميع تقارير النظام. اضغط عليها واكتب اسم ما تبحث عنه (مثال: موظفين، حضور، فواتير، رواتب، خزينة...) وستظهر لك النتيجة فوراً بمجرد الاختيار.</p>
                    </div>
                </div>

                <!-- خطوة 2 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">2</div>
                    <div class="carnet-step-content">
                        <h4>حدد الفترة والفرع وخيارات الفلترة</h4>
                        <p>يمكنك تحديد تاريخ البداية والنهاية (من تاريخ / إلى تاريخ)، واختيار فرع معين، أو كتابة اسم شخص أو رقم في خانة البحث الحر لتضييق النتائج.</p>
                        <div class="carnet-tip-box">
                            💡 <strong>ملاحظة مفيدة:</strong> خانة «الحالة» تتغير خياراتها تلقائياً حسب نوع التقرير؛ فإذا كنت في تقرير الحضور ستجد خيارات (حاضر، غائب، متأخر)، وإذا كنت في تقرير الفواتير ستجد (مدفوعة، غير مدفوعة)، وهكذا لكل تقرير.
                        </div>
                    </div>
                </div>

                <!-- خطوة 3 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">3</div>
                    <div class="carnet-step-content">
                        <h4>قراءة الإحصائيات وترتيب الجدول</h4>
                        <p>في أعلى الصفحة ستظهر لك بطاقات ملخصة تحتوي على الإجماليات والمبالغ الإجمالية مباشرة. وفي الجدول بالأسفل يمكنك ترتيب الأسماء أو المبالغ تصاعدياً وتنازلياً بالضغط مباشرة على عنوان أي عمود تريده.</p>
                    </div>
                </div>

                <!-- خطوة 4 -->
                <div class="carnet-step-item">
                    <div class="carnet-step-num">4</div>
                    <div class="carnet-step-content">
                        <h4>تنزيل التقرير في ملف إكسل أو طباعته</h4>
                        <p>• اضغط على زر <strong>«تصدير إكسل»</strong> لتنزيل البيانات فوراً كملف إكسل باللغة العربية سليم ومرتب.<br>• أو اضغط على زر <strong>«طباعة»</strong> لطباعة كشف ورقي رسمي منسق بشعار وبيانات المنشأة لتقديمه للإدارة.</p>
                        <div class="carnet-tip-box">
                            🔗 <strong>مشاركة سريعة:</strong> يمكنك بعد تصفية أي تقرير نسخ رابط الصفحة من أعلى المتصفح وإرساله لزميلك أو مديرك، وعندما يفتحه سيظهر أمامه نفس التقرير ونفس الأرقام التي اخترتها تماماً.
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. تصنيفات التقارير الـ 38 -->
            <section id="sec-rep-categories" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">📂</span>
                    <span>ما هي التقارير المتوفرة لك في هذه الشاشة؟ (38 تقريراً مصنفاً)</span>
                </h3>

                <div class="carnet-categories-grid">
                    <div class="carnet-cat-card">
                        <strong>👥 شؤون الموظفين</strong>
                        <span>بيانات الموظفين، المستخدمين، الأقسام، المسميات الوظيفية، ملاك المنشآت، والكفلاء.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>⏱️ الحضور والإجازات</strong>
                        <span>سجل الحضور والانصراف، ساعات التأخير، سجلات أجهزة البصمة، والإجازات برصيدها وأنواعها.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>💰 الخزينة والرواتب</strong>
                        <span>سندات القبض والصرف، مسيرات الرواتب الشهرية والبدلات، مكافآت الموظفين، والقيود اليومية.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>🧾 الفواتير والتحصيل</strong>
                        <span>الفواتير المسددة وغير المسددة، الأقساط الشهرية، مبالغ التحصيل، بيانات العملاء، وعروض الأسعار.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>🤝 القروض والجمعيات</strong>
                        <span>صناديق القروض والسلف، أقساط المشتركين، سداد الأقساط، وصناديق الجمعيات التدويرية.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>⚠️ المخالفات والغرامات</strong>
                        <span>سجل المخالفات المسجلة، المبالغ المسددة والمتبقية، ومتابعة فترات السداد والاعتراض النظامية.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>📋 المهام والمتابعة</strong>
                        <span>المهام الإدارية، الموظف المكلف بالعمل، الملاحظات الأسبوعية، وتذاكر الطلبات والدعم.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>🏢 الشركات والفروع</strong>
                        <span>بيانات الشركات، الفروع، السجلات التجارية، وأرقام التواصل الرسمية والضامنين.</span>
                    </div>
                </div>
            </section>

            <!-- 4. شاشة التنبيهات المسبقة -->
            <section id="sec-rep-alerts" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">🔔</span>
                    <span>ثانياً: دليل استخدام شاشة «تقرير التنبيهات» لحماية المنشأة من الغرامات</span>
                </h3>
                <p style="margin: 0; font-size: 14.5px; color: #334155; line-height: 1.9;" class="dark:text-slate-300">
                    وظيفة هذه الشاشة هي <strong>الإنذار المبكر</strong>؛ فالنظام يفحص التواريخ يومياً ويضع أمامك كل ما يقترب من الانتهاء خلال <strong>90 يوماً</strong> (أو ما انتهى بالفعل) لتتخذ الإجراء المناسب في الوقت المناسب.
                </p>
            </section>

            <!-- 5. دليل ألوان التنبيهات -->
            <section id="sec-rep-colors" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">🎨</span>
                    <span>دليلك السريع لفهم ألوان التنبيهات</span>
                </h3>

                <div style="overflow-x: auto;">
                    <table class="carnet-table">
                        <thead>
                            <tr>
                                <th style="width: 180px;">اللون في الشاشة</th>
                                <th style="width: 170px;">المدة المتبقية</th>
                                <th>ماذا يعني هذا اللون وما هو التصرف المطلوب؟</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="carnet-badge badge-red-dark">🚨 أحمر داكن (سالب)</span></td>
                                <td><strong>منتهية بالفعل</strong></td>
                                <td><strong>حالة طارئة:</strong> الوثيقة انتهت بالفعل وظهرت بالأيام السالبة. يجب التجديد والسداد فوراً لتفادي توقف الخدمات الحكومية والغرامات.</td>
                            </tr>
                            <tr>
                                <td><span class="carnet-badge badge-red">🔴 أحمر</span></td>
                                <td><strong>أقل من 30 يوماً</strong></td>
                                <td><strong>مرحلة حرجة:</strong> يتبقى أقل من شهر واحد فقط؛ يجب البدء فوراً في إجراءات التجديد أو سداد الرسوم المطلوبة.</td>
                            </tr>
                            <tr>
                                <td><span class="carnet-badge badge-orange">🟠 برتقالي</span></td>
                                <td><strong>من شهر حتى 3 أشهر</strong></td>
                                <td><strong>مرحلة استعداد:</strong> يتبقى من 31 إلى 90 يوماً؛ نافذة مريحة تمنحك وقتاً لتجهيز الأوراق والمبالغ دون أي استعجال.</td>
                            </tr>
                            <tr>
                                <td><span class="carnet-badge badge-green">🟢 أخضر</span></td>
                                <td><strong>أكثر من 90 يوماً</strong></td>
                                <td><strong>وضع سليم وآمن:</strong> الوثيقة سارية وتاريخها بعيد ولا تتطلب منك أي إجراء حالياً.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 6. مصفوفة الوثائق والتراخيص -->
            <section id="sec-rep-matrices" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">📑</span>
                    <span>ماذا تراقب شاشة التنبيهات عملياً؟</span>
                </h3>

                <div class="carnet-categories-grid">
                    <div class="carnet-cat-card">
                        <strong>👤 مصفوفة وثائق الموظفين</strong>
                        <span>يراقب النظام 4 وثائق أساسية لكل موظف: (تاريخ انتهاء الإقامة، رخصة العمل، التأمين الطبي، ونهاية عقد العمل) ويرتبهم لك من الأقرب انتهاءً فالأبعد.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>🏢 مصفوفة تراخيص المنشأة</strong>
                        <span>يراقب التراخيص القانونية للشركات والفروع: (السجل التجاري، رخصة البلدية، ورخصة الدفاع المدني) حتى لا تتفاجأ بإيقاف السجل أو التراخيص.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>🏖️ متابعة الإجازات المتجاوزة</strong>
                        <span>ينبهك النظام بأي موظف حصل على إجازة وانتهت مدتها الرسمية ولكن لم يُسجل له مباشرة عمل فعلية حتى الآن، للتواصل معه فوراً.</span>
                    </div>
                    <div class="carnet-cat-card">
                        <strong>⚖️ مهام ومخالفات مستحقة</strong>
                        <span>ينبهك بالمهام المتأخرة، والفواتير التي حان موعد تحصيلها، والمهل القانونية للمخالفات (مهلة الـ 60 يوماً ومرحلة الاعتراض والتنفيذ).</span>
                    </div>
                </div>
            </section>

            <!-- 7. الأمان والخصوصية -->
            <section id="sec-rep-privacy" class="carnet-section-card carnet-searchable-report">
                <h3 class="carnet-section-title">
                    <span class="sec-icon">🛡️</span>
                    <span>معلومات تهمك عن الخصوصية والأمان في هذه الصفحات:</span>
                </h3>

                <ul style="margin: 0; padding-inline-start: 20px; font-size: 14px; color: #475569; line-height: 2.1;" class="dark:text-slate-300">
                    <li><strong>حماية أرقام الهويات والإقامات:</strong> ستلاحظ في شاشة التنبيهات أن أرقام الإقامات والهويات لا تظهر كاملة بل يظهر في منتصفها نجوم (مثل: <code>1020****89</code>)؛ وذلك لحماية خصوصية بيانات موظفيك إذا كان هناك شخص يقف بجانب شاشتك.</li>
                    <li><strong>أمان الصلاحيات التلقائي:</strong> كل موظف أو مدير فرع يدخل على النظام لا يرى إلا تقارير وتنبيهات الشركات والفروع المصرح له بها فقط، ولن تظهر له أي بيانات تخص فروعاً أو شركات أخرى.</li>
                </ul>
            </section>

        </main>

    </div>

    <!-- إشعار نسخ الرابط -->
    <div id="carnet-reports-toast">تم نسخ رابط الدليل إلى الحافظة بنجاح ✓</div>

</div>

<!-- سكريبت التفاعل والبحث الحي ومشاركة الرابط -->
<script>
    function filterReportsContent() {
        const query = (document.getElementById('carnet-reports-search').value || '').trim().toLowerCase();
        const sections = document.querySelectorAll('.carnet-searchable-report');

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

    function copyReportsShareLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const toast = document.getElementById('carnet-reports-toast');
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
    document.querySelectorAll('#carnet-reports-hub .carnet-index-item a, #carnet-reports-hub .carnet-tab-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // تحديث الحالة النشطة في التبويبات
                    document.querySelectorAll('#carnet-reports-hub .carnet-tab-link').forEach(t => t.classList.remove('active'));
                    const matchTab = document.querySelector(`#carnet-reports-hub .carnet-tab-link[href="${targetId}"]`);
                    if (matchTab) matchTab.classList.add('active');
                }
            }
        });
    });
</script>
