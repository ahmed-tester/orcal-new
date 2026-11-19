<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف توزيع المهام — {{ $dateFrom }} إلى {{ $dateTo }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body { background: #f8fafc; color: #0f172a; padding: 20px; direction: rtl; font-size: 13px; }

        /* ── Toolbar (hidden on print) ── */
        .print-toolbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; gap: 12px; flex-wrap: wrap;
        }
        @media print { .print-toolbar { display: none !important; } }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; transition: all .2s;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }

        /* ── Document wrapper ── */
        .document {
            background: #fff; max-width: 1150px; margin: 0 auto;
            border-radius: 10px; box-shadow: 0 2px 12px rgba(15,23,42,.08);
            overflow: hidden;
        }

        /* ── Header ── */
        .doc-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: #fff; padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .doc-header-title { font-size: 20px; font-weight: 800; margin-bottom: 6px; }
        .doc-header-sub { font-size: 12px; opacity: .85; }
        .doc-header-meta { text-align: left; font-size: 12px; opacity: .85; }

        /* ── Stats cards ── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 1px;
            background: #e2e8f0; border-bottom: 1px solid #e2e8f0;
        }
        .stat-card { background: #f8fafc; padding: 14px 16px; }
        .stat-label { font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px; }
        .stat-value { font-size: 22px; font-weight: 800; }
        .stat-value.blue { color: #1d4ed8; }
        .stat-value.green { color: #15803d; }
        .stat-value.red { color: #b91c1c; }
        .stat-value.amber { color: #b45309; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 900px; }
        thead tr { background: #1e3a8a; color: #fff; }
        thead th { padding: 10px 8px; font-weight: 700; white-space: nowrap; text-align: start; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        tbody td { padding: 8px; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
        .col-name { min-width: 160px; white-space: normal; }
        .col-num { text-align: center; }
        .pct { font-weight: 700; }
        .overdue-cell { color: #b91c1c; font-weight: 700; }
        .tfoot-row { background: #1e3a8a; color: #fff; font-weight: 700; }
        .tfoot-row td { padding: 10px 8px; }

        /* ── Empty state ── */
        .empty-state { padding: 40px; text-align: center; color: #94a3b8; }

        /* ── Print adjustments ── */
        @media print {
            body { background: #fff; padding: 0; }
            .document { box-shadow: none; border-radius: 0; max-width: 100%; }
            thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tfoot-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="print-toolbar">
        <div>
            <strong>كشف توزيع المهام</strong>
            <span style="color:#64748b; margin-right:8px; font-size:12px;">{{ $dateFrom }} — {{ $dateTo }}</span>
        </div>
        <div style="display:flex; gap:8px;">
            <button class="btn btn-primary" onclick="window.print()">🖨 طباعة</button>
            <button class="btn btn-secondary" onclick="window.close()">✕ إغلاق</button>
        </div>
    </div>

    <div class="document">
        <!-- Header -->
        <div class="doc-header">
            <div>
                <div class="doc-header-title">كشف توزيع المهام</div>
                <div class="doc-header-sub">الفترة من {{ $dateFrom }} إلى {{ $dateTo }}</div>
            </div>
            <div class="doc-header-meta">
                تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>

        <!-- Stats -->
        @php
            $totalItems     = (int) ($stats->total_items ?? 0);
            $completedCount = (int) ($stats->completed_count ?? 0);
            $overdueCount   = (int) ($stats->overdue_count ?? 0);
            $postponedCount = (int) ($stats->postponed_count ?? 0);
            $employeesCount = (int) ($stats->employees_count ?? $rows->count());
        @endphp
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">عدد الموظفين</div>
                <div class="stat-value blue">{{ number_format($employeesCount) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">إجمالي المهام</div>
                <div class="stat-value blue">{{ number_format($totalItems) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">المكتملة في الفترة</div>
                <div class="stat-value green">{{ number_format((int) ($stats->completed_in_period ?? 0)) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">متأخرة الآن</div>
                <div class="stat-value red">{{ number_format($overdueCount) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">مؤجلة</div>
                <div class="stat-value amber">{{ number_format($postponedCount) }}</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="col-name">الموظف</th>
                        <th>الشركة</th>
                        <th>الفرع</th>
                        <th>القسم</th>
                        <th class="col-num">إجمالي</th>
                        <th class="col-num">مكتملة</th>
                        <th class="col-num">جارية</th>
                        <th class="col-num">معلقة</th>
                        <th class="col-num">مؤجلة</th>
                        <th class="col-num">متأخرة</th>
                        <th class="col-num">مكتملة/فترة</th>
                        <th class="col-num">مستهدفة/فترة</th>
                        <th class="col-num">نسبة الإنجاز</th>
                        <th class="col-num">نسبة الالتزام</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        @php
                            $completionRate = \App\Services\TaskAssignmentSummaryService::completionRate($row);
                            $onTimeRate     = \App\Services\TaskAssignmentSummaryService::onTimeRate($row);
                            $weighted       = \App\Services\TaskAssignmentSummaryService::weightedPercentage($row);
                        @endphp
                        <tr>
                            <td style="color:#94a3b8; font-size:11px;">{{ $index + 1 }}</td>
                            <td class="col-name">
                                <div style="font-weight:700;">{{ $row->user?->name ?? $row->employee_number ?? '#'.$row->id }}</div>
                                <div style="font-size:10px; color:#94a3b8;"><bdi>{{ $row->employee_number }}</bdi></div>
                            </td>
                            <td>{{ $row->company?->trade_name ?? '—' }}</td>
                            <td>{{ $row->branch?->name ?? '—' }}</td>
                            <td>{{ $row->department?->name ?? '—' }}</td>
                            <td class="col-num">{{ (int) $row->total_items }}</td>
                            <td class="col-num" style="color:#15803d;">{{ (int) $row->completed_count }}</td>
                            <td class="col-num">{{ (int) $row->in_progress_count }}</td>
                            <td class="col-num">{{ (int) $row->pending_count }}</td>
                            <td class="col-num">{{ (int) $row->postponed_count }}</td>
                            <td class="col-num overdue-cell">{{ (int) $row->overdue_count }}</td>
                            <td class="col-num">{{ (int) $row->completed_in_period }}</td>
                            <td class="col-num">{{ (int) $row->due_in_period }}</td>
                            <td class="col-num pct">
                                @if((float)($row->weight_sum ?? 0) > 0)
                                    <bdi>{{ number_format($weighted, 2) }}%</bdi>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td class="col-num pct">
                                @if($completionRate !== null)
                                    <bdi>{{ number_format($completionRate, 2) }}%</bdi>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="empty-state">لا توجد بيانات للطباعة</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot>
                        <tr class="tfoot-row">
                            <td colspan="5">الإجمالي الكلي</td>
                            <td class="col-num">{{ (int) ($stats->total_items ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->completed_count ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->in_progress_count ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->pending_count ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->postponed_count ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->overdue_count ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->completed_in_period ?? 0) }}</td>
                            <td class="col-num">{{ (int) ($stats->due_in_period ?? 0) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog when page fully loads
        window.addEventListener('load', function () {
            // Small delay so the page renders first
            setTimeout(function () {
                // Only auto-print if opened as a popup / new tab
                if (window.opener || window.name === 'task-print') {
                    window.print();
                }
            }, 400);
        });
    </script>
</body>
</html>
