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
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 968px) {
        .carnet-content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Sticky Right Index Card */
    .carnet-index-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 20px;
    }
    .dark .carnet-index-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-index-heading {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
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
        gap: 6px;
    }
    .carnet-index-item a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
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
        transform: translateX(-4px);
    }
    .dark .carnet-index-item a {
        color: #94a3b8;
    }
    .dark .carnet-index-item a:hover {
        background: #334155;
        color: #38bdf8;
    }

    /* Main Left Flowing Content */
    .carnet-main-flow {
        display: flex;
        flex-direction: column;
        gap: 24px;
        min-width: 0;
    }

    /* 4. Video Box - Red Play Badge */
    .carnet-video-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px;
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
        gap: 12px;
    }
    .carnet-play-badge {
        width: 38px;
        height: 38px;
        background: #dc2626;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 16px;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
        flex-shrink: 0;
    }
    .carnet-video-head h2 {
        font-size: 17px;
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
        margin: 0;
        line-height: 1.6;
    }
    .dark .carnet-video-subtitle {
        color: #94a3b8;
    }
    .carnet-video-element {
        width: 100%;
        max-height: 480px;
        background: #000000;
        border-radius: 12px;
        outline: none;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    .carnet-video-caption {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #64748b;
        padding: 0 4px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .carnet-video-caption code {
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 6px;
        color: #0284c7;
        font-weight: 700;
        direction: ltr;
        display: inline-block;
    }
    .dark .carnet-video-caption code {
        background: #0f172a;
        color: #38bdf8;
    }

    /* 5. Section Cards */
    .carnet-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .dark .carnet-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-card-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark .carnet-card-title-group {
        border-color: #334155;
    }
    .carnet-card-badge {
        font-size: 20px;
    }
    .carnet-card-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .carnet-card-title {
        color: #f8fafc;
    }

    /* Explanation Paragraphs */
    .carnet-text-p {
        font-size: 14px;
        line-height: 1.8;
        color: #334155;
        margin: 0;
    }
    .dark .carnet-text-p {
        color: #cbd5e1;
    }

    /* Architecture Flow Box */
    .carnet-architecture-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        align-items: center;
        justify-content: space-around;
        gap: 12px;
        flex-wrap: wrap;
        margin: 10px 0;
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
        gap: 6px;
        background: #ffffff;
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        min-width: 120px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .dark .carnet-arch-node {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-arch-icon {
        font-size: 22px;
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
       10. Organization Hub Styles (Main Page with the 4 Grand Cards)
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
</style>

<div class="carnet-wrapper">

    @if(empty($activeSub))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم المنشآت (The 4 Grand Cards Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">🏛️ {{ __('documentation.organization.tag') }}</span>
            <h2>{{ __('documentation.organization.hub_heading') }}</h2>
            <p>{{ __('documentation.organization.hub_note') }}</p>
        </div>

        <!-- شبكة الأيقونات والبطاقات الأربعة -->
        <div class="carnet-grand-grid">

            <!-- 1. المنشآت والشركات (Companies) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-blue">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 {{ __('documentation.organization.video_badge') }}</span>
                        <span class="carnet-card-badge-pill pill-info">الكيان الأساسي</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.organization.screen_1_title') }}</h3>
                    <span class="carnet-card-en-title">COMPANIES MANAGEMENT</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.organization.screen_1_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('companies')" class="carnet-btn-open-guide">
                        <span>{{ __('documentation.organization.open_video_guide') }}</span>
                        <span>➔</span>
                    </button>
                    <a href="/companies" class="carnet-btn-screen-direct">
                        <span>{{ __('documentation.organization.direct_screen') }}</span>
                    </a>
                </div>
            </div>

            <!-- 2. الشرح الشامل: المنشآت والملاك والأقسام (Com-Dep-EMP) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو شامل (Com-Dep-EMP)</span>
                        <span class="carnet-card-badge-pill pill-info">الدليل الموحد</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.comprehensive_guide.title') }}</h3>
                    <span class="carnet-card-en-title">COMPREHENSIVE ORGANIZATIONAL GUIDE</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.comprehensive_guide.subtitle') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('comprehensive')" class="carnet-btn-open-guide">
                        <span>🎬 فتح الشرح والفيديو الشامل</span>
                        <span>➔</span>
                    </button>
                    <a href="/branches" class="carnet-btn-screen-direct">
                        <span>الانتقال لشاشات القسم ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. الأقسام والإدارات (Departments) -->
            <div class="carnet-grand-card featured">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو متاح (Departments)</span>
                        <span class="carnet-card-badge-pill pill-info">الهيكل الداخلي</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.organization.screen_3_title') }}</h3>
                    <span class="carnet-card-en-title">DEPARTMENTS & HIERARCHY</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.organization.screen_3_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('departments')" class="carnet-btn-open-guide">
                        <span>{{ __('documentation.organization.open_video_guide') }}</span>
                        <span>➔</span>
                    </button>
                    <a href="/departments" class="carnet-btn-screen-direct">
                        <span>{{ __('documentation.organization.direct_screen') }}</span>
                    </a>
                </div>
            </div>

            <!-- 4. الشركاء والملاك (Owners) -->
            <div class="carnet-grand-card">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو متاح (Owners)</span>
                        <span class="carnet-card-badge-pill pill-info">المستثمرون والحصص</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3>{{ __('documentation.organization.screen_4_title') }}</h3>
                    <span class="carnet-card-en-title">OWNERS & SHAREHOLDERS</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.organization.screen_4_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('owners')" class="carnet-btn-open-guide">
                        <span>{{ __('documentation.organization.open_text_guide') }}</span>
                        <span>➔</span>
                    </button>
                    <a href="/owners" class="carnet-btn-screen-direct">
                        <span>{{ __('documentation.organization.direct_screen') }}</span>
                    </a>
                </div>
            </div>

        </div>

    @else
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية عند اختيار إحدى الشاشات
             ========================================================================== -->

        <!-- شريط الرجوع والتنقل السريع بين الأقسام -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>➔</span>
                <span>{{ __('documentation.organization.back_to_hub') }}</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub('companies')" class="carnet-sub-tab-pill {{ $activeSub === 'companies' ? 'active' : '' }}">
                    <span>🏢 المنشآت والشركات</span>
                </button>
                <button type="button" wire:click="setSub('comprehensive')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['comprehensive', 'branches']) ? 'active' : '' }}">
                    <span>🎬 الشرح الشامل (Com-Dep-EMP)</span>
                </button>
                <button type="button" wire:click="setSub('departments')" class="carnet-sub-tab-pill {{ $activeSub === 'departments' ? 'active' : '' }}">
                    <span>🗂️ الأقسام والإدارات</span>
                </button>
                <button type="button" wire:click="setSub('owners')" class="carnet-sub-tab-pill {{ $activeSub === 'owners' ? 'active' : '' }}">
                    <span>👥 الشركاء والملاك</span>
                </button>
            </div>
        </div>

        @if($activeSub === 'companies')
            <!-- ----------------------------------------------------------------------
                 دليل المنشآت والشركات (Companies) مع الفيديو المسجل
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>{{ __('documentation.organization.title') }}</h1>
                        <p>{{ __('documentation.organization.subtitle') }}</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في محتوى الدليل..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink()" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح (Companies)</span>
                </a>
                <a href="#sec-concept" class="carnet-tab-link">
                    <span>💡</span>
                    <span>فكرة وهدف المنشآت</span>
                </a>
                <a href="#sec-fields" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة والحقول</span>
                </a>
                <a href="#sec-screens" class="carnet-tab-link">
                    <span>📑</span>
                    <span>شاشات القسم المرتبطة</span>
                </a>
                <a href="#sec-workflow" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات الإعداد</span>
                </a>
                <a href="#sec-tips" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>العزل والبيانات</span>
                </a>
                <a href="#sec-faq" class="carnet-tab-link">
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
                        <span>فهرس الصفحة: المنشآت</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديو الشرح (Companies)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept">
                                <span class="icon">💡</span>
                                <span>فكرة وهدف المنشآت</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-screens">
                                <span class="icon">📑</span>
                                <span>شاشات القسم</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow">
                                <span class="icon">📋</span>
                                <span>إعداد المنشأة خطوة بخطوة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips">
                                <span class="icon">⭐</span>
                                <span>العزل المحاسبي وضوابط الزكاة</span>
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

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: فيديو الشرح التوضيحي -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>{{ __('documentation.organization.video_title') }}</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            {{ __('documentation.organization.video_desc') }}
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Companies.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Companies.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/companies.mp4') }}" type="video/mp4">
                                <source src="/vidoes/companies.mp4" type="video/mp4">
                                <source src="/public/vidoes/Companies.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Companies') }}" type="video/mp4">
                                <source src="/vidoes/Companies" type="video/mp4">
                                <source src="{{ asset('videos/Companies.mp4') }}" type="video/mp4">
                                <source src="/videos/Companies.mp4" type="video/mp4">
                                <source src="{{ asset('videos/companies.mp4') }}" type="video/mp4">
                                <source src="/videos/companies.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Companies.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف القسم والمخطط الهيكلي -->
                    <section id="sec-concept" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">{{ __('documentation.organization.concept_label') }}</h2>
                        </div>
                        <p class="carnet-text-p">
                            {{ __('documentation.organization.concept_text') }}
                        </p>

                        <!-- مخطط بصري للهيكل المؤسسي -->
                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏢</span>
                                <span class="carnet-arch-title">المنشأة الأم (Company)</span>
                                <span class="carnet-arch-sub">السجل، الضريبة، الشعار</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏬</span>
                                <span class="carnet-arch-title">الفروع (Branches)</span>
                                <span class="carnet-arch-sub">المواقع الجغرافية ونقاط البيع</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🗂️</span>
                                <span class="carnet-arch-title">الأقسام (Departments)</span>
                                <span class="carnet-arch-sub">الموارد، المالية، العمليات</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👥</span>
                                <span class="carnet-arch-title">الموظفون (Employees)</span>
                                <span class="carnet-arch-sub">عزل الصلاحيات والسجلات</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم: كيف تعمل شاشة المنشآت تفصيلياً (دليل الحقول والإعدادات) -->
                    <section id="sec-fields" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة المنشآت تفصيلياً؟ (دليل الحقول والإعدادات والربط)</h2>
                        </div>
                        <p class="carnet-text-p">
                            تحتوي شاشة إنشاء وتعديل المنشأة على مجموعة من الحقول والإعدادات المحورية التي ترتبط مباشرة بكافة سندات النظام، وفواتيره الضريبية، ومسيرات رواتبه، وخطاباته الرسمية. فيما يلي تفصيل شامل لوظيفة كل حقل وأثره على سير العمل:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. الشعار والختم الرسمي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🖼️</span>
                                        <span>الشعار والختم (Logo & Stamp)</span>
                                    </h3>
                                    <span class="carnet-field-badge">ملفات وصور</span>
                                </div>
                                <p class="carnet-field-desc">
                                    <strong>الشعار:</strong> يظهر تلقائياً في ترويسة جميع الفواتير الضريبية، وسندات القبض والصرف، وكشوفات الحسابات والخطابات الرسمية الصادرة من هذه المنشأة.<br>
                                    <strong>الختم:</strong> يُستخدم لختم المستندات الصادرة إلكترونياً وتوثيق التوقيعات الرسمية.
                                </p>
                            </div>

                            <!-- 2. الاسم التجاري والاسم بالإنجليزي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>الاسم التجاري (عربي / إنجليزي)</span>
                                    </h3>
                                    <span class="carnet-field-badge">إلزامي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الاسم القانوني المعتمد في السجل التجاري؛ يظهر في ترويسة النظام وفي الفواتير الإلكترونية (ZATCA) باللغتين العربية والإنجليزية لضمان مطابقة اشتراطات هيئة الزكاة والضريبة والجمارك.
                                </p>
                            </div>

                            <!-- 3. رقم المنشأة وكود التمييز -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🔢</span>
                                        <span>رقم المنشأة (Company Number)</span>
                                    </h3>
                                    <span class="carnet-field-badge">فريد (Unique)</span>
                                </div>
                                <p class="carnet-field-desc">
                                    معرف رقمي كودي يُمنح لكل منشأة؛ يُستخدم كبادئة لترقيم الفواتير، ومسيرات الرواتب، وتوليد أرقام القيود اليومية في شجرة الحسابات دون تداخل مع بقية المنشآت.
                                </p>
                            </div>

                            <!-- 4. السجل التجاري وتاريخ الانتهاء -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📜</span>
                                        <span>السجل التجاري وتاريخ الانتهاء</span>
                                    </h3>
                                    <span class="carnet-field-badge">تنبيه تلقائي 90 يوماً</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم السجل التجاري الصادر من وزارة التجارة وتاريخ سريانه. يقوم النظام بإرسال إشعارات وتنبيهات ملونة للمدير قبل انتهاء السجل بـ 90 يوماً لتجنب إيقاف الخدمات البنكية والحكومية.
                                </p>
                            </div>

                            <!-- 5. الرقم الضريبي (ZATCA 15 Digits) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🧾</span>
                                        <span>الرقم الضريبي (15 رقماً)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الزكاة والضريبة</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الرقم الضريبي المعياري المعتمد الذي يبدأ وينتهي بالرقم 3. يُضمن تلقائياً في رمز الاستجابة السريعة (QR Code) المشفر للمرحلة الثانية من الفاتورة الإلكترونية.
                                </p>
                            </div>

                            <!-- 6. رخصة البلدية والدفاع المدني -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🚒</span>
                                        <span>رخصة البلدية والدفاع المدني</span>
                                    </h3>
                                    <span class="carnet-field-badge">تراخيص السلامة</span>
                                </div>
                                <p class="carnet-field-desc">
                                    أرقام وتواريخ تجديد رخصة بلدي وتصريح السلامة من الدفاع المدني؛ يتابع النظام صلاحيتها دورياً ويربطها بالفروع التشغيلية التابعة للمنشأة.
                                </p>
                            </div>

                            <!-- 7. الأرقام الحكومية (قوى، التأمينات، الغرفة) -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏛️</span>
                                        <span>الملفات الحكومية (GOSI / QIWA)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الموارد البشرية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم ملف المنشأة في منصة قوى (وزارة الموارد البشرية)، ورقم المشترك في التأمينات الاجتماعية (GOSI)، ورقم عضوية الغرفة التجارية لمطابقة نسب التوطين والاشتراكات.
                                </p>
                            </div>

                            <!-- 8. العنوان الوطني ومعلومات التواصل -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📍</span>
                                        <span>العنوان الوطني ومعلومات التواصل</span>
                                    </h3>
                                    <span class="carnet-field-badge">العنوان المعياري</span>
                                </div>
                                <p class="carnet-field-desc">
                                    المدينة، الحي، الشارع، الرمز البريدي، الرقم الإضافي، البريد الرسمي والهاتف الموحد. تظهر هذه البيانات في أسفل الخطابات والمراسلات والفواتير الضريبية.
                                </p>
                            </div>

                            <!-- 9. الفروع المدمجة وإعدادات الحضور والخصومات -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⏱️</span>
                                        <span>الفروع وإعدادات دوام الحضور</span>
                                    </h3>
                                    <span class="carnet-field-badge">ساعات العمل والخصم</span>
                                </div>
                                <p class="carnet-field-desc">
                                    إمكانية إضافة الفروع التابعة مباشرة وضبط توقيت الحضور والانصراف، وفترة السماح بالدقائق، وتفعيل الخصم التلقائي لشرائح التأخير (ربع يوم / نصف يوم / يوم كامل) في مسير الرواتب.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: الشاشات المشمولة -->
                    <section id="sec-screens" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📑</span>
                            <h2 class="carnet-card-title">{{ __('documentation.organization.screens_label') }}</h2>
                        </div>
                        <p class="carnet-text-p">
                            {{ __('documentation.organization.screens_desc') }}
                        </p>

                        <div class="carnet-screens-grid">
                            <!-- الشاشة 1: المنشآت -->
                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">{{ __('documentation.organization.screen_1_title') }}</h3>
                                    <span class="carnet-screen-badge">الكيان التجاري</span>
                                </div>
                                <p class="carnet-screen-desc">{{ __('documentation.organization.screen_1_desc') }}</p>
                                <a href="/companies" class="carnet-screen-link-btn">
                                    <span>فتح شاشة المنشآت ⇦</span>
                                </a>
                            </div>

                            <!-- الشاشة 2: الفروع -->
                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">{{ __('documentation.organization.screen_2_title') }}</h3>
                                    <span class="carnet-screen-badge">التشغيل الميداني</span>
                                </div>
                                <p class="carnet-screen-desc">{{ __('documentation.organization.screen_2_desc') }}</p>
                                <a href="/branches" class="carnet-screen-link-btn">
                                    <span>فتح شاشة الفروع ⇦</span>
                                </a>
                            </div>

                            <!-- الشاشة 3: الأقسام -->
                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">{{ __('documentation.organization.screen_3_title') }}</h3>
                                    <span class="carnet-screen-badge">الهيكل الإداري</span>
                                </div>
                                <p class="carnet-screen-desc">{{ __('documentation.organization.screen_3_desc') }}</p>
                                <a href="/departments" class="carnet-screen-link-btn">
                                    <span>فتح شاشة الأقسام ⇦</span>
                                </a>
                            </div>

                            <!-- الشاشة 4: الملاك -->
                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">{{ __('documentation.organization.screen_4_title') }}</h3>
                                    <span class="carnet-screen-badge">المستثمرون</span>
                                </div>
                                <p class="carnet-screen-desc">{{ __('documentation.organization.screen_4_desc') }}</p>
                                <a href="/owners" class="carnet-screen-link-btn">
                                    <span>فتح شاشة الملاك ⇦</span>
                                </a>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات العمل خطوة بخطوة -->
                    <section id="sec-workflow" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">{{ __('documentation.organization.workflow_label') }}</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.organization.step_1_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.organization.step_1_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.organization.step_1_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.organization.step_2_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.organization.step_2_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.organization.step_2_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.organization.step_3_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.organization.step_3_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.organization.step_3_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.organization.step_4_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.organization.step_4_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.organization.step_4_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات العزل والحوكمة -->
                    <section id="sec-tips" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">{{ __('documentation.organization.tips_label') }}</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>🔒</span>
                                <div>
                                    <strong>{{ __('documentation.organization.tip_1_title') }}</strong>
                                    {{ __('documentation.organization.tip_1_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🖼️</span>
                                <div>
                                    <strong>{{ __('documentation.organization.tip_2_title') }}</strong>
                                    {{ __('documentation.organization.tip_2_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>📅</span>
                                <div>
                                    <strong>{{ __('documentation.organization.tip_3_title') }}</strong>
                                    {{ __('documentation.organization.tip_3_desc') }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">{{ __('documentation.organization.faq_label') }}</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.organization.faq_1_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.organization.faq_1_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.organization.faq_2_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.organization.faq_2_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.organization.faq_3_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.organization.faq_3_a') }}
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>

        @elseif($activeSub === 'departments')
            <!-- ----------------------------------------------------------------------
                 دليل الأقسام والإدارات (Departments) مع فيديو Departments.mp4
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>{{ __('documentation.departments_guide.title') }}</h1>
                        <p>{{ __('documentation.departments_guide.subtitle') }}</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في محتوى دليل الأقسام..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('departments')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/departments" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                        <span>فتح شاشة الأقسام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل صفحة الأقسام -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح (Departments)</span>
                </a>
                <a href="#sec-concept-dep" class="carnet-tab-link">
                    <span>💡</span>
                    <span>فكرة وأهمية الأقسام</span>
                </a>
                <a href="#sec-structure-dep" class="carnet-tab-link">
                    <span>🏛️</span>
                    <span>الهيكل التنظيمي والشجرة</span>
                </a>
                <a href="#sec-fields-dep" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة والحقول</span>
                </a>
                <a href="#sec-workflow-dep" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات إنشاء قسم</span>
                </a>
                <a href="#sec-tips-dep" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>مراكز التكلفة والاعتمادات</span>
                </a>
                <a href="#sec-faq-dep" class="carnet-tab-link">
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
                        <span>فهرس الصفحة: الأقسام</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديو الشرح (Departments)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-dep">
                                <span class="icon">💡</span>
                                <span>فكرة وأهمية الأقسام</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-structure-dep">
                                <span class="icon">🏛️</span>
                                <span>الهيكل التنظيمي</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-dep">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-dep">
                                <span class="icon">📋</span>
                                <span>خطوات العمل خطوة بخطوة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-dep">
                                <span class="icon">⭐</span>
                                <span>إرشادات مراكز التكلفة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-dep">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: فيديو شرح الأقسام Departments.mp4 -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>{{ __('documentation.departments_guide.video_title') }}</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            {{ __('documentation.departments_guide.video_desc') }}
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Departments.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Departments.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Departments.mp4') }}" type="video/mp4">
                                <source src="/videos/Departments.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/departments.mp4') }}" type="video/mp4">
                                <source src="/vidoes/departments.mp4" type="video/mp4">
                                <source src="{{ asset('videos/departments.mp4') }}" type="video/mp4">
                                <source src="/videos/departments.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Com-Dep-EMP.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Com-Dep-EMP.mp4" type="video/mp4">
                                <source src="/videos/Com-Dep-EMP.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Departments.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف قسم الأقسام والإدارات -->
                    <section id="sec-concept-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">{{ __('documentation.departments_guide.concept_label') }}</h2>
                        </div>
                        <p class="carnet-text-p">
                            {{ __('documentation.departments_guide.concept_text') }}
                        </p>

                        <!-- شبكة تفاعلية لأشهر الإدارات بالمنشأة -->
                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">💼</span>
                                <span class="carnet-arch-title">الموارد البشرية (HR)</span>
                                <span class="carnet-arch-sub">التوظيف، الإجازات، الرواتب</span>
                            </div>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">💰</span>
                                <span class="carnet-arch-title">الإدارة المالية</span>
                                <span class="carnet-arch-sub">الخزينة، السندات، الحسابات</span>
                            </div>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">📈</span>
                                <span class="carnet-arch-title">المبيعات والتسويق</span>
                                <span class="carnet-arch-sub">العقود، العملاء، الفواتير</span>
                            </div>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">⚙️</span>
                                <span class="carnet-arch-title">العمليات والتشغيل</span>
                                <span class="carnet-arch-sub">المشاريع، المستودعات، اللوجستيات</span>
                            </div>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">💻</span>
                                <span class="carnet-arch-title">تقنية المعلومات (IT)</span>
                                <span class="carnet-arch-sub">الأنظمة، الدعم، الأمان</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: الهيكل التنظيمي والربط الإداري -->
                    <section id="sec-structure-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">🏛️</span>
                            <h2 class="carnet-card-title">الهيكل التنظيمي وعلاقة التبعية الإدارية</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُمم نظام الأقسام ليوفر تسلسلاً إدارياً دقيقاً ومرناً؛ بحيث ترتبط كل إدارة بالمنشأة الأم وبالفرع الميداني، ويتفرع منها المسميات الوظيفية والموظفون، مما يتيح للإدارة العليا متابعة مؤشرات الأداء (KPIs) ونسب التوطين والمصروفات لكل وحدة تنظيمية بدقة:
                        </p>

                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏢</span>
                                <span class="carnet-arch-title">الشركة (Company)</span>
                                <span class="carnet-arch-sub">الكيان القانوني الأم</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🗂️</span>
                                <span class="carnet-arch-title">الإدارة / القسم (Department)</span>
                                <span class="carnet-arch-sub">الوحدة التنظيمية ومراكز التكلفة</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👔</span>
                                <span class="carnet-arch-title">رئيس القسم (Manager)</span>
                                <span class="carnet-arch-sub">المسؤول عن الاعتمادات والطلبات</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👥</span>
                                <span class="carnet-arch-title">الموظفون (Employees)</span>
                                <span class="carnet-arch-sub">الكوادر وفرق العمل التنفيذية</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم: كيف تعمل شاشة الأقسام تفصيلياً (دليل الحقول والإعدادات) -->
                    <section id="sec-fields-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الأقسام تفصيلياً؟ (دليل الحقول وشجرة التبعية)</h2>
                        </div>
                        <p class="carnet-text-p">
                            توفر شاشة إدارة الأقسام (Department Form) إمكانيات هيكلية متقدمة لضبط صلاحيات ومسارات العمل ومراكز التكلفة المحاسبية. إليك شرح دقيق لوظيفة كل حقل وكيف يؤثر على المنظومة:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. خيار قسم عام للمجموعة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🌐</span>
                                        <span>قسم عام للمجموعة (Is Global)</span>
                                    </h3>
                                    <span class="carnet-field-badge">مرونة إدارية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    <strong>مفعل:</strong> يُتاح القسم لجميع شركات المجموعة (مثل الإدارة القانونية المركزية، التدقيق الداخلي، أو تقنية المعلومات المشتركة).<br>
                                    <strong>غير مفعل:</strong> يُربط القسم حصرياً بمنشأة واحدة فقط ولا يظهر في باقي الشركات.
                                </p>
                            </div>

                            <!-- 2. الشركة التابعة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>الشركة التابعة (Company Association)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الكيان الحاضن</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد الكيان القانوني الذي يتبع له القسم، مما يضمن تحميل نفقات موظفي هذا القسم وميزانياته التشغيلية على المركز المالي الخاص بهذه الشركة تحديداً.
                                </p>
                            </div>

                            <!-- 3. مسمى القسم وكود التمييز -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏷️</span>
                                        <span>اسم القسم وكوده (Name & Code)</span>
                                    </h3>
                                    <span class="carnet-field-badge">مركز التكلفة</span>
                                </div>
                                <p class="carnet-field-desc">
                                    المسمى الرسمي (عربي/إنجليزي) وكود القسم (مثال: HR-01, FIN-02)؛ يُستخدم هذا الكود تلقائياً كمعرف مركز تكلفة (Cost Center) في دليل الحسابات وسندات الصرف.
                                </p>
                            </div>

                            <!-- 4. الإدارة العليا / القسم الأب -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🌳</span>
                                        <span>القسم الأب (Parent Department)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الهيكل الشجري</span>
                                </div>
                                <p class="carnet-field-desc">
                                    يتيح لك بناء هيكل إداري هرمي غير محدود المستويات (مثال: قطاع العمليات ➔ إدارة التشغيل ➔ قسم الصيانة)؛ مما يسهل استخراج تقارير تجميعية للإدارات العليا.
                                </p>
                            </div>

                            <!-- 5. مدير / رئيس القسم -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👔</span>
                                        <span>مدير القسم (Manager / Head)</span>
                                    </h3>
                                    <span class="carnet-field-badge">سلاسل الاعتماد</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد الموظف أو المستخدم المسؤول عن الإدارة؛ يُحوّل النظام طلبات الإجازات والسلف وتفويض الصلاحيات الخاصة بموظفي هذا القسم إلى حسابه مباشرة للموافقة أو الرفض.
                                </p>
                            </div>

                            <!-- 6. الحالة التشغيلية للقسم -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⚡</span>
                                        <span>الحالة التشغيلية (Status)</span>
                                    </h3>
                                    <span class="carnet-field-badge">نشط / معطل</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تفعيل أو تجميد القسم؛ عند تعطيل القسم، يمنع النظام تسكين أي موظفين جدد عليه أو إنشاء معاملات مستحدثة، مع الحفاظ الكامل على أرشيفه المالي وسجلاته السابقة.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات العمل خطوة بخطوة -->
                    <section id="sec-workflow-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">{{ __('documentation.departments_guide.workflow_label') }}</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.departments_guide.step_1_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.departments_guide.step_1_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.departments_guide.step_1_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.departments_guide.step_2_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.departments_guide.step_2_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.departments_guide.step_2_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.departments_guide.step_3_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.departments_guide.step_3_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.departments_guide.step_3_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.departments_guide.step_4_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.departments_guide.step_4_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.departments_guide.step_4_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات وحوكمة مراكز التكلفة والاعتمادات -->
                    <section id="sec-tips-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">{{ __('documentation.departments_guide.tips_label') }}</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>🎯</span>
                                <div>
                                    <strong>{{ __('documentation.departments_guide.tip_1_title') }}</strong>
                                    {{ __('documentation.departments_guide.tip_1_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>✍️</span>
                                <div>
                                    <strong>{{ __('documentation.departments_guide.tip_2_title') }}</strong>
                                    {{ __('documentation.departments_guide.tip_2_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🛡️</span>
                                <div>
                                    <strong>{{ __('documentation.departments_guide.tip_3_title') }}</strong>
                                    {{ __('documentation.departments_guide.tip_3_desc') }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-dep" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">{{ __('documentation.departments_guide.faq_label') }}</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.departments_guide.faq_1_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.departments_guide.faq_1_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.departments_guide.faq_2_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.departments_guide.faq_2_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.departments_guide.faq_3_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.departments_guide.faq_3_a') }}
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>

        @elseif(in_array($activeSub, ['comprehensive', 'branches']))
            <!-- ----------------------------------------------------------------------
                 الدليل الشامل: المنشآت والملاك والأقسام مع فيديو Com-Dep-EMP.mp4
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>{{ __('documentation.comprehensive_guide.title') }}</h1>
                        <p>{{ __('documentation.comprehensive_guide.subtitle') }}</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في محتوى الدليل الشامل..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('comprehensive')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/companies" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                        <span>فتح شاشة المنشآت ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الدليل الشامل -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح الشامل (Com-Dep-EMP)</span>
                </a>
                <a href="#sec-concept-comp" class="carnet-tab-link">
                    <span>💡</span>
                    <span>التكامل المؤسسي</span>
                </a>
                <a href="#sec-fields-comp" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل المنظومة ككل</span>
                </a>
                <a href="#sec-lifecycle-comp" class="carnet-tab-link">
                    <span>🔄</span>
                    <span>دورة العمل والعزل الذكي</span>
                </a>
                <a href="#sec-workflow-comp" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات التهيئة الشاملة</span>
                </a>
                <a href="#sec-tips-comp" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>حوكمة وعزل المنظومة</span>
                </a>
                <a href="#sec-faq-comp" class="carnet-tab-link">
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
                        <span>فهرس الدليل الشامل</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>الفيديو الشامل (Com-Dep-EMP)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-comp">
                                <span class="icon">💡</span>
                                <span>التكامل المؤسسي</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-comp">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل المنظومة ككل (المستويات)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-lifecycle-comp">
                                <span class="icon">🔄</span>
                                <span>دورة العمل المترابطة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-comp">
                                <span class="icon">📋</span>
                                <span>خطوات التهيئة خطوة بخطوة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-comp">
                                <span class="icon">⭐</span>
                                <span>قواعد العزل والحوكمة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-comp">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: فيديو الشرح الشامل Com-Dep-EMP.mp4 -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>{{ __('documentation.comprehensive_guide.video_title') }}</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            {{ __('documentation.comprehensive_guide.video_desc') }}
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Com-Dep-EMP.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Com-Dep-EMP.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Com-Dep-EMP.mp4') }}" type="video/mp4">
                                <source src="/videos/Com-Dep-EMP.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/com-dep-emp.mp4') }}" type="video/mp4">
                                <source src="/vidoes/com-dep-emp.mp4" type="video/mp4">
                                <source src="{{ asset('videos/com-dep-emp.mp4') }}" type="video/mp4">
                                <source src="/videos/com-dep-emp.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Organization.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Organization.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Organization.mp4') }}" type="video/mp4">
                                <source src="/videos/Organization.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Branches.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Branches.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو المباشر، يرجى استخدام متصفح حديث.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو شامل مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Com-Dep-EMP.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: التكامل المؤسسي بين المنشآت والملاك والأقسام -->
                    <section id="sec-concept-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">{{ __('documentation.comprehensive_guide.concept_label') }}</h2>
                        </div>
                        <p class="carnet-text-p">
                            {{ __('documentation.comprehensive_guide.concept_text') }}
                        </p>

                        <!-- شبكة تفاعلية للدورة المؤسسية الشاملة -->
                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👥</span>
                                <span class="carnet-arch-title">1. الملاك والمستثمرون</span>
                                <span class="carnet-arch-sub">أصحاب الحصص ورأس المال</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏢</span>
                                <span class="carnet-arch-title">2. المنشآت والشركات</span>
                                <span class="carnet-arch-sub">السجل، الضريبة، الهوية</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🗂️</span>
                                <span class="carnet-arch-title">3. الأقسام والإدارات</span>
                                <span class="carnet-arch-sub">مراكز التكلفة والمديرون</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏬</span>
                                <span class="carnet-arch-title">4. الفروع والمواقع</span>
                                <span class="carnet-arch-sub">التشغيل الميداني ونقاط البيع</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👤</span>
                                <span class="carnet-arch-title">5. الكوادر والموظفون</span>
                                <span class="carnet-arch-sub">الرواتب والإجازات والعزل</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم: كيف تعمل المنظومة ككل؟ (دليل دورة البيانات والربط الشامل) -->
                    <section id="sec-fields-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل المنظومة ككل؟ (المستويات الخمسة والترابط التشغيلي)</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُممت المنظومة المتكاملة (Com-Dep-EMP) لتعمل بنظام الطبقات المؤسسية المتسلسلة؛ حيث تتدفق البيانات بسلاسة وتلقائية من أعلى قمة الهيكل المؤسسي (الملاك) وحتى أصغر عملية يومية يسجلها الموظف، مع ضمان العزل التام بين الكيانات:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. مستوى الملاك والمستثمرين -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👑</span>
                                        <span>1. مستوى الملاك (Owners Level)</span>
                                    </h3>
                                    <span class="carnet-field-badge">رأس المال والقرار</span>
                                </div>
                                <p class="carnet-field-desc">
                                    توثيق هويات الشركاء ونسب حصصهم الاستثمارية؛ يمنح النظام كل شريك حساب دخول معزول للاطلاع اللحظي على مؤشرات أداء الشركات التي يملك فيها حصة فقط، دون الإخلال بخصوصية بقية شركات المجموعة.
                                </p>
                            </div>

                            <!-- 2. مستوى المنشآت والكيانات -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>2. مستوى الشركات (Companies Level)</span>
                                    </h3>
                                    <span class="carnet-field-badge">الهوية القانونية والضريبية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    الكيان التجاري المستقل؛ يشمل السجل التجاري، الرقم الضريبي (ZATCA)، تراخيص البلدية، الشعار والختم المؤسسي. تتوارث كافة فواتير وسندات الشركة هذه الهوية تلقائياً.
                                </p>
                            </div>

                            <!-- 3. مستوى الأقسام ومراكز التكلفة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🗂️</span>
                                        <span>3. مستوى الأقسام (Departments Level)</span>
                                    </h3>
                                    <span class="carnet-field-badge">التنظيم ومراكز التكلفة</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تقسيم النشاط التشغيلي إلى إدارات وأقسام؛ يُربط كل قسم بمركز تكلفة في شجرة الحسابات، ويُعين مدير لكل إدارة لاعتماد طلبات موظفيه وإجازاتهم وسلفهم إلكترونياً.
                                </p>
                            </div>

                            <!-- 4. مستوى الفروع والمواقع -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏬</span>
                                        <span>4. مستوى الفروع (Branches Level)</span>
                                    </h3>
                                    <span class="carnet-field-badge">التشغيل الميداني والدوام</span>
                                </div>
                                <p class="carnet-field-desc">
                                    توزيع جغرافي للمعارض والمستودعات ومراكز الخدمة؛ يضبط الفرع أوقات الحضور والانصراف، فترات السماح، وحساب خصومات التأخير التلقائية على رواتب موظفي الفرع.
                                </p>
                            </div>

                            <!-- 5. مستوى الموظفين وتطبيق العزل -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>👤</span>
                                        <span>5. مستوى الكوادر (Employees Level)</span>
                                    </h3>
                                    <span class="carnet-field-badge">التنفيذ والعزل (Scoping)</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تسكين الموظف بدقة على منشأة وفرع وقسم؛ يتحكم هذا التسكين تلقائياً في تحديد مسير الراتب، شروط الإجازات، والبيانات المالية التي يحق للموظف الوصول إليها في النظام.
                                </p>
                            </div>

                            <!-- 6. أثر الترابط التلقائي في المستندات -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>⚡</span>
                                        <span>توارث البيانات وسرعة المعالجة</span>
                                    </h3>
                                    <span class="carnet-field-badge">أتمتة ذكية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    عند إنشاء فاتورة، أو صرف سلفة، أو إصدار خطاب؛ يقوم النظام بملء بيانات المنشأة، والفرع، ومركز التكلفة، والختم والشعار آلياً بناءً على مستخدم النظام والفرع التابع له دون حاجة لإعادة إدخالها.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: دورة العمل المترابطة -->
                    <section id="sec-lifecycle-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">🔄</span>
                            <h2 class="carnet-card-title">تكامل البيانات والعزل التلقائي (Company Scoping)</h2>
                        </div>
                        <p class="carnet-text-p">
                            يعتمد النظام تقنية العزل المؤسسي الشامل؛ حيث يتم عزل كل منشأة محاسبياً وإدارياً بشكل تام. عند دخول مالك المنشأة أو مدير الفرع أو رئيس القسم، تُطبّق عليه فوراً سياسة الصلاحيات المحددة، مما يضمن سرية البيانات المالية وسجلات الموظفين والفواتير بين الشركات المختلفة بكل أمان واحترافية.
                        </p>

                        <div class="carnet-screens-grid" style="margin-top: 10px;">
                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">المنشآت والشركات</h3>
                                    <span class="carnet-screen-badge">الكيان</span>
                                </div>
                                <p class="carnet-screen-desc">إدارة الكيانات والسجلات التجارية والأرقام الضريبية والشعار.</p>
                                <a href="/companies" class="carnet-screen-link-btn">فتح شاشة المنشآت ⇦</a>
                            </div>

                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">الشركاء والملاك</h3>
                                    <span class="carnet-screen-badge">الملكية</span>
                                </div>
                                <p class="carnet-screen-desc">سجلات المستثمرين ونسب الملكية وحسابات الدخول ومتابعة الأرباح.</p>
                                <a href="/owners" class="carnet-screen-link-btn">فتح شاشة الملاك ⇦</a>
                            </div>

                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">الأقسام والإدارات</h3>
                                    <span class="carnet-screen-badge">التنظيم</span>
                                </div>
                                <p class="carnet-screen-desc">الهيكل الداخلي، مراكز التكلفة، وتعيين رؤساء الإدارات.</p>
                                <a href="/departments" class="carnet-screen-link-btn">فتح شاشة الأقسام ⇦</a>
                            </div>

                            <div class="carnet-screen-item-card">
                                <div class="carnet-screen-header">
                                    <h3 class="carnet-screen-title">الفروع التشغيلية</h3>
                                    <span class="carnet-screen-badge">الميدان</span>
                                </div>
                                <p class="carnet-screen-desc">المواقع الجغرافية والمستودعات والمعارض ونقاط البيع.</p>
                                <a href="/branches" class="carnet-screen-link-btn">فتح شاشة الفروع ⇦</a>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات العمل خطوة بخطوة -->
                    <section id="sec-workflow-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">{{ __('documentation.comprehensive_guide.workflow_label') }}</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.comprehensive_guide.step_1_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.comprehensive_guide.step_1_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.comprehensive_guide.step_1_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.comprehensive_guide.step_2_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.comprehensive_guide.step_2_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.comprehensive_guide.step_2_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.comprehensive_guide.step_3_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.comprehensive_guide.step_3_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.comprehensive_guide.step_3_desc') }}</p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">{{ __('documentation.comprehensive_guide.step_4_num') }}</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">{{ __('documentation.comprehensive_guide.step_4_title') }}</h3>
                                    <p class="carnet-step-text">{{ __('documentation.comprehensive_guide.step_4_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات وقواعد التكامل المؤسسي -->
                    <section id="sec-tips-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">{{ __('documentation.comprehensive_guide.tips_label') }}</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>🎯</span>
                                <div>
                                    <strong>{{ __('documentation.comprehensive_guide.tip_1_title') }}</strong>
                                    {{ __('documentation.comprehensive_guide.tip_1_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🖼️</span>
                                <div>
                                    <strong>{{ __('documentation.comprehensive_guide.tip_2_title') }}</strong>
                                    {{ __('documentation.comprehensive_guide.tip_2_desc') }}
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🔒</span>
                                <div>
                                    <strong>{{ __('documentation.comprehensive_guide.tip_3_title') }}</strong>
                                    {{ __('documentation.comprehensive_guide.tip_3_desc') }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-comp" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">{{ __('documentation.comprehensive_guide.faq_label') }}</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.comprehensive_guide.faq_1_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.comprehensive_guide.faq_1_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.comprehensive_guide.faq_2_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.comprehensive_guide.faq_2_a') }}
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>{{ __('documentation.comprehensive_guide.faq_3_q') }}</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    {{ __('documentation.comprehensive_guide.faq_3_a') }}
                                </div>
                            </details>
                        </div>
                    </section>

                </main>
            </div>

        @elseif($activeSub === 'owners')
            <!-- ----------------------------------------------------------------------
                 دليل الشركاء والملاك (Owners) بتصميم كارنت الكامل
                 ---------------------------------------------------------------------- -->

            <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
            <div class="carnet-top-bar">
                <div class="carnet-title-box">
                    <div class="carnet-logo-icon bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-title-text">
                        <h1>شرح الشركاء والملاك (Owners & Shareholders)</h1>
                        <p>دليل إدارة سجل المستثمرين، نسب الملكية في رأس المال، حسابات الدخول المعزولة، وتوزيعات الأرباح.</p>
                    </div>
                </div>

                <div class="carnet-actions-box">
                    <input
                        type="search"
                        id="carnetSearchInput"
                        class="carnet-search-input"
                        placeholder="ابحث في محتوى دليل الملاك..."
                        onkeyup="filterCarnetContent()"
                    >
                    <button type="button" onclick="copyCarnetShareLink('owners')" class="carnet-btn-action">
                        <span>🔗</span>
                        <span>نسخ الرابط</span>
                    </button>
                    <button type="button" onclick="window.print()" class="carnet-btn-action">
                        <span>🖨️</span>
                        <span>طباعة الدليل</span>
                    </button>
                    <a href="/owners" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                        <span>فتح شاشة الملاك ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل صفحة الملاك -->
            <nav class="carnet-secondary-tabs">
                <a href="#sec-video" class="carnet-tab-link">
                    <span>🎬</span>
                    <span>فيديو الشرح (Owners)</span>
                </a>
                <a href="#sec-concept-owners" class="carnet-tab-link">
                    <span>💡</span>
                    <span>فكرة وأهمية شاشة الملاك</span>
                </a>
                <a href="#sec-fields-owners" class="carnet-tab-link">
                    <span>⚙️</span>
                    <span>كيف تعمل الشاشة والحقول</span>
                </a>
                <a href="#sec-workflow-owners" class="carnet-tab-link">
                    <span>📋</span>
                    <span>خطوات تسجيل شريك</span>
                </a>
                <a href="#sec-tips-owners" class="carnet-tab-link">
                    <span>⭐</span>
                    <span>حوكمة الحصص والسرية</span>
                </a>
                <a href="#sec-faq-owners" class="carnet-tab-link">
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
                        <span>فهرس الصفحة: الملاك</span>
                    </div>
                    <ul class="carnet-index-list">
                        <li class="carnet-index-item">
                            <a href="#sec-video">
                                <span class="icon">🎬</span>
                                <span>فيديو الشرح (Owners)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-concept-owners">
                                <span class="icon">💡</span>
                                <span>فكرة وأهمية شاشة الملاك</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-fields-owners">
                                <span class="icon">⚙️</span>
                                <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-workflow-owners">
                                <span class="icon">📋</span>
                                <span>خطوات تسجيل شريك خطوة بخطوة</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-tips-owners">
                                <span class="icon">⭐</span>
                                <span>العزل وسرية بيانات المستثمر</span>
                            </a>
                        </li>
                        <li class="carnet-index-item">
                            <a href="#sec-faq-owners">
                                <span class="icon">❓</span>
                                <span>الأسئلة الشائعة</span>
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- المحتوى الرئيسي (يسار) -->
                <main class="carnet-main-flow">

                    <!-- القسم 1: مشغل فيديو الملاك Owners.mp4 -->
                    <section id="sec-video" class="carnet-searchable-section">
                        <div class="carnet-video-head">
                            <span class="carnet-play-badge">▶</span>
                            <h2>فيديو شرح شاشة الشركاء والملاك (Owners)</h2>
                        </div>
                        <p class="carnet-video-subtitle">
                            فيديو توضيحي مسجل يوضح خطوات توثيق بيانات الشركاء، نسب الملكية في رأس المال، إنشاء حسابات الدخول، ومتابعة الأرباح:
                        </p>

                        <div class="carnet-video-box">
                            <video controls preload="metadata" class="carnet-video-element">
                                <source src="{{ asset('vidoes/Owners.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Owners.mp4" type="video/mp4">
                                <source src="{{ asset('videos/Owners.mp4') }}" type="video/mp4">
                                <source src="/videos/Owners.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/owners.mp4') }}" type="video/mp4">
                                <source src="/vidoes/owners.mp4" type="video/mp4">
                                <source src="{{ asset('videos/owners.mp4') }}" type="video/mp4">
                                <source src="/videos/owners.mp4" type="video/mp4">
                                <source src="{{ asset('vidoes/Com-Dep-EMP.mp4') }}" type="video/mp4">
                                <source src="/vidoes/Com-Dep-EMP.mp4" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو.
                            </video>
                            <div class="carnet-video-caption">
                                <span>📹 فيديو توضيحي مسجل من واقع شاشات النظام</span>
                                <span>مسار الفيديو: <code>/vidoes/Owners.mp4</code></span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 2: فكرة وهدف شاشة الملاك -->
                    <section id="sec-concept-owners" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">💡</span>
                            <h2 class="carnet-card-title">فكرة وأهمية إدارة سجل الشركاء والمستثمرين</h2>
                        </div>
                        <p class="carnet-text-p">
                            صُممت شاشة الشركاء والملاك لتكون المظلة الاستثمارية العليا لكافة الكيانات التجارية للمجموعة؛ حيث تتيح توثيق حصص الملاك بدقة قانونية ومحاسبية، وربط كل شريك بالشركات التي يستثمر فيها، مع إنشاء حساب دخول تلقائي يمكنه من متابعة التقارير المالية للشركات التابعة له دون التدخل في العمليات اليومية أو الاطلاع على بيانات الكيانات الأخرى:
                        </p>

                        <div class="carnet-architecture-box">
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">👤</span>
                                <span class="carnet-arch-title">بيانات الشريك (Owner Profile)</span>
                                <span class="carnet-arch-sub">الهوية، البريد، الجوال، التوقيع</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🏢</span>
                                <span class="carnet-arch-title">الشركة المستثمر بها</span>
                                <span class="carnet-arch-sub">ربط بالكيان التجاري المعتمد</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">📊</span>
                                <span class="carnet-arch-title">نسبة الملكية (% Equity)</span>
                                <span class="carnet-arch-sub">توزيع الأرباح وحصص التصويت</span>
                            </div>
                            <span class="carnet-arch-arrow">➔</span>
                            <div class="carnet-arch-node">
                                <span class="carnet-arch-icon">🔒</span>
                                <span class="carnet-arch-title">حساب دخول معزول</span>
                                <span class="carnet-arch-sub">لوحة متابعة استثمارية خاصة</span>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 3: كيف تعمل شاشة الملاك تفصيلياً (دليل الحقول والإعدادات) -->
                    <section id="sec-fields-owners" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⚙️</span>
                            <h2 class="carnet-card-title">كيف تعمل شاشة الشركاء والملاك تفصيلياً؟ (دليل الحقول والحسابات)</h2>
                        </div>
                        <p class="carnet-text-p">
                            تتضمن شاشة إضافة وتعديل الشريك (Owner Form) حقولاً هامة تدير الحساب الإلكتروني والحصة القانونية والمالية للشريك في رأس المال. إليك تفصيل كل حقل ودوره:
                        </p>

                        <div class="carnet-fields-grid">
                            <!-- 1. حساب المستخدم التلقائي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🔑</span>
                                        <span>إنشاء حساب دخول تلقائي (User Account)</span>
                                    </h3>
                                    <span class="carnet-field-badge">أتمتة النظام</span>
                                </div>
                                <p class="carnet-field-desc">
                                    عند إدخال اسم الشريك، بريده الإلكتروني، رقم جواله، وكلمة المرور؛ يقوم النظام تلقائياً بإنشاء مستخدم (User Record) في جدول المستخدمين، مما يتيح للشريك تسجيل الدخول مباشرة للنظام والوصول إلى لوحة متابعة خاصة بشركاته.
                                </p>
                            </div>

                            <!-- 2. الشركة التابعة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🏢</span>
                                        <span>الشركة التابعة (Company Association)</span>
                                    </h3>
                                    <span class="carnet-field-badge">إلزامي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تحديد الكيان التجاري الذي يمتلك فيه الشريك حصة؛ يُستخدم هذا الربط في تطبيق صلاحيات العزل (Scoping)، بحيث لا تظهر للشريك إلا بيانات ومبيعات ومصروفات شركته فقط.
                                </p>
                            </div>

                            <!-- 3. نسبة الملكية في رأس المال -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📊</span>
                                        <span>نسبة الملكية (Ownership Percentage %)</span>
                                    </h3>
                                    <span class="carnet-field-badge">نسبة مئوية</span>
                                </div>
                                <p class="carnet-field-desc">
                                    حصة الشريك المسجلة في عقد التأسيس (مثال: 50% أو 25%)؛ يعتمد النظام هذه النسبة تلقائياً عند احتساب توزيعات الأرباح السنوية والمسؤوليات القانونية.
                                </p>
                            </div>

                            <!-- 4. الهوية الوطنية والجنسية وتاريخ الميلاد -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🪪</span>
                                        <span>بيانات الهوية والجنسية</span>
                                    </h3>
                                    <span class="carnet-field-badge">توثيق رسمي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    رقم الهوية الوطنية أو الإقامة، وتاريخ الميلاد، والجنسية؛ لضمان التوافق مع سجلات وزارة التجارة، وقرارات الجمعية العمومية، وتوثيق أسماء الشركاء في السجلات الرسمية.
                                </p>
                            </div>

                            <!-- 5. تاريخ بدء الشراكة والاستثمار -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>📅</span>
                                        <span>تاريخ بدء الشراكة (Start Date)</span>
                                    </h3>
                                    <span class="carnet-field-badge">بدء الاستحقاق</span>
                                </div>
                                <p class="carnet-field-desc">
                                    تاريخ انضمام الشريك للكيان التجاري أو تاريخ قيد الحصة؛ يُعتمد لحساب استحقاق الأرباح الدورية عن الفترات المالية بدقة.
                                </p>
                            </div>

                            <!-- 6. تاريخ إنهاء الشراكة والتعطيل التلقائي -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛑</span>
                                        <span>تاريخ التخارج (Termination Date)</span>
                                    </h3>
                                    <span class="carnet-field-badge">تعطيل فوري</span>
                                </div>
                                <p class="carnet-field-desc">
                                    عند تخارج الشريك أو بيع حصته؛ بمجرد تحديد تاريخ إنهاء الشراكة، يقوم النظام تلقائياً بتحويل حالة الشريك إلى «معطل» وإلغاء صلاحية تسجيل دخوله للبيانات النشطة، مع الحفاظ الكامل على الأرشيف التاريخي لمعاملاته.
                                </p>
                            </div>

                            <!-- 7. الصورة الشخصية والختم الخاص -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🖼️</span>
                                        <span>الصورة والتوقيع (Photo / Signature)</span>
                                    </h3>
                                    <span class="carnet-field-badge">ملف تعريفي</span>
                                </div>
                                <p class="carnet-field-desc">
                                    صورة الملف الشخصي للشريك وتوثيق توقيعه المعتمد لطباعته في محاضر اجتماعات مجلس الإدارة ومستندات الجمعية العمومية الرسمية.
                                </p>
                            </div>

                            <!-- 8. العزل والسرية الاستثمارية المتقدمة -->
                            <div class="carnet-field-card">
                                <div class="carnet-field-header">
                                    <h3 class="carnet-field-title">
                                        <span>🛡️</span>
                                        <span>العزل المالي للمستثمر (Investor Scoping)</span>
                                    </h3>
                                    <span class="carnet-field-badge">أمان مطلق</span>
                                </div>
                                <p class="carnet-field-desc">
                                    يضمن النظام أماناً تاماً؛ بحيث لا يستطيع أي شريك الاطلاع على إيرادات أو كشوفات حساب أي شركة أخرى في المجموعة القابضة لا يملك فيها حصة معتمدة.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 4: خطوات تسجيل شريك جديد -->
                    <section id="sec-workflow-owners" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">📋</span>
                            <h2 class="carnet-card-title">خطوات تسجيل شريك/مستثمر جديد خطوة بخطوة</h2>
                        </div>

                        <div class="carnet-steps-list">
                            <!-- الخطوة 1 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">1</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">فتح نموذج إضافة شريك جديد</h3>
                                    <p class="carnet-step-text">
                                        انتقل إلى شاشة <strong>الشركاء والملاك (Owners)</strong> من القائمة الجانبية أو من زر الوصول السريع أعلى الصفحة، ثم اضغط على زر <strong>«إضافة شريك جديد (New Owner)»</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 2 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">2</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">تحديد الشركة المستثمر بها وإدخال نسبة الملكية</h3>
                                    <p class="carnet-step-text">
                                        اختر <strong>الشركة (Company)</strong> من القائمة المنسدلة، وأدخل <strong>نسبة الملكية (% Equity)</strong> في رأس المال كما هي مسجلة في عقد التأسيس والسجل التجاري.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 3 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">3</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">إدخال بيانات الهوية والحساب الإلكتروني</h3>
                                    <p class="carnet-step-text">
                                        سجّل رقم الهوية الوطنية/الإقامة، تاريخ الميلاد، وأدخل البريد الإلكتروني ورقم الجوال وكلمة المرور لإنشاء حساب الدخول التلقائي للشريك.
                                    </p>
                                </div>
                            </div>

                            <!-- الخطوة 4 -->
                            <div class="carnet-step-row">
                                <div class="carnet-step-number">4</div>
                                <div class="carnet-step-content">
                                    <h3 class="carnet-step-title">حفظ السجل وتفعيل الحساب</h3>
                                    <p class="carnet-step-text">
                                        اضغط على <strong>«حفظ (Save)»</strong>؛ يقوم النظام فوراً بإنشاء سجل الشريك، وتفعيل حساب دخوله، وإرسال بيانات الدخول لتمكينه من متابعة شركاته بأمان تام.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 5: إرشادات الحوكمة والسرية -->
                    <section id="sec-tips-owners" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">⭐</span>
                            <h2 class="carnet-card-title">إرشادات حوكمة الحصص والسرية الاستثمارية</h2>
                        </div>

                        <div class="carnet-tips-box">
                            <div class="carnet-tip-item">
                                <span>🔒</span>
                                <div>
                                    <strong>عزل حسابات الملاك التام:</strong>
                                    لا تسمح للشريك باستخدام حساب موظف تشغيلي؛ حساب المالك مخصص للمتابعة والتقارير الاستثمارية لحماية دورة العمل.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>📊</span>
                                <div>
                                    <strong>مجموع نسب الملكية 100%:</strong>
                                    تأكد دائماً أن إجمالي نسب حصص الملاك المسجلين لكل شركة لا يتجاوز 100% لضمان صحة معادلات توزيع الأرباح.
                                </div>
                            </div>
                            <div class="carnet-tip-item">
                                <span>🛑</span>
                                <div>
                                    <strong>إجراءات التخارج الفوري:</strong>
                                    في حال خروج أي شريك أو نقل ملكيته، قم بتعبئة «تاريخ إنهاء الشراكة» فوراً ليقوم النظام بوقف صلاحيات دخوله آلياً.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- القسم 6: الأسئلة الشائعة -->
                    <section id="sec-faq-owners" class="carnet-card carnet-searchable-section">
                        <div class="carnet-card-title-group">
                            <span class="carnet-card-badge">❓</span>
                            <h2 class="carnet-card-title">الأسئلة الشائعة حول شاشة الشركاء والملاك</h2>
                        </div>

                        <div class="carnet-faq-group">
                            <details class="carnet-faq-item" open>
                                <summary class="carnet-faq-summary">
                                    <span>هل يستطيع الشريك الدخول على النظام بنفسه؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، عند تسجيل الشريك يقوم النظام تلقائياً بإنشاء مستخدم (User Record) ببريده وكلمة المرور المحددة، ويمكنه تسجيل الدخول واستعراض لوحة المتابعة الخاصة بشركاته فقط.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>هل يمكن للمالك أن يمتلك حصصاً في أكثر من شركة تابعة للمجموعة؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    نعم، يمكن ربط المالك بعدة شركات، وفي هذه الحالة يتيح له النظام التبديل السلس بين شركاته أو عرض تقرير تجميعي لحصصه وأرباحه في كافة تلك المنشآت.
                                </div>
                            </details>

                            <details class="carnet-faq-item">
                                <summary class="carnet-faq-summary">
                                    <span>ماذا يحدث عند حذف شريك أو تحديد تاريخ انتهاء شراكته؟</span>
                                    <span>▼</span>
                                </summary>
                                <div class="carnet-faq-body">
                                    عند إدخال تاريخ انتهاء الشراكة، يتم تعطيل حساب الدخول تلقائياً مع بقاء كافة المعاملات المالية السابقة والتقارير التاريخية محفوظة بأمان تام في قاعدة البيانات.
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
    function copyCarnetShareLink(sub = 'companies') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=organization&activeSub=' + sub + '#sec-video';
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
</script>
