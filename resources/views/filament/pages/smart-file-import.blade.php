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
    </style>

    <div class="import-page-container">

        <!-- بطاقة قراءة ملف نصي أو ذكي وإدراج المحتوى مباشرة -->
        <div class="custom-panel-card">
            <div class="card-header-bar">
                <span>{{ __('smart_import.section_title') }}</span>
            </div>

            <form wire:submit.prevent="processFile" class="space-y-4">
                {{ $this->form }}

                <div class="flex items-center justify-start pt-3 pb-1">
                    <button type="submit" class="btn-read-content" wire:loading.attr="disabled" wire:target="processFile">
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

            <div class="guide-banner-blue">
                <span class="text-xl">💡</span>
                <span>{{ __('smart_import.guide_tip') }}</span>
            </div>

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
                        <div class="records-list-grid">
                            @foreach($processingReport['results'] as $item)
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
                                    
                                    <!-- رأس بطاقة السجل -->
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

                                    <!-- رقائق البيانات المستخرجة -->
                                    <div class="record-fields-chips">
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
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
