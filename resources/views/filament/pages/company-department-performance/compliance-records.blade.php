@php
    $licensePrefixes = ['municipality_', 'civil_defense_', 'commercial_reg_'];
    $isEmployeeRecords = ! collect($licensePrefixes)->contains(fn ($prefix) => str_starts_with($type, $prefix));
    $warningDays = $isEmployeeRecords
        ? \App\Services\CompanyCompliancePerformanceService::DOCUMENT_WARNING_DAYS
        : \App\Services\CompanyCompliancePerformanceService::LICENSE_WARNING_DAYS;
@endphp

<div class="perf-table-wrapper">
    <table class="perf-table">
        <thead>
            <tr>
                <th>{{ __('performance.statement.company') }}</th>
                @if($isEmployeeRecords)
                    <th>{{ __('performance.statement.employee_name') }}</th>
                    <th>{{ __('performance.filters.branch') }}</th>
                @endif
                <th>{{ __('performance.statement.expiry_date') }}</th>
                <th style="text-align: end;">{{ __('performance.statement.days_remaining') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                @php
                    $employee = $record instanceof \App\Models\Employee;
                    $expiry = $record->compliance_expiry ? \Carbon\Carbon::parse($record->compliance_expiry)->startOfDay() : null;
                    $days = $expiry ? (int) today()->diffInDays($expiry, false) : null;
                    $daysColor = $days === null
                        ? 'var(--mu, #6b7490)'
                        : ($days < 0 ? '#e11d48' : ($days <= $warningDays ? '#d97706' : '#059669'));
                @endphp
                <tr>
                    <td>
                        <strong>{{ ($employee ? $record->company?->trade_name : $record->trade_name) ?? '—' }}</strong>
                    </td>
                    @if($isEmployeeRecords)
                        <td>{{ $employee ? $record->name : '—' }}</td>
                        <td>{{ $employee ? ($record->branch?->name ?? '—') : '—' }}</td>
                    @endif
                    <td><bdi>{{ $expiry?->toDateString() ?? '—' }}</bdi></td>
                    <td style="text-align: end; font-weight: 700;">
                        @if($days !== null)
                            <span style="color: {{ $daysColor }};">
                                <bdi>{{ number_format($days) }} {{ __('performance.compliance.days_unit') }}</bdi>
                            </span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isEmployeeRecords ? 5 : 3 }}">
                        <div class="perf-empty-state-card" style="padding: 2.5rem 1rem;">
                            <div class="perf-empty-icon">
                                <x-heroicon-o-document-magnifying-glass style="width: 28px; height: 28px;" />
                            </div>
                            <div class="perf-empty-title">{{ __('performance.statement.empty') }}</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
