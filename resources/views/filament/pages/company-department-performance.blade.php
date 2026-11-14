<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');

        .company-perf-page {
            --bg: #f3f5fb;
            --card: #ffffff;
            --tx: #141a2e;
            --mu: #6b7490;
            --bd: #e4e8f3;
            --a: #5b5bf0;
            --b: #14b8a6;
            --c: #f59e0b;
            --d: #ef4444;
            --g: #22c55e;
            --sh: 0 8px 30px rgba(40, 50, 100, 0.08);

            font-family: 'Tajawal', system-ui, -apple-system, sans-serif;
            color: var(--tx);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }

        .dark .company-perf-page {
            --bg: #0d1020;
            --card: #171b30;
            --tx: #eef0fa;
            --mu: #8e96b8;
            --bd: #262b47;
            --sh: 0 8px 30px rgba(0, 0, 0, 0.35);
        }

        .company-perf-page .perf-overview-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .company-perf-page .perf-overview-tabs button {
            padding: .5rem .9rem;
            border: 1px solid var(--bd);
            border-radius: .75rem;
            background: var(--card);
            color: var(--tx);
            font-weight: 700;
        }

        .company-perf-page .perf-overview-tabs button[aria-pressed="true"] {
            background: var(--a);
            color: white;
        }

        .company-perf-page .perf-overview-group {
            display: grid;
            gap: .75rem;
        }

        .company-perf-page .perf-overview-note {
            color: var(--mu);
            font-size: .875rem;
        }

        /* Hero Header */
        .company-perf-page .perf-hero-header {
            background: linear-gradient(135deg, #4f46e5, #8b5cf6 60%, #ec4899);
            color: #ffffff;
            border-radius: 18px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 10px 32px rgba(91, 91, 240, 0.22);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .company-perf-page .perf-hero-header::after {
            content: '';
            position: absolute;
            inset-inline-end: -40px;
            top: -40px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .company-perf-page .perf-hero-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 1;
        }

        .company-perf-page .perf-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            display: grid;
            place-items: center;
            font-size: 24px;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .company-perf-page .perf-hero-title {
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            line-height: 1.25;
        }

        .company-perf-page .perf-hero-sub {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85);
            margin: 0.25rem 0 0;
            line-height: 1.4;
        }

        .company-perf-page .perf-hero-chips {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            z-index: 1;
        }

        .company-perf-page .perf-hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
            font-size: 0.775rem;
            font-weight: 700;
        }

        /* Top 6 KPI Metric Cards Grid */
        .company-perf-page .perf-kpi-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        }

        @media (min-width: 1280px) {
            .company-perf-page .perf-kpi-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        /* Base Card Styling (from HTML mockup) */
        .company-perf-page .card,
        .company-perf-page .perf-card {
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: var(--sh);
            opacity: 0;
            transform: translateY(12px);
            animation: perfUp 0.4s forwards;
            animation-delay: calc(var(--i, 0) * 35ms);
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .company-perf-page .card:hover,
        .company-perf-page .perf-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 36px rgba(40, 50, 100, 0.12);
        }

        .dark .company-perf-page .card:hover,
        .dark .company-perf-page .perf-card:hover {
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.5);
            border-color: #3b456d;
        }

        .company-perf-page .perf-card.is-clickable {
            cursor: pointer;
        }

        @keyframes perfUp {
            to {
                opacity: 1;
                transform: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .company-perf-page .card,
            .company-perf-page .perf-card {
                animation: none !important;
                transition: none !important;
            }
            .company-perf-page .ring {
                transition: none !important;
            }
        }

        .company-perf-page .k {
            color: var(--mu);
            font-size: 12px;
            font-weight: 700;
        }

        .company-perf-page .v {
            font-size: 24px;
            font-weight: 800;
            margin-top: 4px;
            color: var(--tx);
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .company-perf-page .v u {
            text-decoration: none;
            font-size: 11.5px;
            color: var(--mu);
            font-weight: 500;
        }

        .company-perf-page .ic {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .company-perf-page .perf-card-hint {
            margin-top: 8px;
            font-size: 11.5px;
            color: var(--mu);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px dashed var(--bd);
            padding-top: 6px;
        }

        .company-perf-page .perf-card-hint-arrow {
            color: var(--a);
            display: inline-flex;
            transition: transform 0.2s ease;
        }

        .company-perf-page .perf-card:hover .perf-card-hint-arrow {
            transform: translateX(-3px);
        }

        /* Circular Progress Rings (Pure CSS conic-gradient) */
        .company-perf-page .ring {
            --p: 0;
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: conic-gradient(var(--col, var(--g)) calc(var(--p) * 1%), var(--bd) 0);
            display: grid;
            place-items: center;
            flex: none;
            transition: background 0.4s ease;
        }

        .company-perf-page .ring i {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--card);
            display: grid;
            place-items: center;
            font-style: normal;
            font-weight: 800;
            font-size: 13.5px;
            color: var(--tx);
        }

        .company-perf-page .ring.s {
            width: 46px;
            height: 46px;
        }

        .company-perf-page .ring.s i {
            width: 35px;
            height: 35px;
            font-size: 11px;
        }

        .company-perf-page .ring.m {
            width: 68px;
            height: 68px;
        }

        .company-perf-page .ring.m i {
            width: 52px;
            height: 52px;
            font-size: 14px;
        }

        .company-perf-page .ring.l {
            width: 96px;
            height: 96px;
        }

        .company-perf-page .ring.l i {
            width: 74px;
            height: 74px;
            font-size: 18px;
        }

        .company-perf-page .ring.dept {
            width: 58px;
            height: 58px;
        }

        .company-perf-page .ring.dept i {
            width: 44px;
            height: 44px;
            font-size: 13px;
            font-weight: 800;
        }

        .company-perf-page .ring-null {
            color: var(--mu);
            font-size: 12px;
        }

        .company-perf-page .perf-ring-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
        }

        .company-perf-page .perf-ring-item.is-clickable {
            cursor: pointer;
            transition: transform 0.18s ease;
        }

        .company-perf-page .perf-ring-item.is-clickable:hover {
            transform: scale(1.05);
        }

        .company-perf-page .perf-ring-label {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--mu);
        }

        .company-perf-page .perf-ring-sublabel {
            font-size: 10px;
            color: var(--mu);
            direction: ltr;
            display: inline-block;
            margin-top: 1px;
            font-weight: 600;
        }

        /* Stepper & Breadcrumb Bar */
        .company-perf-page .perf-stepper-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 14px;
            box-shadow: var(--sh);
        }

        .company-perf-page .stepper-trail {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .company-perf-page .stepper-step {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
            border: 1px solid transparent;
            text-decoration: none;
            background: transparent;
            color: var(--mu);
        }

        .company-perf-page .stepper-step.active {
            background: var(--bg);
            color: var(--tx);
            border-color: var(--bd);
        }

        .company-perf-page .stepper-step.link {
            color: var(--a);
            cursor: pointer;
        }

        .company-perf-page .stepper-step.link:hover {
            background: color-mix(in srgb, var(--a) 10%, transparent);
            border-color: color-mix(in srgb, var(--a) 30%, transparent);
        }

        .company-perf-page .stepper-divider {
            color: var(--mu);
            font-size: 0.75rem;
        }

        .company-perf-page .perf-notice-banner {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 1.15rem;
            font-size: 0.785rem;
            line-height: 1.45;
            border-radius: 12px;
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fef3c7;
        }

        .dark .company-perf-page .perf-notice-banner {
            background: rgba(245, 158, 11, 0.1);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.25);
        }

        /* Entity Section & Cards Grid (Companies, Departments, Employees) */
        .company-perf-page .perf-entity-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 4px;
        }

        .company-perf-page .perf-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .company-perf-page .perf-section-title-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .company-perf-page .perf-section-title {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
            color: var(--tx);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .company-perf-page .perf-section-title::before {
            content: "";
            width: 4px;
            height: 18px;
            border-radius: 4px;
            background: var(--a);
        }

        .company-perf-page .perf-count-badge {
            background: var(--bg);
            border: 1px solid var(--bd);
            color: var(--mu);
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .company-perf-page .perf-back-btn {
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--bd);
            color: var(--tx);
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .company-perf-page .perf-back-btn:hover {
            background: var(--bg);
            border-color: var(--a);
            color: var(--a);
        }

        .company-perf-page .perf-cards-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fill, minmax(min(330px, 100%), 1fr));
        }

        /* Entity Card Style (.card.inst from HTML mockup) */
        .company-perf-page .card.inst {
            padding: 16px;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            box-shadow: var(--sh);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 12px;
            height: 100%;
            box-sizing: border-box;
        }

        .company-perf-page .inst-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            min-height: 56px;
        }

        .company-perf-page .inst-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            min-width: 0;
        }

        .company-perf-page .inst-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--tx);
            cursor: pointer;
            transition: color 0.18s ease;
            line-height: 1.35;
            min-height: 2.7em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .company-perf-page .inst-title:hover {
            color: var(--a);
        }

        .company-perf-page .inst-subtitle {
            font-size: 12px;
            color: var(--mu);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .company-perf-page .inst-subtitle-dot {
            font-size: 10px;
            color: var(--mu);
        }

        .company-perf-page .inst-badge {
            font-size: 12px;
            font-weight: 700;
            color: var(--a);
            background: color-mix(in srgb, var(--a) 12%, transparent);
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid color-mix(in srgb, var(--a) 25%, transparent);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .company-perf-page .inst-badges-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 3px;
        }

        .company-perf-page .inst-pill-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 6px;
        }

        .company-perf-page .inst-pill-badge.is-team {
            background: color-mix(in srgb, var(--b) 14%, transparent);
            color: var(--b);
            border: 1px solid color-mix(in srgb, var(--b) 30%, transparent);
        }

        .company-perf-page .inst-pill-badge.is-unassigned {
            background: color-mix(in srgb, var(--c) 14%, transparent);
            color: var(--c);
            border: 1px solid color-mix(in srgb, var(--c) 30%, transparent);
        }

        .company-perf-page .inst-pill-badge.is-saudi {
            background: color-mix(in srgb, var(--g) 14%, transparent);
            color: var(--g);
            border: 1px solid color-mix(in srgb, var(--g) 30%, transparent);
        }

        .company-perf-page .inst-pill-badge.is-foreign {
            background: color-mix(in srgb, var(--mu) 14%, transparent);
            color: var(--mu);
            border: 1px solid var(--bd);
        }

        .company-perf-page .card.inst.is-unassigned {
            border-color: color-mix(in srgb, var(--c) 40%, var(--bd));
            background: color-mix(in srgb, var(--c) 3%, var(--card));
        }

        /* Major Highlight Banner inside Entity Card */
        .company-perf-page .inst-kpi-banner {
            background: var(--bg);
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 50px;
            box-sizing: border-box;
            flex-shrink: 0;
        }

        .company-perf-page .inst-kpi-banner .v {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            color: var(--tx);
            display: flex;
            align-items: baseline;
            gap: 6px;
            line-height: 1;
        }

        .company-perf-page .inst-kpi-banner .v span {
            font-size: 20px;
            font-weight: 800;
            color: var(--tx);
            line-height: 1;
        }

        .company-perf-page .inst-kpi-banner .v u {
            font-size: 12px;
            color: var(--mu);
            font-weight: 600;
            text-decoration: none;
        }

        .company-perf-page .inst-kpi-banner:hover {
            border-color: var(--a);
            background: color-mix(in srgb, var(--a) 6%, var(--bg));
        }

        .company-perf-page .inst-kpi-banner.has-active {
            border-color: color-mix(in srgb, var(--a) 30%, var(--bd));
        }

        .company-perf-page .inst-kpi-link {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--a);
            white-space: nowrap;
        }

        /* Mini specs rows (.mini from HTML mockup) */
        .company-perf-page .mini {
            display: flex;
            flex-direction: column;
            margin: 6px 0;
            font-size: 12.5px;
        }

        .company-perf-page .mini span {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-top: 1px dashed var(--bd);
            color: var(--mu);
        }

        .company-perf-page .mini span:first-child {
            border-top: 0;
        }

        .company-perf-page .mini b {
            color: var(--tx);
            font-weight: 700;
            direction: ltr;
        }

        .company-perf-page .mini-clickable {
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .company-perf-page .mini-clickable:hover {
            color: var(--a);
        }

        .company-perf-page .mini-clickable:hover b {
            color: var(--a);
        }

        /* Rings row inside Card */
        .company-perf-page .rings-row {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 8px 0;
            border-top: 1px solid var(--bd);
            border-bottom: 1px solid var(--bd);
            margin: 4px 0;
        }

        /* Entity Card Footer */
        .company-perf-page .inst-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--a) 8%, transparent);
            color: var(--a);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            text-align: center;
        }

        .company-perf-page .inst-footer:hover {
            background: var(--a);
            color: #ffffff;
        }

        .company-perf-page .inst-footer.is-link {
            background: color-mix(in srgb, var(--b) 8%, transparent);
            color: var(--b);
        }

        .company-perf-page .inst-footer.is-link:hover {
            background: var(--b);
            color: #ffffff;
        }

        .company-perf-page .inst-footer.is-disabled {
            background: var(--bg);
            color: var(--mu);
            cursor: default;
            opacity: 0.6;
        }

        /* Empty State Card */
        .company-perf-page .perf-empty-state-card {
            grid-column: 1 / -1;
            padding: 3rem 1.5rem;
            text-align: center;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .company-perf-page .perf-empty-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--bg);
            color: var(--mu);
            display: grid;
            place-items: center;
            margin-bottom: 4px;
        }

        .company-perf-page .perf-empty-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--tx);
        }

        .company-perf-page .perf-empty-sub {
            font-size: 13px;
            color: var(--mu);
        }

        /* ================================================= */
        /* Supervisor & Department Performance View (Prototype Replica) */
        /* ================================================= */

        /* Top Average Banner Card */
        .company-perf-page .perf-avg-banner {
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            padding: 1.25rem 1.75rem;
            box-shadow: var(--sh);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            animation: perfUp 0.35s ease;
        }

        .company-perf-page .perf-avg-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .company-perf-page .perf-avg-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--tx);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .company-perf-page .perf-avg-chevron {
            background: transparent;
            border: none;
            color: var(--mu);
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            transition: all 0.2s ease;
        }

        .company-perf-page .perf-avg-chevron:hover {
            background: var(--bg);
            color: var(--tx);
        }

        .company-perf-page .perf-avg-rings {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 0.5rem 0;
        }

        /* 4-Columns Department Cards Grid */
        .company-perf-page .dept-grid-style {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        }

        @media (min-width: 1200px) {
            .company-perf-page .dept-grid-style {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Department / Supervisor Card */
        .company-perf-page .dept-card {
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            padding: 18px 14px;
            box-shadow: var(--sh);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            gap: 12px;
            opacity: 0;
            transform: translateY(12px);
            animation: perfUp 0.4s forwards;
            animation-delay: calc(var(--i, 0) * 35ms);
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        }

        .company-perf-page .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(40, 50, 100, 0.12);
        }

        .dark .company-perf-page .dept-card:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
            border-color: #3b456d;
        }

        .company-perf-page .dept-card-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .company-perf-page .dept-card-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--tx);
            cursor: pointer;
            transition: color 0.18s ease;
        }

        .company-perf-page .dept-card-title:hover {
            color: var(--a);
        }

        .company-perf-page .dept-card-lead {
            font-size: 13px;
            color: var(--mu);
            font-weight: 500;
        }

        .company-perf-page .dept-lead-role {
            font-size: 11px;
            color: var(--mu);
            opacity: 0.85;
        }

        .company-perf-page .dept-card-rings {
            display: flex;
            align-items: flex-start;
            justify-content: space-around;
            gap: 6px;
            margin: 4px 0;
        }

        .company-perf-page .dept-card-rings .perf-ring-label {
            font-size: 10.5px;
            max-width: 72px;
            line-height: 1.25;
            color: var(--mu);
            font-weight: 700;
        }

        .company-perf-page .dept-card-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        /* Purple Button (supervisor action) */
        .company-perf-page .btn-action-purple {
            background: #5c5cfc;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 9px 12px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            transition: background 0.18s ease, transform 0.15s ease, box-shadow 0.18s ease;
        }

        .company-perf-page .btn-action-purple:hover {
            background: #4848ea;
            box-shadow: 0 4px 14px rgba(92, 92, 252, 0.35);
            transform: translateY(-1px);
        }

        /* Teal Button (beneficiary review) */
        .company-perf-page .btn-action-teal {
            background: #0bb5a2;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 9px 12px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            transition: background 0.18s ease, transform 0.15s ease, box-shadow 0.18s ease;
        }

        .company-perf-page .btn-action-teal:hover {
            background: #099b8b;
            box-shadow: 0 4px 14px rgba(11, 181, 162, 0.35);
            transform: translateY(-1px);
        }

        .dark .company-perf-page .btn-action-purple {
            background: #6366f1;
        }

        .dark .company-perf-page .btn-action-teal {
            background: #14b8a6;
        }

        /* Modal / Kashf Modal Styling */
        .perf-modal-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 800;
            color: var(--tx);
        }

        .perf-modal-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: color-mix(in srgb, var(--a) 14%, transparent);
            color: var(--a);
            display: grid;
            place-items: center;
        }

        .perf-tx-code {
            color: var(--a);
            font-weight: 700;
        }

        .perf-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--bd);
            background: var(--card);
            box-shadow: var(--sh);
            margin-top: 10px;
        }

        .perf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            white-space: nowrap;
            text-align: right;
        }

        .perf-table th {
            padding: 10px 14px;
            font-weight: 700;
            color: var(--mu);
            background: var(--bg);
            border-bottom: 1px solid var(--bd);
        }

        .perf-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--bd);
            color: var(--tx);
            vertical-align: middle;
        }

        .perf-table tr:last-child td {
            border-bottom: none;
        }

        .perf-table tbody tr:hover {
            background: color-mix(in srgb, var(--a) 3%, transparent);
        }
    </style>

    <div class="company-perf-page">
        <!-- Top Hero Header Bar (from HTML mockup) -->
        <div class="perf-hero-header">
            <div class="perf-hero-info">
                <div class="perf-hero-icon">
                    <x-heroicon-o-chart-bar-square style="width: 28px; height: 28px; color: #ffffff;" />
                </div>
                <div>
                    <h1 class="perf-hero-title">{{ __('performance.title') }}</h1>
                    <p class="perf-hero-sub">{{ __('performance.subtitle') }}</p>
                </div>
            </div>

            <div class="perf-hero-chips">
                <div class="perf-hero-chip">
                    <x-heroicon-m-calendar style="width: 15px; height: 15px;" />
                    <span><bdi>{{ $this->dateFrom ?? '—' }}</bdi> &larr; <bdi>{{ $this->dateTo ?? '—' }}</bdi></span>
                </div>
                <div class="perf-hero-chip">
                    <x-heroicon-m-building-office-2 style="width: 15px; height: 15px;" />
                    <span>{{ count($this->companies) }} {{ __('performance.columns.company') }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <x-filter-bar
            :title="__('performance.filters.title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('performance.filters.company')"
            :show-branch="true"
            :branches="$this->branches"
            :branch-label="__('performance.filters.branch')"
            :show-dates="true"
            :date-from-label="__('performance.filters.date_from')"
            :date-to-label="__('performance.filters.date_to')"
            :show-search="! ($this->showAttendanceAndLeaves && $this->canViewWorkforceOverview)"
            :search-placeholder="__('performance.filters.search')"
            :has-active="$this->hasActiveFilterBar()"
        />

        @if($this->canViewWorkforceOverview)
            <nav class="perf-overview-tabs" aria-label="{{ __('performance.overview.navigation') }}">
                <button type="button" wire:click="showOperationsOverview" aria-pressed="{{ $this->showAttendanceAndLeaves ? 'false' : 'true' }}">
                    {{ __('performance.overview.operations') }}
                </button>
                <button type="button" wire:click="showWorkforceOverview" aria-pressed="{{ $this->showAttendanceAndLeaves ? 'true' : 'false' }}">
                    {{ __('performance.overview.workforce') }}
                </button>
            </nav>
        @endif

        @if($this->showAttendanceAndLeaves && $this->canViewWorkforceOverview)
            @include('filament.pages.company-department-performance.workforce-overview')
        @else
            @include('filament.pages.company-department-performance.operations-overview')
        @endif
        <!-- ========================================== -->
        <!-- Kashf Modal -->
        <!-- ========================================== -->
        @include('filament.pages.company-department-performance.statement-modal')
    </div>
</x-filament-panels::page>
