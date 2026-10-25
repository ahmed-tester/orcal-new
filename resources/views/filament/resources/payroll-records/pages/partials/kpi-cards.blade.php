{{-- 2. KPI Summary Cards --}}
<div class="stats-grid no-print">
    <div class="stat-card" style="border-top-color: #3b82f6;">
        <div>
            <div class="stat-title">{{ __('finance.payroll_records.sheet.kpi_employees_count') }}</div>
            <div class="stat-num" style="color: #2563eb;">{{ $totalEmployees }}</div>
        </div>
        <x-heroicon-o-users style="color: #3b82f6; width: 1.5rem; height: 1.5rem;" />
    </div>

    <div class="stat-card" style="border-top-color: #6366f1;">
        <div>
            <div class="stat-title">{{ __('finance.payroll_records.sheet.kpi_total_net') }}</div>
            <div class="stat-num" style="color: #4f46e5;">{{ number_format($totalNetAmount, 2) }} <span style="font-size: 0.75rem; font-weight: normal;">{{ __('finance.payroll_records.sheet.currency') }}</span></div>
        </div>
        <x-heroicon-o-calculator style="color: #6366f1; width: 1.5rem; height: 1.5rem;" />
    </div>

    <div class="stat-card" style="border-top-color: #10b981;">
        <div>
            <div class="stat-title">{{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.kpi_total_paid') : __('finance.payroll_records.sheet.kpi_total_collected') }}</div>
            <div class="stat-num" style="color: #059669;">{{ number_format($totalPaidAmount, 2) }} <span style="font-size: 0.75rem; font-weight: normal;">{{ __('finance.payroll_records.sheet.currency') }}</span></div>
        </div>
        <x-heroicon-o-check-badge style="color: #10b981; width: 1.5rem; height: 1.5rem;" />
    </div>

    <div class="stat-card" style="border-top-color: #f59e0b;">
        <div>
            <div class="stat-title">{{ __('finance.payroll_records.sheet.kpi_total_pending') }}</div>
            <div class="stat-num" style="color: #d97706;">{{ number_format($totalPendingAmount, 2) }} <span style="font-size: 0.75rem; font-weight: normal;">{{ __('finance.payroll_records.sheet.currency') }}</span></div>
        </div>
        <x-heroicon-o-clock style="color: #f59e0b; width: 1.5rem; height: 1.5rem;" />
    </div>
</div>
