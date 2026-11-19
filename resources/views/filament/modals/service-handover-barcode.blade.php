@php
    $isReceived = ($record->metadata['custody_status'] ?? '') === 'in_treasury';
    $isSettled = ($record->metadata['custody_status'] ?? '') === 'settled';
    $amountFormatted = number_format($record->paid_amount, 2);
@endphp

<div style="font-family: inherit;">

    {{-- Wide 2-column landscape layout --}}
    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: stretch;">

        {{-- Column 1: QR code card and transaction code --}}
        <div style="flex: 1 1 300px; min-width: 280px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            
            <div style="margin-bottom: 8px; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e2e8f0; border-radius: 9999px; font-size: 11px; font-weight: 700; color: #475569;">
                <svg style="width: 13px; height: 13px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                {{ __('services_tracking.custody.voucher_badge') }}
            </div>

            <div style="background: #ffffff; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; width: 100%; max-width: 280px; margin: 6px auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                {!! $barcodeSvg !!}
            </div>

            {{-- Encrypted scan notice (plain text & PIN are hidden, retrieved solely via QR) --}}
            <div style="margin-top: 10px; padding: 8px 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 11px; color: #475569; display: flex; align-items: center; justify-content: center; gap: 6px; text-align: center;">
                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: #0284c7;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span>{{ __('services_tracking.custody.qr_only_secure_hint') }}</span>
            </div>
        </div>

        {{-- Column 2: Amount, data and guidance --}}
        <div style="flex: 2 1 360px; min-width: 280px; display: flex; flex-direction: column; gap: 10px; justify-content: space-between;">
            
            {{-- Cash amount highlight card --}}
            <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; padding: 12px 16px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 3px 8px rgba(5, 150, 105, 0.2);">
                <div>
                    <span style="font-size: 12px; opacity: 0.95; display: block; font-weight: 600;">{{ __('services_tracking.custody.amount_to_handover') }}</span>
                    <span style="font-size: 10px; opacity: 0.8;">{{ __('services_tracking.custody.custody_in_pocket') }}</span>
                </div>
                <div style="direction: ltr; text-align: left;">
                    <span style="font-size: 24px; font-weight: 900; letter-spacing: -0.5px;">{{ $amountFormatted }}</span>
                    <span style="font-size: 12px; font-weight: 700; margin-left: 3px; opacity: 0.9;">{{ __('services_tracking.custody.currency_sar') }}</span>
                </div>
            </div>

            {{-- 2x2 data grid --}}
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;">
                
                {{-- Service name --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 2px;">
                        {{ __('services_tracking.fields.service') }}
                    </div>
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $record->display_service_name }}">
                        {{ $record->display_service_name }}
                    </div>
                </div>

                {{-- Beneficiary / company --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 2px;">
                        {{ __('services_tracking.custody.beneficiary_label') }}
                    </div>
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $record->display_beneficiary }}">
                        {{ $record->display_beneficiary }}
                    </div>
                </div>

                {{-- Custody holder employee --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 2px;">
                        {{ __('services_tracking.custody.employee_holder') }}
                    </div>
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $record->executor?->user?->name ?? __('services_tracking.custody.cash_payer') }}
                    </div>
                </div>

                {{-- Current custody status --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 2px;">
                        {{ __('services_tracking.custody.custody_status_label') }}
                    </div>
                    <div>
                        @if($isReceived || $isSettled)
                            <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #15803d;">
                                ✓ {{ __('services_tracking.custody.status_in_treasury') }}
                            </span>
                        @else
                            <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #b45309;">
                                ⏳ {{ __('services_tracking.custody.status_on_employee') }}
                            </span>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Bottom guidance banner --}}
            @if($isReceived || $isSettled)
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 10px; padding: 10px 12px; font-size: 11px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0; color: #059669;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ __('services_tracking.custody.received_banner') }}</span>
                </div>
            @else
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 10px; padding: 10px 12px; font-size: 11px; display: flex; align-items: flex-start; gap: 8px; line-height: 1.4;">
                    <span style="font-size: 14px; flex-shrink: 0; line-height: 1;">💡</span>
                    <div>
                        <strong>{{ __('services_tracking.custody.treasury_guide_title') }}</strong>
                        {{ __('services_tracking.custody.treasury_guide_text') }}
                    </div>
                </div>
            @endif

        </div>

    </div>

</div>
