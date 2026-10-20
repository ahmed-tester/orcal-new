{{-- 3. Actions Toolbar --}}
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;" class="no-print">
    {{-- 
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button
            type="button"
            wire:click="toggleCheckAll(true)"
            class="btn-action"
            style="background: #ecfdf5; color: #047857; border-color: #a7f3d0;"
        >
            <x-heroicon-s-check-circle style="width: 1rem; height: 1rem;" />
            <span>{{ $operationType === 'payroll' ? __('finance.payroll_records.sheet.check_all_disburse') : __('finance.payroll_records.sheet.check_all_collect') }}</span>
        </button>

        <button
            type="button"
            wire:click="toggleCheckAll(false)"
            class="btn-action btn-secondary"
        >
            <x-heroicon-s-x-circle style="width: 1rem; height: 1rem;" />
            <span>{{ __('finance.payroll_records.sheet.uncheck_all') }}</span>
        </button>
    </div>
    --}}

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button
            type="button"
            wire:click="savePayrollSheet"
            wire:loading.attr="disabled"
            class="btn-action btn-primary"
            style="font-size: 0.875rem; padding: 0.65rem 1.35rem;"
        >
            <x-heroicon-s-check-badge style="width: 1.15rem; height: 1.15rem;" />
            <span>{{ __('finance.payroll_records.sheet.save_btn') }}</span>
        </button>
    </div>
</div>
