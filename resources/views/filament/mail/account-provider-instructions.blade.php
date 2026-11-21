<div style="border: 1px solid var(--gray-200, #e2e8f0); border-radius: 12px; padding: 20px; line-height: 1.9; font-size: 0.875rem;">
    <p style="font-weight: 700; margin: 0 0 12px; font-size: 1rem;">
        {{ __('mail_inbox.provider_setup.'.$provider.'.heading') }}
    </p>
    <ol style="list-style: decimal; padding-inline-start: 24px; margin: 0;">
        @foreach (__('mail_inbox.provider_setup.'.$provider.'.steps') as $step)
            <li style="margin-block: 6px;">{{ $step }}</li>
        @endforeach
    </ol>
    @if ($provider !== 'custom')
        <p style="margin: 12px 0 0;">
            <a href="{{ __('mail_inbox.provider_setup.'.$provider.'.link_url') }}" target="_blank" rel="noopener noreferrer"
               style="color: var(--primary-600, #2563eb); font-weight: 600; text-decoration: underline;">
                {{ __('mail_inbox.provider_setup.'.$provider.'.link_label') }} ↗
            </a>
        </p>
    @endif
</div>
