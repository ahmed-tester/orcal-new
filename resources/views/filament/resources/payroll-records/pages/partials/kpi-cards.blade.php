{{-- 2. KPI Summary Cards --}}
<div class="stats-grid no-print">
    {{-- Card 1: Total Employees --}}
    <div class="stat-card stat-card-blue">
        <div class="stat-info">
            <span class="stat-title">{{ __('finance.payroll_records.sheet.kpi_employees_count') }}</span>
            <div class="stat-num stat-num-blue">
                {{ $totalEmployees ?? $this->totalEmployees ?? 0 }}
                <span class="stat-unit">{{ __('finance.payroll_records.sheet.employees_unit') }}</span>
            </div>
        </div>
        <div class="stat-icon-wrap stat-icon-blue">
            <x-heroicon-o-users style="width: 1.5rem; height: 1.5rem;" />
        </div>
    </div>

    {{-- Card 2: Total Net --}}
    <div class="stat-card stat-card-indigo">
        <div class="stat-info">
            <span class="stat-title">{{ __('finance.payroll_records.sheet.kpi_total_net') }}</span>
            <div class="stat-num stat-num-indigo">
                {{ number_format($totalNetAmount ?? $this->totalNetAmount ?? 0, 2) }}
                <span class="stat-unit">{{ __('finance.payroll_records.sheet.currency') }}</span>
            </div>
        </div>
        <div class="stat-icon-wrap stat-icon-indigo">
            <x-heroicon-o-calculator style="width: 1.5rem; height: 1.5rem;" />
        </div>
    </div>

    {{-- Card 3: Total Paid / Collected --}}
    <div class="stat-card stat-card-emerald">
        <div class="stat-info">
            <span class="stat-title">{{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.kpi_total_paid') : __('finance.payroll_records.sheet.kpi_total_collected') }}</span>
            <div class="stat-num stat-num-emerald">
                {{ number_format($totalPaidAmount ?? $this->totalPaidAmount ?? 0, 2) }}
                <span class="stat-unit">{{ __('finance.payroll_records.sheet.currency') }}</span>
            </div>
        </div>
        <div class="stat-icon-wrap stat-icon-emerald">
            <x-heroicon-o-check-badge style="width: 1.5rem; height: 1.5rem;" />
        </div>
    </div>

    {{-- Card 4: Total Remaining / Pending --}}
    <div class="stat-card stat-card-amber">
        <div class="stat-info">
            <span class="stat-title">{{ __('finance.payroll_records.sheet.kpi_total_pending') }}</span>
            <div class="stat-num stat-num-amber">
                {{ number_format($totalPendingAmount ?? $this->totalPendingAmount ?? 0, 2) }}
                <span class="stat-unit">{{ __('finance.payroll_records.sheet.currency') }}</span>
            </div>
        </div>
        <div class="stat-icon-wrap stat-icon-amber">
            <x-heroicon-o-clock style="width: 1.5rem; height: 1.5rem;" />
        </div>
    </div>
</div>
