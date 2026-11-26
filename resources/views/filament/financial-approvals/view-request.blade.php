<x-filament-panels::page>
    @php
        $request = $this->getApprovalRecord();
        $overall = $this->overallTotals;
        $filtered = $this->filteredTotals;
        $items = $this->paginatedItems;
        $summaries = $this->companySummaries;

        $methodLabels = [
            'cash' => __('financial.payment_methods.cash'),
            'bank' => __('financial.payment_methods.bank_transfer'),
            'bank_transfer' => __('financial.payment_methods.bank_transfer'),
        ];
    @endphp

    <div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.875rem; color: #0f172a;" class="dark:text-gray-100">
        {{-- 1. Request Summary Card --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); gap: 0.85rem; padding: 1.1rem 1.25rem; border-radius: 0.85rem; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.04);" class="dark:bg-gray-900 dark:border-gray-700">
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.request_number') }}</div>
                <div style="font-weight: 800; font-family: monospace; font-size: 0.95rem;">{{ $request->request_number }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.status') }}</div>
                <div>
                    <span style="display: inline-block; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                        {{ $request->status?->getLabel() }}
                    </span>
                </div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.payroll_month') }}</div>
                <div style="font-weight: 700;">{{ $request->payroll_month ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.employees_count') }}</div>
                <div style="font-weight: 800; color: #0369a1; font-size: 1rem;">{{ $overall['count'] }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.total_amount') }}</div>
                <div style="font-weight: 800; color: #15803d; font-size: 1rem; font-variant-numeric: tabular-nums;">{{ number_format($overall['requested'], 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.submitter') }}</div>
                <div style="font-weight: 600;">{{ $request->submitter?->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.treasury_reviewer') }}</div>
                <div style="font-weight: 600;">{{ $request->treasuryReviewer?->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.2rem;">{{ __('financial.approval.columns.manager') }}</div>
                <div style="font-weight: 600;">{{ $request->managerUser?->name ?? '-' }}</div>
            </div>
            <div style="grid-column: 1 / -1; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1;">
                <span style="font-size: 0.75rem; color: #64748b;">{{ __('financial.approval.columns.companies') }}:</span>
                <span style="font-weight: 700;">{{ $request->companies->pluck('trade_name')->join('، ') ?: '-' }}</span>
            </div>
        </div>

        @if($request->last_error)
            <div style="padding: 0.85rem 1.1rem; border-radius: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 0.82rem;">
                <strong>{{ __('financial.approval.fields.last_error') }}:</strong> {{ $request->last_error }}
            </div>
        @endif

        {{-- 2. Company Breakdown (Clickable Quick Filter) --}}
        @if($summaries->count() > 1)
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="font-weight: 800; font-size: 0.85rem; color: #334155;" class="dark:text-gray-200">{{ __('financial.approval.view_page.company_breakdown') }}</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.65rem;">
                    @foreach($summaries as $summary)
                        @php
                            $isSelected = (string) $summary->company_id === $companyFilter;
                        @endphp
                        <button
                            type="button"
                            wire:click="filterByCompany({{ (int) $summary->company_id }})"
                            style="text-align: right; padding: 0.75rem 0.9rem; border-radius: 0.75rem; border: 1px solid {{ $isSelected ? '#2563eb' : '#e2e8f0' }}; background: {{ $isSelected ? '#eff6ff' : '#ffffff' }}; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;"
                            class="dark:bg-gray-900 dark:border-gray-700"
                        >
                            <div>
                                <div style="font-weight: 700; font-size: 0.82rem; color: #0f172a;" class="dark:text-white">{{ $summary->company?->trade_name ?? '-' }}</div>
                                <div style="font-size: 0.72rem; color: #64748b;">{{ $summary->employees_count }} {{ __('financial.approval.view_page.employees_unit') }}</div>
                            </div>
                            <div style="font-weight: 800; color: #15803d; font-variant-numeric: tabular-nums;">{{ number_format((float) $summary->total_requested, 2) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 3. Search & Filter Bar --}}
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-radius: 0.75rem; background: #ffffff; border: 1px solid #e2e8f0;" class="dark:bg-gray-900 dark:border-gray-700">
            <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center; flex: 1;">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('financial.approval.view_page.search_employee') }}"
                    style="min-width: 220px; flex: 1; max-width: 340px; padding: 0.45rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-size: 0.82rem; background: #f8fafc;"
                    class="dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                />

                @if($summaries->count() > 1)
                    <select
                        wire:model.live="companyFilter"
                        style="min-width: 190px; padding: 0.45rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-size: 0.82rem; background: #f8fafc;"
                        class="dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">{{ __('financial.approval.view_page.all_companies') }}</option>
                        @foreach($summaries as $summary)
                            <option value="{{ $summary->company_id }}">{{ $summary->company?->trade_name ?? '-' }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.75rem; color: #64748b;">{{ __('financial.approval.view_page.per_page') }}:</label>
                <select
                    wire:model.live="perPage"
                    style="padding: 0.35rem 0.65rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-size: 0.8rem; background: #f8fafc;"
                    class="dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                >
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        {{-- 4. Paginated Items Table --}}
        <div style="overflow-x: auto; border-radius: 0.85rem; border: 1px solid #cbd5e1; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.04);" class="dark:bg-gray-900 dark:border-gray-700">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem; text-align: right;">
                <thead style="background: #1e293b; color: #f8fafc;">
                    <tr>
                        <th style="padding: 0.7rem 0.85rem; text-align: center; width: 48px; border-bottom: 1px solid #334155;">#</th>
                        <th style="padding: 0.7rem 0.85rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.employee') }}</th>
                        <th style="padding: 0.7rem 0.85rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.company') }}</th>
                        <th style="padding: 0.7rem 0.85rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.method') }}</th>
                        <th style="padding: 0.7rem 0.85rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.net_amount') }}</th>
                        <th style="padding: 0.7rem 0.85rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.previously_paid') }}</th>
                        <th style="padding: 0.7rem 0.85rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155; color: #86efac;">{{ __('financial.approval.item_columns.requested_amount') }}</th>
                        <th style="padding: 0.7rem 0.85rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $idx => $item)
                        @php
                            $rowNumber = (($items->currentPage() - 1) * $items->perPage()) + $idx + 1;
                        @endphp
                        <tr style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.6rem 0.85rem; text-align: center; color: #64748b; font-family: monospace;">{{ $rowNumber }}</td>
                            <td style="padding: 0.6rem 0.85rem; font-weight: 700; white-space: nowrap;">{{ $item->employee_name_snapshot }}</td>
                            <td style="padding: 0.6rem 0.85rem; color: #334155;">{{ $item->company?->trade_name ?? '-' }}</td>
                            <td style="padding: 0.6rem 0.85rem; text-align: center; white-space: nowrap;">
                                <span style="display: inline-block; padding: 0.15rem 0.55rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                                    {{ $methodLabels[$item->payment_method] ?? $item->payment_method }}
                                </span>
                            </td>
                            <td style="padding: 0.6rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format((float) $item->net_amount, 2) }}</td>
                            <td style="padding: 0.6rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums; color: #64748b;">{{ number_format((float) $item->previously_paid_amount, 2) }}</td>
                            <td style="padding: 0.6rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums; font-weight: 800; color: #15803d; background: #f0fdf4;">{{ number_format((float) $item->requested_amount, 2) }}</td>
                            <td style="padding: 0.6rem 0.85rem; color: #475569;">{{ $item->reason_description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 2rem; text-align: center; color: #64748b;">{{ __('financial.approval.view_page.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($filtered['count'] > 0)
                    <tfoot>
                        <tr style="background: #f1f5f9; font-weight: 800; border-top: 2px solid #cbd5e1;">
                            <td colspan="4" style="padding: 0.7rem 0.85rem;">{{ __('financial.approval.view_page.filtered_total', ['count' => $filtered['count']]) }}</td>
                            <td style="padding: 0.7rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format($filtered['net'], 2) }}</td>
                            <td style="padding: 0.7rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format($filtered['prev_paid'], 2) }}</td>
                            <td style="padding: 0.7rem 0.85rem; text-align: center; font-variant-numeric: tabular-nums; color: #15803d;">{{ number_format($filtered['requested'], 2) }}</td>
                            <td style="padding: 0.7rem 0.85rem;"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($items->hasPages())
            <div>
                {{ $items->links() }}
            </div>
        @endif

        {{-- 5. Decision & Audit Logs --}}
        <div style="display: flex; flex-direction: column; gap: 0.6rem; padding: 1rem 1.25rem; border-radius: 0.85rem; background: #ffffff; border: 1px solid #e2e8f0;" class="dark:bg-gray-900 dark:border-gray-700">
            <div style="font-weight: 800; font-size: 0.9rem; color: #1e293b;" class="dark:text-gray-200">{{ __('financial.approval.modals.logs_heading') }}</div>
            <div style="display: flex; flex-direction: column; gap: 0.45rem;">
                @foreach($request->logs as $log)
                    @php
                        $actionKey = 'financial.approval.log_actions.' . $log->action;
                        $actionLabel = __($actionKey) !== $actionKey ? __($actionKey) : $log->action;
                    @endphp
                    <div style="padding: 0.65rem 0.9rem; border-radius: 0.6rem; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.8rem;" class="dark:bg-gray-800/60 dark:border-gray-700">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <div>
                                <span style="font-weight: 700; color: #0f172a;" class="dark:text-white">{{ $actionLabel }}</span>
                                <span style="color: #64748b;"> — {{ $log->actor?->name ?? '-' }}</span>
                            </div>
                            <span style="font-size: 0.72rem; color: #64748b; font-family: monospace;">{{ $log->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        @if($log->reason)
                            <div style="margin-top: 0.3rem; color: #475569;" class="dark:text-gray-300">{{ $log->reason }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
