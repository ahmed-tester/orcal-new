{{-- 5. Pagination Bar (no-print) --}}
@if(count($employeeRows) > 0)
    <div class="payroll-pagination no-print">
        {{-- Entries Per Page Selector --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.825rem; color: #475569;">
            <span>{{ __('finance.payroll_records.sheet.pagination_show') }}</span>
            <select wire:model.live="sheetPerPage" class="form-select" style="width: auto; padding: 0.35rem 0.65rem; font-size: 0.8rem; border-radius: 0.5rem;">
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">{{ __('finance.payroll_records.sheet.pagination_all', ['count' => count($employeeRows)]) }}</option>
            </select>
            <span>{{ __('finance.payroll_records.sheet.pagination_per_page') }}</span>
            <span style="color: #cbd5e1; margin: 0 0.25rem;">|</span>
            <span style="font-weight: 600;">{{ __('finance.payroll_records.sheet.pagination_total_employees', ['count' => count($employeeRows)]) }}</span>
        </div>

        {{-- Pagination Controls --}}
        @if($totalSheetPages > 1)
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <button
                    type="button"
                    wire:click="previousSheetPage"
                    @disabled($sheetPage <= 1)
                    class="page-btn page-btn-inactive"
                    style="{{ $sheetPage <= 1 ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
                >
                    {{ __('finance.payroll_records.sheet.pagination_prev') }}
                </button>

                <div style="display: flex; gap: 0.25rem; align-items: center;">
                    @for($p = max(1, $sheetPage - 2); $p <= min($totalSheetPages, $sheetPage + 2); $p++)
                        <button
                            type="button"
                            wire:click="setSheetPage({{ $p }})"
                            class="page-btn {{ $p === $sheetPage ? 'page-btn-active' : 'page-btn-inactive' }}"
                        >
                            {{ $p }}
                        </button>
                    @endfor
                </div>

                <button
                    type="button"
                    wire:click="nextSheetPage"
                    @disabled($sheetPage >= $totalSheetPages)
                    class="page-btn page-btn-inactive"
                    style="{{ $sheetPage >= $totalSheetPages ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
                >
                    {{ __('finance.payroll_records.sheet.pagination_next') }}
                </button>

                <span style="font-size: 0.75rem; color: #64748b; margin-right: 0.5rem; font-weight: 600;">
                    {{ __('finance.payroll_records.sheet.pagination_page_info', ['page' => $sheetPage, 'total' => $totalSheetPages]) }}
                </span>
            </div>
        @endif
    </div>
@endif
