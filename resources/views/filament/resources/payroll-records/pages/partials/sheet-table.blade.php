{{-- 4. Interactive Data Table --}}
<div class="payroll-table-wrap">
    <div style="padding: 0.85rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div style="font-weight: 800; font-size: 0.875rem; color: #1e293b;">
            {{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.title_payroll', ['month' => $payrollMonth]) : __('finance.payroll_records.sheet.title_collection', ['month' => $payrollMonth]) }}
        </div>
        <div style="font-size: 0.725rem; color: #64748b;">
            {{ __('finance.payroll_records.sheet.tip') }}
        </div>
    </div>

    <table class="payroll-table">
        <thead>
            <tr style="background: #2e3882; color: #ffffff;">
                <th style="text-align: center; width: 40px;">{{ __('finance.payroll_records.sheet.serial') }}</th>
                <th style="text-align: right; min-width: 155px;">{{ __('finance.payroll_records.sheet.col_name') }}</th>
                <th style="text-align: center; width: 105px;">{{ __('finance.payroll_records.sheet.col_identity') }}</th>
                <th style="text-align: center; min-width: 100px;">{{ __('finance.payroll_records.sheet.col_company') }}</th>
                <th style="text-align: center; width: 85px;">{{ __('finance.payroll_records.sheet.col_basic') }}</th>
                <th style="text-align: center; width: 75px;">{{ __('finance.payroll_records.sheet.col_additions') }}</th>
                <th style="text-align: center; width: 75px;">{{ __('finance.payroll_records.sheet.col_deductions') }}</th>
                <th style="text-align: center; width: 95px;">{{ __('finance.payroll_records.sheet.col_net') }}</th>
                <th style="text-align: center; width: 90px; color: #86efac;">{{ __('finance.payroll_records.sheet.col_paid') }}</th>
                <th style="text-align: center; width: 90px; color: #fde047;">{{ __('finance.payroll_records.sheet.col_remaining') }}</th>
                <th style="text-align: center; width: 95px;">{{ __('finance.payroll_records.sheet.col_phone') }}</th>
                <th style="text-align: center; width: 105px;" class="no-print">
                    {{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.col_disburse_status') : __('finance.payroll_records.sheet.col_collect_status') }}
                </th>
                <th style="width: 110px;" class="no-print">{{ __('finance.payroll_records.sheet.col_notes') }}</th>
                <th style="width: 48px; text-align: center;" class="no-print">{{ __('finance.payroll_records.sheet.col_action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paginatedRows as $index => $row)
                @php
                    $empId = $row['employee_id'];
                    $isChecked = $checkedEmployees[$empId] ?? false;
                    $serialNumber = ($sheetPerPage === 'all' ? 0 : ($sheetPage - 1) * (int)$sheetPerPage) + $index + 1;
                    $basicAmount = (float) ($row['basic_salary'] ?? 0);
                    $additionAmount = (float) ($row['additional_amount'] ?? 0);
                    $deductionAmount = (float) ($row['deduction_amount'] ?? 0);
                    $netAmount = (float) ($row['net_amount'] ?? 0);
                    $paidAmount = (float) ($row['paid_amount'] ?? 0);
                    $remainingAmount = (float) ($row['remaining_amount'] ?? 0);
                    $isFullyPaid = ($isChecked || ($remainingAmount <= 0 && $netAmount > 0));
                    $isPartiallyPaid = (!$isFullyPaid && $paidAmount > 0);
                @endphp
                <tr style="{{ $isFullyPaid ? 'background: #f0fdf4;' : ($isPartiallyPaid ? 'background: #fffbeb;' : '') }}">
                    {{-- 1. Serial Number --}}
                    <td
                        style="text-align: center; font-weight: bold; font-family: monospace; cursor: pointer;"
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
                        <div style="font-weight: 800; font-size: 0.85rem; color: #1d4ed8; display: flex; align-items: center; justify-content: space-between; gap: 0.35rem;">
                            <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                {{ $row['name'] }}
                                <x-heroicon-m-arrow-top-right-on-square style="width: 0.85rem; height: 0.85rem; color: #3b82f6;" class="no-print" />
                            </span>
                        </div>
                        <div class="no-print" style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem; display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                            <span style="font-family: monospace; background: #e2e8f0; padding: 0.05rem 0.25rem; border-radius: 0.2rem;">🆔 {{ $row['employee_number'] }}</span>
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
                        <div style="font-family: monospace; font-weight: 600;">{{ $row['iqama_number'] }}</div>
                        @if(!empty($row['iqama_expiry_gregorian']) && $row['iqama_expiry_gregorian'] !== '—')
                            <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">
                                {{ __('finance.payroll_records.sheet.expiry_prefix') }} {{ $row['iqama_expiry_gregorian'] }}
                            </div>
                        @endif
                    </td>

                    {{-- 4. Company --}}
                    <td style="text-align: center; font-weight: 700; color: #b91c1c;">
                        {{ $row['company_name'] }}
                    </td>

                    {{-- 5. Basic Salary --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: #334155;">
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
                        style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: #1e293b; background: rgba(241, 245, 249, 0.5); cursor: pointer;"
                        wire:click="openEmployeeRecord({{ $empId }})"
                        title="{{ __('finance.payroll_records.sheet.open_title') }}"
                    >
                        {{ number_format($netAmount, 2) }}
                    </td>

                    {{-- 9. Paid Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: #15803d; background: rgba(240, 253, 244, 0.5);">
                        {{ number_format($paidAmount, 2) }}
                    </td>

                    {{-- 10. Remaining Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.875rem; color: {{ $remainingAmount > 0 ? '#d97706' : '#94a3b8' }}; background: {{ $remainingAmount > 0 ? 'rgba(254, 243, 199, 0.4)' : 'transparent' }};">
                        {{ number_format($remainingAmount, 2) }}
                    </td>

                    {{-- 11. Phone --}}
                    <td style="text-align: center; font-family: monospace; direction: ltr; font-size: 0.775rem;">
                        {{ $row['phone'] }}
                    </td>

                    {{-- 12. Interactive Checkbox & Status (no-print) --}}
                    <td style="text-align: center;" class="no-print">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                            <input
                                type="checkbox"
                                wire:click="toggleEmployeeCheck({{ $empId }})"
                                @checked($isChecked || $isFullyPaid)
                                class="sheet-checkbox"
                            />
                            @if($isFullyPaid)
                                <span class="status-badge status-badge-paid">
                                    ✓ {{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.status_paid') : __('finance.payroll_records.sheet.status_collected') }}
                                </span>
                            @elseif($isPartiallyPaid)
                                <span class="status-badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.68rem; padding: 0.1rem 0.35rem; border-radius: 0.25rem;">
                                    {{ __('finance.payroll_records.sheet.status_partial') }}
                                </span>
                            @else
                                <span class="status-badge status-badge-unpaid">
                                    {{ __('finance.payroll_records.sheet.status_pending') }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- 13. Notes (no-print) --}}
                    <td class="no-print">
                        <input
                            type="text"
                            wire:model.lazy="notes.{{ $empId }}"
                            placeholder="{{ __('finance.payroll_records.sheet.notes_placeholder') }}"
                            class="form-input"
                            style="padding: 0.35rem 0.5rem; font-size: 0.75rem;"
                        />
                    </td>

                    {{-- 14. Action / Delete Button (no-print) --}}
                    <td style="text-align: center;" class="no-print">
                        <button
                            type="button"
                            wire:click="deleteRecord({{ $empId }}, {{ !empty($row['existing_id']) ? $row['existing_id'] : 'null' }})"
                            wire:confirm="{{ __('finance.payroll_records.sheet.delete_confirm', ['name' => $row['name'], 'month' => $payrollMonth]) }}"
                            class="btn-delete-row"
                            title="{{ __('finance.payroll_records.sheet.delete_title') }}"
                            style="color: #dc2626; background: #fee2e2; border: 1px solid #fecaca; border-radius: 0.375rem; padding: 0.35rem 0.5rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease;"
                            onmouseover="this.style.background='#fca5a5'; this.style.borderColor='#f87171';"
                            onmouseout="this.style.background='#fee2e2'; this.style.borderColor='#fecaca';"
                        >
                            <x-heroicon-o-trash style="width: 1.1rem; height: 1.1rem; color: #dc2626;" />
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <div style="font-weight: 800; font-size: 0.95rem;">{{ __('finance.payroll_records.sheet.empty_heading') }}</div>
                        <div style="font-size: 0.75rem; margin-top: 0.35rem;">{{ __('finance.payroll_records.sheet.empty_subheading') }}</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($employeeRows))
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 800;">
                    <td colspan="4" style="text-align: right; padding: 0.75rem; font-size: 0.85rem;">
                        {{ __('finance.payroll_records.sheet.total_general', ['count' => count($employeeRows)]) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.825rem; color: #334155; font-weight: 800;">
                        {{ number_format($totalBasicAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.825rem; color: #15803d; font-weight: 800;">
                        {{ number_format($totalAdditionalAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.825rem; color: #b91c1c; font-weight: 800;">
                        {{ number_format($totalDeductionAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #1e293b; font-weight: 900; background: #e2e8f0;">
                        {{ number_format($totalNetAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #15803d; font-weight: 900; background: #dcfce7;">
                        {{ number_format($totalPaidAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #b45309; font-weight: 900; background: #fef3c7;">
                        {{ number_format($totalPendingAmount, 2) }}
                    </td>
                    <td colspan="4" class="no-print"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
