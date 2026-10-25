{{-- 
    دليل قسم المحاسبة والمالية ومسيرات التحصيل والرواتب
    Finance & Accounting Management - Collection and Payroll Runs Guide
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

    /* Hero Box for Finance Hub */
    .carnet-hub-hero-box {
        background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
        border-radius: 20px;
        padding: 36px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.35);
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
        color: #047857;
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
        background: #047857;
        color: #ffffff;
        border-color: #047857;
        transform: translateX(3px);
    }
    .dark .carnet-sub-back-btn {
        background: #334155;
        color: #34d399;
        border-color: #475569;
    }
    .dark .carnet-sub-back-btn:hover {
        background: #047857;
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
        background: #047857;
        color: #ffffff;
        border-color: #047857;
        box-shadow: 0 4px 10px rgba(4, 120, 87, 0.25);
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
        border-color: #047857;
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.15);
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
    .dark .carnet-btn-action:hover {
        background: #334155;
    }

    /* Secondary Nav Tabs */
    .carnet-secondary-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        background: #f1f5f9;
        border-radius: 12px;
        margin-bottom: 24px;
        overflow-x: auto;
    }
    .dark .carnet-secondary-tabs {
        background: #1e293b;
    }
    .carnet-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .dark .carnet-tab-link {
        color: #94a3b8;
    }
    .carnet-tab-link:hover {
        background: #ffffff;
        color: #047857;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .dark .carnet-tab-link:hover {
        background: #0f172a;
        color: #34d399;
    }

    /* Two-Column Grid: Content Flow + Index */
    .carnet-content-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .carnet-content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Sticky Index Card */
    .carnet-index-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        position: sticky;
        top: 20px;
    }
    .dark .carnet-index-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-index-heading {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
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
    .dark .carnet-index-item a {
        color: #94a3b8;
    }
    .carnet-index-item a:hover {
        background: #f1f5f9;
        color: #047857;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a:hover {
        background: #0f172a;
        color: #34d399;
    }

    /* Main Flow Cards */
    .carnet-main-flow {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    .carnet-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .dark .carnet-card {
        background: #1e293b;
        border-color: #334155;
    }
    .carnet-card-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark .carnet-card-title-group {
        border-color: #334155;
    }
    .carnet-card-icon-badge {
        width: 32px;
        height: 32px;
        border-radius: 8px;
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
        color: #334155;
        margin: 0;
    }
    .dark .carnet-card-text {
        color: #cbd5e1;
    }

    /* Video Player Elements */
    .carnet-video-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .carnet-play-badge {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
    }
    .carnet-video-head h2 {
        font-size: 18px;
        font-weight: 800;
        margin: 0;
        color: #0f172a;
    }
    .dark .carnet-video-head h2 {
        color: #f8fafc;
    }
    .carnet-video-subtitle {
        font-size: 13.5px;
        color: #64748b;
        margin: 0 0 16px 0;
    }
    .dark .carnet-video-subtitle {
        color: #94a3b8;
    }
    .carnet-video-box {
        background: #000000;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        border: 1px solid #334155;
    }
    .carnet-video-element {
        width: 100%;
        max-height: 480px;
        display: block;
        background: #000000;
        outline: none;
    }
    .carnet-video-caption {
        padding: 10px 16px;
        background: #0f172a;
        color: #94a3b8;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #1e293b;
    }
    .carnet-video-caption code {
        color: #34d399;
        font-size: 11px;
    }

    /* Field Breakdown Grid */
    .carnet-field-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
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
        border-color: #047857;
        box-shadow: 0 4px 14px rgba(4, 120, 87, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-field-card {
        background: #0f172a;
        border-color: #334155;
    }
    .dark .carnet-field-card:hover {
        background: #1e293b;
        border-color: #059669;
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
        background: #d1fae5;
        color: #065f46;
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
        background: #047857;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .carnet-step-content h3 {
        font-size: 14.5px;
        font-weight: 800;
        margin: 0 0 4px 0;
        color: #0f172a;
    }
    .dark .carnet-step-content h3 {
        color: #f8fafc;
    }
    .carnet-step-content p {
        font-size: 13px;
        line-height: 1.6;
        color: #475569;
        margin: 0;
    }
    .dark .carnet-step-content p {
        color: #cbd5e1;
    }

    /* Tips & Rules */
    .carnet-tips-box {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .carnet-tip-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        background: #ecfdf5;
        border-radius: 10px;
        border-right: 4px solid #059669;
        font-size: 13.5px;
        line-height: 1.6;
        color: #064e3b;
    }
    .dark .carnet-tip-item {
        background: rgba(5, 150, 105, 0.12);
        color: #a7f3d0;
        border-color: #34d399;
    }

    /* FAQ Accordion */
    .carnet-faq-accordion {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .carnet-faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }
    .dark .carnet-faq-item {
        background: #0f172a;
        border-color: #334155;
    }
    .carnet-faq-summary {
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #0f172a;
        user-select: none;
    }
    .dark .carnet-faq-summary {
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
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .carnet-grand-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 22px;
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
        border-color: #047857;
    }
    .dark .carnet-grand-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark .carnet-grand-card:hover {
        border-color: #059669;
        box-shadow: 0 12px 24px -6px rgba(0,0,0,0.3);
    }
    .carnet-grand-card.featured {
        border-top: 4px solid #047857;
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
    .bg-icon-emerald {
        background: rgba(4, 120, 87, 0.12);
        color: #047857;
    }
    .bg-icon-sky {
        background: rgba(2, 132, 199, 0.12);
        color: #0284c7;
    }
    .bg-icon-amber {
        background: rgba(217, 119, 6, 0.12);
        color: #d97706;
    }
    .bg-icon-purple {
        background: rgba(124, 58, 237, 0.12);
        color: #7c3aed;
    }

    .carnet-card-badges {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .carnet-card-badge-pill {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .pill-video {
        background: #fee2e2;
        color: #dc2626;
    }
    .pill-info {
        background: #e0f2fe;
        color: #0369a1;
    }
    .pill-success {
        background: #d1fae5;
        color: #065f46;
    }
    .pill-warning {
        background: #fef3c7;
        color: #92400e;
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
        line-height: 1.6;
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
    }
    .carnet-btn-open-guide {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        background: linear-gradient(135deg, #047857 0%, #065f46 100%);
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
        box-shadow: 0 4px 10px rgba(4, 120, 87, 0.25);
    }
    .carnet-btn-open-guide:hover {
        background: linear-gradient(135deg, #065f46 0%, #047857 100%);
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

    @if(empty($activeSub) || !in_array($activeSub, ['payroll', 'payroll-records', 'collection-payroll', 'treasury', 'treasury-transactions', 'installments', 'installment-payments', 'rewards']))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم المحاسبة والمالية (Finance Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">💰 {{ __('documentation.finance_guide.tag') }}</span>
            <h2>{{ __('documentation.finance_guide.hub_heading') }}</h2>
            <p>{{ __('documentation.finance_guide.hub_note') }}</p>
        </div>

        <!-- شبكة البطاقات الكبرى لإدارة المحاسبة والمالية -->
        <div class="carnet-grand-grid">

            <!-- 1. مسيرات التحصيل والرواتب (Collection & Payroll Runs) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #059669;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-emerald">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0a60.07 60.07 0 0 1 15.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-18 0v12m18-12v12m0 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V18.75m18 0V6.75M6 12h12" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">الرواتب والتحصيل</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #047857;">{{ __('documentation.finance_guide.card_payroll_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.finance_guide.card_payroll_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.finance_guide.card_payroll_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('payroll')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/payroll-records" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. الخزنة وحركة الصندوق (Treasury) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #0284c7;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-sky">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A48.357 48.357 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.583V21" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-info">السيولة النقدية</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #0284c7;">{{ __('documentation.finance_guide.card_treasury_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.finance_guide.card_treasury_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.finance_guide.card_treasury_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('treasury')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/treasury-transactions" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. الأقساط والسلف (Installments & Advances) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #d97706;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-warning">السلف والتمويل</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #d97706;">{{ __('documentation.finance_guide.card_installments_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.finance_guide.card_installments_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.finance_guide.card_installments_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('installments')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/installments" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 4. المكافآت والحوافز (Rewards) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #7c3aed;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-purple">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-info">الحوافز</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #7c3aed;">{{ __('documentation.finance_guide.card_rewards_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.finance_guide.card_rewards_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.finance_guide.card_rewards_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('rewards')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/employee-rewards" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

        </div>

    @else
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية لشاشات المالية (الرواتب والخزنة)
             ========================================================================== -->

        <!-- شريط الرجوع والتنقل السريع بين أقسام المالية -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>➔</span>
                <span>العودة للواجهة الرئيسية للمحاسبة والمالية</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub('payroll')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['payroll', 'payroll-records', 'collection-payroll']) ? 'active' : '' }}">
                    <span>💵 مسيرات التحصيل والرواتب</span>
                </button>
                <button type="button" wire:click="setSub('treasury')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['treasury', 'treasury-transactions']) ? 'active' : '' }}">
                    <span>🏦 الخزنة وحركة الصندوق</span>
                </button>
                <button type="button" wire:click="setSub('installments')" class="carnet-sub-tab-pill {{ in_array($activeSub, ['installments', 'installment-payments']) ? 'active' : '' }}">
                    <span>💳 الأقساط والسلف</span>
                </button>
                <button type="button" wire:click="setSub('rewards')" class="carnet-sub-tab-pill {{ $activeSub === 'rewards' ? 'active' : '' }}">
                    <span>⭐ المكافآت والحوافز</span>
                </button>
            </div>
        </div>

        @if(in_array($activeSub, ['payroll', 'payroll-records', 'collection-payroll']))

        <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon bg-icon-emerald" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0a60.07 60.07 0 0 1 15.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-18 0v12m18-12v12m0 0a60.07 60.07 0 0 0-15.797 2.101c-.727.198-1.453-.342-1.453-1.096V18.75m18 0V6.75M6 12h12" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح مسيرات التحصيل والرواتب (Collection and Payroll Runs)</h1>
                    <p>دليل شامل لإعداد مسيرات الرواتب الشهرية وعمولات التحصيل، فحص وتجميع البدلات، استقطاع السلف والغياب، وإصدار قسائم الرواتب وملفات حماية الأجور (WPS).</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل مسيرات الرواتب..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('payroll')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/payroll-records" class="carnet-btn-action" style="background: #047857; color: #ffffff; border-color: #047857;">
                    <span>فتح شاشة المسيرات ⇦</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل</span>
            </a>
            <a href="#sec-concept-pay" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة المسيرات والتكامل المالي</span>
            </a>
            <a href="#sec-fields-pay" class="carnet-tab-link">
                <span>⚙️</span>
                <span>كيف تعمل الشاشة تفصيلياً (الحقول والعمليات)</span>
            </a>
            <a href="#sec-workflow-pay" class="carnet-tab-link">
                <span>📋</span>
                <span>خطوات إعداد واعتماد المسير</span>
            </a>
            <a href="#sec-tips-pay" class="carnet-tab-link">
                <span>⭐</span>
                <span>حماية الأجور (WPS) ومحددات النظام</span>
            </a>
            <a href="#sec-faq-pay" class="carnet-tab-link">
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
                    <span>فهرس دليل المسيرات</span>
                </div>
                <ul class="carnet-index-list">
                    <li class="carnet-index-item">
                        <a href="#sec-video">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-pay">
                            <span class="icon">💡</span>
                            <span>فكرة الشاشة والتكامل المالي</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-pay">
                            <span class="icon">⚙️</span>
                            <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-pay">
                            <span class="icon">📋</span>
                            <span>دورة إعداد واعتماد المسير</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-tips-pay">
                            <span class="icon">⭐</span>
                            <span>حماية الأجور (WPS) وقواعد الصرف</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-pay">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي (يسار) -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو مسيرات التحصيل والرواتب -->
                <section id="sec-video" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge">▶</span>
                        <h2>مشاهدة فيديو شرح مسيرات التحصيل والرواتب (Collection and Payroll Runs)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية إعداد مسيرات الرواتب الشهرية ومسيرات عمولات التحصيل، فحص المستحقات والاستقطاعات، واعتماد الصرف:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerPayroll">
                            <source src="{{ asset('videos/Collection and Payroll Runs.mp4') }}" type="video/mp4">
                            <source src="/videos/Collection and Payroll Runs.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Collection%20and%20Payroll%20Runs.mp4') }}" type="video/mp4">
                            <source src="/videos/Collection%20and%20Payroll%20Runs.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Collection and Payroll Runs.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Collection and Payroll Runs.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Collection%20and%20Payroll%20Runs.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Collection%20and%20Payroll%20Runs.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: دورة مسيرات الرواتب والتحصيل والربط المالي</span>
                            <span>الملف: <code>/videos/Collection and Payroll Runs.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف شاشة المسيرات -->
                <section id="sec-concept-pay" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(4, 120, 87, 0.15); color: #047857;">💡</span>
                        <h2 class="carnet-card-title">فكرة الشاشة والتكامل المحاسبي الشامل</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشة <strong>مسيرات التحصيل والرواتب (Collection and Payroll Runs)</strong> نقطة الالتقاء المركزية بين قسم الموارد البشرية والإدارة المالية. حيث تقوم الشاشة بجمع وتصفية كافة المؤثرات المالية الخاصة بكل موظف خلال الشهر الواحد، وتحويلها إلى قيود مالية ومسيرات صرف دقيقة وموثقة:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item">
                            <span>⚡</span>
                            <div>
                                <strong>تجميع المؤثرات التلقائي (Automatic Financial Sync):</strong>
                                بمجرد اختيار الموظف وشهر المسير، يستدعي محرك <code>PayrollCalculationService</code> تلقائياً: الراتب الأساسي من عقد الموظف، الساعات الإضافية من الحضور، المكافآت المعتمدة، أقساط السلف المستحقة للشهر، وخصومات الغياب والتأخير.
                            </div>
                        </div>
                        <div class="carnet-tip-item">
                            <span>💼</span>
                            <div>
                                <strong>الفصل بين مسير الرواتب ومسير التحصيل:</strong>
                                يدعم النظام نوعين من العمليات: <code>مسير رواتب (Payroll)</code> لصرف الأجور الشهرية النظامية، و<code>مسير تحصيل (Collection)</code> لمسؤولي التحصيل ومندوبي المبيعات لتوثيق العمولات ونسب التحصيل المحققة.
                            </div>
                        </div>
                        <div class="carnet-tip-item">
                            <span>🏦</span>
                            <div>
                                <strong>التوافق مع نظام حماية الأجور (WPS):</strong>
                                يضمن المسير توافق بنود الراتب مع متطلبات البنك المركزي ووزارة الموارد البشرية (منصة مدد)، مع توفير سجلات مدفوعات دقيقة تمنع المخالفات العمالية.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: كيف تعمل الشاشة تفصيلياً (الحقول والعمليات) -->
                <section id="sec-fields-pay" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(4, 120, 87, 0.15); color: #047857;">⚙️</span>
                        <h2 class="carnet-card-title">كيف تعمل شاشة المسيرات تفصيلياً (10 حقول وعمليات أساسية)</h2>
                    </div>
                    <p class="carnet-card-text" style="margin-bottom: 20px;">
                        فيما يلي تفكيك دقيق لكافة الحقول والمعادلات المحاسبية داخل نموذج مسيرات الرواتب والتحصيل:
                    </p>

                    <div class="carnet-field-grid">

                        <!-- الحقل 1: رقم السجل والمنشأة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">1. رقم السجل والشركة التابعة</span>
                                <span class="carnet-field-badge badge-calc">توليد آلي وعزل شركات</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يتم توليد <code>record_number</code> تسلسلي فريد تلقائياً بعد الحفظ. كما يتم اختيار المنشأة عبر مكون <code>OrgSelect::company</code> لضمان حصر موظفي كل شركة وتطبيق صلاحيات العزل التام.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 2: نوع العملية -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">2. نوع العملية (Operation Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي أساسي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    تحديد ما إذا كان السجل هو: <code>مسير رواتب (Payroll)</code> للأجر والبدلات الشهرية، أو <code>مسير تحصيل (Collection)</code> لصرف عمولات وحوافز التحصيل الميداني وفق المستهدفات المحققة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 3: الموظف وشهر المسير -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">3. الموظف وشهر المسير (Employee & Month)</span>
                                <span class="carnet-field-badge badge-sync">مزامنة تلقائية فورية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    اختيار الموظف وشهر الاستحقاق (مثال: أكتوبر 2026). بمجرد تحديدهما، يُشغّل النظام دالة <code>syncFinancials</code> لسحب كافة الحركات المرتبطة بالموظف للشهر المختار فوراً دون تدخل يدوي، مع التحقق من عدم تكرار المسير للموظف في نفس الشهر.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 4: الراتب الأساسي التعاقدي -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">4. الراتب الأساسي (Basic Salary)</span>
                                <span class="carnet-field-badge badge-req">إلزامي تعاقدي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يُسترجع تلقائياً من عقد الموظف المحفوظ في شاشة الموظفين. يمكن للمحاسب تعديل القيمة استثنائياً إن لزم الأمر، ويعاد احتساب الإجماليات والصافي في المتصفح لحظياً.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 5: بدل العمل الإضافي -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">5. العمل الإضافي (Overtime Amount)</span>
                                <span class="carnet-field-badge badge-calc">محسوب من الحضور</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يُجلب آلياً من سجلات شاشة الحضور والانصراف لشهر الاستحقاق؛ حيث تُضرب ساعات العمل الإضافي المعتمدة في معدل أجر الساعة الإضافية طبقاً لنظام العمل (الأجر الأساسي + 50%).
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 6: المكافآت والحوافز -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">6. المكافآت والحوافز (Rewards Amount)</span>
                                <span class="carnet-field-badge badge-integ">ربط بشاشة المكافآت</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يجمع النظام كافة المكافآت والحوافز التقديرية المعتمدة للموظف والمسجلة في شاشة المكافآت (Employee Rewards) للشهر الحالي ويضيفها إلى إجمالي المستحقات.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 7: استقطاع السلف والأقساط -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">7. استقطاع السلف والأقساط (Advance Deduction)</span>
                                <span class="carnet-field-badge badge-integ">ربط بشاشة الأقساط</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    فحص أقساط السلف المستحقة على الموظف في هذا الشهر وجلب القسط المستحق آلياً لخصمه من الراتب، مما يمنع تراكم الديون أو نسيان تحصيل الأقساط.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 8: استقطاع الغياب ودقائق التأخير -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">8. خصومات الغياب والتأخير (Absence Deduction)</span>
                                <span class="carnet-field-badge badge-calc">محسوب من الجزاءات</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    القيمة الإجمالية للخصومات الموقعة على الموظف نتيجة الغياب غير المبرر أو دقائق التأخير المتراكمة بعد فترة السماح وفق لائحة الجزاءات ونظام العمل.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 9: صافي الراتب المستحق -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">9. صافي الراتب (Net Amount)</span>
                                <span class="carnet-field-badge badge-calc">معادلة آلية دقيقة</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    القيمة النهائية المستحقة للصرف للموظف، وتُحسب وفق المعادلة: 
                                    <br><code>الصافي = (الأساسي + الإضافي + المكافآت) - (السلف + الغياب)</code>
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 10: المدفوع والمتبقي وحالة السداد -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">10. حالة السداد والمتبقي (Payment Status)</span>
                                <span class="carnet-field-badge badge-sync">تحديث تلقائي للموقف</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    حقل <code>paid_amount</code> يُسجل المبلغ المصروف فعلياً، ويحسب النظام <code>remaining_amount</code>، وتتغير <code>payment_status</code> آلياً بين (غير مسدد Unpaid، مسدد جزئياً Partially Paid، مسدد بالكامل Paid).
                                </p>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- القسم 4: دورة إعداد واعتماد المسير خطوة بخطوة -->
                <section id="sec-workflow-pay" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(4, 120, 87, 0.15); color: #047857;">📋</span>
                        <h2 class="carnet-card-title">دورة إعداد واعتماد مسيرات التحصيل والرواتب</h2>
                    </div>
                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-content">
                                <h3>اختيار الموظف والشهر وتوليد المؤثرات</h3>
                                <p>انقر على «إضافة مسير جديد»، اختر الشركة والموظف وشهر الاستحقاق؛ ليقوم النظام فوراً بسحب الأساسي والإضافي والسلف والغياب والمكافآت.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-content">
                                <h3>التدقيق المحاسبي ومراجعة الفروقات</h3>
                                <p>يقوم المحاسب بمطابقة بنود الإضافات والاستقطاعات مع التقارير التشغيلية وكشوف الحضور والتأكد من صحة الصافي المحتسب.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-content">
                                <h3>الاعتماد المالي وإصدار أمر الصرف</h3>
                                <p>اعتماد المسير وتوليد سند الصرف أو ملف التحويل البنكي وتوجيهه إلى حساب الخزينة أو البنك المختص لخصم السيولة.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-content">
                                <h3>سداد الراتب وتحديث السجلات وقسيمة الراتب</h3>
                                <p>إدخال المبلغ المسدد لتحديث حالة السداد إلى «مسدد بالكامل»، مع إتاحة طباعة قسيمة الراتب (Payslip) الرسمية للموظف.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: نظام حماية الأجور (WPS) ومحددات الصرف -->
                <section id="sec-tips-pay" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(4, 120, 87, 0.15); color: #047857;">⭐</span>
                        <h2 class="carnet-card-title">إرشادات حماية الأجور (WPS) والضوابط المحاسبية</h2>
                    </div>

                    <div class="carnet-tips-box">
                        <div class="carnet-tip-item">
                            <span>🏛️</span>
                            <div>
                                <strong>الالتزام بنظام حماية الأجور (WPS):</strong>
                                يجب صرف الرواتب الشهرية وإيداعها في الحسابات البنكية للموظفين قبل تاريخ 10 من كل شهر ميلادي تجنباً للمخالفات في منصة "مدد" ووزارة الموارد البشرية.
                            </div>
                        </div>
                        <div class="carnet-tip-item">
                            <span>⚖️</span>
                            <div>
                                <strong>الحد الأقصى للاستقطاعات (المادة 92 من نظام العمل):</strong>
                                لا يجوز أن تزيد نسبة المبالغ المحسومة من راتب الموظف (سواء كانت سلفاً أو غياباً) على نصف أجره المستحق (50%)، ما لم يثبت بموجب حكم قضائي أو إذن خطي صريح.
                            </div>
                        </div>
                        <div class="carnet-tip-item">
                            <span>🔒</span>
                            <div>
                                <strong>إغلاق فترات الحضور قبل المسير:</strong>
                                تأكد من قيام مشرفي الفروع بترحيل واعتماد حركات الحضور والغياب والإجازات للشهر المنقضي بالكامل قبل إنشاء المسير، لضمان دقة الاستقطاعات والإضافي بنسبة 100%.
                            </div>
                        </div>
                        <div class="carnet-tip-item">
                            <span>📑</span>
                            <div>
                                <strong>أرشفة سندات الصرف والتحويل:</strong>
                                اربط دائماً رقم التحويل البنكي أو إشعار الإيداع في حقل الملاحظات لسهولة الرجوع إليه أثناء أعمال المراجعة والتدقيق المحاسبي السنوي.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: الأسئلة الشائعة حول مسيرات الرواتب -->
                <section id="sec-faq-pay" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(4, 120, 87, 0.15); color: #047857;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول مسيرات التحصيل والرواتب</h2>
                    </div>

                    <div class="carnet-faq-accordion">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يسمح النظام بإنشاء أكثر من مسير راتب لنفس الموظف في نفس الشهر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                لا، يفرض النظام قاعدة تحقق فريدة <code>uniquePayrollRule</code> تمنع تكرار مسير الراتب للموظف في نفس الشهر لتفادي ازدواجية الصرف، ولكن يمكن إنشاء مسير تحصيل إضافي مستقل للعمولات.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>ماذا يحدث إذا تم تسجيل سلفة جديدة للموظف بعد إنشاء المسير؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                إذا كان المسير في حالة «مسودة» أو «غير مسدد»، يمكنك تعديل السجل أو إعادة اختيار الموظف لتحديث المؤثرات، أما إذا تم اعتماد المسير وصرفه، فسيتم ترحيل قسط السلفة تلقائياً إلى مسير الشهر التالي.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>كيف يتم احتساب ساعات العمل الإضافي في المسير؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يقوم النظام بجمع دقائق وساعات العمل الإضافي المعتمدة للموظف من سجلات الحضور والانصراف، ويحسب القيمة المالية وفق المعادلة: <code>(الراتب الأساسي ÷ ساعات الشهر الأساسية) × ساعات الإضافي × 1.5</code>.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن صرف جزء من الراتب وتأجيل المتبقي؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، يمكنك إدخال المبلغ المصروف في حقل <code>paid_amount</code>، وسيقوم النظام تلقائياً باحتساب <code>remaining_amount</code> وتحويل حالة السجل إلى «مسدد جزئياً»، لحين صرف الدفعة المتبقية.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        </div>
        @elseif(in_array($activeSub, ['treasury', 'treasury-transactions']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: الخزنة وحركة الصندوق (Treasury and Cash Transactions)
             ========================================================================== -->

        <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon bg-icon-sky" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A48.357 48.357 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.583V21" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح الخزنة وحركة الصندوق (Treasury and Cash Transactions)</h1>
                    <p>دليل شامل لإدارة حركة السيولة النقدية، تسجيل سندات القبض والصرف، بنود الدفع والتحصيل، رقابة أرصدة الصناديق، ومطابقة العهد والإيداعات البنكية.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل الخزنة والصندوق..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('treasury')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/treasury-transactions" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                    <span>فتح شاشة الخزنة ⇦</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل</span>
            </a>
            <a href="#sec-concept-treasury" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة الخزينة والسيولة</span>
            </a>
            <a href="#sec-fields-treasury" class="carnet-tab-link">
                <span>⚙️</span>
                <span>كيف تعمل الشاشة تفصيلياً (الحقول والعمليات)</span>
            </a>
            <a href="#sec-workflow-treasury" class="carnet-tab-link">
                <span>📋</span>
                <span>خطوات تسجيل حركة الخزينة</span>
            </a>
            <a href="#sec-tips-treasury" class="carnet-tab-link">
                <span>⭐</span>
                <span>الرقابة المالية والجرد اليومي</span>
            </a>
            <a href="#sec-faq-treasury" class="carnet-tab-link">
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
                    <span>فهرس دليل الخزنة</span>
                </div>
                <ul class="carnet-index-list">
                    <li class="carnet-index-item">
                        <a href="#sec-video">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-treasury">
                            <span class="icon">💡</span>
                            <span>فكرة الخزينة والسيولة النقدية</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-treasury">
                            <span class="icon">⚙️</span>
                            <span>كيف تعمل الشاشة تفصيلياً (الحقول)</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-treasury">
                            <span class="icon">📋</span>
                            <span>خطوات تسجيل السندات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-tips-treasury">
                            <span class="icon">⭐</span>
                            <span>إجراءات الرقابة والجرد اليومي</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-treasury">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي (يسار) -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو الخزنة وحركة الصندوق -->
                <section id="sec-video" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح الخزنة وحركة الصندوق (Treasury and Cash Transactions)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية تسجيل سندات القبض والصرف، تحديد بنود الحسابات، ربط الموظفين والمستفيدين، واحتساب رصيد الخزينة المتبقي:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerTreasury">
                            <source src="{{ asset('videos/Treasury and Cash Transactions.mp4') }}" type="video/mp4">
                            <source src="/videos/Treasury and Cash Transactions.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Treasury%20and%20Cash%20Transactions.mp4') }}" type="video/mp4">
                            <source src="/videos/Treasury%20and%20Cash%20Transactions.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Treasury and Cash Transactions.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Treasury and Cash Transactions.mp4" type="video/mp4">
                            <source src="{{ asset('vidoes/Treasury%20and%20Cash%20Transactions.mp4') }}" type="video/mp4">
                            <source src="/vidoes/Treasury%20and%20Cash%20Transactions.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: سندات القبض والصرف وحركة الصندوق</span>
                            <span>الملف: <code>/videos/Treasury and Cash Transactions.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف شاشة الخزنة وحركة الصندوق -->
                <section id="sec-concept-treasury" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">💡</span>
                        <h2 class="carnet-card-title">فكرة الشاشة والرقابة على التدفقات النقدية</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُعد شاشة <strong>الخزنة وحركة الصندوق (Treasury and Cash Transactions)</strong> صمام الأمان للسيولة النقدية والمقبوضات اليومية في كل منشأة؛ حيث تُسجل كل ليرة وريال يدخل أو يخرج من خزينة الشركة بدقة متناهية وتربطه بسند رسمي:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1;">
                            <span>📥</span>
                            <div>
                                <strong>سندات القبض (Incoming / Receipts):</strong>
                                توثيق إيرادات المبيعات، تحصيلات أقساط وسلف الموظفين، العمولات، والإيداعات النقدية؛ مما يزيد من رصيد الخزينة تلقائياً.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #e11d48; background: #fff1f2; color: #be123c;">
                            <span>📤</span>
                            <div>
                                <strong>سندات الصرف (Outgoing / Payments):</strong>
                                توثيق سداد المصاريف التشغيلية، فواتير المشتريات، تسليم العهد النقدية، وصرف الرواتب النقدية؛ مما يخصم من رصيد الخزينة فوراً.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #059669; background: #ecfdf5; color: #065f46;">
                            <span>⚖️</span>
                            <div>
                                <strong>الرصيد الدفتري واللحظي (Balance After):</strong>
                                يحسب النظام الرصيد المتبقي للخزينة بعد كل حركة، مما يتيح للإدارة المالية والمحاسبين إجراء جرد مفاجئ ومطابقة النقدية الفعلية مع السجلات في أي لحظة.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: كيف تعمل الشاشة تفصيلياً (الحقول والعمليات) -->
                <section id="sec-fields-treasury" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">⚙️</span>
                        <h2 class="carnet-card-title">كيف تعمل شاشة الخزنة تفصيلياً (10 حقول وعمليات أساسية)</h2>
                    </div>
                    <p class="carnet-card-text" style="margin-bottom: 20px;">
                        فيما يلي تفكيك دقيق لكافة الحقول المحاسبية داخل نموذج حركة الخزينة من واقع كود النظام:
                    </p>

                    <div class="carnet-field-grid">

                        <!-- الحقل 1: رقم المعاملة ورقم السند -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">1. أرقام الحركات والسندات</span>
                                <span class="carnet-field-badge badge-calc">توليد آلي تسلسلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يقوم النظام بتوليد <code>transaction_number</code> و <code>voucher_number</code> تسلسلي فريد وغير قابل للتكرار لكل سند قبض أو صرف، لضمان سهولة التتبع والتوثيق المالي والأرشفة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 2: تاريخ الحركة والمنشأة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">2. تاريخ المعاملة والشركة</span>
                                <span class="carnet-field-badge badge-req">إلزامي أساسي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    تحديد <code>transaction_date</code> الذي يؤثر على التقارير المالية الدورية، واختيار المنشأة عبر <code>OrgSelect::company</code> لضمان فصل حسابات وخزن كل شركة بشكل مستقل.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 3: نوع الحركة (وارد / صادر) -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">3. نوع الحركة (Transaction Type)</span>
                                <span class="carnet-field-badge badge-req">تحديد الأثر المالي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    تحديد ما إذا كانت الحركة: <code>وارد (Incoming)</code> كسند قبض يرفع رصيد الخزينة، أو <code>صادر (Outgoing)</code> كسند صرف يخفض رصيد الخزينة بموجب مبررات الصرف.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 4: بند الدفع والتحصيل -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">4. بند الدفع والتحصيل (Item)</span>
                                <span class="carnet-field-badge badge-req">تصنيف محاسبي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    ربط الحركة ببند محدد من شاشة <code>payment_collection_item_id</code>؛ حيث يظهر التصنيف الرئيسي والفرعي (مثل: إيرادات عقود، سلف موظفين، مصاريف إدارية، صيانة ونظافة) لضبط شجرة الحسابات.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 5: المبلغ المالي -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">5. المبلغ المالي (Amount)</span>
                                <span class="carnet-field-badge badge-req">بالريال السعودي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    قيمة الحركة النقدية الفعلية المسددة أو المقبوضة؛ يتم التحقق برمجياً من عدم إدخال قيم سالبة، وفي سندات الصرف يتم التحقق من توفر رصيد كافٍ بالخزينة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 6: تصنيف المستفيد / الدافع -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">6. تصنيف المستفيد (Beneficiary Type)</span>
                                <span class="carnet-field-badge badge-sync">تفاعل ذكي لحظي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    تحديد هوية الطرف الآخر: <code>موظف سعودي</code>، <code>موظف مقيم</code>، <code>عميل</code>، <code>مورد</code>، <code>شريك أو مالك</code>، أو <code>جهة أخرى</code> لتكييف الحقول المرتبطة به.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 7: اختيار الموظف المستفيد -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">7. ربط الموظف (Employee Select)</span>
                                <span class="carnet-field-badge badge-integ">ربط بالموارد البشرية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    في حال كان المستفيد موظفاً، يُفعل النظام حقل اختيار الموظف <code>employee_id</code> المربوط بالمنشأة عبر <code>TreasuryBeneficiaryService</code>، وينقل اسمه تلقائياً لحقل المستلم.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 8: اسم المستلم أو الدافع الفعلي -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">8. المستلم أو الدافع (Recipient / Payer)</span>
                                <span class="carnet-field-badge badge-req">توثيق الطرف الفعلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    حقل <code>recipient_or_payer</code> يوثق الاسم الصريح للشخص أو المؤسسة التي سلمت أو استلمت المبلغ المالي لإثبات المسؤولية القانونية والمحاسبية.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 9: المسؤول المنفذ للحركة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">9. المنفذ (Executed By)</span>
                                <span class="carnet-field-badge badge-calc">أمين الصندوق المسجل</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يتم تعيين الموظف أو المستخدم الحالي تلقائياً كمنفذ للحركة <code>executed_by</code> لحفظ سجل المساءلة وتتبع من قام بقبض أو صرف النقدية من الخزينة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 10: الرصيد بعد الحركة والبيان -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">10. الرصيد بعد الحركة والوصف</span>
                                <span class="carnet-field-badge badge-calc">رصيد دفتري تراكمي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يظهر حقل <code>balance_after</code> بعد حفظ السجل ليوضح الرصيد التراكمي المتبقي بالخزينة، مع حقل نصي تفصيلي <code>description</code> لشرح مبررات القيد والمرفقات.
                                </p>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- القسم 4: دورة تسجيل حركة الخزينة خطوة بخطوة -->
                <section id="sec-workflow-treasury" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">📋</span>
                        <h2 class="carnet-card-title">خطوات تسجيل واعتماد حركة الخزينة والصندوق</h2>
                    </div>
                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #0284c7;">1</span>
                            <div class="carnet-step-content">
                                <h3>تحديد نوع السند والمنشأة والفرع</h3>
                                <p>اختر ما إذا كنت بصدد تسجيل «سند قبض وارد» لإيداع نقدية، أو «سند صرف صادر» لإخراج نقدية، وحدد المنشأة التابعة.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #0284c7;">2</span>
                            <div class="carnet-step-content">
                                <h3>تحديد بند الحساب وهوية المستفيد</h3>
                                <p>اختر بند التحصيل أو المصروف المناسب من القائمة، وحدد صفة المستفيد (موظف، عميل، مورد) لربطه بسجلاته فوراً.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #0284c7;">3</span>
                            <div class="carnet-step-content">
                                <h3>إدخال المبلغ المالي والبيان المحاسبي</h3>
                                <p>أدخل قيمة المبلغ بالريال السعودي، ودون تفاصيل وفواتير السند في حقل الوصف لضمان سلامة المراجعة والتدقيق.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #0284c7;">4</span>
                            <div class="carnet-step-content">
                                <h3>الحفظ وتحديث الرصيد وطباعة السند</h3>
                                <p>بمجرد الحفظ، يُحدث النظام رصيد الصندوق لحظياً ويصدر رقم سند رسمي قابل للطباعة والتوقيع والتسليم للمستفيد.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: الرقابة المالية وإجراءات الجرد -->
                <section id="sec-tips-treasury" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">⭐</span>
                        <h2 class="carnet-card-title">إرشادات الرقابة النقدية والجرد الدوري للخزن</h2>
                    </div>

                    <div class="carnet-tips-box">
                        <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1;">
                            <span>🔍</span>
                            <div>
                                <strong>الجرد اليومي لصندوق النقدية:</strong>
                                التزم بإجراء مطابقة يومية في نهاية كل وردية عمل بين إجمالي النقدية الفعلية الموجودة في الدرج والرصيد الظاهر في حقل <code>balance_after</code>.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1;">
                            <span>🧾</span>
                            <div>
                                <strong>تسوية العهد النقدية المؤقتة:</strong>
                                عند صرف عهدة نقدية لموظف، يجب تسجيلها كسند صرف تحت بند «عهد موظفين»، ولا تُقفل العهدة إلا بتقديم الفواتير الضريبية الرسمية واسترداد المتبقي.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1;">
                            <span>🏦</span>
                            <div>
                                <strong>التوريد البنكي للسيولة الفائضة:</strong>
                                تجنب الاحتفاظ بمبالغ نقدية كبيرة في خزينة الفرع تفوق الحد التأميني المسموح؛ وقم بتوريد الفائض لحساب الشركة البنكي مع تسجيل سند صرف إيداع بنكي.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #0284c7; background: #f0f9ff; color: #0369a1;">
                            <span>✍️</span>
                            <div>
                                <strong>التوقيع الحي على السندات:</strong>
                                احرص على توقيع المستلم على السند الورقي المطبوع فور تسليم النقدية والاحتفاظ به في ملف سندات الصرف للمراجعة السنوية.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: الأسئلة الشائعة حول الخزينة -->
                <section id="sec-faq-treasury" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول الخزنة وحركة الصندوق</h2>
                    </div>

                    <div class="carnet-faq-accordion">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يسمح النظام بصرف مبلغ يتجاوز رصيد الخزينة الحالي؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                النظام يراقب الرصيد النقدي المتاح لحظياً لمنع ظهور أرصدة سالبة (السحب على المكشوف) في الخزينة؛ وفي حال عدم توفر رصيد كافٍ، يتطلب الأمر تغذية الخزينة أولاً بسند قبض أو تحويل نقدي.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>كيف يتم تسجيل تحويل نقدية بين خزينتين أو فرعين؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يتم ذلك بقيدين متقابلين: «سند صرف صادر» من الخزينة المحولة، و«سند قبض وارد» في الخزينة المستلمة تحت بند «تحويلات داخلية نقدية»، مع توثيق أرقام السندين لضمان توازن الحسابات.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن تعديل أو حذف سند حركة خزينة بعد اعتماده؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                حفاظاً على النزاهة المحاسبية، تقتصر صلاحية تعديل السندات المالية على المشرفين الماليين (Super Admins) فقط؛ ويفضل محاسبياً تسجيل سند عكسي بدلاً من حذف السند الأصلي لتوثيق تاريخ الحركات.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>أين تنعكس حركات الخزينة في القوائم والتقارير المالية؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                تنعكس حركات الخزينة مباشرة في كشف حركة الصندوق اليومي، ميزان المراجعة، حساب الأستاذ المساعد للخزينة، وقائمة التدفقات النقدية الدورية.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        @elseif(in_array($activeSub, ['installments', 'installment-payments']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: الأقساط والسلف (Installments and Advances)
             ========================================================================== -->

        <!-- 1. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon bg-icon-amber" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح الأقساط والسلف (Installments and Advances)</h1>
                    <p>دليل شامل لإدارة أقساط وسلف الموظفين، جدولة السداد، سندات التسديد المباشرة، والاقتطاع التلقائي من الراتب الشهري.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل الأقساط والسلف..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('installments')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/installments" class="carnet-btn-action" style="background: #d97706; color: #ffffff; border-color: #d97706;">
                    <span>فتح شاشة الأقساط ⇦</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-inst" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل</span>
            </a>
            <a href="#sec-concept-inst" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة الأقساط والسلف والتكامل المالي</span>
            </a>
            <a href="#sec-fields-inst" class="carnet-tab-link">
                <span>⚙️</span>
                <span>كيف تعمل الشاشة تفصيلياً (الحقول والعمليات)</span>
            </a>
            <a href="#sec-payments-inst" class="carnet-tab-link">
                <span>💸</span>
                <span>سداد الأقساط (Installment Payments)</span>
            </a>
            <a href="#sec-workflow-inst" class="carnet-tab-link">
                <span>📋</span>
                <span>خطوات إنشاء القسط وجدولة السداد</span>
            </a>
            <a href="#sec-tips-inst" class="carnet-tab-link">
                <span>⭐</span>
                <span>إرشادات وضوابط نظامية</span>
            </a>
            <a href="#sec-faq-inst" class="carnet-tab-link">
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
                    <span>فهرس دليل الأقساط والسلف</span>
                </div>
                <ul class="carnet-index-list">
                    <li class="carnet-index-item">
                        <a href="#sec-video-inst">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-inst">
                            <span class="icon">💡</span>
                            <span>فكرة الشاشة والتكامل المالي</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-inst">
                            <span class="icon">⚙️</span>
                            <span>حقول نموذج القسط / السلفة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-payments-inst">
                            <span class="icon">💸</span>
                            <span>سداد الأقساط (Payments)</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-inst">
                            <span class="icon">📋</span>
                            <span>دورة إنشاء القسط والسداد</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-tips-inst">
                            <span class="icon">⭐</span>
                            <span>ضوابط نظامية وإرشادات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-inst">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي (يسار) -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو الأقساط والسلف -->
                <section id="sec-video-inst" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح الأقساط والسلف (Installments and Advances)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية إنشاء أقساط وسلف الموظفين، تعيين خطط السداد الشهرية، تسجيل دفعات التسديد، والاقتطاع التلقائي من مسيرات الرواتب:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerInstallments">
                            <source src="{{ asset('videos/Installments and Advances.mp4') }}" type="video/mp4">
                            <source src="/videos/Installments and Advances.mp4" type="video/mp4">
                            <source src="{{ asset('videos/Installments%20and%20Advances.mp4') }}" type="video/mp4">
                            <source src="/videos/Installments%20and%20Advances.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: دورة الأقساط والسلف وسندات السداد والربط بالرواتب</span>
                            <span>الملف: <code>/videos/Installments and Advances.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف شاشة الأقساط والسلف -->
                <section id="sec-concept-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">💡</span>
                        <h2 class="carnet-card-title">فكرة الشاشة والتكامل المالي مع الرواتب</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشة <strong>الأقساط والسلف (Installments and Advances)</strong> المركز الرئيسي لإدارة كافة أشكال التمويل والديون المستحقة على الموظفين أو العملاء الخارجيين. تدعم الشاشة نوعين أساسيين من العمليات:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>💳</span>
                            <div>
                                <strong>الأقساط (Installments):</strong>
                                تُستخدم لتوثيق أي مبلغ مالي يُصرف للموظف أو طرف خارجي كدين مقسّط على فترة زمنية محددة (شهرية)، مثل: قروض شخصية، شراء أجهزة، تمويل سيارات، أو أي التزام مالي يُسدد على أقساط منتظمة.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>💵</span>
                            <div>
                                <strong>السلف (Advances):</strong>
                                تُستخدم لتوثيق المبالغ النقدية المصروفة مقدماً للموظف على حساب راتبه المستقبلي. يقوم النظام تلقائياً بخصم قسط السلفة شهرياً من مسير الراتب حتى السداد الكامل.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>🔗</span>
                            <div>
                                <strong>التكامل مع مسيرات الرواتب:</strong>
                                عند إعداد مسير راتب شهري، يقوم محرك <code>PayrollCalculationService</code> تلقائياً بفحص الأقساط والسلف المستحقة على الموظف للشهر الحالي ويخصمها من صافي الراتب، مما يمنع نسيان التحصيل ويضمن السداد المنتظم.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>👥</span>
                            <div>
                                <strong>دعم المستفيدين الداخليين والخارجيين:</strong>
                                يمكن تسجيل الأقساط لموظفي الشركة (داخلي) أو لأطراف خارجية مثل العملاء أو الموردين (خارجي)، مع توثيق بيانات الهوية ومعلومات التواصل والكفيل/الضامن.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: كيف تعمل شاشة الأقساط تفصيلياً (الحقول والعمليات) -->
                <section id="sec-fields-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">⚙️</span>
                        <h2 class="carnet-card-title">كيف تعمل شاشة الأقساط والسلف تفصيلياً (10 حقول وعمليات أساسية)</h2>
                    </div>
                    <p class="carnet-card-text" style="margin-bottom: 20px;">
                        فيما يلي تفكيك دقيق لكافة الحقول والأقسام داخل نموذج إنشاء قسط أو سلفة جديدة:
                    </p>

                    <div class="carnet-field-grid">

                        <!-- الحقل 1: نوع العملية -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">1. نوع العملية (Transaction Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي أساسي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    تحديد ما إذا كان السجل <code>قسط (Installment)</code> مقسّط على أشهر، أو <code>سلفة (Advance)</code> مبلغ نقدي مسبق يُخصم من الراتب. هذا الاختيار يحدد نوع القسط المتاح ويؤثر على آلية الاستقطاع من مسير الرواتب.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 2: نوع المستفيد -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">2. نوع المستفيد (Beneficiary Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي أساسي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>داخلي (Internal)</code>: موظف تابع للمنشأة — يظهر حقل اختيار الشركة والموظف.<br>
                                    <code>خارجي (External)</code>: عميل أو طرف خارجي — تظهر حقول إدخال الاسم ورقم الهوية والهاتف والعنوان والبريد الإلكتروني يدوياً.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 3: تاريخ التسجيل والحالة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">3. تاريخ التسجيل والحالة (Date & Status)</span>
                                <span class="carnet-field-badge badge-sync">تحديث تلقائي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>installment_date</code>: تاريخ تسجيل القسط/السلفة (يُعبأ بتاريخ اليوم افتراضياً).<br>
                                    <code>status</code>: حالة القسط — مفتوح (Open)، جاري السداد (In Progress)، مكتمل (Completed)، ملغي (Cancelled). تتحدث الحالة تلقائياً مع كل عملية سداد.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 4: الشركة والموظف -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">4. الشركة والموظف (Company & Employee)</span>
                                <span class="carnet-field-badge badge-sync">مزامنة تلقائية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    عند اختيار المستفيد الداخلي، يتم اختيار الشركة عبر مكون <code>OrgSelect::company</code> مع حصر الموظفين التابعين لها. عند اختيار الموظف، يتم مزامنة الشركة تلقائياً من بيانات عقد الموظف.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 5: نوع القسط/السلفة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">5. نوع القسط / السلفة (Installment Type)</span>
                                <span class="carnet-field-badge badge-integ">قابل للإنشاء المباشر</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يتم تصنيف الأقساط تحت أنواع مخصصة (ديون، سلف، أخرى) محفوظة في <code>InstallmentTypes</code>. يمكن إنشاء نوع جديد مباشرة من نافذة منبثقة أثناء التسجيل دون مغادرة الشاشة، مع تحديد التصنيف (ديون Debts / سلف Advances / أخرى Others).
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 6: المبلغ الإجمالي والدفعة المقدمة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">6. المبلغ الإجمالي والدفعة المقدمة (Total & Down Payment)</span>
                                <span class="carnet-field-badge badge-calc">إعادة حساب تلقائي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>total_amount</code>: المبلغ الكلي للقسط أو السلفة بالريال السعودي (SAR).<br>
                                    <code>down_payment</code>: الدفعة المقدمة (إن وُجدت) التي تُخصم مباشرة من الإجمالي.<br>
                                    عند تغيير أي من القيمتين، يعيد النظام حساب القسط الشهري والمتبقي فوراً عبر دالة <code>recalculateLoan</code>.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 7: عدد الأشهر والقسط الشهري -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">7. عدد الأشهر والقسط الشهري (Months & Monthly Amount)</span>
                                <span class="carnet-field-badge badge-calc">معادلة آلية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>months_count</code>: عدد أشهر التقسيط (الحد الأدنى شهر واحد).<br>
                                    <code>monthly_amount</code>: القسط الشهري المحسوب تلقائياً:<br>
                                    <code>القسط الشهري = (المبلغ الإجمالي - الدفعة المقدمة) ÷ عدد الأشهر</code><br>
                                    يتم إعادة الحساب لحظياً عند تعديل أي من المدخلات.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 8: المسدد والمتبقي -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">8. المبلغ المسدد والمتبقي (Paid & Remaining)</span>
                                <span class="carnet-field-badge badge-calc">تحديث فوري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>paid_amount</code>: إجمالي المبالغ المسددة حتى الآن.<br>
                                    <code>remaining_amount</code>: المتبقي غير المسدد، يُحسب آلياً:<br>
                                    <code>المتبقي = المبلغ الإجمالي - الدفعة المقدمة - المسدد</code><br>
                                    الحقل معطّل (للقراءة فقط) لمنع التعديل اليدوي وضمان الدقة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 9: تواريخ الاستحقاق والمنفذ -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">9. تواريخ الاستحقاق والمنفذ (Dates & Executor)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>start_due_date</code>: تاريخ بداية استحقاق أول قسط.<br>
                                    <code>final_due_date</code>: الموعد النهائي للسداد الكامل.<br>
                                    <code>executed_by</code>: الموظف المسؤول عن تنفيذ/توثيق العملية (يُعبأ تلقائياً بالمستخدم الحالي، ولا يمكن تغييره إلا بواسطة المشرف الأعلى).
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 10: الكفيل/الضامن والإشعارات -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">10. الكفيل والإشعارات (Guarantor & Notifications)</span>
                                <span class="carnet-field-badge badge-doc">توثيق قانوني</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>guarantor_id</code>: اختيار كفيل/ضامن من قائمة الضامنين أو إنشاء ضامن جديد مباشرة (اسم، هوية، هاتف، وظيفة، عنوان).<br>
                                    <code>notify_employee_id</code>: تعيين موظف يتلقى إشعارات تذكير بمواعيد السداد.<br>
                                    <code>notification_notes</code>: ملاحظات إضافية تُرفق مع الإشعار.
                                </p>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- القسم 4: سداد الأقساط (Installment Payments) -->
                <section id="sec-payments-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">💸</span>
                        <h2 class="carnet-card-title">شاشة سداد الأقساط (Installment Payments) — سندات التسديد</h2>
                    </div>
                    <p class="carnet-card-text" style="margin-bottom: 16px;">
                        شاشة منفصلة لتسجيل كل عملية سداد فردية مقابل قسط أو سلفة قائمة. كل سند سداد يُحدّث المبلغ المسدد والمتبقي في القسط الأصلي تلقائياً:
                    </p>

                    <div class="carnet-field-grid">

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع السداد (Payment Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد ما إذا كان السند لسداد <code>قسط (Installment)</code> أو <code>سلفة (Advance)</code>، ويؤثر على قائمة الأقساط المتاحة للاختيار.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">اختيار القسط المستهدف (Installment)</span>
                                <span class="carnet-field-badge badge-sync">فلترة ذكية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>يعرض فقط الأقساط النشطة ذات المتبقي الأكبر من صفر. عند اختيار القسط، يتم تعبئة المبلغ الشهري المستحق تلقائياً، ومزامنة بيانات الشركة والموظف.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">مبلغ السداد (Amount)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>المبلغ المراد سداده في هذه الدفعة بالريال السعودي. يُعبأ تلقائياً بقيمة القسط الشهري، ويمكن تعديله لسداد مبلغ أكبر أو أقل حسب الاتفاق.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">مصدر السداد (Payment Source)</span>
                                <span class="carnet-field-badge badge-integ">ربط بالخزينة والرواتب</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>نقدي (Cash)</code>: يتم ربط السند بحركة خزينة واردة.<br>
                                    <code>خصم من الراتب (Payroll Deduction)</code>: يتم ربط السند بمسير راتب محدد للخصم التلقائي.<br>
                                    <code>تحويل بنكي (Bank Transfer)</code>: توثيق رقم التحويل.
                                </p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">حالة الاعتماد (Approval Status)</span>
                                <span class="carnet-field-badge badge-doc">صلاحيات محددة</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>يبدأ السند بحالة <code>غير معتمد (Unapproved)</code>، ولا يؤثر على المتبقي حتى اعتماده من صاحب الصلاحية. يمنع النظام التعديل على السندات المعتمدة إلا للمشرفين.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">رقم السند والتاريخ (Voucher & Date)</span>
                                <span class="carnet-field-badge badge-calc">توليد آلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>يتم توليد <code>voucher_number</code> تسلسلي فريد تلقائياً بعد الحفظ لتوثيق السند. تاريخ السداد <code>payment_date</code> يُعبأ بتاريخ اليوم افتراضياً.</p>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- القسم 5: دورة إنشاء القسط وجدولة السداد -->
                <section id="sec-workflow-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">📋</span>
                        <h2 class="carnet-card-title">دورة إنشاء قسط/سلفة وجدولة السداد خطوة بخطوة</h2>
                    </div>
                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #d97706;">1</span>
                            <div class="carnet-step-content">
                                <h3>تحديد نوع العملية والمستفيد</h3>
                                <p>اختر نوع العملية (قسط أو سلفة) ونوع المستفيد (موظف داخلي أو طرف خارجي). في حالة المستفيد الداخلي، اختر الشركة والموظف؛ وفي حالة الخارجي، أدخل بيانات الهوية والتواصل.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #d97706;">2</span>
                            <div class="carnet-step-content">
                                <h3>إدخال البيانات المالية وخطة التقسيط</h3>
                                <p>أدخل المبلغ الإجمالي، الدفعة المقدمة (إن وُجدت)، وعدد أشهر السداد. سيحسب النظام القسط الشهري والمتبقي تلقائياً. حدد تاريخ بداية الاستحقاق والتاريخ النهائي.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #d97706;">3</span>
                            <div class="carnet-step-content">
                                <h3>إضافة الضامن وتفعيل الإشعارات (اختياري)</h3>
                                <p>يمكنك إضافة كفيل/ضامن لضمان السداد، وتعيين موظف مسؤول يتلقى إشعارات تذكيرية قبل مواعيد استحقاق الأقساط.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #d97706;">4</span>
                            <div class="carnet-step-content">
                                <h3>حفظ القسط وبدء دورة السداد</h3>
                                <p>بعد الحفظ يصبح القسط في حالة «مفتوح» وجاهزاً لاستقبال دفعات السداد. يمكن تسجيل الدفعات يدوياً من شاشة «سداد الأقساط» أو تلقائياً عبر الخصم من مسير الراتب الشهري.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #d97706;">5</span>
                            <div class="carnet-step-content">
                                <h3>الاكتمال التلقائي عند سداد كامل المبلغ</h3>
                                <p>عند وصول المبلغ المسدد إلى إجمالي المطلوب (المتبقي = صفر)، يتحول القسط تلقائياً إلى حالة «مكتمل (Completed)» ويتوقف الخصم من الرواتب المستقبلية.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: إرشادات وضوابط نظامية -->
                <section id="sec-tips-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">⭐</span>
                        <h2 class="carnet-card-title">إرشادات وضوابط نظامية لإدارة الأقساط والسلف</h2>
                    </div>

                    <div class="carnet-tips-box">
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>⚖️</span>
                            <div>
                                <strong>الحد الأقصى للاستقطاع (المادة 92 من نظام العمل):</strong>
                                لا يجوز أن تتجاوز نسبة الاستقطاعات الإجمالية من راتب الموظف (سلف + أقساط + جزاءات) نسبة 50% من إجمالي الأجر المستحق، ما لم يوجد إذن قضائي أو خطي صريح من الموظف.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>📋</span>
                            <div>
                                <strong>توثيق إقرار الموظف:</strong>
                                يُنصح بشدة بأخذ إقرار خطي من الموظف عند صرف السلفة أو القسط يتضمن: المبلغ، عدد الأقساط، تاريخ بداية الخصم، وموافقته على الخصم من الراتب. احفظ الإقرار في حقل الملاحظات.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>🔒</span>
                            <div>
                                <strong>عدم تعديل الأقساط المكتملة:</strong>
                                بعد إتمام سداد القسط بالكامل وتحوله لحالة «مكتمل»، لا يُسمح بتعديل بياناته إلا بواسطة المشرف الأعلى (Super Admin)، حفاظاً على سلامة السجلات المحاسبية.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #d97706;">
                            <span>🔗</span>
                            <div>
                                <strong>ربط السداد بمصدر محدد:</strong>
                                عند تسجيل دفعة سداد، احرص على ربطها بالمصدر الصحيح: حركة خزينة في حالة السداد النقدي، أو مسير راتب في حالة الخصم من الراتب، لضمان التتبع المحاسبي الدقيق وسلامة التقارير المالية.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 7: الأسئلة الشائعة حول الأقساط والسلف -->
                <section id="sec-faq-inst" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول الأقساط والسلف</h2>
                    </div>

                    <div class="carnet-faq-accordion">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>ما الفرق بين القسط والسلفة في النظام؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                <strong>القسط (Installment):</strong> مبلغ مالي يُصرف لغرض محدد (شراء جهاز، قرض، تمويل) ويُسدد على أشهر متعددة وفق خطة تقسيط محددة مسبقاً. يمكن أن يكون المستفيد موظفاً أو طرفاً خارجياً.<br>
                                <strong>السلفة (Advance):</strong> مبلغ نقدي يُصرف للموظف مقدماً على حساب رواتبه القادمة، ويُخصم تلقائياً من مسير الراتب الشهري حتى السداد الكامل.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يتم خصم القسط تلقائياً من الراتب الشهري؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، عند إعداد مسير راتب شهري، يقوم محرك <code>PayrollCalculationService</code> تلقائياً بفحص جميع الأقساط والسلف المستحقة على الموظف للشهر الحالي ويخصم القسط الشهري المحدد من صافي الراتب. كما يمكن تسجيل دفعات سداد يدوية من شاشة «سداد الأقساط» للمبالغ النقدية.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن تسجيل قسط لشخص خارجي (غير موظف)؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، عند اختيار نوع المستفيد «خارجي (External)»، تظهر حقول إدخال البيانات الشخصية (الاسم، رقم الهوية الوطنية، رقم الهاتف، البريد الإلكتروني، العنوان) لتوثيق هوية المستفيد الخارجي. يمكن أيضاً إضافة كفيل/ضامن للقسط.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>ماذا يحدث عند سداد آخر قسط؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                عند وصول <code>remaining_amount</code> إلى صفر (أي المبلغ المسدد = المبلغ الإجمالي - الدفعة المقدمة)، يتحول القسط تلقائياً إلى حالة «مكتمل (Completed)» ويتوقف ظهوره في قائمة الاستقطاعات الشهرية في مسيرات الرواتب.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن سداد مبلغ أكبر من القسط الشهري المحدد؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، يمكن تسجيل دفعة سداد بأي مبلغ (أكبر أو أقل من القسط الشهري) من شاشة «سداد الأقساط». النظام سيحدّث المبلغ المسدد والمتبقي تلقائياً وفقاً للمبلغ المُدخل.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن إلغاء قسط بعد بدء السداد؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يمكن تغيير حالة القسط إلى «ملغي (Cancelled)» من قِبل المشرف المالي، لكن يجب أولاً التأكد من تسوية أي دفعات سداد مسجلة مسبقاً. القسط الملغي لا يظهر في مسيرات الرواتب المستقبلية ولا في شاشة السداد.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        @elseif($activeSub === 'rewards')
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: المكافآت والحوافز (Rewards)
             ========================================================================== -->

        <!-- 1. شريط الأدوات العلوي -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon bg-icon-purple" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح المكافآت والحوافز (Rewards)</h1>
                    <p>دليل شامل لتسجيل المكافآت الاستثنائية وحوافز الأداء والإنتاجية، احتساب الساعات والدقائق الإضافية، وترحيل المبالغ المستحقة لمسير الرواتب الشهري.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل المكافآت والحوافز..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('rewards')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/employee-rewards" class="carnet-btn-action" style="background: #7c3aed; color: #ffffff; border-color: #7c3aed;">
                    <span>فتح شاشة المكافآت ⇦</span>
                </a>
            </div>
        </div>

        <!-- 2. شريط التبويبات الثانوية -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-rew" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل</span>
            </a>
            <a href="#sec-concept-rew" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة المكافآت والتكامل المالي</span>
            </a>
            <a href="#sec-fields-rew" class="carnet-tab-link">
                <span>⚙️</span>
                <span>كيف تعمل الشاشة تفصيلياً (الحقول والمعادلات)</span>
            </a>
            <a href="#sec-workflow-rew" class="carnet-tab-link">
                <span>📋</span>
                <span>خطوات إنشاء واعتماد المكافأة</span>
            </a>
            <a href="#sec-tips-rew" class="carnet-tab-link">
                <span>⭐</span>
                <span>إرشادات وضوابط نظامية</span>
            </a>
            <a href="#sec-faq-rew" class="carnet-tab-link">
                <span>❓</span>
                <span>الأسئلة الشائعة</span>
            </a>
        </nav>

        <!-- 3. المحتوى المتدفق والفهرس الجانبي -->
        <div class="carnet-content-grid">

            <!-- الفهرس الجانبي الثابت -->
            <aside class="carnet-index-card">
                <div class="carnet-index-heading">
                    <span>📑</span>
                    <span>فهرس دليل المكافآت والحوافز</span>
                </div>
                <ul class="carnet-index-list">
                    <li class="carnet-index-item">
                        <a href="#sec-video-rew">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-rew">
                            <span class="icon">💡</span>
                            <span>فكرة الشاشة والتكامل المالي</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-fields-rew">
                            <span class="icon">⚙️</span>
                            <span>حقول نموذج المكافأة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-rew">
                            <span class="icon">📋</span>
                            <span>دورة إنشاء واعتماد المكافأة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-tips-rew">
                            <span class="icon">⭐</span>
                            <span>ضوابط وإرشادات</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-rew">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو المكافآت -->
                <section id="sec-video-rew" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح المكافآت والحوافز (Rewards)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية تسجيل المكافآت الاستثنائية وحوافز الأداء، احتساب ساعات ودقائق العمل الإضافي، واعتماد الصرف وربطه بالخزينة ومسيرات الرواتب:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerRewards">
                            <source src="{{ asset('videos/Rewards.mp4') }}" type="video/mp4">
                            <source src="/videos/Rewards.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: دورة المكافآت والحوافز واحتساب الساعات والربط بالرواتب</span>
                            <span>الملف: <code>/videos/Rewards.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف شاشة المكافآت -->
                <section id="sec-concept-rew" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">💡</span>
                        <h2 class="carnet-card-title">فكرة الشاشة والتكامل المالي مع الرواتب</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشة <strong>المكافآت والحوافز (Employee Rewards)</strong> الأداة المركزية لتوثيق وصرف جميع أشكال المكافآت والحوافز المالية للموظفين خارج نطاق الراتب الأساسي. تتكامل مع مسيرات الرواتب والخزينة لضمان التتبع المالي الدقيق:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>🏆</span>
                            <div>
                                <strong>المكافآت الاستثنائية وحوافز الأداء:</strong>
                                تسجيل مكافآت تقديرية للموظفين مقابل إنجازات أو أداء متميز، مع توثيق السبب والموقع وتاريخ الاستحقاق. يمكن ربط المكافأة مباشرة بمسير الراتب الشهري لضمان الصرف في الوقت المناسب.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>⏱️</span>
                            <div>
                                <strong>احتساب ساعات ودقائق العمل الإضافي:</strong>
                                يقوم النظام تلقائياً باحتساب قيمة المكافأة بناءً على معدل الأجر بالساعة (المستخرج من الراتب الأساسي ÷ 240 ساعة)، مضروباً في عدد الساعات والدقائق المدخلة، مع إظهار الصافي المستحق لحظياً.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>🔗</span>
                            <div>
                                <strong>التكامل مع مسيرات الرواتب والخزينة:</strong>
                                عند إعداد مسير راتب شهري، يقوم محرك <code>PayrollCalculationService</code> تلقائياً بجمع جميع المكافآت المعتمدة للموظف في الشهر الحالي وإضافتها إلى إجمالي المستحقات. كما يمكن ربط المكافأة المصروفة بسند خزينة صادر.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>📊</span>
                            <div>
                                <strong>دورة حياة المكافأة (Workflow):</strong>
                                تمر المكافأة بحالات متعددة: <code>معلقة (Pending)</code> → <code>معتمدة (Approved)</code> → <code>مصروفة (Paid)</code> أو <code>مرفوضة (Rejected)</code>. كل حالة تؤثر على ظهور المكافأة في مسيرات الرواتب والتقارير المالية.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: كيف تعمل شاشة المكافآت تفصيلياً -->
                <section id="sec-fields-rew" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">⚙️</span>
                        <h2 class="carnet-card-title">كيف تعمل شاشة المكافآت تفصيلياً (10 حقول وعمليات أساسية)</h2>
                    </div>
                    <p class="carnet-card-text" style="margin-bottom: 20px;">
                        فيما يلي تفكيك دقيق لكافة الحقول والمعادلات الحسابية داخل نموذج إنشاء مكافأة جديدة:
                    </p>

                    <div class="carnet-field-grid">

                        <!-- الحقل 1: رقم المكافأة والتاريخ -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">1. رقم المكافأة والتاريخ (Reward Number & Date)</span>
                                <span class="carnet-field-badge badge-calc">توليد آلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>reward_number</code>: رقم تسلسلي فريد يُولّد تلقائياً بعد الحفظ لتوثيق المكافأة.<br>
                                    <code>reward_date</code>: تاريخ تسجيل المكافأة (يُعبأ بتاريخ اليوم افتراضياً).
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 2: الشركة والموظف -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">2. الشركة والموظف (Company & Employee)</span>
                                <span class="carnet-field-badge badge-sync">مزامنة تلقائية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    اختيار الشركة عبر مكون <code>OrgSelect::company</code> ثم اختيار الموظف المستحق للمكافأة. عند اختيار الموظف، يتم مزامنة الشركة تلقائياً واستخراج <code>معدل الأجر بالساعة (hourly_rate)</code> من الراتب الأساسي: <code>الراتب ÷ 240 ساعة</code>.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 3: سبب المكافأة والموقع -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">3. سبب المكافأة والموقع (Reason & Location)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>reason</code>: وصف سبب استحقاق المكافأة (مثال: أداء متميز، إنجاز مشروع، عمل إضافي).<br>
                                    <code>location</code>: الموقع أو الفرع الذي تم فيه الإنجاز (اختياري).
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 4: حالة المكافأة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">4. حالة المكافأة (Status)</span>
                                <span class="carnet-field-badge badge-doc">دورة اعتماد</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>Pending</code>: معلقة في انتظار الاعتماد.<br>
                                    <code>Approved</code>: معتمدة وجاهزة للصرف (تظهر في مسير الراتب).<br>
                                    <code>Paid</code>: تم الصرف — يظهر حقل تاريخ الصرف وربط سند الخزينة.<br>
                                    <code>Rejected</code>: مرفوضة ولن تُضاف للمسير.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 5: معدل الأجر بالساعة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">5. معدل الأجر بالساعة (Hourly Rate)</span>
                                <span class="carnet-field-badge badge-calc">محسوب من الراتب</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يُحسب تلقائياً عند اختيار الموظف من خلال المعادلة:<br>
                                    <code>hourly_rate = الراتب الأساسي ÷ 240</code> (30 يوم × 8 ساعات)<br>
                                    يمكن تعديل القيمة يدوياً إن تطلب الأمر معدلاً مختلفاً. عند التعديل يُعاد حساب جميع المبالغ فوراً.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 6: عدد الساعات ومبلغها -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">6. عدد الساعات ومبلغها (Hours Count & Amount)</span>
                                <span class="carnet-field-badge badge-calc">معادلة آلية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>hours_count</code>: عدد ساعات العمل الإضافي أو المكافأة (يدعم أجزاء الساعة 0.25).<br>
                                    <code>hours_amount</code>: محسوب تلقائياً:<br>
                                    <code>مبلغ الساعات = معدل الساعة × عدد الساعات</code><br>
                                    الحقل للقراءة فقط لمنع التعديل اليدوي.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 7: الدقائق ومبلغها -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">7. الدقائق ومبلغها (Minutes Count & Amount)</span>
                                <span class="carnet-field-badge badge-calc">معادلة آلية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    <code>minutes_count</code>: عدد الدقائق (0-59).<br>
                                    <code>minute_rate</code>: محسوب تلقائياً: <code>معدل الساعة ÷ 60</code><br>
                                    <code>minutes_amount</code>: <code>معدل الدقيقة × عدد الدقائق</code><br>
                                    جميع الحقول المحسوبة للقراءة فقط.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 8: صافي المكافأة المستحقة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">8. صافي المكافأة (Net Amount)</span>
                                <span class="carnet-field-badge badge-calc">معادلة نهائية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    القيمة الإجمالية المستحقة للصرف، محسوبة تلقائياً:<br>
                                    <code>الصافي = مبلغ الساعات + مبلغ الدقائق</code><br>
                                    يظهر بخط عريض بارز باللون الأخضر لسهولة التحقق السريع. هذا هو المبلغ الذي يُضاف لمسير الراتب أو يُصرف من الخزينة.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 9: تاريخ الصرف وسند الخزينة -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">9. تاريخ الصرف وسند الخزينة (Paid Date & Treasury)</span>
                                <span class="carnet-field-badge badge-integ">ربط بالخزينة</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    يظهران فقط عند تغيير الحالة إلى <code>مصروفة (Paid)</code>:<br>
                                    <code>paid_date</code>: تاريخ الصرف الفعلي.<br>
                                    <code>treasury_transaction_id</code>: ربط المكافأة بسند صرف (حركة خزينة صادرة) لضمان التتبع المالي الكامل والتوازن المحاسبي.
                                </p>
                            </div>
                        </div>

                        <!-- الحقل 10: الملاحظات -->
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">10. الملاحظات (Notes)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>
                                    حقل نصي حر لإضافة أي ملاحظات إضافية حول المكافأة مثل: تفاصيل الإنجاز، قرار الإدارة، مرجع الاعتماد، أو أي تعليقات توثيقية.
                                </p>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- القسم 4: دورة إنشاء واعتماد المكافأة -->
                <section id="sec-workflow-rew" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">📋</span>
                        <h2 class="carnet-card-title">دورة إنشاء واعتماد المكافأة خطوة بخطوة</h2>
                    </div>
                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #7c3aed;">1</span>
                            <div class="carnet-step-content">
                                <h3>إنشاء سجل مكافأة جديد</h3>
                                <p>اختر الشركة والموظف المستحق، وسيقوم النظام تلقائياً باستخراج معدل الأجر بالساعة من الراتب الأساسي. أدخل سبب المكافأة والموقع.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #7c3aed;">2</span>
                            <div class="carnet-step-content">
                                <h3>إدخال الساعات والدقائق المستحقة</h3>
                                <p>أدخل عدد ساعات ودقائق العمل الإضافي أو الحافز. سيقوم النظام فوراً بحساب مبلغ الساعات ومبلغ الدقائق وصافي المكافأة الإجمالي عبر دالة <code>recalculateBonus</code>.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #7c3aed;">3</span>
                            <div class="carnet-step-content">
                                <h3>حفظ المكافأة بحالة «معلقة»</h3>
                                <p>يتم حفظ السجل بحالة <code>Pending</code> في انتظار اعتماد المشرف المالي أو المدير المباشر. المكافأة المعلقة لا تؤثر على مسيرات الرواتب.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #7c3aed;">4</span>
                            <div class="carnet-step-content">
                                <h3>اعتماد المكافأة للصرف</h3>
                                <p>يقوم المشرف بتغيير الحالة إلى <code>Approved</code>. الآن تظهر المكافأة تلقائياً في مسير الراتب الشهري للموظف ضمن بند المكافآت والحوافز.</p>
                            </div>
                        </div>
                        <div class="carnet-step-card">
                            <span class="carnet-step-number" style="background: #7c3aed;">5</span>
                            <div class="carnet-step-content">
                                <h3>الصرف الفعلي وتوثيق السند</h3>
                                <p>عند الصرف، تتحول الحالة إلى <code>Paid</code> ويُسجل تاريخ الصرف الفعلي مع ربط المكافأة بسند خزينة (حركة صادرة) لضمان التوازن المالي والتدقيق المحاسبي.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: إرشادات وضوابط نظامية -->
                <section id="sec-tips-rew" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">⭐</span>
                        <h2 class="carnet-card-title">إرشادات وضوابط نظامية لإدارة المكافآت والحوافز</h2>
                    </div>

                    <div class="carnet-tips-box">
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>📋</span>
                            <div>
                                <strong>توثيق سبب المكافأة بدقة:</strong>
                                احرص دائماً على كتابة سبب واضح ومحدد للمكافأة في حقل <code>reason</code>. الأسباب العامة مثل «أداء جيد» غير كافية — اذكر الإنجاز المحدد مثل: «إنجاز مشروع تطوير واجهة العميل قبل الموعد بأسبوعين».
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>⏰</span>
                            <div>
                                <strong>احتساب الساعات الإضافية (المادة 107 من نظام العمل):</strong>
                                يجب ألا تزيد ساعات العمل الإضافي عن 720 ساعة سنوياً. ويُحتسب أجر الساعة الإضافية بنسبة 150% من أجر الساعة العادية (الأساسي + 50%) وفق نظام العمل السعودي.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>🔒</span>
                            <div>
                                <strong>لا تعدّل مكافأة مصروفة:</strong>
                                بعد تحول المكافأة إلى حالة «مصروفة (Paid)» وربطها بسند خزينة، لا يُنصح بتعديلها. في حالة الخطأ، أنشئ سجل تسوية أو سند عكسي بدلاً من تعديل السجل الأصلي.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #7c3aed;">
                            <span>🏦</span>
                            <div>
                                <strong>ربط المكافأة بسند الخزينة:</strong>
                                عند الصرف النقدي، اربط المكافأة بحركة خزينة صادرة (<code>treasury_transaction_id</code>) لضمان خصم المبلغ من رصيد الخزينة وتوثيق المعاملة في كشف حركة الصندوق.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: الأسئلة الشائعة -->
                <section id="sec-faq-rew" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(124, 58, 237, 0.15); color: #7c3aed;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول المكافآت والحوافز</h2>
                    </div>

                    <div class="carnet-faq-accordion">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>كيف يتم احتساب صافي المكافأة تلقائياً؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                عند اختيار الموظف، يستخرج النظام <code>معدل الأجر بالساعة = الراتب الأساسي ÷ 240</code>. ثم يحسب:<br>
                                <code>مبلغ الساعات = معدل الساعة × عدد الساعات</code><br>
                                <code>معدل الدقيقة = معدل الساعة ÷ 60</code><br>
                                <code>مبلغ الدقائق = معدل الدقيقة × عدد الدقائق</code><br>
                                <code>الصافي = مبلغ الساعات + مبلغ الدقائق</code>
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل تضاف المكافأة تلقائياً لمسير الراتب الشهري؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، ولكن فقط المكافآت التي حالتها <code>Approved</code> (معتمدة) تظهر في مسير الراتب. المكافآت المعلقة (Pending) أو المرفوضة (Rejected) لا تؤثر على حسابات المسير. المكافآت المصروفة (Paid) عبر الخزينة مباشرة لا تُضاف للمسير لتفادي الازدواجية.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن تعديل معدل الأجر بالساعة يدوياً؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، رغم أن <code>hourly_rate</code> يُحسب تلقائياً من الراتب الأساسي، إلا أنه قابل للتعديل اليدوي. هذا مفيد في حالات مثل: معدل خاص للعمل في أيام العطل الرسمية (200% بدلاً من 150%)، أو مكافأة بمعدل ثابت مختلف عن الراتب التعاقدي.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>ما الفرق بين صرف المكافأة من الراتب ومن الخزينة؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                <strong>من الراتب:</strong> يتم اعتماد المكافأة (Approved) وتُضاف تلقائياً كبند في مسير الراتب الشهري التالي ضمن إجمالي المستحقات.<br>
                                <strong>من الخزينة:</strong> تُصرف نقداً مباشرة من الخزينة ويتم ربط المكافأة بسند خزينة صادر، وتتحول الحالة إلى (Paid) مع تسجيل تاريخ الصرف الفعلي.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-summary">
                                <span>هل يمكن إعطاء أكثر من مكافأة لنفس الموظف في نفس الشهر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، لا يوجد قيد على عدد المكافآت لنفس الموظف في نفس الشهر. يمكن تسجيل مكافآت متعددة بأسباب مختلفة، وسيقوم مسير الراتب بجمعها جميعاً تلقائياً ضمن بند المكافآت.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        @endif

    @endif

</div>

<!-- دوال التفاعل مع المحتوى والبحث المباشر ونسخ الروابط -->
<script>
    function copyCarnetShareLink(sub = 'payroll') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=finance&activeSub=' + sub + '#sec-video';
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
