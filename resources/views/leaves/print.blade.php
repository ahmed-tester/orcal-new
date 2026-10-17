<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج إجازة - {{ $leave->employee?->user?->name ?? 'موظف' }} #{{ $leave->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
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
            max-width: 820px;
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
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #64748b;
            color: #ffffff;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }
        .document-card {
            background: #ffffff;
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #1e3a8a;
            border-radius: 10px;
            padding: 28px 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .company-info h2 {
            color: #1e3a8a;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .company-info p {
            color: #64748b;
            font-size: 12px;
        }
        .title-box {
            text-align: center;
            border: 2px solid #2563eb;
            background-color: #eff6ff;
            padding: 8px 24px;
            border-radius: 8px;
        }
        .title-box h1 {
            color: #1e40af;
            font-size: 18px;
            font-weight: 800;
        }
        .meta-box {
            text-align: left;
            font-size: 12px;
            color: #334155;
            line-height: 1.6;
        }
        .meta-box strong {
            color: #0f172a;
        }
        .section-title {
            background: #f1f5f9;
            border-right: 4px solid #2563eb;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
            margin-top: 16px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px 14px;
            font-size: 12.5px;
            margin-bottom: 12px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .info-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }
        .info-value {
            color: #0f172a;
            font-weight: 700;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            border-radius: 5px;
            min-height: 28px;
            display: flex;
            align-items: center;
        }
        .highlight-value {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
            font-weight: 800;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-approved { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .balance-box {
            background: #faf5ff;
            border: 1px dashed #a855f7;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #581c87;
            margin: 12px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .sign-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            min-height: 85px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sign-title {
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }
        .sign-line {
            color: #94a3b8;
            font-size: 10px;
        }
        .footer-note {
            margin-top: 16px;
            text-align: center;
            font-size: 10.5px;
            color: #64748b;
            border-top: 1px dotted #cbd5e1;
            padding-top: 8px;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .print-toolbar {
                display: none !important;
            }
            .document-card {
                border: 1.5px solid #000000;
                box-shadow: none;
                padding: 15px 20px;
                max-width: 100%;
            }
            .title-box {
                border: 1.5px solid #000000;
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .title-box h1 {
                color: #000000;
            }
            .section-title {
                background: #eeeeee !important;
                color: #000000 !important;
                border-right-color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .info-value {
                background: #ffffff !important;
                border: 1px solid #999999 !important;
            }
            .balance-box {
                background: #f8f8f8 !important;
                border: 1px dashed #666666 !important;
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
        <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة الإشعار / حفظ PDF</button>
    </div>

    <div class="document-card">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <h2>{{ $leave->employee?->company?->trade_name ?? $leave->employee?->company?->name ?? config('app.name', 'نظام الموارد البشرية') }}</h2>
                <p>سجل تجاري: {{ $leave->employee?->company?->commercial_registration_number ?? '—' }}</p>
                <p>هاتف: {{ $leave->employee?->company?->phone ?? '—' }}</p>
            </div>
            <div class="title-box">
                <h1>نموذج طلب واعتماد إجازة</h1>
                <div style="font-size: 11px; margin-top: 3px; font-weight: 600;">LEAVE REQUEST VOUCHER</div>
            </div>
            <div class="meta-box">
                <div><strong>رقم الطلب:</strong> #{{ str_pad($leave->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div><strong>تاريخ الطباعة:</strong> {{ now()->format('Y-m-d') }}</div>
                <div><strong>تاريخ الطلب:</strong> {{ $leave->created_at?->format('Y-m-d') }}</div>
            </div>
        </div>

        {{-- Employee Details --}}
        <div class="section-title">أولاً: بيانات الموظف الأساسية</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">اسم الموظف</span>
                <span class="info-value highlight-value">{{ $leave->employee?->user?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">الرقم الوظيفي</span>
                <span class="info-value">{{ $leave->employee?->employee_number ?? ("EMP-" . $leave->employee?->id) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">رقم الهوية الوطنية / الإقامة</span>
                <span class="info-value">{{ $leave->employee?->details?->iqama_number ?: ($leave->employee?->details?->national_id ?: ($leave->employee?->user?->owner?->national_id ?? '—')) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">المنشأة / المؤسسة</span>
                <span class="info-value">{{ $leave->employee?->company?->trade_name ?? $leave->employee?->company?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">الفرع</span>
                <span class="info-value">{{ $leave->employee?->branch?->name ?? 'المركز الرئيسي' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">المسمى الوظيفي</span>
                <span class="info-value">{{ $leave->employee?->jobTitle?->name ?? ($leave->employee?->user?->isOwner() ? 'مالك / شريك' : '—') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">الجنسية</span>
                <span class="info-value">{{ $leave->employee?->details?->nationality ?: ($leave->employee?->user?->user_type === \App\Enums\UserType::SAUDI_EMPLOYEE ? 'سعودي' : '—') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">رقم الجوال</span>
                <span class="info-value">{{ $leave->employee?->user?->phone ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ التعيين والالتحاق</span>
                <span class="info-value">{{ $leave->employee?->hire_date?->format('Y-m-d') ?? ($leave->employee?->created_at?->format('Y-m-d') ?? '—') }}</span>
            </div>
        </div>

        {{-- Leave Details --}}
        <div class="section-title">ثانياً: تفاصيل الإجازة المطلوبة</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">نوع الإجازة</span>
                <span class="info-value highlight-value">{{ $leave->leaveType?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">عدد الأيام المطلوبة</span>
                <span class="info-value highlight-value">{{ $leave->days }} يوم</span>
            </div>
            <div class="info-item">
                <span class="info-label">حالة الطلب</span>
                <span class="info-value">
                    @if($leave->status === \App\Enums\LeaveStatus::APPROVED)
                        <span class="status-badge status-approved">معتمدة ومقبولة ✓</span>
                    @elseif($leave->status === \App\Enums\LeaveStatus::REJECTED)
                        <span class="status-badge status-rejected">مرفوضة ✗</span>
                    @else
                        <span class="status-badge status-pending">معلقة قيد المراجعة</span>
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">بداية الإجازة (من تاريخ)</span>
                <span class="info-value">{{ $leave->start_date?->format('Y-m-d') ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">نهاية الإجازة (إلى تاريخ)</span>
                <span class="info-value">{{ $leave->end_date?->format('Y-m-d') ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ المباشرة المتوقع</span>
                <span class="info-value">{{ $leave->end_date ? $leave->end_date->copy()->addDay()->format('Y-m-d') : '—' }}</span>
            </div>
            <div class="info-item" style="grid-column: span 3;">
                <span class="info-label">سبب الإجازة والملاحظات</span>
                <span class="info-value">{{ $leave->reason ?: '— لا توجد ملاحظات مسجلة —' }}</span>
            </div>
        </div>

        {{-- Accrued and Remaining Balance Box --}}
        <div class="balance-box">
            <div>
                <strong>سجل الرصيد السنوي:</strong>
                <span>الرصيد السنوي المخصص: <strong>{{ $balance['annual_rate'] }} يوم</strong></span>
                <span>• المستهلك سابقاً هذا العام: <strong>{{ $balance['used_days'] }} يوم</strong></span>
            </div>
            <div>
                <span>الرصيد المتاح قبل الطلب: <strong>{{ $balance['available_days'] }} يوم</strong></span>
                <span>• الرصيد المتبقي بعد الإجازة: <strong style="color: #166534; font-size: 13px;">{{ max(0, $balance['available_days'] - ($leave->status === \App\Enums\LeaveStatus::APPROVED ? 0 : $leave->days)) }} يوم</strong></span>
            </div>
        </div>

        {{-- Actual Return if exists --}}
        @if($leave->actual_return_date)
            <div class="section-title">ثالثاً: بيانات مباشرة العمل الفعلية</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">تاريخ العودة الفعلي</span>
                    <span class="info-value">{{ $leave->actual_return_date->format('Y-m-d') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">حالة العودة والفرق</span>
                    <span class="info-value">{{ $leave->return_status_label }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">المعتمد</span>
                    <span class="info-value">{{ $leave->approver?->name ?? '—' }}</span>
                </div>
            </div>
        @endif

        {{-- Approver Information --}}
        @if($leave->status === \App\Enums\LeaveStatus::APPROVED && $leave->approver)
            <div style="font-size: 12px; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 6px; margin-top: 8px;">
                ✓ تم اعتماد هذه الإجازة من قبل: <strong>{{ $leave->approver->name }}</strong> بتاريخ: {{ $leave->approved_at?->format('Y-m-d H:i') ?? '—' }}
            </div>
        @elseif($leave->status === \App\Enums\LeaveStatus::REJECTED && $leave->rejection_reason)
            <div style="font-size: 12px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; margin-top: 8px;">
                ✗ سبب الرفض: {{ $leave->rejection_reason }}
            </div>
        @endif

        {{-- Signatures --}}
        <div class="signatures-grid">
            <div class="sign-box">
                <span class="sign-title">توقيع الموظف</span>
                <span class="sign-line">التاريخ: ... / ... / 202... م</span>
            </div>
            <div class="sign-box">
                <span class="sign-title">المسؤول المباشر</span>
                <span class="sign-line">التاريخ: ... / ... / 202... م</span>
            </div>
            <div class="sign-box">
                <span class="sign-title">إدارة الموارد البشرية</span>
                <span class="sign-line">التاريخ: ... / ... / 202... م</span>
            </div>
            <div class="sign-box">
                <span class="sign-title">الختم الرسمي</span>
                <span class="sign-line">يعتمد</span>
            </div>
        </div>

        <div class="footer-note">
            هذا المستند صادر آلياً من نظام {{ config('app.name', 'أوركال') }} لإدارة الموارد البشرية — أي كشط أو تعديل يدوي يلغي صحة المستند.
        </div>
    </div>
</body>
</html>
