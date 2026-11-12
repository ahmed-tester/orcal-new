@php
    /** @var \App\Models\ServiceTransaction $record */
    $meta = $record->metadata ?? [];

    // Financial numbers
    $totalAmount = (float) $record->total_amount;
    $isManaged = $record->isFinancialManaged();
    $summary = $isManaged ? $record->financialSummary() : null;

    if ($summary) {
        $paidAmount = (float) $summary['received'];
        $remainingAmount = (float) $summary['client_remaining'];
        $currentCustody = (float) $summary['custody'];
        $treasuryAmount = (float) $summary['treasury_received'];
        $disbursedAmount = (float) $summary['fee_paid'];
        $surplusRefunded = (float) $summary['surplus_refunded'];
        $netCash = (float) $summary['net_cash'];
        $feeExpected = $summary['fee_defined'] ? (float) $summary['fee_expected'] : null;
        $employeeDue = (float) $summary['employee_due'];
        $isPaidToTreasury = $currentCustody <= 0.001 && $treasuryAmount > 0;
    } else {
        $paidAmount = (float) $record->paid_amount;
        $remainingAmount = (float) $record->remaining_amount;
        $custodyStatus = $meta['custody_status'] ?? 'on_employee';
        $isPaidToTreasury = in_array($custodyStatus, ['in_treasury', 'settled'], true);
        $treasuryAmount = $isPaidToTreasury ? $paidAmount : 0.0;
        $disbursedAmount = (float) ($meta['gov_fee'] ?? ($meta['disbursed_amount'] ?? 0.0));
        $currentCustody = $isPaidToTreasury ? 0.0 : $paidAmount;
        $surplusRefunded = 0.0;
        $netCash = $treasuryAmount - $disbursedAmount;
        $feeExpected = null;
        $employeeDue = 0.0;
    }

    $receipts = $record->financialReceipts()->with(['collector.user'])->latest()->take(5)->get();
    $disbursements = $record->financialDisbursements()->latest()->take(5)->get();

    // Workflow status
    $workflowStatus = \App\Enums\OperationWorkflowStatus::tryFrom((string) $record->workflow_status) ?? \App\Enums\OperationWorkflowStatus::NEW;
    $openTasks = $record->deficiencyTasks()->open()->get();
    $allTasks = $record->deficiencyTasks()->latest()->get();
    $auditLogs = $record->auditLogs()->latest()->take(10)->get();

    $eval = \App\Services\OperationsReconciliationService::evaluate($record);
    $isLiveMatched = ($eval['status'] === 'matched') && $openTasks->isEmpty();
    if ($isLiveMatched) {
        $workflowStatus = \App\Enums\OperationWorkflowStatus::COMPLETED;
    }

    $responsibleEmployeeName = $record->executor?->user?->name 
        ?? ($record->employee?->user?->name ?? '—');

    // Currency symbol safe fallback
    $currency = __('services_tracking.currency_sar');
    if ($currency === 'services_tracking.currency_sar' || empty($currency)) {
        $currency = 'ر.س';
    }

    // Beneficiary display
    $beneficiaryDisplay = $record->beneficiary_name 
        ?: ($record->employee?->user?->name 
            ?: ($record->company?->trade_name ?: '—'));

    $costBearerLabel = match ($meta['cost_bearer'] ?? 'client_paid') {
        'office_paid' => __('services_tracking.cost_bearers.office_paid'),
        'shared' => __('services_tracking.cost_bearers.shared'),
        default => __('services_tracking.cost_bearers.client_paid'),
    };

    // Digital code (from metadata if entered, otherwise fallback)
    $digitalSignatureCode = $meta['employee_signature_code'] ?? str_pad((string) ((($record->id ?: 1) * 73) % 9000 + 1000), 4, '0', STR_PAD_LEFT);

    // 10-Stages state
    $stage1 = true;
    $stage2 = true;
    $stage3 = $paidAmount > 0;
    $stage4 = $paidAmount > 0;
    $stage5 = $isPaidToTreasury || !empty($meta['handover_requested_at']);
    $stage6 = $isPaidToTreasury;
    $stage7 = $isPaidToTreasury && ($disbursedAmount > 0 || !empty($meta['allocated_at']) || $isLiveMatched);
    $stage8 = $disbursedAmount > 0 || !empty($meta['batch_deposit_number']) || !empty($meta['gov_payment_receipt']) || $isLiveMatched;
    
    $isReconciliationMatched = $isLiveMatched || ($workflowStatus === \App\Enums\OperationWorkflowStatus::COMPLETED)
        || (($meta['reconciliation_status'] ?? '') === 'matched' && $openTasks->isEmpty());

    $isReconciliationMismatched = ! $isReconciliationMatched && (
        ($workflowStatus === \App\Enums\OperationWorkflowStatus::MISMATCHED)
        || $openTasks->isNotEmpty()
        || in_array(($meta['reconciliation_status'] ?? ''), ['mismatched', 'amount_diff', 'missing_document', 'missing_custody', 'missing_counterparty'], true)
        || ! empty($eval['missing_parts'])
    );

    $stage9 = $isReconciliationMatched;
    $stage10 = $workflowStatus === \App\Enums\OperationWorkflowStatus::COMPLETED || $isLiveMatched;

    $govReceipt = $meta['gov_payment_receipt'] ?? null;
    $bankReceipt = $meta['bank_deposit_receipt'] ?? null;
