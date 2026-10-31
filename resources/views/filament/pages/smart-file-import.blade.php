<x-filament-panels::page>
    <style>
        .import-page-container {
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            font-family: inherit;
        }

        /* الكارت المحيط الأنيق ذو الحدود الزرقاء الخفيفة */
        .custom-panel-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1.5px solid #bfdbfe;
            padding: 1.75rem;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.04);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
        }

        .dark .custom-panel-card {
            background: #111827;
            border-color: #1e3a8a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        /* العنوان الرئيسي مع الخط الأزرق البارز على اليمين */
        .card-header-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            border-right: 4px solid #2563eb;
            padding-right: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .dark .card-header-bar {
            color: #f8fafc;
            border-right-color: #3b82f6;
        }

        /* الزر المميز لقراءة الملف ومعالجته */
        .btn-read-content {
            margin-top: 15px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            padding: 0.75rem 1.75rem;
            border-radius: 0.65rem;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center;
            gap: 0.6rem;
            cursor: pointer;
            white-space: nowrap !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
            transition: all 0.2s ease-in-out;
            line-height: 1;
        }

        .btn-read-content:hover:not(:disabled) {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
        }

        .btn-read-content:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-read-content:disabled {
            opacity: 0.8;
            cursor: not-allowed;
            transform: none;
        }

        .btn-icon-svg, .btn-spinner-svg {
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

        .btn-spinner-svg {
            animation: spin-custom 0.8s linear infinite !important;
        }

        @keyframes spin-custom {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* شريط التنبيه الإرشادي الأزرق الفاتح */
        .guide-banner-blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 0.9rem 1.25rem;
            border-radius: 0.6rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            line-height: 1.5;
        }

        .dark .guide-banner-blue {
            background: #172554;
            border-color: #1e40af;
            color: #dbeafe;
        }

        /* صندوق تقرير النتائج الاحترافي */
        .report-dashboard-container {
            border-radius: 1.15rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1rem;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        .dark .report-dashboard-container {
            background: #111827;
            border-color: #374151;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.35);
        }

        /* رأس التقرير الرئيسي */
        .report-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .report-main-header {
            border-bottom-color: #1f2937;
        }

        .report-header-title-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .report-status-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .report-header-text h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.4;
        }

        .dark .report-header-text h3 {
            color: #f8fafc;
        }

        .report-header-text p {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0.2rem 0 0 0;
        }

        .dark .report-header-text p {
            color: #94a3b8;
        }

        /* شبكة بطاقات المؤشرات KPI */
        .kpi-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem;
        }

        .kpi-metric-card {
            border-radius: 0.9rem;
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .kpi-metric-card:hover {
            transform: translateY(-2px);
        }

        .kpi-info-col {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .kpi-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
        }

        .dark .kpi-label {
            color: #94a3b8;
        }

        .kpi-number {
            font-size: 1.6rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .kpi-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* ألوان بطاقات الـ KPI */
        .kpi-total {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #334155;
        }
        .dark .kpi-total {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .kpi-total .kpi-number { color: #0f172a; }
        .dark .kpi-total .kpi-number { color: #f8fafc; }
        .kpi-total .kpi-icon-box { background: #e2e8f0; }
        .dark .kpi-total .kpi-icon-box { background: #334155; }

        .kpi-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .dark .kpi-success {
            background: rgba(6, 78, 59, 0.3);
            border-color: #047857;
        }
        .kpi-success .kpi-number { color: #15803d; }
        .dark .kpi-success .kpi-number { color: #4ade80; }
        .kpi-success .kpi-icon-box { background: #dcfce7; }
        .dark .kpi-success .kpi-icon-box { background: #064e3b; }

        .kpi-duplicate {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .dark .kpi-duplicate {
            background: rgba(120, 53, 15, 0.25);
            border-color: #b45309;
        }
        .kpi-duplicate .kpi-number { color: #b45309; }
        .dark .kpi-duplicate .kpi-number { color: #fbbf24; }
        .kpi-duplicate .kpi-icon-box { background: #fef3c7; }
        .dark .kpi-duplicate .kpi-icon-box { background: #78350f; }

        .kpi-error {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .dark .kpi-error {
            background: rgba(127, 29, 29, 0.25);
            border-color: #991b1b;
        }
        .kpi-error .kpi-number { color: #b91c1c; }
        .dark .kpi-error .kpi-number { color: #f87171; }
        .kpi-error .kpi-icon-box { background: #fee2e2; }
        .dark .kpi-error .kpi-icon-box { background: #7f1d1d; }

        /* أزرار فلترة السجلات (Tabs) */
        .filter-tabs-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            padding: 0.35rem;
            background: #f1f5f9;
            border-radius: 0.75rem;
            width: fit-content;
        }

        .dark .filter-tabs-bar {
            background: #1e293b;
        }

        .tab-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.95rem;
            border-radius: 0.55rem;
            font-size: 0.825rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #64748b;
            background: transparent;
        }

        .dark .tab-filter-btn {
            color: #94a3b8;
        }

        .tab-filter-btn:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.6);
        }

        .dark .tab-filter-btn:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-filter-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .dark .tab-filter-btn.active {
            background: #334155;
            color: #f8fafc;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
        }

        .tab-count-badge {
            font-size: 0.725rem;
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 800;
        }

        .dark .tab-count-badge {
            background: #475569;
            color: #f1f5f9;
        }

        .tab-filter-btn.active .tab-count-badge {
            background: #2563eb;
            color: #ffffff;
        }

        /* شبكة بطاقات السجلات المفصلة */
        .records-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1rem;
        }

        .record-card-item {
            border-radius: 0.9rem;
            padding: 1.15rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .dark .record-card-item {
            background: #1f2937;
            border-color: #374151;
        }

        .record-card-item:hover {
            box-shadow: 0 6px 16px -2px rgba(0, 0, 0, 0.07);
            transform: translateY(-2px);
        }

        .record-card-item.status-border-success {
            border-top: 3.5px solid #10b981;
        }

        .record-card-item.status-border-duplicate {
            border-top: 3.5px solid #f59e0b;
        }

        .record-card-item.status-border-error {
            border-top: 3.5px solid #ef4444;
        }

        .record-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .record-card-title-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .record-card-icon-badge {
            width: 36px;
            height: 36px;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            background: #f1f5f9;
            flex-shrink: 0;
        }

        .dark .record-card-icon-badge {
            background: #374151;
        }

        .record-card-title-text {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .record-type-tag {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
        }

        .dark .record-type-tag {
            color: #94a3b8;
        }

        .record-main-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .dark .record-main-title {
            color: #f8fafc;
        }

        /* شارات الحالة */
        .status-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.725rem;
            font-weight: 800;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .dark .badge-success {
            background: #064e3b;
            color: #86efac;
            border-color: #059669;
        }

        .badge-duplicate {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .dark .badge-duplicate {
            background: #78350f;
            color: #fde047;
            border-color: #92400e;
        }

        .badge-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .dark .badge-error {
            background: #7f1d1d;
            color: #fca5a5;
            border-color: #991b1b;
        }

        /* حقول البيانات المستخرجة */
        .record-fields-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            padding: 0.5rem 0;
            border-top: 1px dashed #e2e8f0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .dark .record-fields-chips {
            border-color: #374151;
        }

        .field-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.775rem;
            background: #f8fafc;
            padding: 0.25rem 0.55rem;
            border-radius: 0.45rem;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .dark .field-chip {
            background: #111827;
            color: #cbd5e1;
            border-color: #374151;
        }

        .field-chip-label {
            font-weight: 700;
            color: #64748b;
        }

        .dark .field-chip-label {
            color: #94a3b8;
        }

        .field-chip-val {
            font-weight: 800;
            color: #0f172a;
        }

        .dark .field-chip-val {
            color: #f8fafc;
        }

        /* شريط رسالة النظام */
        .record-action-note {
            font-size: 0.825rem;
            line-height: 1.5;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .note-success {
            background: #f0fdf4;
            color: #166534;
            border-right: 3px solid #22c55e;
        }
        .dark .note-success {
            background: rgba(6, 78, 59, 0.4);
            color: #86efac;
            border-right-color: #10b981;
        }

        .note-duplicate {
            background: #fffbeb;
            color: #92400e;
            border-right: 3px solid #f59e0b;
        }
        .dark .note-duplicate {
            background: rgba(120, 53, 15, 0.4);
            color: #fde68a;
            border-right-color: #d97706;
        }

        .note-error {
            background: #fef2f2;
            color: #991b1b;
            border-right: 3px solid #ef4444;
        }
        .dark .note-error {
            background: rgba(127, 29, 29, 0.4);
            color: #fca5a5;
            border-right-color: #dc2626;
        }

        /* AI Confirmation box styling */
        .ai-confirmation-box {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #818cf8;
            border-radius: 1.15rem;
            padding: 1.6rem;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.12), 0 8px 10px -6px rgba(99, 102, 241, 0.08);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .dark .ai-confirmation-box {
            background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
            border-color: #6366f1;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.25);
        }

        .ai-confirmation-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #3b82f6, #10b981);
        }

        .ai-header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .ai-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.825rem;
            font-weight: 800;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            border: 1px solid #c7d2fe;
        }

        .dark .ai-badge-pill {
            background: rgba(79, 70, 229, 0.2);
            color: #a5b4fc;
            border-color: #4f46e5;
        }

        .ai-dest-highlight-card {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 0.95rem;
            padding: 1.15rem 1.35rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dark .ai-dest-highlight-card {
            background: rgba(6, 78, 59, 0.25);
            border-color: #059669;
        }

        .ai-dest-info {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .ai-dest-icon {
            font-size: 2.1rem;
            line-height: 1;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 0.8rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            flex-shrink: 0;
        }

        .dark .ai-dest-icon {
            background: #1f2937;
        }

        .ai-dest-title h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .dark .ai-dest-title h4 {
            color: #f8fafc;
        }

        .ai-dest-title p {
            margin: 0.2rem 0 0 0;
            font-size: 0.85rem;
            color: #15803d;
            font-weight: 700;
        }

        .dark .ai-dest-title p {
            color: #86efac;
        }

        .ai-count-chip {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .dark .ai-count-chip {
            background: #064e3b;
            color: #86efac;
            border-color: #047857;
        }

        .ai-prompt-banner {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 0.95rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.6;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .dark .ai-prompt-banner {
            background: rgba(30, 58, 138, 0.3);
            border-color: #2563eb;
            color: #bfdbfe;
        }

        .ai-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.15rem;
        }

        .ai-action-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.9rem;
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            justify-content: space-between;
        }

        .dark .ai-action-card {
            background: #1e293b;
            border-color: #334155;
        }

        .ai-action-title {
            font-size: 0.875rem;
            font-weight: 800;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .dark .ai-action-title {
            color: #e2e8f0;
        }

        .btn-confirm-approve {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 0.85rem 1.25rem;
            border-radius: 0.65rem;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            transition: all 0.2s ease;
        }

        .btn-confirm-approve:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.45);
        }

        .btn-confirm-approve:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-apply-alt {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 0.65rem;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
        }

        .btn-apply-alt:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .btn-apply-alt:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .ai-select-input {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border-radius: 0.6rem;
            border: 1.5px solid #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
            font-size: 0.875rem;
            font-weight: 700;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .ai-select-input {
            background-color: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }

        .ai-select-input:focus {
            border-color: #3b82f6;
        }

        .btn-cancel-pending {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 700;
            background: transparent;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            width: fit-content;
            align-self: center;
        }

        .btn-cancel-pending:hover {
            color: #ef4444;
            background: #fef2f2;
        }

        .dark .btn-cancel-pending:hover {
            background: rgba(239, 68, 68, 0.15);
        }

        /* Extracted Records Preview Box & Cards */
        .ai-preview-toggle-wrapper {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .preview-toggle-btn {
            background: #ffffff;
            border: 1.5px solid #c7d2fe;
            color: #4338ca;
            padding: 0.55rem 1.15rem;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(99, 102, 241, 0.08);
            transition: all 0.2s ease-in-out;
        }

        .preview-toggle-btn:hover {
            border-color: #6366f1;
            color: #3730a3;
            background: #eef2ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.15);
        }

        .dark .preview-toggle-btn {
            background: #1e293b;
            border-color: #4f46e5;
            color: #c7d2fe;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .dark .preview-toggle-btn:hover {
            background: #312e81;
            border-color: #818cf8;
            color: #ffffff;
        }

        .ai-preview-container {
            margin-top: 0.85rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 0.85rem;
            max-height: 340px;
            overflow-y: auto;
            padding: 0.95rem;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
        }

        .dark .ai-preview-container {
            background: #0b1329;
            border-color: #1e293b;
        }

        .ai-preview-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 0.85rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease-in-out;
            position: relative;
        }

        .ai-preview-card:hover {
            border-color: #818cf8;
            box-shadow: 0 6px 14px rgba(99, 102, 241, 0.1);
            transform: translateY(-1.5px);
        }

        .dark .ai-preview-card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }

        .dark .ai-preview-card:hover {
            border-color: #6366f1;
            box-shadow: 0 6px 14px rgba(99, 102, 241, 0.25);
        }

        .ai-preview-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            border-bottom: 1px dashed #f1f5f9;
            padding-bottom: 0.45rem;
        }

        .dark .ai-preview-card-header {
            border-bottom-color: #334155;
        }

        .ai-preview-card-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.875rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .dark .ai-preview-card-title {
            color: #f8fafc;
        }

        .ai-preview-category-badge {
            background: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.725rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .dark .ai-preview-category-badge {
            background: rgba(79, 70, 229, 0.2);
            color: #a5b4fc;
            border-color: #4f46e5;
        }

        .ai-preview-card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .ai-preview-tag {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 0.2rem 0.55rem;
            border-radius: 0.5rem;
            font-size: 0.725rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .dark .ai-preview-tag {
            background: #0f172a;
            color: #94a3b8;
            border-color: #334155;
        }

        /* Target Company Selection Card */
        .ai-company-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
            border: 1.5px solid #bfdbfe;
            border-radius: 1rem;
            padding: 1.15rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
            box-shadow: 0 3px 12px rgba(37, 99, 235, 0.06);
            margin-top: 0.5rem;
        }

        .dark .ai-company-card {
            background: linear-gradient(135deg, #111827 0%, #172554 100%);
            border-color: #2563eb;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .ai-company-info {
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }

        .ai-company-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
            flex-shrink: 0;
        }

        .ai-company-details h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .dark .ai-company-details h5 {
            color: #f8fafc;
        }

        .ai-company-details p {
            margin: 0.25rem 0 0 0;
            font-size: 0.775rem;
            color: #64748b;
            font-weight: 600;
        }

        .dark .ai-company-details p {
            color: #94a3b8;
        }

        .ai-company-select-wrapper {
            min-width: 260px;
            flex: 1;
            max-width: 360px;
        }

        .ai-company-select {
            width: 100% !important;
            padding: 0.65rem 1rem !important;
            border-radius: 0.75rem !important;
            border: 1.5px solid #cbd5e1 !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            font-size: 0.875rem !important;
            font-weight: 800 !important;
            outline: none !important;
            cursor: pointer !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s !important;
        }

        .ai-company-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }

        .dark .ai-company-select {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        .dark .ai-company-select:focus {
            border-color: #60a5fa !important;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important;
        }

        .ai-loading-spinner {
            width: 20px !important;
            height: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
            min-height: 20px !important;
            max-height: 20px !important;
            display: inline-block !important;
            animation: spin-custom 0.8s linear infinite !important;
            flex-shrink: 0 !important;
            vertical-align: middle !important;
        }

        .ai-loading-spinner-indigo {
            color: #4f46e5 !important;
        }

        .ai-loading-spinner-emerald {
            color: #059669 !important;
        }

        /* Luxury AI Processing Card */
        .ai-processing-state-card {
            background: radial-gradient(120% 120% at 85% 15%, rgba(99, 102, 241, 0.08) 0%, rgba(248, 250, 255, 0.96) 45%, #ffffff 100%);
            border: 1.5px solid #c7d2fe;
            border-radius: 1.5rem;
            padding: 2.25rem 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            box-shadow: 0 20px 45px -12px rgba(99, 102, 241, 0.16), 0 4px 14px rgba(0, 0, 0, 0.03);
            animation: fadeIn 0.35s ease-out;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .dark .ai-processing-state-card {
            background: radial-gradient(120% 120% at 85% 15%, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0.98) 55%, #0b0f19 100%);
            border-color: #6366f1;
            box-shadow: 0 20px 45px -12px rgba(99, 102, 241, 0.35);
        }

        .ai-processing-state-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899, #06b6d4, #10b981);
            background-size: 300% 100%;
            animation: flowing-stripe 2.5s linear infinite;
        }

        .ai-loading-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1.1rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.85);
            margin-bottom: 0.25rem;
        }

        .dark .ai-loading-top-bar {
            border-bottom-color: rgba(51, 65, 85, 0.7);
        }

        .ai-badge-active {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.14) 0%, rgba(168, 85, 247, 0.1) 100%);
            color: #4338ca;
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 800;
            letter-spacing: normal;
        }

        .dark .ai-badge-active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(168, 85, 247, 0.2) 100%);
            color: #c7d2fe;
            border-color: rgba(165, 180, 252, 0.4);
        }

        .ai-badge-live-pulse {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: live-blink 1.2s ease-in-out infinite;
        }

        .ai-engine-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.05);
            color: #64748b;
            padding: 0.4rem 0.95rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: normal;
        }

        .dark .ai-engine-tag {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
        }

        .ai-loading-content-row {
            display: flex;
            align-items: flex-start;
            gap: 1.75rem;
            width: 100%;
        }

        .ai-pulse-scanner {
            position: relative;
            width: 76px;
            height: 76px;
            border-radius: 1.5rem;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.25rem;
            box-shadow: 0 12px 28px -4px rgba(79, 70, 229, 0.55);
        }

        .ai-pulse-scanner-emerald {
            background: linear-gradient(135deg, #059669 0%, #0d9488 50%, #10b981 100%);
            box-shadow: 0 12px 28px -4px rgba(5, 150, 105, 0.55);
        }

        .ai-pulse-glow {
            position: absolute;
            inset: -8px;
            border-radius: 1.75rem;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
            filter: blur(8px);
            z-index: 1;
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }

        .ai-pulse-glow-emerald {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.35) 0%, transparent 70%);
        }

        @keyframes pulse-glow {
            0% { transform: scale(0.9); opacity: 0.5; }
            100% { transform: scale(1.15); opacity: 0.9; }
        }

        .ai-pulse-icon {
            font-size: 2.25rem;
            position: relative;
            z-index: 3;
            animation: float-icon 2s ease-in-out infinite alternate;
            line-height: 1;
        }

        .ai-pulse-rings {
            position: absolute;
            inset: -7px;
            border-radius: 1.65rem;
            border: 2px solid rgba(99, 102, 241, 0.7);
            opacity: 0.8;
            animation: radar-wave 2.2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }

        .ai-pulse-rings-emerald {
            border-color: rgba(16, 185, 129, 0.7);
        }

        .ai-processing-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            min-width: 0;
        }

        .ai-loading-title-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.15rem;
        }

        .ai-loading-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.7;
            letter-spacing: normal;
        }

        .dark .ai-loading-title {
            color: #f8fafc;
        }

        .ai-loading-desc {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.95;
            margin: 0 0 0.35rem 0;
            letter-spacing: normal;
            max-width: 950px;
        }

        .dark .ai-loading-desc {
            color: #94a3b8;
        }

        .ai-stages-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
            padding-top: 0.25rem;
        }

        .ai-stage-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(99, 102, 241, 0.08);
            border: 1.5px solid rgba(99, 102, 241, 0.22);
            color: #4338ca;
            padding: 0.45rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.825rem;
            font-weight: 700;
            line-height: 1.5;
            letter-spacing: normal;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .ai-stage-pill {
            background: rgba(99, 102, 241, 0.16);
            border-color: rgba(165, 180, 252, 0.25);
            color: #c7d2fe;
        }

        .ai-stage-pill.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.18) 0%, rgba(168, 85, 247, 0.15) 100%);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.18);
        }

        .ai-stage-pill-emerald {
            background: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.25);
            color: #047857;
        }

        .dark .ai-stage-pill-emerald {
            background: rgba(16, 185, 129, 0.16);
            border-color: rgba(52, 211, 153, 0.3);
            color: #a7f3d0;
        }

        .ai-stage-arrow {
            font-size: 0.95rem;
            color: #94a3b8;
            font-weight: 800;
            margin: 0 0.15rem;
        }

        .ai-progress-bar-wrapper {
            width: 100%;
            height: 9px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            margin: 0.75rem 0 0.25rem 0;
        }

        .dark .ai-progress-bar-wrapper {
            background: #1e293b;
        }

        .ai-progress-bar-animated {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 45%;
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 30%, #ec4899 60%, #06b6d4 100%);
            border-radius: 9999px;
            box-shadow: 0 0 18px rgba(99, 102, 241, 0.85);
            animation: laser-slide-smooth 2s ease-in-out infinite alternate;
        }

        .ai-progress-bar-animated-emerald {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 45%;
            background: linear-gradient(90deg, #059669 0%, #10b981 30%, #14b8a6 60%, #06b6d4 100%);
            border-radius: 9999px;
            box-shadow: 0 0 18px rgba(16, 185, 129, 0.85);
            animation: laser-slide-smooth 2s ease-in-out infinite alternate;
        }

        @keyframes laser-slide-smooth {
            0% { left: 0%; width: 25%; }
            50% { width: 50%; }
            100% { left: 75%; width: 25%; }
        }

        .ai-loading-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.825rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(226, 232, 240, 0.9);
            margin-top: 0.25rem;
        }

        .dark .ai-loading-footer {
            border-top-color: rgba(51, 65, 85, 0.8);
        }

        .ai-footer-note {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 800;
            color: #d97706;
            letter-spacing: normal;
        }

        .dark .ai-footer-note {
            color: #fbbf24;
        }

        .ai-footer-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #64748b;
            font-weight: 700;
            letter-spacing: normal;
        }

        .dark .ai-footer-hint {
            color: #94a3b8;
        }

        @keyframes flowing-stripe {
            0% { background-position: 0% 50%; }
            100% { background-position: 300% 50%; }
        }

        @keyframes float-icon {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-4px) scale(1.08); }
        }

        @keyframes radar-wave {
            0% { transform: scale(0.92); opacity: 0.9; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        @keyframes live-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.75); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="import-page-container">

        <!-- File import card -->
        <div class="custom-panel-card">
            <div class="card-header-bar">
                <span>{{ __('smart_import.section_title') }}</span>
            </div>

            <form wire:submit.prevent="processFile" class="space-y-4">
                {{ $this->form }}

                <div class="flex items-center justify-start pt-3 pb-1">
                    <button type="button" wire:click="processFile" class="btn-read-content" wire:loading.attr="disabled" wire:target="processFile">
                        <span wire:loading.remove wire:target="processFile" class="inline-flex flex-row items-center gap-2.5 whitespace-nowrap">
                            <svg class="btn-icon-svg" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-bold text-sm">{{ __('smart_import.btn_process') }}</span>
                        </span>
                        <span wire:loading wire:target="processFile" class="inline-flex flex-row items-center gap-2.5 whitespace-nowrap">
                            <svg class="btn-spinner-svg text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="font-bold text-sm">{{ __('smart_import.btn_processing') }}</span>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Prominent Livewire Loading Card for Analysis -->
            <div wire:loading wire:target="processFile" class="ai-processing-state-card">
                <!-- Top Header Bar -->
                <div class="ai-loading-top-bar">
                    <div class="ai-badge-active">
                        <span class="ai-badge-live-pulse"></span>
                        <span>{{ __('smart_import.loading.status_badge') }} (Gemini Flash AI)</span>
                    </div>
                    <div class="ai-engine-tag">
                        <span>⚡</span>
                        <span>{{ __('smart_import.loading.engine_badge') }}</span>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="ai-loading-content-row">
                    <div class="ai-pulse-scanner">
                        <div class="ai-pulse-glow"></div>
                        <span class="ai-pulse-icon">🤖</span>
                        <div class="ai-pulse-rings"></div>
                    </div>
                    <div class="ai-processing-text">
                        <div class="ai-loading-title-box">
                            <svg class="ai-loading-spinner ai-loading-spinner-indigo" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <h4 class="ai-loading-title">{{ __('smart_import.loading.analyzing_title') }}</h4>
                        </div>
                        <p class="ai-loading-desc">
                            {{ __('smart_import.loading.analyzing_desc') }}
                        </p>
                        <!-- Dynamic Stage Pills -->
                        <div class="ai-stages-bar">
                            <span class="ai-stage-pill active">
                                <span>📄</span>
                                <span>{{ __('smart_import.loading.stage_reading') }}</span>
                            </span>
                            <span class="ai-stage-arrow">←</span>
                            <span class="ai-stage-pill active">
                                <span>🔍</span>
                                <span>{{ __('smart_import.loading.stage_extracting') }}</span>
                            </span>
                            <span class="ai-stage-arrow">←</span>
                            <span class="ai-stage-pill">
                                <span>🏢</span>
                                <span>{{ __('smart_import.loading.stage_matching') }}</span>
                            </span>
                            <span class="ai-stage-arrow">←</span>
                            <span class="ai-stage-pill">
                                <span>✨</span>
                                <span>{{ __('smart_import.loading.stage_confirming') }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Laser Progress Bar -->
                <div class="ai-progress-bar-wrapper">
                    <div class="ai-progress-bar-animated"></div>
                </div>

                <!-- Footer Bar -->
                <div class="ai-loading-footer">
                    <div class="ai-footer-note">
                        <span>⏳</span>
                        <span>{{ __('smart_import.loading.please_wait') }}</span>
                    </div>
                    <div class="ai-footer-hint">
                        <span>🛡️</span>
                        <span>{{ __('smart_import.loading.secure_badge') }}</span>
                    </div>
                </div>
            </div>

            <!-- Live progress while page batches are being analyzed -->
            @if ($isProcessing)
                <div class="ai-processing-state-card" wire:poll.3s="processNextBatch">
                    <div class="ai-loading-top-bar">
                        <div class="ai-badge-active">
                            <span class="ai-badge-live-pulse"></span>
                            <span>{{ __('smart_import.loading.status_badge') }} (Gemini Flash AI)</span>
                        </div>
                        @if ($totalPages > 0)
                            <div class="ai-engine-tag">
                                <span>📄</span>
                                <span>{{ $processedPages }} / {{ $totalPages }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="ai-loading-content-row">
                        <div class="ai-pulse-scanner">
                            <div class="ai-pulse-glow"></div>
                            <span class="ai-pulse-icon">🤖</span>
                            <div class="ai-pulse-rings"></div>
                        </div>
                        <div class="ai-processing-text">
                            <div class="ai-loading-title-box">
                                <svg class="ai-loading-spinner ai-loading-spinner-indigo" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <h4 class="ai-loading-title">{{ __('smart_import.loading.analyzing_title') }}</h4>
                            </div>
                            <p class="ai-loading-desc">
                                @if ($totalPages > 0)
                                    {{ __('smart_import.loading.progress_pages', ['processed' => $processedPages, 'total' => $totalPages]) }}
                                @else
                                    {{ __('smart_import.loading.analyzing_desc') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="ai-progress-bar-wrapper">
                        <div class="ai-progress-bar-animated"></div>
                    </div>
                </div>
            @endif

            <div class="guide-banner-blue">
                <span class="text-xl">💡</span>
                <span>{{ __('smart_import.guide_tip') }}</span>
            </div>

            <!-- AI auto-detection confirmation card -->
            @if ($pendingAnalysis)
                @php
                    $toolsSummary = $pendingAnalysis['tools_summary'] ?? [];
                    $totalCount = $pendingAnalysis['total_count'] ?? 0;
                    $isSingleTool = count($toolsSummary) === 1;
                    $primaryTool = $toolsSummary[0] ?? null;
                    $primaryName = $primaryTool['name'] ?? '';
                    $primaryTargetLabel = $primaryTool['target_label'] ?? $primaryName;
                    $primaryIcon = $primaryTool['icon'] ?? '🤖';
                @endphp

                <div class="ai-confirmation-box" x-data="{ openPreview: false }">
                    <!-- Header -->
                    <div class="ai-header-wrapper">
                        <div class="ai-badge-pill">
                            <span>✨</span>
                            <span>{{ __('smart_import.confirmation.card_badge') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 font-bold dark:text-gray-400">
                            {{ __('smart_import.confirmation.records_count_label') }} <strong class="text-indigo-600 dark:text-indigo-400">{{ $totalCount }}</strong>
                        </span>
                    </div>

                    <!-- Highlight detected destination -->
                    @if ($isSingleTool && $primaryTool)
                        <div class="ai-dest-highlight-card">
                            <div class="ai-dest-info">
                                <div class="ai-dest-icon">
                                    {{ $primaryIcon }}
                                </div>
                                <div class="ai-dest-title">
                                    <h4>{{ $primaryTargetLabel }}</h4>
                                    <p>{{ __('smart_import.confirmation.subtitle') }}</p>
                                </div>
                            </div>
                            <span class="ai-count-chip">
                                {{ $primaryTool['count'] }} {{ __('smart_import.fields.record_unit') }}
                            </span>
                        </div>
                    @else
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-500">{{ __('smart_import.confirmation.target_box_label') }}</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($toolsSummary as $ts)
                                    <div class="ai-dest-highlight-card !py-2 !px-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">{{ $ts['icon'] }}</span>
                                            <span class="text-sm font-bold">{{ $ts['target_label'] ?? $ts['name'] }}</span>
                                        </div>
                                        <span class="ai-count-chip !text-xs !py-0.5 !px-2">{{ $ts['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Confirmation prompt banner -->
                    <div class="ai-prompt-banner">
                        <span class="text-xl">💬</span>
                        <span>
                            @if ($isSingleTool && $primaryTool)
                                {{ __('smart_import.confirmation.prompt_question', ['destination' => $primaryName]) }}
                            @else
                                {{ __('smart_import.confirmation.prompt_question_multiple', ['count' => $totalCount]) }}
                            @endif
                        </span>
                    </div>

                    <!-- Extracted records preview toggle -->
                    @if(!empty($pendingAnalysis['records']))
                        <div class="ai-preview-toggle-wrapper">
                            <button type="button" @click="openPreview = !openPreview" class="preview-toggle-btn">
                                <span>👁️</span>
                                <span>{{ __('smart_import.confirmation.preview_title') }} ({{ $totalCount }})</span>
                                <span x-text="openPreview ? '▲' : '▼'"></span>
                            </button>

                            <div x-show="openPreview" x-cloak class="ai-preview-container">
                                @php
                                    $previewRecords = array_slice($pendingAnalysis['records'] ?? [], 0, 30);
                                    $remainingRecordsCount = max(0, ($totalCount ?? count($pendingAnalysis['records'] ?? [])) - 30);
                                @endphp
                                @foreach($previewRecords as $pRecord)
                                    <div class="ai-preview-card">
                                        <div class="ai-preview-card-header">
                                            <div class="ai-preview-card-title">
                                                <span>{{ $pRecord['icon'] }}</span>
                                                <span>{{ $pRecord['title'] }}</span>
                                            </div>
                                            <span class="ai-preview-category-badge">{{ $pRecord['category'] }}</span>
                                        </div>
                                        <div class="ai-preview-card-tags">
                                            @if(!empty($pRecord['arguments']['company_name']))
                                                <span class="ai-preview-tag !bg-blue-50 !text-blue-700 !border-blue-200 dark:!bg-blue-950/40 dark:!text-blue-300 dark:!border-blue-800">
                                                    <span>🏢</span>
                                                    <span>{{ __('smart_import.fields.company') }} {{ $pRecord['arguments']['company_name'] }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['national_id']))
                                                <span class="ai-preview-tag">
                                                    <span>🆔</span>
                                                    <span>{{ __('smart_import.fields.national_id') }} {{ $pRecord['arguments']['national_id'] }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['iqama_number']))
                                                <span class="ai-preview-tag">
                                                    <span>💳</span>
                                                    <span>{{ __('smart_import.fields.iqama_number') }} {{ $pRecord['arguments']['iqama_number'] }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['phone']))
                                                <span class="ai-preview-tag">
                                                    <span>📱</span>
                                                    <span>{{ __('smart_import.fields.phone') }} {{ $pRecord['arguments']['phone'] }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['basic_salary']))
                                                <span class="ai-preview-tag">
                                                    <span>💰</span>
                                                    <span>{{ __('smart_import.fields.basic_salary') }} {{ $pRecord['arguments']['basic_salary'] }} {{ __('smart_import.fields.currency') }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['amount']))
                                                <span class="ai-preview-tag">
                                                    <span>💵</span>
                                                    <span>{{ __('smart_import.fields.amount') }} {{ $pRecord['arguments']['amount'] }} {{ __('smart_import.fields.currency') }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($pRecord['arguments']['invoice_number']))
                                                <span class="ai-preview-tag">
                                                    <span>🧾</span>
                                                    <span>{{ __('smart_import.fields.invoice_number') }} {{ $pRecord['arguments']['invoice_number'] }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if($remainingRecordsCount > 0)
                                    <div class="col-span-full py-2.5 px-4 rounded-xl bg-indigo-50/90 border border-indigo-200 text-indigo-800 text-center font-bold text-xs dark:bg-indigo-950/50 dark:border-indigo-800 dark:text-indigo-300">
                                        ✨ بالإضافة إلى {{ $remainingRecordsCount }} سجل آخر سيتم حفظها وإدراجها بالكامل فور الضغط على زر التأكيد أدناه.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Target company assignment box -->
                    <div class="ai-company-card">
                        <div class="ai-company-info">
                            <div class="ai-company-icon-box">
                                <span>🏢</span>
                            </div>
                            <div class="ai-company-details">
                                <h5>{{ __('smart_import.confirmation.target_company_label') }}</h5>
                                <p>{{ __('smart_import.confirmation.company_assigned_hint') }}</p>
                            </div>
                        </div>

                        <div class="ai-company-select-wrapper">
                            <select id="selectedCompanyId" 
                                    wire:model.live="selectedCompanyId" 
                                    class="ai-company-select">
                                <option value="">{{ __('smart_import.confirmation.select_company_placeholder') }}</option>
                                @foreach($this->companies as $cId => $cName)
                                    <option value="{{ $cId }}">{{ $cName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Prominent Livewire Loading Card for Confirmation / Insertion -->
                    <div wire:loading wire:target="confirmAndInsert, applyAlternativeTool" class="ai-processing-state-card ai-processing-state-card-emerald">
                        <!-- Top Header Bar -->
                        <div class="ai-loading-top-bar">
                            <div class="ai-badge-active ai-badge-active-emerald">
                                <span class="ai-badge-live-pulse !bg-emerald-500 !shadow-[0_0_8px_#10b981]"></span>
                                <span>{{ __('smart_import.loading.status_badge') }}</span>
                            </div>
                            <div class="ai-engine-tag">
                                <span>⚡</span>
                                <span>{{ __('smart_import.loading.stage_saving') }}</span>
                            </div>
                        </div>

                        <!-- Main Content Row -->
                        <div class="ai-loading-content-row">
                            <div class="ai-pulse-scanner ai-pulse-scanner-emerald">
                                <div class="ai-pulse-glow ai-pulse-glow-emerald"></div>
                                <span class="ai-pulse-icon">⚡</span>
                                <div class="ai-pulse-rings ai-pulse-rings-emerald"></div>
                            </div>
                            <div class="ai-processing-text">
                                <div class="ai-loading-title-box">
                                    <svg class="ai-loading-spinner ai-loading-spinner-emerald" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <h4 class="ai-loading-title">{{ __('smart_import.loading.processing_title') }}</h4>
                                </div>
                                <p class="ai-loading-desc">
                                    {{ __('smart_import.loading.processing_desc') }}
                                </p>
                                <!-- Dynamic Stage Pills -->
                                <div class="ai-stages-bar">
                                    <span class="ai-stage-pill ai-stage-pill-emerald">
                                        <span>🛡️</span>
                                        <span>التحقق ومنع التكرار</span>
                                    </span>
                                    <span class="ai-stage-arrow">←</span>
                                    <span class="ai-stage-pill ai-stage-pill-emerald">
                                        <span>🏢</span>
                                        <span>ربط المؤسسات</span>
                                    </span>
                                    <span class="ai-stage-arrow">←</span>
                                    <span class="ai-stage-pill ai-stage-pill-emerald active">
                                        <span>💾</span>
                                        <span>إدراج في قاعدة البيانات</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Laser Progress Bar -->
                        <div class="ai-progress-bar-wrapper">
                            <div class="ai-progress-bar-animated-emerald"></div>
                        </div>

                        <!-- Footer Bar -->
                        <div class="ai-loading-footer">
                            <div class="ai-footer-note !text-emerald-600 dark:!text-emerald-400">
                                <span>⚡</span>
                                <span>{{ __('smart_import.loading.please_wait_insert') }}</span>
                            </div>
                            <div class="ai-footer-hint">
                                <span>🔒</span>
                                <span>معاملة آمنة (Database Transaction)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action choices: approve or alternative -->
                    <div class="ai-actions-grid">
                        <!-- Option 1: Confirm and insert -->
                        <div class="ai-action-card border-green-200 dark:border-green-900 bg-green-50/20 dark:bg-green-950/20">
                            <div class="ai-action-title text-green-700 dark:text-green-400">
                                <span>✅</span>
                                <span>{{ __('smart_import.confirmation.option_one_title') }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ __('smart_import.confirmation.option_one_desc', ['count' => $totalCount]) }}
                            </p>
                            <button type="button" 
                                    wire:click="confirmAndInsert" 
                                    wire:loading.attr="disabled" 
                                    wire:target="confirmAndInsert, applyAlternativeTool"
                                    class="btn-confirm-approve">
                                <span wire:loading.remove wire:target="confirmAndInsert">
                                    @if ($isSingleTool && $primaryTool)
                                        {{ __('smart_import.confirmation.confirm_btn', ['destination' => $primaryName]) }}
                                    @else
                                        {{ __('smart_import.confirmation.confirm_btn_multiple', ['count' => $totalCount]) }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="confirmAndInsert" class="inline-flex items-center gap-2">
                                    <svg class="btn-spinner-svg text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('smart_import.confirmation.confirming') }}</span>
                                </span>
                            </button>
                        </div>

                        <!-- Option 2: Select alternative tool -->
                        <div class="ai-action-card border-blue-200 dark:border-blue-900 bg-blue-50/20 dark:bg-blue-950/20">
                            <div class="ai-action-title text-blue-700 dark:text-blue-400">
                                <span>🔄</span>
                                <span>{{ __('smart_import.confirmation.alternative_section_title') }}</span>
                            </div>
                            <div>
                                <select wire:model="alternativeTool" class="ai-select-input">
                                    @foreach(__('smart_import.targets') as $targetKey => $targetLabel)
                                        @if($targetKey !== 'all')
                                            <option value="{{ $targetKey }}">{{ $targetLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" 
                                    wire:click="applyAlternativeTool" 
                                    wire:loading.attr="disabled" 
                                    wire:target="applyAlternativeTool, confirmAndInsert"
                                    class="btn-apply-alt">
                                <span wire:loading.remove wire:target="applyAlternativeTool">
                                    {{ __('smart_import.confirmation.apply_alt_btn') }}
                                </span>
                                <span wire:loading wire:target="applyAlternativeTool" class="inline-flex items-center gap-2">
                                    <svg class="btn-spinner-svg text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('smart_import.confirmation.applying_alt') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Cancel action -->
                    <button type="button" wire:click="cancelPendingAnalysis" class="btn-cancel-pending">
                        {{ __('smart_import.confirmation.cancel_btn') }}
                    </button>
                </div>
            @endif

            <!-- تقرير معالجة البيانات المستخرجة المنظم والاحترافي -->
            @if ($processingReport)

                @php
                    $totalProcessed = $processingReport['total_processed'] ?? 0;
                    $successCount = $processingReport['success_count'] ?? 0;
                    $duplicateCount = $processingReport['duplicate_count'] ?? 0;
                    $errorCount = $processingReport['error_count'] ?? 0;

                    $isAllSuccess = $successCount > 0 && $duplicateCount === 0 && $errorCount === 0;
                    $isAllDuplicate = $successCount === 0 && $duplicateCount > 0 && $errorCount === 0;

                    $headerIconBg = match(true) {
                        $isAllSuccess => 'background: #dcfce7; color: #15803d;',
                        $isAllDuplicate => 'background: #fef3c7; color: #b45309;',
                        $errorCount > 0 => 'background: #fee2e2; color: #b91c1c;',
                        default => 'background: #eff6ff; color: #2563eb;'
                    };

                    $headerIcon = match(true) {
                        $isAllSuccess => '✅',
                        $isAllDuplicate => '🛡️',
                        $errorCount > 0 => '⚠️',
                        default => '📊'
                    };
                @endphp

                <div class="report-dashboard-container" x-data="{ activeTab: 'all' }">
                    <!-- رأس التقرير -->
                    <div class="report-main-header">
                        <div class="report-header-title-box">
                            <div class="report-status-icon-circle" style="{{ $headerIconBg }}">
                                {{ $headerIcon }}
                            </div>
                            <div class="report-header-text">
                                <h3>{{ __('smart_import.results_title') }}</h3>
                                <p>{{ $processingReport['message'] }}</p>
                            </div>
                        </div>

                        <!-- تبويبات الفلترة السريعة -->
                        <div class="filter-tabs-bar">
                            <button type="button" @click="activeTab = 'all'" :class="{ 'active': activeTab === 'all' }" class="tab-filter-btn">
                                <span>{{ __('smart_import.filter_all') }}</span>
                                <span class="tab-count-badge">{{ $totalProcessed }}</span>
                            </button>
                            @if($successCount > 0)
                                <button type="button" @click="activeTab = 'success'" :class="{ 'active': activeTab === 'success' }" class="tab-filter-btn">
                                    <span>{{ __('smart_import.filter_success') }}</span>
                                    <span class="tab-count-badge">{{ $successCount }}</span>
                                </button>
                            @endif
                            @if($duplicateCount > 0)
                                <button type="button" @click="activeTab = 'duplicate'" :class="{ 'active': activeTab === 'duplicate' }" class="tab-filter-btn">
                                    <span>{{ __('smart_import.filter_duplicate') }}</span>
                                    <span class="tab-count-badge">{{ $duplicateCount }}</span>
                                </button>
                            @endif
                            @if($errorCount > 0)
                                <button type="button" @click="activeTab = 'error'" :class="{ 'active': activeTab === 'error' }" class="tab-filter-btn">
                                    <span>{{ __('smart_import.filter_error') }}</span>
                                    <span class="tab-count-badge">{{ $errorCount }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- شبكة بطاقات المؤشرات الرقمية KPI -->
                    <div class="kpi-metrics-grid">
                        <div class="kpi-metric-card kpi-total">
                            <div class="kpi-info-col">
                                <span class="kpi-label">{{ __('smart_import.kpi_total') }}</span>
                                <span class="kpi-number">{{ $totalProcessed }}</span>
                            </div>
                            <div class="kpi-icon-box">📊</div>
                        </div>

                        <div class="kpi-metric-card kpi-success">
                            <div class="kpi-info-col">
                                <span class="kpi-label">{{ __('smart_import.kpi_success') }}</span>
                                <span class="kpi-number">{{ $successCount }}</span>
                            </div>
                            <div class="kpi-icon-box">✓</div>
                        </div>

                        <div class="kpi-metric-card kpi-duplicate">
                            <div class="kpi-info-col">
                                <span class="kpi-label">{{ __('smart_import.kpi_duplicate') }}</span>
                                <span class="kpi-number">{{ $duplicateCount }}</span>
                            </div>
                            <div class="kpi-icon-box">🛡️</div>
                        </div>

                        @if($errorCount > 0)
                            <div class="kpi-metric-card kpi-error">
                                <div class="kpi-info-col">
                                    <span class="kpi-label">{{ __('smart_import.kpi_error') }}</span>
                                    <span class="kpi-number">{{ $errorCount }}</span>
                                </div>
                                <div class="kpi-icon-box">⚠️</div>
                            </div>
                        @endif
                    </div>

                    <!-- قائمة السجلات التفصيلية المنظمة -->
                    @if(!empty($processingReport['results']))
                        @php
                            $displayedResults = array_slice($processingReport['results'] ?? [], 0, 50);
                            $remainingResultsCount = max(0, count($processingReport['results'] ?? []) - 50);
                        @endphp
                        <div class="records-list-grid">
                            @foreach($displayedResults as $item)
                                @php
                                    $itemStatus = $item['status'] ?? 'success';
                                    $category = $item['category'] ?? __('smart_import.categories.default');
                                    $icon = $item['icon'] ?? '📄';
                                    $title = $item['title'] ?? __('smart_import.service.unnamed_record');
                                    $args = $item['arguments'] ?? [];
                                    $resultMsg = $item['result']['message'] ?? '';

                                    $cardBorderClass = match($itemStatus) {
                                        'success' => 'status-border-success',
                                        'duplicate' => 'status-border-duplicate',
                                        default => 'status-border-error'
                                    };

                                    $statusBadgeClass = match($itemStatus) {
                                        'success' => 'badge-success',
                                        'duplicate' => 'badge-duplicate',
                                        default => 'badge-error'
                                    };

                                    $statusBadgeText = match($itemStatus) {
                                        'success' => __('smart_import.status_success'),
                                        'duplicate' => __('smart_import.status_duplicate'),
                                        default => __('smart_import.status_error')
                                    };

                                    $noteClass = match($itemStatus) {
                                        'success' => 'note-success',
                                        'duplicate' => 'note-duplicate',
                                        default => 'note-error'
                                    };
                                @endphp

                                <div class="record-card-item {{ $cardBorderClass }}"
                                     x-show="activeTab === 'all' || activeTab === '{{ $itemStatus }}'"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <div class="record-card-header">
                                        <div class="record-card-title-group">
                                            <div class="record-card-icon-badge">
                                                {{ $icon }}
                                            </div>
                                            <div class="record-card-title-text">
                                                <span class="record-type-tag">{{ $category }}</span>
                                                <span class="record-main-title">{{ $title }}</span>
                                            </div>
                                        </div>

                                        <span class="status-pill-badge {{ $statusBadgeClass }}">
                                            {{ $statusBadgeText }}
                                        </span>
                                    </div>

                                    <div class="record-fields-chips">
                                        @php
                                            $cardCompany = $item['result']['company_name'] ?? $args['company_name'] ?? null;
                                        @endphp
                                        @if(!empty($cardCompany))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.company') }}</span>
                                                <span class="field-chip-val font-bold">{{ $cardCompany }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['national_id']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.national_id') }}</span>
                                                <span class="field-chip-val">{{ $args['national_id'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['iqama_number']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.iqama_number') }}</span>
                                                <span class="field-chip-val">{{ $args['iqama_number'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['commercial_registration']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.commercial_registration') }}</span>
                                                <span class="field-chip-val">{{ $args['commercial_registration'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['phone']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.phone') }}</span>
                                                <span class="field-chip-val">{{ $args['phone'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['nationality']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.nationality') }}</span>
                                                <span class="field-chip-val">{{ $args['nationality'] }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['amount']) || !empty($args['total_amount']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.amount') }}</span>
                                                <span class="field-chip-val">{{ number_format($args['amount'] ?? $args['total_amount'], 2) }} {{ __('smart_import.fields.currency') }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($args['invoice_number']))
                                            <div class="field-chip">
                                                <span class="field-chip-label">{{ __('smart_import.fields.invoice_number') }}</span>
                                                <span class="field-chip-val">{{ $args['invoice_number'] }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- نص رسالة النظام -->
                                    <div class="record-action-note {{ $noteClass }}">
                                        <span>{{ $resultMsg }}</span>
                                    </div>
                                </div>
                            @endforeach

                            @if($remainingResultsCount > 0)
                                <div class="col-span-full py-3 px-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-center font-bold text-xs dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300">
                                    📋 تم إدراج وحفظ جميع الـ {{ count($processingReport['results']) }} سجل بنجاح في قاعدة البيانات (تم عرض أول 50 سجلاً لتسريع استجابة الصفحة).
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
