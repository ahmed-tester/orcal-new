<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف الإجازات - {{ $dateFrom ?? 'البداية' }} إلى {{ $dateTo ?? 'النهاية' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
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
            padding: 20px;
            direction: rtl;
        }
        .print-toolbar {
            max-width: 1100px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #64748b; color: #ffffff; }
        .btn-secondary:hover { background-color: #475569; }

        .report-card {
            background: #ffffff;
            max-width: 1100px;
            margin: 0 auto;
            border: 2px solid #1e3a8a;
            border-radius: 10px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #1e3a8a;
            font-size: 20px;
            font-weight: 800;
        }
        .header-meta {
            text-align: left;
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }
        .filter-summary {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 8px 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12.5px;
        }
        .filter-tag {
            font-weight: 700;
            color: #1e40af;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
        }
        .stat-title {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }
        .stat-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-top: 8px;
        }
        th {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 8px 6px;
            font-weight: 700;
            text-align: center;
            border: 1px solid #1e3a8a;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: center;
            color: #1e293b;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10.5px;
            font-weight: 700;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .footer {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-toolbar {
                display: none !important;
            }
            .report-card {
                border: 1px solid #000000;
                box-shadow: none;
                padding: 10px 15px;
                max-width: 100%;
            }
            th {
                background-color: #f0f0f0 !important;
                color: #000000 !important;
                border: 1px solid #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            td {
                border: 1px solid #666666 !important;
            }
            .filter-summary {
                background: #f8f8f8 !important;
                border: 1px solid #999999 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <a href="javascript:window.close()" class="btn btn-secondary">إغلاق النافذة</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة الكشف / تصدير PDF</button>
    </div>

    <div class="report-card">
        <div class="header">
            <div>
                <h1>كشف وبيان طلبات الإجازات</h1>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    {{ $selectedCompany ? $selectedCompany->trade_name : (config('app.name', 'نظام الموارد البشرية')) }}
                </div>
            </div>
            <div class="header-meta">
                <div><strong>تاريخ الطباعة:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                <div><strong>المستخدم:</strong> {{ auth()->user()?->name ?? 'مدير النظام' }}</div>
            </div>
        </div>

        <div class="filter-summary">
            <div>
                <span>الفترة المحددة: </span>
                <span class="filter-tag">من: {{ $dateFrom ?: 'البداية' }} — إلى: {{ $dateTo ?: 'اليوم' }}</span>
            </div>
            @if($selectedCompany)
                <div>
                    <span>المنشأة: </span>
                    <span class="filter-tag">{{ $selectedCompany->trade_name }}</span>
                </div>
            @endif
            @if($selectedLeaveType)
                <div>
                    <span>نوع الإجازة: </span>
                    <span class="filter-tag">{{ $selectedLeaveType->name }}</span>
                </div>
            @endif
            <div>
                <span>الحالة: </span>
                <span class="filter-tag">
                    {{ match($status) {
                        'approved' => 'المعتمدة فقط',
                        'pending' => 'المعلقة فقط',
                        'rejected' => 'المرفوضة فقط',
                        default => 'جميع الحالات'
                    } }}
                </span>
            </div>
        </div>

        @php
            $totalDays = $leaves->sum('days');
            $approvedCount = $leaves->where('status', \App\Enums\LeaveStatus::APPROVED)->count();
            $pendingCount = $leaves->where('status', \App\Enums\LeaveStatus::PENDING)->count();
            $rejectedCount = $leaves->where('status', \App\Enums\LeaveStatus::REJECTED)->count();
        @endphp

        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-title">إجمالي الطلبات</div>
                <div class="stat-value">{{ $leaves->count() }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">إجمالي أيام الإجازات</div>
                <div class="stat-value" style="color: #2563eb;">{{ $totalDays }} يوم</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">الإجازات المعتمدة</div>
                <div class="stat-value" style="color: #16a34a;">{{ $approvedCount }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-title">قيد المراجعة / معلقة</div>
                <div class="stat-value" style="color: #d97706;">{{ $pendingCount }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>رقم الطلب</th>
                    <th>اسم الموظف</th>
                    <th>الرقم الوظيفي</th>
                    <th>الهوية / الإقامة</th>
                    <th>المنشأة / الفرع</th>
                    <th>نوع الإجازة</th>
                    <th>من تاريخ</th>
                    <th>إلى تاريخ</th>
                    <th>الأيام</th>
                    <th>الحالة</th>
                    <th>العودة الفعلية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight: 700;">#{{ $item->id }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ $item->employee?->user?->name ?? '—' }}</td>
                        <td>{{ $item->employee?->employee_number ?? ("#" . $item->employee?->id) }}</td>
                        <td>{{ $item->employee?->details?->iqama_number ?: ($item->employee?->details?->national_id ?: '—') }}</td>
                        <td>{{ $item->employee?->company?->trade_name ?? $item->employee?->branch?->name ?? '—' }}</td>
                        <td style="font-weight: 600; color: #1e40af;">{{ $item->leaveType?->name ?? '—' }}</td>
                        <td>{{ $item->start_date?->format('Y-m-d') }}</td>
                        <td>{{ $item->end_date?->format('Y-m-d') }}</td>
                        <td style="font-weight: 800; color: #2563eb;">{{ $item->days }}</td>
                        <td>
                            @if($item->status === \App\Enums\LeaveStatus::APPROVED)
                                <span class="status-badge status-approved">معتمدة</span>
                            @elseif($item->status === \App\Enums\LeaveStatus::REJECTED)
                                <span class="status-badge status-rejected">مرفوضة</span>
                            @else
                                <span class="status-badge status-pending">معلقة</span>
                            @endif
                        </td>
                        <td>{{ $item->actual_return_date?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="padding: 24px; text-align: center; color: #64748b;">
                            لا توجد إجازات مسجلة مطابقة للفترة والخيارات المحددة
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <div>إجمالي السجلات المستخرجة: <strong>{{ $leaves->count() }}</strong> سجل</div>
            <div>نظام {{ config('app.name', 'أوركال') }} — تقارير الإجازات</div>
        </div>
    </div>
</body>
</html>
