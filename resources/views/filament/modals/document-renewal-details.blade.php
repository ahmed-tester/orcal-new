@php
    $meta = is_array($record->metadata) ? $record->metadata : [];
    $history = is_array($meta['submission_history'] ?? null) ? $meta['submission_history'] : [];
    $docType = (string) ($meta['document_type'] ?? '');
    $docTypeLabel = \App\Services\Tasks\Renewals\RenewalDocumentRegistry::isValidType($docType)
        ? \App\Services\Tasks\Renewals\RenewalDocumentRegistry::label($docType)
        : ($docType ?: '—');
    $statusLabel = $record->status ? __('document_renewals.statuses.'.$record->status) : '—';
    $feeMode = $meta['renewal_fee_mode'] ?? null;
    $feeText = $feeMode === \App\Models\Invoice::RENEWAL_FEE_NO_FEE
        ? __('document_renewals.invoice.no_fee_badge')
        : (isset($meta['latest_submission']['amount']) ? number_format((float) $meta['latest_submission']['amount'], 2) : '—');
@endphp

<div style="display:flex; flex-direction:column; gap:14px; font-size:0.875rem; line-height:1.5;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid rgba(148,163,184,0.28); background:rgba(148,163,184,0.08);">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-weight:700; font-size:0.95rem;">{{ $record->task_code }}</span>
            <span style="padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600; background:rgba(59,130,246,0.14); color:#2563eb;">
                {{ $docTypeLabel }}
            </span>
        </div>
        <span style="padding:3px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600; background:rgba(245,158,11,0.16); color:#b45309;">
            {{ $statusLabel }}
        </span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px;">
        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.company') }}</div>
            <div style="font-weight:600;">{{ $record->company?->trade_name ?? '—' }}</div>
        </div>

        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.target') }}</div>
            <div style="font-weight:600;">{{ $meta['source_label'] ?? '—' }}</div>
        </div>

        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.cycle_expiry') }}</div>
            <div style="font-weight:600;">{{ $meta['cycle_expiry_date'] ?? '—' }}</div>
        </div>

        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.proposed_expiry') }}</div>
            <div style="font-weight:600;">{{ $meta['applied_expiry_date'] ?? $meta['proposed_new_expiry_date'] ?? '—' }}</div>
        </div>

        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.due_date') }}</div>
            <div style="font-weight:600;">{{ $record->due_date?->format('Y-m-d') ?? '—' }}</div>
        </div>

        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.assignee') }}</div>
            <div style="font-weight:600;">{{ $record->responsibleEmployee?->user?->name ?? '—' }}</div>
        </div>

        <div style="grid-column:1 / -1; padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
            <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.columns.fee_summary') }}</div>
            <div style="font-weight:600;">{{ $feeText }}</div>
        </div>

        @if(! empty($meta['latest_submission']['notes']))
            <div style="grid-column:1 / -1; padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25);">
                <div style="font-size:0.75rem; opacity:0.7; margin-bottom:2px;">{{ __('document_renewals.fields.notes') }}</div>
                <div style="font-weight:500;">{{ $meta['latest_submission']['notes'] }}</div>
            </div>
        @endif
    </div>

    @if(! empty($meta['latest_return_reason']))
        <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(239,68,68,0.4); background:rgba(239,68,68,0.08);">
            <div style="font-weight:700; color:#dc2626; margin-bottom:2px;">{{ __('document_renewals.fields.return_reason') }}</div>
            <div>{{ $meta['latest_return_reason'] }}</div>
        </div>
    @endif

    @if($history !== [])
        <div>
            <div style="font-weight:700; margin-bottom:8px;">{{ __('document_renewals.details.submission_history') }}</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach(array_reverse($history) as $entry)
                    <div style="padding:10px 12px; border-radius:8px; border:1px solid rgba(148,163,184,0.25); background:rgba(148,163,184,0.04);">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px;">
                            <span style="font-weight:600;">
                                #{{ $entry['version'] ?? '?' }} — {{ ($entry['fee_mode'] ?? '') === 'no_fee' ? __('document_renewals.fee_modes.no_fee') : number_format((float) ($entry['amount'] ?? 0), 2) }}
                            </span>
                            <span style="font-size:0.75rem; opacity:0.7;">{{ $entry['submitted_at'] ?? '' }}</span>
                        </div>
                        <div style="margin-top:4px; font-size:0.8rem; opacity:0.85;">
                            {{ __('document_renewals.fields.new_expiry_date') }}: {{ $entry['new_expiry_date'] ?? '—' }}
                            @if(! empty($entry['notes']))
                                · {{ __('document_renewals.fields.notes') }}: {{ $entry['notes'] }}
                            @endif
                            @if(! empty($entry['return_reason']))
                                · {{ __('document_renewals.fields.return_reason') }}: {{ $entry['return_reason'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
