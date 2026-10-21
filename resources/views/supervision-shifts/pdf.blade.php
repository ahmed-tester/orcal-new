<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('tasks.supervision_shifts.print.document_title') }} - {{ $shift->title ?? ($shift->task?->name ?? '#' . $shift->id) }}</title>
    <style>
        body {
            font-family: 'dejavusans', sans-serif;
            direction: rtl;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.4;
            padding: 0;
            margin: 0;
            background: #ffffff;
        }
        .outer-frame {
            width: 100%;
            border: 2px solid #082c50;
            border-collapse: collapse;
        }
        .outer-frame > tr > td {
            padding: 12px 14px;
        }
        table {
            border-collapse: collapse;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #082c50;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #082c50;
            margin-bottom: 3px;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            line-height: 1.4;
        }
        .badge-table {
            border: 2px solid #082c50;
            background-color: #f8fafc;
            border-radius: 6px;
            margin: 0 auto;
        }
        .badge-title {
            font-size: 15px;
            font-weight: bold;
            color: #082c50;
        }
        .badge-sub {
            font-size: 9.5px;
            font-weight: bold;
            color: #082c50;
            margin-top: 3px;
        }
        .meta-text {
            font-size: 9px;
            color: #334155;
            line-height: 1.5;
            text-align: left;
        }
        .section-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            margin-bottom: 9px;
        }
        .sec-hdr-right {
            background-color: #082c50;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 8px;
            text-align: right;
            width: 65%;
        }
        .sec-hdr-left {
            background-color: #082c50;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 5px 8px;
            text-align: left;
            width: 35%;
        }
        .sec-body {
            padding: 8px 10px;
            background-color: #ffffff;
        }
        .task-title {
            font-size: 13px;
            font-weight: bold;
            color: #082c50;
            margin-bottom: 4px;
        }
        .task-desc {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.4;
        }
        .team-table {
            width: 100%;
            border-collapse: collapse;
        }
        .team-table th {
            background-color: #f1f5f9;
            color: #082c50;
            font-size: 9px;
            font-weight: bold;
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #cbd5e1;
        }
        .team-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            text-align: center;
        }
        .team-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .info-cell {
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .info-label {
            font-size: 8.5px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 10px;
            font-weight: bold;
            color: #082c50;
        }
        .signature-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 25px;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #475569;
            width: 85%;
            margin: 0 auto;
        }
        .footer-note {
            margin-top: 8px;
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

<table class="outer-frame">
    <tr>
        <td>
            <!-- الهيدر الرسمي -->
            <table class="header-table">
                <tr>
                    <td style="width: 38%; vertical-align: middle;">
                        <div class="company-name">{{ $shift->company?->trade_name ?? config('app.name', 'مجموعة فواز القابضة') }}</div>
                        @if($shift->company?->commercial_registration_number)
                            <div class="company-sub">{{ __('tasks.supervision_shifts.print.cr_label') }} {{ $shift->company->commercial_registration_number }}</div>
                        @endif
                        <div class="company-sub">{{ __('tasks.supervision_shifts.print.company_subtitle') }}</div>
                    </td>
                    <td style="width: 32%; text-align: center; vertical-align: middle;">
                        <table class="badge-table" align="center">
                            <tr>
                                <td style="padding: 6px 16px; text-align: center;">
                                    <div class="badge-title">{{ $shift->type === 'shift' ? 'مناوبة' : 'إشراف' }}</div>
                                    <div class="badge-sub">{{ __('tasks.supervision_shifts.print.document_title') }} #{{ $shift->id }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 30%; text-align: left; vertical-align: middle;">
                        <div class="meta-text">
                            <div><strong>{{ __('tasks.supervision_shifts.print.issue_date_label') }}</strong> {{ now()->format('Y/m/d') }} {{ __('tasks.supervision_shifts.print.gregorian') }}</div>
                            <div><strong>{{ __('tasks.supervision_shifts.print.hijri_date_label') }}</strong> {{ \App\Services\HijriDateService::toHijri(now()) }} {{ __('tasks.supervision_shifts.print.hijri') }}</div>
                            @if($shift->creator)
                                <div><strong>{{ __('tasks.supervision_shifts.print.creator_label') }}</strong> {{ $shift->creator->name }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <!-- 1. العنوان: اسم المهمة والتكليف -->
            <table class="section-table">
                <tr>
                    <td class="sec-hdr-right">{{ __('tasks.supervision_shifts.print.task_title_section') }}</td>
                    <td class="sec-hdr-left">{{ __('tasks.supervision_shifts.print.status_label') }} {{ __('tasks.supervision_shifts.statuses.' . $shift->status) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="sec-body">
                        <div class="task-title">
                            {{ $shift->title ?: ($shift->task?->name ?? __('tasks.supervision_shifts.print.default_supervision_title')) }}
                        </div>
                        @if($shift->tasks || $shift->notes)
                            <div class="task-desc">
                                <strong>{{ __('tasks.supervision_shifts.print.task_details') }}:</strong>
                                {{ $shift->tasks ?: $shift->notes }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- 2. كشف بأسماء أعضاء فريق العمل المكلفين -->
            <table class="section-table">
                <tr>
                    <td class="sec-hdr-right">{{ __('tasks.supervision_shifts.print.team_members_section') }}</td>
                    <td class="sec-hdr-left">{{ __('tasks.supervision_shifts.print.total_team_count') }}: {{ count($shift->assigned_employees_models) }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 0;">
                        <table class="team-table">
                            <thead>
                                <tr>
                                    <th style="width: 25px;">{{ __('tasks.supervision_shifts.print.col_index') }}</th>
                                    <th style="text-align: right; padding-right: 8px;">{{ __('tasks.supervision_shifts.print.col_name') }}</th>
                                    <th style="width: 85px;">{{ __('tasks.supervision_shifts.print.col_emp_num') }}</th>
                                    <th style="width: 95px;">{{ __('tasks.supervision_shifts.print.col_job_title') }}</th>
                                    <th style="width: 90px;">{{ __('tasks.supervision_shifts.print.col_dept') }}</th>
                                    <th style="width: 80px;">{{ __('tasks.supervision_shifts.print.col_phone') }}</th>
                                    <th style="width: 95px;">{{ __('tasks.supervision_shifts.print.col_signature') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shift->assigned_employees_models as $index => $employee)
                                    <tr>
                                        <td style="font-weight: bold; background-color: #f8fafc;">{{ $index + 1 }}</td>
                                        <td style="text-align: right; padding-right: 8px; font-weight: bold; color: #082c50;">
                                            {{ $employee->user?->name ?? ('موظف #' . $employee->id) }}
                                        </td>
                                        <td>{{ $employee->employee_number ?? '—' }}</td>
                                        <td>{{ $employee->jobTitle?->name ?? '—' }}</td>
                                        <td>{{ $employee->department?->name ?? '—' }}</td>
                                        <td>{{ $employee->user?->phone ?? '—' }}</td>
                                        <td>
                                            <div style="border-bottom: 1px dashed #cbd5e1; height: 12px; width: 80%; margin: 0 auto;"></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="padding: 10px; color: #64748b; font-style: italic;">
                                            {{ __('tasks.supervision_shifts.print.no_team_members') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- 3. مدة المهمة وفترة التكليف -->
            <table class="section-table">
                <tr>
                    <td class="sec-hdr-right">{{ __('tasks.supervision_shifts.print.duration_section') }}</td>
                    <td class="sec-hdr-left">
                        @if($shift->start_time || $shift->end_time)
                            {{ __('tasks.supervision_shifts.print.daily_hours') }}: {{ $shift->start_time ? substr($shift->start_time, 0, 5) : '—' }} &larr; {{ $shift->end_time ? substr($shift->end_time, 0, 5) : '—' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="sec-body">
                        <table style="width: 100%;">
                            <tr>
                                <td class="info-cell" style="width: 49%; vertical-align: top;">
                                    <div class="info-label">{{ __('tasks.supervision_shifts.fields.start_date') }} ({{ __('tasks.supervision_shifts.print.gregorian') }} / {{ __('tasks.supervision_shifts.print.hijri') }}):</div>
                                    <div class="info-value">
                                        {{ $shift->start_date ? $shift->start_date->format('Y/m/d') : __('tasks.table.not_specified') }} {{ __('tasks.supervision_shifts.print.gregorian') }}
                                        @if($shift->start_date)
                                            <span style="font-size: 8.5px; color: #64748b; font-weight: normal;">({{ \App\Services\HijriDateService::toHijriNumeric($shift->start_date) }} {{ __('tasks.supervision_shifts.print.hijri') }})</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="width: 2%;"></td>
                                <td class="info-cell" style="width: 49%; vertical-align: top;">
                                    <div class="info-label">{{ __('tasks.supervision_shifts.fields.end_date') }} ({{ __('tasks.supervision_shifts.print.gregorian') }} / {{ __('tasks.supervision_shifts.print.hijri') }}):</div>
                                    <div class="info-value">
                                        {{ $shift->end_date ? $shift->end_date->format('Y/m/d') : __('tasks.table.not_specified') }} {{ __('tasks.supervision_shifts.print.gregorian') }}
                                        @if($shift->end_date)
                                            <span style="font-size: 8.5px; color: #64748b; font-weight: normal;">({{ \App\Services\HijriDateService::toHijriNumeric($shift->end_date) }} {{ __('tasks.supervision_shifts.print.hijri') }})</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- 4. بيانات المشرف / المناوب المسؤول عن المهمة -->
            <table class="section-table">
                <tr>
                    <td class="sec-hdr-right">{{ __('tasks.supervision_shifts.print.supervisor_section') }}</td>
                    <td class="sec-hdr-left">{{ $shift->supervisor?->jobTitle?->name ?? __('tasks.supervision_shifts.print.certified_supervisor') }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="sec-body">
                        <table style="width: 100%; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                            <tr>
                                <td style="width: 65%; padding: 8px 12px; vertical-align: middle;">
                                    <div style="color: #082c50; font-size: 11px; font-weight: bold; margin-bottom: 4px;">
                                        {{ $shift->supervisor?->user?->name ?? __('tasks.supervision_shifts.table.not_specified') }}
                                    </div>
                                    @if($shift->supervisor?->employee_number)
                                        <div style="font-size: 8.5px; color: #475569; margin-bottom: 2px;">
                                            <strong>{{ __('tasks.supervision_shifts.print.col_emp_num') }}:</strong> {{ $shift->supervisor->employee_number }}
                                        </div>
                                    @endif
                                    @if($shift->supervisor?->department?->name)
                                        <div style="font-size: 8.5px; color: #475569; margin-bottom: 2px;">
                                            <strong>{{ __('tasks.supervision_shifts.print.col_dept') }}:</strong> {{ $shift->supervisor->department->name }}
                                        </div>
                                    @endif
                                    @if($shift->supervisor?->user?->phone)
                                        <div style="font-size: 8.5px; color: #475569;">
                                            <strong>{{ __('tasks.supervision_shifts.fields.supervisor_phone') }}:</strong> {{ $shift->supervisor->user->phone }}
                                        </div>
                                    @endif
                                </td>
                                <td style="width: 35%; padding: 8px 12px; text-align: center; vertical-align: middle; border-right: 1px dashed #cbd5e1;">
                                    <div class="signature-title">{{ __('tasks.supervision_shifts.print.supervisor_signature') }}</div>
                                    <div class="signature-line"></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- 5. اعتماد مدير المهمة -->
            <table class="section-table" style="border: 2px solid #082c50;">
                <tr>
                    <td class="sec-hdr-right" style="background-color: #082c50;">{{ __('tasks.supervision_shifts.print.task_manager_section') }}</td>
                    <td class="sec-hdr-left" style="background-color: #082c50;">{{ __('tasks.supervision_shifts.print.approval_subtitle') }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="sec-body">
                        <table style="width: 100%; background-color: #f8fafc; border: 1px dashed #082c50; border-radius: 4px;">
                            <tr>
                                <td style="width: 65%; padding: 8px 12px; vertical-align: middle;">
                                    <div style="color: #082c50; font-size: 11px; font-weight: bold; margin-bottom: 4px;">
                                        {{ __('tasks.supervision_shifts.print.task_manager_name') }}
                                    </div>
                                    <div style="font-size: 8.5px; color: #64748b; line-height: 1.4;">
                                        {{ __('tasks.supervision_shifts.print.approval_description') }}
                                    </div>
                                    <div style="font-size: 8.5px; color: #334155; margin-top: 5px;">
                                        <strong>{{ __('tasks.supervision_shifts.print.approval_receipt_date') }}</strong> ____ / ____ / ________ {{ __('tasks.supervision_shifts.print.gregorian') }}
                                    </div>
                                </td>
                                <td style="width: 35%; padding: 8px 12px; text-align: center; vertical-align: middle; border-right: 1px dashed #cbd5e1;">
                                    <div class="signature-title">{{ __('tasks.supervision_shifts.print.task_manager_signature') }}</div>
                                    <div class="signature-line"></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="footer-note">
                {{ __('tasks.supervision_shifts.print.footer_official') }} &mdash; {{ $shift->company?->trade_name ?? config('app.name', 'مجموعة فواز') }}
            </div>
        </td>
    </tr>
</table>

</body>
</html>
