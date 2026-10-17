{{-- Formal Printable Header (Displays only when printing) --}}
<div class="print-only" style="display: none; border-bottom: 2px solid #2e3882; padding-bottom: 12px; margin-bottom: 10px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="text-align: right;">
            <div style="font-size: 15px; font-weight: 900; color: #1e3a8a;">
                {{ $selectedCompanyId && ($company = \App\Models\Company::find($selectedCompanyId)) ? $company->trade_name : 'مجموعة فواز القابضة' }}
            </div>
            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">
                إدارة الموارد البشرية والعمليات المالية
            </div>
        </div>

        <div style="text-align: center;">
            <div style="font-size: 18px; font-weight: 900; color: #2e3882; text-decoration: underline;">
                {{ $operationType === 'payroll' ? 'كشف دفع رواتب الموظفين' : 'كشف التحصيل العام للموظفين' }}
            </div>
            <div style="font-size: 11px; font-weight: bold; color: #334155; margin-top: 4px;">
                عن استحقاق شهر: {{ $payrollMonth }}
            </div>
        </div>

        <div style="text-align: left; font-size: 10.5px; color: #475569;">
            <div><strong>تاريخ الطباعة:</strong> {{ \App\Services\HijriDateService::formatDual(now(), true) }}</div>
            <div style="margin-top: 3px;"><strong>المستخدم:</strong> {{ auth()->user()?->name ?? 'المسؤول' }}</div>
        </div>
    </div>
</div>
