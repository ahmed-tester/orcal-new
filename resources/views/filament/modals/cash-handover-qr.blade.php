<div style="display: flex; flex-direction: column; gap: 16px;">

    @foreach ($cards as $card)
        @php
            $handover = $card['handover'];
        @endphp

        <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc; display: flex; flex-wrap: wrap; gap: 14px; align-items: center;">

            {{-- QR code card: carries the handover number only --}}
            <div style="flex: 0 0 auto; width: 190px; text-align: center;">
                {!! $card['qrSvg'] !!}
            </div>

            {{-- Handover identification --}}
            <div style="flex: 1; min-width: 220px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">
                    {{ __('financial.fields.handover_receipts') }}
                </div>
                <div style="font-size: 16px; font-weight: 800; direction: ltr; text-align: start; margin-top: 2px;">
                    {{ $handover->handover_number }}
                </div>

                <div style="margin-top: 6px; font-size: 12px; color: #475569;">
                    {{ __('financial.fields.amount') }}:
                    <span style="font-weight: 700; direction: ltr; display: inline-block;">
                        {{ number_format((float) $handover->total_requested_amount, 2) }}
                    </span>
                    {{ __('services_tracking.currency_sar') }}
                </div>

                @if($card['signatureCode'] !== null)
                    <div style="margin-top: 8px; font-size: 12px;">
                        {{ __('financial.fields.handover_signature') }}
                        <strong dir="ltr" style="display: inline-block; letter-spacing: 2px;">{{ $card['signatureCode'] }}</strong>
                    </div>
                @endif

                <div style="margin-top: 8px; padding: 6px 10px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 11px; color: #475569;">
                    {{ __('financial.handover_qr.scan_hint') }}
                </div>
            </div>
        </div>
    @endforeach

</div>
