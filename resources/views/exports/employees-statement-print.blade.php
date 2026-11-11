<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'كشف وبيان الموظفين والمُلّاك' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 16px;
            direction: rtl;
        }
        .print-toolbar {
            max-width: 1200px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-success { background-color: #059669; color: #ffffff; }
        .btn-success:hover { background-color: #047857; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }
        .tab-btn {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .tab-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .tab-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .report-card {
            background: #ffffff;
            max-width: 1200px;
            margin: 0 auto;
            border: 1.5px solid #0f172a;
            border-radius: 8px;
            padding: 20px 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #1e3a8a;
        }
        .report-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
        }
        .header-meta {
            text-align: left;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }

        .filter-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 11px;
        }
        .filter-tag {
            font-weight: 700;
            color: #1e3a8a;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
        }
        .stat-title {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 600;
        }
        .stat-value {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 4px;
        }
        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 7px 6px;
            font-weight: 700;
            font-size: 10.5px;
            border: 1px solid #0f172a;
            text-align: center;
            white-space: nowrap;
        }
        table.data-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: center;
            vertical-align: middle;
            color: #0f172a;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Capacity Badge Styles */
        .capacity-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 9.5px;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .capacity-employee {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .capacity-owner {
            background-color: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .capacity-dual {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 600;
        }
        .status-active {
            background-color: #ecfdf5;
            color: #065f46;
        }
        .status-inactive {
            background-color: #fef2f2;
            color: #991b1b;
        }

        .signature-section {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            text-align: center;
            font-size: 11px;
            color: #334155;
        }
        .signature-box {
            width: 28%;
        }
        .signature-line {
            margin-top: 36px;
            border-top: 1px dashed #94a3b8;
            padding-top: 4px;
            font-size: 10px;
            color: #64748b;
        }

        .footer-note {
            margin-top: 14px;
            text-align: center;
            font-size: 9.5px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .print-toolbar {
                display: none !important;
            }
            .report-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            table.data-table th {
                background-color: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            table.data-table tr:nth-child(even) {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .capacity-badge, .stat-box {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    {{-- Toolbar (Hidden during print) --}}
    <div class="print-toolbar">
        <div class="toolbar-group">
            <span style="font-weight: 700; font-size: 12.5px; color: #1e293b;">نوع الكشف عند العرض:</span>
            <a href="{{ request()->fullUrlWithQuery(['statement_type' => 'all', 'export_mode' => 'print']) }}" 
               class="tab-btn {{ $statement_type === 'all' ? 'active' : '' }}">
                📋 الكل (الموظفين والمُلّاك معاً)
            </a>
            <a href="{{ request()->fullUrlWithQuery(['statement_type' => 'employees_only', 'export_mode' => 'print']) }}" 
               class="tab-btn {{ $statement_type === 'employees_only' ? 'active' : '' }}">
                👤 كشف للموظفين فقط
            </a>
            <a href="{{ request()->fullUrlWithQuery(['statement_type' => 'owners_only', 'export_mode' => 'print']) }}" 
               class="tab-btn {{ $statement_type === 'owners_only' ? 'active' : '' }}">
                🏢 كشف للمُلّاك فقط
            </a>
        </div>

        <div class="toolbar-group">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ طباعة الكشف (Ctrl+P)
            </button>
            <a href="{{ request()->fullUrlWithQuery(['export_mode' => 'pdf']) }}" class="btn btn-success" target="_blank">
                📥 تحميل PDF
            </a>
            <button onclick="window.close()" class="btn btn-secondary">
                ✕ إغلاق
            </button>
        </div>
    </div>

    {{-- Report Paper Sheet --}}
    <div class="report-card">
        <table class="header-table">
            <tr>
                <td style="width: 33%; vertical-align: middle;">
                    <div class="company-name">{{ $company_title }}</div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">نظام إدارة الموارد البشرية والمنشآت</div>
                </td>
                <td style="width: 34%; vertical-align: middle; text-align: center;">
                    <div class="report-title">{{ $title }}</div>
                    <div style="font-size: 11px; color: #475569; margin-top: 3px; font-weight: 600;">{{ $type_label }}</div>
                </td>
                <td style="width: 33%; vertical-align: middle; text-align: left;">
                    <div class="header-meta">
                        <div><strong>تاريخ الاستخراج:</strong> {{ $date_label }}</div>
                        <div><strong>التاريخ الهجري:</strong> {{ $hijri_date }}</div>
                        <div><strong>المنفذ:</strong> {{ $printed_by }}</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Filters Summary --}}
        <div class="filter-summary">
            <div>
                <span>المنشأة: </span>
                <span class="filter-tag">{{ $company_title }}</span>
            </div>
            <div>
                <span>الحالة: </span>
                <span class="filter-tag">{{ $status === 'active' ? 'النشطين / على رأس العمل فقط' : 'كافة الحالات (نشط وغير نشط)' }}</span>
            </div>
            <div>
                <span>إجمالي المسجلين بالكشف: </span>
                <span class="filter-tag">{{ count($records) }} سجل</span>
            </div>
        </div>

        {{-- Statistics Bar --}}
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-title">إجمالي السجلات</div>
                <div class="stat-value">{{ count($records) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">الموظفون فقط</div>
                <div class="stat-value" style="color: #2563eb;">{{ $stats['employees_only_count'] ?? 0 }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">المُلّاك فقط</div>
                <div class="stat-value" style="color: #059669;">{{ $stats['owners_only_count'] ?? 0 }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">موظف ومالك منشأة</div>
                <div class="stat-value" style="color: #d97706;">{{ $stats['dual_count'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Data Table --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 140px; text-align: right; padding-right: 8px;">الاسم الكامل</th>
                    <th style="width: 120px;">الصفة</th>
                    <th style="width: 95px;">الهوية / الإقامة</th>
                    <th style="width: 110px;">المنشأة / الشركة</th>
                    <th style="width: 100px;">الفرع / القسم</th>
                    <th style="width: 120px;">المسمى الوظيفي / المهنة</th>
                    <th style="width: 95px;">رقم الجوال</th>
                    <th style="width: 65px;">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $index => $row)
                    @php
                        $badgeClass = match($row['capacity_code'] ?? '') {
                            'owner' => 'capacity-owner',
                            'employee_and_owner' => 'capacity-dual',
                            default => 'capacity-employee',
                        };
                        $statusClass = ($row['status'] === 'active') ? 'status-active' : 'status-inactive';
                        $statusLabel = match($row['status']) {
                            'active' => 'نشط',
                            'inactive' => 'غير نشط',
                            'on_leave' => 'في إجازة',
                            'suspended' => 'موقوف',
                            'terminated' => 'منتهي',
                            default => $row['status'],
                        };
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="text-align: right; font-weight: 700; padding-right: 8px;">{{ $row['name'] }}</td>
                        <td>
                            <span class="capacity-badge {{ $badgeClass }}">
                                {{ $row['capacity'] }}
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 10px;">{{ $row['national_id'] }}</td>
                        <td>{{ $row['company_name'] }}</td>
                        <td>
                            {{ $row['branch_name'] !== '—' ? $row['branch_name'] : '' }}
                            {{ $row['branch_name'] !== '—' && $row['department_name'] !== '—' ? ' / ' : '' }}
                            {{ $row['department_name'] }}
                        </td>
                        <td>{{ $row['job_title'] }}</td>
                        <td style="font-family: monospace; font-size: 10px; direction: ltr; text-align: center;">{{ $row['phone'] }}</td>
                        <td>
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 24px; text-align: center; color: #64748b;">
                            لا توجد بيانات مطابقة لخيارات الفرز المحددة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures Section --}}
        <div class="signature-section">
            <div class="signature-box">
                <strong>إعداد الموارد البشرية</strong>
                <div class="signature-line">التوقيع / التاريخ</div>
            </div>
            <div class="signature-box">
                <strong>مراجعة الحسابات والمالية</strong>
                <div class="signature-line">التوقيع / التاريخ</div>
            </div>
            <div class="signature-box">
                <strong>اعتماد الإدارة العامة / الختم</strong>
                <div class="signature-line">الختم الرسمي</div>
            </div>
        </div>

        <div class="footer-note">
            تم استخراج هذا الكشف آلياً بواسطة نظام Orcal الإداري والمالي في {{ now()->format('Y/m/d H:i') }} — الصفحة 1 من 1
        </div>
    </div>

</body>
</html>

