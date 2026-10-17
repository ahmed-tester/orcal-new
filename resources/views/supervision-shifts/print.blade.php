<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('tasks.supervision_shifts.print.document_title') }} - {{ $shift->title ?? ($shift->task?->name ?? '#' . $shift->id) }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
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
        .action-bar {
            max-width: 860px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #082c50;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #0b3d6f;
        }
        .btn-pdf {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background-color: #0369a1;
        }
        .btn-close {
            background-color: #e2e8f0;
            color: #475569;
        }
        .btn-close:hover {
            background-color: #cbd5e1;
        }
        .print-card {
            background: #ffffff;
            max-width: 860px;
            margin: 0 auto;
            border: 2px solid #082c50;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #082c50;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .company-info h2 {
            color: #082c50;
            font-size: 20px;
            margin-bottom: 4px;
        }
        .company-info p {
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }
        .doc-title-box {
            text-align: center;
            border: 2px solid #082c50;
            background-color: #f8fafc;
            padding: 8px 24px;
            border-radius: 8px;
        }
        .doc-title-box h1 {
            color: #082c50;
            font-size: 18px;
            margin-bottom: 4px;
        }
        .doc-meta {
            text-align: left;
            font-size: 11px;
            color: #334155;
            line-height: 1.6;
        }
        .section-box {
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }
        .section-header {
            background-color: #082c50;
            color: #ffffff;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-body {
            padding: 12px 16px;
            background-color: #ffffff;
        }
        .task-title-text {
            font-size: 16px;
            font-weight: bold;
            color: #082c50;
            margin-bottom: 6px;
        }
        .task-desc-text {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
        }
        .team-table {
            width: 100%;
            border-collapse: collapse;
        }
        .team-table th {
            background-color: #f1f5f9;
            color: #082c50;
            font-size: 11px;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #cbd5e1;
        }
        .team-table td {
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            text-align: center;
        }
        .team-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .info-item {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .info-item .label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .info-item .value {
            font-size: 13px;
            font-weight: bold;
            color: #082c50;
        }
        .supervisor-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
        }
        .supervisor-details h4 {
            color: #082c50;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .supervisor-details p {
            font-size: 11px;
            color: #475569;
            margin-bottom: 3px;
        }
        .approval-box {
            background-color: #f8fafc;
            border: 2px dashed #082c50;
            border-radius: 8px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .approval-info h4 {
            color: #082c50;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .approval-info p {
            color: #64748b;
            font-size: 11px;
        }
        .signature-area {
            text-align: center;
            width: 220px;
        }
        .signature-area p {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 35px;
        }
        .signature-line {
            border-bottom: 1px solid #475569;
            width: 100%;
        }
        .footer-note {
            margin-top: 18px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .print-card {
                border: 1px solid #082c50;
                box-shadow: none;
                max-width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- شريط الإجراءات والطباعة العلوي -->
    <div class="action-bar">
        <div style="font-weight: bold; color: #082c50;">
            {{ __('tasks.supervision_shifts.print.document_title') }}: {{ $shift->title ?? ($shift->task?->name ?? '#' . $shift->id) }}
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="action-btn btn-print">
                {{ __('tasks.supervision_shifts.actions.print_shift') }}
            </button>
            <a href="{{ route('supervision-shifts.pdf', $shift) }}" class="action-btn btn-pdf">
                {{ __('tasks.supervision_shifts.actions.export_pdf') }}
            </a>
            <button onclick="window.close()" class="action-btn btn-close">
                {{ __('tasks.supervision_shifts.actions.close') }}
            </button>
        </div>
    </div>

    <!-- بطاقة كشف التكليف الرسمية -->
    <div class="print-card">

        <!-- الهيدر الرسمي -->
        <div class="header">
            <div class="company-info">
                <h2>{{ $shift->company?->trade_name ?? config('app.name', 'مجموعة فواز القابضة') }}</h2>
                @if($shift->company?->commercial_registration_number)
                    <p>{{ __('tasks.supervision_shifts.print.cr_label') }} {{ $shift->company->commercial_registration_number }}</p>
                @endif
                <p>{{ __('tasks.supervision_shifts.print.company_subtitle') }}</p>
            </div>

            <div class="doc-title-box">
                <h1>{{ $shift->type === 'shift' ? __('tasks.supervision_shifts.types.shift') : __('tasks.supervision_shifts.types.supervision') }}</h1>
                <div style="font-size: 11px; font-weight: bold; color: #082c50; margin-top: 4px;">
                    {{ __('tasks.supervision_shifts.print.document_title') }} #{{ $shift->id }}
                </div>
            </div>

            <div class="doc-meta">
                <div><strong>{{ __('tasks.supervision_shifts.print.issue_date_label') }}</strong> {{ now()->format('Y/m/d') }} {{ __('tasks.supervision_shifts.print.gregorian') }}</div>
                <div><strong>{{ __('tasks.supervision_shifts.print.hijri_date_label') }}</strong> {{ \App\Services\HijriDateService::toHijri(now()) }} {{ __('tasks.supervision_shifts.print.hijri') }}</div>
                @if($shift->creator)
                    <div><strong>{{ __('tasks.supervision_shifts.print.creator_label') }}</strong> {{ $shift->creator->name }}</div>
                @endif
            </div>
        </div>

        <!-- 1. العنوان: اسم المهمة والتكليف -->
        <div class="section-box">
            <div class="section-header">
                <span>{{ __('tasks.supervision_shifts.print.task_title_section') }}</span>
                <span>{{ __('tasks.supervision_shifts.print.status_label') }} {{ __('tasks.supervision_shifts.statuses.' . $shift->status) }}</span>
            </div>
            <div class="section-body">
                <div class="task-title-text">
                    {{ $shift->title ?: ($shift->task?->name ?? __('tasks.supervision_shifts.print.default_supervision_title')) }}
                </div>
                @if($shift->tasks || $shift->notes)
                    <div class="task-desc-text">
                        <strong>{{ __('tasks.supervision_shifts.print.task_details') }}:</strong>
                        {{ $shift->tasks ?: $shift->notes }}
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. كشف بأسماء الفريق مرتبة بالأرقام أو الترتيب -->
        <div class="section-box">
            <div class="section-header">
                <span>{{ __('tasks.supervision_shifts.print.team_members_section') }}</span>
                <span>{{ __('tasks.supervision_shifts.print.total_team_count') }}: {{ count($shift->assigned_employees_models) }}</span>
            </div>
            <div class="section-body" style="padding: 0;">
                <table class="team-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">{{ __('tasks.supervision_shifts.print.col_index') }}</th>
                            <th style="text-align: right; padding-right: 12px;">{{ __('tasks.supervision_shifts.print.col_name') }}</th>
                            <th style="width: 100px;">{{ __('tasks.supervision_shifts.print.col_emp_num') }}</th>
                            <th style="width: 120px;">{{ __('tasks.supervision_shifts.print.col_job_title') }}</th>
                            <th style="width: 110px;">{{ __('tasks.supervision_shifts.print.col_dept') }}</th>
                            <th style="width: 95px;">{{ __('tasks.supervision_shifts.print.col_phone') }}</th>
                            <th style="width: 110px;">{{ __('tasks.supervision_shifts.print.col_signature') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shift->assigned_employees_models as $index => $employee)
                            <tr>
                                <td style="font-weight: bold; background-color: #f8fafc;">{{ $index + 1 }}</td>
                                <td style="text-align: right; padding-right: 12px; font-weight: bold; color: #082c50;">
                                    {{ $employee->user?->name ?? ('موظف #' . $employee->id) }}
                                </td>
                                <td>{{ $employee->employee_number ?? '—' }}</td>
                                <td>{{ $employee->jobTitle?->name ?? '—' }}</td>
                                <td>{{ $employee->department?->name ?? '—' }}</td>
                                <td>{{ $employee->user?->phone ?? '—' }}</td>
                                <td>
                                    <div style="border-bottom: 1px dashed #cbd5e1; height: 16px; width: 85%; margin: 0 auto;"></div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 16px; color: #64748b; font-style: italic;">
                                    {{ __('tasks.supervision_shifts.print.no_team_members') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. مدة المهمة وفترة التكليف -->
        <div class="section-box">
            <div class="section-header">
                <span>{{ __('tasks.supervision_shifts.print.duration_section') }}</span>
                @if($shift->start_time || $shift->end_time)
                    <span>{{ __('tasks.supervision_shifts.print.daily_hours') }}: {{ $shift->start_time ? substr($shift->start_time, 0, 5) : '—' }} ➔ {{ $shift->end_time ? substr($shift->end_time, 0, 5) : '—' }}</span>
                @endif
            </div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">{{ __('tasks.supervision_shifts.fields.start_date') }} ({{ __('tasks.supervision_shifts.print.gregorian') }} / {{ __('tasks.supervision_shifts.print.hijri') }}):</div>
                        <div class="value">
                            {{ $shift->start_date ? $shift->start_date->format('Y/m/d') : __('tasks.table.not_specified') }} {{ __('tasks.supervision_shifts.print.gregorian') }}
                            @if($shift->start_date)
                                <span style="font-size: 11px; color: #64748b; font-weight: normal;">({{ \App\Services\HijriDateService::toHijriNumeric($shift->start_date) }} {{ __('tasks.supervision_shifts.print.hijri') }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">{{ __('tasks.supervision_shifts.fields.end_date') }} ({{ __('tasks.supervision_shifts.print.gregorian') }} / {{ __('tasks.supervision_shifts.print.hijri') }}):</div>
                        <div class="value">
                            {{ $shift->end_date ? $shift->end_date->format('Y/m/d') : __('tasks.table.not_specified') }} {{ __('tasks.supervision_shifts.print.gregorian') }}
                            @if($shift->end_date)
                                <span style="font-size: 11px; color: #64748b; font-weight: normal;">({{ \App\Services\HijriDateService::toHijriNumeric($shift->end_date) }} {{ __('tasks.supervision_shifts.print.hijri') }})</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. مشرف المهمة / المناوب المسؤول -->
        <div class="section-box">
            <div class="section-header">
                <span>{{ __('tasks.supervision_shifts.print.supervisor_section') }}</span>
                <span>{{ $shift->supervisor?->jobTitle?->name ?? __('tasks.supervision_shifts.print.certified_supervisor') }}</span>
            </div>
            <div class="section-body">
                <div class="supervisor-card">
                    <div class="supervisor-details">
                        <h4>{{ $shift->supervisor?->user?->name ?? __('tasks.supervision_shifts.table.not_specified') }}</h4>
                        @if($shift->supervisor?->employee_number)
                            <p><strong>{{ __('tasks.supervision_shifts.print.col_emp_num') }}:</strong> {{ $shift->supervisor->employee_number }}</p>
                        @endif
                        @if($shift->supervisor?->department?->name)
                            <p><strong>{{ __('tasks.supervision_shifts.print.col_dept') }}:</strong> {{ $shift->supervisor->department->name }}</p>
                        @endif
                        @if($shift->supervisor?->user?->phone)
                            <p><strong>{{ __('tasks.supervision_shifts.fields.supervisor_phone') }}:</strong> {{ $shift->supervisor->user->phone }}</p>
                        @endif
                    </div>
                    <div class="signature-area">
                        <p>{{ __('tasks.supervision_shifts.print.supervisor_signature') }}</p>
                        <div class="signature-line"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. اعتماد مدير المهمة (ليكون الكشف عند مدير المهمة كما هو مطلوب) -->
        <div class="section-box" style="border: 2px solid #082c50;">
            <div class="section-header" style="background-color: #082c50;">
                <span>{{ __('tasks.supervision_shifts.print.task_manager_section') }}</span>
                <span>{{ __('tasks.supervision_shifts.print.approval_subtitle') }}</span>
            </div>
            <div class="section-body">
                <div class="approval-box">
                    <div class="approval-info">
                        <h4>{{ __('tasks.supervision_shifts.print.task_manager_name') }}</h4>
                        <p>{{ __('tasks.supervision_shifts.print.approval_description') }}</p>
                        <p style="margin-top: 6px;"><strong>{{ __('tasks.supervision_shifts.print.approval_receipt_date') }}</strong> ____ / ____ / ________ {{ __('tasks.supervision_shifts.print.gregorian') }}</p>
                    </div>
                    <div class="signature-area">
                        <p>{{ __('tasks.supervision_shifts.print.task_manager_signature') }}</p>
                        <div class="signature-line"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('tasks.supervision_shifts.print.footer_official') }} — {{ $shift->company?->trade_name ?? config('app.name', 'مجموعة فواز') }}
        </div>
    </div>

</body>
</html>
