{{-- 
    دليل قسم إدارة المهام والعمليات الميدانية
    Tasks & Operations Management - Supervision and Shifts Guide
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

    /* Hero Box for Tasks Hub */
    .carnet-hub-hero-box {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        border-radius: 20px;
        padding: 36px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.35);
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
        color: #0f766e;
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
        background: #0f766e;
        color: #ffffff;
        border-color: #0f766e;
        transform: translateX(3px);
    }
    .dark .carnet-sub-back-btn {
        background: #334155;
        color: #2dd4bf;
        border-color: #475569;
    }
    .dark .carnet-sub-back-btn:hover {
        background: #0f766e;
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
        background: #0f766e;
        color: #ffffff;
        border-color: #0f766e;
        box-shadow: 0 4px 10px rgba(15, 118, 110, 0.25);
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
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
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
        color: #0f766e;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .dark .carnet-tab-link:hover {
        background: #0f172a;
        color: #2dd4bf;
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
        color: #0f766e;
        transform: translateX(-3px);
    }
    .dark .carnet-index-item a:hover {
        background: #0f172a;
        color: #2dd4bf;
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
        border-color: #0f766e;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.08);
        transform: translateY(-2px);
    }
    .dark .carnet-field-card {
        background: #0f172a;
        border-color: #334155;
    }
    .dark .carnet-field-card:hover {
        background: #1e293b;
        border-color: #14b8a6;
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
        background: #ccfbf1;
        color: #0f766e;
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
        background: #0f766e;
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
        border-inline-start: 4px solid #0f766e;
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
        border-color: #0f766e;
    }
    .dark .carnet-grand-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark .carnet-grand-card:hover {
        border-color: #14b8a6;
        box-shadow: 0 12px 24px -6px rgba(0,0,0,0.3);
    }
    .carnet-grand-card.featured {
        border-top: 4px solid #0f766e;
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
    .bg-icon-teal {
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
    }
    .bg-icon-blue {
        background: rgba(2, 132, 199, 0.12);
        color: #0284c7;
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
        background: #ccfbf1;
        color: #0f766e;
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
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        flex: 1;
        box-shadow: 0 4px 10px rgba(15, 118, 110, 0.25);
    }
    .carnet-btn-open-guide:hover {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
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

    @if(empty($activeSub) || !in_array($activeSub, ['supervision-shifts', 'supervision', 'shifts', 'tasks', 'distribution-tasks', 'task-assignments', 'weekly-notes']))
        <!-- ==========================================================================
             الواجهة الرئيسية لقسم إدارة المهام (Tasks Hub)
             ========================================================================== -->

        <!-- ترويسة رئيسية للقسم -->
        <div class="carnet-hub-hero-box">
            <span class="carnet-hub-badge">📋 {{ __('documentation.tasks_guide.tag') }}</span>
            <h2>{{ __('documentation.tasks_guide.hub_heading') }}</h2>
            <p>{{ __('documentation.tasks_guide.hub_note') }}</p>
        </div>

        <!-- شبكة البطاقات الكبرى لإدارة المهام -->
        <div class="carnet-grand-grid">

            <!-- 1. تعريف المهام والإشراف والمناوبة (Supervision and shifts) - Featured Card -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #0f766e;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-teal">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">إشراف ومناوبة</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #0f766e;">{{ __('documentation.tasks_guide.card_supervision_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.tasks_guide.card_supervision_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.tasks_guide.card_supervision_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('supervision-shifts')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/tasks" class="carnet-btn-screen-direct">
                        <span>شاشة تعريف المهام ⇦</span>
                    </a>
                    <a href="/supervision-shifts" class="carnet-btn-screen-direct">
                        <span>شاشة الإشراف والمناوبة ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 2. توزيع ومتابعة المهام (Task Assignments) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #0284c7;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-blue">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">توزيع ومتابعة</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #0284c7;">{{ __('documentation.tasks_guide.card_assignments_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.tasks_guide.card_assignments_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.tasks_guide.card_assignments_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('distribution-tasks')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/task-assignments" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

            <!-- 3. الملاحظات الأسبوعية (Weekly Notes) -->
            <div class="carnet-grand-card featured" style="border-top: 4px solid #d97706;">
                <div class="carnet-card-top">
                    <div class="carnet-card-icon-wrap bg-icon-amber">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="carnet-card-badges">
                        <span class="carnet-card-badge-pill pill-video">🎬 فيديو مسجل</span>
                        <span class="carnet-card-badge-pill pill-success">تقارير أسبوعية</span>
                    </div>
                </div>

                <div class="carnet-card-middle">
                    <h3 style="color: #d97706;">{{ __('documentation.tasks_guide.card_notes_title') }}</h3>
                    <span class="carnet-card-en-title">{{ __('documentation.tasks_guide.card_notes_en') }}</span>
                    <p class="carnet-card-desc">
                        {{ __('documentation.tasks_guide.card_notes_desc') }}
                    </p>
                </div>

                <div class="carnet-card-actions">
                    <button type="button" wire:click="setSub('weekly-notes')" class="carnet-btn-open-guide" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <span>عرض الفيديو والدليل التفصيلي ➔</span>
                    </button>
                    <a href="/weekly-notes" class="carnet-btn-screen-direct">
                        <span>الانتقال للشاشة بالنظام ⇦</span>
                    </a>
                </div>
            </div>

        </div>

    @elseif(in_array($activeSub, ['supervision-shifts', 'supervision', 'shifts', 'tasks']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: تعريف المهام والإشراف والمناوبة (Supervision and shifts)
             ========================================================================== -->

        <!-- 1. شريط التنقل العلوي -->
        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل إدارة المهام</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('supervision-shifts')" class="carnet-sub-tab-pill active">
                    <span>🛡️ الإشراف والمناوبة وتعريف المهام</span>
                </button>
                <button type="button" wire:click="setSub('distribution-tasks')" class="carnet-sub-tab-pill">
                    <span>👥 توزيع ومتابعة المهام</span>
                </button>
                <button type="button" wire:click="setSub('weekly-notes')" class="carnet-sub-tab-pill">
                    <span>📝 الملاحظات الأسبوعية</span>
                </button>
            </div>
        </div>

        <!-- 2. شريط الأدوات العلوي على نمط كارنت -->
        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح تعريف المهام والإشراف والمناوبة (Supervision and shifts)</h1>
                    <p>دليل شامل لإعداد وتوصيف بنك المهام القياسية، أوزان التقييم، وجداول فترات الإشراف والمناوبة وتكليف المشرفين والفرق.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <input
                    type="search"
                    id="carnetSearchInput"
                    class="carnet-search-input"
                    placeholder="ابحث في دليل المهام والإشراف..."
                    onkeyup="filterCarnetContent()"
                >
                <button type="button" onclick="copyCarnetShareLink('supervision-shifts')" class="carnet-btn-action">
                    <span>🔗</span>
                    <span>نسخ الرابط</span>
                </button>
                <button type="button" onclick="window.print()" class="carnet-btn-action">
                    <span>🖨️</span>
                    <span>طباعة الدليل</span>
                </button>
                <a href="/tasks" class="carnet-btn-action" style="background: #0f766e; color: #ffffff; border-color: #0f766e;">
                    <span>فتح بنك المهام ⇦</span>
                </a>
                <a href="/supervision-shifts" class="carnet-btn-action" style="background: #0d9488; color: #ffffff; border-color: #0d9488;">
                    <span>فتح الإشراف والمناوبة ⇦</span>
                </a>
            </div>
        </div>

        <!-- 3. شريط التبويبات الثانوية للتنقل السريع داخل الصفحة -->
        <nav class="carnet-secondary-tabs">
            <a href="#sec-video-sup" class="carnet-tab-link">
                <span>🎬</span>
                <span>فيديو الشرح المسجل (Supervision & Shifts)</span>
            </a>
            <a href="#sec-concept-sup" class="carnet-tab-link">
                <span>💡</span>
                <span>فكرة النظام والتكامل الإداري</span>
            </a>
            <a href="#sec-tasks-fields" class="carnet-tab-link">
                <span>📋</span>
                <span>دليل حقول بنك المهام (Tasks Catalog)</span>
            </a>
            <a href="#sec-shifts-fields" class="carnet-tab-link">
                <span>🏢</span>
                <span>دليل حقول الإشراف والمناوبة (Supervision & Shifts)</span>
            </a>
            <a href="#sec-workflow-sup" class="carnet-tab-link">
                <span>🔄</span>
                <span>دورة العمل التشغيلية خطوة بخطوة</span>
            </a>
            <a href="#sec-faq-sup" class="carnet-tab-link">
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
                        <a href="#sec-video-sup">
                            <span class="icon">🎬</span>
                            <span>فيديو الشرح المسجل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-concept-sup">
                            <span class="icon">💡</span>
                            <span>فكرة الشاشات والتكامل</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-tasks-fields">
                            <span class="icon">📋</span>
                            <span>بنك المهام القياسية</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-shifts-fields">
                            <span class="icon">🏢</span>
                            <span>الإشراف والمناوبة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-workflow-sup">
                            <span class="icon">🔄</span>
                            <span>دورة العمل خطوة بخطوة</span>
                        </a>
                    </li>
                    <li class="carnet-index-item">
                        <a href="#sec-faq-sup">
                            <span class="icon">❓</span>
                            <span>الأسئلة الشائعة</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- المحتوى الرئيسي -->
            <main class="carnet-main-flow">

                <!-- القسم 1: مشغل فيديو الإشراف والمناوبة وتعريف المهام -->
                <section id="sec-video-sup" class="carnet-searchable-section">
                    <div class="carnet-video-head">
                        <span class="carnet-play-badge" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">▶</span>
                        <h2>مشاهدة فيديو شرح تعريف المهام والإشراف والمناوبة (Supervision and shifts)</h2>
                    </div>
                    <p class="carnet-video-subtitle">
                        شاهد التطبيق العملي الكامل لكيفية توصيف المهام في البنك المركزي للمهام وتحديد أوزانها، وإنشاء جداول الإشراف والمناوبة، وتعيين المشرفين وفرق العمل الميدانية ومتابعة فترات الدوام:
                    </p>

                    <div class="carnet-video-box">
                        <video controls preload="auto" class="carnet-video-element" id="playerSupervisionShifts">
                            <source src="{{ asset('videos/Supervision and shifts.mp4') }}" type="video/mp4">
                            <source src="/videos/Supervision and shifts.mp4" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو المباشر.
                        </video>
                        <div class="carnet-video-caption">
                            <span>📹 فيديو توضيحي مسجل: شرح تعريف وبنك المهام وجداول الإشراف والمناوبة وتكليف الفرق</span>
                            <span>الملف: <code>/videos/Supervision and shifts.mp4</code></span>
                        </div>
                    </div>
                </section>

                <!-- القسم 2: فكرة وهدف الشاشات -->
                <section id="sec-concept-sup" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">💡</span>
                        <h2 class="carnet-card-title">فكرة الشاشات والتكامل الإداري والتشغيلي</h2>
                    </div>
                    <p class="carnet-card-text">
                        تُمثل شاشتا <strong>تعريف وبنك المهام (Tasks Catalog)</strong> و<strong>الإشراف والمناوبة (Supervision & Shifts)</strong> العمود الفقري لإدارة العمليات الميدانية والرقابية في المنشأة. يوفر النظام آلية موحدة لتوثيق المهام الوظيفية وجدولة النوبات الرقابية:
                    </p>
                    <div class="carnet-tips-box" style="margin-top: 14px;">
                        <div class="carnet-tip-item" style="border-color: #0f766e;">
                            <span>📋</span>
                            <div>
                                <strong>بنك المهام المركزي القياسي:</strong>
                                مكتبة إدارية موحدة لكافة المهام الوظيفية والتشغيلية المطلوبة في الشركة، مع تحديد أوزان تقييمية افتراضية (Weight %) تساعد في قياس معدل إنجاز الموظف لاحقاً.
                            </div>
                        </div>
                        <div class="carnet-tip-item" style="border-color: #0d9488;">
                            <span>🛡️</span>
                            <div>
                                <strong>الإشراف والمناوبة الميدانية:</strong>
                                التكليف الرسمي للمشرفين والمسؤولين بمتابعة الفروع أو الأقسام خلال فترات محددة (إشراف دوري أو مناوبة مسائية/صباحية)، وتعيين فريق العمل من الموظفين المصاحبين وتوثيق ملاحظات النوبة.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 3: دليل حقول شاشة تعريف وبنك المهام -->
                <section id="sec-tasks-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">📋</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة تعريف وبنك المهام (Tasks Catalog)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تتضمن شاشة تعريف المهمة حقولاً مخصصة لضبط بيانات المهمة القياسية:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">كود المهمة (Task Code)</span>
                                <span class="carnet-field-badge badge-calc">تلقائي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>رمز تسلسلي فريد تولده المنظومة تلقائياً لكل مهمة لسهولة الإشارة إليها وأرشفتها.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">اسم المهمة (Task Name)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>المسمى الرسمي الواضح للمهمة (مثل: جرد المخزون الأسبوعي، مراجعة سندات الصندوق، معاينة الموقع الميداني).</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الوزن الافتراضي (Default Weight %)</span>
                                <span class="carnet-field-badge badge-integ">نسبة مئوية</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الوزن النسبي المئوي الافتراضي للمهمة من 0% إلى 100%، ويستخدم كأساس لحساب نسبة الإنجاز عند توزيع المهام.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">حالة التفعيل (Status)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد ما إذا كانت المهمة نشطة (Active) ومتاحة للإسناد في جداول المهام والمناوبة أو معطلة مؤقتاً.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card" style="grid-column: 1 / -1;">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الوصف الإجرائي والتفاصيل (Description)</span>
                                <span class="carnet-field-badge badge-opt">اختياري</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>شرح إرشادي مفصل للموظف والمشرف يوضح خطوات تنفيذ المهمة والمعايير المطلوبة لقبول إنجازها بنجاح.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 4: دليل حقول شاشة الإشراف والمناوبة -->
                <section id="sec-shifts-fields" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(13, 148, 136, 0.15); color: #0d9488;">🛡️</span>
                        <h2 class="carnet-card-title">دليل حقول شاشة الإشراف والمناوبة (Supervision & Shifts)</h2>
                    </div>
                    <p class="carnet-card-text">
                        تتيح هذه الشاشة توثيق فترات التكليف الإشرافي والمناوبات بدقة، مع الجلب الآلي لبيانات المشرف من ملف الموظف:
                    </p>

                    <div class="carnet-fields-grid">
                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">نوع التكليف (Type)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>الاختيار بين نوعين: <strong>إشراف (Supervision)</strong> أو <strong>مناوبة (Shift)</strong>، حيث يحدد طبيعة الصلاحيات والمسؤوليات الميدانية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">المشرف المسؤول (Supervisor)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>قائمة بحث سريعة في الموظفين المعتمدين، يعرض الاسم والمسمى الوظيفي والرقم الوظيفي.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">البيانات الوظيفية للمشرف</span>
                                <span class="carnet-field-badge badge-sync">جلب آلي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>يقوم النظام تلقائياً باستدعاء (رقم الهوية الوطنية، المسمى الوظيفي، القسم الإداري، ورقم الجوال) بمجرد اختيار المشرف.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">الشركة / المنشأة (Company)</span>
                                <span class="carnet-field-badge badge-req">إلزامي</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>المنشأة التابع لها هذا التكليف أو الموقع الميداني محل الإشراف والمتابعة.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">فريق العمل والمهام المسندة</span>
                                <span class="carnet-field-badge badge-integ">متعدد الاختيار</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد أسماء الموظفين المكلفين بالعمل تحت إشراف هذا المشرف وتوصيف واجباتهم خلال الوردية.</p>
                            </div>
                        </div>

                        <div class="carnet-field-card">
                            <div class="carnet-field-header">
                                <span class="carnet-field-code">التوقيت والتاريخ والورديات</span>
                                <span class="carnet-field-badge badge-doc">زمني</span>
                            </div>
                            <div class="carnet-field-body">
                                <p>تحديد تاريخ المناوبة، وقت البدء ووقت الانتهاء بدقة لتوثيق ساعات الالتزام الفعلي.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 5: دورة العمل خطوة بخطوة -->
                <section id="sec-workflow-sup" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">🔄</span>
                        <h2 class="carnet-card-title">دورة العمل التشغيلية خطوة بخطوة</h2>
                    </div>

                    <div class="carnet-workflow-box">
                        <div class="carnet-step-card">
                            <span class="carnet-step-number">1</span>
                            <div class="carnet-step-details">
                                <h4>تجهيز بنك المهام القياسية</h4>
                                <p>الدخول لشاشة <code>/tasks</code> وإضافة المهام المتكررة في المنشأة مع تحديد اسم المهمة والوزن الافتراضي ووصفها الإجرائي.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">2</span>
                            <div class="carnet-step-details">
                                <h4>إنشاء جدول الإشراف والمناوبة</h4>
                                <p>التوجه لشاشة <code>/supervision-shifts</code> والضغط على زر "إضافة تكليف جديد"، وتحديد نوع التكليف (إشراف أو مناوبة).</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">3</span>
                            <div class="carnet-step-details">
                                <h4>اختيار المشرف واستدعاء بياناته</h4>
                                <p>اختيار المشرف المسؤول من القائمة المنسدلة، حيث يقوم النظام فوراً بملء بيانات الهوية، الوظيفة، القسم، والجوال تلقائياً دون إدخال يدوي.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">4</span>
                            <div class="carnet-step-details">
                                <h4>تحديد المنشأة والفترة وتكليف الفريق</h4>
                                <p>ربط التكليف بالمنشأة، وضبط تاريخ ووقت الوردية، ثم اختيار الموظفين المكلفين بالمهام تحت إشراف هذا المسؤول.</p>
                            </div>
                        </div>

                        <div class="carnet-step-card">
                            <span class="carnet-step-number">5</span>
                            <div class="carnet-step-details">
                                <h4>الحفظ والمتابعة والتقييم الميداني</h4>
                                <p>حفظ السجل، واستعراضه في جدول الإشراف والمناوبة لمتابعة نسبة الالتزام وتوثيق أي ملاحظات تشغيلية لاحقة.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- القسم 6: الأسئلة الشائعة -->
                <section id="sec-faq-sup" class="carnet-card carnet-searchable-section">
                    <div class="carnet-card-title-group">
                        <span class="carnet-card-icon-badge" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">❓</span>
                        <h2 class="carnet-card-title">الأسئلة الشائعة حول تعريف المهام والإشراف والمناوبة</h2>
                    </div>

                    <div class="carnet-faq-box">
                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>ما الفرق الجوهري بين بنك المهام وجدول الإشراف والمناوبة؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                بنك المهام هو القاموس الإداري للمهام النموذجية وأوزانها في الشركة، بينما الإشراف والمناوبة يمثل التكليف الزمني والمكاني لمشرف محدد لإدارة وردية أو فريق عمل معين.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يقوم النظام بتعبئة بيانات المشرف تلقائياً؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، بمجرد اختيار المشرف يقوم النظام بربط فوري مع ملفه الوظيفي وسحب رقم الهوية الوطنية، المسمى الوظيفي، القسم الإداري، ورقم الاتصال مباشرة لمنع الأخطاء اليدوية.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>هل يمكن تكليف أكثر من موظف ضمن فريق المشرف في نفس السجل؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                نعم، يدعم قسم "فريق العمل والمهام المسندة" اختيار وتكليف عدة موظفين مع تحديد واجباتهم خلال نفس فترة الإشراف أو الوردية.
                            </div>
                        </details>

                        <details class="carnet-faq-item">
                            <summary class="carnet-faq-header">
                                <span>أين أجد ملف الفيديو المشروح في السيرفر؟</span>
                                <span>▼</span>
                            </summary>
                            <div class="carnet-faq-body">
                                يتواجد ملف الفيديو في المسار العام <code>public/videos/Supervision and shifts.mp4</code> ويمكن تشغيله مباشرة عبر هذا الدليل.
                            </div>
                        </details>
                    </div>
                </section>

            </main>
        </div>

    @elseif(in_array($activeSub, ['distribution-tasks', 'task-assignments']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: توزيع ومتابعة المهام (Distribution of tasks)
             ========================================================================== -->

        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل إدارة المهام</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('supervision-shifts')" class="carnet-sub-tab-pill">
                    <span>🛡️ الإشراف والمناوبة وتعريف المهام</span>
                </button>
                <button type="button" wire:click="setSub('distribution-tasks')" class="carnet-sub-tab-pill active">
                    <span>👥 توزيع ومتابعة المهام</span>
                </button>
                <button type="button" wire:click="setSub('weekly-notes')" class="carnet-sub-tab-pill">
                    <span>📝 الملاحظات الأسبوعية</span>
                </button>
            </div>
        </div>

        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح توزيع ومتابعة المهام (Distribution of tasks)</h1>
                    <p>دليل إسناد المهام على الموظفين، تحديد درجات الأوزان، ومتابعة نسب التنفيذ والتقييم الدوري.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <a href="/task-assignments" class="carnet-btn-action" style="background: #0284c7; color: #ffffff; border-color: #0284c7;">
                    <span>فتح شاشة توزيع المهام ⇦</span>
                </a>
            </div>
        </div>

        <section class="carnet-searchable-section" style="margin-bottom: 24px;">
            <div class="carnet-video-head">
                <span class="carnet-play-badge" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">▶</span>
                <h2>مشاهدة فيديو شرح توزيع ومتابعة المهام (Distribution of tasks)</h2>
            </div>
            <div class="carnet-video-box">
                <video controls preload="auto" class="carnet-video-element" id="playerTaskDistribution">
                    <source src="{{ asset('videos/Distribution of tasks.mp4') }}" type="video/mp4">
                    <source src="/videos/Distribution of tasks.mp4" type="video/mp4">
                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                </video>
                <div class="carnet-video-caption">
                    <span>📹 فيديو توضيحي مسجل: دورة إسناد وتوزيع المهام ومتابعة تقييم الإنجاز</span>
                    <span>الملف: <code>/videos/Distribution of tasks.mp4</code></span>
                </div>
            </div>
        </section>

    @elseif(in_array($activeSub, ['weekly-notes']))
        <!-- ==========================================================================
             الصفحة المنفصلة التوضيحية: الملاحظات الأسبوعية (Weekly notes)
             ========================================================================== -->

        <div class="carnet-sub-header-bar">
            <button type="button" wire:click="setSub(null)" class="carnet-sub-back-btn">
                <span>⇦ العودة لدليل إدارة المهام</span>
            </button>

            <div class="carnet-sub-nav-tabs">
                <button type="button" wire:click="setSub(null)" class="carnet-sub-tab-pill">
                    <span>📌 الرئيسية (Hub)</span>
                </button>
                <button type="button" wire:click="setSub('supervision-shifts')" class="carnet-sub-tab-pill">
                    <span>🛡️ الإشراف والمناوبة وتعريف المهام</span>
                </button>
                <button type="button" wire:click="setSub('distribution-tasks')" class="carnet-sub-tab-pill">
                    <span>👥 توزيع ومتابعة المهام</span>
                </button>
                <button type="button" wire:click="setSub('weekly-notes')" class="carnet-sub-tab-pill active">
                    <span>📝 الملاحظات الأسبوعية</span>
                </button>
            </div>
        </div>

        <div class="carnet-top-bar">
            <div class="carnet-title-box">
                <div class="carnet-logo-icon" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="carnet-title-text">
                    <h1>شرح الملاحظات الأسبوعية (Weekly notes)</h1>
                    <p>دليل تسجيل التقارير الميدانية الدورية، رصد التحديات التشغيلية، وتقديم التوصيات الإدارية الأسبوعية.</p>
                </div>
            </div>

            <div class="carnet-actions-box">
                <a href="/weekly-notes" class="carnet-btn-action" style="background: #d97706; color: #ffffff; border-color: #d97706;">
                    <span>فتح شاشة الملاحظات الأسبوعية ⇦</span>
                </a>
            </div>
        </div>

        <section class="carnet-searchable-section" style="margin-bottom: 24px;">
            <div class="carnet-video-head">
                <span class="carnet-play-badge" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">▶</span>
                <h2>مشاهدة فيديو شرح الملاحظات الأسبوعية (Weekly notes)</h2>
            </div>
            <div class="carnet-video-box">
                <video controls preload="auto" class="carnet-video-element" id="playerWeeklyNotes">
                    <source src="{{ asset('videos/Weekly notes.mp4') }}" type="video/mp4">
                    <source src="/videos/Weekly notes.mp4" type="video/mp4">
                    متصفحك لا يدعم تشغيل الفيديو المباشر.
                </video>
                <div class="carnet-video-caption">
                    <span>📹 فيديو توضيحي مسجل: توثيق الملاحظات الميدانية والتقارير الأسبوعية</span>
                    <span>الملف: <code>/videos/Weekly notes.mp4</code></span>
                </div>
            </div>
        </section>

    @endif

</div>

<!-- دوال التفاعل مع المحتوى والبحث المباشر ونسخ الروابط -->
<script>
    function copyCarnetShareLink(sub = 'supervision-shifts') {
        const url = window.location.origin + window.location.pathname + '?activeGroup=tasks&activeSub=' + sub + '#sec-video-sup';
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
