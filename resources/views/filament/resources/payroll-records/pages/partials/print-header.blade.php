{{-- Formal Printable Header (Displays only when printing) --}}
<div class="print-only" style="display: none; border-bottom: 2px solid #2e3882; padding-bottom: 12px; margin-bottom: 10px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="text-align: right;">
            <div style="font-size: 15px; font-weight: 900; color: #1e3a8a;">
                {{ $selectedCompanyId && ($company = \App\Models\Company::find($selectedCompanyId)) ? $company->trade_name : __('finance.payroll_records.sheet.print_default_company') }}
            </div>
            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">
                {{ __('finance.payroll_records.sheet.print_dept') }}
            </div>
        </div>

        <div style="text-align: center;">
            <div style="font-size: 18px; font-weight: 900; color: #2e3882; text-decoration: underline;">
                @if($operationType === 'payroll')
                    @if($paymentScope === 'paid')
                        {{ __('finance.payroll_records.sheet.print_payroll_paid_title') }}
                    @elseif($paymentScope === 'unpaid')
                        {{ __('finance.payroll_records.sheet.print_payroll_unpaid_title') }}
                    @else
                        {{ __('finance.payroll_records.sheet.print_payroll_title') }}
                    @endif
                @else
                    @if($paymentScope === 'paid')
                        {{ __('finance.payroll_records.sheet.print_collection_paid_title') }}
                    @elseif($paymentScope === 'unpaid')
                        {{ __('finance.payroll_records.sheet.print_collection_unpaid_title') }}
                    @else
                        {{ __('finance.payroll_records.sheet.print_collection_title') }}
                    @endif
                @endif
            </div>
            <div style="font-size: 11px; font-weight: bold; color: #334155; margin-top: 4px;">
                {{ __('finance.payroll_records.sheet.print_month_label', ['month' => $payrollMonth]) }}
                @if(!empty($employeeRows))
                    <span style="color: #64748b;">— ({{ count($employeeRows) }} {{ __('finance.payroll_records.sheet.employees_unit') }})</span>
                @endif
            </div>
        </div>

        <div style="text-align: left; font-size: 10.5px; color: #475569;">
            <div><strong>{{ __('finance.payroll_records.sheet.print_date_label') }}</strong> {{ \App\Services\HijriDateService::formatDual(now(), true) }}</div>
            <div style="margin-top: 3px;"><strong>{{ __('finance.payroll_records.sheet.print_user_label') }}</strong> {{ auth()->user()?->name ?? __('finance.payroll_records.sheet.print_default_user') }}</div>
        </div>
    </div>
</div>
