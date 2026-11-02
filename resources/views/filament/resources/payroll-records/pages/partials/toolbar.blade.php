{{-- 3. Actions Toolbar --}}
<div class="payroll-toolbar no-print">
    {{-- Segmented View Filter (All / Paid / Unpaid) --}}
    <div style="display: flex; gap: 0.35rem; align-items: center; background: #f1f5f9; padding: 0.25rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;" class="dark:bg-slate-800 dark:border-slate-700">
        <button
            type="button"
            wire:click="setPaymentScope('all')"
            class="scope-pill-btn {{ $paymentScope === 'all' ? 'scope-pill-btn-active' : 'scope-pill-btn-inactive' }}"
        >
            <span>{{ __('finance.payroll_records.sheet.scope_all') }}</span>
            <span style="font-family: monospace; font-size: 0.7rem; opacity: 0.85; background: rgba(255,255,255,0.2); padding: 0.05rem 0.35rem; border-radius: 9999px;">({{ $countAll }})</span>
        </button>

        <button
            type="button"
            wire:click="setPaymentScope('paid')"
            class="scope-pill-btn {{ $paymentScope === 'paid' ? 'scope-pill-btn-active' : 'scope-pill-btn-inactive' }}"
        >
            <x-heroicon-m-check-circle style="width: 0.95rem; height: 0.95rem; color: #10b981;" />
            <span>{{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.scope_paid_payroll') : __('finance.payroll_records.sheet.scope_paid_collection') }}</span>
            <span style="font-family: monospace; font-size: 0.7rem; opacity: 0.85; background: rgba(255,255,255,0.2); padding: 0.05rem 0.35rem; border-radius: 9999px;">({{ $countPaid }})</span>
        </button>

        <button
            type="button"
            wire:click="setPaymentScope('unpaid')"
            class="scope-pill-btn {{ $paymentScope === 'unpaid' ? 'scope-pill-btn-active' : 'scope-pill-btn-inactive' }}"
        >
            <x-heroicon-m-clock style="width: 0.95rem; height: 0.95rem; color: #f59e0b;" />
            <span>{{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.scope_unpaid_payroll') : __('finance.payroll_records.sheet.scope_unpaid_collection') }}</span>
            <span style="font-family: monospace; font-size: 0.7rem; opacity: 0.85; background: rgba(255,255,255,0.2); padding: 0.05rem 0.35rem; border-radius: 9999px;">({{ $countUnpaid }})</span>
        </button>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        {{-- Bulk Basic Salary Input & Tool --}}
        @if($operationType === 'payroll')
        <div style="display: flex; gap: 0.4rem; align-items: center; background: #f8fafc; padding: 0.25rem 0.65rem; border-radius: 0.75rem; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:border-slate-700">
            <label style="font-size: 0.75rem; font-weight: 700; color: #475569; white-space: nowrap;" class="dark:text-slate-300">
                💵 {{ __('finance.payroll_records.sheet.bulk_salary_title') }}:
            </label>
            <input
                type="number"
                step="0.01"
                min="0"
                wire:model.defer="bulkBasicSalary"
                placeholder="{{ __('finance.payroll_records.sheet.bulk_salary_placeholder') }}"
                style="width: 140px; padding: 0.35rem 0.5rem; font-size: 0.775rem; font-family: monospace; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #ffffff;"
                class="dark:bg-slate-900 dark:border-slate-600 dark:text-white"
            />
            <button
                type="button"
                wire:click="applyBulkBasicSalary"
                wire:loading.attr="disabled"
                style="padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; background: #0284c7; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem; transition: background 0.15s ease;"
                onmouseover="this.style.background='#0369a1'"
                onmouseout="this.style.background='#0284c7'"
                title="{{ __('finance.payroll_records.sheet.bulk_salary_apply') }}"
            >
                <x-heroicon-m-sparkles style="width: 0.85rem; height: 0.85rem;" />
                <span>{{ __('finance.payroll_records.sheet.bulk_salary_apply') }}</span>
            </button>
        </div>
        @endif

        <button
            type="button"
            wire:click="savePayrollSheet"
            wire:loading.attr="disabled"
            class="btn-save-payroll"
        >
            <x-heroicon-s-check-badge wire:loading.remove wire:target="savePayrollSheet" style="width: 1.15rem; height: 1.15rem;" />
            <svg wire:loading wire:target="savePayrollSheet" style="width: 1.15rem; height: 1.15rem; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ __('finance.payroll_records.sheet.save_btn') }}</span>
        </button>
    </div>
</div>
