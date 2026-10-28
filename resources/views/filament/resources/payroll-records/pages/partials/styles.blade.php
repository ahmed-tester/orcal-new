<style>
    .payroll-container {
        direction: rtl;
        font-family: inherit;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        width: 100%;
    }

    .payroll-container svg {
        width: 1.2rem !important;
        height: 1.2rem !important;
        max-width: 1.2rem !important;
        max-height: 1.2rem !important;
        display: inline-block;
        vertical-align: middle;
        flex-shrink: 0;
    }

    /* ─── Cards & Containers ─── */
    .payroll-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 14px -3px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
        transition: all 0.2s ease;
    }
    .dark .payroll-card {
        background: #111827;
        border-color: #1f2937;
        box-shadow: 0 4px 14px -3px rgba(0, 0, 0, 0.4);
    }

    /* ─── Top Tabs & Nav ─── */
    .payroll-top-tabs {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-bottom: 0.25rem;
    }
    .tabs-nav-group {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 0.85rem;
        border: 1px solid #e2e8f0;
    }
    .dark .tabs-nav-group {
        background: #1e293b;
        border-color: #334155;
    }
    .tab-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1.15rem;
        border-radius: 0.65rem;
        font-weight: 700;
        font-size: 0.85rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .tab-nav-btn-active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
    }
    .tab-nav-btn-inactive {
        background: transparent;
        color: #64748b;
    }
    .dark .tab-nav-btn-inactive {
        color: #94a3b8;
    }
    .tab-nav-btn-inactive:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .tab-nav-btn-inactive:hover {
        background: #334155;
        color: #f8fafc;
    }

    /* ─── Print Action Buttons ─── */
    .print-actions-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1.15rem;
        border-radius: 0.75rem;
        font-size: 0.825rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .btn-print:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .btn-print-navy {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(30, 58, 138, 0.25);
    }
    .btn-print-purple {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25);
    }
    .btn-print-emerald {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
    }
    .btn-print-amber {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);
    }

    /* ─── Scope Filter Pills ─── */
    .scope-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.85rem;
        border-radius: 0.65rem;
        font-size: 0.775rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .scope-pill-btn-active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25);
    }
    .dark .scope-pill-btn-active {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
    }
    .scope-pill-btn-inactive {
        background: #ffffff;
        color: #64748b;
        border-color: #e2e8f0;
    }
    .dark .scope-pill-btn-inactive {
        background: #1e293b;
        color: #94a3b8;
        border-color: #334155;
    }
    .scope-pill-btn-inactive:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .dark .scope-pill-btn-inactive:hover {
        background: #334155;
        color: #ffffff;
    }

    /* ─── Operation Switch ─── */
    .op-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
    }
    .dark .op-switch {
        background: #1e293b;
        border-color: #334155;
    }
    .op-btn {
        border: none;
        border-radius: 9999px;
        padding: 0.45rem 1.15rem;
        font-weight: 700;
        font-size: 0.825rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;
    }
    .op-btn-payroll {
        background: #10b981;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
    }
    .op-btn-collection {
        background: #8b5cf6;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.35);
    }
    .op-btn-inactive {
        background: transparent;
        color: #64748b;
    }
    .dark .op-btn-inactive {
        color: #94a3b8;
    }
    .op-btn-inactive:hover {
        color: #0f172a;
    }
    .dark .op-btn-inactive:hover {
        color: #f8fafc;
    }

    /* ─── Mode Cards Grid ─── */
    .modes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.85rem;
    }
    .mode-box {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.9rem 1.15rem;
        border-radius: 0.85rem;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
        user-select: none;
    }
    .mode-box:hover {
        transform: translateY(-1px);
    }
    .mode-box-active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    .dark .mode-box-active {
        background: rgba(30, 58, 138, 0.35);
        border-color: #3b82f6;
        color: #93c5fd;
    }
    .mode-box-inactive {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #475569;
    }
    .dark .mode-box-inactive {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }
    .mode-box-inactive:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
    .dark .mode-box-inactive:hover {
        border-color: #475569;
        background: #243247;
    }

    /* ─── KPI Dashboard Cards ─── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .dark .stat-card {
        background: #111827;
        border-color: #1f2937;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.1);
    }
    .stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 4px;
    }
    .stat-card-blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stat-card-indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
    .stat-card-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-card-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

    .stat-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .stat-title {
        font-size: 0.775rem;
        font-weight: 700;
        color: #64748b;
    }
    .dark .stat-title {
        color: #94a3b8;
    }
    .stat-num {
        font-size: 1.5rem;
        font-weight: 900;
        font-family: monospace;
        letter-spacing: -0.025em;
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
    }
    .stat-num-blue { color: #2563eb; }
    .dark .stat-num-blue { color: #60a5fa; }
    .stat-num-indigo { color: #4f46e5; }
    .dark .stat-num-indigo { color: #818cf8; }
    .stat-num-emerald { color: #059669; }
    .dark .stat-num-emerald { color: #34d399; }
    .stat-num-amber { color: #d97706; }
    .dark .stat-num-amber { color: #fbbf24; }

    .stat-unit {
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
    }

    .stat-icon-wrap {
        width: 3rem;
        height: 3rem;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-icon-blue { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
    .stat-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #4f46e5; }
    .stat-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .stat-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }

    /* ─── Forms & Inputs ─── */
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.15rem;
        align-items: end;
    }
    .form-control-label {
        display: block;
        font-size: 0.775rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        color: #334155;
    }
    .dark .form-control-label {
        color: #cbd5e1;
    }
    .form-input, .form-select {
        width: 100%;
        border-radius: 0.65rem;
        border: 1px solid #cbd5e1;
        padding: 0.6rem 0.85rem;
        font-size: 0.85rem;
        background: #ffffff;
        color: #0f172a;
        outline: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .dark .form-input, .dark .form-select {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    .form-input:focus, .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* ─── Toolbar & Hints ─── */
    .payroll-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
        box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.04);
    }
    .dark .payroll-toolbar {
        background: #111827;
        border-color: #1f2937;
    }
    .toolbar-hint {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.785rem;
        color: #64748b;
        font-weight: 600;
    }
    .dark .toolbar-hint {
        color: #94a3b8;
    }
    .toolbar-hint-icon {
        font-size: 1rem;
    }

    .btn-save-payroll {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 800;
        font-size: 0.875rem;
        cursor: pointer;
        border: none;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        transition: all 0.2s ease;
    }
    .btn-save-payroll:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
    }
    .btn-save-payroll:disabled {
        opacity: 0.75;
        cursor: not-allowed;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* ─── Interactive Data Table ─── */
    .payroll-table-wrap {
        overflow-x: auto;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 16px -3px rgba(15, 23, 42, 0.06);
    }
    .dark .payroll-table-wrap {
        border-color: #1f2937;
        background: #111827;
        box-shadow: 0 4px 16px -3px rgba(0, 0, 0, 0.5);
    }

    .payroll-table-header-bar {
        padding: 0.9rem 1.25rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
    }
    .dark .payroll-table-header-bar {
        background: #1e293b;
        border-color: #334155;
    }

    .payroll-table {
        width: 100%;
        border-collapse: collapse;
        text-align: right;
        font-size: 0.825rem;
    }
    .payroll-table th {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        font-weight: 700;
        padding: 0.75rem 0.55rem;
        border: 1px solid #334155;
        white-space: nowrap;
        text-align: center;
        font-size: 0.8rem;
        letter-spacing: 0.01em;
    }
    .dark .payroll-table th {
        background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
        border-color: #1e293b;
    }

    .payroll-table td {
        padding: 0.65rem 0.55rem;
        border: 1px solid #f1f5f9;
        color: #1e293b;
        text-align: center;
        vertical-align: middle;
        transition: background-color 0.15s ease;
    }
    .dark .payroll-table td {
        border-color: #1e293b;
        color: #e2e8f0;
    }
    .payroll-table tbody tr:hover td {
        background-color: #f8fafc;
    }
    .dark .payroll-table tbody tr:hover td {
        background-color: #1e293b;
    }

    .payroll-table tfoot td {
        background: #f1f5f9;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
        padding: 0.85rem 0.55rem;
    }
    .dark .payroll-table tfoot td {
        background: #1e293b;
        border-color: #475569;
    }

    /* Badges & Actions */
    .company-pill {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
        font-size: 0.725rem;
        font-weight: 700;
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark .company-pill {
        background: rgba(153, 27, 27, 0.25);
        color: #fca5a5;
        border-color: rgba(153, 27, 27, 0.4);
    }

    .btn-delete-row {
        color: #dc2626;
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 0.5rem;
        padding: 0.4rem 0.55rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .btn-delete-row:hover {
        background: #ef4444;
        border-color: #dc2626;
        color: #ffffff;
        transform: scale(1.05);
    }

    .table-notes-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
        background: #ffffff;
        color: #1e293b;
        outline: none;
        transition: all 0.2s ease;
    }
    .dark .table-notes-input {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    .table-notes-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }

    /* ─── Pagination ─── */
    .payroll-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
        box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.04);
    }
    .dark .payroll-pagination {
        background: #111827;
        border-color: #1f2937;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 0.65rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .page-btn-active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
    }
    .page-btn-inactive {
        background: #f1f5f9;
        color: #475569;
    }
    .dark .page-btn-inactive {
        background: #1e293b;
        color: #94a3b8;
    }
    .page-btn-inactive:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .page-btn-inactive:hover {
        background: #334155;
        color: #ffffff;
    }

    /* ─── Historical Archive Styles ─── */
    .archive-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 14px -3px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .dark .archive-card {
        background: #111827;
        border-color: #1f2937;
        box-shadow: 0 4px 14px -3px rgba(0, 0, 0, 0.4);
    }
    .archive-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 1rem;
    }
    .dark .archive-header-bar {
        border-color: #1e293b;
    }
    .archive-year-nav {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 0.85rem;
        border: 1px solid #e2e8f0;
    }
    .dark .archive-year-nav {
        background: #1e293b;
        border-color: #334155;
    }
    .archive-year-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 1rem;
        border-radius: 0.65rem;
        font-weight: 800;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .archive-year-btn-active {
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
    }
    .dark .archive-year-btn-active {
        background: #2563eb;
        color: #ffffff;
    }
    .archive-year-btn-inactive {
        background: transparent;
        color: #64748b;
    }
    .dark .archive-year-btn-inactive {
        color: #94a3b8;
    }
    .archive-year-btn-inactive:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .archive-year-btn-inactive:hover {
        background: #334155;
        color: #ffffff;
    }

    .archive-section {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    .archive-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 800;
        color: #1e293b;
    }
    .dark .archive-section-title {
        color: #f8fafc;
    }
    .archive-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 1.15rem;
    }
    .archive-file-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.15rem;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .dark .archive-file-card {
        background: #1e293b;
        border-color: #334155;
    }
    .archive-file-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px -4px rgba(15, 23, 42, 0.12);
        border-color: #3b82f6;
    }
    .archive-file-card-payroll::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 4px;
        background: linear-gradient(90deg, #1e3a8a, #3b82f6);
    }
    .archive-file-card-collection::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 4px;
        background: linear-gradient(90deg, #7c3aed, #a855f7);
    }
    .archive-file-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .archive-file-icon-wrap {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .archive-file-icon-payroll {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    .dark .archive-file-icon-payroll {
        background: rgba(30, 64, 175, 0.25);
        color: #93c5fd;
        border-color: #1e40af;
    }
    .archive-file-icon-collection {
        background: #faf5ff;
        color: #7e22ce;
        border: 1px solid #e9d5ff;
    }
    .dark .archive-file-icon-collection {
        background: rgba(126, 34, 206, 0.25);
        color: #d8b4fe;
        border-color: #7e22ce;
    }
    .archive-file-title {
        font-weight: 800;
        font-size: 0.95rem;
        color: #0f172a;
        margin-top: 0.2rem;
    }
    .dark .archive-file-title {
        color: #ffffff;
    }
    .archive-file-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        background: #f8fafc;
        padding: 0.65rem 0.75rem;
        border-radius: 0.65rem;
        border: 1px solid #f1f5f9;
    }
    .dark .archive-file-stats {
        background: #111827;
        border-color: #1f2937;
    }
    .archive-stat-item {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .archive-stat-lbl {
        font-size: 0.68rem;
        font-weight: 700;
        color: #64748b;
    }
    .dark .archive-stat-lbl {
        color: #94a3b8;
    }
    .archive-stat-val {
        font-family: monospace;
        font-weight: 800;
        font-size: 0.825rem;
        color: #0f172a;
    }
    .dark .archive-stat-val {
        color: #f8fafc;
    }
    .archive-file-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.25rem;
    }
    .btn-open-file {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.65rem;
        font-weight: 700;
        font-size: 0.8rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #2563eb;
        color: #ffffff;
    }
    .btn-open-file:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }
    .btn-archive-print {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 0.65rem;
        border-radius: 0.65rem;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .dark .btn-archive-print {
        background: #334155;
        border-color: #475569;
        color: #f8fafc;
    }
    .btn-archive-print:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* ─── Print Media Query ─── */
    @media print {
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
        }
        body * { visibility: hidden !important; }
        #printable-sheet, #printable-sheet * { visibility: visible !important; }
        #printable-sheet {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            background: #ffffff !important;
            color: #000000 !important;
            padding: 10px 12px !important;
            box-sizing: border-box !important;
        }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .payroll-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 11px !important;
            margin-top: 8px !important;
        }
        .payroll-table th {
            background-color: #1e293b !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            padding: 6px 4px !important;
            border: 1px solid #1e293b !important;
            font-size: 11px !important;
            text-align: center !important;
        }
        .payroll-table td {
            color: #000000 !important;
            padding: 5px 4px !important;
            border: 1px solid #475569 !important;
            font-size: 10.5px !important;
            text-align: center !important;
        }
        .payroll-table tr:nth-child(even) td {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .print-signatures {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            text-align: center !important;
            margin-top: 25px !important;
            font-size: 11px !important;
            font-weight: bold !important;
        }
    }
</style>
