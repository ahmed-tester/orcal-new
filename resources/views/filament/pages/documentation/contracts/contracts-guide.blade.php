{{-- 
    دليل قسم المبيعات والعقود والفوترة
    Sales, Contracts & Invoicing Guide
    Design System: CarNet Interactive Design (Tajawal, Glassmorphism, Dual Themes)
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

    /* Hero Box for Contracts Hub */
    .carnet-hub-hero-box {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
        border-radius: 20px;
        padding: 36px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.35);
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
        color: #2563eb;
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
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        transform: translateX(3px);
    }
    .dark .carnet-sub-back-btn {
        background: #334155;
        color: #60a5fa;
        border-color: #475569;
    }
    .dark .carnet-sub-back-btn:hover {
        background: #2563eb;
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
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
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
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
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
        border-color: #2563eb;
        color: #2563eb;
    }
    .carnet-video-tab-btn.active {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    .dark .carnet-video-tab-btn {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }
    .dark .carnet-video-tab-btn.active {
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
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
        color: #2563eb;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .dark .carnet-tab-link:hover {
        background: #0f172a;
        color: #60a5fa;
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
        color: #2563eb;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a:hover {
        background: #0f172a;
        color: #60a5fa;
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
        border-color: #2563eb;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-field-card {
        background: #0f172a;
        border-color: #334155;
    }
    .dark .carnet-field-card:hover {
        background: #1e293b;
        border-color: #3b82f6;
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
        background: #e0f2fe;
        color: #0284c7;
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
        background: #2563eb;
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
        border-inline-start: 4px solid #2563eb;
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
        border-color: #2563eb;
    }
    .dark .carnet-grand-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark .carnet-grand-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 12px 24px -6px rgba(0,0,0,0.3);
    }
    .carnet-grand-card.featured {
        border-top: 4px solid #2563eb;
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
    .bg-icon-blue {
        background: rgba(37, 99, 235, 0.12);
        color: #2563eb;
    }
    .bg-icon-emerald {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }
    .bg-icon-purple {
        background: rgba(124, 58, 237, 0.12);
        color: #7c3aed;
    }
    .bg-icon-amber {
        background: rgba(217, 119, 6, 0.12);
        color: #d97706;
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
        background: #dbeafe;
        color: #1e40af;
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
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }
    .carnet-btn-open-guide:hover {
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
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

    @if(empty($activeSub) || !in_array($activeSub, ['sales-contracts', 'sales', 'contracts', 'offers', 'invoices', 'customers', 'services']))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم المبيعات والعقود والفوترة (Contracts Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">📑 {{ __('documentation.contracts_guide.tag') }}</span>
            <h2>{{ __('documentation.contracts_guide.hub_heading') }}</h2>
            <p>{{ __('documentation.contracts_guide.hub_note') }}</p>
        </div>

        <!-- شبكة البطاقات الكبرى لإدارة المبيعات والعقود -->
        <div class="carnet-grand-grid">

            <!-- 1. عروض الأسعار والعقود والفوترة (Sales, Contracts & Invoices) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #2563eb;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-blue">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو من جزأين</span>
                        <span class="carnet-card-badge-pill pill-success">عقود وفواتير</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #2563eb;">{{ __('documentation.contracts_guide.card_sales_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.contracts_guide.card_sales_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.contracts_guide.card_sales_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('sales-contracts')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/offers" class="carnet-btn-screen-direct">
                        <span>شاشة عروض الأسعار ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. الفواتير وسندات الخدمات (Invoices & Service Orders) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #10b981;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0a60.07 60.07 0 0 1 15.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-18 0v12m18-12v12m0 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V18.75m18 0V6.75M6 12h12" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">فوترة ضريبية</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #059669;">{{ __('documentation.contracts_guide.card_invoices_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.contracts_guide.card_invoices_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.contracts_guide.card_invoices_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('invoices')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                        <span>عرض فيديو الفواتير والدليل ➔</span>
                    </button>
                    <a href="/invoices" class="carnet-btn-screen-direct">
                        <span>الانتقال لشاشة الفواتير ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. العملاء والجهات المستفيدة (Customers & Beneficiaries) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #7c3aed;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">بيانات العملاء</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #7c3aed;">{{ __('documentation.contracts_guide.card_customers_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.contracts_guide.card_customers_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.contracts_guide.card_customers_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('customers')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/customers" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 4. قائمة أسعار الخدمات (Services & Pricing) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #d97706;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">باقات وتسعير</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #d97706;">{{ __('documentation.contracts_guide.card_services_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.contracts_guide.card_services_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.contracts_guide.card_services_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('services')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/services" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

        </div>

    @elseif(in_array($activeSub, ['sales-contracts', 'sales', 'contracts', 'offers']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: المبيعات والعقود والفوترة (Sales, contracts, and invoices)
             فيديو من جزأين: Sales, contracts, and invoices.mp4 + Sales, contracts, and invoices 2.mp4
             ========================================================================== -->

        <!-- 1. شريط التنقل العلوي -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل المبيعات والعقود</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('sales-contracts')" class="carnet-sub-tab-pill active">
                    <span>📑 عروض الأسعار والعقود والفوترة</span>
                </button>
                <button type="button" wire:click="setSub('invoices')" class="carnet-sub-tab-pill">
                    <span>🧾 الفواتير وسندات الخدمات</span>
                </button>
                <a href="/customers" class="carnet-sub-tab-pill">
                    <span>👥 العملاء</span>
                </a>
                <a href="/services" class="carnet-sub-tab-pill">
                    <span>🏷️ قائمة الخدمات</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح المبيعات والعقود والفوترة (Sales, Contracts and Invoices)</h1>
                    <p>دليل شامل لإعداد عروض الأسعار، إبرام العقود المعتمدة، جدولة الدفعات، وإصدار الفواتير وسندات استحقاق الخدمات.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل المبيعات والعقود..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('sales-contracts')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/offers" class="carnet-btn-action" style="background: #2563eb; color: #ffffff; border-color: #2563eb;">
                    <span>فتح شاشة عروض الأسعار ⇦</span>
                </a>
                <a href="/invoices" class="carnet-btn-action" style="background: #10b981; color: #ffffff; border-color: #10b981;">
                    <span>فتح شاشة الفواتير ⇦</span>
                </a>
            </div>
        </div>

        <!-- 3. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-contracts" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل (الجزء 1 & 2)</span>
            </a>
            <a href="#sec-concept-contracts" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة الدورة التجارية والتكامل المالي</span>
            </a>
            <a href="#sec-offers-fields" class="carnet-tab-link">
                <span>📋</span>
                <span>دليل حقول شاشة عروض الأسعار والعقود</span>
            </a>
            <a href="#sec-workflow-contracts" class="carnet-tab-link">
                <span>🔄</span>
                <span>دورة العمل خطوة بخطوة</span>
            </a>
            <a href="#sec-faq-contracts" class="carnet-tab-link">
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
                        <a href="#sec-video-contracts">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل (جزأين)</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-contracts">
                            <span class="icon">💡</span>
                            <span>فكرة الدورة التجارية</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-offers-fields">
                            <span class="icon">📋</span>
                            <span>دليل حقول العقود وعروض الأسعار</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-contracts">
                            <span class="icon">🔄</span>
                            <span>دورة العمل التشغيلية</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-contracts">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو المبيعات والعقود والفوترة (جزأين مع أزرار تبديل تفاعلية) -->
                <section id="sec-video-contracts" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح المبيعات والعقود والفوترة (Sales, contracts, and invoices)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لدورة المبيعات مقسمة إلى جزأين متكاملين لتغطية إعداد عروض الأسعار، إبرام العقود، وإصدار الفواتير وسندات الخدمات:
                    </p>

                    <!-- أزرار التبديل السريع بين الجزء الأول والجزء الثاني -->
                    <div class="carnet-video-switcher">
                        <button type="button"
                                id="contractsBtnPart1"
                                onclick="switchContractsVideo(1)"
                                class="carnet-video-tab-btn active">
                            <span>📹 الجزء الأول: عروض الأسعار وصياغة العقود الرسمية</span>
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.25);">الجزء 1</span>
                        </button>

                        <button type="button"
                                id="contractsBtnPart2"
                                onclick="switchContractsVideo(2)"
                                class="carnet-video-tab-btn">
                            <span>📹 الجزء الثاني: اعتماد العقود، سندات الخدمات، والفوترة</span>
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.08);">الجزء 2</span>
                        </button>

                        <button type="button"
                                id="contractsBtnInvoices"
                                onclick="switchContractsVideo(3)"
                                class="carnet-video-tab-btn">
                            <span>🧾 شرح الفواتير التخصصي (Invoices)</span>
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.08);">فيديو 3</span>
                        </button>
                    </div>

                    <div class="carnet-video-box">
                        <!-- مشغل الجزء الأول -->
                        <div id="contractsVideoContainer1">
                            <video controls preload="auto" class="carnet-video-element" id="playerContracts1">
                                <source src="{{ asset('videos/Sales, contracts, and invoices.mp4') }}" type="video/mp4">
                                <source src="/videos/Sales, contracts, and invoices.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو مسجل (الجزء 1): دورة إعداد عروض الأسعار، صياغة البنود، وإبرام العقود الرسمية</span>
                                <span>الملف: <code>/videos/Sales, contracts, and invoices.mp4</code></span>
                            </div>
                        </div>

                        <!-- مشغل الجزء الثاني -->
                        <div id="contractsVideoContainer2" style="display: none;">
                            <video controls preload="auto" class="carnet-video-element" id="playerContracts2">
                                <source src="{{ asset('videos/Sales, contracts, and invoices 2.mp4') }}" type="video/mp4">
                                <source src="/videos/Sales, contracts, and invoices 2.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو مسجل (الجزء 2): استكمال اعتماد العقود، سندات استحقاق الخدمات، وإصدار الفواتير</span>
                                <span>الملف: <code>/videos/Sales, contracts, and invoices 2.mp4</code></span>
                            </div>
                        </div>

                        <!-- مشغل فيديو الفواتير المباشر -->
                        <div id="contractsVideoContainer3" style="display: none;">
                            <video controls preload="auto" class="carnet-video-element" id="playerContracts3">
                                <source src="{{ asset('videos/Invoices.mp4') }}" type="video/mp4">
                                <source src="/videos/Invoices.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل: دورة الفواتير وسندات الخدمات الضريبية ومتابعة التحصيل</span>
                                <span>الملف: <code>/videos/Invoices.mp4</code></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف الشاشات -->
                <section id="sec-concept-contracts" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">💡</span>
                        <h2 class="carnet-card-title">فكرة الدورة التجارية والتكامل المالي والإداري</h2>
                    </div>
                    <p class="carnet-card-text">
                        يُشكل قسم <strong>المبيعات والعقود والفوترة</strong> محور النشاط الإيرادي في المنشأة، حيث يبدأ من فتح ملف العميل وحتى التحصيل المالي النهائي:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #2563eb;">
                            <span>📑</span>
                            <div>
                                <strong>عروض الأسعار والعقود (Quotations & Binding Contracts):</strong>
                                إنشاء عرض سعر رسمي للعميل يحتوي على بنود الخدمات وقيمتها والخصومات والضرائب. بمجرد موافقة العميل، يتحول العرض بضغطة زر إلى عقد رسمي ملزم يتضمن شروط التعاقد والدفعات.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #10b981;">
                            <span>🧾</span>
                            <div>
                                <strong>الفواتير الضريبية وسندات استحقاق الخدمات:</strong>
                                توليد الفواتير المرتبطة بالعقد أو فواتير الخدمات المنفصلة، واحتساب ضريبة القيمة المضافة 15%، والربط المباشر مع حركة الخزينة وسندات القبض ومسيرات التحصيل.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: دليل حقول شاشة عروض الأسعار والعقود -->
                <section id="sec-offers-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">📋</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة عروض الأسعار والعقود (Offers & Contracts)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تشمل شاشة العقد وعرض السعر الحقول المنظمة للاتفاق التجاري والمالي:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">تصنيف العقد (Contract Category)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>نوع القالب المعتمد (عرض سعر مبدئي، عقد عمل موحد، عقد توريد وخدمات)، حيث يستدعي النموذج الشروط والأحكام الافتراضية فوراً.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">العميل المستفيد (Customer)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>اختيار العميل أو الجهة المستفيدة من قاعدة بيانات العملاء، واستدعاء الرقم الضريبي وعنوان المقر والسجل التجاري تلقائياً.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الشركة المصدرة (Company)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>منشأة المؤسسة التابع لها التعاقد، لوضع الشعار الرسمي والترويسة والختم الإلكتروني المعتمد على الورقة الرسمية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">تاريخ السريان والصلاحية</span>
                                <span class="carnet-field-badge badge-doc">زمني</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد تاريخ بدء العقد، وتاريخ الانتهاء، ومدة صلاحية العرض المالي قبل اعتماده النهائي.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card" style="grid-column: 1 / -1;">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">جدول بنود الخدمات والكميات (Items Repeater)</span>
                                <span class="carnet-field-badge badge-integ">جدول مالي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>إضافة الخدمات من قائمة الأسعار، تحديد الكمية، سعر الوحدة، نسبة الخصم، ونسبة الضريبة، ليقوم النظام بحساب الإجمالي الصافي تلقائياً.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 4: دورة العمل خطوة بخطوة -->
                <section id="sec-workflow-contracts" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">🔄</span>
                        <h2 class="carnet-card-title">دورة إبرام العقود والفوترة خطوة بخطوة</h2>
                    </div>

                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-details">
                                <h4>تسجيل بيانات العميل وبنك الخدمات</h4>
                                <p>التأكد من تسجيل العميل في شاشة <code>/customers</code> وإضافة الخدمات والأسعار في شاشة <code>/services</code>.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-details">
                                <h4>إنشاء عرض السعر (Quotation)</h4>
                                <p>الدخول لشاشة <code>/offers</code> واختيار قالب العرض، واختيار العميل، وإضافة بنود الخدمات المطلوبة مع الأسعار والشروط.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-details">
                                <h4>المعاينة والطباعة أو الإرسال للعميل</h4>
                                <p>طباعة العرض بهوية الشركة الرسمية أو تصديره للمراجعة والاعتماد من قبل إدارة العميل.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-details">
                                <h4>تحويل العرض لعقد رسمي معتمد</h4>
                                <p>تحديث حالة العرض إلى "معتمد / عقد ساري"، وتوثيق التوقيعات وجدولة دفعات السداد المستحقة.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">5</span>
                            <div class="carnet-step-details">
                                <h4>إصدار الفواتير وسندات استحقاق الخدمات</h4>
                                <p>توليد الفواتير الضريبية عبر شاشة <code>/invoices</code> ومتابعة حالة التحصيل والربط المالي مع الخزينة.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: الأسئلة الشائعة -->
                <section id="sec-faq-contracts" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول المبيعات والعقود والفوترة</h2>
                    </div>

                    <div class="carnet-faq-box">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>لماذا تم تقسيم شرح العقود والفوترة إلى جزأين؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                تم تقسيم الفيديو إلى جزأين لتسهيل المتابعة: الجزء الأول يركز على إعداد عروض الأسعار، صياغة البنود، وإبرام العقد، بينما يركز الجزء الثاني على اعتماد العقود، سندات الخدمات، والفوترة الضريبية.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يمكن تحويل عرض السعر إلى فاتورة مباشرة؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، بعد اعتماد عرض السعر، يمكن توليد الفاتورة الضريبية وجلب جميع بنود الخدمات والأسعار تلقائياً دون إعادة إدخالها.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يتم احتساب ضريبة القيمة المضافة تلقائياً؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، يقوم النظام بحساب ضريبة القيمة المضافة (15%) على البنود الخاضعة للضريبة، وعرض الإجمالي قبل الضريبة وقيمة الضريبة والإجمالي النهائي بدقة.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        </div>

    @elseif(in_array($activeSub, ['invoices']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: الفواتير وسندات الخدمات (Invoices.mp4)
             ========================================================================== -->

        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل المبيعات والعقود</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('sales-contracts')" class="carnet-sub-tab-pill">
                    <span>📑 عروض الأسعار والعقود والفوترة</span>
                </button>
                <button type="button" wire:click="setSub('invoices')" class="carnet-sub-tab-pill active">
                    <span>🧾 الفواتير وسندات الخدمات</span>
                </button>
            </div>
        </div>

        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0a60.07 60.07 0 0 1 15.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-18 0v12m18-12v12m0 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V18.75m18 0V6.75M6 12h12" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح الفواتير وسندات الخدمات (Invoices & Billing)</h1>
                    <p>دليل شامل لإصدار الفواتير الضريبية، توثيق سندات الخدمات، ومتابعة حالات السداد والتحصيل المالي.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <a href="/invoices" class="carnet-btn-action" style="background: #10b981; color: #ffffff; border-color: #10b981;">
                    <span>فتح شاشة الفواتير ⇦</span>
                </a>
            </div>
        </div>

        <section class="carnet-searchable-section" style="margin-bottom: 24px;">
            <div class="carnet-video-head">
                <span class="carnet-play-badge" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">▶</span>
                <h2>مشاهدة فيديو شرح الفواتير وسندات الخدمات (Invoices)</h2>
            </div>
            <div class="carnet-video-box">
                <video controls preload="auto" class="carnet-video-element" id="playerInvoicesDirect">
                    <source src="{{ asset('videos/Invoices.mp4') }}" type="video/mp4">
                    <source src="/videos/Invoices.mp4" type="video/mp4">
                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                </video>
                <div class="carnet-video-caption">
                    <span>📹 فيديو توضيحي مسجل: دورة الفواتير الضريبية وسندات استحقاق الخدمات</span>
                    <span>الملف: <code>/videos/Invoices.mp4</code></span>
                </div>
            </div>
        </section>

    @elseif(in_array($activeSub, ['services', 'service-prices', 'pricing']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: قائمة أسعار الخدمات (Service Price List)
             الفيديو: Service Price List.mp4
             ========================================================================== -->

        <!-- 1. شريط التنقل العلوي -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل المبيعات والعقود</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('sales-contracts')" class="carnet-sub-tab-pill">
                    <span>📑 عروض الأسعار والعقود</span>
                </button>
                <button type="button" wire:click="setSub('invoices')" class="carnet-sub-tab-pill">
                    <span>🧾 الفواتير وسندات الخدمات</span>
                </button>
                <button type="button" wire:click="setSub('services')" class="carnet-sub-tab-pill active">
                    <span>🏷️ قائمة أسعار الخدمات</span>
                </button>
                <a href="/customers" class="carnet-sub-tab-pill">
                    <span>👥 العملاء</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح قائمة أسعار الخدمات (Service Price List)</h1>
                    <p>دليل شامل لإدارة بنك الخدمات، ضبط وتحديث الأسعار القياسية، تصنيف الباقات، والربط مع عروض الأسعار والفواتير.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في قائمة أسعار الخدمات..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('services')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/services" class="carnet-btn-action" style="background: #d97706; color: #ffffff; border-color: #d97706;">
                    <span>فتح شاشة قائمة الخدمات ⇦</span>
                </a>
            </div>
        </div>

        <!-- 3. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-serv" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل (Service Price List)</span>
            </a>
            <a href="#sec-concept-serv" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة بنك الخدمات والتكامل التجاري</span>
            </a>
            <a href="#sec-fields-serv" class="carnet-tab-link">
                <span>📋</span>
                <span>دليل حقول شاشة الخدمات والأسعار</span>
            </a>
            <a href="#sec-workflow-serv" class="carnet-tab-link">
                <span>🔄</span>
                <span>دورة إدارة الأسعار خطوة بخطوة</span>
            </a>
            <a href="#sec-faq-serv" class="carnet-tab-link">
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
                        <a href="#sec-video-serv">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-serv">
                            <span class="icon">💡</span>
                            <span>فكرة الشاشة والتكامل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-serv">
                            <span class="icon">📋</span>
                            <span>دليل الحقول والتسعير</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-serv">
                            <span class="icon">🔄</span>
                            <span>دورة العمل خطوة بخطوة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-serv">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو قائمة أسعار الخدمات -->
                <section id="sec-video-serv" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح قائمة أسعار الخدمات (Service Price List)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية إضافة وتوصيف الخدمات في بنك الأسعار، تحديد النطاق (عام أو خاص بمنشأة)، تسعير الباقات وتصنيفاتها، وربطها التلقائي بعروض الأسعار والفواتير:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerServicePriceList">
                            <source src="{{ asset('videos/Service Price List.mp4') }}" type="video/mp4">
                            <source src="/videos/Service Price List.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Service%20Price%20List.mp4') }}" type="video/mp4">
                            <source src="/videos/Service%20Price%20List.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Service Price List.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Service Price List.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: شرح بنك وقائمة أسعار الخدمات وتصنيفاتها وضبط الأسعار القياسية</span>
                            <span>الملف: <code>/videos/Service Price List.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف الشاشة -->
                <section id="sec-concept-serv" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">💡</span>
                        <h2 class="carnet-card-title">فكرة بنك الخدمات والتكامل التجاري والمالي</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشة <strong>قائمة أسعار الخدمات (Services & Price List)</strong> الفهرس المرجعي المركزي لجميع المنتجات والخدمات المعروضة لعملاء المنشأة. تمنع التخبط وتضمن توحيد الأسعار والخصومات:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>🏷️</span>
                            <div>
                                <strong>توحيد الأسعار القياسية:</strong>
                                ضبط الأسعار الافتراضية لكل خدمة مع تحديد الفئة (سجلات، بلديات، رخص عمل، جوازات، تأشيرات، تأمين)، مما يمنح مسؤولي المبيعات سرعة فائقة في اختيار البنود داخل عروض الأسعار والعقود.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #b45309;">
                            <span>🏢</span>
                            <div>
                                <strong>مرونة نطاق الخدمة (عامة أو تابعة لمنشأة):</strong>
                                يتيح النظام تخصيص خدمات عامة مشتركة لجميع الشركات، أو خدمات حصرية تابعة لشركة محددة بأسعار وشروط خاصة بها.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: دليل حقول شاشة الخدمات -->
                <section id="sec-fields-serv" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">📋</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة قائمة أسعار الخدمات (Services Fields)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تتضمن شاشة الخدمة البيانات الأساسية المحددة للخدمة وقيمتها:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نطاق الخدمة (Scope Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الاختيار بين <strong>خدمة عامة (General)</strong> تطبق على مستوى المجموعة، أو <strong>خدمة منشأة (Company)</strong> تقتصر على شركة معينة.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الشركة التابعة (Company)</span>
                                <span class="carnet-field-badge badge-opt">شرطي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>يظهر في حال تحديد نطاق الخدمة كـ "خاص بمنشأة"، لتحديد الشركة المالكة لهذه الخدمة بدقة.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">اسم الخدمة (Service Name)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>المسمى الواضح للخدمة (مثل: تجديد رخصة العمل، إصدار إقامة جديدة، فحص موقع بلدي، تجديد سجل تجاري).</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">فئة وتصنيف الخدمة (Category)</span>
                                <span class="carnet-field-badge badge-integ">تصنيف</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تصنيف الخدمة حسب الجهة: سجلات ورخص، بلديات، رخص عمل، جوازات، تأشيرات، تأمين طبي، أو أخرى.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع الهدف (Target Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد الجهة المستفيدة من تنفيذ الخدمة: منشأة (Company)، موظف (Employee)، أو عام (General).</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">السعر القياسي (Price)</span>
                                <span class="carnet-field-badge badge-req">إلزامي - مالي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>القيمة المالية المعتمدة للخدمة بالريال السعودي قبل الضريبة، وتظهر تلقائياً عند إضافة الخدمة في الفواتير أو العروض.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card" style="grid-column: 1 / -1;">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الوصف والشروط الإضافية (Description)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>ملاحظات إيضاحية عن متطلبات الخدمة والاشتراطات الحكومية ومكونات الحزمة التسعيرية.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 4: دورة العمل خطوة بخطوة -->
                <section id="sec-workflow-serv" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">🔄</span>
                        <h2 class="carnet-card-title">دورة إدارة وتحديث الأسعار خطوة بخطوة</h2>
                    </div>

                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-details">
                                <h4>الدخول لشاشة قائمة الخدمات</h4>
                                <p>التوجه للرابط <code>/services</code> والضغط على زر "إضافة خدمة جديدة".</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-details">
                                <h4>تحديد النطاق والمنشأة</h4>
                                <p>اختيار ما إذا كانت الخدمة عامة لجميع الشركات أو خاصة بمنشأة معينة، وتحديد المنشأة التابعة.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-details">
                                <h4>توصيف الخدمة وتصنيفها</h4>
                                <p>إدخال اسم الخدمة بدقة، وتحديد فئتها ونوع الهدف (منشأة أو موظف أو عام).</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-details">
                                <h4>تحديد السعر وحفظ السجل</h4>
                                <p>إدخال السعر القياسي المعتمد، وحفظ السجل لتصبح الخدمة جاهزة للاستخدام الفوري.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">5</span>
                            <div class="carnet-step-details">
                                <h4>استدعاء الخدمة في العروض والفواتير</h4>
                                <p>عند إنشاء أي عرض سعر أو فاتورة، تظهر الخدمة في القائمة المنسدلة ويتم جلب سعرها آلياً بضغطة زر واحدة.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: الأسئلة الشائعة -->
                <section id="sec-faq-serv" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول قائمة أسعار الخدمات</h2>
                    </div>

                    <div class="carnet-faq-box">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يمكن تعديل سعر الخدمة أثناء إنشاء عرض السعر أو الفاتورة؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، السعر المحدد في قائمة الخدمات هو السعر الافتراضي المرجعي، ولكن يتيح النظام لمسؤول المبيعات تقديم خصم أو تعديل السعر النهائي حسب الاتفاق مع العميل.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>ما فائدة تحديد فئة الخدمة (Category)؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                تصنيف الخدمة يساعد في الفلترة السريعة وتوليد التقارير الإدارية التحليلية لمعرفة أكثر الخدمات طلباً وإيراداً لكل قطاع (بلديات، جوازات، تأمينات، إلخ).
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>أين يتم وضع فيديو شرح قائمة الأسعار؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يتم وضع الفيديو في المسار <code>public/videos/Service Price List.mp4</code> ويتم تشغيله مباشرة في هذه الصفحة.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        </div>

    @elseif(in_array($activeSub, ['customers', 'beneficiaries', 'clients']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: العملاء والجهات المستفيدة (Customers and Beneficiarie)
             الفيديو: Customers and Beneficiarie.mp4
             ========================================================================== -->

        <!-- 1. شريط التنقل العلوي -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل المبيعات والعقود</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('sales-contracts')" class="carnet-sub-tab-pill">
                    <span>📑 عروض الأسعار والعقود</span>
                </button>
                <button type="button" wire:click="setSub('invoices')" class="carnet-sub-tab-pill">
                    <span>🧾 الفواتير وسندات الخدمات</span>
                </button>
                <button type="button" wire:click="setSub('customers')" class="carnet-sub-tab-pill active">
                    <span>👥 العملاء والجهات المستفيدة</span>
                </button>
                <button type="button" wire:click="setSub('services')" class="carnet-sub-tab-pill">
                    <span>🏷️ قائمة أسعار الخدمات</span>
                </button>
            </div>
        </div>

        <!-- 2. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح العملاء والجهات المستفيدة (Customers and Beneficiarie)</h1>
                    <p>دليل شامل لإدارة ملفات العملاء المركزية، السجلات التجارية، الأرقام الضريبية، جهات الاتصال، ومتابعة الحسابات والتعاملات.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل العملاء..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('customers')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/customers" class="carnet-btn-action" style="background: #7c3aed; color: #ffffff; border-color: #7c3aed;">
                    <span>فتح شاشة العملاء ⇦</span>
                </a>
            </div>
        </div>

        <!-- 3. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-cust" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل (Customers & Beneficiaries)</span>
            </a>
            <a href="#sec-concept-cust" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة ملف العميل والتكامل المالي</span>
            </a>
            <a href="#sec-fields-cust" class="carnet-tab-link">
                <span>📋</span>
                <span>دليل حقول شاشة العملاء</span>
            </a>
            <a href="#sec-workflow-cust" class="carnet-tab-link">
                <span>🔄</span>
                <span>دورة إدارة العملاء خطوة بخطوة</span>
            </a>
            <a href="#sec-faq-cust" class="carnet-tab-link">
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
                        <a href="#sec-video-cust">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-cust">
                            <span class="icon">💡</span>
                            <span>فكرة ملف العميل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-cust">
                            <span class="icon">📋</span>
                            <span>دليل الحقول والبيانات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-cust">
                            <span class="icon">🔄</span>
                            <span>دورة العمل خطوة بخطوة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-cust">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو العملاء والجهات المستفيدة -->
                <section id="sec-video-cust" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح العملاء والجهات المستفيدة (Customers and Beneficiarie)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية إضافة وتوثيق ملفات العملاء والشركات والجهات المستفيدة، تسجيل السجلات التجارية والأرقام الضريبية، وبيانات التواصل والعناوين، والربط مع العقود والفواتير:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerCustomerVideo">
                            <source src="{{ asset('videos/Customers and Beneficiarie.mp4') }}" type="video/mp4">
                            <source src="/videos/Customers and Beneficiarie.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Customers%20and%20Beneficiarie.mp4') }}" type="video/mp4">
                            <source src="/videos/Customers%20and%20Beneficiarie.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Customers and Beneficiaries.mp4') }}" type="video/mp4">
                            <source src="/videos/Customers and Beneficiaries.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Customers%20and%20Beneficiaries.mp4') }}" type="video/mp4">
                            <source src="/videos/Customers%20and%20Beneficiaries.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Customers and Beneficiarie.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Customers and Beneficiarie.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: إدارة ملفات العملاء والجهات المستفيدة والسجلات الضريبية</span>
                            <span>الملف: <code>/videos/Customers and Beneficiarie.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف الشاشة -->
                <section id="sec-concept-cust" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">💡</span>
                        <h2 class="carnet-card-title">فكرة ملف العميل والتكامل المالي والتجاري</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشة <strong>العملاء والجهات المستفيدة (Customers & Beneficiaries)</strong> السجل الأساسي لجميع الشركاء التجاريين للمنشأة. يضمن التوثيق الدقيق للبيانات القانونية والضريبية سلامة الفواتير الإلكترونية والعقود الملزمة:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>🏢</span>
                            <div>
                                <strong>تعدد أنواع الكيانات (أفراد، شركات، مؤسسات، جهات حكومية):</strong>
                                يتكيف النموذج بذكاء حسب نوع العميل، فيطلب الهوية الوطنية للأفراد، بينما يطلب السجل التجاري والرقم الضريبي للشركات والمؤسسات لضمان الامتثال مع هيئة الزكاة والضريبة والجمارك (ZATCA).
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #6d28d9;">
                            <span>📑</span>
                            <div>
                                <strong>الربط الفوري مع العقود والفواتير:</strong>
                                بمجرد حفظ ملف العميل، يتم استدعاؤه فوراً في شاشة عروض الأسعار وشاشة الفواتير وسندات استحقاق الخدمات دون الحاجة لإعادة إدخال بياناته.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: دليل حقول شاشة العملاء -->
                <section id="sec-fields-cust" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">📋</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة العملاء والجهات المستفيدة (Customer Fields)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تتوزع بيانات العميل على قسمين أساسيين: البيانات الأساسية وبيانات الاتصال والعنوان:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع العميل (Customer Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الاختيار بين: فرد (Individual)، شركة (Company)، مؤسسة (Establishment)، جهة حكومية (Government)، أو أخرى.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">اسم العميل (Name)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الاسم التجاري أو الشخصي المعتمد الذي سيظهر في ترويسة العقود والفواتير الضريبية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">حالة الحساب (Status)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد ما إذا كان العميل نشطاً (Active) ومتاحاً للتعامل أو موقوفاً مؤقتاً (Inactive).</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الهوية الوطنية / الإقامة</span>
                                <span class="carnet-field-badge badge-opt">للأفراد</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>رقم الهوية الوطنية أو الإقامة النظامية للعملاء الأفراد لتوثيق العقود الشخصية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">رقم السجل التجاري (CR Number)</span>
                                <span class="carnet-field-badge badge-integ">للشركات والمؤسسات</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>رقم السجل التجاري الصادر من وزارة التجارة لربط الكيان الرسمي في العقود المؤسسية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الرقم الضريبي (Tax Number)</span>
                                <span class="carnet-field-badge badge-doc">15 رقماً</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الرقم الضريبي المعتمد المكون من 15 رقماً واللازم لإصدار الفواتير الضريبية النظامية وتوليد رمز الاستجابة السريع QR.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">رقم الجوال والتواصل (Phone)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>رقم الهاتف المعتمد مع مفتاح الدولة للاتصال وإرسال إشعارات العقود وتأكيدات السداد.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">البريد الإلكتروني (Email)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>عنوان البريد الإلكتروني المعتمد لإرسال نسخ عروض الأسعار والفواتير الإلكترونية وسندات القبض.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">المدينة والعنوان الوطني (Address)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>المدينة وتفاصيل العنوان الوطني والشارع والمبنى المعتمد لإثبات المقر في المراسلات والعقود.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 4: دورة العمل خطوة بخطوة -->
                <section id="sec-workflow-cust" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">🔄</span>
                        <h2 class="carnet-card-title">دورة إدارة ملف العميل خطوة بخطوة</h2>
                    </div>

                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-details">
                                <h4>الدخول لشاشة العملاء</h4>
                                <p>التوجه للرابط <code>/customers</code> والضغط على زر "إضافة عميل جديد".</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-details">
                                <h4>تحديد نوع العميل واسمه التجاري</h4>
                                <p>اختيار صفة العميل (شركة أو مؤسسة أو فرد)، وكتابة الاسم الرسمي المعتمد وتحديد حالة التفعيل.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-details">
                                <h4>توثيق السجل التجاري والرقم الضريبي</h4>
                                <p>إدخال رقم السجل التجاري والرقم الضريبي (15 رقماً) لضمان دقة الفوترة الإلكترونية.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-details">
                                <h4>إدخال بيانات الاتصال والعنوان</h4>
                                <p>تسجيل رقم الجوال، البريد الإلكتروني، والمدينة والعنوان الوطني، وتدوين أي ملاحظات خاصة بالعميل.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">5</span>
                            <div class="carnet-step-details">
                                <h4>الحفظ والاستدعاء الفوري في العقود والفواتير</h4>
                                <p>حفظ الملف، ليظهر العميل فوراً في القوائم المنسدلة لشاشات عروض الأسعار <code>/offers</code> والفواتير <code>/invoices</code>.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: الأسئلة الشائعة -->
                <section id="sec-faq-cust" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول شاشة العملاء والجهات المستفيدة</h2>
                    </div>

                    <div class="carnet-faq-box">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يمكن إضافة عميل جديد مباشرة أثناء إنشاء عرض السعر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، تدعم شاشة عروض الأسعار خاصية الإضافة السريعة للعميل دون الحاجة لمغادرة الشاشة، ويتم حفظ بياناته مباشرة في سجل العملاء.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>ما أهمية تسجيل الرقم الضريبي للعميل؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                الرقم الضريبي ضروري لإصدار الفواتير الضريبية المتوافقة مع اشتراطات هيئة الزكاة والضريبة والجمارك (ZATCA)، حيث يظهر في الفاتورة المطبوعة وضمن بيانات رمز الاستجابة السريع QR.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>أين يتم وضع فيديو شرح العملاء في السيرفر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يتم وضع الفيديو في المسار <code>public/videos/Customers and Beneficiarie.mp4</code> ويمكن تشغيله مباشرة عبر هذا الدليل.
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
    function copyCarnetShareLink(sub = 'sales-contracts') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=contracts&activeSub=' + sub + '#sec-video-contracts';
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

    function switchContractsVideo(part) {
        const v1 = document.getElementById('contractsVideoContainer1');
        const v2 = document.getElementById('contractsVideoContainer2');
        const v3 = document.getElementById('contractsVideoContainer3');
        const b1 = document.getElementById('contractsBtnPart1');
        const b2 = document.getElementById('contractsBtnPart2');
        const b3 = document.getElementById('contractsBtnInvoices');
        const p1 = document.getElementById('playerContracts1');
        const p2 = document.getElementById('playerContracts2');
        const p3 = document.getElementById('playerContracts3');

        // إخفاء الكل وإيقاف التشغيل
        if (v1) v1.style.display = 'none';
        if (v2) v2.style.display = 'none';
        if (v3) v3.style.display = 'none';

        if (b1) b1.classList.remove('active');
        if (b2) b2.classList.remove('active');
        if (b3) b3.classList.remove('active');

        if (p1) p1.pause();
        if (p2) p2.pause();
        if (p3) p3.pause();

        // إظهار الجزء المطلوب
        if (part === 1) {
            if (v1) v1.style.display = 'block';
            if (b1) b1.classList.add('active');
        } else if (part === 2) {
            if (v2) v2.style.display = 'block';
            if (b2) b2.classList.add('active');
            if (p2) {
                try {
                    if (p2.readyState === 0) p2.load();
                } catch(e) {}
            }
        } else if (part === 3) {
            if (v3) v3.style.display = 'block';
            if (b3) b3.classList.add('active');
            if (p3) {
                try {
                    if (p3.readyState === 0) p3.load();
                } catch(e) {}
            }
        }
    }
</script>