@endphp

<style>
    .op-dash {
        direction: rtl;
        font-family: inherit;
        color: #1e293b;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .op-dash * {
        box-sizing: border-box;
    }
    .op-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .op-grid-main {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .op-grid-main {
            grid-template-columns: 1fr;
        }
    }
    .op-header-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    .op-header-subtitle {
        font-size: 11px;
        color: #64748b;
        margin-bottom: 14px;
    }
    .op-stepper-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
        margin-bottom: 14px;
    }
    @media (max-width: 768px) {
        .op-stepper-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .op-step-box {
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
    .op-step-box.completed {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .op-step-box.current {
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .op-step-box.danger {
        background: #fef2f2;
        border-color: #fecaca;
    }
    .op-step-circle {
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
    .op-step-title {
        font-size: 11px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.25;
        margin-bottom: 3px;
    }
    .op-step-status {
        font-size: 10px;
        font-weight: 600;
    }
    .op-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 14px;
    }
    @media (max-width: 640px) {
        .op-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .op-kpi-box {
        border-radius: 10px;
        padding: 12px 10px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    .op-kpi-label {
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .op-kpi-val {
        font-size: 17px;
        font-weight: 800;
    }
    .op-trail-step {
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
    .op-data-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 12px;
    }
    .op-data-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 6px;
        border-bottom: 1px dashed #f1f5f9;
    }
    .op-data-label {
        color: #64748b;
        font-weight: 500;
    }
    .op-data-val {
        color: #0f172a;
        font-weight: 700;
        text-align: left;
    }
</style>

<div class="op-dash">

    {{-- GRID: LEFT = 320px (Data, Tasks, Digital Code) / RIGHT = Remaining (Stepper, Money Stream, Audit) --}}
    <div class="op-grid-main">

        {{-- ================= LEFT COLUMN ================= --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">

            {{-- 1. Operation Details --}}
            <div class="op-card">
                <div class="op-header-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                    {{ __('services_tracking.dashboard.operation_details') }}
                </div>
                <div class="op-header-subtitle">{{ __('services_tracking.dashboard.operation_details_desc') }}</div>

                <div class="op-data-list">
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.client_entity') }}:</span>
                        <span class="op-data-val">{{ $record->company?->trade_name ?? '—' }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.required_service') }}:</span>
                        <span class="op-data-val" style="color: #2563eb;">{{ $record->service?->name ?? ($record->service_name ?? '—') }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.beneficiary') }}:</span>
                        <span class="op-data-val">{{ $beneficiaryDisplay }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.counterparty') }}:</span>
                        <span class="op-data-val">{{ !empty($meta['target_platform']) ? $meta['target_platform'] : __('services_tracking.dashboard.gov_platform') }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.creator') }}:</span>
                        <span class="op-data-val">{{ $record->creator?->name ?? __('services_tracking.audit.system') }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.executor') }}:</span>
                        <span class="op-data-val" style="color: #d97706;">{{ $responsibleEmployeeName }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.cost_bearer') }}:</span>
                        <span class="op-data-val">{{ $costBearerLabel }}</span>
                    </div>
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.total_amount') }}:</span>
                        <span class="op-data-val" style="font-size: 13px; color: #047857;">{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
                    </div>
                    @if($surplusRefunded > 0)
                        <div class="op-data-row">
                            <span class="op-data-label">{{ __('financial.summary.surplus_refunded') }}:</span>
                            <span class="op-data-val" style="color: #0891b2;">{{ number_format($surplusRefunded, 2) }} {{ $currency }}</span>
                        </div>
                    @endif
                    @if($isManaged)
                        <div class="op-data-row">
                            <span class="op-data-label">{{ __('financial.summary.net_cash') }}:</span>
                            <span class="op-data-val" style="color: #0f172a; font-weight: 800;">{{ number_format($netCash, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="op-data-row">
                            <span class="op-data-label">{{ __('financial.summary.client_remaining') }}:</span>
                            <span class="op-data-val" style="color: {{ $remainingAmount > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ $remainingAmount > 0 ? (number_format($remainingAmount, 2).' '.$currency) : __('financial.summary.cleared').' ✓' }}
                            </span>
                        </div>
                    @endif
                    <div class="op-data-row">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.created_at') }}:</span>
                        <span class="op-data-val" style="font-size: 11px; font-family: monospace;">{{ $record->created_at?->format('Y-m-d') }}</span>
                    </div>
                    <div class="op-data-row" style="border-bottom: none;">
                        <span class="op-data-label">{{ __('services_tracking.dashboard.due_date') }}:</span>
                        <span class="op-data-val" style="color: #b91c1c; font-size: 11px;">{{ $record->due_date ? $record->due_date->format('Y-m-d') : __('services_tracking.dashboard.due_in_days', ['days' => 7]) }}</span>
                    </div>
                </div>
            </div>

            {{-- 2. Deficiency Tasks --}}
            <div class="op-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div class="op-header-title" style="margin-bottom: 0;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $openTasks->count() > 0 ? '#ef4444' : '#10b981' }};"></span>
                        {{ __('services_tracking.dashboard.deficiency_tasks') }}
                    </div>
                    <span style="background: {{ $openTasks->count() > 0 ? '#fee2e2' : '#dcfce7' }}; color: {{ $openTasks->count() > 0 ? '#991b1b' : '#166534' }}; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                        {{ __('services_tracking.dashboard.open_tasks_count', ['count' => $openTasks->count()]) }}
                    </span>
                </div>
                <div class="op-header-subtitle">{{ __('services_tracking.dashboard.deficiency_tasks_desc') }}</div>

                @if($allTasks->isEmpty())
                    <div style="background: #f0fdf4; border: 1px dashed #bbf7d0; border-radius: 8px; padding: 14px; text-align: center; color: #166534; font-size: 11px; font-weight: 600;">
                        {{ __('services_tracking.dashboard.no_tasks_recorded') }}
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach($allTasks as $task)
                            <div style="background: {{ $task->status === 'open' ? '#fff5f5' : '#f8fafc' }}; border: 1px solid {{ $task->status === 'open' ? '#fecaca' : '#e2e8f0' }}; border-radius: 8px; padding: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px; gap: 6px;">
                                    <div style="font-weight: 700; font-size: 11px; color: #1e293b;">
                                        {{ $task->title }}
                                    </div>
                                    <span style="font-family: monospace; font-size: 9px; font-weight: 700; padding: 1px 4px; border-radius: 4px; background: {{ $task->status === 'open' ? '#fee2e2' : '#e2e8f0' }}; color: {{ $task->status === 'open' ? '#991b1b' : '#475569' }};">
                                        {{ $task->task_code }}
                                    </span>
                                </div>
                                <div style="font-size: 10px; color: #64748b; margin-bottom: 6px;">
                                    {{ __('services_tracking.dashboard.executor') }}: {{ $task->responsibleEmployee?->user?->name ?? '—' }}
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 4px; font-size: 10px;">
                                    <span style="color: #64748b;">{{ __('services_tracking.dashboard.due_date') }}: {{ $task->due_date ? $task->due_date->format('Y-m-d') : __('services_tracking.dashboard.immediate') }}</span>
                                    <span style="font-weight: 700; color: {{ $task->status === 'open' ? '#dc2626' : '#16a34a' }};">
                                        {{ $task->status === 'open' ? __('services_tracking.dashboard.task_pending') : __('services_tracking.dashboard.task_closed') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 3. Digital Signature & QR Verification --}}
            <div class="op-card">
                <div class="op-header-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284c7;"></span>
                    {{ __('services_tracking.dashboard.digital_signature') }}
                </div>
                <div class="op-header-subtitle">{{ __('services_tracking.dashboard.digital_signature_desc') }}</div>

                {{-- QR Code Section --}}
                @php
                    $isManaged = $record->isFinancialManaged();
                    $submittedHandover = null;
                    if ($isManaged) {
                        $submittedHandover = \App\Models\CashHandover::accessibleBy()
                            ->submitted()
                            ->whereHas('items', fn ($query) => $query->whereHas('receipt', fn ($receiptQuery) => $receiptQuery
                                ->forSource(\App\Models\FinancialReceipt::SOURCE_SERVICE_TRANSACTION, (int) $record->id)))
                            ->latest('id')
                            ->first();
                    }

                    $signatureCode = $isManaged
                        ? ($submittedHandover ? \App\Services\CashHandoverService::signatureCodeForDelivery($submittedHandover) : null)
                        : $digitalSignatureCode;

                    if ($isManaged) {
                        if ($submittedHandover) {
                            $qrPayload = 'HANDOVER:' . $submittedHandover->handover_number;
                            $qrCodeSvg = \App\Services\BarcodeService::generateQrSvg($qrPayload, 5);
                            $displayNumber = $submittedHandover->handover_number;
                            $scanHint = __('financial.handover_qr.scan_hint');
                        } else {
                            $qrPayload = null;
                            $qrCodeSvg = null;
                            $displayNumber = null;
                            $scanHint = __('financial.handover_qr.no_pending_handover');
                        }
                    } else {
                        $qrPayload = __('services_tracking.custody.qr_payload_format', [
                            'code' => $record->transaction_number,
                            'pin' => $digitalSignatureCode,
                        ]);
                        $qrCodeSvg = \App\Services\BarcodeService::generateQrSvg($qrPayload, 5);
                        $displayNumber = $record->transaction_number;
                        $scanHint = __('services_tracking.dashboard.qr_scan_hint');
                    }
                @endphp
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    @if($qrCodeSvg)
                        <div style="max-width: 140px; margin: 0 auto 8px auto;">
                            {!! $qrCodeSvg !!}
                        </div>
                    @else
                        <div style="padding: 12px 6px; color: #64748b; font-size: 11px;">
                            <div style="font-size: 20px; margin-bottom: 4px;">ℹ️</div>
                            {{ $scanHint }}
                        </div>
                    @endif
                    @if($displayNumber !== null)
                        <div style="font-size: 12px; font-weight: 800; color: #1e293b; font-family: monospace; letter-spacing: 1px;">
                            {{ $displayNumber }}
                        </div>
                    @endif
                    @if($qrCodeSvg)
                        <div style="font-size: 10px; color: #64748b; margin-top: 3px;">
                            {{ $scanHint }}
                        </div>
                    @endif
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; text-align: center;">
                    @if($signatureCode !== null)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                            <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">
                                {{ $isManaged ? __('financial.fields.handover_signature') : __('services_tracking.dashboard.employee_signature') }}
                            </div>
                            <div dir="ltr" style="font-size: 18px; font-weight: 900; font-family: monospace; color: #0f172a; letter-spacing: 2px;">
                                {{ $signatureCode }}
                            </div>
                        </div>
                    @endif

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                        <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">{{ __('services_tracking.dashboard.treasury_signature') }}</div>
                        <div style="font-size: 11px; font-weight: 700; color: {{ $isPaidToTreasury ? '#16a34a' : '#d97706' }}; margin-top: 4px;">
                            {{ $isPaidToTreasury ? __('services_tracking.dashboard.treasury_received') : __('services_tracking.dashboard.treasury_waiting') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ================= RIGHT COLUMN ================= --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">

            {{-- 1. Service Path & Responsibility --}}
            <div class="op-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div>
                        <div class="op-header-title" style="margin-bottom: 0;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                            {{ __('services_tracking.dashboard.service_and_responsibility') }}
                        </div>
                        <div class="op-header-subtitle" style="margin-bottom: 0;">
                            {{ __('services_tracking.dashboard.service_responsibility_desc') }}
                        </div>
                    </div>

                    <span style="background: {{ $workflowStatus->getColor() === 'success' ? '#f0fdf4' : ($workflowStatus->getColor() === 'danger' ? '#fef2f2' : '#fefce8') }}; color: {{ $workflowStatus->getColor() === 'success' ? '#166534' : ($workflowStatus->getColor() === 'danger' ? '#991b1b' : '#854d0e') }}; border: 1px solid {{ $workflowStatus->getColor() === 'success' ? '#bbf7d0' : ($workflowStatus->getColor() === 'danger' ? '#fecaca' : '#fef08a') }}; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">
                        {{ $workflowStatus->getLabel() }}
                    </span>
                </div>

                {{-- 10-Stages Grid --}}
                <div class="op-stepper-grid" style="margin-top: 14px;">
                    {{-- 1 --}}
                    <div class="op-step-box {{ $stage1 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage1 ? '#10b981' : '#94a3b8' }};">✓</div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.client_and_service') }}</div>
                        <div class="op-step-status" style="color: #16a34a;">{{ __('services_tracking.dashboard.stages.completed') }}</div>
                    </div>

                    {{-- 2 --}}
                    <div class="op-step-box {{ $stage2 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage2 ? '#10b981' : '#94a3b8' }};">✓</div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.cost_bearing') }}</div>
                        <div class="op-step-status" style="color: #16a34a;">{{ __('services_tracking.dashboard.stages.completed') }}</div>
                    </div>

                    {{-- 3 --}}
                    <div class="op-step-box {{ $stage3 ? 'completed' : 'current' }}">
                        <div class="op-step-circle" style="background: {{ $stage3 ? '#10b981' : '#f59e0b' }};">
                            {{ $stage3 ? '✓' : '3' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.client_collection') }}</div>
                        <div class="op-step-status" style="color: {{ $stage3 ? '#16a34a' : '#d97706' }};">
                            {{ $stage3 ? __('services_tracking.dashboard.stages.completed') : __('services_tracking.dashboard.stages.waiting_collection') }}
                        </div>
                    </div>

                    {{-- 4 --}}
                    <div class="op-step-box {{ $stage4 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $isPaidToTreasury ? '#10b981' : '#8b5cf6' }};">
                            {{ $stage4 ? '✓' : '4' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.employee_custody') }}</div>
                        <div class="op-step-status" style="color: {{ $isPaidToTreasury ? '#16a34a' : '#7c3aed' }};">
                            {{ $isPaidToTreasury ? __('services_tracking.dashboard.stages.settled') : __('services_tracking.dashboard.stages.with_employee') }}
                        </div>
                    </div>

                    {{-- 5 --}}
                    <div class="op-step-box {{ $stage5 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage5 ? '#10b981' : '#0284c7' }};">
                            {{ $stage5 ? '✓' : '5' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.treasury_handover') }}</div>
                        <div class="op-step-status" style="color: {{ $stage5 ? '#16a34a' : '#0284c7' }};">
                            {{ $stage5 ? __('services_tracking.dashboard.stages.sent') : __('services_tracking.dashboard.stages.not_started') }}
                        </div>
                    </div>

                    {{-- 6 --}}
                    <div class="op-step-box {{ $stage6 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage6 ? '#10b981' : '#94a3b8' }};">
                            {{ $stage6 ? '✓' : '6' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.treasury_confirm') }}</div>
                        <div class="op-step-status" style="color: {{ $stage6 ? '#16a34a' : '#64748b' }};">
                            {{ $stage6 ? __('services_tracking.dashboard.stages.completed') : __('services_tracking.dashboard.stages.waiting_treasury') }}
                        </div>
                    </div>

                    {{-- 7 --}}
                    <div class="op-step-box {{ $stage7 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage7 ? '#10b981' : '#94a3b8' }};">
                            {{ $stage7 ? '✓' : '7' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.financial_allocation') }}</div>
                        <div class="op-step-status" style="color: {{ $stage7 ? '#16a34a' : '#64748b' }};">
                            {{ $stage7 ? __('services_tracking.dashboard.stages.allocated') : __('services_tracking.dashboard.stages.waiting_allocation') }}
                        </div>
                    </div>

                    {{-- 8 --}}
                    <div class="op-step-box {{ $stage8 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage8 ? '#10b981' : '#94a3b8' }};">
                            {{ $stage8 ? '✓' : '8' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.payment_or_deposit') }}</div>
                        <div class="op-step-status" style="color: {{ $stage8 ? '#16a34a' : '#64748b' }};">
                            {{ $stage8 ? __('services_tracking.dashboard.stages.certified') : __('services_tracking.dashboard.stages.waiting_payment') }}
                        </div>
                    </div>

                    {{-- 9 --}}
                    @php
                        $stage9Class = $isReconciliationMismatched ? 'danger' : ($stage9 ? 'completed' : 'current');
                        $stage9Bg = $isReconciliationMismatched ? '#ef4444' : ($stage9 ? '#10b981' : '#0284c7');
                        $stage9Icon = $isReconciliationMismatched ? '!' : ($stage9 ? '✓' : '9');
                        $stage9Color = $isReconciliationMismatched ? '#dc2626' : ($stage9 ? '#16a34a' : '#0284c7');
                        $stage9StatusText = $isReconciliationMismatched ? __('services_tracking.dashboard.stages.mismatched') : ($stage9 ? __('services_tracking.dashboard.stages.matched') : __('services_tracking.dashboard.stages.current_stage'));
                    @endphp
                    <div class="op-step-box {{ $stage9Class }}">
                        <div class="op-step-circle" style="background: {{ $stage9Bg }};">
                            {{ $stage9Icon }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.two_way_reconciliation') }}</div>
                        <div class="op-step-status" style="color: {{ $stage9Color }};">
                            {{ $stage9StatusText }}
                        </div>
                    </div>

                    {{-- 10 --}}
                    <div class="op-step-box {{ $stage10 ? 'completed' : '' }}">
                        <div class="op-step-circle" style="background: {{ $stage10 ? '#10b981' : '#94a3b8' }};">
                            {{ $stage10 ? '✓' : '10' }}
                        </div>
                        <div class="op-step-title">{{ __('services_tracking.dashboard.stages.final_closure') }}</div>
                        <div class="op-step-status" style="color: {{ $stage10 ? '#16a34a' : '#94a3b8' }};">
                            {{ $stage10 ? __('services_tracking.dashboard.stages.completed') : __('services_tracking.dashboard.stages.not_started') }}
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
                        {{ __('services_tracking.dashboard.due_date') }}: <strong style="color: #0f172a;">{{ $record->due_date ? $record->due_date->format('Y-m-d') : __('services_tracking.dashboard.due_in_days', ['days' => 7]) }}</strong>
                    </div>
                    @if($openTasks->count() > 0)
                        <span style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                            {{ __('services_tracking.dashboard.mismatch_alert') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- 2. Money Flow & Stream --}}
            <div class="op-card">
                <div class="op-header-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                    {{ __('services_tracking.dashboard.money_trail') }}
                </div>
                <div class="op-header-subtitle">
                    {{ __('services_tracking.dashboard.money_trail_desc') }}
                </div>

                {{-- 4 Big KPI Cards --}}
                <div class="op-kpi-grid">
                    <div class="op-kpi-box" style="background: #fffbeb; border-color: #fde68a;">
                        <div class="op-kpi-label" style="color: #92400e;">{{ __('services_tracking.dashboard.collected_from_client') }}</div>
                        <div class="op-kpi-val" style="color: #78350f;">
                            {{ number_format($paidAmount, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                        </div>
                    </div>

                    <div class="op-kpi-box" style="background: #f5f3ff; border-color: #ddd6fe;">
                        <div class="op-kpi-label" style="color: #5b21b6;">{{ __('services_tracking.dashboard.current_custody') }}</div>
                        <div class="op-kpi-val" style="color: #4c1d95;">
                            {{ number_format($currentCustody, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                        </div>
                    </div>

                    <div class="op-kpi-box" style="background: #eff6ff; border-color: #bfdbfe;">
                        <div class="op-kpi-label" style="color: #1e40af;">{{ __('services_tracking.dashboard.received_at_treasury') }}</div>
                        <div class="op-kpi-val" style="color: #1e3a8a;">
                            {{ number_format($treasuryAmount, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                        </div>
                    </div>

                    <div class="op-kpi-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                        <div class="op-kpi-label" style="color: #166534;">{{ __('services_tracking.dashboard.certified_disbursement') }}</div>
                        <div class="op-kpi-val" style="color: #14532d;">
                            {{ number_format($disbursedAmount, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                        </div>
                    </div>
                </div>

                @if($isManaged)
                    <div class="op-kpi-grid" style="margin-top: -4px;">
                        <div class="op-kpi-box" style="background: {{ $remainingAmount <= 0.001 ? '#f0fdf4' : '#fff1f2' }}; border-color: {{ $remainingAmount <= 0.001 ? '#bbf7d0' : '#fecdd3' }};">
                            <div class="op-kpi-label" style="color: {{ $remainingAmount <= 0.001 ? '#166534' : '#be123c' }};">{{ __('financial.summary.client_remaining') }}</div>
                            <div class="op-kpi-val" style="color: {{ $remainingAmount <= 0.001 ? '#15803d' : '#9f1239' }};">
                                {{ number_format($remainingAmount, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        <div class="op-kpi-box" style="background: #ecfeff; border-color: #a5f3fc;">
                            <div class="op-kpi-label" style="color: #0e7490;">{{ __('financial.summary.surplus_refunded') }}</div>
                            <div class="op-kpi-val" style="color: #155e75;">
                                {{ number_format($surplusRefunded, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        <div class="op-kpi-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                            <div class="op-kpi-label" style="color: #166534;">{{ __('financial.summary.net_cash') }}</div>
                            <div class="op-kpi-val" style="color: #15803d;">
                                {{ number_format($netCash, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>

                        <div class="op-kpi-box" style="background: #f8fafc; border-color: #e2e8f0;">
                            <div class="op-kpi-label" style="color: #475569;">{{ __('financial.summary.employee_due') }}</div>
                            <div class="op-kpi-val" style="color: #0f172a;">
                                {{ number_format($employeeDue, 2) }} <span style="font-size: 11px; font-weight: 500;">{{ $currency }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Financial Flow Steps --}}
                <div class="op-trail-step">
                    <div>
                        <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                            {{ __('services_tracking.dashboard.step_1_title') }}
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            {{ __('services_tracking.dashboard.cash_label') }} — {{ $record->created_at?->format('Y-m-d') }} — {{ $responsibleEmployeeName }}
                        </div>
                    </div>
                    <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                        {{ __('services_tracking.dashboard.step_1_confirmed') }}
                    </span>
                </div>

                <div class="op-trail-step">
                    <div>
                        <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                            {{ __('services_tracking.dashboard.step_2_title') }}
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; max-width: 480px;">
                            {{ __('services_tracking.dashboard.step_2_desc') }}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($digitalSignatureCode)
                            <span style="font-family: monospace; font-weight: 700; font-size: 11px; background: #ffffff; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 4px;">
                                {{ __('services_tracking.dashboard.code_label') }}: {{ $digitalSignatureCode }}
                            </span>
                        @else
                            <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">
                                ({{ __('services_tracking.dashboard.waiting_signature') }})
                            </span>
                        @endif
                        <span style="background: {{ $isPaidToTreasury ? '#dcfce7' : '#f3e8ff' }}; color: {{ $isPaidToTreasury ? '#166534' : '#6b21a8' }}; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                            {{ $isPaidToTreasury ? __('services_tracking.dashboard.custody_received_treasury') : __('services_tracking.dashboard.custody_waiting_treasury') }}
                        </span>
                    </div>
                </div>

                <div class="op-trail-step">
                    <div>
                        <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                            {{ __('services_tracking.dashboard.step_3_title') }}
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            {{ __('services_tracking.dashboard.channel_label') }}: {{ !empty($meta['gov_channel']) ? $meta['gov_channel'] : __('services_tracking.disbursement.channel_sadad') }}
                            @if(!empty($meta['is_self_paid']))
                                — <strong style="color: #d97706;">{{ __('services_tracking.dashboard.self_paid_badge') }}</strong>
                            @endif
                        </div>
                    </div>
                    <span style="font-weight: 800; font-size: 13px; color: #0f172a;">
                        {{ number_format($disbursedAmount, 2) }} {{ $currency }}
                    </span>
                </div>

                @if($surplusRefunded > 0)
                    <div class="op-trail-step">
                        <div>
                            <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                                {{ __('financial.summary.step_surplus_title') }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                {{ __('financial.summary.surplus_refunded') }}: {{ number_format($surplusRefunded, 2) }} {{ $currency }}
                            </div>
                        </div>
                        <span style="background: #ecfeff; color: #0e7490; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                            {{ __('financial.summary.status_refunded') }}
                        </span>
                    </div>
                @endif

                {{-- Proofs Box --}}
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; background: #ffffff; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-weight: 700; font-size: 12px; color: #1e293b;">
                            {{ __('services_tracking.dashboard.step_4_title') }}
                        </div>
                        <span style="font-size: 10px; color: #64748b;">{{ __('services_tracking.dashboard.step_4_desc') }}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div style="background: {{ $govReceipt ? '#f0fdf4' : '#fafafa' }}; border: 1px {{ $govReceipt ? 'solid #bbf7d0' : 'dashed #cbd5e1' }}; border-radius: 8px; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; font-size: 11px; color: #1e293b;">{{ __('services_tracking.dashboard.gov_receipt') }}</div>
                                <div style="font-size: 10px; color: {{ $govReceipt ? '#16a34a' : '#dc2626' }};">
                                    {{ $govReceipt ? __('services_tracking.dashboard.gov_receipt_attached') : __('services_tracking.dashboard.gov_receipt_missing') }}
                                </div>
                            </div>
                            @if($govReceipt)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($govReceipt) }}" target="_blank" style="font-size: 11px; color: #2563eb; font-weight: 700; text-decoration: underline;">{{ __('services_tracking.dashboard.preview_btn') }}</a>
                            @endif
                        </div>

                        <div style="background: {{ $bankReceipt ? '#f0fdf4' : '#fafafa' }}; border: 1px {{ $bankReceipt ? 'solid #bbf7d0' : 'dashed #cbd5e1' }}; border-radius: 8px; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; font-size: 11px; color: #1e293b;">{{ __('services_tracking.dashboard.bank_receipt') }}</div>
                                <div style="font-size: 10px; color: {{ $bankReceipt ? '#16a34a' : '#64748b' }};">
                                    {{ $bankReceipt ? __('services_tracking.dashboard.bank_receipt_attached') : __('services_tracking.dashboard.bank_receipt_missing') }}
                                </div>
                            </div>
                            @if($bankReceipt)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($bankReceipt) }}" target="_blank" style="font-size: 11px; color: #2563eb; font-weight: 700; text-decoration: underline;">{{ __('services_tracking.dashboard.preview_btn') }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 5. Linked Receipts & Disbursements --}}
                @if($receipts->isNotEmpty() || $disbursements->isNotEmpty())
                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; background: #ffffff; margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div style="font-weight: 700; font-size: 12px; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #0284c7;"></span>
                                {{ __('financial.summary.recent_receipts') }}
                            </div>
                            <span style="font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">
                                {{ $receipts->count() }}
                            </span>
                        </div>

                        @if($receipts->isNotEmpty())
                            <div style="width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 12px;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 11px; text-align: right;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.receipt_number') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.fields.amount') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.fields.payment_method') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.collector') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.status') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($receipts as $rcp)
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
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 7px 10px; font-weight: 700; font-family: monospace;">{{ $rcp->receipt_number }}</td>
                                                <td style="padding: 7px 10px; font-weight: 800; color: #166534;">{{ number_format((float) $rcp->amount, 2) }} {{ $currency }}</td>
                                                <td style="padding: 7px 10px;">{{ $methodLabel }}</td>
                                                <td style="padding: 7px 10px;">{{ $rcp->collector?->user?->name ?? '—' }}</td>
                                                <td style="padding: 7px 10px;">
                                                    <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; background: {{ $statusKey === 'confirmed' ? '#dcfce7' : '#fef3c7' }}; color: {{ $statusKey === 'confirmed' ? '#166534' : '#b45309' }};">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td style="padding: 7px 10px; color: #64748b; font-family: monospace;">{{ $rcp->received_at?->format('Y-m-d') ?? $rcp->created_at?->format('Y-m-d') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($disbursements->isNotEmpty())
                            <div style="display: flex; justify-content: space-between; align-items: center; margin: 12px 0 8px 0;">
                                <div style="font-weight: 700; font-size: 12px; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #d97706;"></span>
                                    {{ __('financial.summary.recent_disbursements') }}
                                </div>
                                <span style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 6px;">
                                    {{ $disbursements->count() }}
                                </span>
                            </div>

                            <div style="width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 11px; text-align: right;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.disbursement_number') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.fields.amount') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.fields.disbursement_type') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.status') }}</th>
                                            <th style="padding: 7px 10px; font-weight: 700; color: #475569;">{{ __('financial.summary.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($disbursements as $dsb)
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
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 7px 10px; font-weight: 700; font-family: monospace;">{{ $dsb->disbursement_number }}</td>
                                                <td style="padding: 7px 10px; font-weight: 800; color: #dc2626;">{{ number_format((float) $dsb->amount, 2) }} {{ $currency }}</td>
                                                <td style="padding: 7px 10px;">{{ $typeLabel }}</td>
                                                <td style="padding: 7px 10px;">
                                                    <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; background: {{ in_array($dsbStatusKey, ['paid', 'fully_reimbursed']) ? '#dcfce7' : '#eff6ff' }}; color: {{ in_array($dsbStatusKey, ['paid', 'fully_reimbursed']) ? '#166534' : '#1e40af' }};">
                                                        {{ $dsbStatusLabel }}
                                                    </span>
                                                </td>
                                                <td style="padding: 7px 10px; color: #64748b; font-family: monospace;">{{ $dsb->created_at?->format('Y-m-d') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        </div>

    </div>

    {{-- ================= BOTTOM FULL-WIDTH: Operational Audit Trail ================= --}}
    <div class="op-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
            <div>
                <div class="op-header-title" style="margin-bottom: 0;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                    {{ __('services_tracking.dashboard.audit_trail') }}
                </div>
                <div class="op-header-subtitle" style="margin-bottom: 0;">
                    {{ __('services_tracking.dashboard.audit_trail_desc') }}
                </div>
            </div>
            <span style="font-size: 11px; color: #94a3b8;">{{ __('services_tracking.dashboard.latest_actions', ['count' => $auditLogs->count()]) }}</span>
        </div>

        @if($auditLogs->isEmpty())
            <div style="background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px; padding: 14px; text-align: center; color: #94a3b8; font-size: 11px;">
                {{ __('services_tracking.dashboard.no_actions_recorded') }}
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @foreach($auditLogs as $log)
                    @php
                        $actionKey = 'services_tracking.audit.action_'.$log->action_type;
                        $actionLabel = __($actionKey);
                        if ($actionLabel === $actionKey) {
                            $actionLabel = $log->action_type;
                        }
                    @endphp
                    <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 11px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                            <strong style="color: #0f172a;">{{ $actionLabel }}</strong>
                            <span style="color: #94a3b8;">—</span>
                            <span style="color: #64748b;">{{ $log->user?->name ?? __('services_tracking.audit.system') }}</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; font-family: monospace; color: #94a3b8; direction: ltr;">
                            <span>{{ $log->created_at?->format('Y-m-d H:i') }}</span>
                            @if($log->ip_address)
                                <span style="color: #cbd5e1;">({{ $log->ip_address }})</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
