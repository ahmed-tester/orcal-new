<x-filament-panels::page>
    <style>
        .notif-page-ctn {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-family: inherit;
        }

        /* ----------------------------------------------------
           KPI Stats Cards
        ---------------------------------------------------- */
        .fi-stat-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 0.9rem 1.1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .dark .fi-stat-card {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }
        .fi-stat-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
        }
        .dark .fi-stat-card:hover {
            border-color: rgba(255, 255, 255, 0.18);
        }
        .fi-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 0.55rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        /* ----------------------------------------------------
           Filter Toolbar (Compact & Sleek)
        ---------------------------------------------------- */
        .fi-filter-bar {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .dark .fi-filter-bar {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .fi-filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.6rem;
            width: 100%;
        }

        .fi-filter-col {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .fi-filter-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }
        .dark .fi-filter-label {
            color: #94a3b8;
        }

        /* ----------------------------------------------------
           Form Inputs (Compact 34px)
        ---------------------------------------------------- */
        .fi-custom-input {
            width: 100%;
            height: 34px;
            padding: 0.35rem 0.65rem;
            border-radius: 0.45rem;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 0.8125rem;
            font-weight: 500;
            outline: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
            transition: all 0.15s ease-in-out;
            box-sizing: border-box;
        }
        .fi-custom-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2.5px rgba(59, 130, 246, 0.15);
        }
        .dark .fi-custom-input {
            background-color: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }
        .dark .fi-custom-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2.5px rgba(96, 165, 250, 0.2);
        }

        /* ----------------------------------------------------
           Buttons (Compact 34px)
        ---------------------------------------------------- */
        .fi-btn-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            height: 34px;
            padding: 0 0.85rem;
            border-radius: 0.45rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            border: 1px solid transparent;
            box-sizing: border-box;
        }
        .fi-btn-primary-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.2);
        }
        .fi-btn-primary-custom:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }
        .fi-btn-secondary-custom {
            background-color: #ffffff;
            color: #475569;
            border-color: #d1d5db;
        }
        .fi-btn-secondary-custom:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .dark .fi-btn-secondary-custom {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
        }
        .dark .fi-btn-secondary-custom:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* ----------------------------------------------------
           Tables
        ---------------------------------------------------- */
        .fi-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }
        .dark .fi-table-container {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .fi-native-table {
            width: 100%;
            text-align: start;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.835rem;
        }
        
        .fi-native-table thead tr {
            background-color: #f8fafc;
        }
        .dark .fi-native-table thead tr {
            background-color: rgba(255, 255, 255, 0.03);
        }
        .fi-native-table thead th {
            padding: 0.8rem 0.9rem;
            font-weight: 700;
            font-size: 0.78rem;
            color: #475569;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
            border-inline-end: 1px solid #f1f5f9;
            white-space: nowrap;
        }
        .dark .fi-native-table thead th {
            color: #cbd5e1;
            border-bottom-color: rgba(255, 255, 255, 0.08);
            border-inline-end-color: rgba(255, 255, 255, 0.04);
        }
        .fi-native-table thead th:last-child {
            border-inline-end: none;
        }

        .fi-native-table tbody tr {
            transition: background-color 0.15s ease;
        }
        .fi-native-table tbody tr:not(:last-child) td {
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .fi-native-table tbody tr:not(:last-child) td {
            border-bottom-color: rgba(255, 255, 255, 0.04);
        }
        .fi-native-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .dark .fi-native-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .fi-native-table td {
            padding: 0.75rem 0.9rem;
            color: #1e293b;
            vertical-align: middle;
            text-align: center;
            border-inline-end: 1px solid #f8fafc;
        }
        .dark .fi-native-table td {
            color: #f8fafc;
            border-inline-end-color: rgba(255, 255, 255, 0.02);
        }
        .fi-native-table td:last-child {
            border-inline-end: none;
        }

        /* ----------------------------------------------------
           Matrix Cell Box
        ---------------------------------------------------- */
        .matrix-doc-cell {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0.3rem 0.5rem;
            border-radius: 0.45rem;
            background-color: #fafbfc;
            border: 1px solid #f1f5f9;
            min-width: 95px;
        }
        .dark .matrix-doc-cell {
            background-color: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);
        }
        .matrix-doc-date {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.74rem;
            font-weight: 700;
            color: #334155;
            letter-spacing: -0.2px;
        }
        .dark .matrix-doc-date { color: #e2e8f0; }

        /* ----------------------------------------------------
           Pill Badges
        ---------------------------------------------------- */
        .fi-badge-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            border-radius: 9999px;
            padding: 0.15rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .fi-badge-success {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .dark .fi-badge-success {
            background-color: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.25);
        }

        .fi-badge-warning {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .dark .fi-badge-warning {
            background-color: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.25);
        }

        .fi-badge-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .dark .fi-badge-danger {
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.25);
        }

        .fi-badge-gray {
            color: #94a3b8;
            font-weight: 500;
        }

        /* ----------------------------------------------------
           Segmented Tabs Control
        ---------------------------------------------------- */
        .fi-tabs-segmented {
            display: inline-flex;
            background-color: #f1f5f9;
            border-radius: 0.55rem;
            padding: 0.2rem;
            gap: 0.2rem;
        }
        .dark .fi-tabs-segmented {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .fi-tab-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.85rem;
            border-radius: 0.45rem;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            color: #64748b;
            background-color: transparent;
            transition: all 0.15s ease-in-out;
        }
        .fi-tab-item:hover {
            color: #0f172a;
        }
        .dark .fi-tab-item:hover {
            color: #ffffff;
        }
        .fi-tab-item.active {
            background-color: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .dark .fi-tab-item.active {
            background-color: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        /* ----------------------------------------------------
           Company Special Row
        ---------------------------------------------------- */
        .fi-tr-company {
            background-color: #f8fafc;
        }
        .dark .fi-tr-company {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Constrain pagination SVGs */
        nav svg, .pagination svg, [role="navigation"] svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            flex-shrink: 0 !important;
            display: inline-block !important;
        }
    </style>

    @php
        $counts = $this->summaryCounts;
        $matrixData = $this->paginatedDocumentsMatrix;
        $paginatedAlerts = $this->paginatedAlerts;
    @endphp

    <div class="notif-page-ctn" dir="rtl">
        
        <!-- Header Bar with View Mode Tabs -->
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
            <div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.3px;" class="dark:text-white">
                    تقرير الإشعارات والتنبيهات
                </h1>
                <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.15rem;" class="dark:text-gray-400">
                    متابعة مواعيد انتهاء وثائق وتراخيص الموظفين والمنشآت ومراحل المخالفات
                </p>
            </div>

            <!-- View Switcher -->
            <div class="fi-tabs-segmented">
                <button wire:click="switchViewMode('matrix')" class="fi-tab-item {{ $viewMode === 'matrix' ? 'active' : '' }}">
                    <span>📊</span>
                    <span>مصفوفة الوثائق</span>
                </button>
                <button wire:click="switchViewMode('list')" class="fi-tab-item {{ $viewMode === 'list' ? 'active' : '' }}">
                    <span>🔔</span>
                    <span>سجل التنبيهات ({{ $counts['total'] }})</span>
                </button>
            </div>
        </div>

        <!-- 1. Stats KPI Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
            <!-- Overdue -->
            <div class="fi-stat-card">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">تنبيهات منتهية</div>
                    <div style="font-size: 1.45rem; font-weight: 800; color: #dc2626; margin-top: 0.15rem; font-variant-numeric: tabular-nums;">
                        {{ $counts['overdue'] ?? 0 }}
                    </div>
                </div>
                <div class="fi-stat-icon" style="background-color: #fef2f2; color: #dc2626;" class="dark:bg-red-900/20">
                    🚨
                </div>
            </div>

            <!-- Urgent -->
            <div class="fi-stat-card">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">تنبيهات عاجلة (≤ 15 يوم)</div>
                    <div style="font-size: 1.45rem; font-weight: 800; color: #d97706; margin-top: 0.15rem; font-variant-numeric: tabular-nums;">
                        {{ $counts['urgent'] ?? 0 }}
                    </div>
                </div>
                <div class="fi-stat-icon" style="background-color: #fffbeb; color: #d97706;" class="dark:bg-amber-900/20">
                    ⚠️
                </div>
            </div>

            <!-- Documents -->
            <div class="fi-stat-card">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">وثائق وتراخيص</div>
                    <div style="font-size: 1.45rem; font-weight: 800; color: #2563eb; margin-top: 0.15rem; font-variant-numeric: tabular-nums;">
                        {{ $counts['documents'] ?? 0 }}
                    </div>
                </div>
                <div class="fi-stat-icon" style="background-color: #eff6ff; color: #2563eb;" class="dark:bg-blue-900/20">
                    📄
                </div>
            </div>

            <!-- Violations -->
            <div class="fi-stat-card">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">مخالفات وغرامات</div>
                    <div style="font-size: 1.45rem; font-weight: 800; color: #ea580c; margin-top: 0.15rem; font-variant-numeric: tabular-nums;">
                        {{ $counts['violations'] ?? 0 }}
                    </div>
                </div>
                <div class="fi-stat-icon" style="background-color: #fff7ed; color: #ea580c;" class="dark:bg-orange-900/20">
                    ⚖️
                </div>
            </div>

            <!-- Tasks & Invoices -->
            <div class="fi-stat-card">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">مهام وفواتير</div>
                    <div style="font-size: 1.45rem; font-weight: 800; color: #059669; margin-top: 0.15rem; font-variant-numeric: tabular-nums;">
                        {{ ($counts['tasks'] ?? 0) + ($counts['invoices'] ?? 0) }}
                    </div>
                </div>
                <div class="fi-stat-icon" style="background-color: #ecfdf5; color: #059669;" class="dark:bg-emerald-900/20">
                    📋
                </div>
            </div>
        </div>

        <!-- 2. Filter Bar (Horizontal & Compact) -->
        <div class="fi-filter-bar">
            <div class="fi-filter-row">
                <!-- Search (Flex 1.2) -->
                <div class="fi-filter-col" style="flex: 1.2 1 180px;">
                    <label class="fi-filter-label">🔍 بحث بالاسم أو الهوية</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="اكتب للبحث..." class="fi-custom-input" />
                </div>

                <!-- Entity (Flex 1.2) -->
                <div class="fi-filter-col" style="flex: 1.2 1 180px;">
                    <label class="fi-filter-label">🏢 المنشأة أو الموظف</label>
                    <select wire:model.live="entityFilter" class="fi-custom-input">
                        <option value="all">الكل (كافة المنشآت والموظفين)</option>
                        @php $entities = $this->entitiesList; @endphp
                        @if(!empty($entities))
                            <optgroup label="🏢 المنشآت والشركات">
                                @foreach($entities as $key => $label)
                                    @if(str_starts_with($key, 'comp_'))
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="👤 الموظفون">
                                @foreach($entities as $key => $label)
                                    @if(str_starts_with($key, 'emp_'))
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                <!-- Date From (Flex 0.8) -->
                <div class="fi-filter-col" style="flex: 0.8 1 130px;">
                    <label class="fi-filter-label">📅 من تاريخ</label>
                    <input type="date" wire:model.live="dateFrom" class="fi-custom-input" />
                </div>

                <!-- Date To (Flex 0.8) -->
                <div class="fi-filter-col" style="flex: 0.8 1 130px;">
                    <label class="fi-filter-label">📅 إلى تاريخ</label>
                    <input type="date" wire:model.live="dateTo" class="fi-custom-input" />
                </div>

                <!-- Reset Filter Action (Flex auto) -->
                <div class="fi-filter-col" style="flex: 0 0 auto;">
                    <button wire:click="resetFilters" class="fi-btn-custom fi-btn-secondary-custom" title="إعادة تعيين الفلاتر">
                        <span>🔄</span>
                        <span>إعادة تعيين</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. VIEW MODE A: Matrix Table (Single Unified Modern Header) -->
        @if ($viewMode === 'matrix')
            <div class="fi-table-container">
                <table class="fi-native-table">
                    <thead>
                        <tr>
                            <th style="width: 44px;">#</th>
                            <th style="text-align: right; padding-right: 1.1rem; min-width: 180px;">المستفيد / المنشأة</th>
                            <th style="min-width: 120px;">رقم الهوية / الإقامة</th>
                            <th style="min-width: 130px;">🪪 الإقامة / السجل التجاري</th>
                            <th style="min-width: 130px;">💼 رخصة العمل / البلدية</th>
                            <th style="min-width: 130px;">🏥 التأمين / الدفاع المدني</th>
                            <th style="min-width: 130px;">📝 عقد العمل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matrixData as $index => $row)
                            @if (!$row['is_company'])
                                <!-- Employee Row -->
                                <tr>
                                    <td style="font-weight: 600; color: #94a3b8;">{{ ($matrixData->currentPage() - 1) * $matrixData->perPage() + $loop->iteration }}</td>
                                    
                                    <!-- Name & Company -->
                                    <td style="text-align: right; padding-right: 1.1rem;">
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;" class="dark:text-white">{{ $row['name'] }}</div>
                                        @if(!empty($row['company_name']))
                                            <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.1rem;">🏢 {{ $row['company_name'] }}</div>
                                        @endif
                                    </td>

                                    <!-- Masked Iqama Number -->
                                    <td>
                                        <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.77rem; font-weight: 600; background: #f1f5f9; padding: 0.15rem 0.45rem; border-radius: 0.35rem; border: 1px solid #e2e8f0; color: #334155; direction: ltr; display: inline-block;" class="dark:bg-gray-800 dark:border-white/10 dark:text-gray-200">
                                            {{ $row['iqama_number'] }}
                                        </span>
                                    </td>

                                    <!-- 1. Iqama -->
                                    <td>
                                        @if(!empty($row['iqama']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['iqama']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['iqama']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['iqama']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['iqama']['icon'] }} {{ $row['iqama']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 2. Work Permit -->
                                    <td>
                                        @if(!empty($row['work_permit']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['work_permit']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['work_permit']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['work_permit']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['work_permit']['icon'] }} {{ $row['work_permit']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 3. Insurance -->
                                    <td>
                                        @if(!empty($row['insurance']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['insurance']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['insurance']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['insurance']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['insurance']['icon'] }} {{ $row['insurance']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 4. Contract -->
                                    <td>
                                        @if(!empty($row['contract']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['contract']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['contract']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['contract']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['contract']['icon'] }} {{ $row['contract']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                <!-- Company Licenses Row (Structured in the exact 4 columns) -->
                                <tr class="fi-tr-company">
                                    <td style="font-weight: 700; color: #2563eb;">{{ ($matrixData->currentPage() - 1) * $matrixData->perPage() + $loop->iteration }}</td>
                                    <td style="text-align: right; padding-right: 1.1rem;">
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;" class="dark:text-white">
                                            <span>🏢</span>
                                            <span>{{ $row['company_name'] }}</span>
                                            <span style="font-size: 0.68rem; background-color: #eff6ff; color: #2563eb; padding: 0.1rem 0.4rem; border-radius: 0.3rem; font-weight: 600;" class="dark:bg-blue-900/30 dark:text-blue-400">منشأة</span>
                                        </div>
                                    </td>
                                    <td style="color: #94a3b8;">—</td>

                                    <!-- 1. Commercial Registration (السجل التجاري) -->
                                    <td>
                                        @if(!empty($row['cr']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['cr']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['cr']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['cr']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['cr']['icon'] }} {{ $row['cr']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 2. Municipality License (رخصة البلدية) -->
                                    <td>
                                        @if(!empty($row['municipality']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['municipality']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['municipality']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['municipality']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['municipality']['icon'] }} {{ $row['municipality']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 3. Civil Defense License (الدفاع المدني) -->
                                    <td>
                                        @if(!empty($row['civil_defense']))
                                            <div class="matrix-doc-cell">
                                                <span class="matrix-doc-date">{{ $row['civil_defense']['date'] }}</span>
                                                <span class="fi-badge-custom {{ $row['civil_defense']['class'] === 'badge-danger' ? 'fi-badge-danger' : ($row['civil_defense']['class'] === 'badge-warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                                    {{ $row['civil_defense']['icon'] }} {{ $row['civil_defense']['text'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="fi-badge-gray">—</span>
                                        @endif
                                    </td>

                                    <!-- 4. Contract (Not applicable for companies) -->
                                    <td>
                                        <span class="fi-badge-gray">—</span>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                    <div style="font-size: 2rem; margin-bottom: 0.35rem;">🔍</div>
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #475569;" class="dark:text-gray-300">لا توجد سجلات مطابقة لمعايير البحث</div>
                                    <div style="font-size: 0.78rem; margin-top: 0.2rem; color: #94a3b8;">يرجى تجربة تعديل الكيان أو إزالة التواريخ المحددة.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($matrixData->total() > 0)
                    <div style="padding: 0.65rem 1rem; border-top: 1px solid #e2e8f0;" class="dark:border-white/10">
                        <x-filament::pagination :paginator="$matrixData" :page-options="[10, 20, 50, 100]" current-page-option-property="perPage" />
                    </div>
                @endif
            </div>
        @endif

        <!-- 4. VIEW MODE B: List View -->
        @if ($viewMode === 'list')
            <div class="fi-filter-bar" style="padding: 0.6rem 0.9rem;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.6rem;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        <button wire:click="setCategory('all')" class="fi-tab-item {{ $category === 'all' ? 'active' : '' }}" style="{{ $category === 'all' ? 'background-color: #ffffff; color: #0f172a; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);' : 'background-color: #f1f5f9; color: #64748b;' }}">
                            الكل ({{ $counts['total'] }})
                        </button>
                        <button wire:click="setCategory('documents')" class="fi-tab-item {{ $category === 'documents' ? 'active' : '' }}" style="{{ $category === 'documents' ? 'background-color: #ffffff; color: #0f172a; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);' : 'background-color: #f1f5f9; color: #64748b;' }}">
                            🪪 وثائق وعقود ({{ $counts['documents'] }})
                        </button>
                        <button wire:click="setCategory('violations')" class="fi-tab-item {{ $category === 'violations' ? 'active' : '' }}" style="{{ $category === 'violations' ? 'background-color: #ffffff; color: #0f172a; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);' : 'background-color: #f1f5f9; color: #64748b;' }}">
                            ⚖️ مخالفات وغرامات ({{ $counts['violations'] ?? 0 }})
                        </button>
                        <button wire:click="setCategory('tasks')" class="fi-tab-item {{ $category === 'tasks' ? 'active' : '' }}" style="{{ $category === 'tasks' ? 'background-color: #ffffff; color: #0f172a; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);' : 'background-color: #f1f5f9; color: #64748b;' }}">
                            📋 مهام عمل ({{ $counts['tasks'] }})
                        </button>
                        <button wire:click="setCategory('invoices')" class="fi-tab-item {{ $category === 'invoices' ? 'active' : '' }}" style="{{ $category === 'invoices' ? 'background-color: #ffffff; color: #0f172a; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);' : 'background-color: #f1f5f9; color: #64748b;' }}">
                            💰 فواتير واستحقاقات ({{ $counts['invoices'] }})
                        </button>
                    </div>

                    <select wire:model.live="urgency" class="fi-custom-input" style="width: auto; min-width: 150px; height: 32px; font-size: 0.78rem;">
                        <option value="all">كل درجات الإلحاح</option>
                        <option value="urgent">عاجلة فقط (≤ 15 يوم)</option>
                        <option value="overdue">منتهية بالفعل</option>
                        <option value="upcoming">قادمة مستقبلاً</option>
                    </select>
                </div>
            </div>

            <div class="fi-table-container">
                <table class="fi-native-table">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">نوع التنبيه</th>
                            <th style="text-align: right; padding-right: 1.1rem; min-width: 150px;">المعني / المستلم</th>
                            <th style="text-align: right; min-width: 160px;">التفاصيل / الجهة</th>
                            <th style="min-width: 95px;">تاريخ الاستحقاق</th>
                            <th style="min-width: 110px;">الحالة والمتبقي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paginatedAlerts as $item)
                            <tr>
                                <td>
                                    <span class="fi-badge-custom {{ $item['category'] === 'documents' ? 'fi-badge-success' : ($item['category'] === 'violations' ? 'fi-badge-danger' : ($item['category'] === 'tasks' ? 'fi-badge-warning' : 'fi-badge-success')) }}">
                                        {{ $item['type_label'] }}
                                    </span>
                                </td>
                                <td style="text-align: right; padding-right: 1.1rem; font-weight: 700;">
                                    <div style="color: #0f172a;" class="dark:text-white">{{ $item['title'] }}</div>
                                </td>
                                <td style="text-align: right; color: #64748b;">
                                    <div style="font-weight: 600; color: #334155;" class="dark:text-gray-300">{{ $item['subtitle'] }}</div>
                                    @if(!empty($item['company_name']))
                                        <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 0.1rem;">🏢 {{ $item['company_name'] }}</div>
                                    @endif
                                </td>
                                <td style="font-weight: 600; white-space: nowrap; font-size: 0.78rem;">
                                    {{ $item['due_date'] }}
                                </td>
                                <td>
                                    <span class="fi-badge-custom {{ $item['urgency_level'] === 'overdue' || $item['urgency_level'] === 'critical' ? 'fi-badge-danger' : ($item['urgency_level'] === 'warning' ? 'fi-badge-warning' : 'fi-badge-success') }}">
                                        {{ $item['status_text'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                    <div style="font-size: 2rem; margin-bottom: 0.35rem;">🎉</div>
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #475569;" class="dark:text-gray-300">لا توجد تنبيهات نشطة حالياً</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($paginatedAlerts->total() > 0)
                    <div style="padding: 0.65rem 1rem; border-top: 1px solid #e2e8f0;" class="dark:border-white/10">
                        <x-filament::pagination :paginator="$paginatedAlerts" :page-options="[10, 20, 50, 100]" current-page-option-property="perPage" />
                    </div>
                @endif
            </div>
        @endif
    </div>

</x-filament-panels::page>
