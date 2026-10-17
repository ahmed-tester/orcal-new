{{-- 5. Pagination Bar (no-print) --}}
@if(count($employeeRows) > 0)
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.75rem 1rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; margin-top: 0.75rem;">
        {{-- Entries Per Page Selector --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #475569;">
            <span>عرض</span>
            <select wire:model.live="sheetPerPage" class="form-select" style="width: auto; padding: 0.35rem 0.65rem; font-size: 0.8rem;">
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">الكل ({{ count($employeeRows) }})</option>
            </select>
            <span>موظف لكل صفحة</span>
            <span style="color: #94a3b8; margin: 0 0.25rem;">|</span>
            <span>إجمالي الموظفين: <strong>{{ count($employeeRows) }}</strong></span>
        </div>

        {{-- Pagination Controls --}}
        @if($totalSheetPages > 1)
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <button
                    type="button"
                    wire:click="previousSheetPage"
                    @disabled($sheetPage <= 1)
                    class="tab-btn {{ $sheetPage <= 1 ? 'tab-btn-inactive' : 'tab-btn-active' }}"
                    style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 0.5rem;"
                >
                    السابق
                </button>

                <div style="display: flex; gap: 0.25rem; align-items: center;">
                    @for($p = max(1, $sheetPage - 2); $p <= min($totalSheetPages, $sheetPage + 2); $p++)
                        <button
                            type="button"
                            wire:click="setSheetPage({{ $p }})"
                            class="tab-btn {{ $p === $sheetPage ? 'tab-btn-active' : 'tab-btn-inactive' }}"
                            style="padding: 0.35rem 0.7rem; font-size: 0.8rem; border-radius: 0.5rem; min-width: 32px; text-align: center;"
                        >
                            {{ $p }}
                        </button>
                    @endfor
                </div>

                <button
                    type="button"
                    wire:click="nextSheetPage"
                    @disabled($sheetPage >= $totalSheetPages)
                    class="tab-btn {{ $sheetPage >= $totalSheetPages ? 'tab-btn-inactive' : 'tab-btn-active' }}"
                    style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 0.5rem;"
                >
                    التالي
                </button>

                <span style="font-size: 0.75rem; color: #64748b; margin-right: 0.5rem;">
                    صفحة {{ $sheetPage }} من {{ $totalSheetPages }}
                </span>
            </div>
        @endif
    </div>
@endif
