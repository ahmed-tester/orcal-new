<!-- تصميم مطابق لنمط "كارنت | دليل لوحة التحكم" بخط تجوال (Tajawal) وبنفس الأحجام والتنسيق لقسم الموارد البشرية وشؤون الموظفين -->
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
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        flex-shrink: 0;
    }
    .carnet-logo-icon svg {
        width: 24px;
        height: 24px;
    }
    .carnet-title-text h1 {
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }
    .dark .carnet-title-text h1 {
        color: #f8fafc;
    }
    .carnet-title-text p {
        font-size: 13px;
        color: #64748b;
        margin: 2px 0 0 0;
    }
    .dark .carnet-title-text p {
        color: #94a3b8;
    }

    .carnet-actions-box {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .carnet-search-input {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 8px 14px;
        font-size: 13px;
        color: #1e293b;
        width: 240px;
        outline: none;
        transition: all 0.2s ease;
    }
    .carnet-search-input:focus {
        border-color: #0284c7;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .dark .carnet-search-input {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    .carnet-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .carnet-btn-action:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .carnet-btn-action {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    .dark .carnet-btn-action:hover {
        background: #475569;
        color: #ffffff;
    }

    /* Subpage Back Bar */
    .carnet-sub-header-bar {
        background: linear-gradient(135deg, #0b2545 0%, #134074 100%);
        border-radius: 14px;
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(11, 37, 69, 0.15);
    }
    .carnet-sub-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 800;
        border: 1px solid rgba(255, 255, 255, 0.25);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .carnet-sub-back-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateX(3px);
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
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12.5px;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.12);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.18);
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .carnet-sub-tab-pill:hover {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
    }
    .carnet-sub-tab-pill.active {
        background: #0284c7;
        color: #ffffff;
        border-color: #38bdf8;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
    }

    /* 2. Secondary Tab Navigation Bar (Under Top Toolbar) */
    .carnet-secondary-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        border-bottom: 2px solid #e2e8f0;
    }
    .dark .carnet-secondary-tabs {
        border-color: #334155;
    }
    .carnet-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 10px 10px 0 0;
        font-size: 13.5px;
        font-weight: 700;
        color: #64748b;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }
    .carnet-tab-link:hover {
        color: #0284c7;
        background: rgba(2, 132, 199, 0.05);
    }
    .dark .carnet-tab-link {
        color: #94a3b8;
    }
    .dark .carnet-tab-link:hover {
        color: #38bdf8;
    }

    /* 3. Main Content Grid (Right Sticky Index + Left Flowing Content) */
    .carnet-content-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .carnet-content-grid {
            grid-template-columns: 1fr;
        }
    }

    .carnet-index-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        position: sticky;
        top: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }
    .dark .carnet-index-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-index-heading {
        font-size: 13.5px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .carnet-index-heading {
        color: #f8fafc;
        border-color: #334155;
    }
    .carnet-index-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .carnet-index-item a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .carnet-index-item a:hover {
        background: #f1f5f9;
        color: #0284c7;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a {
        color: #cbd5e1;
    }
    .dark .carnet-index-item a:hover {
        background: #334155;
        color: #38bdf8;
    }
    .carnet-index-item a span.icon {
        font-size: 14px;
    }

    .carnet-main-flow {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    /* 4. Video Box with Real Player */
    .carnet-video-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .dark .carnet-video-box {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-video-head {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .carnet-play-badge {
        width: 32px;
        height: 32px;
        background: #fee2e2;
        color: #dc2626;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .dark .carnet-play-badge {
        background: rgba(220, 38, 38, 0.2);
        color: #fca5a5;
    }
    .carnet-video-head h2 {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-video-head h2 {
        color: #f8fafc;
    }
    .carnet-video-subtitle {
        font-size: 13.5px;
        color: #64748b;
        line-height: 1.6;
        margin: 0;
    }
    .dark .carnet-video-subtitle {
        color: #94a3b8;
    }
    .carnet-video-element {
        width: 100%;
        max-height: 520px;
        border-radius: 12px;
        background: #000000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        outline: none;
    }
    .carnet-video-caption {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        border-top: 1px dashed #e2e8f0;
        padding-top: 10px;
    }
    .dark .carnet-video-caption {
        color: #94a3b8;
        border-color: #334155;
    }

    /* 5. Content Cards & Sections */
    .carnet-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .dark .carnet-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-card-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .carnet-card-badge {
        font-size: 18px;
    }
    .carnet-card-title {
        font-size: 16.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-card-title {
        color: #f8fafc;
    }
    .carnet-text-p {
        font-size: 14px;
        line-height: 1.8;
        color: #475569;
        margin: 0;
    }
    .dark .carnet-text-p {
        color: #cbd5e1;
    }

    /* Architecture Box */
    .carnet-architecture-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        overflow-x: auto;
    }
    .dark .carnet-architecture-box {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-arch-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 4px;
        min-width: 140px;
    }
    .carnet-arch-icon {
        font-size: 26px;
    }
    .carnet-arch-title {
        font-size: 13px;
        font-weight: 800;
        color: #0f172a;
    }
    .dark .carnet-arch-title {
        color: #f8fafc;
    }
    .carnet-arch-sub {
        font-size: 11px;
        color: #64748b;
    }
    .carnet-arch-arrow {
        font-size: 18px;
        color: #0284c7;
        font-weight: 800;
    }

    /* 6. Screens 4-Column Grid */
    .carnet-screens-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }
    .carnet-screen-item-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 12px;
        transition: all 0.2s ease;
    }
    .carnet-screen-item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        border-color: #0284c7;
    }
    .dark .carnet-screen-item-card {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-screen-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .carnet-screen-title {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-screen-title {
        color: #f8fafc;
    }
    .carnet-screen-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: #e0f2fe;
        color: #0369a1;
    }
    .dark .carnet-screen-badge {
        background: #075985;
        color: #e0f2fe;
    }
    .carnet-screen-desc {
        font-size: 13px;
        line-height: 1.6;
        color: #64748b;
        margin: 0;
    }
    .dark .carnet-screen-desc {
        color: #94a3b8;
    }
    .carnet-screen-link-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        background: #0284c7;
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
    }
    .carnet-screen-link-btn:hover {
        background: #0369a1;
        color: #ffffff;
    }

    /* 7. Step-by-Step Workflow Cards */
    .carnet-steps-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .carnet-step-row {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .dark .carnet-step-row {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-step-number {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 16px;
        font-weight: 800;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
    }
    .carnet-step-content {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .carnet-step-title {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-step-title {
        color: #f8fafc;
    }
    .carnet-step-text {
        font-size: 13.5px;
        line-height: 1.7;
        color: #475569;
        margin: 0;
    }
    .dark .carnet-step-text {
        color: #94a3b8;
    }

    /* 8. Tips Banner */
    .carnet-tips-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .dark .carnet-tips-box {
        background: rgba(30, 58, 138, 0.2);
        border-color: #1e40af;
    }
    .carnet-tip-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13.5px;
        line-height: 1.6;
        color: #1e3a8a;
    }
    .dark .carnet-tip-item {
        color: #93c5fd;
    }
    .carnet-tip-item strong {
        font-weight: 800;
        color: #172554;
    }
    .dark .carnet-tip-item strong {
        color: #bfdbfe;
    }

    /* 9. FAQ Accordion Items */
    .carnet-faq-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .carnet-faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
    }
    .dark .carnet-faq-item {
        border-color: #334155;
        background: #0f172a;
    }
    .carnet-faq-summary {
        padding: 14px 18px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        user-select: none;
    }
    .dark .carnet-faq-summary {
        color: #f8fafc;
    }
    .carnet-faq-body {
        padding: 0 18px 14px 18px;
        font-size: 13.5px;
        line-height: 1.7;
        color: #475569;
    }
    .dark .carnet-faq-body {
        color: #94a3b8;
    }

    /* 9.1 Field Breakdown Cards */
    .carnet-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 10px;
    }
    .carnet-field-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .carnet-field-card:hover {
        border-color: #0284c7;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-field-card {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-field-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .carnet-field-title {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }
    .dark .carnet-field-title {
        color: #f8fafc;
    }
    .carnet-field-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        background: #e0f2fe;
        color: #0369a1;
    }
    .dark .carnet-field-badge {
        background: #075985;
        color: #bae6fd;
    }
    .carnet-field-desc {
        font-size: 13px;
        line-height: 1.6;
        color: #64748b;
        margin: 0;
    }
    .dark .carnet-field-desc {
        color: #94a3b8;
    }

    /* ==========================================================================
       10. HR Hub Styles (Main Page with the 4 Grand Cards)
       ========================================================================== */
    .carnet-hub-hero-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .dark .carnet-hub-hero-box {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-hub-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 800;
        width: fit-content;
    }
    .dark .carnet-hub-badge {
        background: #075985;
        color: #bae6fd;
    }
    .carnet-hub-hero-box h2 {
        font-size: 21px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-hub-hero-box h2 {
        color: #f8fafc;
    }
    .carnet-hub-hero-box p {
        font-size: 14px;
        color: #64748b;
        margin: 0;
        line-height: 1.7;
    }
    .dark .carnet-hub-hero-box p {
        color: #94a3b8;
    }

    .carnet-grand-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    @media (max-width: 860px) {
        .carnet-grand-grid {
            grid-template-columns: 1fr;
        }
    }

    .carnet-grand-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 20px;
        transition: all 0.25s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }
    .dark .carnet-grand-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-grand-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(2, 132, 199, 0.12);
        border-color: #0284c7;
    }
    .carnet-grand-card.featured {
        border-top: 4px solid #0284c7;
    }

    .carnet-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }
    .carnet-card-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .carnet-card-icon-wrap svg {
        width: 28px;
        height: 28px;
        color: #ffffff;
    }
    .bg-icon-blue {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    }
    .bg-icon-emerald {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }
    .bg-icon-purple {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    }
    .bg-icon-amber {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    }

    .carnet-card-badges {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .carnet-card-badge-pill {
        font-size: 11.5px;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 9999px;
    }
    .pill-video {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .dark .pill-video {
        background: rgba(220, 38, 38, 0.2);
        color: #fca5a5;
        border-color: #7f1d1d;
    }
    .pill-info {
        background: #f1f5f9;
        color: #475569;
    }
    .dark .pill-info {
        background: #334155;
        color: #cbd5e1;
    }

    .carnet-card-middle {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .carnet-card-middle h3 {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-card-middle h3 {
        color: #f8fafc;
    }
    .carnet-card-en-title {
        font-size: 12px;
        color: #0284c7;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .carnet-card-desc {
        font-size: 13.5px;
        line-height: 1.7;
        color: #64748b;
        margin: 4px 0 0 0;
    }
    .dark .carnet-card-desc {
        color: #94a3b8;
    }

    .carnet-card-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
    }
    .dark .carnet-card-actions {
        border-color: #334155;
    }
    .carnet-btn-open-guide {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        font-size: 13px;
        font-weight: 800;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
    }
    .carnet-btn-open-guide:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
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

    /* Video Dual Tabs */
    .carnet-video-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 12px;
        flex-wrap: wrap;
    }
    .dark .carnet-video-tabs {
        background: #0f172a;
    }
    .carnet-video-tab-btn {
        flex: 1;
        min-width: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        background: transparent;
        color: #475569;
        transition: all 0.2s ease;
    }
    .dark .carnet-video-tab-btn {
        color: #94a3b8;
    }
    .carnet-video-tab-btn.active {
        background: #ffffff;
        color: #0284c7;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .dark .carnet-video-tab-btn.active {
        background: #1e293b;
        color: #38bdf8;
    }
</style>

<div class="carnet-wrapper">

    @if(empty($activeSub) || !in_array($activeSub, ['saudi-employees', 'saudi', 'foreign-employees', 'foreign', 'non-saudi', 'attendance', 'attendances', 'leaves', 'leave']))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم الموارد البشرية وشؤون الموظفين (The 4 Grand Cards Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">👥 {{ __('documentation.hr_guide.tag') }}</span>
            <h2>{{ __('documentation.hr_guide.hub_heading') }}</h2>
            <p>{{ __('documentation.hr_guide.hub_note') }}</p>
        </div>

        <!-- شبكة البطاقات الكبرى الأربعة لقسم الموارد البشرية -->
        <div class="carnet-grand-grid">

            <!-- 1. الموظفون السعوديون (Saudi Employees) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو متاح (Saudi Employee)</span>
                        <span class="carnet-card-badge-pill pill-info">الكادر الوطني</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.hr_guide.card_saudi_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.hr_guide.card_saudi_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.hr_guide.card_saudi_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('saudi-employees')" class="carnet-btn-open-guide">
                        <span>فتح الشرح والفيديو التفصيلي</span>
                        <span>➔</span>
                    </button>
                    <a href="/saudi-employees" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. الموظفون غير السعوديين (Non-Saudi Employees) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-blue">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو متاح (Non-Saudi Employee)</span>
                        <span class="carnet-card-badge-pill pill-info">شؤون المقيمين</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.hr_guide.card_foreign_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.hr_guide.card_foreign_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.hr_guide.card_foreign_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('foreign-employees')" class="carnet-btn-open-guide">
                        <span>فتح الشرح والفيديو التفصيلي</span>
                        <span>➔</span>
                    </button>
                    <a href="/foreign-employees" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. الحضور والانصراف وتسجيل الغياب (Attendance & Absence) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديوهان توضيحيان (جزئين)</span>
                        <span class="carnet-card-badge-pill pill-info">حضور، انصراف، وغياب</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>الحضور والانصراف وتسجيل الغياب (Attendance)</h3>
                    <span class="carnet-card-en-title">ATTENDANCE, ABSENCE & DEPARTURE</span>
                    <p class="carnet-card-desc">
                        دليل شامل من جزئين مسجلين: تسجيل الحضور والانصراف وأجهزة البصمة، وتوثيق الغياب واحتساب دقائق التأخير وخصومات الرواتب.
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('attendance')" class="carnet-btn-open-guide">
                        <span>فتح الشرح والفيديو (جزئين)</span>
                        <span>➔</span>
                    </button>
                    <a href="/attendances" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 4. الإجازات والأرصدة (Leaves & Balances - Dual Video) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #d97706;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-warning">🌴 الأرصدة والطلبات</span>
                        <span class="carnet-card-badge-pill pill-success">🎬 جزئين مسجلين</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #b45309;">{{ __('documentation.hr_guide.card_leaves_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.hr_guide.card_leaves_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.hr_guide.card_leaves_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('leaves')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <span>مشاهدة الجزئين والشرح التفصيلي ➔</span>
                    </button>
                    <a href="/leaves" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

        </div>

    @else
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية عند اختيار إحدى الشاشات (الموظفون والحضور والإجازات)
             ========================================================================== -->

        <!-- شريط الرجوع والتنقل السريع بين الأقسام -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>➔</span>
                <span>العودة للواجهة الرئيسية للموارد البشرية</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub('saudi-employees')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['saudi-employees', 'saudi']) ? 'active' : '' }}">
                    <span>🇸🇦 الموظفون السعوديون</span>
                </button>
                <button type="button" wire:click="setSub('foreign-employees')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['foreign-employees', 'foreign', 'non-saudi']) ? 'active' : '' }}">
                    <span>🌐 الموظفون غير السعوديين</span>
                </button>
                <button type="button" wire:click="setSub('attendance')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['attendance', 'attendances']) ? 'active' : '' }}">
                    <span>⏱️ الحضور والانصراف والغياب (جزئين)</span>
                </button>
                <button type="button" wire:click="setSub('leaves')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['leaves', 'leave']) ? 'active' : '' }}">
                    <span>📅 الإجازات والأرصدة (جزئين)</span>
                </button>
            </div>
        </div>

        @if(in_array($activeSub, ['saudi-employees', 'saudi']))
            <!-- ----------------------------------------------------------------------
                 دليل الموظفين السعوديين (Saudi Employees) مع الفيديو المسجل Saudi Employee.mp4
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>{{ __('documentation.hr_guide.saudi_title') }}</h1>
                        <p>{{ __('documentation.hr_guide.saudi_subtitle') }}</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في دليل الموظفين السعوديين..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('saudi-employees')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/saudi-employees" class="carnet-btn-action" style="background: #059669; color: #ffffff; border-color: #059669;">
                        <span>فتح شاشة الموظفين السعوديين ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح (Saudi Employee)</span>
                </a>
                <a href="#sec-concept-saudi" class="carnet-tab-link">
                    <span>💡</span>
                    <span>التوطين والتأمينات</span>
                </a>
                <a href="#sec-fields-saudi" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة تفصيلياً (الحقول والرواتب)</span>
                </a>
                <a href="#sec-workflow-saudi" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات تسجيل موظف سعودي</span>
                </a>
                <a href="#sec-tips-saudi" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>حماية الأجور ونظام العمل</span>
                </a>
                <a href="#sec-faq-saudi" class="carnet-tab-link">
                    <span>❓</span>
                    <span>الأسئلة الشائعة</span>
                </a>
            </nav>

            <!-- 3. المحتوى المتدفق والفهرس الجانبي -->
            <div class="carnet-content-grid">

                <!-- الفهرس الجانبي الثابت (يمين) -->
                <aside class="carnet-index-card">
                    <div class="carnet-index-heading">
                        <span>📑</span>
                        <span>فهرس الدليل: الموظفون السعوديون</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديو الشرح (Saudi Employee)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-saudi">
                                <span class="icon">💡</span>
                                <span>أهمية توثيق الكادر الوطني</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-saudi">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-saudi">
                                <span class="icon">📋</span>
                                <span>خطوات التسجيل والتسكين</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-saudi">
                                <span class="icon">⭐</span>
                                <span>حماية الأجور وحساب العمر</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-saudi">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: فيديو شرح الموظف السعودي Saudi Employee.mp4 -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>{{ __('documentation.hr_guide.saudi_video_title') }}</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            {{ __('documentation.hr_guide.saudi_video_desc') }}
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Saudi Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Saudi Employee.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Saudi Employee.mp4') }}" type="video/mp4">
                                <source src="/videos/Saudi Employee.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Saudi%20Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Saudi%20Employee.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Saudi%20Employee.mp4') }}" type="video/mp4">
                                <source src="/videos/Saudi%20Employee.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Saudi-Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Saudi-Employee.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Saudi Employee.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف توثيق الموظفين السعوديين ونطاقات -->
                    <section id="sec-concept-saudi" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">إدارة الكفاءات الوطنية ونسب التوطين (Nitaqat & GOSI)</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُممت شاشة الموظفين السعوديين لتكون القاعدة الأساسية للامتثال لأنظمة وزارة الموارد البشرية والتنمية الاجتماعية والمؤسسة العامة للتأمينات الاجتماعية؛ حيث تتيح تسجيل بيانات المواطنين بدقة، مطابقة الهوية الوطنية، احتساب الراتب الخاضع للتأمينات، والتسكين الهيكلي على المنشآت والفروع لرفع نسب التوطين واحتسابها آلياً في مؤشرات نطاقات:
                        </p>

                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🇸🇦</span>
                                <span class="carnet-arch-title">الهوية الوطنية والسن</span>
                                <span class="carnet-arch-sub">10 أرقام وحساب العمر آلياً</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏢</span>
                                <span class="carnet-arch-title">التسكين المؤسسي</span>
                                <span class="carnet-arch-sub">المنشأة، الفرع، القسم، والمسمى</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">💳</span>
                                <span class="carnet-arch-title">الراتب والآيبان (WPS)</span>
                                <span class="carnet-arch-sub">مطابقة نظام حماية الأجور</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🛡️</span>
                                <span class="carnet-arch-title">صلاحيات النظام</span>
                                <span class="carnet-arch-sub">أدوار Spatie وحساب الدخول</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: كيف تعمل شاشة الموظفين السعوديين تفصيلياً (دليل الحقول والإعدادات) -->
                    <section id="sec-fields-saudi" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الموظفين السعوديين تفصيلياً؟ (دليل الحقول والرواتب)</h2>
                        </div>
                        <p class="carnet-text-p">
                            تحتوي شاشة إضافة وتعديل الموظف السعودي (Saudi Employee Form) على حقول متكاملة تغطي الجوانب القانونية والمالية والإدارية. إليك تفصيل كل حقل ودوره:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. مصدر التسجيل والاستيراد من الملاك -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⚡</span>
                                        <span>مصدر التسجيل (Registration Source)</span>
                                    </h3>
                                    <span class="carnet-field-badge">ميزة حصرية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    <strong>تسجيل يدوي:</strong> إدخال بيانات الموظف من البداية.<br>
                                    <strong>من الملاك (From Owner):</strong> ميزة استثنائية تمكنك من اختيار شريك/مالك، ليقوم النظام تلقائياً بتعبئة اسمه، بريده، جواله، هويته، تاريخ ميلاده، وحسابه كـ موظف سعودي دون تكرار الإدخال.
                                </p>
                            </div>

                            <!-- 2. الاسم والبريد والجوال -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👤</span>
                                        <span>الاسم والبريد ورقم الجوال</span>
                                    </h3>
                                    <span class="carnet-field-badge">إلزامي وفريد</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الاسم الرباعي الرسمي، البريد الإلكتروني المعتمد، ورقم الجوال؛ تُستخدم لإنشاء حساب دخول للموظف وإرسال إشعارات مسيرات الرواتب والموافقات على الطلبات.
                                </p>
                            </div>

                            <!-- 3. رقم الهوية الوطنية -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🪪</span>
                                        <span>رقم الهوية الوطنية (National ID)</span>
                                    </h3>
                                    <span class="carnet-field-badge">10 أرقام</span>
                                </div>
                                <p class="carnet-field-desc">
                                    المعرف الوطني الرسمي المكون من 10 خانات ويبدأ بالرقم 1؛ يُعتمد للربط مع المؤسسة العامة للتأمينات الاجتماعية والتأكد من عدم تكرار قيد الموظف.
                                </p>
                            </div>

                            <!-- 4. تاريخ الميلاد وحساب العمر التلقائي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📅</span>
                                        <span>تاريخ الميلاد والعمر (Auto Age)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حساب لحظي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    بمجرد اختيار تاريخ الميلاد، يقوم محرك <code>EmployeeAgeService</code> باحتساب عمر الموظف بالسنة والشهر واليوم، مما يحدد استحقاقه لبرامج دعم صندوق هدف والتأمينات.
                                </p>
                            </div>

                            <!-- 5. الراتب الأساسي والبدلات -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>💰</span>
                                        <span>الراتب الأساسي (Basic Salary)</span>
                                    </h3>
                                    <span class="carnet-field-badge">ريال سعودي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الراتب التعاقدي المسجل في التأمينات الاجتماعية (GOSI)، والذي يُبنى عليه احتساب استقطاعات ساند، وحساب مكافأة نهاية الخدمة، وساعات العمل الإضافية.
                                </p>
                            </div>

                            <!-- 6. الآيبان البنكي وحماية الأجور -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏦</span>
                                        <span>الآيبان البنكي (Bank IBAN)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حماية الأجور WPS</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم الحساب البنكي الدولي المعتمد المكون من 24 خانة ويبدأ بـ SA؛ يُستخدم لتصدير ملف صرف الرواتب البنكي المتوافق بنسبة 100% مع منصة مدد ونظام حماية الأجور.
                                </p>
                            </div>

                            <!-- 7. التسكين المؤسسي (الشركة، الفرع، القسم، المسمى) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>التسكين الإداري والوظيفي</span>
                                    </h3>
                                    <span class="carnet-field-badge">الهيكل الوظيفي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد المنشأة التابع لها الموظف، ثم الفرع الجغرافي، فالقسم الإداري (مركز التكلفة)، وأخيراً المسمى الوظيفي المسجل بدليل المسميات بالمنشأة.
                                </p>
                            </div>

                            <!-- 8. تاريخ المباشرة ونوع التوظيف -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⏱️</span>
                                        <span>تاريخ المباشرة ونوع العقد</span>
                                    </h3>
                                    <span class="carnet-field-badge">نظام العمل</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تاريخ بدء العمل الفعلي (Hire Date)، نوع التوظيف (دوام كامل / جزئي)، وتاريخ انتهاء العقد؛ يُستخدم لمتابعة فترة التجربة وتجديد العقود السنوية تلقائياً.
                                </p>
                            </div>

                            <!-- 9. رصيد الإجازة السنوية -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏖️</span>
                                        <span>رصيد الإجازة السنوية (Leave Days)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الافتراضي 21 يوماً</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد أيام الإجازة السنوية المستحقة (21 يوماً كحد أدنى أو 30 يوماً لمن أمضى 5 سنوات خدمة) وفق المادة 109 من نظام العمل السعودي، ويتم خصم أيام الإجازات المعتمدة منها آلياً.
                                </p>
                            </div>

                            <!-- 10. الصلاحيات وحساب الدخول للوحة التحكم -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛡️</span>
                                        <span>صلاحيات الدخول (Dashboard Access)</span>
                                    </h3>
                                    <span class="carnet-field-badge">أدوار Spatie</span>
                                </div>
                                <p class="carnet-field-desc">
                                    إمكانية إعطاء الموظف حساب دخول إلى لوحة تحكم النظام، وتحديد الأدوار والصلاحيات المسموحة له (مثل: محاسب، مدير فرع، موظف استقبال، مسؤول موارد بشرية).
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات تسجيل موظف سعودي -->
                    <section id="sec-workflow-saudi" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">خطوات تسجيل وتسكين موظف سعودي خطوة بخطوة</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">1</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">فتح شاشة الموظفين السعوديين</h3>
                                    <p class="carnet-step-text">
                                        انتقل إلى <strong>الموظفون السعوديون (Saudi Employees)</strong> واضغط على زر <strong>«إضافة موظف سعودي جديد»</strong>، واختر مصدر التسجيل (يدوي أو من الملاك).
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">2</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">إدخال البيانات الشخصية ورقم الهوية</h3>
                                    <p class="carnet-step-text">
                                        أدخل الاسم الرباعي، البريد، الجوال، رقم الهوية الوطنية (10 أرقام)، وتاريخ الميلاد ليقوم النظام باحتساب العمر تلقائياً.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">3</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">تحديد الراتب والآيبان البنكي والتسكين</h3>
                                    <p class="carnet-step-text">
                                        سجّل الراتب الأساسي، رقم الآيبان البنكي الدولي، واختر الشركة الأم والفرع والقسم الإداري والمسمى الوظيفي المعتمد.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">4</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">ضبط رصيد الإجازات والصلاحيات وحفظ السجل</h3>
                                    <p class="carnet-step-text">
                                        حدد رصيد الإجازة السنوية، امنحه الأدوار والصلاحيات المناسبة إن كان مستخدماً للنظام، ثم اضغط <strong>«حفظ (Save)»</strong> ليتم تفعيل سجله الوظيفي فوراً.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات حماية الأجور والتأمينات -->
                    <section id="sec-tips-saudi" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">إرشادات حماية الأجور والتأمينات الاجتماعية (GOSI & WPS)</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>💳</span>
                                <div>
                                    <strong>مطابقة الآيبان مع الاسم:</strong>
                                    تأكد دائماً أن الآيبان المدخل يخص الموظف نفسه ومطابق لاسمه الرسمي في البنك لضمان قبول مسير الرواتب في منصة مدد.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🛡️</span>
                                <div>
                                    <strong>التسجيل في التأمينات خلال أول أسبوعين:</strong>
                                    يلزم نظام العمل بتسجيل الموظف السعودي في التأمينات الاجتماعية خلال شهر الالتحاق لضمان احتسابه الفوري في نسبة التوطين بنطاقات.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>⏱️</span>
                                <div>
                                    <strong>فترة التجربة القانونية:</strong>
                                    لا يجوز أن تزيد فترة التجربة عن 90 يوماً إلا باتفاق كتابي لا يتجاوز 180 يوماً كحد أقصى وفق المادة 53 من نظام العمل.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-saudi" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">الأسئلة الشائعة حول شاشة الموظفين السعوديين</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>كيف تفيد ميزة «استيراد من الملاك» في شاشة الموظف السعودي؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    عندما يكون أحد الشركاء أو الملاك يعمل أيضاً كمدير تنفيذي أو موظف بالمنشأة، تتيح لك هذه الميزة جلب كافة بياناته من سجل الملاك بضغطة زر واحدة، مما يمنع التكرار ويوحد بيانات الحساب بالبريد ورقم الجوال نفسه.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يتأثر مسير الرواتب برقم الآيبان والراتب الأساسي للموظف؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، يعتمد مسير الرواتب الإلكتروني تلقائياً على الراتب الأساسي والبدلات المسجلة في ملف الموظف، ويولد ملف تحويل الرواتب البنكي بصيغة WPS المعيارية المعتمدة لدى البنك المركزي السعودي (SAMA).
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>ماذا يحدث لرصيد الإجازات عند تقديم طلب إجازة؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    بمجرد اعتماد طلب الإجازة من مدير القسم أو الإدارة العليا، يقوم النظام آلياً بخصم أيام الإجازة من رصيد الموظف السنوي وتحديث بطاقته الوظيفية فوراً.
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>

        @elseif(in_array($activeSub, ['foreign-employees', 'foreign', 'non-saudi']))
            <!-- ----------------------------------------------------------------------
                 دليل الموظفين غير السعوديين (Non-Saudi Employees) مع الفيديو المسجل Non-Saudi Employee.mp4
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-blue">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>{{ __('documentation.hr_guide.foreign_title') }}</h1>
                        <p>{{ __('documentation.hr_guide.foreign_subtitle') }}</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في دليل الموظفين المقيمين..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('foreign-employees')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/foreign-employees" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                        <span>فتح شاشة الموظفين غير السعوديين ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح (Non-Saudi Employee)</span>
                </a>
                <a href="#sec-concept-foreign" class="carnet-tab-link">
                    <span>💡</span>
                    <span>الإقامات ورخص العمل والتأمين</span>
                </a>
                <a href="#sec-fields-foreign" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة تفصيلياً (الحقول والوثائق)</span>
                </a>
                <a href="#sec-workflow-foreign" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات تسجيل موظف مقيم</span>
                </a>
                <a href="#sec-tips-foreign" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>التنبيهات المبكرة وتجنب الغرامات</span>
                </a>
                <a href="#sec-faq-foreign" class="carnet-tab-link">
                    <span>❓</span>
                    <span>الأسئلة الشائعة</span>
                </a>
            </nav>

            <!-- 3. المحتوى المتدفق والفهرس الجانبي -->
            <div class="carnet-content-grid">

                <!-- الفهرس الجانبي الثابت (يمين) -->
                <aside class="carnet-index-card">
                    <div class="carnet-index-heading">
                        <span>📑</span>
                        <span>فهرس الدليل: الموظفون المقيمون</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديو الشرح (Non-Saudi Employee)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-foreign">
                                <span class="icon">💡</span>
                                <span>شؤون المقيمين وتراخيص العمل</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-foreign">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-foreign">
                                <span class="icon">📋</span>
                                <span>خطوات تسجيل وافد خطوة بخطوة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-foreign">
                                <span class="icon">⭐</span>
                                <span>إدارة الصلاحيات والتجديدات</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-foreign">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: فيديو شرح الموظف غير السعودي Non-Saudi Employee.mp4 -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>{{ __('documentation.hr_guide.foreign_video_title') }}</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            {{ __('documentation.hr_guide.foreign_video_desc') }}
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Non-Saudi Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Non-Saudi Employee.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Non-Saudi Employee.mp4') }}" type="video/mp4">
                                <source src="/videos/Non-Saudi Employee.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Non-Saudi%20Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Non-Saudi%20Employee.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Non-Saudi%20Employee.mp4') }}" type="video/mp4">
                                <source src="/videos/Non-Saudi%20Employee.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Non-Saudi-Employee.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Non-Saudi-Employee.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Non-Saudi Employee.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف إدارة شؤون المقيمين والتراخيص الرسمية -->
                    <section id="sec-concept-foreign" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">إدارة شؤون المقيمين والامتثال لمنصات قوى وأبشر أعمال</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُممت شاشة الموظفين غير السعوديين لتمنح إدارة الموارد البشرية سيطرة كاملة واستباقية على وثائق الكوادر الوافدة؛ حيث ترتبط مباشرة بمواعيد انتهاء الإقامات، صلاحيات جوازات السفر، رخص العمل الصادرة عبر منصة قوى، وبوالص التأمين الطبي المعتمدة من مجلس الضمان الصحي (CCHI)، مع إطلاق تنبيهات ذكية تمنع توقيع أي غرامات تأخير أو إيقاف لخدمات المنشأة:
                        </p>

                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🌐</span>
                                <span class="carnet-arch-title">رقم الإقامة والجنسية</span>
                                <span class="carnet-arch-sub">10 أرقام تبدأ بـ 2 وتنبيهات</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🛂</span>
                                <span class="carnet-arch-title">الجواز ورخصة العمل</span>
                                <span class="carnet-arch-sub">صلاحية الجواز وسداد قوى</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏥</span>
                                <span class="carnet-arch-title">التأمين الصحي (CCHI)</span>
                                <span class="carnet-arch-sub">بوليصة التأمين الطبي السارية</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">✈️</span>
                                <span class="carnet-arch-title">تذاكر السفر والعودة</span>
                                <span class="carnet-arch-sub">الخروج والعودة ونهاية الخدمة</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: كيف تعمل شاشة الموظفين غير السعوديين تفصيلياً (دليل الحقول والإعدادات) -->
                    <section id="sec-fields-foreign" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الموظفين غير السعوديين تفصيلياً؟ (دليل الحقول وتراخيص العمل)</h2>
                        </div>
                        <p class="carnet-text-p">
                            تحتوي شاشة الموظف غير السعودي (Foreign Employee Form) على حقول شاملة تدير البيانات الشخصية والوثائق الحكومية والكفالة المالية والإدارية. إليك البيان التفصيلي لكل حقل:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. البيانات الشخصية والجنسية والجنس -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👤</span>
                                        <span>الاسم والجنسية (Name & Nationality)</span>
                                    </h3>
                                    <span class="carnet-field-badge">إلزامي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الاسم الرباعي الرسمي المسجل في بطاقة الإقامة وجواز السفر، اختيار الجنسية من قائمة الجنسيات الدولية، تحديد الجنس (ذكر / أنثى)، والحالة الاجتماعية.
                                </p>
                            </div>

                            <!-- 2. رقم الإقامة وتاريخ الانتهاء -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🪪</span>
                                        <span>رقم الإقامة وتاريخ الانتهاء</span>
                                    </h3>
                                    <span class="carnet-field-badge">تنبيه تلقائي 90 يوماً</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم الإقامة المكون من 10 أرقام ويبدأ بالرقم 2 وتاريخ انتهائها؛ يرسل النظام تنبيهات مبكرة بلون تحذيري قبل موعد الانتهاء لتجديد الإقامة عبر أبشر أعمال وتفادي غرامات الجوازات.
                                </p>
                            </div>

                            <!-- 3. بيانات جواز السفر وتاريخ الصلاحية -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛂</span>
                                        <span>رقم الجواز وتاريخ الصلاحية</span>
                                    </h3>
                                    <span class="carnet-field-badge">تأشيرات السفر</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم جواز السفر وتاريخ انتهائه؛ يلزم أن يكون سارياً لمدة لا تقل عن 6 أشهر لإصدار تأشيرات الخروج والعودة السنوية للموظف عبر منصة مقيم.
                                </p>
                            </div>

                            <!-- 4. رخصة العمل وتاريخ انتهائها (منصة قوى) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📜</span>
                                        <span>رخصة العمل (Work Permit)</span>
                                    </h3>
                                    <span class="carnet-field-badge">منصة قوى</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم رخصة العمل الصادرة وتاريخ انتهائها؛ يُسجل النظام رقم السداد ورسوم المقابل المالي ويتابع صلاحيتها المرتبطة بصلاحية الإقامة النظامية.
                                </p>
                            </div>

                            <!-- 5. التأمين الطبي ومجلس الضمان الصحي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏥</span>
                                        <span>التأمين الطبي (Insurance Expiry)</span>
                                    </h3>
                                    <span class="carnet-field-badge">CCHI معتمد</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تاريخ سريان وثيقة التأمين الطبي المعتمدة لدى مجلس الضمان الصحي؛ لا يمكن تجديد إقامة الموظف نظاماً إلا بسريان هذه الوثيقة، ويتابع النظام صلاحيتها دورياً.
                                </p>
                            </div>

                            <!-- 6. الكفيل والضامن (Guarantor) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🤝</span>
                                        <span>الضامن والكفيل (Guarantor Option)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حوكمة المسؤولية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد ما إذا كان للموظف كفيل ضامن: <strong>بدون</strong>، أو <strong>داخلي</strong> (اختيار شركة تابعة أو موظف ضامن)، أو <strong>خارجي</strong> لتوثيق الكفالات الإدارية والمالية.
                                </p>
                            </div>

                            <!-- 7. التسكين المؤسسي والوظيفي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>التسكين الإداري والفرع والقسم</span>
                                    </h3>
                                    <span class="carnet-field-badge">عزل وتخصيص</span>
                                </div>
                                <p class="carnet-field-desc">
                                    ربط الموظف بالشركة الحاضنة (وفق الرقم الموحد 700)، وتسكينه على الفرع الميداني والقسم الإداري المناسب، مع تحديد المسمى الوظيفي المعتمد.
                                </p>
                            </div>

                            <!-- 8. الراتب والبدلات والآيبان البنكي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>💰</span>
                                        <span>الراتب والآيبان (Bank IBAN)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حماية الأجور WPS</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الراتب الأساسي والبدلات (سكن، نقل، طعام)، وتوثيق الآيبان البنكي الصادر من أحد البنوك السعودية لضمان رفع الرواتب الشهرية بانتظام عبر ملف حماية الأجور.
                                </p>
                            </div>

                            <!-- 9. تذاكر السفر وتاريخ العودة من الإجازة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>✈️</span>
                                        <span>تذاكر السفر والعودة من الإجازة</span>
                                    </h3>
                                    <span class="carnet-field-badge">العقد السنوي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    استحقاق تذاكر السفر السنوية ذهاباً وإياباً لموطن الموظف، وتسجيل تاريخ العودة الفعلي من آخر إجازة لمتابعة سريان تأشيرة الخروج والعودة بدقة.
                                </p>
                            </div>

                            <!-- 10. مكافأة نهاية الخدمة (EOSB) وحساب الدخول -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛡️</span>
                                        <span>نهاية الخدمة وصلاحيات الدخول</span>
                                    </h3>
                                    <span class="carnet-field-badge">المادة 84 عمل</span>
                                </div>
                                <p class="carnet-field-desc">
                                    احتساب استحقاق مكافأة نهاية الخدمة آلياً (نصف شهر عن أول 5 سنوات وشهر كامل عن كل سنة تالية)، وتحديد حساب دخوله للنظام وصلاحياته (Active / Inactive).
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات تسجيل موظف مقيم -->
                    <section id="sec-workflow-foreign" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">خطوات تسجيل موظف مقيم جديد وتوثيق وثائقه</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">1</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">فتح شاشة الموظفين غير السعوديين</h3>
                                    <p class="carnet-step-text">
                                        انتقل إلى <strong>الموظفون غير السعوديين (Non-Saudi Employees)</strong> واضغط على زر <strong>«إضافة موظف غير سعودي جديد»</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">2</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">تسجيل البيانات الشخصية والجنسية</h3>
                                    <p class="carnet-step-text">
                                        أدخل الاسم بالإنجليزية والعربية، البريد، الجوال، الجنسية، تاريخ الميلاد ليقوم النظام باحتساب العمر تلقائياً.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">3</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">توثيق الإقامة والجواز ورخصة العمل والتأمين</h3>
                                    <p class="carnet-step-text">
                                        سجّل رقم الإقامة وتاريخ انتهائها، رقم الجواز وتاريخ صلاحيته، رخصة العمل عبر قوى، وتاريخ انتهاء بوليصة التأمين الطبي.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">4</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">تحديد الراتب والتسكين المؤسسي والحفظ</h3>
                                    <p class="carnet-step-text">
                                        أدخل الراتب الأساسي والبدلات، الآيبان البنكي، اختر الشركة والفرع والقسم، ثم اضغط <strong>«حفظ (Save)»</strong> لتفعيل تنبيهات الوثائق والتسكين الفوري.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات الصلاحيات والتجديد المبكر -->
                    <section id="sec-tips-foreign" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">إرشادات التجديد المبكر وتفادي غرامات الجوازات</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>⚠️</span>
                                <div>
                                    <strong>التجديد قبل انتهاء الإقامة بـ 30 يوماً على الأقل:</strong>
                                    تجنب مضاعفة رسوم التجديد أو غرامة التأخير التي تفرضها الجوازات عند انتهاء الإقامة دون تجديد.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🏥</span>
                                <div>
                                    <strong>ربط التأمين الطبي الإلزامي:</strong>
                                    لا يقبل نظام سداد إصدار الإقامة إلا بعد رفع وثيقة التأمين الطبي لمجلس الضمان الصحي وربطها بنظام الجوازات آلياً.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>✈️</span>
                                <div>
                                    <strong>صلاحية تأشيرة الخروج والعودة:</strong>
                                    احرص على ألا تتجاوز مدة تأشيرة الخروج والعودة تاريخ انتهاء الإقامة أو صلاحية جواز السفر.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-foreign" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">الأسئلة الشائعة حول شاشة الموظفين غير السعوديين</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>كيف ينبهني النظام عند اقتراب انتهاء إقامة أو جواز سفر الموظف؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    يوفر النظام جدولاً استباقياً ملوناً في لوحة التحكم وتنبيهات بريدية وقوائم فلترة فورية تفرز الموظفين الذين تنتهي وثائقهم خلال 30 أو 60 أو 90 يوماً لاتخاذ إجراء التجديد قبل الموعد.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يمكن تسجيل كفيل ضامن للموظف من داخل المنظومة؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، يدعم قسم الضامن والكفيل (Guarantor) اختيار «كفيل داخلي»، وتحديد إحدى شركات المجموعة أو موظف آخر مسجل كضامن رسمي، مما يحمي المعاملات والعهد الميدانية.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>كيف تحسب مكافأة نهاية الخدمة (EOSB) للموظف الوافد؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    تحسب المكافأة آلياً وفق المادة 84 من نظام العمل السعودي على أساس آخر أجر تقاضاه الموظف: أجر نصف شهر عن كل سنة من السنوات الخمس الأولى، وأجر شهر عن كل سنة من السنوات التالية.
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>
        @elseif(in_array($activeSub, ['attendance', 'attendances']))
            <!-- ----------------------------------------------------------------------
                 دليل الحضور والانصراف وتسجيل الغياب مع الفيديوهين المسجلين (Attendance and Departure 1 & 2)
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>شرح الحضور والانصراف وتسجيل الغياب (Attendance & Absence)</h1>
                        <p>دليل شامل مسجل من جزئين: ضبط ساعات الدوام، أجهزة البصمة، تسجيل الغياب، احتساب التأخير، وخصومات الرواتب.</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في دليل الحضور والغياب..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('attendance')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/attendances" class="carnet-btn-action" style="background: #7c3aed; color: #ffffff; border-color: #7c3aed;">
                        <span>فتح شاشة الحضور ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديوهات الشرح (جزئين)</span>
                </a>
                <a href="#sec-concept-att" class="carnet-tab-link">
                    <span>💡</span>
                    <span>فكرة وأهمية نظام الحضور</span>
                </a>
                <a href="#sec-fields-att" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة تفصيلياً (الحقول والخصومات)</span>
                </a>
                <a href="#sec-workflow-att" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات تسجيل الحضور والغياب</span>
                </a>
                <a href="#sec-tips-att" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>لائحة الجزاءات ومسير الرواتب</span>
                </a>
                <a href="#sec-faq-att" class="carnet-tab-link">
                    <span>❓</span>
                    <span>الأسئلة الشائعة</span>
                </a>
            </nav>

            <!-- 3. المحتوى المتدفق والفهرس الجانبي -->
            <div class="carnet-content-grid">

                <!-- الفهرس الجانبي الثابت (يمين) -->
                <aside class="carnet-index-card">
                    <div class="carnet-index-heading">
                        <span>📑</span>
                        <span>فهرس الدليل: الحضور والغياب</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديوهات الشرح (الجزء 1 و 2)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-att">
                                <span class="icon">💡</span>
                                <span>أهمية الانضباط والربط المالي</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-att">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-att">
                                <span class="icon">📋</span>
                                <span>خطوات تسجيل الحضور والغياب</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-att">
                                <span class="icon">⭐</span>
                                <span>فترة السماح والخصم من الراتب</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-att">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: مشغل الفيديوهين الذكي (الجزء 1 و الجزء 2) -->
                    <script>
                        window.switchAttendanceVideo = function(part) {
                            var v1 = document.getElementById('videoContainer1');
                            var v2 = document.getElementById('videoContainer2');
                            var b1 = document.getElementById('tabBtnVideo1');
                            var b2 = document.getElementById('tabBtnVideo2');
                            var p1 = document.getElementById('playerAttendance1');
                            var p2 = document.getElementById('playerAttendance2');

                            if (part === 1) {
                                if (v1) { v1.style.display = 'block'; v1.removeAttribute('hidden'); }
                                if (v2) { v2.style.display = 'none'; }
                                if (b1) { b1.classList.add('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
                                if (b2) { b2.classList.remove('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
                                if (p2) { p2.pause(); }
                            } else {
                                if (v1) { v1.style.display = 'none'; }
                                if (v2) { v2.style.display = 'block'; v2.removeAttribute('hidden'); }
                                if (b1) { b1.classList.remove('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
                                if (b2) { b2.classList.add('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
                                if (p1) { p1.pause(); }
                                if (p2) {
                                    try {
                                        if (p2.readyState === 0) { p2.load(); }
                                    } catch(e) {}
                                }
                            }
                        };
                    </script>

                    <section id="sec-video" class="carnet-searchable-section" x-data="{
                        activePart: 1,
                        selectPart(p) {
                            this.activePart = p;
                            if (window.switchAttendanceVideo) {
                                window.switchAttendanceVideo(p);
                            }
                        }
                    }">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>فيديوهات شرح الحضور والانصراف وتسجيل الغياب (جزئين مسجلين)</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            تم تقسيم الشرح التوضيحي إلى جزئين متكاملين لتغطية كافة الإجراءات اليومية والرقابية بدقة عالية. انقر على أي جزء للتنقل الفوري بين الفيديوهين:
                        </p>

                        <!-- أزرار التبديل التفاعلية بين الفيديوهين -->
                        <div class="carnet-video-tabs">
                            <button type="button"
                                    id="tabBtnVideo1"
                                    @click="selectPart(1)"
                                    onclick="window.switchAttendanceVideo ? window.switchAttendanceVideo(1) : null"
                                    :class="activePart === 1 ? 'carnet-video-tab-btn active' : 'carnet-video-tab-btn'"
                                    class="carnet-video-tab-btn active">
                                <span>▶</span>
                                <span>الجزء الأول: تسجيل الحضور والانصراف والبصمة</span>
                            </button>
                            <button type="button"
                                    id="tabBtnVideo2"
                                    @click="selectPart(2)"
                                    onclick="window.switchAttendanceVideo ? window.switchAttendanceVideo(2) : null"
                                    :class="activePart === 2 ? 'carnet-video-tab-btn active' : 'carnet-video-tab-btn'"
                                    class="carnet-video-tab-btn">
                                <span>▶</span>
                                <span>الجزء الثاني: تسجيل الغياب وحساب خصومات التأخير</span>
                            </button>
                        </div>

                        <div class="carnet-video-box">
                            <!-- مشغل الجزء الأول: الحضور والانصراف والبصمة -->
                            <div id="videoContainer1"
                                 class="attendance-video-panel"
                                 x-show="activePart === 1"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <span style="font-size: 13.5px; font-weight: 800; color: #0284c7;">
                                        📹 الجزء الأول: تسجيل مواعيد العمل، الحضور، الانصراف، وربط أجهزة البصمة
                                    </span>
                                    <span style="font-size: 11.5px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">
                                        الجزء 1
                                    </span>
                                </div>
                                <video controls preload="auto" class="carnet-video-element" id="playerAttendance1" x-ref="playerAttendance1">
                                    <source src="{{ asset('videos/Attendance and Departure.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance and Departure.mp4" type="video/mp4">
                                    <source src="{{ asset('videos/Attendance%20and%20Departure.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance%20and%20Departure.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/Attendance and Departure.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/Attendance and Departure.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/Attendance%20and%20Departure.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/Attendance%20and%20Departure.mp4" type="video/mp4">
                                    <source src="{{ asset('videos/Attendance-and-Departure.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance-and-Departure.mp4" type="video/mp4">
                                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                                </video>
                                <div class="carnet-video-caption">
                                    <span>📹 فيديو توضيحي مسجل: تسجيل البصمة والدخول والخروج</span>
                                    <span>الملف: <code>/videos/Attendance and Departure.mp4</code></span>
                                </div>
                            </div>

                            <!-- مشغل الجزء الثاني: تسجيل الغياب وخصومات التأخير -->
                            <div id="videoContainer2"
                                 class="attendance-video-panel"
                                 x-show="activePart === 2"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <span style="font-size: 13.5px; font-weight: 800; color: #7c3aed;">
                                        📹 الجزء الثاني: تسجيل حالات الغياب، احتساب دقائق التأخير، والخصم الآلي من الراتب
                                    </span>
                                    <span style="font-size: 11.5px; font-weight: 700; background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 6px;">
                                        الجزء 2
                                    </span>
                                </div>
                                <video controls preload="auto" class="carnet-video-element" id="playerAttendance2" x-ref="playerAttendance2">
                                    <source src="{{ asset('videos/Attendance and Departure2.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance and Departure2.mp4" type="video/mp4">
                                    <source src="{{ asset('videos/Attendance%20and%20Departure2.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance%20and%20Departure2.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/Attendance and Departure2.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/Attendance and Departure2.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/Attendance%20and%20Departure2.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/Attendance%20and%20Departure2.mp4" type="video/mp4">
                                    <source src="{{ asset('videos/Attendance-and-Departure2.mp4') }}" type="video/mp4">
                                    <source src="/videos/Attendance-and-Departure2.mp4" type="video/mp4">
                                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                                </video>
                                <div class="carnet-video-caption">
                                    <span>📹 فيديو توضيحي مسجل: احتساب الغياب والجزاءات التلقائية</span>
                                    <span>الملف: <code>/videos/Attendance and Departure2.mp4</code></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف شاشة الحضور والغياب -->
                    <section id="sec-concept-att" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">فكرة وأهمية نظام الحضور والربط المباشر مع الرواتب</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُمم نظام الحضور والانصراف ليكون الرابط المحوري بين الالتزام الميداني للموظف وبين الإدارة المالية ومسير الرواتب الشهري؛ حيث يقوم النظام بجمع حركات البصمة الحية من الأجهزة أو استقبال التسجيلات اليدوية، ويقارنها بمواعيد دوام الفرع المسكن عليه الموظف بعد احتساب فترة السماح، ويقوم آلياً بحساب دقائق التأخير وساعات العمل الإضافي، وتطبيق لائحة الجزاءات والخصم المباشر من مسير الراتب:
                        </p>

                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">⏱️</span>
                                <span class="carnet-arch-title">حركة البصمة</span>
                                <span class="carnet-arch-sub">جهاز ZKTeco أو تسجيل يدوي</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">⚖️</span>
                                <span class="carnet-arch-title">مقارنة دوام الفرع</span>
                                <span class="carnet-arch-sub">فترة السماح ودقائق التأخير</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🛑</span>
                                <span class="carnet-arch-title">تسجيل الغياب والجزاء</span>
                                <span class="carnet-arch-sub">حاضر، غائب، عذر، إجازة</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">💳</span>
                                <span class="carnet-arch-title">مسير الرواتب (WPS)</span>
                                <span class="carnet-arch-sub">ترحيل الخصومات والاستقطاع</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: كيف تعمل شاشة الحضور والانصراف والغياب تفصيلياً -->
                    <section id="sec-fields-att" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الحضور والانصراف والغياب تفصيلياً؟ (دليل الحقول والإعدادات)</h2>
                        </div>
                        <p class="carnet-text-p">
                            تحتوي شاشة تسجيل وتعديل الحضور (Attendance Form) على حقول ذكية تحسب المؤشرات والجزاءات بصورة تفاعلية لحظية. إليك تفصيل كل حقل ودوره:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. الموظف والتاريخ -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👤</span>
                                        <span>الموظف والتاريخ (Employee & Date)</span>
                                    </h3>
                                    <span class="carnet-field-badge">إلزامي ومُعزل</span>
                                </div>
                                <p class="carnet-field-desc">
                                    اختيار الموظف من القائمة؛ يقوم النظام تلقائياً بعزل الموظفين بحسب فرع وصلاحية المستخدم الحالي، وتحديد تاريخ يوم العمل لاحتساب الحركة ضمن شهره المالي.
                                </p>
                            </div>

                            <!-- 2. حالة الحضور والغياب -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📌</span>
                                        <span>حالة الحضور (Attendance Status)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حالات معتمدة</span>
                                </div>
                                <p class="carnet-field-desc">
                                    <strong>حاضر (Present):</strong> سجل دخوله والتزم بالعمل.<br>
                                    <strong>غائب (Absent):</strong> لم يسجل حضوراً ويُطبق عليه استقطاع الغياب.<br>
                                    <strong>متأخر (Late):</strong> حضر بعد فترة السماح.<br>
                                    <strong>في إجازة (On Leave):</strong> مستبعد من الغياب لاعتماده مسبقاً.
                                </p>
                            </div>

                            <!-- 3. وقت الحضور ووقت الانصراف -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⏰</span>
                                        <span>وقت الدخول والخروج (Check-in/Out)</span>
                                    </h3>
                                    <span class="carnet-field-badge">توقيت دقيق</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تسجيل وقت البصمة الفعلي بنظام 24 ساعة؛ بمجرد إدخال أو تعديل الوقت، يعيد النظام تلقائياً احتساب ساعات التأخير وساعات العمل الفعلية والإضافية.
                                </p>
                            </div>

                            <!-- 4. احتساب دقائق التأخير التلقائي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⏳</span>
                                        <span>دقائق التأخير (Late Minutes)</span>
                                    </h3>
                                    <span class="carnet-field-badge">حساب آلي ذكي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    يقوم النظام بمقارنة وقت الحضور بوقت بدء دوام فرع الموظف، ويطرح منه فترة السماح بالدقائق (Grace Period)، ثم يُسجل الدقائق المتأخرة بالدقة تمهيداً لتطبيق الخصم.
                                </p>
                            </div>

                            <!-- 5. ساعات العمل الإضافي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>➕</span>
                                        <span>ساعات العمل الإضافي (Overtime)</span>
                                    </h3>
                                    <span class="carnet-field-badge">مكافأة الدوام</span>
                                </div>
                                <p class="carnet-field-desc">
                                    حساب الدقائق الإضافية التي قضاها الموظف في العمل بعد موعد الانصراف الرسمي المعتمد، لتُصرف له ضمن مسير الرواتب بنسبة أجر الساعة مضافاً إليه 50% وفق المادة 107.
                                </p>
                            </div>

                            <!-- 6. إجمالي ساعات العمل الفعلية -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⏱️</span>
                                        <span>إجمالي الساعات (Total Hours)</span>
                                    </h3>
                                    <span class="carnet-field-badge">صافي الدوام</span>
                                </div>
                                <p class="carnet-field-desc">
                                    صافي الوقت المنقضي بين وقت الدخول ووقت الخروج؛ للتأكد من استيفاء الموظف لساعات العمل اليومية المقررة نظاماً (8 ساعات أو 9 ساعات في بعض الأنشطة).
                                </p>
                            </div>

                            <!-- 7. مصدر الحضور وجهاز البصمة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📟</span>
                                        <span>المصدر وجهاز البصمة (Device)</span>
                                    </h3>
                                    <span class="carnet-field-badge">توثيق حي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    يوثق النظام ما إذا كانت الحركة قادمة من جهاز البصمة البيومتري (ZKTeco) مع حفظ اسم الجهاز وموقعه، أو تسجيل يدوي بواسطة مشرف الفرع مع كتابة السبب في الملاحظات.
                                </p>
                            </div>

                            <!-- 8. تطبيق الجزاءات والخصم من الراتب -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛑</span>
                                        <span>خدمة الجزاءات (Penalty Service)</span>
                                    </h3>
                                    <span class="carnet-field-badge">استقطاع آلي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تتولى خدمة <code>AttendancePenaltyService</code> تطبيق شرائح الخصم المقررة (خصم ربع يوم، نصف يوم، يوم كامل عند تكرار التأخير، أو خصم يوم غياب كامل) وترحيلها لمسير الراتب.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات تسجيل الحضور والغياب -->
                    <section id="sec-workflow-att" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">خطوات تسجيل ومتابعة الحضور والغياب خطوة بخطوة</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">1</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">المزامنة التلقائية لأجهزة البصمة</h3>
                                    <p class="carnet-step-text">
                                        تقوم أجهزة البصمة المتصلة بالشبكة بإرسال حركات حضور وانصراف الموظفين تلقائياً إلى النظام دون تدخل بشري، ويقوم النظام بمطابقتها مع ملف الموظف.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">2</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">مراجعة كشف الحضور وتسجيل الاستثناءات اليدوية</h3>
                                    <p class="carnet-step-text">
                                        يفتح مشرف الفرع أو مسؤول الموارد البشرية شاشة <strong>سجل الحضور والانصراف</strong>؛ لمراجعة الحالات، وتعديل الحركات الاستثنائية بتحديد الموظف وتوقيت الحضور.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">3</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">توثيق الغياب وتحديد الأسباب</h3>
                                    <p class="carnet-step-text">
                                        في حال عدم حضور الموظف، يتم تحويل حالته إلى <strong>غائب (Absent)</strong>، مع تدوين ما إذا كان الغياب بعذر مقبول أو بدون عذر لتحديد سريان الخصم.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">4</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">الاعتماد النهائي والترحيل لمسير الرواتب</h3>
                                    <p class="carnet-step-text">
                                        في نهاية الشهر، يعتمد مدير الموارد البشرية كشف الحضور؛ فيقوم النظام تلقائياً بترحيل إجمالي ساعات التأخير وأيام الغياب كـ استقطاعات بقسائم الرواتب الشهرية.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات الجزاءات ومسير الرواتب -->
                    <section id="sec-tips-att" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">إرشادات ضبط الدوام ولائحة تنظيم العمل</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>⏱️</span>
                                <div>
                                    <strong>فترة السماح (Grace Period):</strong>
                                    احرص على ضبط فترة السماح في إعدادات فرع المنشأة (مثال: 15 دقيقة) حتى لا يتم احتساب تأخير غير مقصود على الموظفين.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>⚖️</span>
                                <div>
                                    <strong>لائحة الجزاءات لنظام العمل:</strong>
                                    لا يجوز توقيع جزاء خصم على الموظف عن المخالفة الواحدة بما يزيد عن أجر خمسة أيام في الشهر الواحد وفق المادة 70 من نظام العمل.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🔄</span>
                                <div>
                                    <strong>مزامنة أجهزة البصمة دورياً:</strong>
                                    تأكد من اتصال أجهزة البصمة بالإنترنت واستقرار عنوان الـ IP الخاص بها لضمان تدفق الحركات في الوقت الفعلي.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-att" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">الأسئلة الشائعة حول الحضور والانصراف والغياب</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>كيف يعرض النظام الفيديوهين المسجلين للحضور والغياب؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    يوفر النظام مشغلاً ذكياً في أعلى الصفحة يتيح لك التبديل السلس بين «الجزء الأول: تسجيل الحضور وأجهزة البصمة» و«الجزء الثاني: تسجيل الغياب وحساب خصومات التأخير» بضغطة زر واحدة.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يُلغى احتساب الغياب تلقائياً إذا كان الموظف في إجازة معتمدة؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، عند تسجيل إجازة معتمدة للموظف، يتعرف النظام تلقائياً على أن تاريخ اليوم يقع ضمن فترة الإجازة، ويُسجل حالة الموظف كـ «في إجازة» دون احتساب أي غياب أو خصم.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>ماذا يحدث إذا نسي الموظف تسجيل بصمة الانصراف؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    يُظهر النظام إشعاراً بنقص حركة الانصراف، ويمكن لمشرف الفرع أو مسؤول الموارد البشرية تعديل السجل يدوياً وإضافة وقت الانصراف الفعلي مع توثيق السبب في الملاحظات.
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>
        @elseif(in_array($activeSub, ['leaves', 'leave']))
            <!-- ----------------------------------------------------------------------
                 دليل الإجازات والأرصدة ومباشرة العمل مع الفيديوهين المسجلين (leave1 & leave2)
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>شرح شاشة الإجازات والأرصدة ومباشرة العمل (Leaves & Balances)</h1>
                        <p>دليل شامل مسجل من جزئين: تقديم طلبات الإجازات، احتساب الأرصدة تلقائياً، دورة الاعتمادات، تسجيل مباشرة العمل، وحماية الموظف من خصم الغياب.</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في دليل الإجازات والأرصدة..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('leaves')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/leaves" class="carnet-btn-action" style="background: #d97706; color: #ffffff; border-color: #d97706;">
                        <span>فتح شاشة الإجازات ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديوهات الشرح (جزئين)</span>
                </a>
                <a href="#sec-concept-leaves" class="carnet-tab-link">
                    <span>💡</span>
                    <span>فكرة وأهمية إدارة الإجازات</span>
                </a>
                <a href="#sec-fields-leaves" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة تفصيلياً (الحقول والعمليات)</span>
                </a>
                <a href="#sec-workflow-leaves" class="carnet-tab-link">
                    <span>📋</span>
                    <span>دورة حياة طلب الإجازة</span>
                </a>
                <a href="#sec-tips-leaves" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>نظام العمل والأرصدة القانونية</span>
                </a>
                <a href="#sec-faq-leaves" class="carnet-tab-link">
                    <span>❓</span>
                    <span>الأسئلة الشائعة</span>
                </a>
            </nav>

            <!-- 3. المحتوى المتدفق والفهرس الجانبي -->
            <div class="carnet-content-grid">

                <!-- الفهرس الجانبي الثابت (يمين) -->
                <aside class="carnet-index-card">
                    <div class="carnet-index-heading">
                        <span>📑</span>
                        <span>فهرس الدليل: الإجازات</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديوهات الشرح (الجزء 1 و 2)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-leaves">
                                <span class="icon">💡</span>
                                <span>أهمية الأرصدة والربط المالي</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-leaves">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-leaves">
                                <span class="icon">📋</span>
                                <span>خطوات دورة طلب الإجازة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-leaves">
                                <span class="icon">⭐</span>
                                <span>قواعد نظام العمل السعودي ومباشرة العمل</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-leaves">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: مشغل الفيديوهين الذكي للإجازات (الجزء 1: الطلبات والأرصدة / الجزء 2: مباشرة العمل وفروقات العودة) -->
                    <script>
                        window.switchLeaveVideo = function(part) {
                            var v1 = document.getElementById('leaveVideoContainer1');
                            var v2 = document.getElementById('leaveVideoContainer2');
                            var b1 = document.getElementById('leaveTabBtnVideo1');
                            var b2 = document.getElementById('leaveTabBtnVideo2');
                            var p1 = document.getElementById('playerLeave1');
                            var p2 = document.getElementById('playerLeave2');

                            if (part === 1) {
                                if (v1) { v1.style.display = 'block'; v1.removeAttribute('hidden'); }
                                if (v2) { v2.style.display = 'none'; }
                                if (b1) { b1.classList.add('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
                                if (b2) { b2.classList.remove('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
                                if (p2) { p2.pause(); }
                            } else {
                                if (v1) { v1.style.display = 'none'; }
                                if (v2) { v2.style.display = 'block'; v2.removeAttribute('hidden'); }
                                if (b1) { b1.classList.remove('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
                                if (b2) { b2.classList.add('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
                                if (p1) { p1.pause(); }
                                if (p2) {
                                    try {
                                        if (p2.readyState === 0) { p2.load(); }
                                    } catch(e) {}
                                }
                            }
                        };
                    </script>

                    <section id="sec-video" class="carnet-searchable-section" x-data="{
                        activePart: 1,
                        selectPart(p) {
                            this.activePart = p;
                            if (window.switchLeaveVideo) {
                                window.switchLeaveVideo(p);
                            }
                        }
                    }">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">▶</span>
                            <h2>فيديوهات شرح الإجازات والأرصدة ومباشرة العمل (جزئين مسجلين)</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            تم تقسيم الشرح التوضيحي إلى جزئين متكاملين لتغطية كافة دورة الإجازة من تقديم الطلب حتى مباشرة العمل. انقر على أي جزء للتنقل الفوري بين الفيديوهين:
                        </p>

                        <!-- أزرار التبديل التفاعلية بين الفيديوهين -->
                        <div class="carnet-video-tabs">
                            <button type="button"
                                    id="leaveTabBtnVideo1"
                                    @click="selectPart(1)"
                                    onclick="window.switchLeaveVideo ? window.switchLeaveVideo(1) : null"
                                    :class="activePart === 1 ? 'carnet-video-tab-btn active' : 'carnet-video-tab-btn'"
                                    class="carnet-video-tab-btn active">
                                <span>▶</span>
                                <span>الجزء الأول: تقديم الإجازات، احتساب الأرصدة، وسلسلة الموافقات</span>
                            </button>
                            <button type="button"
                                    id="leaveTabBtnVideo2"
                                    @click="selectPart(2)"
                                    onclick="window.switchLeaveVideo ? window.switchLeaveVideo(2) : null"
                                    :class="activePart === 2 ? 'carnet-video-tab-btn active' : 'carnet-video-tab-btn'"
                                    class="carnet-video-tab-btn">
                                <span>▶</span>
                                <span>الجزء الثاني: تسجيل مباشرة العمل، فروقات العودة، والربط بالرواتب</span>
                            </button>
                        </div>

                        <div class="carnet-video-box">
                            <!-- مشغل الجزء الأول: تقديم الطلبات واحتساب الأرصدة -->
                            <div id="leaveVideoContainer1"
                                 class="attendance-video-panel"
                                 x-show="activePart === 1"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <span style="font-size: 13.5px; font-weight: 800; color: #d97706;">
                                        📹 الجزء الأول: اختيار الموظف، فحص رصيد الإجازات، تزامن التواريخ، ومسار الاعتماد
                                    </span>
                                    <span style="font-size: 11.5px; font-weight: 700; background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px;">
                                        الجزء 1
                                    </span>
                                </div>
                                <video controls preload="auto" class="carnet-video-element" id="playerLeave1" x-ref="playerLeave1">
                                    <source src="{{ asset('videos/leave1.mp4') }}" type="video/mp4">
                                    <source src="/videos/leave1.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/leave1.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/leave1.mp4" type="video/mp4">
                                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                                </video>
                                <div class="carnet-video-caption">
                                    <span>📹 فيديو توضيحي مسجل: تقديم طلب الإجازة وفحص الرصيد المستحق</span>
                                    <span>الملف: <code>/videos/leave1.mp4</code></span>
                                </div>
                            </div>

                            <!-- مشغل الجزء الثاني: تسجيل العودة ومباشرة العمل -->
                            <div id="leaveVideoContainer2"
                                 class="attendance-video-panel"
                                 x-show="activePart === 2"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <span style="font-size: 13.5px; font-weight: 800; color: #059669;">
                                        📹 الجزء الثاني: تسجيل مباشرة العمل الفعلي، احتساب أيام التأخير أو التبكير، والتكامل مع الحضور والرواتب
                                    </span>
                                    <span style="font-size: 11.5px; font-weight: 700; background: #d1fae5; color: #047857; padding: 2px 8px; border-radius: 6px;">
                                        الجزء 2
                                    </span>
                                </div>
                                <video controls preload="auto" class="carnet-video-element" id="playerLeave2" x-ref="playerLeave2">
                                    <source src="{{ asset('videos/leave2.mp4') }}" type="video/mp4">
                                    <source src="/videos/leave2.mp4" type="video/mp4">
                                    <source src="{{ asset('vidoes/leave2.mp4') }}" type="video/mp4">
                                    <source src="/vidoes/leave2.mp4" type="video/mp4">
                                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                                </video>
                                <div class="carnet-video-caption">
                                    <span>📹 فيديو توضيحي مسجل: مباشرة العمل وفروقات التواريخ والانضباط</span>
                                    <span>الملف: <code>/videos/leave2.mp4</code></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف شاشة الإجازات والأرصدة -->
                    <section id="sec-concept-leaves" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">💡</span>
                            <h2 class="carnet-card-title">فكرة وأهمية إدارة الإجازات والأرصدة المؤسسية</h2>
                        </div>
                        <p class="carnet-card-text">
                            تُعد شاشة <strong>الإجازات والأرصدة (Leaves & Balances)</strong> المحرك التنظيمي لحقوق الموظفين النظامية في الراحة السنوية والعوارض الصحية والاضطرارية وفق نظام العمل السعودي ولائحته التنفيذية. تضمن الشاشة التوازن الدقيق بين استحقاقات الموظف واحتياجات المنشأة التشغيلية من خلال:
                        </p>
                        <div class="carnet-tips-box" style="margin-top: 14px;">
                            <div class="carnet-tip-item">
                                <span>⚖️</span>
                                <div>
                                    <strong>الضبط الآلي للأرصدة المستحقة:</strong>
                                    يرتبط كل موظف برصيد إجازات سنوي محدد (21 أو 30 يوماً حسب سنوات الخدمة)؛ حيث يقوم النظام بخصم الأيام المعتمدة تلقائياً ومنع تقديم إجازات تتجاوز الرصيد إلا بموافقة إدارية استثنائية.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🛡️</span>
                                <div>
                                    <strong>الحماية التلقائية من رصد الغياب:</strong>
                                    أثناء فترة الإجازة المعتمدة، يقوم محرك الحضور والانصراف بتجميد رصد الغياب على الموظف تلقائياً، ويعتبره في حالة «في إجازة رسمية» دون توقيع أي جزاء أو خصم من الراتب.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🔄</span>
                                <div>
                                    <strong>متابعة استئناف العمل (مباشرة العمل):</strong>
                                    لا تكتمل دورة الإجازة إلا بتوثيق تاريخ العودة الفعلي؛ حيث يحسب النظام فوراً ما إذا كان الموظف قد عاد مبكراً أو في موعده أو تأخر عن العمل لتطبيق الإجراء النظامي المناسب.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: كيف تعمل الشاشة تفصيلياً (الحقول والعمليات) -->
                    <section id="sec-fields-leaves" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الإجازات تفصيلياً (10 حقول وعمليات أساسية)</h2>
                        </div>
                        <p class="carnet-card-text" style="margin-bottom: 20px;">
                            فيما يلي تفكيك شامل لكافة الحقول والوظائف البرمجية داخل نموذج الإجازات وفق أحدث المعايير البرمجية:
                        </p>

                        <div class="carnet-field-grid">

                            <!-- الحقل 1: تحديد الموظف والهيكل الإداري -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">1. اختيار الموظف والفرع والقسم</span>
                                    <span class="carnet-field-badge badge-req">إلزامي أساسي</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        يتم عبر مكون <code>EmployeeSelectionService</code> الذكي؛ حيث يمكنك فلترة واختيار الموظف حسب الشركة أو الفرع أو القسم الإداري، أو البحث بالاسم ورقم الهوية/الإقامة مباشرة. بمجرد اختيار الموظف، يسترجع النظام سجل رصيده وتاريخ تعيينه فوراً.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 2: نوع الإجازة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">2. نوع الإجازة (Leave Type)</span>
                                    <span class="carnet-field-badge badge-req">إلزامي</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        قائمة بأنواع الإجازات المعرفة بالمنشأة (سنوية اعتيادية، مرضية، اضطرارية، وفاة، زواج، أمومة/أبوة، أداء امتحانات، أو إجازة بدون راتب). كل نوع إجازة يرتبط بقواعد رصيد وأثر مالي مستقل ومفعل في دليل أنواع الإجازات.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 3: رصيد الأيام المتاحة لحظياً -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">3. رصيد الإجازات المتاح (Available Balance)</span>
                                    <span class="carnet-field-badge badge-calc">محسوب آلياً لحظياً</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        حقل قراءة فقط <code>available_days_display</code> يعرض الرصيد المتبقي للموظف من هذا النوع حتى تاريخ اليوم، مع شريط مساعدة يوضح تفاصيل (المستحق كلياً - المستهلك سابقاً = المتاح حالياً)، لضمان الشفافية الكاملة قبل تقديم الطلب.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 4: تاريخ البداية والنهاية والأيام (المزامنة الذكية) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">4. التواريخ والأيام (Start / End Date / Days)</span>
                                    <span class="carnet-field-badge badge-sync">مزامنة تفاعلية ثلاثية</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        مزامنة ذكية ثنائية الاتجاه: إذا حددت تاريخ البدء وأدخلت عدد الأيام، يحسب النظام تاريخ النهاية تلقائياً. وإذا اخترت تاريخي البدء والانتهاء، يُحسب عدد الأيام فوراً. مع تحقق لحظي لمنع تجاوز الرصيد المتاح المسموح به.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 5: حالة الطلب ومسار الاعتماد -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">5. حالة الطلب (Status)</span>
                                    <span class="carnet-field-badge badge-req">إلزامي رقابي</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        يتحكم في دورة حياة الطلب عبر حالات محددة: <code>معلق (Pending)</code> للطلبات الجديدة، <code>معتمد (Approved)</code> لاعتماد الإجازة وخصم الرصيد، <code>مرفوض (Rejected)</code> لرفض الطلب، و<code>منجز (Completed)</code> بعد مباشرة العمل وإغلاق الدورة.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 6: سبب الإجازة وسبب الرفض -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">6. مبررات الإجازة وملاحظات الرفض</span>
                                    <span class="carnet-field-badge badge-opt">حقل نصي توثيقي</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        مساحة لتدوين سبب طلب الإجازة للمراجعة من قبل المدير المباشر، وفي حال تغيير الحالة إلى «مرفوض»، يظهر حقل إلزامي مشروط <code>rejection_reason</code> لتوثيق أسباب الرفض حفظاً لحقوق الموظف الإدارية.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 7: المرفقات والتقارير الطبية الرسمية -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">7. المرفقات والمستندات (Attachments)</span>
                                    <span class="carnet-field-badge badge-doc">مستند إلكتروني</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        إمكانية رفع التقارير الطبية المعتمدة من منصة «صحتي» في حالات الإجازات المرضية، أو تذاكر الطيران وتأشيرات الخروج والعودة، مع دعم التنزيل والاستعراض المباشر للمشرفين والمديرين المصرح لهم.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 8: تاريخ العودة الفعلي ومباشرة العمل -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">8. تاريخ العودة الفعلي (Actual Return Date)</span>
                                    <span class="carnet-field-badge badge-sync">يظهر في التعديل فقط</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        قسم مخصص يظهر في شاشة تعديل الإجازة عند استئناف الموظف لعمله؛ حيث يدخل مسؤول الموارد البشرية تاريخ العودة الفعلي لمقارنته آلياً بتاريخ نهاية الإجازة المعتمد وتحديث حالة الموظف بالنظام.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 9: مؤشر فرق العودة والانضباط -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">9. مؤشر فرق العودة (Return Diff Info)</span>
                                    <span class="carnet-field-badge badge-calc">تحليل ذكي ملون</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        يقوم النظام آلياً بتحليل فارق الأيام:
                                        <br>• <strong>عودة مبكرة (Early Return):</strong> عاد الموظف قبل انتهاء إجازته ويُعاد الرصيد المتبقي لصالحه.
                                        <br>• <strong>في الموعد (On Time):</strong> عودة منضبطة ومطابقة تماماً.
                                        <br>• <strong>تأخير عن العودة (Late Return):</strong> رصد أيام التأخير تمهيداً لاحتسابها غياباً غير مبرر.
                                    </p>
                                </div>
                            </div>

                            <!-- الحقل 10: الأثر المالي والربط بالحضور ومسير الرواتب -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <span class="carnet-field-code">10. الأثر المالي والربط بمسير الرواتب</span>
                                    <span class="carnet-field-badge badge-integ">ربط مالي تلقائي</span>
                                </div>
                                <div class="carnet-field-body">
                                    <p>
                                        تتكامل شاشة الإجازات مباشرة مع مسير الرواتب (Payroll Runs)؛ حيث تُصرف مستحقات الإجازة السنوية المدفوعة، بينما تُخصم أيام الإجازات غير المدفوعة أو أيام التأخير عن مباشرة العمل تلقائياً من راتب الشهر دون أي تدخل يدوي.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </section>

                    <!-- القسم 4: دورة حياة طلب الإجازة ومسار العمل -->
                    <section id="sec-workflow-leaves" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">📋</span>
                            <h2 class="carnet-card-title">دورة حياة طلب الإجازة ومسار الاعتماد خطوة بخطوة</h2>
                        </div>
                        <div class="carnet-workflow-box">
                            <div class="carnet-step-card">
                                <span class="carnet-step-number">1</span>
                                <div class="carnet-step-content">
                                    <h3>تقديم الطلب والتحقق من الرصيد</h3>
                                    <p>يتم اختيار الموظف ونوع الإجازة، فيقوم النظام فوراً بعرض رصيده المتبقي ومزامنة تواريخ البدء والنهاية والأيام.</p>
                                </div>
                            </div>
                            <div class="carnet-step-card">
                                <span class="carnet-step-number">2</span>
                                <div class="carnet-step-content">
                                    <h3>المراجعة الإدارية وسلسلة الاعتماد</h3>
                                    <p>يراجع المدير المباشر ومسؤول الموارد البشرية توفر البديل ومرفقات التقرير الطبي ثم تغيير الحالة إلى «معتمد».</p>
                                </div>
                            </div>
                            <div class="carnet-step-card">
                                <span class="carnet-step-number">3</span>
                                <div class="carnet-step-content">
                                    <h3>سريان الإجازة وتجميد رصد الغياب</h3>
                                    <p>طوال أيام الإجازة، يتعرف نظام الحضور والبصمة تلقائياً على أن الموظف مجاز رسمياً ويحميه من أي خصومات غياب.</p>
                                </div>
                            </div>
                            <div class="carnet-step-card">
                                <span class="carnet-step-number">4</span>
                                <div class="carnet-step-content">
                                    <h3>تسجيل مباشرة العمل وإغلاق الدورة</h3>
                                    <p>عند عودة الموظف، يتم فتح الطلب وتدوين تاريخ العودة الفعلي للتحقق من الانضباط وترحيل الفروقات للرواتب.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: قواعد نظام العمل السعودي والأرصدة -->
                    <section id="sec-tips-leaves" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">⭐</span>
                            <h2 class="carnet-card-title">قواعد نظام العمل السعودي ولوائح الإجازات المعتمدة</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>📜</span>
                                <div>
                                    <strong>الإجازة السنوية (المادة 109 من نظام العمل):</strong>
                                    يستحق العامل إجازة سنوية لا تقل عن 21 يوماً مدفوعة الأجر، وتزاد إلى ما لا يقل عن 30 يوماً إذا أمضى العامل في خدمة صاحب العمل خمس سنوات متصلة.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🏥</span>
                                <div>
                                    <strong>الإجازة المرضية (المادة 117):</strong>
                                    للعامل الحق في إجازة مرضية بأجر كامل عن الـ 30 يوماً الأولى، وبثلاثة أرباع الأجر عن الـ 60 يوماً التالية، ودون أجر للـ 30 يوماً التي تلي ذلك خلال السنة الواحدة.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>📱</span>
                                <div>
                                    <strong>التقارير الطبية الرسمية عبر منصة «صحتي»:</strong>
                                    يلتزم الموظف برفع الإجازة المرضية الصادرة من المنظومة الصحية المعتمدة بوزارة الصحة لتكون مقبولة ومعتمدة بنظام العمل.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>⚠️</span>
                                <div>
                                    <strong>التأخر عن مباشرة العمل بعد الإجازة:</strong>
                                    يُعد الانقطاع عن العمل بعد انتهاء الإجازة دون إذن أو عذر مشروع غياباً غير مبرر يخضع لأحكام المادة 80 من نظام العمل بعد إنذار العامل كتابياً.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة حول الإجازات -->
                    <section id="sec-faq-leaves" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">❓</span>
                            <h2 class="carnet-card-title">الأسئلة الشائعة حول شاشة الإجازات والأرصدة</h2>
                        </div>

                        <div class="carnet-faq-accordion">
                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يمنع النظام تقديم إجازة تتجاوز رصيد الموظف المتاح؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، يتحقق النظام برمجياً عبر دالة <code>LeaveBalanceService</code> من الرصيد؛ وفي حال كان عدد الأيام المطلوبة أكبر من الرصيد، يمنع الحفظ ويعرض تنبيهاً بالرصيد المتاح، ولا يمكن تجاوز ذلك إلا للمستخدمين ذوي الصلاحيات الإدارية الخاصة (Manager Override).
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>كيف يتعامل نظام البصمة مع الموظف الموجود في إجازة معتمدة؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    بمجرد اعتماد طلب الإجازة، يرصد محرك الحضور حالة الموظف كـ «في إجازة معتمدة»، ولا يُسجل عليه أي غياب أو خصم دقائق تأخير، ويبقى سجله نظيفاً تماماً لحين تاريخ عودته.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>ماذا يحدث إذا عاد الموظف من إجازته مبكراً عن الموعد المحدد؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    عند تسجيل تاريخ العودة الفعلي المبكر، يُظهر النظام شارة «عودة مبكرة ✓»، ويتم استرجاع الأيام غير المستهلكة وإعادتها فوراً إلى رصيد إجازات الموظف السنوية.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يؤثر تقديم إجازة بدون راتب (Unpaid Leave) على مسير الرواتب؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، عند اعتماد إجازة بدون راتب، يتم ترحيل عدد أيام الإجازة تلقائياً كبند استقطاع في مسير رواتب الشهر المعني، ويُحسم أجر تلك الأيام بدقة وفق الأجر الأساسي والبدلات الخاضعة للخصم.
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>
        @endif

    @endif

</div>

<!-- دوال التفاعل مع المحتوى والبحث المباشر ونسخ الروابط -->
<script>
    function copyCarnetShareLink(sub = 'saudi-employees') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=hr&activeSub=' + sub + '#sec-video';
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

    function switchAttendanceVideo(part) {
        const v1 = document.getElementById('videoContainer1');
        const v2 = document.getElementById('videoContainer2');
        const b1 = document.getElementById('tabBtnVideo1');
        const b2 = document.getElementById('tabBtnVideo2');
        const p1 = document.getElementById('playerAttendance1');
        const p2 = document.getElementById('playerAttendance2');

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
        }
    }

    function switchLeaveVideo(part) {
        var v1 = document.getElementById('leaveVideoContainer1');
        var v2 = document.getElementById('leaveVideoContainer2');
        var b1 = document.getElementById('leaveTabBtnVideo1');
        var b2 = document.getElementById('leaveTabBtnVideo2');
        var p1 = document.getElementById('playerLeave1');
        var p2 = document.getElementById('playerLeave2');

        if (part === 1) {
            if (v1) { v1.style.display = 'block'; v1.removeAttribute('hidden'); }
            if (v2) { v2.style.display = 'none'; }
            if (b1) { b1.classList.add('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
            if (b2) { b2.classList.remove('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
            if (p2) { p2.pause(); }
        } else {
            if (v1) { v1.style.display = 'none'; }
            if (v2) { v2.style.display = 'block'; v2.removeAttribute('hidden'); }
            if (b1) { b1.classList.remove('active'); b1.style.backgroundColor = ''; b1.style.color = ''; }
            if (b2) { b2.classList.add('active'); b2.style.backgroundColor = ''; b2.style.color = ''; }
            if (p1) { p1.pause(); }
            if (p2) {
                try {
                    if (p2.readyState === 0) { p2.load(); }
                } catch(e) {}
            }
        }
    }
</script>
