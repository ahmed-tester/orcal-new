{{-- Top Navigation: Sheet View vs Records History Table --}}
<div class="payroll-top-tabs no-print">
    {{-- Main View Switcher --}}
    <div class="tabs-nav-group">
        <button
            type="button"
            wire:click="switchTab('sheet')"
            class="tab-nav-btn {{ $pageViewTab === 'sheet' ? 'tab-nav-btn-active' : 'tab-nav-btn-inactive' }}"
        >
            <x-heroicon-o-document-currency-dollar class="tab-nav-icon" />
            <span>{{ __('finance.payroll_records.sheet.tab_sheet') }}</span>
        </button>

        <button
            type="button"
            wire:click="switchTab('records')"
            class="tab-nav-btn {{ $pageViewTab === 'records' ? 'tab-nav-btn-active' : 'tab-nav-btn-inactive' }}"
        >
            <x-heroicon-o-archive-box class="tab-nav-icon" />
            <span>{{ __('finance.payroll_records.sheet.tab_records') }}</span>
        </button>
    </div>

    {{-- Quick Print Triggers --}}
    <div class="print-actions-group">
        <button
            type="button"
            wire:click="printWithScope('all')"
            class="btn-print btn-print-navy"
            title="{{ __('finance.payroll_records.sheet.print_all_btn') }}"
        >
            <x-heroicon-o-printer style="width: 1.05rem; height: 1.05rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_all_btn') }}</span>
        </button>

        <button
            type="button"
            wire:click="printWithScope('paid')"
            class="btn-print btn-print-emerald"
            title="{{ __('finance.payroll_records.sheet.print_paid_btn') }}"
        >
            <x-heroicon-o-check-badge style="width: 1.05rem; height: 1.05rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_paid_btn') }} ({{ $countPaid }})</span>
        </button>

        <button
            type="button"
            wire:click="printWithScope('unpaid')"
            class="btn-print btn-print-amber"
            title="{{ __('finance.payroll_records.sheet.print_unpaid_btn') }}"
        >
            <x-heroicon-o-clock style="width: 1.05rem; height: 1.05rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_unpaid_btn') }} ({{ $countUnpaid }})</span>
        </button>
    </div>
</div>
