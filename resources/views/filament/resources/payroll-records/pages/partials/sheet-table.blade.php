{{-- 4. Interactive Data Table --}}
<div class="payroll-table-wrap">
    <div style="padding: 0.85rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: 800; font-size: 0.875rem; color: #1e293b;">
            كشف مسير ({{ $operationType === 'payroll' ? 'تسليم الرواتب' : 'التحصيل' }}) - شهر {{ $payrollMonth }}
        </div>
        <div style="font-size: 0.725rem; color: #64748b;">
            * ضع علامة (✓) في المربع للتأكيد على التسليم/التحصيل، أو اتركه فارغاً ليبقى معلقاً
        </div>
    </div>

    <table class="payroll-table">
        <thead>
            <tr style="background: #2e3882; color: #ffffff;">
                <th style="text-align: center; width: 45px;">م</th>
                <th style="text-align: right; min-width: 170px;">الاسم / NAME</th>
                <th style="text-align: center; width: 110px;">رقم الاقامة</th>
                <th style="text-align: center; min-width: 110px;">المؤسسة</th>
                <th style="text-align: center; width: 100px;">الإقامة ميلادي</th>
                <th style="text-align: center; width: 115px;">الإقامة بالهجري</th>
                <th style="text-align: center; width: 95px;">الدفع</th>
                <th style="text-align: center; width: 85px;">اخرى</th>
                <th style="text-align: center; width: 110px;">الجوال</th>
                <th style="text-align: center; width: 100px;" class="no-print">
                    {{ $operationType === 'payroll' ? 'تم الصرف' : 'تم التحصيل' }}
                </th>
                <th style="width: 130px;" class="no-print">ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paginatedRows as $index => $row)
                @php
                    $empId = $row['employee_id'];
                    $isChecked = $checkedEmployees[$empId] ?? false;
                    $serialNumber = ($sheetPerPage === 'all' ? 0 : ($sheetPage - 1) * (int)$sheetPerPage) + $index + 1;
                @endphp
                <tr style="{{ $isChecked ? 'background: #f0fdf4;' : '' }}">
                    {{-- 1. Serial Number --}}
                    <td style="text-align: center; font-weight: bold; font-family: monospace;">
                        {{ $serialNumber }}.
                    </td>

                    {{-- 2. Employee Name --}}
                    <td style="text-align: right;">
                        <div style="font-weight: 800; font-size: 0.85rem; color: #0f172a;">{{ $row['name'] }}</div>
                        <div class="no-print" style="font-size: 0.7rem; color: #64748b; margin-top: 0.1rem; display: flex; gap: 0.35rem; align-items: center;">
                            <span style="font-family: monospace; background: #e2e8f0; padding: 0.05rem 0.25rem; border-radius: 0.2rem;">🆔 {{ $row['employee_number'] }}</span>
                            @if(!empty($row['record_number']))
                                <span style="color: #2563eb; font-weight: 700;">#{{ $row['record_number'] }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- 3. Iqama Number --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 600;">
                        {{ $row['iqama_number'] }}
                    </td>

                    {{-- 4. Company --}}
                    <td style="text-align: center; font-weight: 700; color: #b91c1c;">
                        {{ $row['company_name'] }}
                    </td>

                    {{-- 5. Iqama Expiry Gregorian --}}
                    <td style="text-align: center; font-family: monospace; font-size: 0.8rem;">
                        {{ $row['iqama_expiry_gregorian'] }}
                    </td>

                    {{-- 6. Iqama Expiry Hijri --}}
                    <td style="text-align: center; font-family: monospace; font-size: 0.8rem; color: #1e3a8a; font-weight: 600;">
                        {{ $row['iqama_expiry_hijri'] }}
                    </td>

                    {{-- 7. Payment Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 0.9rem; color: #15803d;">
                        {{ number_format($row['net_amount'], 2) }}
                    </td>

                    {{-- 8. Other --}}
                    <td style="text-align: center; font-size: 0.75rem; color: #64748b;">
                        {{ $row['other_amount'] }}
                    </td>

                    {{-- 9. Phone --}}
                    <td style="text-align: center; font-family: monospace; direction: ltr; font-size: 0.8rem;">
                        {{ $row['phone'] }}
                    </td>

                    {{-- 10. Interactive Checkbox (no-print) --}}
                    <td style="text-align: center;" class="no-print">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                            <input
                                type="checkbox"
                                wire:click="toggleEmployeeCheck({{ $empId }})"
                                @checked($isChecked)
                                class="sheet-checkbox"
                            />
                            @if($isChecked)
                                <span class="status-badge status-badge-paid">
                                    ✓ {{ $operationType === 'payroll' ? 'تم الصرف' : 'تم التحصيل' }}
                                </span>
                            @else
                                <span class="status-badge status-badge-unpaid">
                                    ⏳ معلق
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- 11. Notes (no-print) --}}
                    <td class="no-print">
                        <input
                            type="text"
                            wire:model.lazy="notes.{{ $empId }}"
                            placeholder="ملاحظات..."
                            class="form-input"
                            style="padding: 0.35rem 0.5rem; font-size: 0.75rem;"
                        />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <div style="font-weight: 800; font-size: 0.95rem;">لا يوجد موظفون مطابقون للشروط المحددة</div>
                        <div style="font-size: 0.75rem; margin-top: 0.35rem;">يرجى اختيار المنشأة أو الضامن أو التأكد من كلمات البحث</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($employeeRows))
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 800;">
                    <td colspan="6" style="text-align: right; padding: 0.75rem; font-size: 0.85rem;">
                        الإجمالي العام ({{ count($employeeRows) }} موظف):
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 1rem; color: #15803d; font-weight: 900;">
                        {{ number_format($totalNetAmount, 2) }}
                    </td>
                    <td colspan="2" style="text-align: center; color: #64748b; font-size: 0.75rem;">
                        ريال سعودي
                    </td>
                    <td colspan="2" class="no-print"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
