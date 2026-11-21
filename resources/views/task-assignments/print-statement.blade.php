<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيان مهام — {{ $employee->user?->name ?? $employee->employee_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body { background: #f8fafc; color: #0f172a; padding: 20px; direction: rtl; font-size: 13px; }

        /* ── Toolbar (screen only) ── */
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

        /* ── Document ── */
        .document {
            background: #fff; max-width: 820px; margin: 0 auto;
            border-radius: 10px; box-shadow: 0 2px 12px rgba(15,23,42,.08);
            overflow: hidden;
        }

        /* ── Header ── */
        .doc-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: #fff; padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .doc-header-title { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .doc-header-sub { font-size: 12px; opacity: .85; }
        .doc-header-meta { text-align: left; font-size: 12px; opacity: .85; }

        /* ── Employee info card ── */
        .employee-card {
            padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
            display: flex; flex-wrap: wrap; gap: 1rem;
        }
        .employee-name { font-size: 17px; font-weight: 800; color: #1d4ed8; flex: 100%; }
        .employee-meta-item { display: flex; flex-direction: column; gap: 2px; min-width: 140px; }
        .employee-meta-label { font-size: 11px; font-weight: 600; color: #64748b; }
        .employee-meta-value { font-size: 13px; font-weight: 700; }

        /* ── Task item card ── */
        .task-item {
            margin: 12px 20px; padding: 14px;
            border: 1px solid #e2e8f0; border-radius: 8px;
            page-break-inside: avoid;
        }
        .task-item:last-child { margin-bottom: 20px; }
        .task-item-header {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 8px;
            padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;
            margin-bottom: 10px;
        }
        .task-name { font-size: 14px; font-weight: 800; }
        .task-code {
            font-size: 11px; font-weight: 600; color: #475569;
            background: #f1f5f9; padding: 2px 8px; border-radius: 999px;
        }
        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .badge-amber  { background: #fef3c7; color: #b45309; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-gray   { background: #f1f5f9; color: #475569; }
        .badge-purple { background: #f3e8ff; color: #7e22ce; }
        .badge-orange { background: #fff7ed; color: #c2410c; }

        /* ── Task meta grid ── */
        .task-meta {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
        }
        .task-meta-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 6px; padding: 6px 10px; }
        .task-meta-label { font-size: 10px; font-weight: 600; color: #64748b; }
        .task-meta-value { font-size: 13px; font-weight: 700; margin-top: 2px; }

        /* ── Notes section ── */
        .task-notes {
            margin-top: 8px; padding: 8px 10px;
            background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px;
            font-size: 12px; color: #78350f;
        }

        /* ── Empty state ── */
        .empty-state { padding: 40px; text-align: center; color: #94a3b8; }

        /* ── Print adjustments ── */
        @media print {
            body { background: #fff; padding: 0; }
            .document { box-shadow: none; border-radius: 0; max-width: 100%; }
            .doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="print-toolbar">
        <div>
            <strong>بيان مهام الموظف</strong>
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
                <div class="doc-header-title">بيان مهام الموظف</div>
                <div class="doc-header-sub">الفترة من {{ $dateFrom }} إلى {{ $dateTo }}</div>
            </div>
            <div class="doc-header-meta">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
        </div>

        <!-- Employee card -->
        <div class="employee-card">
            <div class="employee-name">{{ $employee->user?->name ?? $employee->employee_number }}</div>
            @if($employee->employee_number)
                <div class="employee-meta-item">
                    <span class="employee-meta-label">رقم الموظف</span>
                    <span class="employee-meta-value"><bdi>{{ $employee->employee_number }}</bdi></span>
                </div>
            @endif
            @if($employee->company?->trade_name)
                <div class="employee-meta-item">
                    <span class="employee-meta-label">الشركة</span>
                    <span class="employee-meta-value">{{ $employee->company->trade_name }}</span>
                </div>
            @endif
            @if($employee->branch?->name)
                <div class="employee-meta-item">
                    <span class="employee-meta-label">الفرع</span>
                    <span class="employee-meta-value">{{ $employee->branch->name }}</span>
                </div>
            @endif
            @if($employee->department?->name)
                <div class="employee-meta-item">
                    <span class="employee-meta-label">القسم</span>
                    <span class="employee-meta-value">{{ $employee->department->name }}</span>
                </div>
            @endif
            <div class="employee-meta-item">
                <span class="employee-meta-label">عدد المهام</span>
                <span class="employee-meta-value">{{ $items->count() }}</span>
            </div>
        </div>

        <!-- Task items -->
        @forelse($items as $item)
            @php
                $statusColor = match($item->status?->value ?? '') {
                    'completed'              => 'badge-green',
                    'in_progress'            => 'badge-blue',
                    'partial_in_progress'    => 'badge-blue',
                    'pending'                => 'badge-gray',
                    'pending_review'         => 'badge-amber',
                    'returned_for_revision'  => 'badge-red',
                    'postponed'              => 'badge-orange',
                    'transferred'            => 'badge-purple',
                    'escalated'              => 'badge-red',
                    default                  => 'badge-gray',
                };
            @endphp
            <div class="task-item">
                <div class="task-item-header">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                        <span class="task-name">{{ $item->task?->name ?? '—' }}</span>
                        @if($item->taskAssignment?->transaction_number)
                            <span class="task-code"><bdi>{{ $item->taskAssignment->transaction_number }}</bdi></span>
                        @endif
                        <span class="badge {{ $statusColor }}">{{ $item->status?->getLabel() ?? '—' }}</span>
                    </div>
                </div>

                <dl class="task-meta">
                    <div class="task-meta-item">
                        <dt class="task-meta-label">تاريخ الاستحقاق</dt>
                        <dd class="task-meta-value"><bdi>{{ $item->due_date?->format('Y-m-d') ?? '—' }}</bdi></dd>
                    </div>
                    <div class="task-meta-item">
                        <dt class="task-meta-label">تاريخ الإنجاز</dt>
                        <dd class="task-meta-value"><bdi>{{ $item->completed_at?->format('Y-m-d') ?? '—' }}</bdi></dd>
                    </div>
                    <div class="task-meta-item">
                        <dt class="task-meta-label">الوزن</dt>
                        <dd class="task-meta-value"><bdi>{{ number_format((float) $item->weight, 2) }}%</bdi></dd>
                    </div>
                    <div class="task-meta-item">
                        <dt class="task-meta-label">الدرجة</dt>
                        <dd class="task-meta-value"><bdi>{{ number_format((float) $item->score, 2) }}</bdi></dd>
                    </div>
                </dl>

                @if(filled($item->notes))
                    <div class="task-notes">
                        <strong>ملاحظات:</strong> {{ $item->notes }}
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">لا توجد مهام في هذه الفترة</div>
        @endforelse
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                if (window.opener || window.name === 'task-print') {
                    window.print();
                }
            }, 400);
        });
    </script>
</body>
</html>
