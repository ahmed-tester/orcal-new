{{-- 
    دليل قسم المخالفات والجزاءات وصندوق التحصيل
    Violations & Disciplinary Management Guide
    Design System: CarNet Interactive Design (Tajawal, Glassmorphism, Dual Themes)
    الفيديو الشامل: Violations Collection Fund.mp4 (شارح الثلاثة معاً: سجل المخالفات، لوائح المخالفات، صندوق التحصيل)
--}}

<style>
    @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap');

    .carnet-wrapper {
        font-family: 'Tajawal', sans-serif !important;
        color: #1e293b;
        direction: rtl;
        text-align: right;
    }
    .dark .carnet-wrapper {
        color: #f1f5f9;
    }

    /* Hero Box for Violations Hub */
    .carnet-hub-hero-box {
        background: linear-gradient(135deg, #881337 0%, #be123c 50%, #e11d48 100%);
        border-radius: 20px;
        padding: 36px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.35);
        position: relative;
        overflow: hidden;
    }
    .carnet-hub-hero-box::after {
        content: '';
        position: absolute;
        top: -40%;
        left: -10%;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .carnet-hub-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .carnet-hub-hero-box h2 {
        font-size: 26px;
        font-weight: 900;
        margin: 0 0 10px 0;
        line-height: 1.3;
    }
    .carnet-hub-hero-box p {
        font-size: 15px;
        line-height: 1.7;
        margin: 0;
        max-width: 850px;
        opacity: 0.95;
        font-weight: 400;
    }

    /* Sub-Header Navigation Bar */
    .carnet-sub-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
        padding: 14px 20px;
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        flex-wrap: wrap;
    }
    .dark .carnet-sub-header-bar {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-sub-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1f5f9;
        color: #be123c;
        font-weight: 700;
        font-size: 13.5px;
        padding: 8px 16px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .carnet-sub-back-btn:hover {
        background: #be123c;
        color: #ffffff;
        border-color: #be123c;
        transform: translateX(3px);
    }
    .dark .carnet-sub-back-btn {
        background: #334155;
        color: #fb7185;
        border-color: #475569;
    }
    .dark .carnet-sub-back-btn:hover {
        background: #be123c;
        color: #ffffff;
    }
    .carnet-sub-nav-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .carnet-sub-tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .dark .carnet-sub-tab-pill {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    .carnet-sub-tab-pill.active {
        background: #be123c;
        color: #ffffff;
        border-color: #be123c;
        box-shadow: 0 4px 10px rgba(190, 18, 60, 0.25);
    }
    .carnet-sub-tab-pill:hover:not(.active) {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    /* Top Bar for Subpages */
    .carnet-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .carnet-title-box {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .carnet-logo-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .carnet-logo-icon svg {
        width: 26px;
        height: 26px;
    }
    .carnet-title-text h1 {
        font-size: 22px;
        font-weight: 900;
        margin: 0;
        line-height: 1.25;
        color: #0f172a;
    }
    .dark .carnet-title-text h1 {
        color: #f8fafc;
    }
    .carnet-title-text p {
        font-size: 13.5px;
        color: #64748b;
        margin: 4px 0 0 0;
        font-weight: 500;
    }
    .dark .carnet-title-text p {
        color: #94a3b8;
    }

    .carnet-actions-box {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .carnet-search-input {
        padding: 9px 14px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13.5px;
        width: 240px;
        background: #ffffff;
        color: #0f172a;
        font-family: inherit;
        outline: none;
        transition: all 0.2s ease;
    }
    .carnet-search-input:focus {
        border-color: #be123c;
        box-shadow: 0 0 0 3px rgba(190, 18, 60, 0.15);
    }
    .dark .carnet-search-input {
        background: #1e293b;
        border-color: #475569;
        color: #f8fafc;
    }
    .carnet-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .carnet-btn-action:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .dark .carnet-btn-action {
        background: #1e293b;
        border-color: #475569;
        color: #e2e8f0;
    }

    /* Video Switcher Tabs (Parts) */
    .carnet-video-switcher {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }
    .carnet-video-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 800;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        transition: all 0.2s ease;
        font-family: inherit;
    }
    .carnet-video-tab-btn:hover {
        background: #f1f5f9;
        border-color: #be123c;
        color: #be123c;
    }
    .carnet-video-tab-btn.active {
        background: linear-gradient(135deg, #881337 0%, #be123c 100%);
        color: #ffffff;
        border-color: #be123c;
        box-shadow: 0 4px 12px rgba(190, 18, 60, 0.3);
    }
    .dark .carnet-video-tab-btn {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }
    .dark .carnet-video-tab-btn.active {
        background: linear-gradient(135deg, #be123c 0%, #e11d48 100%);
        color: #ffffff;
    }

    /* Secondary Navigation Tabs */
    .carnet-secondary-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 24px;
        overflow-x: auto;
        scrollbar-width: thin;
        white-space: nowrap;
    }
    .dark .carnet-secondary-tabs {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .dark .carnet-tab-link {
        color: #cbd5e1;
    }
    .carnet-tab-link:hover {
        background: #ffffff;
        color: #be123c;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .dark .carnet-tab-link:hover {
        background: #0f172a;
        color: #fb7185;
    }

    /* Layout: Sidebar + Main Content */
    .carnet-content-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 26px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .carnet-content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Sidebar Index */
    .carnet-sidebar-index {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        position: sticky;
        top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .dark .carnet-sidebar-index {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-sidebar-index h3 {
        font-size: 15px;
        font-weight: 800;
        margin: 0 0 14px 0;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .carnet-sidebar-index h3 {
        color: #f8fafc;
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
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .dark .carnet-index-item a {
        color: #cbd5e1;
    }
    .carnet-index-item a:hover {
        background: #f1f5f9;
        color: #be123c;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a:hover {
        background: #0f172a;
        color: #fb7185;
    }

    /* Main Flow */
    .carnet-main-flow {
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    /* Video Player Card */
    .carnet-video-head {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    .carnet-play-badge {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }
    .carnet-video-head h2 {
        font-size: 19px;
        font-weight: 800;
        margin: 0;
        color: #0f172a;
    }
    .dark .carnet-video-head h2 {
        color: #f8fafc;
    }
    .carnet-video-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 0 0 16px 0;
        line-height: 1.6;
    }
    .dark .carnet-video-subtitle {
        color: #94a3b8;
    }
    .carnet-video-box {
        background: #000000;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0,0,0,0.18);
        border: 1px solid #334155;
    }
    .carnet-video-element {
        width: 100%;
        max-height: 520px;
        display: block;
        background: #000000;
    }
    .carnet-video-caption {
        background: #0f172a;
        color: #94a3b8;
        padding: 10px 18px;
        font-size: 12.5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-top: 1px solid #1e293b;
        flex-wrap: wrap;
    }
    .carnet-video-caption code {
        background: #1e293b;
        color: #38bdf8;
        padding: 2px 8px;
        border-radius: 6px;
        direction: ltr;
        display: inline-block;
    }

    /* Content Cards */
    .carnet-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .dark .carnet-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-card-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .carnet-card-icon-badge {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .carnet-card-title {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: #0f172a;
    }
    .dark .carnet-card-title {
        color: #f8fafc;
    }
    .carnet-card-text {
        font-size: 14px;
        line-height: 1.8;
        color: #475569;
        margin: 0;
    }
    .dark .carnet-card-text {
        color: #cbd5e1;
    }

    /* Fields Grid */
    .carnet-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 14px;
        margin-top: 16px;
    }
    .carnet-field-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .carnet-field-card:hover {
        background: #ffffff;
        border-color: #be123c;
        box-shadow: 0 4px 14px rgba(190, 18, 60, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-field-card {
        background: #0f172a;
        border-color: #334155;
    }
    .dark .carnet-field-card:hover {
        background: #1e293b;
        border-color: #e11d48;
    }
    .carnet-field-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }
    .carnet-field-code {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
    }
    .dark .carnet-field-code {
        color: #f8fafc;
    }
    .carnet-field-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .badge-req {
        background: #fee2e2;
        color: #991b1b;
    }
    .badge-opt {
        background: #f1f5f9;
        color: #475569;
    }
    .badge-calc {
        background: #dbeafe;
        color: #1e40af;
    }
    .badge-sync {
        background: #ede9fe;
        color: #6b21a8;
    }
    .badge-doc {
        background: #fef3c7;
        color: #92400e;
    }
    .badge-integ {
        background: #ffe4e6;
        color: #be123c;
    }
    .carnet-field-body p {
        font-size: 13px;
        line-height: 1.65;
        color: #475569;
        margin: 0;
    }
    .dark .carnet-field-body p {
        color: #cbd5e1;
    }

    /* Workflow Cards */
    .carnet-workflow-box {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .carnet-step-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    .dark .carnet-step-card {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-step-number {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #be123c;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 900;
        flex-shrink: 0;
    }
    .carnet-step-details h4 {
        font-size: 14px;
        font-weight: 800;
        margin: 0 0 4px 0;
        color: #0f172a;
    }
    .dark .carnet-step-details h4 {
        color: #f8fafc;
    }
    .carnet-step-details p {
        font-size: 13px;
        line-height: 1.65;
        color: #64748b;
        margin: 0;
    }
    .dark .carnet-step-details p {
        color: #94a3b8;
    }

    /* Tips Box */
    .carnet-tips-box {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .carnet-tip-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        background: #f8fafc;
        border-radius: 10px;
        border-inline-start: 4px solid #be123c;
        font-size: 13px;
        line-height: 1.65;
        color: #334155;
    }
    .dark .carnet-tip-item {
        background: #0f172a;
        color: #cbd5e1;
    }

    /* FAQ */
    .carnet-faq-box {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .carnet-faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .dark .carnet-faq-item {
        border-color: #334155;
    }
    .carnet-faq-header {
        padding: 14px 18px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        cursor: pointer;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        user-select: none;
    }
    .dark .carnet-faq-header {
        background: #0f172a;
        color: #f8fafc;
    }
    .carnet-faq-body {
        padding: 12px 16px;
        font-size: 13.5px;
        line-height: 1.7;
        color: #475569;
        border-top: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .dark .carnet-faq-body {
        color: #cbd5e1;
        border-color: #334155;
        background: #1e293b;
    }

    /* Grand Cards Grid (Hub) */
    .carnet-grand-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    .carnet-grand-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    .carnet-grand-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -6px rgba(0,0,0,0.08);
        border-color: #be123c;
    }
    .dark .carnet-grand-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark .carnet-grand-card:hover {
        border-color: #e11d48;
        box-shadow: 0 12px 24px -6px rgba(0,0,0,0.3);
    }
    .carnet-grand-card.featured {
        border-top: 4px solid #be123c;
    }
    .carnet-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .carnet-card-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .carnet-card-icon-wrap svg {
        width: 24px;
        height: 24px;
    }
    .bg-icon-rose {
        background: rgba(190, 18, 60, 0.12);
        color: #be123c;
    }
    .bg-icon-amber {
        background: rgba(217, 119, 6, 0.12);
        color: #d97706;
    }
    .bg-icon-indigo {
        background: rgba(79, 70, 229, 0.12);
        color: #4f46e5;
    }
    .carnet-card-badges {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .carnet-card-badge-pill {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .pill-video {
        background: #ede9fe;
        color: #6d28d9;
    }
    .pill-success {
        background: #ffe4e6;
        color: #be123c;
    }
    .carnet-card-middle h3 {
        font-size: 17px;
        font-weight: 800;
        margin: 0 0 4px 0;
        color: #0f172a;
    }
    .dark .carnet-card-middle h3 {
        color: #f8fafc;
    }
    .carnet-card-en-title {
        font-size: 11.5px;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 8px;
    }
    .carnet-card-desc {
        font-size: 13px;
        line-height: 1.65;
        color: #64748b;
        margin: 0 0 16px 0;
    }
    .dark .carnet-card-desc {
        color: #94a3b8;
    }

    .carnet-card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;
        flex-wrap: wrap;
    }
    .carnet-btn-open-guide {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        background: linear-gradient(135deg, #881337 0%, #be123c 100%);
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
        box-shadow: 0 4px 10px rgba(190, 18, 60, 0.25);
    }
    .carnet-btn-open-guide:hover {
        background: linear-gradient(135deg, #be123c 0%, #e11d48 100%);
        transform: translateY(-2px);
    }
    .carnet-btn-screen-direct {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        background: #f1f5f9;
        color: #334155;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
    }
    .carnet-btn-screen-direct:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .carnet-btn-screen-direct {
        background: #334155;
        color: #e2e8f0;
        border-color: #475569;
    }
</style>

<div class="carnet-wrapper">

    @if(empty($activeSub) || !in_array($activeSub, ['violations-fund', 'violations', 'regulations', 'transactions', 'fund']))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم المخالفات والجزاءات (Violations Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">⚠️ {{ __('documentation.violations_guide.tag') }}</span>
            <h2>{{ __('documentation.violations_guide.hub_heading') }}</h2>
            <p>{{ __('documentation.violations_guide.hub_note') }}</p>
        </div>

        <!-- شبكة البطاقات الكبرى لإدارة المخالفات -->
        <div class="carnet-grand-grid">

            <!-- 1. صندوق تحصيل وسجل المخالفات واللوائح (Violations Collection Fund) - Featured Card (شارح الثلاثة معاً) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #be123c; grid-column: 1 / -1;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-rose">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video" style="background: #ffe4e6; color: #be123c; font-weight: 800;">🎬 فيديو شامل (شارح الثلاثة معاً)</span>
                        <span class="carnet-card-badge-pill pill-success">لائحة + سجل + صندوق تحصيل</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #be123c; font-size: 19px;">{{ __('documentation.violations_guide.card_fund_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.violations_guide.card_fund_en') }}</span>
                    <p class="carnet-card-desc" style="font-size: 14px; line-height: 1.7;">
                        {{ __('documentation.violations_guide.card_fund_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('violations-fund')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #881337 0%, #be123c 100%); padding: 11px 20px; font-size: 14px;">
                        <span>مشاهدة الفيديو الشامل والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/violations" class="carnet-btn-screen-direct">
                        <span>سجل المخالفات ⇦</span>
                    </a>
                    <a href="/violation-regulations" class="carnet-btn-screen-direct">
                        <span>لوائح المخالفات ⇦</span>
                    </a>
                    <a href="/violation-transactions" class="carnet-btn-screen-direct">
                        <span>صندوق تحصيل المخالفات ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. سجل المخالفات والجزاءات (Record of Violations) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #e11d48;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-rose">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">رصد وجزاءات</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #be123c;">{{ __('documentation.violations_guide.card_violations_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.violations_guide.card_violations_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.violations_guide.card_violations_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('violations-fund')" class="carnet-btn-open-guide">
                        <span>عرض الشرح والفيديو ➔</span>
                    </button>
                    <a href="/violations" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. لوائح وأنظمة المخالفات (Violation Regulations) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #4f46e5;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-indigo">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-success">لائحة العمل</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #4f46e5;">{{ __('documentation.violations_guide.card_regulations_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.violations_guide.card_regulations_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.violations_guide.card_regulations_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('violations-fund')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);">
                        <span>عرض الشرح واللوائح ➔</span>
                    </button>
                    <a href="/violation-regulations" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 4. صندوق تحصيل المخالفات (Violations Collection Fund) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #d97706;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0a60.07 60.07 0 0 1 15.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-18 0v12m18-12v12m0 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V18.75m18 0V6.75M6 12h12" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-success">صندوق مالي</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #d97706;">{{ __('documentation.violations_guide.card_transactions_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.violations_guide.card_transactions_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.violations_guide.card_transactions_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('violations-fund')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <span>عرض الشرح والصندوق ➔</span>
                    </button>
                    <a href="/violation-transactions" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

        </div>

    @else
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية الشاملة: Violations Collection Fund
             الفيديو المعتمد: Violations Collection Fund.mp4 (شارح الثلاثة معاً)
             وفيديو: Record of violations.mp4
             ========================================================================== -->

        <!-- 1. شريط التنقل العلوي -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل المخالفات والجزاءات</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" class="carnet-sub-tab-pill active">
                    <span>🎬 الشرح الشامل (شارح الثلاثة معاً)</span>
                </button>
                <a href="/violations" class="carnet-sub-tab-pill">
                    <span>⚠️ سجل المخالفات</span>
                </a>
                <a href="/violation-regulations" class="carnet-sub-tab-pill">
                    <span>📜 لوائح الجزاءات</span>
                </a>
                <a href="/violation-transactions" class="carnet-sub-tab-pill">
                    <span>💰 صندوق تحصيل المخالفات</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #881337 0%, #be123c 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح قسم المخالفات والجزاءات وصندوق التحصيل (Violations Collection Fund)</h1>
                    <p>دليل إداري وقانوني موحد يوضح دورة رصد المخالفة، تطبيق اللائحة المتدرجة، وإيداع الخصومات في صندوق تحصيل الجزاءات.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل المخالفات واللوائح..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('violations-fund')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/violations" class="carnet-btn-action" style="background: #be123c; color: #ffffff; border-color: #be123c;">
                    <span>سجل المخالفات ⇦</span>
                </a>
                <a href="/violation-transactions" class="carnet-btn-action" style="background: #881337; color: #ffffff; border-color: #881337;">
                    <span>صندوق التحصيل ⇦</span>
                </a>
            </div>
        </div>

        <!-- 3. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-vio" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المعتمد (شارح الثلاثة معاً)</span>
            </a>
            <a href="#sec-concept-vio" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة اللائحة والتكامل مع نظام العمل</span>
            </a>
            <a href="#sec-violations-fields" class="carnet-tab-link">
                <span>📋</span>
                <span>دليل حقول سجل المخالفات</span>
            </a>
            <a href="#sec-regulations-fields" class="carnet-tab-link">
                <span>📜</span>
                <span>لوائح الجزاءات وتدرج العقوبة</span>
            </a>
            <a href="#sec-fund-fields" class="carnet-tab-link">
                <span>💰</span>
                <span>صندوق تحصيل المخالفات وصرفه</span>
            </a>
            <a href="#sec-workflow-vio" class="carnet-tab-link">
                <span>🔄</span>
                <span>دورة العمل الإدارية والرقابية</span>
            </a>
            <a href="#sec-faq-vio" class="carnet-tab-link">
                <span>❓</span>
                <span>الأسئلة الشائعة</span>
            </a>
        </nav>

        <!-- 4. محتوى الصفحة: الفهرس الجانبي + التدفق الرئيسي -->
        <div class="carnet-content-grid">

            <!-- الفهرس الجانبي -->
            <aside class="carnet-sidebar-index">
                <h3>
                    <span>📑</span>
                    <span>محتويات الدليل</span>
                </h3>
                <ul class="carnet-index-list">
                    <li class="carnet-index-item">
                        <a href="#sec-video-vio">
                            <span class="icon">🎬</span>
                            <span>الفيديو الشامل المعتمد</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-vio">
                            <span class="icon">💡</span>
                            <span>فكرة اللائحة والامتثال</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-violations-fields">
                            <span class="icon">📋</span>
                            <span>سجل وتوثيق المخالفات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-regulations-fields">
                            <span class="icon">📜</span>
                            <span>لوائح الجزاءات والتدرج</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fund-fields">
                            <span class="icon">💰</span>
                            <span>صندوق تحصيل الجزاءات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-vio">
                            <span class="icon">🔄</span>
                            <span>دورة العمل خطوة بخطوة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-vio">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل الفيديو الشامل المعتمد (Violations Collection Fund) -->
                <section id="sec-video-vio" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #881337 0%, #be123c 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح قسم المخالفات وصندوق التحصيل (Violations Collection Fund)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل والشامل الذي يوضح دورة العمل المشتركة بين سجل المخالفات، لوائح وأنظمة الجزاءات، وصندوق تحصيل المخالفات في فيديو موحد:
                    </p>

                    <!-- أزرار التبديل السريع بين الفيديو الشامل وفيديو سجل المخالفات -->
                    <div class="carnet-video-switcher">
                        <button type="button"
                                id="vioBtnFund"
                                onclick="switchViolationsVideo(1)"
                                class="carnet-video-tab-btn active">
                            <span>📹 الفيديو الشامل (شارح الثلاثة معاً: Violations Collection Fund)</span>
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.25);">الفيديو الأساسي</span>
                        </button>

                        <button type="button"
                                id="vioBtnRecord"
                                onclick="switchViolationsVideo(2)"
                                class="carnet-video-tab-btn">
                            <span>📹 فيديو سجل وتوثيق المخالفات (Record of violations)</span>
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.08);">سجل المخالفات</span>
                        </button>
                    </div>

                    <div class="carnet-video-box">
                        <!-- مشغل الفيديو الشامل: Violations Collection Fund.mp4 -->
                        <div id="vioVideoContainer1">
                            <video controls preload="auto" class="carnet-video-element" id="playerViolationsFund">
                                <source src="{{ asset('videos/Violations Collection Fund.mp4') }}" type="video/mp4">
                                <source src="/videos/Violations Collection Fund.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Violations%20Collection%20Fund.mp4') }}" type="video/mp4">
                                <source src="/videos/Violations%20Collection%20Fund.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Violations Collection Fund.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Violations Collection Fund.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو شامل (شارح الثلاثة معاً): سجل المخالفات، لوائح وأنظمة الجزاءات، وصندوق تحصيل المخالفات</span>
                                <span>الملف: <code>/videos/Violations Collection Fund.mp4</code></span>
                            </div>
                        </div>

                        <!-- مشغل فيديو سجل المخالفات: Record of violations.mp4 -->
                        <div id="vioVideoContainer2" style="display: none;">
                            <video controls preload="auto" class="carnet-video-element" id="playerViolationsRecord">
                                <source src="{{ asset('videos/Record of violations.mp4') }}" type="video/mp4">
                                <source src="/videos/Record of violations.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Record%20of%20violations.mp4') }}" type="video/mp4">
                                <source src="/videos/Record%20of%20violations.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Record of violations.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Record of violations.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل: رصد وتوثيق المخالفات وتطبيق الجزاءات المقررة</span>
                                <span>الملف: <code>/videos/Record of violations.mp4</code></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة اللائحة والامتثال لنظام العمل -->
                <section id="sec-concept-vio" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(190, 18, 60, 0.15); color: #be123c;">💡</span>
                        <h2 class="carnet-card-title">فكرة النظام والتكامل مع لائحة الجزاءات ونظام العمل السعودي</h2>
                    </div>
                    <p class="carnet-card-text">
                        يُطبق هذا القسم المعايير النظامية الواردة في اللائحة التنفيذية لنظام العمل السعودي لإدارة الانضباط الوظيفي بطريقة شفافة وعادلة تضمن حفظ حقوق المنشأة والموظف معاً:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #be123c;">
                            <span>⚖️</span>
                            <div>
                                <strong>تدرج العقوبات والمخالفات المعتمدة:</strong>
                                لا تُفرض العقوبة عشوائياً، بل يستند النظام إلى جدول تدرج الجزاء (المرة الأولى: إنذار/لفت نظر، المرة الثانية: خصم نسبة، المرة الثالثة: خصم كامل، المرة الرابعة: إيقاف/فصل)، مع تحديد فترة التقادم القانونية.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>💰</span>
                            <div>
                                <strong>صندوق حصيلة الجزاءات (حساب نظامي مخصص):</strong>
                                وفقاً للمادة (73) من نظام العمل، فإن مبالغ الخصومات الناتجة عن الجزاءات لا تُعتبر إيراداً للشركة، بل تُودع في صندوق خاص مستقل يُصرف منه على الأنشطة الاجتماعية والرعاية الطبية والمكافآت التقديرية لكافة العاملين.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: دليل حقول شاشة سجل المخالفات -->
                <section id="sec-violations-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(190, 18, 60, 0.15); color: #be123c;">📋</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة سجل المخالفات (Violations Log)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تتضمن شاشة تسجيل المخالفة <code>/violations</code> حقول التوثيق والتحقيق الإداري:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الموظف المخالف (Employee)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>اختيار الموظف، ويقوم النظام تلقائياً بربط منشأته، قسمه، مسماه الوظيفي، وراتبه الأساسي لاحتساب قيمة الخصم.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع وتصنيف المخالفة</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>اختيار بند المخالفة من لائحة الجزاءات (تأخر، غياب، مخالفة تعليمات السلامة، عدم الانضباط في الوردية).</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">تكرار المخالفة (Degree / Tier)</span>
                                <span class="carnet-field-badge badge-sync">آلي / محدد</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد سابقة الموظف في نفس المخالفة: المرة الأولى، الثانية، الثالثة، أو الرابعة لتطبيق الجزاء المناسب.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع الجزاء المطبق (Penalty Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>إنذار كتابي، لفت نظر، حسم من الراتب (عدد أيام أو نسبة محددة)، إيقاف مؤقت، أو إحالة للتحقيق.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">قيمة الحسم المالي (Deduction)</span>
                                <span class="carnet-field-badge badge-calc">حساب آلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>في حال تضمن الجزاء خصماً مالياً، يُحسب المبلغ بدقة وترحيله تلقائياً لمسير الراتب الشهري ولصندوق التحصيل.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">تاريخ المخالفة والواقعة</span>
                                <span class="carnet-field-badge badge-doc">زمني</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تاريخ ارتكاب المخالفة وتاريخ إيقاع الجزاء لتوثيق المدد النظامية وسقوط المخالفة بمضي المدة.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 4: دليل لوائح وأنظمة الجزاءات -->
                <section id="sec-regulations-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(79, 70, 229, 0.15); color: #4f46e5;">📜</span>
                        <h2 class="carnet-card-title">لوائح وأنظمة وتصنيفات المخالفات (Violation Regulations)</h2>
                    </div>
                    <p class="carnet-card-text">
                        توفر شاشة <code>/violation-regulations</code> الهيكل القانوني المعتمد للمخالفات في المنشأة، وتتضمن:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">مسمى المخالفة والتوصيف</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>النص الدقيق للمخالفة وفق صياغة نموذج لائحة تنظيم العمل الموحدة المعتمدة من وزارة الموارد البشرية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">مصفوفة تدرج العقوبات (Penalty Matrix)</span>
                                <span class="carnet-field-badge badge-integ">متدرج</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد عقوبة كل تكرار بدقة: عقوبة المرة الأولى، المرة الثانية، المرة الثالثة، والمرة الرابعة لتوحيد القرارات.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">أقسام وتصنيفات المخالفات</span>
                                <span class="carnet-field-badge badge-sync">تصنيف</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تقسيم المخالفات إلى: مخالفات مواعيد العمل، مخالفات السلوك والانضباط، مخالفات السلامة والصحة المهنية، ومخالفات الإضرار بالعمل.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: صندوق تحصيل المخالفات -->
                <section id="sec-fund-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">💰</span>
                        <h2 class="carnet-card-title">صندوق تحصيل المخالفات (Violations Collection Fund)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُسجل شاشة <code>/violation-transactions</code> حركة الصندوق التكافلي للجزاءات وتتكامل مع الخزينة ومسيرات الرواتب:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">حركات الإيداع الآلية (Deposits)</span>
                                <span class="carnet-field-badge badge-calc">ربط مع الرواتب</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>عند اعتماد مسير الرواتب الشهري، تُرحل كافة مبالغ استقطاعات الجزاءات تلقائياً إلى رصيد هذا الصندوق.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">سندات الصرف المعتمدة (Disbursements)</span>
                                <span class="carnet-field-badge badge-doc">صرف نظامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>توثيق مصارف الصندوق النظامية: مساعدات إنسانية للموظفين، مكافآت التميز، أو دعم الأنشطة الاجتماعية والرياضية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الرصيد التراكمي والإحصائيات</span>
                                <span class="carnet-field-badge badge-integ">إحصائي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>شاشة إحصائية تعرض إجمالي المبالغ المحصلة، إجمالي المنصرف، والرصيد النقدي المتاح في الصندوق في أي وقت.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: دورة العمل خطوة بخطوة -->
                <section id="sec-workflow-vio" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(190, 18, 60, 0.15); color: #be123c;">🔄</span>
                        <h2 class="carnet-card-title">دورة العمل الإدارية والرقابية خطوة بخطوة</h2>
                    </div>

                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-details">
                                <h4>ضبط لوائح وتصنيفات المخالفات</h4>
                                <p>التأكد من اعتماد جدول المخالفات وتدرج العقوبات في شاشة <code>/violation-regulations</code> المتوافقة مع نظام العمل.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-details">
                                <h4>رصد المخالفة وتوثيق الواقعة</h4>
                                <p>الدخول لشاشة <code>/violations</code> وتسجيل الواقعة، اختيار الموظف ونوع المخالفة وتاريخها مع إرفاق محضر التحقيق أو الإفادة.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-details">
                                <h4>احتساب درجة التكرار وإيقاع الجزاء</h4>
                                <p>التحقق من سوابق الموظف وإصدار القرار الإداري بالجزاء المقرر (إنذار، خصم مالي، إلخ).</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-details">
                                <h4>الربط الآلي مع مسير الرواتب</h4>
                                <p>في حال وجود خصم مالي، يُدرج الخصم تلقائياً في خانة الجزاءات بمسير راتب الشهر المستحق.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">5</span>
                            <div class="carnet-step-details">
                                <h4>الترحيل لصندوق تحصيل الجزاءات</h4>
                                <p>يتم تحويل قيمة الخصم لحساب <code>/violation-transactions</code> لتوظيفها لاحقاً في أوجه الرعاية المقررة نظاماً.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 7: الأسئلة الشائعة -->
                <section id="sec-faq-vio" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(190, 18, 60, 0.15); color: #be123c;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول قسم المخالفات وصندوق التحصيل</h2>
                    </div>

                    <div class="carnet-faq-box">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>لماذا يشرح فيديو Violations Collection Fund الشاشات الثلاث معاً؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                لأنها تمثل دورة نظامية مترابطة ومتكاملة: اللائحة تحدد العقوبة، وسجل المخالفات يوثق الواقعة، وصندوق التحصيل يستقبل مبالغ الخصومات ويتابع حركتها المالية، ولذا جاء الشرح شاملاً وموحداً لتغطية الدورة كاملة.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يحق للمنشأة الاستفادة من مبالغ جزاءات الموظفين كأرباح؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نظاماً ووفق المادة 73 من نظام العمل السعودي، لا يجوز لصاحب العمل التصرف في الغرامات المقتطعة لمنفعته الخاصة، بل تُخصص للخدمات الاجتماعية والأنشطة الترفيهية والمكافآت لعموم العاملين بالمنشأة.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>أين يتم حفظ ملف الفيديو في السيرفر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يتواجد الفيديو في المسار <code>public/videos/Violations Collection Fund.mp4</code> كما يتوفر أيضاً فيديو سجل المخالفات <code>public/videos/Record of violations.mp4</code> ويمكن التبديل بينهما مباشرة عبر هذا الدليل.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        </div>

    @endif

</div>

<!-- دوال التفاعل مع المحتوى والبحث المباشر والتبديل بين الفيديوهات ونسخ الروابط -->
<script>
    function copyCarnetShareLink(sub = 'violations-fund') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=violations&activeSub=' + sub + '#sec-video-vio';
        navigator.clipboard.writeText(url).then(function() {
            alert('تم نسخ رابط صفحة الشرح بنجاح!');
        }).catch(function() {
            prompt('انسخ رابط الصفحة من هنا:', url);
        });
    }

    function filterCarnetContent() {
        const query = (document.getElementById('carnetSearchInput')?.value || '').trim().toLowerCase();
        const sections = document.querySelectorAll('.carnet-searchable-section');

        sections.forEach(sec => {
            if (!query) {
                sec.style.display = '';
                return;
            }
            const text = sec.innerText.toLowerCase();
            sec.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function switchViolationsVideo(part) {
        const v1 = document.getElementById('vioVideoContainer1');
        const v2 = document.getElementById('vioVideoContainer2');
        const b1 = document.getElementById('vioBtnFund');
        const b2 = document.getElementById('vioBtnRecord');
        const p1 = document.getElementById('playerViolationsFund');
        const p2 = document.getElementById('playerViolationsRecord');

        if (part === 1) {
            if (v1) v1.style.display = 'block';
            if (v2) v2.style.display = 'none';
            if (b1) b1.classList.add('active');
            if (b2) b2.classList.remove('active');
            if (p2) p2.pause();
        } else {
            if (v1) v1.style.display = 'none';
            if (v2) v2.style.display = 'block';
            if (b1) b1.classList.remove('active');
            if (b2) b2.classList.add('active');
            if (p1) p1.pause();
            if (p2) {
                try {
                    if (p2.readyState === 0) p2.load();
                } catch(e) {}
            }
        }
    }
</script>
