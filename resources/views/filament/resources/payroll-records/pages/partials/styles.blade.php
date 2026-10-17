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

    .payroll-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .dark .payroll-card {
        background: #111827;
        border-color: #1f2937;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }

    .payroll-top-tabs {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.75rem;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .dark .payroll-top-tabs {
        border-color: #374151;
    }

    .tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .tab-btn-active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    }
    .tab-btn-inactive {
        background: #f1f5f9;
        color: #475569;
    }
    .dark .tab-btn-inactive {
        background: #1f2937;
        color: #94a3b8;
    }
    .tab-btn-inactive:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .dark .tab-btn-inactive:hover {
        background: #374151;
        color: #f8fafc;
    }

    .op-switch {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
    }
    .dark .op-switch {
        background: #1f2937;
        border-color: #374151;
    }
    .op-btn {
        border: none;
        border-radius: 9999px;
        padding: 0.45rem 1.15rem;
        font-weight: 700;
        font-size: 0.85rem;
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

    .modes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.75rem;
    }
    .mode-box {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1.1rem;
        border-radius: 0.75rem;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
        user-select: none;
    }
    .mode-box-active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);
    }
    .dark .mode-box-active {
        background: rgba(30, 58, 138, 0.3);
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
        transform: translateY(-1px);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.85rem;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1rem 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        border-top: 4px solid #3b82f6;
    }
    .dark .stat-card {
        background: #111827;
        border-color: #1f2937;
    }
    .stat-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
    }
    .dark .stat-title {
        color: #94a3b8;
    }
    .stat-num {
        font-size: 1.45rem;
        font-weight: 900;
        color: #0f172a;
        margin-top: 0.25rem;
        font-family: monospace;
    }
    .dark .stat-num {
        color: #f8fafc;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        align-items: end;
    }
    .form-control-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        color: #334155;
    }
    .dark .form-control-label {
        color: #cbd5e1;
    }
    .form-input, .form-select {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        padding: 0.55rem 0.75rem;
        font-size: 0.85rem;
        background: #ffffff;
        color: #0f172a;
        outline: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .dark .form-input, .dark .form-select {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    .form-input:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .payroll-table-wrap {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .dark .payroll-table-wrap {
        border-color: #1f2937;
        background: #111827;
    }
    .payroll-table {
        width: 100%;
        border-collapse: collapse;
        text-align: right;
        font-size: 0.825rem;
    }
    .payroll-table th {
        background: #2e3882;
        color: #ffffff;
        font-weight: 700;
        padding: 0.65rem 0.5rem;
        border: 1px solid #1e293b;
        white-space: nowrap;
        text-align: center;
    }
    .dark .payroll-table th {
        background: #1e293b;
        color: #ffffff;
        border-color: #334155;
    }
    .payroll-table td {
        padding: 0.55rem 0.5rem;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        text-align: center;
    }
    .dark .payroll-table td {
        border-color: #1f2937;
        color: #e2e8f0;
    }
    .payroll-table tr:hover {
        background: rgba(248, 250, 252, 0.8);
    }
    .dark .payroll-table tr:hover {
        background: rgba(30, 41, 59, 0.5);
    }
    .payroll-table tfoot td {
        background: #f8fafc;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
    }
    .dark .payroll-table tfoot td {
        background: #1e293b;
        border-color: #475569;
    }

    .sheet-checkbox {
        width: 1.35rem !important;
        height: 1.35rem !important;
        cursor: pointer;
        accent-color: #10b981;
        border-radius: 0.35rem;
    }

    .multi-select-trigger:hover {
        border-color: #93c5fd !important;
        background: #f8fafc !important;
    }

    .multi-select-dropdown::-webkit-scrollbar,
    .multi-select-dropdown div::-webkit-scrollbar {
        width: 6px;
    }
    .multi-select-dropdown div::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 9999px;
    }
    .multi-select-dropdown div::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }

    .dark .multi-select-trigger {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }
    .dark .multi-select-trigger:hover {
        border-color: #3b82f6 !important;
        background: #1e293b !important;
    }
    .dark .multi-select-trigger span[style*="background: #eff6ff"] {
        background: rgba(30, 58, 138, 0.4) !important;
        border-color: #1e3a8a !important;
        color: #93c5fd !important;
    }
    .dark .multi-select-dropdown {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark .multi-select-dropdown input[type="text"] {
        background: #111827 !important;
        border-color: #374151 !important;
        color: #f8fafc !important;
    }
    .dark .multi-select-dropdown label {
        color: #f1f5f9 !important;
    }
    .dark .multi-select-dropdown label:hover {
        background: #334155 !important;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .status-badge-paid {
        background: #d1fae5;
        color: #065f46;
    }
    .dark .status-badge-paid {
        background: rgba(6, 95, 70, 0.35);
        color: #6ee7b7;
    }
    .status-badge-unpaid {
        background: #fef3c7;
        color: #92400e;
    }
    .dark .status-badge-unpaid {
        background: rgba(146, 64, 14, 0.35);
        color: #fcd34d;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 1.15rem;
        border-radius: 0.65rem;
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s ease;
    }
    .btn-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }
    .btn-primary:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #334155;
    }
    .dark .btn-secondary {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

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
            background-color: #2e3882 !important;
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
