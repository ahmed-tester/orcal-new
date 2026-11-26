@php
    $items = ($currentItems ?? $request->items->where('version', $request->version));
    $totalNet = (float) $items->sum('net_amount');
    $totalPrevPaid = (float) $items->sum('previously_paid_amount');
    $totalRequested = (float) $items->sum('requested_amount');

    $methodLabels = [
        'cash' => __('financial.payment_methods.cash'),
        'bank' => __('financial.payment_methods.bank_transfer'),
        'bank_transfer' => __('financial.payment_methods.bank_transfer'),
    ];
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; color: #0f172a;" class="dark:text-gray-100">
    {{-- Summary Header Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); gap: 0.75rem; padding: 0.9rem 1rem; border-radius: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0;" class="dark:bg-gray-800/60 dark:border-gray-700">
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.request_number') }}</div>
            <div style="font-weight: 800; font-family: monospace; font-size: 0.9rem;">{{ $request->request_number }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.status') }}</div>
            <div>
                <span style="display: inline-block; padding: 0.15rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                    {{ $request->status?->getLabel() }}
                </span>
            </div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.payroll_month') }}</div>
            <div style="font-weight: 700;">{{ $request->payroll_month ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.employees_count') }}</div>
            <div style="font-weight: 800; color: #0369a1;">{{ $items->count() }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.total_amount') }}</div>
            <div style="font-weight: 800; color: #15803d; font-variant-numeric: tabular-nums;">{{ number_format($totalRequested, 2) }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.submitter') }}</div>
            <div style="font-weight: 600;">{{ $request->submitter?->name ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.treasury_reviewer') }}</div>
            <div style="font-weight: 600;">{{ $request->treasuryReviewer?->name ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.15rem;">{{ __('financial.approval.columns.manager') }}</div>
            <div style="font-weight: 600;">{{ $request->managerUser?->name ?? '-' }}</div>
        </div>
        <div style="grid-column: 1 / -1; padding-top: 0.45rem; border-top: 1px dashed #cbd5e1;">
            <span style="font-size: 0.75rem; color: #64748b;">{{ __('financial.approval.columns.companies') }}:</span>
            <span style="font-weight: 700;">{{ $request->companies->pluck('trade_name')->join('، ') ?: '-' }}</span>
        </div>
    </div>

    @if($request->last_error)
        <div style="padding: 0.75rem 1rem; border-radius: 0.65rem; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 0.8rem;">
            <strong>{{ __('financial.approval.fields.last_error') }}:</strong> {{ $request->last_error }}
        </div>
    @endif

    {{-- Scrollable Items Table with Sticky Header --}}
    <div style="max-height: 360px; overflow: auto; border-radius: 0.75rem; border: 1px solid #cbd5e1; background: #ffffff;" class="dark:bg-gray-900 dark:border-gray-700">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; text-align: right;">
            <thead style="position: sticky; top: 0; z-index: 2; background: #1e293b; color: #f8fafc;">
                <tr>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; width: 42px; border-bottom: 1px solid #334155;">#</th>
                    <th style="padding: 0.65rem 0.75rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.employee') }}</th>
                    <th style="padding: 0.65rem 0.75rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.company') }}</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.method') }}</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.net_amount') }}</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.previously_paid') }}</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #334155; color: #86efac;">{{ __('financial.approval.item_columns.requested_amount') }}</th>
                    <th style="padding: 0.65rem 0.75rem; white-space: nowrap; border-bottom: 1px solid #334155;">{{ __('financial.approval.item_columns.reason') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                    <tr style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 0.55rem 0.75rem; text-align: center; color: #64748b; font-family: monospace;">{{ $idx + 1 }}</td>
                        <td style="padding: 0.55rem 0.75rem; font-weight: 700; white-space: nowrap;">{{ $item->employee_name_snapshot }}</td>
                        <td style="padding: 0.55rem 0.75rem; color: #334155;">{{ $item->company?->trade_name ?? '-' }}</td>
                        <td style="padding: 0.55rem 0.75rem; text-align: center; white-space: nowrap;">
                            <span style="display: inline-block; padding: 0.1rem 0.5rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                                {{ $methodLabels[$item->payment_method] ?? $item->payment_method }}
                            </span>
                        </td>
                        <td style="padding: 0.55rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format((float) $item->net_amount, 2) }}</td>
                        <td style="padding: 0.55rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums; color: #64748b;">{{ number_format((float) $item->previously_paid_amount, 2) }}</td>
                        <td style="padding: 0.55rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums; font-weight: 800; color: #15803d; background: #f0fdf4;">{{ number_format((float) $item->requested_amount, 2) }}</td>
                        <td style="padding: 0.55rem 0.75rem; color: #475569;">{{ $item->reason_description ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 800; border-top: 2px solid #cbd5e1;">
                    <td colspan="4" style="padding: 0.65rem 0.75rem;">{{ __('financial.approval.modals.total_row', ['count' => $items->count()]) }}</td>
                    <td style="padding: 0.65rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format($totalNet, 2) }}</td>
                    <td style="padding: 0.65rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums;">{{ number_format($totalPrevPaid, 2) }}</td>
                    <td style="padding: 0.65rem 0.75rem; text-align: center; font-variant-numeric: tabular-nums; color: #15803d;">{{ number_format($totalRequested, 2) }}</td>
                    <td style="padding: 0.65rem 0.75rem;"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Decision & Audit Logs --}}
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #1e293b;" class="dark:text-gray-200">{{ __('financial.approval.modals.logs_heading') }}</div>
        <div style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.4rem;">
            @foreach($request->logs as $log)
                @php
                    $actionKey = 'financial.approval.log_actions.' . $log->action;
                    $actionLabel = __($actionKey) !== $actionKey ? __($actionKey) : $log->action;
                @endphp
                <div style="padding: 0.55rem 0.8rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.78rem;" class="dark:bg-gray-800/60 dark:border-gray-700">
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
