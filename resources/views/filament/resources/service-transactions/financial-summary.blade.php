@php
    /** @var \App\Models\ServiceTransaction|null $record */
    $record = $record ?? null;
@endphp

@if ($record)
    @php
        $summary = $record->financialSummary();
        $snapshot = \App\Services\ServiceFinancialWorkflowService::displaySnapshot($record);
        $currency = __('services_tracking.currency_sar');
        if ($currency === 'services_tracking.currency_sar' || empty($currency)) {
            $currency = 'ر.س';
        }
        $money = fn ($value) => number_format((float) $value, 2).' '.$currency;

        $clientDue = (float) $summary['client_due'];
        $received = (float) $summary['received'];
        $clientRemaining = (float) $summary['client_remaining'];
        $custody = (float) $summary['custody'];
        $cashReceived = (float) $summary['cash_received'];
        $nonCashReceived = (float) $summary['non_cash_received'];
        $treasuryReceived = (float) $summary['treasury_received'];
        $netCash = (float) $summary['net_cash'];
        $surplus = (float) $summary['surplus'];
        $surplusRefunded = (float) $summary['surplus_refunded'];
        $employeeDue = (float) $summary['employee_due'];
        $feeExpected = $summary['fee_defined'] ? (float) $summary['fee_expected'] : null;
        $feePaid = (float) $summary['fee_paid'];
        $feeRemaining = $summary['fee_remaining'] !== null ? (float) $summary['fee_remaining'] : null;
        $feeFunded = (float) $summary['fee_funded_remaining'];
        $pendingReceipts = (float) $summary['pending_receipts'];
        $pendingReceiptsCount = (int) $summary['pending_receipts_count'];

        $responsibleEmployeeName = $record->executor?->user?->name 
            ?? ($record->employee?->user?->name ?? '—');
        $beneficiaryDisplay = $record->beneficiary_name 
            ?: ($record->employee?->user?->name 
                ?: ($record->company?->trade_name ?: '—'));

        // Stepper status logic
        $isClientSettled = $clientRemaining <= 0.001 && $received > 0;
        $isCustodySettled = $custody <= 0.001;
        $isFullyMatched = $isClientSettled && $isCustodySettled;

        // Fetch recent receipts and disbursements
        $receipts = $record->financialReceipts()->with(['collector.user'])->latest()->take(5)->get();
        $disbursements = $record->financialDisbursements()->latest()->take(5)->get();
    @endphp

    <div class="fin-dash">
        <style>
            .fin-dash {
                direction: rtl;
                font-family: inherit;
                color: #1e293b;
                display: flex;
                flex-direction: column;
                gap: 18px;
            }
            .fin-dash * {
                box-sizing: border-box;
            }
            .fin-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 18px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            }
            .dark .fin-card {
                background: #1e293b;
                border-color: #334155;
                color: #f8fafc;
            }
            .fin-grid-main {
                display: grid;
                grid-template-columns: 320px 1fr;
                gap: 18px;
                align-items: start;
            }
            @media (max-width: 1024px) {
                .fin-grid-main {
                    grid-template-columns: 1fr;
                }
            }
            .fin-header-title {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 4px;
            }
            .dark .fin-header-title {
                color: #f8fafc;
            }
            .fin-header-subtitle {
                font-size: 11px;
                color: #64748b;
                margin-bottom: 14px;
            }
            .dark .fin-header-subtitle {
                color: #94a3b8;
            }
            .fin-stepper-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 8px;
                margin-bottom: 14px;
            }
            @media (max-width: 768px) {
                .fin-stepper-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
            @media (max-width: 480px) {
                .fin-stepper-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .fin-step-box {
                border-radius: 10px;
                padding: 10px 6px;
                text-align: center;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 82px;
            }
            .dark .fin-step-box {
                background: #0f172a;
                border-color: #334155;
            }
            .fin-step-box.completed {
                background: #f0fdf4;
                border-color: #bbf7d0;
            }
            .dark .fin-step-box.completed {
                background: rgba(16, 185, 129, 0.12);
                border-color: rgba(16, 185, 129, 0.3);
            }
            .fin-step-box.current {
                background: #eff6ff;
                border-color: #bfdbfe;
            }
            .dark .fin-step-box.current {
                background: rgba(59, 130, 246, 0.12);
                border-color: rgba(59, 130, 246, 0.3);
            }
            .fin-step-box.warning {
                background: #fffbeb;
                border-color: #fde68a;
            }
            .dark .fin-step-box.warning {
                background: rgba(245, 158, 11, 0.12);
                border-color: rgba(245, 158, 11, 0.3);
            }
            .fin-step-box.danger {
                background: #fef2f2;
                border-color: #fecaca;
            }
            .dark .fin-step-box.danger {
                background: rgba(239, 68, 68, 0.12);
                border-color: rgba(239, 68, 68, 0.3);
            }
            .fin-step-circle {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 700;
                margin-bottom: 6px;
                color: #ffffff;
            }
            .fin-step-title {
                font-size: 11px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.25;
                margin-bottom: 3px;
            }
            .dark .fin-step-title {
                color: #f1f5f9;
            }
            .fin-step-status {
                font-size: 10px;
                font-weight: 600;
            }
            .fin-kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 12px;
            }
            @media (max-width: 640px) {
                .fin-kpi-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .fin-kpi-box {
                border-radius: 10px;
                padding: 12px 10px;
                text-align: center;
                border: 1px solid #e2e8f0;
            }
            .fin-kpi-label {
                font-size: 11px;
                font-weight: 600;
                margin-bottom: 4px;
            }
            .fin-kpi-val {
                font-size: 17px;
                font-weight: 800;
                font-variant-numeric: tabular-nums;
            }
            .fin-trail-step {
                border: 1px solid #f1f5f9;
                border-radius: 10px;
                padding: 12px 14px;
                background: #fafafa;
                margin-bottom: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
            }
            .dark .fin-trail-step {
                background: #0f172a;
                border-color: #334155;
            }
            .fin-data-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
                font-size: 12px;
            }
            .fin-data-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 6px;
                border-bottom: 1px dashed #f1f5f9;
            }
            .dark .fin-data-row {
                border-bottom-color: #334155;
            }
            .fin-data-label {
                color: #64748b;
                font-weight: 500;
            }
            .dark .fin-data-label {
                color: #94a3b8;
            }
            .fin-data-val {
                color: #0f172a;
                font-weight: 700;
                text-align: left;
            }
            .dark .fin-data-val {
                color: #f8fafc;
            }
            .fin-badge {
                font-size: 11px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 20px;
                line-height: 1;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .fin-badge-success {
                background: #f0fdf4;
                color: #166534;
                border: 1px solid #bbf7d0;
            }
            .fin-badge-warning {
                background: #fefce8;
                color: #854d0e;
                border: 1px solid #fef08a;
            }
            .fin-badge-danger {
                background: #fef2f2;
                color: #991b1b;
                border: 1px solid #fecaca;
            }
            .fin-badge-info {
                background: #eff6ff;
                color: #1e40af;
                border: 1px solid #bfdbfe;
            }

            /* Tables */
            .fin-table-wrap {
                width: 100%;
                overflow-x: auto;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                margin-top: 8px;
            }
            .dark .fin-table-wrap {
                border-color: #334155;
            }
            .fin-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                text-align: right;
            }
            .fin-table th {
                background: #f8fafc;
                padding: 7px 10px;
                font-weight: 700;
                color: #475569;
                border-bottom: 1px solid #e2e8f0;
                white-space: nowrap;
            }
            .dark .fin-table th {
                background: #0f172a;
                color: #94a3b8;
                border-color: #334155;
            }
            .fin-table td {
                padding: 7px 10px;
                border-bottom: 1px solid #f1f5f9;
                color: #1e293b;
                vertical-align: middle;
            }
            .dark .fin-table td {
                border-color: #334155;
                color: #cbd5e1;
            }
            .fin-table tr:last-child td {
                border-bottom: none;
            }
            .fin-footer-note {
                font-size: 11px;
                color: #64748b;
                text-align: start;
                padding-top: 4px;
            }
            .dark .fin-footer-note {
                color: #94a3b8;
            }
        </style>

        {{-- MAIN TWO-COLUMN GRID: LEFT = 320px / RIGHT = 1fr --}}
        <div class="fin-grid-main">

            {{-- ================= LEFT COLUMN ================= --}}
            <div style="display: flex; flex-direction: column; gap: 16px;">

                {{-- 1. Financial Details Card --}}
                <div class="fin-card">
                    <div class="fin-header-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                        {{ __('financial.summary.financial_data') }}
                    </div>
                    <div class="fin-header-subtitle">{{ __('financial.summary.financial_data_desc') }}</div>

                    <div class="fin-data-list">
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('services_tracking.dashboard.client_entity') }}:</span>
                            <span class="fin-data-val">{{ $record->company?->trade_name ?? ($record->company?->name ?? '—') }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('services_tracking.dashboard.required_service') }}:</span>
                            <span class="fin-data-val" style="color: #2563eb;">{{ $record->display_service_name }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('services_tracking.dashboard.beneficiary') }}:</span>
                            <span class="fin-data-val">{{ $beneficiaryDisplay }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('services_tracking.dashboard.executor') }}:</span>
                            <span class="fin-data-val" style="color: #d97706;">{{ $responsibleEmployeeName }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.client_due') }}:</span>
                            <span class="fin-data-val" style="font-size: 13px; color: #047857;">{{ $money($clientDue) }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.received') }}:</span>
                            <span class="fin-data-val" style="color: #15803d;">{{ $money($received) }}</span>
                        </div>
                        @if ($surplusRefunded > 0)
                            <div class="fin-data-row">
                                <span class="fin-data-label">{{ __('financial.summary.surplus_refunded') }}:</span>
                                <span class="fin-data-val" style="color: #0891b2;">{{ $money($surplusRefunded) }}</span>
                            </div>
                        @endif
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.net_cash') }}:</span>
                            <span class="fin-data-val" style="color: #0f172a; font-weight: 800;">{{ $money($netCash) }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.client_remaining') }}:</span>
                            <span class="fin-data-val" style="color: {{ $clientRemaining > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ $clientRemaining > 0 ? $money($clientRemaining) : __('financial.summary.cleared').' ✓' }}
                            </span>
                        </div>
                        <div class="fin-data-row" style="border-bottom: none;">
                            <span class="fin-data-label">{{ __('services_tracking.dashboard.due_date') }}:</span>
                            <span class="fin-data-val" style="color: #b91c1c; font-size: 11px;">
                                {{ $record->due_date ? $record->due_date->format('Y-m-d') : __('services_tracking.dashboard.due_in_days', ['days' => 7]) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 2. Cash & Custody Status Card --}}
                <div class="fin-card">
                    <div class="fin-header-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span>
                        {{ __('financial.summary.cash_custody_details') }}
                    </div>
                    <div class="fin-header-subtitle">{{ __('financial.summary.cash_custody_details_desc') }}</div>

                    <div class="fin-data-list">
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.cash_received') }}:</span>
                            <span class="fin-data-val">{{ $money($cashReceived) }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.non_cash_portion') }}</span>
                            <span class="fin-data-val">{{ $money($nonCashReceived) }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.treasury_received') }}:</span>
                            <span class="fin-data-val" style="color: #1e40af;">{{ $money($treasuryReceived) }}</span>
                        </div>
                        <div class="fin-data-row">
                            <span class="fin-data-label">{{ __('financial.summary.custody') }}:</span>
                            <span class="fin-data-val" style="color: {{ $custody > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ $custody > 0 ? $money($custody).' (معلقة)' : '0.00 '.$currency.' (مسواة ✓)' }}
                            </span>
                        </div>
                        <div class="fin-data-row" style="border-bottom: none;">
                            <span class="fin-data-label">{{ __('financial.summary.pending_receipts') }}:</span>
                            <span class="fin-data-val" style="color: {{ $pendingReceiptsCount > 0 ? '#dc2626' : '#64748b' }};">
                                {{ $money($pendingReceipts) }} ({{ $pendingReceiptsCount }})
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ================= RIGHT COLUMN ================= --}}
            <div style="display: flex; flex-direction: column; gap: 16px;">

                {{-- 1. Service Financial Lifecycle (Stepper Card) --}}
                <div class="fin-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <div>
                            <div class="fin-header-title" style="margin-bottom: 0;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                                {{ __('financial.summary.financial_lifecycle') }}
                            </div>
                            <div class="fin-header-subtitle" style="margin-bottom: 0;">
                                {{ __('financial.summary.financial_lifecycle_desc') }}
                            </div>
                        </div>

                        <span class="fin-badge {{ $isFullyMatched ? 'fin-badge-success' : 'fin-badge-warning' }}">
                            {{ $isFullyMatched ? __('financial.summary.status_matched') : __('financial.summary.status_action_needed') }}
                        </span>
                    </div>

                    {{-- 6-Stages Grid --}}
                    <div class="fin-stepper-grid" style="margin-top: 14px;">
                        {{-- 1: Client Due Defined --}}
                        <div class="fin-step-box completed">
                            <div class="fin-step-circle" style="background: #10b981;">✓</div>
                            <div class="fin-step-title">{{ __('financial.summary.step_due_title') }}</div>
                            <div class="fin-step-status" style="color: #16a34a;">{{ $money($clientDue) }}</div>
                        </div>

                        {{-- 2: Client Collection --}}
                        @php
                            $step2Completed = $received > 0;
                        @endphp
                        <div class="fin-step-box {{ $step2Completed ? 'completed' : 'current' }}">
                            <div class="fin-step-circle" style="background: {{ $step2Completed ? '#10b981' : '#f59e0b' }};">
                                {{ $step2Completed ? '✓' : '2' }}
                            </div>
                            <div class="fin-step-title">{{ __('financial.summary.step_receipt_title') }}</div>
                            <div class="fin-step-status" style="color: {{ $step2Completed ? '#16a34a' : '#d97706' }};">
                                {{ $step2Completed ? __('services_tracking.dashboard.stages.completed') : __('services_tracking.dashboard.stages.waiting_collection') }}
                            </div>
                        </div>

                        {{-- 3: Treasury Handover --}}
                        @php
                            $step3Completed = $custody <= 0.001 && $treasuryReceived > 0;
                        @endphp
                        <div class="fin-step-box {{ $step3Completed ? 'completed' : ($custody > 0 ? 'warning' : '') }}">
                            <div class="fin-step-circle" style="background: {{ $step3Completed ? '#10b981' : ($custody > 0 ? '#f59e0b' : '#0284c7') }};">
                                {{ $step3Completed ? '✓' : '3' }}
                            </div>
                            <div class="fin-step-title">{{ __('financial.summary.step_handover_title') }}</div>
                            <div class="fin-step-status" style="color: {{ $step3Completed ? '#16a34a' : ($custody > 0 ? '#d97706' : '#0284c7') }};">
                                {{ $step3Completed ? __('financial.summary.status_in_treasury') : ($custody > 0 ? __('financial.summary.status_with_collector') : __('financial.summary.status_settled')) }}
                            </div>
                        </div>

                        {{-- 4: Surplus Settlement --}}
                        @php
                            $step4Completed = $surplusRefunded > 0 || $surplus <= 0.001;
                        @endphp
                        <div class="fin-step-box {{ $step4Completed ? 'completed' : 'warning' }}">
                            <div class="fin-step-circle" style="background: {{ $step4Completed ? '#10b981' : '#f59e0b' }};">
                                {{ $step4Completed ? '✓' : '4' }}
                            </div>
                            <div class="fin-step-title">{{ __('financial.summary.step_surplus_title') }}</div>
                            <div class="fin-step-status" style="color: {{ $step4Completed ? '#16a34a' : '#d97706' }};">
                                {{ $surplusRefunded > 0 ? __('financial.summary.status_refunded') : ($surplus > 0 ? __('financial.summary.surplus') : __('financial.summary.status_no_surplus')) }}
                            </div>
                        </div>

                        {{-- 5: Entity Fees --}}
                        @php
                            $step5Completed = $feePaid > 0 || $feeExpected === null || $feeRemaining <= 0;
                        @endphp
                        <div class="fin-step-box {{ $step5Completed ? 'completed' : 'current' }}">
                            <div class="fin-step-circle" style="background: {{ $step5Completed ? '#10b981' : '#0284c7' }};">
                                {{ $step5Completed ? '✓' : '5' }}
                            </div>
                            <div class="fin-step-title">{{ __('financial.summary.step_fees_title') }}</div>
                            <div class="fin-step-status" style="color: {{ $step5Completed ? '#16a34a' : '#0284c7' }};">
                                {{ $feePaid > 0 ? __('financial.summary.status_fees_paid') : ($feeExpected > 0 ? __('financial.summary.status_fees_pending') : __('financial.summary.status_no_fees')) }}
                            </div>
                        </div>

                        {{-- 6: Final Closure --}}
                        <div class="fin-step-box {{ $isFullyMatched ? 'completed' : '' }}">
                            <div class="fin-step-circle" style="background: {{ $isFullyMatched ? '#10b981' : '#94a3b8' }};">
                                {{ $isFullyMatched ? '✓' : '6' }}
                            </div>
                            <div class="fin-step-title">{{ __('financial.summary.step_closure_title') }}</div>
                            <div class="fin-step-status" style="color: {{ $isFullyMatched ? '#16a34a' : '#64748b' }};">
                                {{ $isFullyMatched ? __('financial.summary.status_matched') : __('financial.summary.status_action_needed') }}
                            </div>
                        </div>
                    </div>

                    {{-- Status Bar --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 11px;">
                        <div>
                            <span style="color: #64748b;">{{ __('services_tracking.dashboard.executor') }}:</span>
                            <strong style="color: #1e293b; margin-right: 4px;">{{ $responsibleEmployeeName }}</strong>
                        </div>
                        <div style="color: #64748b;">
                            {{ __('services_tracking.dashboard.due_date') }}:
                            <strong style="color: #0f172a;">{{ $record->due_date ? $record->due_date->format('Y-m-d') : __('services_tracking.dashboard.due_in_days', ['days' => 7]) }}</strong>
                        </div>
                        @if ($custody > 0)
                            <span style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                {{ __('financial.summary.status_custody_pending') }}: {{ $money($custody) }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- 2. Money Trail & KPI Grid --}}
                <div class="fin-card">
                    <div class="fin-header-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                        {{ __('financial.summary.money_trail') }}
                    </div>
                    <div class="fin-header-subtitle">
                        {{ __('financial.summary.money_trail_desc') }}
                    </div>

                    {{-- Row 1: 4 Big KPI Cards --}}
                    <div class="fin-kpi-grid">
                        {{-- 1. Client Due --}}
                        <div class="fin-kpi-box" style="background: #fffbeb; border-color: #fde68a;">
                            <div class="fin-kpi-label" style="color: #92400e;">{{ __('financial.summary.client_due') }}</div>
                            <div class="fin-kpi-val" style="color: #78350f;">
                                {{ number_format($clientDue, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 2. Current Custody --}}
                        <div class="fin-kpi-box" style="background: #f5f3ff; border-color: #ddd6fe;">
                            <div class="fin-kpi-label" style="color: #5b21b6;">{{ __('financial.summary.custody') }}</div>
                            <div class="fin-kpi-val" style="color: #4c1d95;">
                                {{ number_format($custody, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 3. Received at Treasury --}}
                        <div class="fin-kpi-box" style="background: #eff6ff; border-color: #bfdbfe;">
                            <div class="fin-kpi-label" style="color: #1e40af;">{{ __('financial.summary.treasury_received') }}</div>
                            <div class="fin-kpi-val" style="color: #1e3a8a;">
                                {{ number_format($treasuryReceived, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 4. Net Cash / Certified Disbursement --}}
                        <div class="fin-kpi-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                            <div class="fin-kpi-label" style="color: #166534;">{{ __('financial.summary.net_cash') }}</div>
                            <div class="fin-kpi-val" style="color: #14532d;">
                                {{ number_format($netCash, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Secondary 4 KPI Cards --}}
                    <div class="fin-kpi-grid">
                        {{-- 5. Confirmed Received --}}
                        <div class="fin-kpi-box" style="background: #f8fafc; border-color: #e2e8f0;">
                            <div class="fin-kpi-label" style="color: #475569;">{{ __('financial.summary.received') }}</div>
                            <div class="fin-kpi-val" style="color: #0f172a;">
                                {{ number_format($received, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 6. Surplus Refunded --}}
                        <div class="fin-kpi-box" style="background: #ecfeff; border-color: #a5f3fc;">
                            <div class="fin-kpi-label" style="color: #0e7490;">{{ __('financial.summary.surplus_refunded') }}</div>
                            <div class="fin-kpi-val" style="color: #155e75;">
                                {{ number_format($surplusRefunded, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 7. Client Remaining --}}
                        <div class="fin-kpi-box" style="background: {{ $clientRemaining <= 0.001 ? '#f0fdf4' : '#fff1f2' }}; border-color: {{ $clientRemaining <= 0.001 ? '#bbf7d0' : '#fecdd3' }};">
                            <div class="fin-kpi-label" style="color: {{ $clientRemaining <= 0.001 ? '#166534' : '#be123c' }};">{{ __('financial.summary.client_remaining') }}</div>
                            <div class="fin-kpi-val" style="color: {{ $clientRemaining <= 0.001 ? '#15803d' : '#9f1239' }};">
                                {{ number_format($clientRemaining, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        {{-- 8. Entity Fee Paid --}}
                        <div class="fin-kpi-box" style="background: #f8fafc; border-color: #e2e8f0;">
                            <div class="fin-kpi-label" style="color: #475569;">{{ __('financial.summary.fee_paid') }}</div>
                            <div class="fin-kpi-val" style="color: #0f172a;">
                                {{ number_format($feePaid, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Financial Trail Steps --}}
                    <div class="fin-trail-step">
                        <div>
                            <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                                1. {{ __('services_tracking.dashboard.step_1_title') }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                {{ __('financial.summary.received') }}: {{ $money($received) }} — {{ __('financial.summary.cash_received') }}: {{ $money($cashReceived) }} — {{ __('financial.summary.non_cash_portion') }} {{ $money($nonCashReceived) }}
                            </div>
                        </div>
                        <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                            {{ $clientRemaining <= 0.001 ? __('financial.summary.cleared').' ✓' : __('financial.summary.status_partial_paid') }}
                        </span>
                    </div>

                    <div class="fin-trail-step">
                        <div>
                            <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                                2. {{ __('services_tracking.dashboard.step_2_title') }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                {{ $custody <= 0.001 ? __('financial.summary.status_custody_cleared') : __('financial.summary.status_custody_pending').': '.$money($custody) }} — {{ __('financial.summary.treasury_received') }}: {{ $money($treasuryReceived) }}
                            </div>
                        </div>
                        <span style="background: {{ $custody <= 0.001 ? '#dcfce7' : '#fee2e2' }}; color: {{ $custody <= 0.001 ? '#166534' : '#991b1b' }}; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                            {{ $custody <= 0.001 ? __('financial.summary.status_settled') : __('financial.summary.status_with_collector') }}
                        </span>
                    </div>

                    @if ($surplusRefunded > 0 || $surplus > 0)
                        <div class="fin-trail-step">
                            <div>
                                <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                                    3. {{ __('financial.summary.step_surplus_title') }}
                                </div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                    {{ __('financial.summary.surplus_refunded') }}: {{ $money($surplusRefunded) }} — {{ __('financial.summary.surplus') }}: {{ $money($surplus) }}
                                </div>
                            </div>
                            <span style="background: #ecfeff; color: #0e7490; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                                {{ $surplusRefunded > 0 ? __('financial.summary.status_refunded') : __('financial.summary.surplus') }}
                            </span>
                        </div>
                    @endif

                    <div class="fin-trail-step">
                        <div>
                            <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                                {{ ($surplusRefunded > 0 || $surplus > 0) ? '4.' : '3.' }} {{ __('financial.summary.step_fees_title') }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                {{ __('financial.summary.fee_expected') }}: {{ $feeExpected !== null ? $money($feeExpected) : __('financial.summary.fee_undefined') }} — {{ __('financial.summary.fee_paid') }}: {{ $money($feePaid) }} — {{ __('financial.summary.employee_due') }}: {{ $money($employeeDue) }}
                            </div>
                        </div>
                        <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                            {{ $feePaid > 0 ? __('financial.summary.status_fees_paid') : __('financial.summary.status_no_fees') }}
                        </span>
                    </div>
                </div>

                {{-- 3. Receipts & Disbursements Tables --}}
                <div class="fin-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div class="fin-header-title" style="margin-bottom: 0;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284c7;"></span>
                            {{ __('financial.summary.recent_receipts') }}
                        </div>
                        <span style="font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">
                            {{ $receipts->count() }} {{ __('financial.summary.recent_receipts') }}
                        </span>
                    </div>

                    @if ($receipts->isNotEmpty())
                        <div class="fin-table-wrap">
                            <table class="fin-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('financial.summary.receipt_number') }}</th>
                                        <th>{{ __('financial.fields.amount') }}</th>
                                        <th>{{ __('financial.fields.payment_method') }}</th>
                                        <th>{{ __('financial.summary.collector') }}</th>
                                        <th>{{ __('financial.summary.status') }}</th>
                                        <th>{{ __('financial.summary.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($receipts as $rcp)
                                        @php
                                            $methodLabel = match($rcp->payment_method?->value ?? $rcp->payment_method) {
                                                'cash' => __('financial.payment_methods.cash'),
                                                'bank_transfer' => __('financial.payment_methods.bank_transfer'),
                                                'pos' => __('financial.payment_methods.pos'),
                                                default => (string) ($rcp->payment_method?->value ?? $rcp->payment_method),
                                            };
                                            $statusKey = $rcp->status?->value ?? $rcp->status;
                                            $statusLabel = match($statusKey) {
                                                'confirmed' => __('financial.receipt_statuses.confirmed'),
                                                'pending_confirmation' => __('financial.receipt_statuses.pending_confirmation'),
                                                'reversed' => __('financial.receipt_statuses.reversed'),
                                                default => (string) $statusKey,
                                            };
                                        @endphp
                                        <tr>
                                            <td style="font-weight: 700; font-family: monospace;">{{ $rcp->receipt_number }}</td>
                                            <td style="font-weight: 800; color: #166534;">{{ $money($rcp->amount) }}</td>
                                            <td>{{ $methodLabel }}</td>
                                            <td>{{ $rcp->collector?->user?->name ?? '—' }}</td>
                                            <td>
                                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; background: {{ $statusKey === 'confirmed' ? '#dcfce7' : '#fef3c7' }}; color: {{ $statusKey === 'confirmed' ? '#166534' : '#b45309' }};">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-family: monospace;">{{ $rcp->received_at?->format('Y-m-d') ?? $rcp->created_at?->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; color: #64748b; font-size: 11px;">
                            {{ __('financial.summary.no_receipts') }}
                        </div>
                    @endif

                    @if ($disbursements->isNotEmpty())
                        <div style="display: flex; justify-content: space-between; align-items: center; margin: 16px 0 8px 0;">
                            <div class="fin-header-title" style="margin-bottom: 0;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #d97706;"></span>
                                {{ __('financial.summary.recent_disbursements') }}
                            </div>
                            <span style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 6px;">
                                {{ $disbursements->count() }}
                            </span>
                        </div>

                        <div class="fin-table-wrap">
                            <table class="fin-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('financial.summary.disbursement_number') }}</th>
                                        <th>{{ __('financial.fields.amount') }}</th>
                                        <th>{{ __('financial.fields.disbursement_type') }}</th>
                                        <th>{{ __('financial.summary.status') }}</th>
                                        <th>{{ __('financial.summary.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($disbursements as $dsb)
                                        @php
                                            $typeKey = $dsb->disbursement_type?->value ?? $dsb->disbursement_type;
                                            $typeLabel = match($typeKey) {
                                                'treasury_direct' => __('financial.disbursement_types.treasury_direct'),
                                                'employee_personal' => __('financial.disbursement_types.employee_personal'),
                                                default => (string) $typeKey,
                                            };
                                            $dsbStatusKey = $dsb->status?->value ?? $dsb->status;
                                            $dsbStatusLabel = match($dsbStatusKey) {
                                                'paid' => __('financial.disbursement_statuses.paid'),
                                                'pending_approval' => __('financial.disbursement_statuses.pending_approval'),
                                                'approved' => __('financial.disbursement_statuses.approved'),
                                                'partially_reimbursed' => __('financial.disbursement_statuses.partially_reimbursed'),
                                                'fully_reimbursed' => __('financial.disbursement_statuses.fully_reimbursed'),
                                                default => (string) $dsbStatusKey,
                                            };
                                        @endphp
                                        <tr>
                                            <td style="font-weight: 700; font-family: monospace;">{{ $dsb->disbursement_number }}</td>
                                            <td style="font-weight: 800; color: #dc2626;">{{ $money($dsb->amount) }}</td>
                                            <td>{{ $typeLabel }}</td>
                                            <td>
                                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; background: {{ in_array($dsbStatusKey, ['paid', 'fully_reimbursed']) ? '#dcfce7' : '#eff6ff' }}; color: {{ in_array($dsbStatusKey, ['paid', 'fully_reimbursed']) ? '#166534' : '#1e40af' }};">
                                                    {{ $dsbStatusLabel }}
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-family: monospace;">{{ $dsb->created_at?->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Footer Note --}}
                <div class="fin-footer-note">
                    * {{ __('financial.summary.description') }}
                </div>

            </div>

        </div>
    </div>
@endif
