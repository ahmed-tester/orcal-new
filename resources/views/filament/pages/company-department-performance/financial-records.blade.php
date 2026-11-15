@if(\App\Services\CompanyFinancialPerformanceService::isTreasuryMetric($type))
    @include('filament.pages.company-department-performance.treasury-statement')
@else
    @include('filament.pages.company-department-performance.financial-statement')
@endif
