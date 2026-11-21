@php
    $translateStatus = function(?string $status) {
        if (!$status) return null;
        $enum = \App\Enums\OperationWorkflowStatus::tryFrom($status);
        if ($enum) return $enum->getLabel();
        
        $approvalEnum = \App\Enums\ApprovalStatus::tryFrom($status);
        if ($approvalEnum) return $approvalEnum->getLabel();

        $key = 'services_tracking.workflow_statuses.' . $status;
        $trans = __($key);
        if ($trans !== $key) return $trans;

        $key2 = 'services_tracking.service_statuses.' . $status;
        $trans2 = __($key2);
        if ($trans2 !== $key2) return $trans2;

        return $status;
    };

    $payloadLabels = __('services_tracking.audit.payload_labels');
    if (!is_array($payloadLabels)) {
        $payloadLabels = [];
    }
@endphp

<div style="font-family: inherit; display: flex; flex-direction: column; gap: 12px;">
    @if($logs->isEmpty())
        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px; text-align: center; color: #64748b; font-size: 13px;">
            {{ __('services_tracking.audit.timeline_empty') }}
        </div>
    @else
        <div style="position: relative; padding-left: 6px; padding-right: 6px;">
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($logs as $log)
                    @php
                        $actionKey = 'services_tracking.audit.action_' . $log->action_type;
                        $actionLabel = __($actionKey);
                        if ($actionLabel === $actionKey) {
                            $actionLabel = match ($log->action_type) {
                                'created' => 'إنشاء المعاملة',
                                'custody_received' => 'استلام العهدة بالخزينة',
                                'proofs_attached' => 'إرفاق إثباتات السداد',
                                'disbursement_recorded' => 'توثيق السداد الحكومي',
                                'self_paid_approved' => 'اعتماد السداد الشخصي',
                                'batch_deposited' => 'توزيع إيداع بنكي مجمّع',
                                'reconciled' => 'فحص ومطابقة',
                                'task_resolved' => 'إغلاق مهمة نقيصة',
                                'escalated' => 'تصعيد للإدارة',
                                'escalation_resolved' => 'معالجة وفك التصعيد',
                                default => $log->action_type,
                            };
                        }
                        $oldStatusLabel = $translateStatus($log->old_status);
                        $newStatusLabel = $translateStatus($log->new_status);
                    @endphp
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="background: #f1f5f9; color: #1e293b; font-weight: 700; font-size: 12px; padding: 3px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    {{ $actionLabel }}
                                </span>
                                @if($oldStatusLabel || $newStatusLabel)
                                    <span style="font-size: 11px; color: #64748b;">
                                        {{ $oldStatusLabel ?? '—' }} &larr; <strong style="color: #0f172a;">{{ $newStatusLabel }}</strong>
                                    </span>
                                @endif
                            </div>

                            <span style="font-size: 11px; color: #94a3b8; white-space: nowrap; direction: ltr;">
                                {{ $log->created_at?->format('Y-m-d H:i') }}
                            </span>
                        </div>

                        {{-- Payload summary in Arabic badges instead of raw JSON --}}
                        @if(!empty($log->payload) && is_array($log->payload))
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; margin-bottom: 6px; background: #f8fafc; border: 1px solid #f1f5f9; padding: 6px 10px; border-radius: 8px; font-size: 11px;">
                                @foreach($log->payload as $k => $v)
                                    @php
                                        $kLabel = $payloadLabels[$k] ?? match ($k) {
                                            'amount' => 'المبلغ',
                                            'channel' => 'قناة السداد',
                                            'is_self_paid' => 'سداد شخصي',
                                            'has_gov_receipt' => 'إيصال حكومي',
                                            'has_bank_receipt' => 'إيصال بنكي',
                                            'batch_deposit_number' => 'رقم الإيداع',
                                            'allocated_amount' => 'المبلغ المخصص',
                                            'bank_name' => 'البنك',
                                            'reason' => 'السبب',
                                            'resolution_notes' => 'ملاحظات الحل',
                                            'receipt_note' => 'ملاحظة الاستلام',
                                            'scope_type' => 'نطاق الخدمة',
                                            'total_amount' => 'إجمالي المبلغ',
                                            'paid_amount' => 'المدفوع',
                                            'remaining_amount' => 'المتبقي',
                                            'treasury_transaction_id' => 'رقم حركة الخزينة',
                                            'approved_amount' => 'المبلغ المعتمد',
                                            'approval_note' => 'ملاحظة الاعتماد',
                                            'task_code' => 'كود المهمة',
                                            'deficiency_type' => 'نوع النقيصة',
                                            default => $k,
                                        };

                                        if (is_bool($v)) {
                                            if ($k === 'is_self_paid') {
                                                $vLabel = $v ? 'نعم (مستحق للموظف)' : 'لا (من أموال المؤسسة)';
                                            } else {
                                                $vLabel = $v ? 'نعم' : 'لا';
                                            }
                                        } elseif ($k === 'scope_type') {
                                            $vLabel = match ((string) $v) {
                                                'internal' => 'خدمة داخلية (منشأة)',
                                                'external' => 'خدمة خارجية (تسجيل حر)',
                                                default => (string) $v,
                                            };
                                        } elseif ($k === 'channel') {
                                            $vLabel = match ((string) $v) {
                                                'sadad' => 'نظام سداد / فواتير حكومية',
                                                'mada' => 'بطاقة مدى / البطاقة الائتمانية',
                                                'qiwa' => 'منصة قوى',
                                                'absher' => 'أبشر أعمال / مقيم',
                                                'balady' => 'منصة بلدي',
                                                'cash' => 'نقداً',
                                                'bank_transfer' => 'تحويل بنكي',
                                                default => (string) $v,
                                            };
                                        } elseif ($k === 'deficiency_type') {
                                            $vLabel = match ((string) $v) {
                                                'missing_gov_receipt' => 'نقص إيصال سداد حكومي',
                                                'missing_bank_deposit' => 'نقص إيصال إيداع بنكي',
                                                'amount_mismatch' => 'عدم تطابق في المبالغ',
                                                'unsettled_custody' => 'عهدة غير مسواة',
                                                default => (string) $v,
                                            };
                                        } elseif (is_numeric($v) && in_array($k, ['amount', 'allocated_amount', 'total_amount', 'paid_amount', 'remaining_amount', 'net_amount'], true)) {
                                            $vLabel = number_format((float) $v, 2) . ' ر.س';
                                        } elseif (in_array($k, ['status', 'workflow_status', 'old_status', 'new_status'], true)) {
                                            $vLabel = $translateStatus((string) $v);
                                        } elseif (is_array($v)) {
                                            $vLabel = json_encode($v, JSON_UNESCAPED_UNICODE);
                                        } else {
                                            $vLabel = (string) $v;
                                        }
                                    @endphp
                                    <span style="background: #ffffff; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px; color: #334155;">
                                        <strong style="color: #64748b;">{{ $kLabel }}:</strong> {{ $vLabel }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #64748b; margin-top: 6px; border-top: 1px solid #f8fafc; padding-top: 6px;">
                            <div>
                                <span style="font-weight: 600;">{{ __('services_tracking.audit.user') }}:</span>
                                <span style="color: #334155;">{{ $log->user?->name ?? __('services_tracking.audit.system') }}</span>
                            </div>
                            @if($log->ip_address)
                                <span style="color: #94a3b8; font-size: 10px; direction: ltr;">{{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
