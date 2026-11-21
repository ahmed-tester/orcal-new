@php
    use App\Enums\Mail\MailAccountStatus;
@endphp

<div style="border: 1px solid var(--gray-200, #e2e8f0); border-radius: 12px; padding: 20px; line-height: 1.9; font-size: 0.875rem;">
    @if ($provider === 'outlook')
        @if (! ($microsoftConfigured ?? false))
            <div style="padding: 12px 16px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; margin-bottom: 12px; font-weight: 600;">
                {{ __('mail_inbox.oauth.site_not_configured') }}
            </div>
            @if ($canConfigure ?? false)
                <p style="margin: 0 0 12px; color: #475569;">
                    {{ __('mail_inbox.oauth.site_config_admin_hint') }}
                </p>
            @endif
        @elseif ($record && ($isVerifying ?? false))
            <div style="padding: 12px 16px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; margin-bottom: 12px; font-weight: 600;">
                {{ __('mail_inbox.oauth.verifying_syncing') }}
            </div>
        @elseif ($record && $record->hasOAuthTokens() && $record->status === MailAccountStatus::CONNECTED)
            <div style="padding: 12px 16px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; margin-bottom: 12px; font-weight: 600;">
                {{ __('mail_inbox.oauth.connected_badge') }}
            </div>
        @elseif ($record && $record->status === MailAccountStatus::REAUTH_REQUIRED)
            <div style="padding: 12px 16px; border-radius: 8px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; margin-bottom: 12px; font-weight: 600;">
                {{ __('mail_inbox.oauth.reauth_needed') }}
            </div>
        @endif
    @endif

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
