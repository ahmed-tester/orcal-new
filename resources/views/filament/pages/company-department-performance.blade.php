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
            align-self: stretch;
            min-width: 0;
            overflow-x: auto;
            gap: 6px;
            padding: 5px;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 14px;
            box-shadow: var(--sh);
        }

        .company-perf-page .perf-overview-tabs button {
            flex: 1 0 auto;
            min-height: 2.5rem;
            padding: .45rem .75rem;
            border-radius: 10px;
            color: var(--mu);
            font-size: .875rem;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
        }

        @media (min-width: 1200px) {
            .company-perf-page .perf-overview-tabs button {
                flex: 1 1 0;
                min-width: 0;
                white-space: normal;
            }
        }

        .company-perf-page .perf-overview-tabs button[aria-pressed="true"] {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
        }

        .company-perf-page .perf-overview-tabs button:focus-visible,
        .company-perf-page .perf-workforce-grid .perf-card:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: -2px;
        }

        .company-perf-page .perf-overview-group {
            display: grid;
            gap: .75rem;
            min-width: 0;
        }

        .company-perf-page .perf-overview-note {
            color: var(--mu);
            font-size: .875rem;
        }

        .company-perf-page .perf-header-context {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem 1rem;
        }

        .company-perf-page .perf-context-list {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .company-perf-page .perf-context-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .6rem;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--bd);
            color: var(--mu);
            font-size: .775rem;
            font-weight: 700;
        }

        .company-perf-page .perf-filters {
            container: performance-filters / inline-size;
        }

        .company-perf-page .perf-filters .fb-grid {
            --filter-gap: 0.85rem;
            --filter-width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: var(--filter-gap);
        }

        .company-perf-page .perf-filters .fb-grid > .fb-col {
            flex: 1 1 var(--filter-width);
        }

        .company-perf-page .perf-filters .fb-grid > .fb-search-wrap {
            flex: 0 0 100%;
        }

        @container performance-filters (min-width: 28rem) {
            .company-perf-page .perf-filters .fb-grid {
                --filter-width: calc((100% - var(--filter-gap)) / 2);
            }
        }

        @container performance-filters (min-width: 52rem) {
            .company-perf-page .perf-filters .fb-grid {
                --filter-width: calc((100% - 3 * var(--filter-gap)) / 4);
            }
        }

        .company-perf-page .perf-kpi-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(min(170px, 100%), 1fr));
        }

        @media (min-width: 1280px) {
            .company-perf-page .perf-kpi-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        .company-perf-page .perf-operations-kpi-grid,
        .company-perf-page .perf-kpi-grid.perf-operations-kpi-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 640px) {
            .company-perf-page .perf-operations-kpi-grid,
            .company-perf-page .perf-kpi-grid.perf-operations-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .company-perf-page .perf-operations-kpi-grid,
            .company-perf-page .perf-kpi-grid.perf-operations-kpi-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .company-perf-page .perf-workforce-grid {
            grid-template-columns: repeat(auto-fill, minmax(min(145px, 100%), 1fr));
        }

        .company-perf-page .perf-financial-grid,
        .company-perf-page .perf-workforce-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .company-perf-page .perf-financial-grid .perf-card,
        .company-perf-page .perf-workforce-grid .perf-card {
            flex: 1 1 11rem;
            min-inline-size: min(100%, 11rem);
            display: flex;
            flex-direction: column;
            padding: 14px;
        }

        .company-perf-page .perf-financial-grid .perf-card-hint,
        .company-perf-page .perf-workforce-grid .perf-card-hint {
            flex: 1;
        }

        .company-perf-page .perf-financial-grid .perf-card[aria-expanded="true"],
        .company-perf-page .perf-financial-grid .perf-card:focus-visible,
        .company-perf-page .perf-workforce-grid .perf-card[aria-expanded="true"],
        .company-perf-page .perf-workforce-grid .perf-card:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: -2px;
        }

        .company-perf-page .perf-financial-preview {
            margin-block-start: 12px;
            padding: 14px;
            border: 1px solid var(--bd);
            border-radius: 16px;
            background: var(--card);
        }

        .company-perf-page .perf-financial-preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .company-perf-page .perf-financial-preview-header h3 {
            font-weight: 700;
        }
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
                opacity: 1;
                transform: none;
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

        .company-perf-page .perf-ring-item.is-clickable:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: 2px;
            border-radius: 8px;
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

        /* Performance Score Panel (0-100) */
        .company-perf-page .perf-score-panel {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 1.6fr;
            gap: 14px;
            margin-top: 4px;
        }

        @media (max-width: 820px) {
            .company-perf-page .perf-score-panel {
                grid-template-columns: 1fr;
            }
        }

        .company-perf-page .perf-score-rules {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--bd, #e4e8f3);
        }

        .dark .company-perf-page .perf-score-rules {
            background: rgba(255, 255, 255, 0.03);
        }

        .company-perf-page .perf-score-rules-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .company-perf-page .perf-score-rules-header .ic {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--a) 14%, transparent);
            color: var(--a);
        }

        .company-perf-page .perf-score-rules ul {
            margin: 0;
            padding: 0 1.1rem 0 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.76rem;
            line-height: 1.55;
            color: var(--mu, #6b7490);
        }

        .company-perf-page .perf-score-partial-note {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            color: var(--mu, #6b7490);
            border-top: 1px dashed var(--bd, #e4e8f3);
            padding-top: 10px;
        }

        .company-perf-page .perf-score-main {
            display: flex;
            align-items: stretch;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--bd, #e4e8f3);
        }

        .dark .company-perf-page .perf-score-main {
            background: rgba(255, 255, 255, 0.03);
        }

        .company-perf-page .perf-score-ring-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 150px;
        }

        .company-perf-page .perf-score-of {
            font-size: 0.7rem;
            color: var(--mu, #6b7490);
        }

        .company-perf-page .perf-score-status {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--col, var(--mu, #6b7490));
            text-align: center;
        }

        .company-perf-page .perf-score-deductions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }

        .company-perf-page .perf-score-deductions-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--mu, #6b7490);
        }

        .company-perf-page .perf-score-deduction {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1px solid var(--bd, #e4e8f3);
            background: transparent;
            font-size: 0.78rem;
            text-align: start;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }

        .company-perf-page .perf-score-deduction:hover {
            background: color-mix(in srgb, var(--a) 8%, transparent);
            border-color: color-mix(in srgb, var(--a) 30%, transparent);
        }

        .company-perf-page .perf-score-deduction-name {
            font-weight: 600;
        }

        .company-perf-page .perf-score-deduction-math {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--mu, #6b7490);
        }

        .company-perf-page .perf-score-deduction-points {
            font-size: 0.82rem;
        }

        .company-perf-page .perf-score-no-deductions {
            font-size: 0.76rem;
            color: var(--mu, #6b7490);
        }

        .company-perf-page .perf-score-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            font-weight: 700;
            border-top: 1px dashed var(--bd, #e4e8f3);
            padding-top: 8px;
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

        .company-perf-page .perf-operational-rates {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 0.75rem;
        }

        .company-perf-page .perf-operational-rates:not(.perf-operations-rate-card) {
            padding: 8px 0;
            border-block: 1px solid var(--bd);
            margin-block: 4px;
        }

        .company-perf-page .perf-operational-rates .ring.m,
        .company-perf-page .perf-metric-rate .ring.m {
            inline-size: min(76px, 100%);
            block-size: auto;
            aspect-ratio: 1;
        }

        .company-perf-page .perf-operational-rates .ring.m i,
        .company-perf-page .perf-metric-rate .ring.m i {
            inline-size: 76%;
            block-size: 76%;
        }

        .company-perf-page .perf-operational-rates .perf-ring-label,
        .company-perf-page .perf-operational-rates .perf-ring-sublabel,
        .company-perf-page .perf-metric-rate .perf-ring-label,
        .company-perf-page .perf-metric-rate .perf-ring-sublabel {
            overflow-wrap: anywhere;
            direction: inherit;
        }

        .company-perf-page .perf-metric-with-rate {
            flex-wrap: wrap;
        }

        .company-perf-page .perf-metric-rate {
            max-inline-size: min(100%, 8rem);
        }

        .company-perf-page .perf-operations-rate-card {
            align-content: center;
        }

        @media (min-width: 640px) {
            .company-perf-page .perf-operations-rate-card {
                grid-column: span 2;
            }
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

        .company-perf-page .perf-company-hero-card {
            --hero-panel: #ffffff;
            --hero-hover: #e2e8f0;
            --hero-border: #cbd5e1;
            --hero-muted: #475569;
            --hero-positive: #166534;
            --hero-caution: #854d0e;
            --hero-danger: #9f2936;
            --hero-danger-bg: #fff1f2;
            --hero-danger-border: #e9b8bd;
            background: #f1f5f9;
            border: 1px solid var(--hero-border);
            border-radius: 18px;
            padding: 1.5rem 1.75rem;
            color: #182c43;
            box-shadow: 0 8px 22px -14px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            animation: perfUp 0.35s ease;
        }

        .dark .company-perf-page .perf-company-hero-card {
            --hero-panel: #253347;
            --hero-hover: #33445a;
            --hero-border: #45566d;
            --hero-muted: #cbd5e1;
            --hero-positive: #86d5a5;
            --hero-caution: #f2cb83;
            --hero-danger: #f6a7b0;
            --hero-danger-bg: #432d39;
            --hero-danger-border: #825460;
            background: #192536;
            color: #e9f0f8;
            box-shadow: 0 8px 22px -14px rgba(0, 0, 0, 0.55);
        }

        .company-perf-page .perf-hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--hero-border);
            padding-bottom: 0.85rem;
        }

        .company-perf-page .perf-hero-identity {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .company-perf-page .perf-hero-badge-wrap {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-perf-page .perf-hero-pill {
            font-size: 0.725rem;
            font-weight: 700;
            color: var(--hero-muted);
            letter-spacing: 0.02em;
        }

        .company-perf-page .perf-hero-company-name {
            font-size: 1.35rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.25;
        }

        .company-perf-page .perf-hero-subtitle {
            font-size: 0.785rem;
            color: var(--hero-muted);
            margin: 0;
        }

        .company-perf-page .perf-hero-back-link {
            all: unset;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--hero-panel);
            border: 1px solid var(--hero-border);
            color: inherit;
            font-size: 0.75rem;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .company-perf-page .perf-hero-back-link:hover {
            background: var(--hero-hover);
        }

        .company-perf-page .perf-hero-body {
            display: grid;
            grid-template-columns: minmax(180px, 220px) 1fr;
            gap: 1.5rem;
            align-items: center;
        }

        @media (max-width: 768px) {
            .company-perf-page .perf-hero-body {
                grid-template-columns: 1fr;
            }
        }

        .company-perf-page .perf-hero-score-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background: var(--hero-panel);
            border: 1px solid var(--hero-border);
            border-radius: 14px;
            padding: 1rem;
        }

        .company-perf-page .perf-hero-score-of {
            font-size: 0.7rem;
            color: var(--hero-muted);
        }

        .company-perf-page .perf-hero-score-status {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--hero-muted);
            text-align: center;
        }

        .company-perf-page .perf-hero-score-status.review {
            background: var(--hero-danger-bg);
            color: var(--hero-danger);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            border: 1px solid var(--hero-danger-border);
        }

        .company-perf-page .perf-hero-score-status.good {
            color: var(--hero-positive);
        }

        .company-perf-page .perf-hero-score-status.caution {
            color: var(--hero-caution);
        }

        .company-perf-page .perf-hero-deductions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .company-perf-page .perf-hero-deductions-title {
            font-size: 0.75rem;
            font-weight: 700;
        }

        .company-perf-page .perf-hero-deductions-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
        }

        .company-perf-page .perf-hero-deduction {
            all: unset;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            background: var(--hero-panel);
            border: 1px solid var(--hero-border);
            font-size: 0.75rem;
            color: inherit;
            transition: background 0.15s, border-color 0.15s;
        }

        .company-perf-page .perf-hero-deduction:hover {
            background: var(--hero-hover);
        }

        .company-perf-page .perf-hero-deduction.active {
            border-color: var(--hero-danger-border);
            background: var(--hero-danger-bg);
        }

        .company-perf-page .perf-hero-deduction-math {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 700;
        }

        .company-perf-page .perf-hero-deduction-points {
            color: var(--hero-positive);
            font-size: 0.825rem;
        }

        .company-perf-page .perf-hero-deduction-points.active {
            color: var(--hero-danger);
        }

        .company-perf-page .perf-hero-total-deduction {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.785rem;
            padding: 0.35rem 0.5rem;
            border-top: 1px dashed var(--hero-border);
        }

        .company-perf-page .perf-hero-no-deductions {
            font-size: 0.75rem;
            color: var(--hero-positive);
            font-weight: 600;
        }

        .company-perf-page .perf-hero-note {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            color: var(--hero-muted);
        }

        .company-perf-page .perf-hero-score-rules {
            font-size: 0.8rem;
        }

        .company-perf-page .perf-hero-score-rules summary {
            width: fit-content;
            padding-block: 0.45rem;
            cursor: pointer;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .company-perf-page .perf-hero-score-rules summary:focus-visible,
        .company-perf-page .perf-hero-back-link:focus-visible,
        .company-perf-page .perf-hero-deduction:focus-visible {
            outline: 2px solid currentColor;
            outline-offset: 2px;
        }

        .company-perf-page .perf-hero-score-rules ul {
            padding-inline-start: 1.25rem;
            list-style: disc;
            line-height: 1.6;
        }

        .company-perf-page .perf-hero-metrics-section {
            border-top: 1px solid var(--hero-border);
            padding-top: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .company-perf-page .perf-hero-metrics-title {
            font-size: 0.75rem;
            font-weight: 700;
        }

        .company-perf-page .perf-hero-rings {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1.25rem;
            background: var(--hero-panel);
            border: 1px solid var(--hero-border);
            border-radius: 14px;
            padding: 0.85rem 1rem;
        }

        .company-perf-page .perf-company-hero-card .perf-ring-label,
        .company-perf-page .perf-company-hero-card .perf-ring-sublabel {
            color: var(--hero-muted);
        }

        /* Sprint Track (Company Departments Leadership) */
        .company-perf-page .perf-sprint-card {
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            box-shadow: var(--sh);
            margin-top: 0.5rem;
            animation: perfUp 0.35s ease;
        }

        .company-perf-page .perf-sprint-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.85rem;
            border-bottom: 1px solid var(--bd);
            padding-bottom: 0.65rem;
        }

        .company-perf-page .perf-sprint-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .company-perf-page .perf-sprint-icon {
            font-size: 1.35rem;
            line-height: 1;
        }

        .company-perf-page .perf-sprint-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--tx);
            margin: 0;
        }

        .company-perf-page .perf-sprint-subtitle {
            font-size: 0.725rem;
            color: var(--mu);
            margin: 0;
        }

        .company-perf-page .perf-sprint-lanes {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .company-perf-page .perf-sprint-row {
            all: unset;
            cursor: pointer;
            box-sizing: border-box;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.85rem;
            border-radius: 12px;
            background: var(--bg);
            border: 1px solid var(--bd);
            transition: background 0.18s, transform 0.15s, border-color 0.15s;
        }

        .company-perf-page .perf-sprint-row:hover {
            background: color-mix(in srgb, var(--a, #4f46e5) 8%, var(--bg));
            border-color: color-mix(in srgb, var(--a, #4f46e5) 30%, var(--bd));
            transform: translateX(-2px);
        }

        .company-perf-page .perf-sprint-row:focus-visible {
            outline: 2px solid var(--a, #4f46e5);
            outline-offset: 2px;
        }

        .company-perf-page .perf-sprint-rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.85rem;
            background: var(--card);
            border: 1px solid var(--bd);
            flex-shrink: 0;
        }

        .company-perf-page .perf-sprint-crown {
            font-size: 1.1rem;
            line-height: 1;
        }

        .company-perf-page .perf-sprint-rank-num {
            color: var(--mu);
            font-size: 0.785rem;
            font-weight: 800;
        }

        .company-perf-page .perf-sprint-dept-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            width: 150px;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .company-perf-page .perf-sprint-dept-info {
                width: 100px;
            }
        }

        .company-perf-page .perf-sprint-dept-name {
            font-weight: 700;
            font-size: 0.825rem;
            color: var(--tx);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .company-perf-page .perf-sprint-tag {
            font-size: 0.685rem;
            line-height: 1.2;
        }

        .company-perf-page .perf-sprint-leader-label {
            color: #d97706;
            font-weight: 700;
        }

        .company-perf-page .perf-sprint-diff-label {
            color: var(--mu);
            font-weight: 600;
        }

        .company-perf-page .perf-sprint-diff-label.is-tied {
            color: #059669;
        }

        .company-perf-page .perf-sprint-track-bar {
            flex: 1;
            height: 22px;
            border-radius: 999px;
            background: var(--card);
            border: 1px solid var(--bd);
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .company-perf-page .perf-sprint-track-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
            min-width: 6px;
        }

        .company-perf-page .perf-sprint-row.is-excellent .perf-sprint-track-fill {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .company-perf-page .perf-sprint-row.is-good .perf-sprint-track-fill {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .company-perf-page .perf-sprint-row.is-review .perf-sprint-track-fill {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .company-perf-page .perf-sprint-score-badge {
            width: 55px;
            text-align: end;
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--tx);
            flex-shrink: 0;
            direction: ltr;
        }

        .company-perf-page .perf-sprint-score-badge small {
            font-size: 0.675rem;
            color: var(--mu);
            font-weight: 500;
        }

        @media (prefers-reduced-motion: reduce) {
            .company-perf-page .perf-sprint-track-fill {
                transition: none !important;
            }
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

        /* 3-Columns Department Overview Grid & Card Refinements */
        .company-perf-page .dept-grid-style--3 {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .company-perf-page .dept-grid-style--3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1100px) {
            .company-perf-page .dept-grid-style--3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .company-perf-page .dept-overview-card {
            padding: 20px 18px;
            gap: 14px;
        }

        .company-perf-page .dept-card-score-badge.dept-overview-lowest-badge {
            width: 100%;
            justify-content: space-between;
            border-radius: 12px;
            padding: 7px 12px;
            gap: 10px;
            text-align: start;
            line-height: 1.45;
        }

        .company-perf-page .dept-overview-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px 12px;
            background: color-mix(in srgb, var(--tx) 3%, transparent);
            border: 1px solid var(--bd);
            border-radius: 12px;
        }

        .company-perf-page .dept-overview-stats .perf-department-service-share + .perf-department-service-share {
            border-top: 1px dashed var(--bd);
            padding-top: 8px;
        }

        .company-perf-page .dept-overview-stat-val.has-overdue {
            color: #ef4444;
        }

        /* Department display mode buttons */
        .company-perf-page .perf-rank-modes {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            padding: 5px;
            background: var(--card);
            border: 1px solid var(--bd);
            border-radius: 12px;
            box-shadow: var(--sh);
        }

        .company-perf-page .perf-rank-modes-title {
            padding-inline: 8px;
            color: var(--mu);
            font-size: 12px;
            font-weight: 700;
        }

        .company-perf-page .perf-rank-mode {
            font-family: inherit;
            padding: .35rem .85rem;
            border-radius: 9px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--mu);
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .company-perf-page .perf-rank-mode:hover {
            background: var(--bg);
            color: var(--tx);
        }

        .company-perf-page .perf-rank-mode.is-active {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-color: transparent;
            color: #ffffff;
        }

        .company-perf-page .perf-rank-mode:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: -2px;
        }

        .company-perf-page .dept-score-pill {
            display: inline-flex;
            align-items: center;
            align-self: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--col, var(--a)) 35%, transparent);
            background: color-mix(in srgb, var(--col, var(--a)) 12%, transparent);
            color: var(--col, var(--a));
            font-size: 11.5px;
            font-weight: 700;
        }

        .company-perf-page .dept-score-pill b {
            font-size: 14px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .company-perf-page .dept-score-pill u {
            text-decoration: none;
            font-size: 10.5px;
            font-weight: 600;
            opacity: 0.85;
        }

        .company-perf-page .dept-card-score-row {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 0;
        }

        .company-perf-page .dept-card-score-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            background: color-mix(in srgb, var(--tx) 4%, transparent);
            border: 1px solid var(--bd);
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .company-perf-page .dept-card-score-badge.needs-review {
            border-color: color-mix(in srgb, #ef4444 35%, transparent);
            background: color-mix(in srgb, #ef4444 8%, transparent);
        }

        .company-perf-page .dept-card-score-label {
            color: var(--mu);
            font-weight: 600;
            font-size: 11px;
        }

        .company-perf-page .dept-card-score-val {
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .company-perf-page .dept-card-score-warn {
            font-size: 11px;
            line-height: 1;
        }

        .company-perf-page .dept-card-score-empty {
            color: var(--mu);
            font-size: 11.5px;
            font-weight: 500;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
            font-size: 13px;
            color: var(--mu);
            font-weight: 500;
            line-height: 1.4;
        }

        .company-perf-page .dept-overview-scope {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-block-start: 2px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
        }

        .company-perf-page .dept-overview-scope svg {
            width: 16px;
            height: 16px;
            flex: none;
        }

        .company-perf-page .dept-overview-scope--single {
            background: #dbeafe;
            color: #1e40af;
        }

        .company-perf-page .dept-overview-scope--shared {
            background: #ede9fe;
            color: #5b21b6;
        }

        .company-perf-page .dept-overview-scope--group {
            background: #d1fae5;
            color: #065f46;
        }

        .company-perf-page .dept-overview-affiliation {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 8px;
            color: var(--tx);
            overflow-wrap: anywhere;
            font-size: 13px;
            line-height: 1.45;
        }

        .company-perf-page .dept-card-header .dept-overview-affiliation {
            justify-content: center;
        }

        .company-perf-page .dept-overview-affiliation button {
            color: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .company-perf-page .dept-overview-company-count {
            color: var(--tx);
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
        }

        .company-perf-page .dept-lead-role {
            font-size: 11px;
            color: var(--mu);
            opacity: 0.85;
        }

        .company-perf-page .perf-department-service-share {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 8px;
            font-size: 0.75rem;
            color: var(--mu);
        }

        .company-perf-page .perf-department-service-share small {
            display: block;
            font-size: 0.65rem;
        }

        .company-perf-page .perf-department-service-share strong {
            color: var(--tx);
        }

        .company-perf-page .dept-card-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .company-perf-page .dept-btn {
            border: 1px solid transparent;
            border-radius: 10px;
            min-height: 44px;
            padding: 8px 12px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            transition: background 0.18s ease, border-color 0.18s ease;
        }

        .company-perf-page .dept-btn svg {
            width: 1.1rem;
            height: 1.1rem;
            flex-shrink: 0;
        }

        .company-perf-page .dept-btn.primary {
            background: #284763;
            color: #ffffff;
        }

        .company-perf-page .dept-btn.primary:hover {
            background: #1d364e;
        }

        .company-perf-page .dept-btn.secondary {
            background: #f7fafc;
            border-color: #7c8fa3;
            color: #263c53;
        }

        .company-perf-page .dept-btn.secondary:hover {
            background: #e8eff6;
            border-color: #50708e;
        }

        .company-perf-page .dept-btn:focus-visible {
            outline: 2px solid #3b6a98;
            outline-offset: 2px;
        }

        .dark .company-perf-page .dept-btn.primary {
            background: #335472;
        }

        .dark .company-perf-page .dept-btn.primary:hover {
            background: #416688;
        }

        .dark .company-perf-page .dept-btn.secondary {
            background: #253347;
            border-color: #8498ae;
            color: #e9f0f8;
        }

        .dark .company-perf-page .dept-btn.secondary:hover {
            background: #33445a;
        }

        .dark .company-perf-page .dept-btn:focus-visible {
            outline-color: #90b8df;
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

        .company-perf-page .perf-violations-overview {
            container: performance-violations / inline-size;
        }

        .company-perf-page .perf-violation-status-grid,
        .company-perf-page .perf-violation-totals-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
        }

        .company-perf-page .perf-violations-overview .perf-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            grid-template-rows: auto minmax(0, 1fr) auto;
            min-inline-size: 0;
        }

        .company-perf-page .perf-violation-totals-grid .perf-metric-value-row {
            flex-wrap: wrap;
        }

        .company-perf-page .perf-violation-total-amount {
            display: grid;
            gap: 0.25rem;
        }

        .company-perf-page .perf-violation-total-amount span {
            font-size: 0.75rem;
            color: var(--mu);
        }

        .company-perf-page .perf-violation-total-amount bdi {
            font-weight: 700;
            color: var(--tx);
        }

        .company-perf-page .perf-violation-status-grid .perf-card-header,
        .company-perf-page .perf-violation-totals-grid .perf-card-header {
            min-block-size: 2.75rem;
        }

        @container performance-violations (min-width: 22rem) {
            .company-perf-page .perf-violation-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @container performance-violations (min-width: 36rem) {
            .company-perf-page .perf-violation-totals-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @container performance-violations (min-width: 49rem) {
            .company-perf-page .perf-violation-status-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .company-perf-page .perf-tickets-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 640px) {
            .company-perf-page .perf-tickets-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .company-perf-page .perf-tickets-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
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

        .company-perf-page .perf-compliance-table {
            table-layout: fixed;
        }

        .company-perf-page .perf-compliance-table thead th:first-child {
            width: 28%;
        }

        .company-perf-page .perf-compliance-table th {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .company-perf-page .perf-compliance-table th:not(:first-child),
        .company-perf-page .perf-compliance-table td {
            text-align: center;
        }

        .company-perf-page .perf-compliance-table th,
        .company-perf-page .perf-compliance-table td {
            padding: .625rem .875rem;
        }

        .company-perf-page .perf-compliance-table-scroll:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: 2px;
        }

        .company-perf-page .perf-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
            box-sizing: border-box;
            min-inline-size: 4.5rem;
            min-block-size: 2rem;
            padding: .25rem .625rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--pill, var(--mu)) 35%, transparent);
            background: color-mix(in srgb, var(--pill, var(--mu)) 12%, transparent);
            color: var(--pill, var(--mu));
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1.4;
        }

        .company-perf-page .perf-status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            flex: none;
        }

        .company-perf-page button.perf-status-pill {
            cursor: pointer;
        }

        .company-perf-page button.perf-status-pill:hover {
            border-color: var(--pill);
            background: color-mix(in srgb, var(--pill) 18%, transparent);
        }

        .company-perf-page .perf-status-pill:focus-visible {
            outline: 2px solid var(--a);
            outline-offset: 1px;
        }

        .company-perf-page .perf-status-chevron {
            inline-size: .75rem;
            block-size: .75rem;
            flex: none;
        }

        .company-perf-page .perf-status-chevron:dir(ltr) {
            transform: scaleX(-1);
        }

        .company-perf-page .perf-status-pill.is-zero {
            border-color: var(--bd);
            background: var(--bg);
        }

        .company-perf-page .perf-row-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .company-perf-page .perf-row-label svg {
            color: var(--a);
            flex: none;
        }

        @media (max-width: 640px) {
            .company-perf-page .perf-compliance-table {
                min-width: 32rem;
                border-collapse: separate;
                border-spacing: 0;
            }

            .company-perf-page .perf-compliance-table thead th:first-child {
                width: 9rem;
            }

            .company-perf-page .perf-compliance-table th,
            .company-perf-page .perf-compliance-table td {
                padding: .625rem .5rem;
            }

            .company-perf-page .perf-compliance-table th:first-child {
                position: sticky;
                inset-inline-start: 0;
                z-index: 1;
                text-align: start;
                border-inline-end: 1px solid var(--bd);
            }
        }

        /* Simplified Executive Summary Tab */
        .company-perf-page .perf-summary-shell {
            display: grid;
            gap: 1.25rem;
        }

        .company-perf-page .perf-summary-hero {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.25rem;
            padding: 1.5rem;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--card) 0%, var(--bg) 100%);
            border: 1px solid var(--bd);
            box-shadow: var(--sh);
        }

        @media (max-width: 960px) {
            .company-perf-page .perf-summary-hero {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        .company-perf-page .perf-summary-hero-side,
        .company-perf-page .perf-summary-hero-meta {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        @media (max-width: 960px) {
            .company-perf-page .perf-summary-hero-side,
            .company-perf-page .perf-summary-hero-meta {
                align-items: center;
            }
        }

        .company-perf-page .perf-summary-back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            width: fit-content;
            padding: .35rem .75rem;
            border-radius: 999px;
            border: 1px solid var(--bd);
            background: var(--card);
            color: var(--tx);
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .company-perf-page .perf-summary-back:hover {
            border-color: var(--a);
            color: var(--a);
        }

        .company-perf-page .perf-summary-greeting {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--tx);
        }

        .company-perf-page .perf-summary-scope {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .45rem;
        }

        .company-perf-page .perf-summary-scope-badge {
            display: inline-flex;
            align-items: center;
            padding: .25rem .65rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--a) 12%, transparent);
            color: var(--a);
            font-size: .8rem;
            font-weight: 700;
        }

        .company-perf-page .perf-summary-scope-companies {
            font-size: .8rem;
            color: var(--mu);
        }

        .company-perf-page .perf-summary-period {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            color: var(--mu);
            font-weight: 600;
        }

        .company-perf-page .perf-summary-hero-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .45rem;
        }

        .company-perf-page .perf-summary-status {
            display: inline-flex;
            align-items: center;
            padding: .25rem .8rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
            background: color-mix(in srgb, var(--col, var(--mu)) 14%, transparent);
            color: var(--col, var(--mu));
        }

        .company-perf-page .perf-summary-score-note,
        .company-perf-page .perf-summary-legend {
            font-size: .75rem;
            color: var(--mu);
            margin: 0;
            max-width: 26ch;
            text-align: center;
        }

        .company-perf-page .perf-summary-legend {
            text-align: start;
            max-width: none;
        }

        .company-perf-page .perf-summary-meta-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .5rem .85rem;
            border-radius: 12px;
            background: var(--card);
            border: 1px solid var(--bd);
            font-size: .84rem;
        }

        .company-perf-page .perf-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 1120px) {
            .company-perf-page .perf-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .company-perf-page .perf-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        .company-perf-page .perf-summary-card {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: .85rem;
            padding: 1.1rem;
            border-radius: 16px;
            background: var(--card);
            border: 1px solid var(--bd);
            box-shadow: var(--sh);
        }

        .company-perf-page .perf-summary-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .company-perf-page .perf-summary-card-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
        }

        .company-perf-page .perf-summary-card-title-wrap h3 {
            margin: 0;
            font-size: .95rem;
            font-weight: 800;
            color: var(--tx);
        }

        .company-perf-page .perf-summary-tag {
            display: inline-block;
            margin-top: .2rem;
            font-size: .72rem;
            color: var(--mu);
        }

        .company-perf-page .perf-summary-icon {
            display: grid;
            place-items: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 12px;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--ic, var(--a)) 14%, transparent);
            color: var(--ic, var(--a));
        }

        .company-perf-page .perf-summary-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
        }

        .company-perf-page .perf-summary-metrics > .perf-summary-metric-btn:last-child:nth-child(odd) {
            grid-column: 1 / -1;
        }

        .company-perf-page .perf-summary-metrics.is-stacked {
            grid-template-columns: 1fr;
            flex: 1;
            align-content: stretch;
            gap: .55rem;
        }

        .company-perf-page .perf-summary-metrics.is-stacked > .perf-summary-metric-btn {
            padding: .65rem .85rem;
        }

        .company-perf-page .perf-summary-split {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: .75rem;
        }

        .company-perf-page .perf-summary-compliance-docs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .5rem;
        }

        @media (max-width: 520px) {
            .company-perf-page .perf-summary-compliance-docs {
                grid-template-columns: 1fr;
            }
        }

        .company-perf-page .perf-summary-doc-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            padding: .5rem .4rem;
            border-radius: 12px;
            background: var(--bg);
            border: 1px solid var(--bd);
        }

        .company-perf-page .perf-summary-doc-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .25rem;
            width: 100%;
        }

        .company-perf-page .perf-summary-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .45rem;
            border-radius: 999px;
            border: 1px solid var(--bd);
            background: var(--card);
            color: var(--tx);
            font-size: .7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .company-perf-page .perf-summary-chip-btn:hover {
            border-color: var(--a);
        }

        .company-perf-page .perf-summary-chip-btn.is-danger {
            color: var(--d);
        }

        .company-perf-page .perf-summary-chip-btn.is-warn {
            color: var(--c);
        }

        .company-perf-page .perf-summary-license-list {
            display: grid;
            gap: .35rem;
        }

        .company-perf-page .perf-summary-license-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .35rem .6rem;
            padding: .35rem .55rem;
            border-radius: 10px;
            background: var(--bg);
            border: 1px solid var(--bd);
            font-size: .76rem;
        }

        .company-perf-page .perf-summary-license-name {
            font-weight: 700;
            color: var(--tx);
        }

        .company-perf-page .perf-summary-license-rate {
            font-weight: 800;
            color: var(--a);
        }

        .company-perf-page .perf-summary-subblock,
        .company-perf-page .perf-summary-mini-actions {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .company-perf-page .perf-summary-subblock {
            height: 100%;
        }

        .company-perf-page .perf-summary-subblock > .perf-summary-metric-btn {
            margin-top: auto;
        }

        .company-perf-page .perf-summary-metric-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .45rem .65rem;
            border-radius: 10px;
            border: 1px solid var(--bd);
            background: var(--bg);
            color: var(--tx);
            font-size: .78rem;
            cursor: pointer;
            text-align: start;
        }

        .company-perf-page .perf-summary-metric-btn:hover {
            border-color: var(--a);
        }

        .company-perf-page .perf-summary-metric-btn.is-warn strong {
            color: var(--c);
        }

        .company-perf-page .perf-summary-metric-btn.is-danger strong {
            color: var(--d);
        }

        .company-perf-page .perf-summary-subs {
            padding-top: .5rem;
            border-top: 1px solid var(--bd);
            display: grid;
            gap: .4rem;
        }

        .company-perf-page .perf-summary-subs-title {
            font-size: .76rem;
            font-weight: 700;
            color: var(--mu);
        }

        .company-perf-page .perf-summary-subs-row {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .company-perf-page .perf-summary-sub-chip {
            padding: .25rem .55rem;
            border-radius: 8px;
            background: var(--bg);
            border: 1px solid var(--bd);
            font-size: .74rem;
        }

        .company-perf-page .perf-summary-sub-chip .is-expired {
            color: var(--d);
            font-weight: 700;
        }

        .company-perf-page .perf-summary-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .4rem 1rem;
            margin-bottom: .75rem;
        }

        .company-perf-page .perf-summary-section-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
        }

        .company-perf-page .perf-summary-section-head span {
            font-size: .8rem;
            color: var(--mu);
        }

        .company-perf-page .perf-summary-dept-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        @media (max-width: 1120px) {
            .company-perf-page .perf-summary-dept-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .company-perf-page .perf-summary-dept-grid {
                grid-template-columns: 1fr;
            }
        }

        .company-perf-page .perf-summary-dept-card {
            display: grid;
            gap: .5rem;
            padding: .95rem 1rem;
            border-radius: 14px;
            background: var(--card);
            border: 1px solid var(--bd);
            box-shadow: var(--sh);
            text-align: start;
            cursor: pointer;
        }

        .company-perf-page .perf-summary-dept-card:hover {
            border-color: var(--a);
        }

        .company-perf-page .perf-summary-dept-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .company-perf-page .perf-summary-dept-score {
            padding: .2rem .55rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--a) 12%, transparent);
            color: var(--a);
            font-size: .82rem;
            font-weight: 800;
        }

        .company-perf-page .perf-summary-dept-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .85rem;
            font-size: .78rem;
            color: var(--mu);
        }
    </style>

    <div class="company-perf-page">
        <div class="perf-header-context">
            <p class="perf-overview-note">{{ __('performance.subtitle') }}</p>
            <div class="perf-context-list">
                <div class="perf-context-chip">
                    <x-heroicon-m-calendar style="width: 15px; height: 15px;" />
                    <span><bdi>{{ $this->dateFrom ?? '—' }}</bdi> &larr; <bdi>{{ $this->dateTo ?? '—' }}</bdi></span>
                </div>
                <div class="perf-context-chip">
                    <x-heroicon-m-building-office-2 style="width: 15px; height: 15px;" />
                    <span>{{ count($this->companyFilterOptions) }} {{ __('performance.columns.company') }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="perf-filters" wire:key="performance-filters-{{ implode('-', (array) $this->companyId) }}">
            <x-filter-bar
                :title="__('performance.filters.title')"
                :show-company="true"
                :companies="$this->companyFilterOptions"
                :company-label="__('performance.filters.company')"
                :show-branch="true"
                :branches="$this->branchFilterOptions"
                :branch-label="__('performance.filters.branch')"
                :show-dates="true"
                :date-from-label="__('performance.filters.date_from')"
                :date-to-label="__('performance.filters.date_to')"
                :show-search="in_array($this->activeOverviewTab, ['summary', 'operations'], true)"
                :search-placeholder="__('performance.filters.search')"
                :has-active="$this->hasActiveFilterBar()"
            />
        </div>

        <nav class="perf-overview-tabs" aria-label="{{ __('performance.overview.navigation') }}">
            <button type="button" wire:click="showSummaryOverview" aria-pressed="{{ $this->activeOverviewTab === 'summary' ? 'true' : 'false' }}">
                {{ __('performance.overview.summary') }}
            </button>
            <button type="button" wire:click="showOperationsOverview" aria-pressed="{{ $this->activeOverviewTab === 'operations' ? 'true' : 'false' }}">
                {{ __('performance.overview.operations') }}
            </button>
            @if($this->canViewWorkforceOverview)
                <button type="button" wire:click="showWorkforceOverview" aria-pressed="{{ $this->activeOverviewTab === 'workforce' ? 'true' : 'false' }}">
                    {{ __('performance.overview.workforce') }}
                </button>
            @endif
            @if($this->canViewComplianceOverview)
                <button type="button" wire:click="showComplianceOverview" aria-pressed="{{ $this->activeOverviewTab === 'compliance' ? 'true' : 'false' }}">
                    {{ __('performance.overview.compliance') }}
                </button>
            @endif
            @if($this->canViewViolationsOverview)
                <button type="button" wire:click="showViolationsOverview" aria-pressed="{{ $this->activeOverviewTab === 'violations' ? 'true' : 'false' }}">
                    {{ __('performance.overview.violations') }}
                </button>
            @endif
            @if($this->canViewFinancialOverview)
                <button type="button" wire:click="showFinancialOverview" aria-pressed="{{ $this->activeOverviewTab === 'financial' ? 'true' : 'false' }}">
                    {{ __('performance.overview.financial') }}
                </button>
            @endif
            @if($this->canViewTicketsOverview)
                <button type="button" wire:click="showTicketsOverview" aria-pressed="{{ $this->activeOverviewTab === 'tickets' ? 'true' : 'false' }}">
                    {{ __('performance.overview.tickets') }}
                </button>
            @endif
        </nav>

        @if($this->activeOverviewTab === 'summary')
            @include('filament.pages.company-department-performance.summary-overview')
        @elseif($this->activeOverviewTab === 'workforce' && $this->canViewWorkforceOverview)
            @include('filament.pages.company-department-performance.workforce-overview')
        @elseif($this->activeOverviewTab === 'compliance' && $this->canViewComplianceOverview)
            @include('filament.pages.company-department-performance.compliance-overview')
        @elseif($this->activeOverviewTab === 'violations' && $this->canViewViolationsOverview)
            @include('filament.pages.company-department-performance.violations-overview')
        @elseif($this->activeOverviewTab === 'financial' && $this->canViewFinancialOverview)
            @include('filament.pages.company-department-performance.financial-overview')
        @elseif($this->activeOverviewTab === 'tickets' && $this->canViewTicketsOverview)
            @include('filament.pages.company-department-performance.tickets-overview')
        @else
            @include('filament.pages.company-department-performance.operations-overview')
        @endif
        <!-- ========================================== -->
        <!-- Kashf Modal -->
        <!-- ========================================== -->
        @include('filament.pages.company-department-performance.statement-modal')
    </div>
</x-filament-panels::page>
