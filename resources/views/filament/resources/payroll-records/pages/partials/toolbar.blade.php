{{-- 3. Actions Toolbar --}}
<div class="payroll-toolbar no-print">
    <div class="toolbar-hint">
        <span class="toolbar-hint-icon">💡</span>
        <span>{{ __('finance.payroll_records.sheet.tip') }}</span>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center;">
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
