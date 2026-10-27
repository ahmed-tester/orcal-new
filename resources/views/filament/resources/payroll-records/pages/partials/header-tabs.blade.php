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
            <x-heroicon-o-table-cells class="tab-nav-icon" />
            <span>{{ __('finance.payroll_records.sheet.tab_records') }}</span>
        </button>
    </div>

    {{-- Quick Print Triggers --}}
    <div class="print-actions-group">
        <button
            type="button"
            wire:click="setOperationTypeAndPrint('payroll')"
            class="btn-print btn-print-navy"
            title="{{ __('finance.payroll_records.sheet.print_payroll') }}"
        >
            <x-heroicon-o-printer style="width: 1.05rem; height: 1.05rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_payroll') }}</span>
        </button>

        <button
            type="button"
            wire:click="setOperationTypeAndPrint('collection')"
            class="btn-print btn-print-purple"
            title="{{ __('finance.payroll_records.sheet.print_collection') }}"
        >
            <x-heroicon-o-printer style="width: 1.05rem; height: 1.05rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_collection') }}</span>
        </button>
    </div>
</div>
