{{-- 4. Interactive Data Table --}}
<div class="payroll-table-wrap">
    {{-- Table Top Header Bar --}}
    <div class="payroll-table-header-bar">
        <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
            <span style="font-weight: 800; font-size: 0.925rem; color: #0f172a;" class="dark:text-white">
                {{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.title_payroll', ['month' => $payrollMonth]) : __('finance.payroll_records.sheet.title_collection', ['month' => $payrollMonth]) }}
            </span>
            <span style="font-size: 0.725rem; font-weight: 700; padding: 0.15rem 0.6rem; border-radius: 9999px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                {{ $payrollMonth }}
            </span>
            @if(!empty($employeeRows))
                <span style="font-size: 0.725rem; font-weight: 600; color: #64748b;">
                    ({{ count($employeeRows) }} {{ __('finance.payroll_records.sheet.employees_unit') }})
                </span>
            @endif
        </div>
        <div style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 0.35rem;">
            <span>ℹ️</span>
            <span>{{ __('finance.payroll_records.sheet.open_title') }}</span>
        </div>
    </div>

    {{-- The Table --}}
    <table class="payroll-table">
        <thead>
            <tr>
                <th style="text-align: center; width: 45px;">{{ __('finance.payroll_records.sheet.serial') }}</th>
                <th style="text-align: right; min-width: 160px;">{{ __('finance.payroll_records.sheet.col_name') }}</th>
                <th style="text-align: center; width: 110px;">{{ __('finance.payroll_records.sheet.col_identity') }}</th>
                <th style="text-align: center; min-width: 110px;">{{ __('finance.payroll_records.sheet.col_company') }}</th>
                <th style="text-align: center; width: 90px;">{{ __('finance.payroll_records.sheet.col_basic') }}</th>
                <th style="text-align: center; width: 80px;">{{ __('finance.payroll_records.sheet.col_additions') }}</th>
                <th style="text-align: center; width: 80px;">{{ __('finance.payroll_records.sheet.col_deductions') }}</th>
                <th style="text-align: center; width: 100px;">{{ __('finance.payroll_records.sheet.col_net') }}</th>
                <th style="text-align: center; width: 95px; color: #86efac;">{{ __('finance.payroll_records.sheet.col_paid') }}</th>
                <th style="text-align: center; width: 95px; color: #fde047;">{{ __('finance.payroll_records.sheet.col_remaining') }}</th>
                <th style="text-align: center; width: 100px;">{{ __('finance.payroll_records.sheet.col_phone') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paginatedRows as $index => $row)
                @php
                    $empId = $row['employee_id'];
                    $serialNumber = ($sheetPerPage === 'all' ? 0 : ($sheetPage - 1) * (int)$sheetPerPage) + $index + 1;
                    $basicAmount = (float) ($row['basic_salary'] ?? 0);
                    $additionAmount = (float) ($row['additional_amount'] ?? 0);
                    $deductionAmount = (float) ($row['deduction_amount'] ?? 0);
                    $netAmount = (float) ($row['net_amount'] ?? 0);
                    $paidAmount = (float) ($row['paid_amount'] ?? 0);
                    $remainingAmount = (float) ($row['remaining_amount'] ?? 0);
                    $isFullyPaid = ($remainingAmount <= 0 && $netAmount > 0);
                    $isPartiallyPaid = (!$isFullyPaid && $paidAmount > 0);
                @endphp
                <tr style="{{ $isFullyPaid ? 'background: #f0fdf4;' : ($isPartiallyPaid ? 'background: #fffbeb;' : '') }}">
                    {{-- 1. Serial Number --}}
                    <td
                        style="text-align: center; font-weight: 800; font-family: monospace; cursor: pointer; color: #64748b;"
                        wire:click="openEmployeeRecord({{ $empId }})"
                        title="{{ __('finance.payroll_records.sheet.open_title') }}"
                    >
                        {{ $serialNumber }}.
                    </td>

                    {{-- 2. Employee Name --}}
                    <td
                        style="text-align: right; cursor: pointer;"
                        wire:click="openEmployeeRecord({{ $empId }})"
                        title="{{ __('finance.payroll_records.sheet.open_title') }}"
                    >
                        <div style="font-weight: 800; font-size: 0.85rem; color: #2563eb; display: flex; align-items: center; justify-content: space-between; gap: 0.35rem;">
                            <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                {{ $row['name'] }}
                                <x-heroicon-m-arrow-top-right-on-square style="width: 0.85rem; height: 0.85rem; color: #3b82f6;" class="no-print" />
                            </span>
                        </div>
                        <div class="no-print" style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem; display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                            <span style="font-family: monospace; background: #e2e8f0; padding: 0.05rem 0.3rem; border-radius: 0.25rem; font-weight: 600;">🆔 {{ $row['employee_number'] }}</span>
                            @if(!empty($row['record_number']))
                                <span style="color: #2563eb; font-weight: 700; direction: ltr; display: inline-block;">{{ $row['record_number'] }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- 3. National ID / Iqama Number --}}
                    <td
                        style="text-align: center; cursor: pointer;"
                        wire:click="openEmployeeRecord({{ $empId }})"
                        title="{{ __('finance.payroll_records.sheet.open_title') }}"
                    >
                        <div style="font-family: monospace; font-weight: 700; color: #1e293b;" class="dark:text-white">{{ $row['iqama_number'] }}</div>
                        @if(!empty($row['iqama_expiry_gregorian']) && $row['iqama_expiry_gregorian'] !== '—')
                            <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">
                                {{ __('finance.payroll_records.sheet.expiry_prefix') }} {{ $row['iqama_expiry_gregorian'] }}
                            </div>
                        @endif
                    </td>

                    {{-- 4. Company --}}
                    <td style="text-align: center;">
                        <span class="company-pill" title="{{ $row['company_name'] }}">
                            {{ $row['company_name'] }}
                        </span>
                    </td>

                    {{-- 5. Basic Salary --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: #334155;" class="dark:text-slate-300">
                        {{ number_format($basicAmount, 2) }}
                    </td>

                    {{-- 6. Additions --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: {{ $additionAmount > 0 ? '#15803d' : '#94a3b8' }};">
                        {{ $additionAmount > 0 ? '+' . number_format($additionAmount, 2) : '—' }}
                    </td>

                    {{-- 7. Deductions --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: {{ $deductionAmount > 0 ? '#b91c1c' : '#94a3b8' }};">
                        {{ $deductionAmount > 0 ? '-' . number_format($deductionAmount, 2) : '—' }}
                    </td>

                    {{-- 8. Net Amount --}}
                    <td
                        style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: #0f172a; background: rgba(241, 245, 249, 0.7); cursor: pointer;"
                        wire:click="openEmployeeRecord({{ $empId }})"
                        title="{{ __('finance.payroll_records.sheet.open_title') }}"
                        class="dark:text-white"
                    >
                        {{ number_format($netAmount, 2) }}
                    </td>

                    {{-- 9. Paid Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: #15803d; background: rgba(240, 253, 244, 0.7);">
                        {{ number_format($paidAmount, 2) }}
                    </td>

                    {{-- 10. Remaining Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: {{ $remainingAmount > 0 ? '#d97706' : '#94a3b8' }}; background: {{ $remainingAmount > 0 ? 'rgba(254, 243, 199, 0.5)' : 'transparent' }};">
                        {{ number_format($remainingAmount, 2) }}
                    </td>

                    {{-- 11. Phone --}}
                    <td style="text-align: center; font-family: monospace; direction: ltr; font-size: 0.775rem; color: #475569;">
                        {{ $row['phone'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 3.5rem 1rem; color: #94a3b8;">
                        <div style="font-weight: 800; font-size: 1rem; color: #475569;" class="dark:text-slate-300">
                            {{ __('finance.payroll_records.sheet.empty_heading') }}
                        </div>
                        <div style="font-size: 0.775rem; margin-top: 0.35rem; color: #94a3b8;">
                            {{ __('finance.payroll_records.sheet.empty_subheading') }}
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($employeeRows))
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; padding: 0.85rem 1rem; font-size: 0.875rem; color: #0f172a;" class="dark:text-white">
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                            <span>📊</span>
                            <span>{{ __('finance.payroll_records.sheet.total_general', ['count' => count($employeeRows)]) }}</span>
                        </span>
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #334155; font-weight: 800;" class="dark:text-slate-200">
                        {{ number_format($totalBasicAmount ?? $this->totalBasicAmount ?? 0, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #15803d; font-weight: 800;">
                        {{ number_format($totalAdditionalAmount ?? $this->totalAdditionalAmount ?? 0, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #b91c1c; font-weight: 800;">
                        {{ number_format($totalDeductionAmount ?? $this->totalDeductionAmount ?? 0, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.9rem; color: #0f172a; font-weight: 900; background: #e2e8f0;" class="dark:bg-slate-700 dark:text-white">
                        {{ number_format($totalNetAmount ?? $this->totalNetAmount ?? 0, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.9rem; color: #15803d; font-weight: 900; background: #dcfce7;" class="dark:bg-emerald-950 dark:text-emerald-300">
                        {{ number_format($totalPaidAmount ?? $this->totalPaidAmount ?? 0, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.9rem; color: #b45309; font-weight: 900; background: #fef3c7;" class="dark:bg-amber-950 dark:text-amber-300">
                        {{ number_format($totalPendingAmount ?? $this->totalPendingAmount ?? 0, 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
