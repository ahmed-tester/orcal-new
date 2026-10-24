<x-filament-panels::page>
    <style>
        /* ==========================================================================
           1. Dedicated Full-Width Hub Mode (Hide Main Admin Sidebar)
           ========================================================================== */
        aside.fi-sidebar,
        .fi-sidebar,
        .fi-sidebar-close-overlay,
        .fi-layout-sidebar-toggle-btn-ctn {
            display: none !important;
        }

        .fi-main-ctn,
        body .fi-layout > .fi-main-ctn {
            padding-inline-start: 0 !important;
            margin-inline-start: 0 !important;
            max-width: 100% !important;
        }

        /* ==========================================================================
           2. Scoped Documentation Hub Container & Typography (Tajawal Font)
           ========================================================================== */
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap');

        .doc-hub-root,
        .doc-hub-root * {
            font-family: 'Tajawal', sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            box-sizing: border-box;
        }

        .doc-hub-root {
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: 22px;
            color: #1e293b;
            line-height: 1.8;
        }

        .dark .doc-hub-root {
            color: #f1f5f9;
        }

        /* Enforce fixed SVG sizing everywhere to prevent icon blowout */
        .doc-hub-root svg {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            max-width: 18px !important;
            min-height: 18px !important;
            max-height: 18px !important;
            display: inline-block !important;
            flex-shrink: 0 !important;
            vertical-align: middle !important;
        }

        /* ==========================================================================
           3. Hero Header Bar with Back-to-Dashboard Button
           ========================================================================== */
        .doc-hub-top-bar {
            background: linear-gradient(135deg, #052445 0%, #093c72 50%, #1e40af 100%);
            border-radius: 16px;
            padding: 24px 30px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            box-shadow: 0 10px 25px -5px rgba(8, 44, 80, 0.2);
            position: relative;
            overflow: hidden;
        }

        .doc-hub-top-bar::after {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .doc-hub-top-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            z-index: 1;
        }

        .doc-hub-top-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 800;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 3px 10px;
            border-radius: 20px;
            color: #e0f2fe;
            width: fit-content;
            margin-bottom: 2px;
        }

        .doc-hub-top-info h1 {
            font-size: 23px;
            font-weight: 900;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .doc-hub-top-info p {
            font-size: 14px;
            font-weight: 500;
            color: #e2e8f0;
            margin: 0;
            max-width: 820px;
            line-height: 1.7;
        }

        .doc-hub-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: #082c50;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            position: relative;
            z-index: 1;
        }

        .doc-hub-back-btn:hover {
            transform: translateY(-2px);
            background: #f8fafc;
            color: #1e40af;
            box-shadow: 0 6px 20px rgba(0,0,0,0.22);
        }

        /* ==========================================================================
           4. Horizontal Layout (Top Horizontal Bar + Full Width Viewport)
           ========================================================================== */
        .doc-hub-body-layout {
            display: flex;
            flex-direction: column;
            gap: 18px;
            width: 100%;
        }

        /* Top Horizontal Department Navigation Bar (بالعرض في أول الصفحة) */
        .doc-hub-nav-horizontal-wrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 8px 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            width: 100%;
        }

        .dark .doc-hub-nav-horizontal-wrap {
            background: #1e293b;
            border-color: #334155;
        }

        .doc-hub-nav-horizontal {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: thin;
            padding-bottom: 2px;
        }

        .doc-hub-horiz-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 700;
            color: #475569;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .dark .doc-hub-horiz-btn {
            color: #cbd5e1;
        }

        .doc-hub-horiz-btn:hover {
            background: #f1f5f9;
            color: #0284c7;
        }

        .dark .doc-hub-horiz-btn:hover {
            background: #0f172a;
            color: #38bdf8;
        }

        .doc-hub-horiz-btn.active {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
        }

        .dark .doc-hub-horiz-btn.active {
            background: #0284c7;
            color: #ffffff;
        }

        .doc-hub-horiz-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            background: #e0f2fe;
            color: #0369a1;
        }

        .doc-hub-horiz-btn.active .doc-hub-horiz-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .dark .doc-hub-nav-title {
            color: #94a3b8;
            border-bottom-color: #1f2937;
        }

        .doc-hub-nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 11px 14px;
            border-radius: 10px;
            border: 1px solid transparent;
            background: #f8fafc;
            color: #334155;
            font-size: 13.5px;
            font-weight: 700;
            text-align: right;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .doc-hub-nav-item {
            background: #1e293b;
            color: #cbd5e1;
        }

        .doc-hub-nav-item:hover {
            background: #f1f5f9;
            color: #082c50;
            border-color: #cbd5e1;
            transform: translateX(-3px);
        }

        .dark .doc-hub-nav-item:hover {
            background: #334155;
            color: #ffffff;
            border-color: #475569;
        }

        .doc-hub-nav-item.active {
            background: linear-gradient(135deg, #082c50 0%, #0e447b 100%);
            color: #ffffff;
            border-inline-start: 4px solid #38bdf8;
            box-shadow: 0 4px 14px rgba(8, 44, 80, 0.25);
            transform: translateX(-4px);
        }

        .dark .doc-hub-nav-item.active {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            border-inline-start-color: #93c5fd;
            color: #ffffff;
        }

        .doc-hub-nav-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .doc-hub-nav-item.active .doc-hub-nav-badge {
            background: #38bdf8;
            color: #082c50;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .doc-hub-nav-item:not(.active) .doc-hub-nav-badge {
            background: #e2e8f0;
            color: #475569;
        }

        .dark .doc-hub-nav-item:not(.active) .doc-hub-nav-badge {
            background: #334155;
            color: #cbd5e1;
        }

        /* Main Viewport Card */
        .doc-hub-main-panel {
            flex: 1;
            min-width: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px 34px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 26px;
        }

        .dark .doc-hub-main-panel {
            background: #111827;
            border-color: #1f2937;
        }

        /* ==========================================================================
           5. Dedicated Guide Styles (Section, Concept, Steps, Advantages)
           ========================================================================== */
        .doc-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .doc-section-head {
            border-bottom-color: #1f2937;
        }

        .doc-section-title-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .doc-section-tag {
            font-size: 12px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .dark .doc-section-tag {
            color: #60a5fa;
        }

        .doc-section-title {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
        }

        .dark .doc-section-title {
            color: #f8fafc;
        }

        .doc-actions-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .doc-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .doc-btn-primary {
            background: linear-gradient(135deg, #082c50 0%, #1e40af 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 12px rgba(8, 44, 80, 0.2);
        }

        .doc-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(8, 44, 80, 0.3);
            color: #ffffff;
        }

        .dark .doc-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .doc-btn-secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .doc-btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
            transform: translateY(-2px);
        }

        .dark .doc-btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }

        /* Concept Card */
        .doc-concept-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-inline-start: 5px solid #0284c7;
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.06);
        }

        .dark .doc-concept-card {
            background: linear-gradient(135deg, #082f49 0%, #0c4a6e 100%);
            border-color: #0369a1;
            border-inline-start-color: #38bdf8;
            color: #e0f2fe;
        }

        .doc-concept-header {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-concept-icon {
            font-size: 18px;
        }

        .doc-concept-badge {
            font-size: 14.5px;
            font-weight: 900;
            color: #0369a1;
        }

        .dark .doc-concept-badge {
            color: #38bdf8;
        }

        .doc-concept-text {
            font-size: 14.5px;
            font-weight: 600;
            color: #0f2d4a;
            margin: 0;
            line-height: 2.0;
        }

        .dark .doc-concept-text {
            color: #f0f9ff;
        }

        /* Steps */
        .doc-workflow-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .doc-workflow-header-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .doc-workflow-heading {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dark .doc-workflow-heading {
            color: #f8fafc;
        }

        .doc-workflow-subtag {
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 6px;
        }

        .dark .doc-workflow-subtag {
            background: #1e293b;
            color: #94a3b8;
        }

        .doc-steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }

        .doc-step-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #082c50;
            border-radius: 14px;
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .dark .doc-step-card {
            background: #1e293b;
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        .doc-step-card:hover {
            transform: translateY(-4px);
            border-top-color: #2563eb;
            box-shadow: 0 10px 24px -4px rgba(8, 44, 80, 0.12);
        }

        .doc-step-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .doc-step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: linear-gradient(135deg, #082c50 0%, #1e40af 100%);
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 900;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(8, 44, 80, 0.25);
        }

        .dark .doc-step-num {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        }

        .doc-step-card h4 {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.5;
        }

        .dark .doc-step-card h4 {
            color: #f8fafc;
        }

        .doc-step-card p {
            font-size: 13.5px;
            font-weight: 500;
            color: #334155;
            margin: 0;
            line-height: 1.85;
        }

        .dark .doc-step-card p {
            color: #94a3b8;
        }

        /* Advantages Box */
        .doc-advantages-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px 26px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .dark .doc-advantages-box {
            background: #161e2e;
            border-color: #1f2937;
        }

        .doc-advantages-header {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-advantages-title {
            font-size: 15.5px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
        }

        .dark .doc-advantages-title {
            color: #f8fafc;
        }

        .doc-advantages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }

        .doc-adv-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .doc-adv-card {
            background: #1e293b;
            border-color: #334155;
        }

        .doc-adv-card:hover {
            transform: translateY(-2px);
            border-color: #93c5fd;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
        }

        .doc-adv-badge-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f0f7ff;
            border: 1px solid #bfdbfe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .dark .doc-adv-badge-icon {
            background: #0f2d4a;
            border-color: #0369a1;
        }

        .doc-adv-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .doc-adv-content strong {
            font-size: 14px;
            font-weight: 800;
            color: #082c50;
        }

        .dark .doc-adv-content strong {
            color: #60a5fa;
        }

        .doc-adv-content p {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin: 0;
            line-height: 1.7;
        }

        .dark .doc-adv-content p {
            color: #94a3b8;
        }

        /* ==========================================================================
           6. Fallback Screens Grid for Other Sections
           ========================================================================== */
        .doc-screens-notice {
            background: #fdf4ff;
            border: 1px solid #f0abfc;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #86198f;
            line-height: 1.7;
        }

        .dark .doc-screens-notice {
            background: #4a044e;
            border-color: #a21caf;
            color: #fdf4ff;
        }

        .doc-screens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .doc-screen-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .doc-screen-card {
            background: #1e293b;
            border-color: #334155;
        }

        .doc-screen-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .dark .doc-screen-card:hover {
            background: #0f172a;
            border-color: #475569;
        }

        .doc-screen-card h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .dark .doc-screen-card h4 {
            color: #f8fafc;
        }

        .doc-screen-link {
            font-size: 12px;
            font-weight: 800;
            color: #2563eb;
            text-decoration: none;
            white-space: nowrap;
        }

        .doc-screen-link:hover {
            text-decoration: underline;
        }
    </style>

    <div class="doc-hub-root">

        <!-- Top Header Bar with Exit / Return Button -->
        <div class="doc-hub-top-bar">
            <div class="doc-hub-top-info">
                <span class="doc-hub-top-badge">✨ المركز الإداري المعتمد</span>
                <h1>{{ __('documentation.hub_title') }}</h1>
                <p>{{ __('documentation.hub_subtitle') }}</p>
            </div>

            <a href="/" class="doc-hub-back-btn">
                <span>{{ __('documentation.back_to_dashboard') }} ⇦</span>
            </a>
        </div>

        <!-- Layout: Horizontal Navigation + Full-Width Content -->
        <div class="doc-hub-body-layout">

            <!-- Horizontal Department Navigation across the top (السايد بار في أول الصفحة وبالعرض) -->
            <div class="doc-hub-nav-horizontal-wrap">
                <nav class="doc-hub-nav-horizontal">
                    @foreach($groups as $groupKey => $groupData)
                        <button
                            type="button"
                            wire:click="setGroup('{{ $groupKey }}')"
                            class="doc-hub-horiz-btn {{ $activeGroup === $groupKey ? 'active' : '' }}"
                        >
                            <span>{{ $groupData['title'] }}</span>

                            @if(!empty($groupData['has_detailed_guide']))
                                <span class="doc-hub-horiz-badge">{{ __('documentation.detailed_guide') }} ✨</span>
                            @else
                                <span class="doc-hub-horiz-badge">{{ count($groupData['resources'] ?? []) }}</span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            <!-- Main Content Area (Full Width) -->
            <main class="doc-hub-main-panel" style="width: 100%; max-width: 100%; flex: 1;">

                @if(!empty($current['has_detailed_guide']) && !empty($current['view']))
                    <!-- Render Dedicated Guide Partial -->
                    @include($current['view'])
                @else
                    <!-- Fallback View for In-Progress Groups -->
                    <div class="doc-section-head">
                        <div class="doc-section-title-group">
                            <span class="doc-section-tag">{{ __('documentation.sidebar_heading') }}</span>
                            <h2 class="doc-section-title">{{ $current['title'] }}</h2>
                        </div>
                    </div>

                    <div class="doc-screens-notice">
                        <span>ℹ️ {{ __('documentation.common.coming_soon_note') }}</span>
                    </div>

                    <div>
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                            {{ __('documentation.common.screens_heading') }}
                        </h3>

                        <div class="doc-screens-grid">
                            @foreach($current['resources'] as $res)
                                <div class="doc-screen-card">
                                    <h4>{{ $res['name'] }}</h4>
                                    <a href="{{ $res['url'] }}" class="doc-screen-link">
                                        <span>{{ __('documentation.common.open_screen') }} ⇦</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </main>

        </div>

    </div>
</x-filament-panels::page>
