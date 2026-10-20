{{-- Top Navigation: Sheet View vs Records History Table --}}
<div class="payroll-top-tabs no-print">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <div class="tab-btn tab-btn-active" style="cursor: default;">
            <x-heroicon-o-document-currency-dollar style="width: 1.15rem; height: 1.15rem;" />
            <span>{{ __('finance.payroll_records.sheet.tab_sheet') }}</span>
        </div>

        @php /* Records tab button — commented out
        <button
            type="button"
            wire:click="switchTab('records')"
            class="tab-btn {{ $pageViewTab === 'records' ? 'tab-btn-active' : 'tab-btn-inactive' }}"
        >
            <x-heroicon-o-table-cells style="width: 1.15rem; height: 1.15rem;" />
            <span>{{ __('finance.payroll_records.sheet.tab_records') }}</span>
        </button>
        */ @endphp
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button
            type="button"
            wire:click="setOperationTypeAndPrint('payroll')"
            class="btn-action"
            style="background: #1e3a8a; color: #ffffff; border-color: #1e3a8a; box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);"
        >
            <x-heroicon-o-printer style="width: 1.1rem; height: 1.1rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_payroll') }}</span>
        </button>

        <button
            type="button"
            wire:click="setOperationTypeAndPrint('collection')"
            class="btn-action"
            style="background: #6b21a8; color: #ffffff; border-color: #6b21a8; box-shadow: 0 2px 4px rgba(107, 33, 168, 0.2);"
        >
            <x-heroicon-o-printer style="width: 1.1rem; height: 1.1rem;" />
            <span>{{ __('finance.payroll_records.sheet.print_collection') }}</span>
        </button>
    </div>
</div>
